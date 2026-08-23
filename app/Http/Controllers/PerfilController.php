<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerfilRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('perfil.edit', ['usuario' => $request->user()]);
    }

    public function update(PerfilRequest $request): RedirectResponse
    {
        $usuario = $request->user();

        $usuario->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $usuario->password = $request->input('password');
        }

        $usuario->save();

        return redirect()->route('perfil.edit')
            ->with('sucesso', 'Dados atualizados.');
    }
}
