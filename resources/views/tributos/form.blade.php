@extends('layouts.app')

@section('titulo', $tributo->exists ? 'Editar tributo' : 'Novo tributo')
@section('subtitulo', 'Informe a alíquota efetiva: a nominal serve apenas para comparação e auditoria')

@section('conteudo')
    <form method="POST" action="{{ $tributo->exists ? route('tributos.update', $tributo) : route('tributos.store') }}"
          class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($tributo->exists) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <x-card titulo="Identificação" icone="fa-tag">
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-campo nome="sigla" rotulo="Sigla" :valor="$tributo->sigla" ajuda="ICMS, PIS, ISS..." obrigatorio />

                    <div class="sm:col-span-2">
                        <x-campo nome="nome" rotulo="Nome completo" :valor="$tributo->nome" obrigatorio />
                    </div>

                    <div class="sm:col-span-3">
                        <x-selecao nome="aplica_a" rotulo="Incide sobre" :opcoes="\App\Models\Tributo::APLICACOES"
                                   :valor="$tributo->aplica_a" obrigatorio
                                   ajuda="ICMS incide sobre produtos; ISS sobre serviços; PIS e COFINS costumam incidir sobre ambos." />
                    </div>
                </div>
            </x-card>

            <x-card titulo="Alíquotas" icone="fa-percent"
                    descricao="A efetiva é a que entra na fórmula. Ela não pode ser maior que a nominal.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo nome="aliquota_nominal" rotulo="Alíquota nominal" tipo="number" step="0.01" min="0" max="100"
                             :valor="$tributo->aliquota_nominal" sufixo="%" obrigatorio
                             ajuda="A alíquota de tabela, antes de créditos e reduções." />

                    <x-campo nome="aliquota_efetiva" rotulo="Alíquota efetiva" tipo="number" step="0.01" min="0" max="100"
                             :valor="$tributo->aliquota_efetiva" sufixo="%" obrigatorio
                             ajuda="O que de fato sai do caixa, líquido de créditos e reduções de base." />
                </div>
            </x-card>

            <x-card titulo="Fundamentação" icone="fa-gavel"
                    descricao="Registrar a base legal facilita a defesa do preço em fiscalização ou questionamento.">
                <div class="space-y-5">
                    <x-campo nome="base_legal" rotulo="Base legal" :valor="$tributo->base_legal"
                             ajuda="Ex.: Lei 10.833/2003, art. 2º — regime não cumulativo." />

                    <div>
                        <label for="observacoes" class="block text-sm font-medium text-slate-700">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="3"
                                  placeholder="Como a alíquota efetiva foi apurada, créditos considerados, período de vigência..."
                                  class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">{{ old('observacoes', $tributo->observacoes) }}</textarea>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Situação" icone="fa-toggle-on">
                <x-interruptor nome="ativo" rotulo="Tributo ativo" :valor="$tributo->ativo ?? true"
                               ajuda="Apenas tributos ativos entram no cálculo do preço." />
            </x-card>

            <x-alerta tipo="atencao" titulo="Nominal x efetiva">
                Usar a alíquota nominal quando existe crédito a aproveitar infla o preço e reduz a competitividade.
                Usar uma efetiva subestimada corrói a margem real. Apure com o contador.
            </x-alerta>

            <div class="flex flex-wrap gap-3">
                <x-botao variante="sucesso" icone="fa-floppy-disk" class="flex-1">
                    {{ $tributo->exists ? 'Salvar alterações' : 'Cadastrar tributo' }}
                </x-botao>
                <x-botao href="{{ route('tributos.index') }}" variante="neutro">Cancelar</x-botao>
            </div>
        </div>
    </form>
@endsection
