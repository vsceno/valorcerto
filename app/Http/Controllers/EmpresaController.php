<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmpresaRequest;
use App\Models\Empresa;
use App\Models\Tributo;
use App\Support\RegimeTributario;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index(): View
    {
        return view('empresa.index', [
            'empresas' => Empresa::query()
                ->withCount(['itens', 'tributos', 'precificacoes'])
                ->orderBy('razao_social')
                ->get(),
            'atual' => Empresa::atual(),
        ]);
    }

    public function create(): View
    {
        return view('empresa.form', [
            'empresa' => new Empresa([
                'ativo' => true,
                'atividade' => 'comercio',
                'regime_tributario' => 'simples_nacional',
                'volume_projetado_mensal' => 1,
                'regime_vigente_desde' => now()->startOfYear(),
            ]),
        ]);
    }

    public function store(EmpresaRequest $request): RedirectResponse
    {
        $empresa = Empresa::create($request->validated());

        // Passa a operar sobre a empresa recém-cadastrada.
        session(['empresa_id' => $empresa->id]);

        return redirect()
            ->route('empresa.tributos-sugeridos', $empresa)
            ->with('sucesso', 'Empresa cadastrada. Confira os tributos que o regime escolhido comporta.');
    }

    public function edit(Empresa $empresa): View
    {
        return view('empresa.form', ['empresa' => $empresa]);
    }

    public function update(EmpresaRequest $request, Empresa $empresa): RedirectResponse
    {
        $regimeAnterior = $empresa->regime_tributario;
        $atividadeAnterior = $empresa->atividade;

        $empresa->update($request->validated());

        // Mudar de regime ou de atividade muda o conjunto de tributos válidos.
        if ($regimeAnterior !== $empresa->regime_tributario || $atividadeAnterior !== $empresa->atividade) {
            return redirect()
                ->route('empresa.tributos-sugeridos', $empresa)
                ->with('sucesso', 'Empresa atualizada. O regime mudou — revise os tributos.');
        }

        return redirect()->route('empresa.index')->with('sucesso', 'Empresa atualizada.');
    }

    public function destroy(Request $request, Empresa $empresa): RedirectResponse
    {
        if (Empresa::query()->where('ativo', true)->count() <= 1) {
            return back()->with('erro', 'Esta é a única empresa ativa. O sistema precisa de ao menos uma para calcular preços.');
        }

        if ($empresa->precificacoes()->exists()) {
            return back()->with('erro', 'Esta empresa possui precificações registradas. Desative-a em vez de excluir, para preservar a auditoria.');
        }

        if ((int) $request->session()->get('empresa_id') === $empresa->id) {
            $request->session()->forget('empresa_id');
        }

        $empresa->delete();

        return redirect()->route('empresa.index')->with('sucesso', 'Empresa removida.');
    }

    /**
     * Troca a empresa sobre a qual o sistema opera.
     */
    public function selecionar(Request $request, Empresa $empresa): RedirectResponse
    {
        abort_unless($empresa->ativo, 403, 'Empresa inativa não pode ser selecionada.');

        $request->session()->put('empresa_id', $empresa->id);

        return back()->with('sucesso', sprintf(
            'Operando agora sobre %s.',
            $empresa->nome_fantasia ?: $empresa->razao_social
        ));
    }

    /**
     * Mostra os tributos que o regime comporta e o que está cadastrado hoje.
     */
    public function tributosSugeridos(Empresa $empresa): View
    {
        $sugeridos = RegimeTributario::tributosSugeridos(
            $empresa->regime_tributario,
            $empresa->atividade ?? 'comercio'
        );

        $cadastrados = $empresa->tributos()->orderBy('sigla')->get()->keyBy('sigla');

        return view('empresa.tributos-sugeridos', [
            'empresa' => $empresa,
            'sugeridos' => $sugeridos,
            'cadastrados' => $cadastrados,
            'incompativeis' => $empresa->tributosIncompativeis(),
        ]);
    }

    /**
     * Cria os tributos do regime que ainda não existem, sem tocar nos já
     * cadastrados — as alíquotas ajustadas pelo usuário são preservadas.
     */
    public function aplicarSugestao(Empresa $empresa): RedirectResponse
    {
        $sugeridos = RegimeTributario::tributosSugeridos(
            $empresa->regime_tributario,
            $empresa->atividade ?? 'comercio'
        );

        $existentes = $empresa->tributos()->pluck('sigla')->all();
        $criados = 0;

        foreach ($sugeridos as $tributo) {
            if (in_array($tributo['sigla'], $existentes, true)) {
                continue;
            }

            Tributo::create($tributo + [
                'empresa_id' => $empresa->id,
                'ativo' => true,
            ]);

            $criados++;
        }

        return redirect()->route('tributos.index')->with(
            'sucesso',
            $criados > 0
                ? "{$criados} tributo(s) do regime cadastrados. Ajuste as alíquotas efetivas com sua contabilidade."
                : 'Todos os tributos do regime já estavam cadastrados.'
        );
    }
}
