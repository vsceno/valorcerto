@extends('layouts.app')

@section('titulo', $custo->exists ? 'Editar custo fixo' : 'Novo custo fixo')
@section('subtitulo', 'Despesas que não variam com o volume vendido')

@section('conteudo')
    <form method="POST" action="{{ $custo->exists ? route('custos-fixos.update', $custo) : route('custos-fixos.store') }}"
          class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($custo->exists) @method('PUT') @endif

        <div class="xl:col-span-2">
            <x-card titulo="Dados do custo" icone="fa-building-columns">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-campo nome="descricao" rotulo="Descrição" :valor="$custo->descricao" obrigatorio
                                 ajuda="Ex.: Aluguel do ponto comercial, Honorários contábeis." />
                    </div>

                    <x-selecao nome="grupo" rotulo="Grupo" :opcoes="\App\Models\CustoFixo::GRUPOS"
                               :valor="$custo->grupo" obrigatorio
                               ajuda="Agrupar ajuda a enxergar onde a estrutura pesa mais." />

                    <x-campo nome="valor_mensal" rotulo="Valor mensal" tipo="number" step="0.01" min="0"
                             :valor="$custo->valor_mensal" prefixo="R$" obrigatorio />
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Situação" icone="fa-toggle-on">
                <x-interruptor nome="ativo" rotulo="Custo ativo" :valor="$custo->ativo ?? true"
                               ajuda="Apenas custos ativos entram no rateio." />
            </x-card>

            <x-alerta tipo="info" titulo="Fixo ou variável?">
                Se a despesa acontece mesmo com venda zero, ela é fixa e pertence a esta tela.
                Se só existe quando há venda, é custo variável e vai no cadastro do item.
            </x-alerta>

            <div class="flex flex-wrap gap-3">
                <x-botao variante="sucesso" icone="fa-floppy-disk" class="flex-1">
                    {{ $custo->exists ? 'Salvar alterações' : 'Cadastrar custo' }}
                </x-botao>
                <x-botao href="{{ route('custos-fixos.index') }}" variante="neutro">Cancelar</x-botao>
            </div>
        </div>
    </form>
@endsection
