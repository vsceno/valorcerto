<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmpresaRequest;
use App\Models\Empresa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmpresaController extends Controller
{
    public function edit(): View
    {
        return view('empresa.edit', [
            'empresa' => Empresa::atual() ?? new Empresa(['volume_projetado_mensal' => 1, 'ativo' => true]),
        ]);
    }

    public function update(EmpresaRequest $request): RedirectResponse
    {
        $empresa = Empresa::atual();

        if ($empresa) {
            $empresa->update($request->validated());
        } else {
            $empresa = Empresa::create($request->validated() + ['ativo' => true]);
        }

        return redirect()
            ->route('empresa.edit')
            ->with('sucesso', 'Dados da empresa atualizados. Os próximos cálculos já usam estas configurações.');
    }
}
