<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsumoRequest;
use App\Models\Empresa;
use App\Models\Insumo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InsumoController extends Controller
{
    public function index(Request $request): View
    {
        $insumos = Insumo::query()
            ->where('empresa_id', Empresa::atual()?->id)
            ->withCount('composicoes')
            ->when($request->filled('grupo'), fn ($q) => $q->where('grupo', $request->string('grupo')->toString()))
            ->when($request->filled('busca'), function ($q) use ($request): void {
                $busca = $request->string('busca')->toString();
                $q->where(function ($sub) use ($busca): void {
                    $sub->where('nome', 'like', "%{$busca}%")
                        ->orWhere('codigo', 'like', "%{$busca}%")
                        ->orWhere('fornecedor', 'like', "%{$busca}%");
                });
            })
            ->orderBy('grupo')
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('insumos.index', [
            'insumos' => $insumos,
            'busca' => $request->string('busca')->toString(),
            'grupoSelecionado' => $request->string('grupo')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('insumos.form', [
            'insumo' => new Insumo([
                'ativo' => true,
                'grupo' => 'outros',
                'unidade_compra' => 'UN',
                'unidade_uso' => 'UN',
                'rendimento' => 1,
                'perda_percentual' => 0,
            ]),
        ]);
    }

    public function store(InsumoRequest $request): RedirectResponse
    {
        Insumo::create($request->validated() + ['empresa_id' => Empresa::atual()?->id]);

        return redirect()->route('insumos.index')->with('sucesso', 'Insumo cadastrado.');
    }

    public function edit(Insumo $insumo): View
    {
        return view('insumos.form', ['insumo' => $insumo]);
    }

    public function update(InsumoRequest $request, Insumo $insumo): RedirectResponse
    {
        $insumo->update($request->validated());

        $usadoEm = $insumo->composicoes()->count();

        return redirect()->route('insumos.index')->with(
            'sucesso',
            $usadoEm > 0
                ? "Insumo atualizado. O custo de {$usadoEm} ".($usadoEm === 1 ? 'produto foi recalculado' : 'produtos foi recalculado').' na próxima precificação.'
                : 'Insumo atualizado.'
        );
    }

    public function destroy(Insumo $insumo): RedirectResponse
    {
        $usadoEm = $insumo->composicoes()->count();

        if ($usadoEm > 0) {
            return back()->with('erro', sprintf(
                'Este insumo está em %d ficha(s) técnica(s). Remova-o dessas fichas antes de excluí-lo.',
                $usadoEm
            ));
        }

        $insumo->delete();

        return redirect()->route('insumos.index')->with('sucesso', 'Insumo removido.');
    }
}
