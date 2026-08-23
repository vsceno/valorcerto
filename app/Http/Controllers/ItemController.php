<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Item;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = Empresa::atual();

        $itens = Item::query()
            ->where('empresa_id', $empresa?->id)
            ->with(['categoria', 'ultimaPrecificacao'])
            ->doTipo($request->string('tipo')->toString() ?: null)
            ->when($request->filled('busca'), function ($query) use ($request): void {
                $busca = $request->string('busca')->toString();
                $query->where(function ($q) use ($busca): void {
                    $q->where('nome', 'like', "%{$busca}%")
                        ->orWhere('sku', 'like', "%{$busca}%");
                });
            })
            ->orderBy('nome')
            ->paginate(12)
            ->withQueryString();

        return view('itens.index', [
            'itens' => $itens,
            'tipoSelecionado' => $request->string('tipo')->toString(),
            'busca' => $request->string('busca')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('itens.form', [
            'item' => new Item(['ativo' => true, 'tipo' => 'produto', 'unidade_medida' => 'UN']),
            'categorias' => $this->categorias(),
        ]);
    }

    public function store(ItemRequest $request): RedirectResponse
    {
        $item = Item::create($request->validated() + ['empresa_id' => Empresa::atual()?->id]);

        return redirect()->route('precificacao.simulador', ['item' => $item->id])
            ->with('sucesso', 'Item cadastrado. Confira o preço sugerido abaixo.');
    }

    public function show(Item $item): View
    {
        $item->load(['categoria', 'precificacoes' => fn ($q) => $q->recentes()->limit(20)]);

        return view('itens.show', ['item' => $item]);
    }

    public function edit(Item $item): View
    {
        return view('itens.form', [
            'item' => $item,
            'categorias' => $this->categorias(),
        ]);
    }

    public function update(ItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()->route('itens.index')->with('sucesso', 'Item atualizado.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('itens.index')
            ->with('sucesso', 'Item removido. O histórico de precificações foi preservado para auditoria.');
    }

    /**
     * @return Collection<int, Categoria>
     */
    private function categorias()
    {
        return Categoria::query()
            ->where('empresa_id', Empresa::atual()?->id)
            ->ativos()
            ->orderBy('nome')
            ->get();
    }
}
