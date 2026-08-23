@extends('layouts.app')

@section('titulo', 'Insumos')
@section('subtitulo', 'Matéria-prima e mão de obra, com a conversão da unidade de compra para a de uso')

@section('acoes')
    <x-botao href="{{ route('insumos.create') }}" icone="fa-plus">Novo insumo</x-botao>
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
                    <input type="search" name="busca" id="busca" value="{{ $busca }}" placeholder="Nome, código ou fornecedor"
                           class="block w-full min-w-0 rounded-r-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                </div>
            </div>
            <div>
                <label for="grupo" class="block text-sm font-medium text-slate-700">Grupo</label>
                <select name="grupo" id="grupo" onchange="this.form.submit()"
                        class="mt-1.5 rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Insumo::GRUPOS as $chave => $rotulo)
                        <option value="{{ $chave }}" @selected($grupoSelecionado === $chave)>
                            {{ \Illuminate\Support\Str::before($rotulo, ' (') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <x-botao variante="neutro" icone="fa-filter">Filtrar</x-botao>
        </form>

        @if ($insumos->isEmpty())
            <x-vazio icone="fa-cubes-stacked" titulo="Nenhum insumo cadastrado"
                     descricao="Cadastre a matéria-prima com a unidade em que você compra (vara de 6 m, chapa de 2 m², rolo) e o sistema converte para a unidade consumida.">
                <x-botao href="{{ route('insumos.create') }}" icone="fa-plus">Cadastrar insumo</x-botao>
            </x-vazio>
        @else
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 pb-2 sm:px-6">Insumo</th>
                            <th class="px-3 pb-2">Compra</th>
                            <th class="px-3 pb-2">Conversão</th>
                            <th class="px-3 pb-2 text-right">Perda</th>
                            <th class="px-3 pb-2 text-right">Custo por unidade de uso</th>
                            <th class="px-3 pb-2 text-right">Usos</th>
                            <th class="px-5 pb-2 text-right sm:px-6">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($insumos as $insumo)
                            <tr class="transition hover:bg-slate-50 {{ $insumo->ativo ? '' : 'opacity-50' }}">
                                <td class="px-5 py-3 sm:px-6">
                                    <p class="font-medium text-slate-900">{{ $insumo->nome }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $insumo->codigo ?: 'sem código' }}
                                        · {{ \Illuminate\Support\Str::before($insumo->grupo_label, ' (') }}
                                        @if ($insumo->fornecedor) · {{ $insumo->fornecedor }} @endif
                                    </p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="tabular font-medium text-slate-900">@moeda($insumo->preco_compra)</p>
                                    <p class="text-xs text-slate-500">por {{ $insumo->unidade_compra }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($insumo->exigeConversao())
                                        <x-badge cor="marca" icone="fa-arrows-left-right">{{ $insumo->conversao }}</x-badge>
                                    @else
                                        <span class="text-xs text-slate-400">direta</span>
                                    @endif
                                </td>
                                <td class="tabular px-3 py-3 text-right {{ (float) $insumo->perda_percentual > 0 ? 'text-amber-700' : 'text-slate-400' }}">
                                    @pct($insumo->perda_percentual)
                                </td>
                                <td class="px-3 py-3 text-right">
                                    <p class="tabular font-semibold text-slate-900">
                                        R$ {{ number_format($insumo->custoUnitarioUso(), 4, ',', '.') }}
                                    </p>
                                    <p class="text-xs text-slate-500">por {{ $insumo->unidade_uso }}</p>
                                </td>
                                <td class="tabular px-3 py-3 text-right text-slate-600">{{ $insumo->composicoes_count }}</td>
                                <td class="px-5 py-3 text-right sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('insumos.edit', $insumo) }}"
                                           class="rounded-lg px-2 py-1.5 text-slate-500 hover:bg-slate-100" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" action="{{ route('insumos.destroy', $insumo) }}"
                                              onsubmit="return confirm('Remover este insumo?')">
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

            <div class="mt-5">{{ $insumos->links() }}</div>
        @endif
    </x-card>
@endsection
