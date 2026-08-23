@extends('layouts.app')

@section('titulo', $categoria->exists ? 'Editar categoria' : 'Nova categoria')

@section('conteudo')
    <form method="POST" action="{{ $categoria->exists ? route('categorias.update', $categoria) : route('categorias.store') }}"
          class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($categoria->exists) @method('PUT') @endif

        <div class="xl:col-span-2">
            <x-card titulo="Dados da categoria" icone="fa-tags">
                <div class="space-y-5">
                    <x-campo nome="nome" rotulo="Nome" :valor="$categoria->nome" obrigatorio />
                    <x-campo nome="descricao" rotulo="Descrição" :valor="$categoria->descricao" />
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Situação" icone="fa-toggle-on">
                <x-interruptor nome="ativo" rotulo="Categoria ativa" :valor="$categoria->ativo ?? true" />
            </x-card>

            <div class="flex flex-wrap gap-3">
                <x-botao variante="sucesso" icone="fa-floppy-disk" class="flex-1">
                    {{ $categoria->exists ? 'Salvar alterações' : 'Cadastrar categoria' }}
                </x-botao>
                <x-botao href="{{ route('categorias.index') }}" variante="neutro">Cancelar</x-botao>
            </div>
        </div>
    </form>
@endsection
