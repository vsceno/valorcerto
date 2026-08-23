<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use App\Models\Empresa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CategoriaController extends Controller
{
    public function index(): View
    {
        $empresa = Empresa::atual();

        return view('categorias.index', [
            'categorias' => Categoria::query()
                ->where('empresa_id', $empresa?->id)
                ->withCount('itens')
                ->orderBy('nome')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('categorias.form', ['categoria' => new Categoria(['ativo' => true])]);
    }

    public function store(CategoriaRequest $request): RedirectResponse
    {
        Categoria::create($request->validated() + ['empresa_id' => Empresa::atual()?->id]);

        return redirect()->route('categorias.index')->with('sucesso', 'Categoria cadastrada.');
    }

    public function edit(Categoria $categoria): View
    {
        return view('categorias.form', ['categoria' => $categoria]);
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        return redirect()->route('categorias.index')->with('sucesso', 'Categoria atualizada.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $categoria->delete();

        return redirect()->route('categorias.index')->with('sucesso', 'Categoria removida.');
    }
}
