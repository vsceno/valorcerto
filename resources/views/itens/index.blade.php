@extends('layouts.app')

@section('titulo', 'Produtos e serviços')
@section('subtitulo', 'O que a empresa vende, com o custo direto e a margem desejada de cada item')

@section('acoes')
    <x-botao href="{{ route('itens.create') }}" icone="fa-plus">Novo item</x-botao>
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
                    <input type="search" name="busca" id="busca" value="{{ $busca }}" placeholder="Nome ou SKU"
                           class="block w-full min-w-0 rounded-r-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                </div>
            </div>
            <div>
                <label for="tipo" class="block text-sm font-medium text-slate-700">Tipo</label>
                <select name="tipo" id="tipo" onchange="this.form.submit()"
                        class="mt-1.5 rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                    <option value="">Todos</option>
                    <option value="produto" @selected($tipoSelecionado === 'produto')>Produtos</option>
                    <option value="servico" @selected($tipoSelecionado === 'servico')>Serviços</option>
                </select>
            </div>
            <x-botao variante="neutro" icone="fa-filter">Filtrar</x-botao>
        </form>

        @if ($itens->isEmpty())
            <x-vazio icone="fa-box-open" titulo="Nenhum item encontrado"
                     descricao="Cadastre os produtos e serviços que você vende para formar preços com base no custo real.">
                <x-botao href="{{ route('itens.create') }}" icone="fa-plus">Cadastrar item</x-botao>
            </x-vazio>
        @else
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 pb-2 sm:px-6">Item</th>
                            <th class="px-3 pb-2">Categoria</th>
                            <th class="px-3 pb-2 text-right">Custo variável</th>
                            <th class="px-3 pb-2 text-right">Margem</th>
                            <th class="px-3 pb-2 text-right">Último preço</th>
                            <th class="px-5 pb-2 text-right sm:px-6">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($itens as $item)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <span @class([
                                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                                            'bg-amber-50 text-amber-600' => $item->tipo === 'servico',
                                            'bg-marca-50 text-marca-600' => $item->tipo !== 'servico',
                                        ])>
                                            <i class="fa-solid {{ $item->tipo === 'servico' ? 'fa-screwdriver-wrench' : 'fa-box' }}"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <a href="{{ route('itens.show', $item) }}" class="font-medium text-slate-900 hover:text-marca-700">
                                                {{ $item->nome }}
                                            </a>
                                            <p class="text-xs text-slate-500">
                                                {{ $item->sku ?: 'sem SKU' }} · {{ $item->unidade_medida }}
                                                @unless ($item->ativo) · <span class="text-rose-600">inativo</span> @endunless
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-slate-600">{{ $item->categoria?->nome ?? '—' }}</td>
                                <td class="tabular px-3 py-3 text-right text-slate-600">@moeda($item->custo_variavel_unitario)</td>
                                <td class="tabular px-3 py-3 text-right text-emerald-700">@pct($item->margem_contribuicao_desejada)</td>
                                <td class="tabular px-3 py-3 text-right font-semibold text-slate-900">
                                    @if ($item->ultimaPrecificacao)
                                        @moeda($item->ultimaPrecificacao->preco_venda)
                                    @else
                                        <span class="text-xs font-normal text-amber-600">não precificado</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('precificacao.simulador', ['item' => $item->id]) }}"
                                           class="rounded-lg px-2 py-1.5 text-marca-700 hover:bg-marca-50" title="Simular preço">
                                            <i class="fa-solid fa-calculator"></i>
                                        </a>
                                        <a href="{{ route('itens.edit', $item) }}"
                                           class="rounded-lg px-2 py-1.5 text-slate-500 hover:bg-slate-100" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" action="{{ route('itens.destroy', $item) }}"
                                              onsubmit="return confirm('Remover {{ $item->nome }}? O histórico de precificações é preservado.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="rounded-lg px-2 py-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Remover">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $itens->links() }}</div>
        @endif
    </x-card>
@endsection
