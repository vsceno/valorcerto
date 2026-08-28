<?php

namespace App\Http\Controllers;

use App\Exceptions\PrecificacaoInviavelException;
use App\Http\Requests\CalcularPrecoRequest;
use App\Models\Empresa;
use App\Models\Item;
use App\Models\Precificacao;
use App\Models\Tributo;
use App\Services\PrecificacaoService;
use App\Support\ReformaTributaria;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PrecificacaoController extends Controller
{
    public function __construct(private readonly PrecificacaoService $servico) {}

    /**
     * Simulador: mostra o preço sendo construído passo a passo.
     */
    public function simulador(Request $request): View
    {
        $empresa = Empresa::atual();

        $itens = Item::query()
            ->where('empresa_id', $empresa?->id)
            ->ativos()
            ->orderBy('nome')
            ->get();

        $item = $request->filled('item')
            ? $itens->firstWhere('id', (int) $request->integer('item'))
            : $itens->first();

        // A ficha técnica alimenta o custo variável e é exibida ao lado do cálculo.
        $item?->load('composicoes.insumo');

        $resultado = null;
        $erro = null;

        if ($item) {
            try {
                $resultado = $this->servico->calcularParaItem($item);
            } catch (PrecificacaoInviavelException $e) {
                $erro = $e->getMessage();
            }
        }

        // Comparação com o cenário pós-reforma, quando houver tributos com
        // vigência futura cadastrados.
        $comparacao = null;
        $dataReforma = Carbon::create(ReformaTributaria::ANO_CBS_INTEGRAL, 1, 1)->startOfDay();

        if ($item && $resultado && $dataReforma->isFuture() && $this->temTributosFuturos($empresa, $dataReforma)) {
            try {
                $comparacao = $this->servico->compararCenarios($item, $dataReforma);
            } catch (PrecificacaoInviavelException) {
                $comparacao = null;
            }
        }

        return view('precificacao.simulador', [
            'empresa' => $empresa,
            'itens' => $itens,
            'item' => $item,
            'resultado' => $resultado,
            'erro' => $erro,
            'comparacao' => $comparacao,
            'dataReforma' => $dataReforma,
            'custoFixoTotal' => $empresa?->custoFixoTotalMensal() ?? 0.0,
            'tributosProduto' => $this->tributosDoTipo($empresa, 'produto'),
            'tributosServico' => $this->tributosDoTipo($empresa, 'servico'),
        ]);
    }

    /**
     * Recalcula em tempo real conforme o usuário mexe nos campos.
     */
    public function calcular(CalcularPrecoRequest $request): JsonResponse
    {
        $item = Item::query()->findOrFail($request->integer('item_id'));

        try {
            $resultado = $this->servico->calcularParaItem(
                item: $item,
                margemContribuicao: (float) $request->input('margem_contribuicao'),
                custoVariavelUnitario: (float) $request->input('custo_variavel_unitario'),
                volumeProjetado: (float) $request->input('volume_projetado'),
            );
        } catch (PrecificacaoInviavelException $e) {
            return response()->json(['erro' => $e->getMessage()], 422);
        }

        return response()->json($resultado->toArray());
    }

    /**
     * Registra o cálculo no histórico imutável de auditoria.
     */
    public function store(CalcularPrecoRequest $request): RedirectResponse
    {
        $item = Item::query()->findOrFail($request->integer('item_id'));

        try {
            $resultado = $this->servico->calcularParaItem(
                item: $item,
                margemContribuicao: (float) $request->input('margem_contribuicao'),
                custoVariavelUnitario: (float) $request->input('custo_variavel_unitario'),
                volumeProjetado: (float) $request->input('volume_projetado'),
            );
        } catch (PrecificacaoInviavelException $e) {
            return back()->withInput()->withErrors(['margem_contribuicao' => $e->getMessage()]);
        }

        $precificacao = $this->servico->registrar(
            resultado: $resultado,
            item: $item,
            justificativa: $request->input('justificativa'),
            observacoes: $request->input('observacoes'),
            userId: $request->user()?->id,
        );

        return redirect()
            ->route('precificacao.show', $precificacao)
            ->with('sucesso', 'Precificação registrada. A memória de cálculo ficou disponível para auditoria.');
    }

    /**
     * Histórico de preços registrados.
     */
    public function index(Request $request): View
    {
        $empresa = Empresa::atual();

        $precificacoes = Precificacao::query()
            ->where('empresa_id', $empresa?->id)
            ->with('item')
            ->when($request->filled('item'), fn ($q) => $q->where('item_id', $request->integer('item')))
            ->when($request->filled('busca'), function ($q) use ($request): void {
                $busca = $request->string('busca')->toString();
                $q->where(function ($sub) use ($busca): void {
                    $sub->where('item_nome', 'like', "%{$busca}%")
                        ->orWhere('item_sku', 'like', "%{$busca}%");
                });
            })
            ->recentes()
            ->paginate(15)
            ->withQueryString();

        return view('precificacao.index', [
            'precificacoes' => $precificacoes,
            'busca' => $request->string('busca')->toString(),
        ]);
    }

    /**
     * Memória de cálculo completa de um registro.
     */
    public function show(Precificacao $precificacao): View
    {
        $precificacao->load(['item', 'user', 'empresa']);

        $anterior = Precificacao::query()
            ->where('item_id', $precificacao->item_id)
            ->where('calculado_em', '<', $precificacao->calculado_em)
            ->recentes()
            ->first();

        return view('precificacao.show', [
            'precificacao' => $precificacao,
            'anterior' => $anterior,
            'integra' => $precificacao->hash_auditoria !== null,
        ]);
    }

    /**
     * Existe tributo que só passa a valer na data futura informada?
     */
    private function temTributosFuturos(?Empresa $empresa, Carbon $data): bool
    {
        return Tributo::query()
            ->where('empresa_id', $empresa?->id)
            ->ativos()
            ->whereNotNull('vigencia_inicio')
            ->where('vigencia_inicio', '>', now())
            ->where('vigencia_inicio', '<=', $data)
            ->exists();
    }

    /**
     * @return Collection<int, Tributo>
     */
    private function tributosDoTipo(?Empresa $empresa, string $tipo)
    {
        return Tributo::query()
            ->where('empresa_id', $empresa?->id)
            ->ativos()
            ->aplicaveisA($tipo)
            ->orderBy('sigla')
            ->get();
    }
}
