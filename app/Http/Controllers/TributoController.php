<?php

namespace App\Http\Controllers;

use App\Http\Requests\TributoRequest;
use App\Models\Empresa;
use App\Models\Tributo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TributoController extends Controller
{
    public function index(): View
    {
        $empresa = Empresa::atual();

        $tributos = Tributo::query()
            ->where('empresa_id', $empresa?->id)
            ->orderBy('sigla')
            ->get();

        return view('tributos.index', [
            'empresa' => $empresa,
            'tributos' => $tributos,
            'cargaProduto' => $tributos->where('ativo', true)
                ->whereIn('aplica_a', ['produto', 'ambos'])
                ->sum(fn (Tributo $t): float => (float) $t->aliquota_efetiva),
            'cargaServico' => $tributos->where('ativo', true)
                ->whereIn('aplica_a', ['servico', 'ambos'])
                ->sum(fn (Tributo $t): float => (float) $t->aliquota_efetiva),
        ]);
    }

    public function create(): View
    {
        return view('tributos.form', [
            'tributo' => new Tributo(['ativo' => true, 'aplica_a' => 'ambos']),
        ]);
    }

    public function store(TributoRequest $request): RedirectResponse
    {
        Tributo::create($request->validated() + ['empresa_id' => Empresa::atual()?->id]);

        return redirect()->route('tributos.index')->with('sucesso', 'Tributo cadastrado.');
    }

    public function edit(Tributo $tributo): View
    {
        return view('tributos.form', ['tributo' => $tributo]);
    }

    public function update(TributoRequest $request, Tributo $tributo): RedirectResponse
    {
        $tributo->update($request->validated());

        return redirect()->route('tributos.index')->with('sucesso', 'Tributo atualizado.');
    }

    public function destroy(Tributo $tributo): RedirectResponse
    {
        $tributo->delete();

        return redirect()->route('tributos.index')->with('sucesso', 'Tributo removido.');
    }
}
