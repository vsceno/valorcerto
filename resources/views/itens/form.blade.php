@extends('layouts.app')

@section('titulo', $item->exists ? 'Editar item' : 'Novo produto ou serviço')
@section('subtitulo', 'O custo variável e a margem definidos aqui são o ponto de partida de cada cálculo')

@section('conteudo')
    <form method="POST" action="{{ $item->exists ? route('itens.update', $item) : route('itens.store') }}"
          class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <x-card titulo="Identificação" icone="fa-tag">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-selecao nome="tipo" rotulo="Tipo" :opcoes="\App\Models\Item::TIPOS" :valor="$item->tipo"
                               ajuda="Define quais tributos incidem: ICMS em produtos, ISS em serviços." obrigatorio />

                    <x-campo nome="unidade_medida" rotulo="Unidade de medida" :valor="$item->unidade_medida"
                             ajuda="UN, KG, PC, HR, MES..." obrigatorio />

                    <div class="sm:col-span-2">
                        <x-campo nome="nome" rotulo="Nome" :valor="$item->nome" obrigatorio />
                    </div>

                    <x-campo nome="sku" rotulo="SKU / código" :valor="$item->sku" ajuda="Opcional, mas único dentro da empresa." />

                    <x-selecao nome="categoria_id" rotulo="Categoria" :valor="$item->categoria_id"
                               :opcoes="$categorias->pluck('nome', 'id')" vazio="Sem categoria" />

                    <div class="sm:col-span-2">
                        <label for="descricao" class="block text-sm font-medium text-slate-700">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                                  class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">{{ old('descricao', $item->descricao) }}</textarea>
                    </div>
                </div>
            </x-card>

            <x-card titulo="Base do cálculo" icone="fa-calculator"
                    descricao="Estes três valores entram diretamente na fórmula de precificação.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo nome="custo_variavel_unitario" rotulo="Custo variável unitário" tipo="number" step="0.0001" min="0"
                             :valor="$item->custo_variavel_unitario" prefixo="R$" obrigatorio
                             ajuda="Só o que existe por causa da venda: insumo, embalagem, comissão." />

                    <x-campo nome="margem_contribuicao_desejada" rotulo="Margem de contribuição desejada" tipo="number" step="0.01" min="0" max="99.99"
                             :valor="$item->margem_contribuicao_desejada" sufixo="%" obrigatorio
                             ajuda="Percentual sobre o PREÇO FINAL, não sobre o custo." />

                    <div class="sm:col-span-2">
                        <x-campo nome="volume_projetado_mensal" rotulo="Volume projetado no mês" tipo="number" step="1" min="1"
                                 :valor="$item->volume_projetado_mensal"
                                 :sufixo="$item->unidade_medida ?: 'UN'"
                                 ajuda="Divisor do rateio dos custos fixos. Em branco, o sistema usa o volume padrão da empresa." />
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Situação" icone="fa-toggle-on">
                <x-interruptor nome="ativo" rotulo="Item ativo" :valor="$item->ativo ?? true"
                               ajuda="Itens inativos não aparecem no simulador." />
            </x-card>

            <x-alerta tipo="info" titulo="Como a margem funciona aqui">
                Se você define 25%, a margem será exatamente 25% do preço final — porque o percentual entra
                no divisor da fórmula, e não como multiplicador sobre o custo.
            </x-alerta>

            <div class="flex flex-wrap gap-3">
                <x-botao variante="sucesso" icone="fa-floppy-disk" class="flex-1">
                    {{ $item->exists ? 'Salvar alterações' : 'Cadastrar item' }}
                </x-botao>
                <x-botao href="{{ route('itens.index') }}" variante="neutro">Cancelar</x-botao>
            </div>
        </div>
    </form>
@endsection
