<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtualizarFichaRequest;
use App\Http\Requests\ComposicaoRequest;
use App\Models\Composicao;
use App\Models\Empresa;
use App\Models\Insumo;
use App\Models\Item;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FichaTecnicaController extends Controller
{
    /**
     * Monta a ficha técnica do item: quais insumos e quanto de cada um.
     */
    public function edit(Item $item): View
    {
        $item->load(['composicoes.insumo', 'categoria']);

        return view('itens.ficha-tecnica', [
            'item' => $item,
            'insumosDisponiveis' => Insumo::query()
                ->where('empresa_id', Empresa::atual()?->id)
                ->ativos()
                ->whereNotIn('id', $item->composicoes->pluck('insumo_id'))
                ->orderBy('grupo')
                ->orderBy('nome')
                ->get(),
            'custoTotal' => $item->custoDaFichaTecnica(),
            'porGrupo' => $item->composicoes
                ->groupBy(fn (Composicao $linha): string => $linha->insumo?->grupo ?? 'outros')
                ->map(fn ($linhas): float => $linhas->sum(fn (Composicao $l): float => $l->custoTotal()))
                ->sortDesc(),
        ]);
    }

    public function store(ComposicaoRequest $request, Item $item): RedirectResponse
    {
        $item->composicoes()->create($request->validated() + [
            'ordem' => (int) $item->composicoes()->max('ordem') + 1,
        ]);

        return back()->with('sucesso', 'Insumo adicionado à ficha técnica.');
    }

    /**
     * Salva as quantidades de todas as linhas de uma vez: em fabricação se
     * ajusta o consumo de vários insumos no mesmo raciocínio.
     */
    public function atualizar(AtualizarFichaRequest $request, Item $item): RedirectResponse
    {
        $linhas = $request->validated()['linhas'];

        // Buscar pela relação do item garante que só linhas dele sejam tocadas.
        $composicoes = $item->composicoes()->whereIn('id', array_keys($linhas))->get();

        foreach ($composicoes as $composicao) {
            $composicao->update([
                'quantidade' => $linhas[$composicao->id]['quantidade'],
                'observacao' => $linhas[$composicao->id]['observacao'] ?? null,
            ]);
        }

        $item->load('composicoes.insumo');

        return back()->with('sucesso', sprintf(
            '%d %s. Novo custo de produção: R$ %s por %s.',
            $composicoes->count(),
            $composicoes->count() === 1 ? 'linha atualizada' : 'linhas atualizadas',
            number_format($item->custoDaFichaTecnica(), 2, ',', '.'),
            $item->unidade_medida
        ));
    }

    public function destroy(Item $item, Composicao $composicao): RedirectResponse
    {
        abort_unless($composicao->item_id === $item->id, 404);

        $composicao->delete();

        return back()->with('sucesso', 'Insumo removido da ficha técnica.');
    }

    /**
     * Grava o custo apurado pela ficha no cadastro do item, para servir de
     * referência mesmo quando a ficha for desmontada.
     */
    public function sincronizarCusto(Item $item): RedirectResponse
    {
        $item->load('composicoes.insumo');

        if (! $item->temFichaTecnica()) {
            return back()->with('erro', 'Monte a ficha técnica antes de sincronizar o custo.');
        }

        $item->update(['custo_variavel_unitario' => round($item->custoDaFichaTecnica(), 4)]);

        return back()->with('sucesso', 'Custo do cadastro atualizado com o valor apurado na ficha técnica.');
    }
}
