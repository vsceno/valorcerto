@extends('layouts.app')

@section('titulo', 'Categorias')
@section('subtitulo', 'Agrupamento dos produtos e serviços para análise')

@section('acoes')
    <x-botao href="{{ route('categorias.create') }}" icone="fa-plus">Nova categoria</x-botao>
@endsection

@section('conteudo')
    <x-card>
        @if ($categorias->isEmpty())
            <x-vazio icone="fa-tags" titulo="Nenhuma categoria cadastrada"
                     descricao="Categorias são opcionais, mas ajudam a comparar margens entre linhas de produto.">
                <x-botao href="{{ route('categorias.create') }}" icone="fa-plus">Cadastrar categoria</x-botao>
            </x-vazio>
        @else
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 pb-2 sm:px-6">Categoria</th>
                            <th class="px-3 pb-2 text-right">Itens</th>
                            <th class="px-3 pb-2">Situação</th>
                            <th class="px-5 pb-2 text-right sm:px-6">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($categorias as $categoria)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3 sm:px-6">
                                    <p class="font-medium text-slate-900">{{ $categoria->nome }}</p>
                                    @if ($categoria->descricao)
                                        <p class="text-xs text-slate-500">{{ $categoria->descricao }}</p>
                                    @endif
                                </td>
                                <td class="tabular px-3 py-3 text-right text-slate-600">{{ $categoria->itens_count }}</td>
                                <td class="px-3 py-3">
                                    <x-badge :cor="$categoria->ativo ? 'verde' : 'rosa'">
                                        {{ $categoria->ativo ? 'Ativa' : 'Inativa' }}
                                    </x-badge>
                                </td>
                                <td class="px-5 py-3 text-right sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('categorias.edit', $categoria) }}"
                                           class="rounded-lg px-2 py-1.5 text-slate-500 hover:bg-slate-100" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" action="{{ route('categorias.destroy', $categoria) }}"
                                              onsubmit="return confirm('Remover esta categoria? Os itens ficam sem categoria.')">
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

            <div class="mt-5">{{ $categorias->links() }}</div>
        @endif
    </x-card>
@endsection
