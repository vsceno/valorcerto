<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustoFixoRequest;
use App\Models\CustoFixo;
use App\Models\Empresa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CustoFixoController extends Controller
{
    public function index(): View
    {
        $empresa = Empresa::atual();

        $custos = CustoFixo::query()
            ->where('empresa_id', $empresa?->id)
            ->orderBy('grupo')
            ->orderByDesc('valor_mensal')
            ->get();

        return view('custos-fixos.index', [
            'empresa' => $empresa,
            'custos' => $custos,
            'total' => $custos->where('ativo', true)->sum(fn (CustoFixo $c): float => (float) $c->valor_mensal),
            'porGrupo' => $custos->where('ativo', true)
                ->groupBy('grupo')
                ->map(fn ($grupo): float => $grupo->sum(fn (CustoFixo $c): float => (float) $c->valor_mensal))
                ->sortDesc(),
        ]);
    }

    public function create(): View
    {
        return view('custos-fixos.form', [
            'custo' => new CustoFixo(['ativo' => true, 'grupo' => 'outros']),
        ]);
    }

    public function store(CustoFixoRequest $request): RedirectResponse
    {
        CustoFixo::create($request->validated() + ['empresa_id' => Empresa::atual()?->id]);

        return redirect()->route('custos-fixos.index')
            ->with('sucesso', 'Custo fixo cadastrado. O rateio dos próximos cálculos já considera este valor.');
    }

    public function edit(CustoFixo $custosFixo): View
    {
        return view('custos-fixos.form', ['custo' => $custosFixo]);
    }

    public function update(CustoFixoRequest $request, CustoFixo $custosFixo): RedirectResponse
    {
        $custosFixo->update($request->validated());

        return redirect()->route('custos-fixos.index')->with('sucesso', 'Custo fixo atualizado.');
    }

    public function destroy(CustoFixo $custosFixo): RedirectResponse
    {
        $custosFixo->delete();

        return redirect()->route('custos-fixos.index')->with('sucesso', 'Custo fixo removido.');
    }
}
