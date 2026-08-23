@extends('layouts.app')

@section('titulo', 'Histórico de precificações')
@section('subtitulo', 'Registros imutáveis: cada linha guarda a memória de cálculo do preço no momento em que foi formado')

@section('acoes')
    <x-botao href="{{ route('precificacao.simulador') }}" icone="fa-calculator">Novo cálculo</x-botao>
@endsection

@section('conteudo')
    <x-card>
        <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
            <div class="min-w-64 flex-1">
                <label for="busca" class="block text-sm font-medium text-slate-700">Buscar</label>
                <div class="mt-1.5 flex rounded-lg shadow-sm">
                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </span>
                    <input type="search" name="busca" id="busca" value="{{ $busca }}" placeholder="Nome do item ou SKU"
                           class="block w-full min-w-0 rounded-r-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                </div>
            </div>
            <x-botao variante="neutro" icone="fa-filter">Filtrar</x-botao>
            @if ($busca)
                <x-botao href="{{ route('precificacao.index') }}" variante="fantasma">Limpar</x-botao>
            @endif
        </form>

        @if ($precificacoes->isEmpty())
            <x-vazio icone="fa-receipt" titulo="Nenhuma precificação encontrada"
                     descricao="Preços registrados aparecem aqui com a memória de cálculo completa e o hash de auditoria.">
                <x-botao href="{{ route('precificacao.simulador') }}" icone="fa-calculator">Abrir simulador</x-botao>
            </x-vazio>
        @else
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 pb-2 sm:px-6">Item</th>
                            <th class="px-3 pb-2">Data</th>
                            <th class="px-3 pb-2 text-right">Custo unit.</th>
                            <th class="px-3 pb-2 text-right">Tributos</th>
                            <th class="px-3 pb-2 text-right">Margem</th>
                            <th class="px-3 pb-2 text-right">Preço</th>
                            <th class="px-5 pb-2 text-right sm:px-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($precificacoes as $p)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3 sm:px-6">
                                    <p class="font-medium text-slate-900">{{ $p->item_nome }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $p->item_sku ?: 'sem SKU' }} ·
                                        {{ $p->item_tipo === 'servico' ? 'Serviço' : 'Produto' }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 text-slate-600">{{ $p->calculado_em->format('d/m/Y H:i') }}</td>
                                <td class="tabular px-3 py-3 text-right text-slate-600">@moeda($p->custo_total_unitario)</td>
                                <td class="tabular px-3 py-3 text-right text-amber-700">@pct($p->soma_aliquotas_efetivas)</td>
                                <td class="tabular px-3 py-3 text-right text-emerald-700">@pct($p->margem_contribuicao)</td>
                                <td class="tabular px-3 py-3 text-right font-semibold text-slate-900">@moeda($p->preco_venda)</td>
                                <td class="px-5 py-3 text-right sm:px-6">
                                    <a href="{{ route('precificacao.show', $p) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-marca-700 hover:bg-marca-50">
                                        <i class="fa-solid fa-list-ol"></i> Memória
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $precificacoes->links() }}</div>
        @endif
    </x-card>
@endsection
