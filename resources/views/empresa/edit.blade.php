@extends('layouts.app')

@section('titulo', 'Configuração da empresa')
@section('subtitulo', 'Regime tributário e volume padrão usados como base do cálculo')

@section('conteudo')
    <form method="POST" action="{{ route('empresa.update') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf @method('PUT')

        <div class="space-y-6 xl:col-span-2">
            <x-card titulo="Identificação" icone="fa-building">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-campo nome="razao_social" rotulo="Razão social" :valor="$empresa->razao_social" obrigatorio />
                    </div>
                    <x-campo nome="nome_fantasia" rotulo="Nome fantasia" :valor="$empresa->nome_fantasia" />
                    <x-campo nome="cnpj" rotulo="CNPJ" :valor="$empresa->cnpj" ajuda="Formato 00.000.000/0000-00" />
                </div>
            </x-card>

            <x-card titulo="Parâmetros do cálculo" icone="fa-sliders"
                    descricao="Definem quais tributos incidem e como os custos fixos são rateados por padrão.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-selecao nome="regime_tributario" rotulo="Regime tributário"
                               :opcoes="\App\Models\Empresa::REGIMES" :valor="$empresa->regime_tributario" obrigatorio
                               ajuda="O regime define a apuração das alíquotas efetivas cadastradas em Tributos." />

                    <x-campo nome="volume_projetado_mensal" rotulo="Volume projetado mensal padrão" tipo="number" step="1" min="1"
                             :valor="$empresa->volume_projetado_mensal" sufixo="un/mês" obrigatorio
                             ajuda="Usado no rateio quando o item não informa volume próprio." />
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Situação" icone="fa-toggle-on">
                <x-interruptor nome="ativo" rotulo="Empresa ativa" :valor="$empresa->ativo ?? true" />
            </x-card>

            <x-alerta tipo="atencao" titulo="Mudanças não alteram o passado">
                Precificações já registradas guardam os valores vigentes no momento do cálculo.
                Alterações aqui valem apenas para os próximos cálculos.
            </x-alerta>

            <x-botao variante="sucesso" icone="fa-floppy-disk" class="w-full">Salvar configuração</x-botao>
        </div>
    </form>
@endsection
