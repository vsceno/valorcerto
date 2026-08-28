@extends('layouts.app')

@section('titulo', $empresa->exists ? 'Editar empresa' : 'Cadastrar empresa')
@section('subtitulo', 'O regime e a atividade definem quais tributos entram no cálculo do preço')

@section('conteudo')
    <form method="POST"
          action="{{ $empresa->exists ? route('empresa.update', $empresa) : route('empresa.store') }}"
          x-data="{
              regime: @js(old('regime_tributario', $empresa->regime_tributario ?? 'simples_nacional')),
              atividade: @js(old('atividade', $empresa->atividade ?? 'comercio')),
              get unificado() { return ['simples_nacional', 'mei'].includes(this.regime) },
          }"
          class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($empresa->exists) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <x-card titulo="Identificação" icone="fa-building">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-campo nome="razao_social" rotulo="Razão social" :valor="$empresa->razao_social" obrigatorio />
                    </div>
                    <x-campo nome="nome_fantasia" rotulo="Nome fantasia" :valor="$empresa->nome_fantasia" />
                    <x-campo nome="cnpj" rotulo="CNPJ" :valor="$empresa->cnpj" ajuda="Formato 00.000.000/0000-00" />
                    <x-campo nome="inscricao_estadual" rotulo="Inscrição estadual" :valor="$empresa->inscricao_estadual"
                             ajuda="Obrigatória para quem recolhe ICMS." />
                    <x-campo nome="inscricao_municipal" rotulo="Inscrição municipal" :valor="$empresa->inscricao_municipal"
                             ajuda="Obrigatória para quem recolhe ISS." />
                    <x-campo nome="cnae_principal" rotulo="CNAE principal" :valor="$empresa->cnae_principal"
                             ajuda="Ex.: 4751-2/01" />
                    <div class="grid grid-cols-3 gap-3">
                        <x-campo nome="uf" rotulo="UF" :valor="$empresa->uf" maxlength="2" class="uppercase" />
                        <div class="col-span-2">
                            <x-campo nome="municipio" rotulo="Município" :valor="$empresa->municipio" />
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card titulo="Enquadramento tributário" icone="fa-scale-balanced"
                    descricao="É esta escolha que determina quais tributos o sistema aplica ao formar o preço.">
                <div class="space-y-5">
                    <div>
                        <span class="block text-sm font-medium text-slate-700">
                            Regime tributário <span class="text-rose-500">*</span>
                        </span>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            @foreach (\App\Models\Empresa::REGIMES as $chave => $rotulo)
                                <label :class="regime === @js($chave)
                                        ? 'border-marca-300 bg-marca-50'
                                        : 'border-slate-200 hover:bg-slate-50'"
                                       class="flex cursor-pointer gap-3 rounded-xl border p-4 transition">
                                    <input type="radio" name="regime_tributario" value="{{ $chave }}"
                                           x-model="regime"
                                           class="mt-1 h-4 w-4 border-slate-300 text-marca-600 focus:ring-marca-500">
                                    <span class="text-sm">
                                        <span class="font-medium text-slate-900">{{ $rotulo }}</span>
                                        <span class="mt-0.5 block text-slate-500">
                                            @switch($chave)
                                                @case('simples_nacional')
                                                    Recolhimento unificado no DAS. Limite de R$ 4,8 milhões em 12 meses.
                                                    @break
                                                @case('lucro_presumido')
                                                    Tributos apurados em separado, com base de lucro presumida.
                                                    @break
                                                @case('lucro_real')
                                                    PIS e COFINS não cumulativos, com aproveitamento de créditos.
                                                    @break
                                                @case('mei')
                                                    Valor fixo mensal, sem percentual sobre a receita. Limite de R$ 81 mil.
                                                    @break
                                            @endswitch
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('regime_tributario')
                            <p class="mt-2 flex items-center gap-1.5 text-sm text-rose-600">
                                <i class="fa-solid fa-circle-exclamation text-xs"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="atividade" class="block text-sm font-medium text-slate-700">
                                Atividade principal <span class="text-rose-500">*</span>
                            </label>
                            <select name="atividade" id="atividade" x-model="atividade"
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                                @foreach (\App\Models\Empresa::ATIVIDADES as $chave => $rotulo)
                                    <option value="{{ $chave }}">{{ $rotulo }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-sm text-slate-500">
                                Comércio e indústria recolhem ICMS; serviços recolhem ISS.
                            </p>
                        </div>

                        <x-campo nome="regime_vigente_desde" rotulo="Regime vigente desde" tipo="date"
                                 :valor="$empresa->regime_vigente_desde?->format('Y-m-d')"
                                 ajuda="Data em que o enquadramento atual passou a valer." />
                    </div>

                    <div x-show="unificado" x-cloak>
                        <x-alerta tipo="atencao" titulo="Tributação unificada">
                            Neste regime o recolhimento é único. Não cadastre ICMS, PIS, COFINS ou ISS
                            separadamente — isso dobraria a carga no cálculo do preço.
                        </x-alerta>
                    </div>
                </div>
            </x-card>

            <x-card titulo="Base do cálculo" icone="fa-sliders"
                    descricao="Números usados no rateio dos custos fixos e no enquadramento do regime.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo nome="faturamento_12_meses" rotulo="Faturamento nos últimos 12 meses" tipo="number"
                             step="0.01" min="0" :valor="$empresa->faturamento_12_meses" prefixo="R$" obrigatorio
                             ajuda="Define a faixa do Simples e valida o limite do regime." />

                    <x-campo nome="volume_projetado_mensal" rotulo="Volume projetado mensal padrão" tipo="number"
                             step="1" min="1" :valor="$empresa->volume_projetado_mensal" sufixo="un/mês" obrigatorio
                             ajuda="Usado no rateio quando o item não informa volume próprio." />
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Situação" icone="fa-toggle-on">
                <x-interruptor nome="ativo" rotulo="Empresa ativa" :valor="$empresa->ativo ?? true"
                               ajuda="Empresas inativas não podem ser selecionadas para operar." />
            </x-card>

            <x-alerta tipo="info" titulo="O que acontece depois de salvar">
                O sistema mostra os tributos que o regime escolhido comporta e permite cadastrá-los
                de uma vez. As alíquotas vêm como referência — ajuste as efetivas com sua contabilidade.
            </x-alerta>

            <x-alerta tipo="atencao" titulo="Mudanças não alteram o passado">
                Precificações já registradas guardam os valores vigentes no momento do cálculo.
                Alterações aqui valem apenas para os próximos cálculos.
            </x-alerta>

            <div class="flex flex-wrap gap-3">
                <x-botao variante="sucesso" icone="fa-floppy-disk" class="flex-1">
                    {{ $empresa->exists ? 'Salvar alterações' : 'Cadastrar empresa' }}
                </x-botao>
                <x-botao href="{{ route('empresa.index') }}" variante="neutro">Cancelar</x-botao>
            </div>
        </div>
    </form>
@endsection
