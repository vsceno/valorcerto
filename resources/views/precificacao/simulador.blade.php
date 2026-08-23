@extends('layouts.app')

@section('titulo', 'Simulador de preço')
@section('subtitulo', 'Veja o preço sendo construído, passo a passo, enquanto você ajusta os números')

@section('acoes')
    <x-botao href="{{ route('precificacao.index') }}" variante="neutro" icone="fa-clock-rotate-left">
        Histórico
    </x-botao>
@endsection

@section('conteudo')
    @if ($itens->isEmpty())
        <x-vazio icone="fa-box-open" titulo="Nenhum produto ou serviço cadastrado"
                 descricao="Cadastre o que você vende para o sistema calcular o preço com base no custo real e na carga tributária efetiva.">
            <x-botao href="{{ route('itens.create') }}" icone="fa-plus">Cadastrar item</x-botao>
        </x-vazio>
    @else
        <div x-data="simulador(@js([
            'itemId' => $item?->id,
            'custo' => (float) ($item?->custoVariavelEfetivo() ?? 0),
            'volume' => (float) ($item?->volumeParaRateio() ?? 1),
            'margem' => (float) ($item?->margem_contribuicao_desejada ?? 0),
            'resultado' => $resultado?->toArray(),
            'erro' => $erro,
            'rotaCalculo' => route('precificacao.calcular'),
        ]))" class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- ============ COLUNA DE ENTRADAS E MEMÓRIA ============ --}}
            <div class="space-y-6 xl:col-span-2">

                {{-- Seleção do item --}}
                <x-card titulo="1. O que você vai precificar" icone="fa-box-open"
                        descricao="Os valores abaixo vêm do cadastro do item e podem ser simulados sem alterá-lo.">
                    <form method="GET" action="{{ route('precificacao.simulador') }}" class="grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label for="item" class="block text-sm font-medium text-slate-700">Produto ou serviço</label>
                            <select name="item" id="item" onchange="this.form.submit()"
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                                @foreach ($itens as $opcao)
                                    <option value="{{ $opcao->id }}" @selected($item?->id === $opcao->id)>
                                        {{ $opcao->nome }}{{ $opcao->sku ? " ({$opcao->sku})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <x-badge :cor="$item?->tipo === 'servico' ? 'ambar' : 'marca'"
                                     :icone="$item?->tipo === 'servico' ? 'fa-screwdriver-wrench' : 'fa-box'"
                                     class="h-10 px-3">
                                {{ $item?->tipo_label }} · {{ $item?->unidade_medida }}
                            </x-badge>
                        </div>
                    </form>
                </x-card>

                {{-- Entradas do cálculo --}}
                <x-card titulo="2. Ajuste as variáveis" icone="fa-sliders"
                        descricao="Cada mudança recalcula o preço na hora, usando a mesma regra que será registrada na auditoria.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="custo" class="block text-sm font-medium text-slate-700">Custo variável unitário</label>
                            <div class="mt-1.5 flex rounded-lg shadow-sm">
                                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">R$</span>
                                <input type="number" id="custo" step="0.0001" min="0"
                                       x-model.number="custo" @input="agendar()"
                                       class="tabular block w-full min-w-0 rounded-r-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                            </div>
                            @if ($item?->temFichaTecnica())
                                <p class="mt-1.5 text-sm text-emerald-700">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                    Apurado na ficha técnica: <strong>@moeda($item->custoDaFichaTecnica())</strong>.
                                    Altere aqui apenas para simular outro cenário.
                                </p>
                            @else
                                <p class="mt-1.5 text-sm text-slate-500">Matéria-prima, insumos, embalagem, comissão — o que só existe se a venda acontecer.</p>
                            @endif
                        </div>

                        <div>
                            <label for="volume" class="block text-sm font-medium text-slate-700">Volume projetado no mês</label>
                            <div class="mt-1.5 flex rounded-lg shadow-sm">
                                <input type="number" id="volume" step="1" min="1"
                                       x-model.number="volume" @input="agendar()"
                                       class="tabular block w-full min-w-0 rounded-l-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                                <span class="inline-flex items-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">{{ $item?->unidade_medida }}</span>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-500">Divisor do rateio: quanto maior o volume, menor o custo fixo por unidade.</p>
                        </div>

                        <div class="sm:col-span-2">
                            <div class="flex items-baseline justify-between">
                                <label for="margem" class="block text-sm font-medium text-slate-700">Margem de contribuição desejada</label>
                                <span class="tabular text-lg font-semibold text-marca-700" x-text="pct(margem)"></span>
                            </div>
                            <input type="range" id="margem" min="0" max="80" step="0.5"
                                   x-model.number="margem" @input="agendar()"
                                   class="mt-3 h-2 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-marca-600">
                            <div class="mt-1 flex justify-between text-xs text-slate-400">
                                <span>0%</span><span>40%</span><span>80%</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">
                                <i class="fa-solid fa-circle-info text-marca-500"></i>
                                Percentual sobre o <strong>preço final</strong> — não sobre o custo. Por isso ele entra no divisor da fórmula.
                            </p>
                        </div>
                    </div>
                </x-card>

                {{-- Base fixa vinda dos cadastros --}}
                <x-card titulo="3. Base vinda dos seus cadastros" icone="fa-database"
                        descricao="Estes valores não são simulados aqui: eles vêm das telas de custos fixos e tributos.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-slate-600">Custo fixo total do mês</p>
                                @can('administrar')
                                    <a href="{{ route('custos-fixos.index') }}" class="text-xs font-medium text-marca-600 hover:text-marca-700">
                                        ajustar <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                @endcan
                            </div>
                            <p class="tabular mt-2 text-2xl font-semibold text-slate-900">@moeda($custoFixoTotal)</p>
                            <p class="mt-1 text-sm text-slate-500">Rateado pelo volume projetado acima.</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            @php $tributosDoItem = $item?->tipo === 'servico' ? $tributosServico : $tributosProduto; @endphp
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-slate-600">Tributos sobre {{ $item?->tipo === 'servico' ? 'serviços' : 'produtos' }}</p>
                                @can('administrar')
                                    <a href="{{ route('tributos.index') }}" class="text-xs font-medium text-marca-600 hover:text-marca-700">
                                        ajustar <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                @endcan
                            </div>
                            <p class="tabular mt-2 text-2xl font-semibold text-slate-900">
                                @pct($tributosDoItem->sum(fn ($t) => (float) $t->aliquota_efetiva))
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @forelse ($tributosDoItem as $tributo)
                                    <x-badge cor="slate" title="{{ $tributo->base_legal }}">
                                        {{ $tributo->sigla }} @pct($tributo->aliquota_efetiva)
                                    </x-badge>
                                @empty
                                    <span class="text-sm text-slate-500">Nenhum tributo cadastrado.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </x-card>

                {{-- Ficha técnica, quando existe --}}
                @if ($item?->temFichaTecnica())
                    <x-card titulo="De onde vem o custo variável" icone="fa-clipboard-list"
                            descricao="Ficha técnica do produto: cada insumo convertido da unidade de compra para a de uso.">
                        <x-slot:acoes>
                            <x-botao href="{{ route('ficha-tecnica.edit', $item) }}" variante="fantasma" class="!px-2 !py-1 text-sm">
                                editar <i class="fa-solid fa-arrow-right text-xs"></i>
                            </x-botao>
                        </x-slot:acoes>

                        <div class="-mx-5 overflow-x-auto sm:-mx-6">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                        <th class="px-5 pb-2 sm:px-6">Insumo</th>
                                        <th class="px-3 pb-2 text-right">Qtd.</th>
                                        <th class="px-3 pb-2 text-right">Custo unit.</th>
                                        <th class="px-5 pb-2 text-right sm:px-6">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($item->composicoes as $linha)
                                        <tr>
                                            <td class="px-5 py-2.5 sm:px-6">
                                                <span class="font-medium text-slate-800">{{ $linha->insumo?->nome }}</span>
                                                @if ($linha->insumo?->exigeConversao())
                                                    <span class="block text-xs text-slate-500">{{ $linha->insumo->conversao }}</span>
                                                @endif
                                            </td>
                                            <td class="tabular px-3 py-2.5 text-right text-slate-600">
                                                @num($linha->quantidade, 2) {{ $linha->insumo?->unidade_uso }}
                                            </td>
                                            <td class="tabular px-3 py-2.5 text-right text-slate-600">
                                                R$ {{ number_format($linha->insumo?->custoUnitarioUso() ?? 0, 4, ',', '.') }}
                                            </td>
                                            <td class="tabular px-5 py-2.5 text-right font-medium text-slate-900 sm:px-6">
                                                @moeda($linha->custoTotal())
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-slate-200">
                                        <td colspan="3" class="px-5 pt-3 font-semibold text-slate-700 sm:px-6">Custo de produção por unidade</td>
                                        <td class="tabular px-5 pt-3 text-right text-lg font-bold text-emerald-700 sm:px-6">
                                            @moeda($item->custoDaFichaTecnica())
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </x-card>
                @endif

                {{-- Memória de cálculo --}}
                <x-card titulo="4. Memória de cálculo" icone="fa-list-ol"
                        descricao="Cada etapa do preço, com fórmula e substituição — é este rastro que justifica o valor praticado.">
                    <template x-if="resultado">
                        <ol class="space-y-3">
                            <template x-for="passo in resultado.memoria_calculo" :key="passo.ordem">
                                <li class="relative flex gap-4 rounded-xl border p-4 transition"
                                    :class="passo.ordem === 5 ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-white'">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                                          :class="passo.ordem === 5 ? 'bg-emerald-600 text-white' : 'bg-marca-100 text-marca-700'"
                                          x-text="passo.ordem"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-slate-900" x-text="passo.titulo"></p>
                                        <p class="mt-2 overflow-x-auto rounded-lg bg-slate-900 px-3 py-2 font-mono text-xs text-slate-100" x-text="passo.formula"></p>
                                        <div class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-1 text-sm">
                                            <span class="tabular font-mono text-slate-600" x-text="passo.substituicao"></span>
                                            <span class="text-slate-400">=</span>
                                            <span class="tabular font-semibold"
                                                  :class="passo.ordem === 5 ? 'text-base text-emerald-700' : 'text-slate-900'"
                                                  x-text="passo.resultado"></span>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-500" x-text="passo.explicacao"></p>
                                    </div>
                                </li>
                            </template>
                        </ol>
                    </template>

                    <template x-if="! resultado">
                        <p class="py-6 text-center text-sm text-slate-500">Ajuste as variáveis acima para ver a memória de cálculo.</p>
                    </template>
                </x-card>
            </div>

            {{-- ============ COLUNA DO RESULTADO ============ --}}
            <div class="xl:col-span-1">
                <div class="space-y-6 xl:sticky xl:top-24">

                    {{-- Preço --}}
                    <div class="overflow-hidden rounded-2xl bg-marca-950 shadow-lg shadow-marca-950/20">
                        <div class="px-6 py-6">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-marca-100/70">Preço de venda sugerido</p>
                                <span x-show="carregando" x-cloak class="text-marca-100/70">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                                </span>
                            </div>

                            <template x-if="resultado && ! erro">
                                <div>
                                    <p class="tabular mt-2 text-4xl font-bold text-white transition"
                                       :class="pulsando && 'valor-atualizado'"
                                       x-text="moeda(resultado.preco_venda_comercial)"></p>
                                    <p class="mt-1 text-sm text-marca-100/60">
                                        por {{ $item?->unidade_medida }} ·
                                        <span x-text="`markup ${num(resultado.markup, 2)}x`"></span>
                                    </p>
                                </div>
                            </template>

                            <template x-if="erro">
                                <p class="mt-3 rounded-lg bg-rose-500/20 p-3 text-sm text-rose-100" x-text="erro"></p>
                            </template>
                        </div>

                        {{-- Barra de composição --}}
                        <template x-if="resultado && ! erro">
                            <div class="border-t border-white/10 px-6 py-5">
                                <p class="text-xs font-semibold uppercase tracking-wider text-marca-100/50">Como o preço se divide</p>

                                <div class="mt-3 flex h-3 overflow-hidden rounded-full bg-white/10">
                                    <div class="bg-slate-300 transition-all duration-300" :style="`width: ${fatiaCusto}%`" title="Custo"></div>
                                    <div class="bg-amber-400 transition-all duration-300" :style="`width: ${fatiaTributos}%`" title="Tributos"></div>
                                    <div class="bg-emerald-400 transition-all duration-300" :style="`width: ${fatiaMargem}%`" title="Margem"></div>
                                </div>

                                <dl class="mt-4 space-y-2.5 text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                                        <dt class="text-marca-100/70">Custo total</dt>
                                        <dd class="tabular ml-auto font-medium text-white" x-text="moeda(resultado.custo_total_unitario)"></dd>
                                        <dd class="tabular w-14 text-right text-marca-100/50" x-text="pct(fatiaCusto, 1)"></dd>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                        <dt class="text-marca-100/70">Tributos</dt>
                                        <dd class="tabular ml-auto font-medium text-white" x-text="moeda(resultado.valor_tributos)"></dd>
                                        <dd class="tabular w-14 text-right text-marca-100/50" x-text="pct(fatiaTributos, 1)"></dd>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                        <dt class="text-marca-100/70">Margem</dt>
                                        <dd class="tabular ml-auto font-medium text-white" x-text="moeda(resultado.valor_margem_contribuicao)"></dd>
                                        <dd class="tabular w-14 text-right text-marca-100/50" x-text="pct(fatiaMargem, 1)"></dd>
                                    </div>
                                </dl>
                            </div>
                        </template>
                    </div>

                    {{-- Detalhamento --}}
                    <template x-if="resultado && ! erro">
                        <x-card titulo="Números do cálculo" icone="fa-magnifying-glass-chart">
                            <dl class="divide-y divide-slate-100 text-sm">
                                <div class="flex justify-between py-2.5">
                                    <dt class="text-slate-500">Rateio fixo por unidade</dt>
                                    <dd class="tabular font-medium text-slate-900" x-text="moeda(resultado.rateio_fixo_unitario)"></dd>
                                </div>
                                <div class="flex justify-between py-2.5">
                                    <dt class="text-slate-500">Custo total por unidade</dt>
                                    <dd class="tabular font-medium text-slate-900" x-text="moeda(resultado.custo_total_unitario)"></dd>
                                </div>
                                <div class="flex justify-between py-2.5">
                                    <dt class="text-slate-500">Carga tributária efetiva</dt>
                                    <dd class="tabular font-medium text-slate-900" x-text="pct(resultado.soma_aliquotas_efetivas)"></dd>
                                </div>
                                <div class="flex justify-between py-2.5">
                                    <dt class="text-slate-500">Divisor da fórmula</dt>
                                    <dd class="tabular font-medium text-slate-900" x-text="num(resultado.divisor, 6)"></dd>
                                </div>
                                <div class="flex justify-between py-2.5">
                                    <dt class="text-slate-500">Preço exato (4 casas)</dt>
                                    <dd class="tabular font-medium text-slate-900" x-text="moeda(resultado.preco_venda)"></dd>
                                </div>
                            </dl>
                        </x-card>
                    </template>

                    {{-- Alertas --}}
                    <template x-if="resultado && resultado.alertas && resultado.alertas.length">
                        <div class="space-y-3">
                            <template x-for="(alerta, i) in resultado.alertas" :key="i">
                                <div class="flex gap-3 rounded-xl border p-4 text-sm"
                                     :class="{
                                        'border-rose-200 bg-rose-50 text-rose-900': alerta.nivel === 'critico',
                                        'border-amber-200 bg-amber-50 text-amber-900': alerta.nivel === 'atencao',
                                        'border-marca-100 bg-marca-50 text-marca-900': alerta.nivel === 'info',
                                     }">
                                    <i class="fa-solid mt-0.5"
                                       :class="alerta.nivel === 'critico' ? 'fa-triangle-exclamation text-rose-600'
                                            : (alerta.nivel === 'atencao' ? 'fa-circle-exclamation text-amber-600' : 'fa-circle-info text-marca-600')"></i>
                                    <div class="min-w-0">
                                        <p class="font-semibold" x-text="alerta.titulo"></p>
                                        <p class="mt-0.5" x-text="alerta.mensagem"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Registro para auditoria --}}
                    @if ($item)
                        <x-card titulo="Registrar este preço" icone="fa-file-signature"
                                descricao="Grava o preço com toda a memória de cálculo no histórico imutável.">
                            <form method="POST" action="{{ route('precificacao.store') }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="item_id" :value="itemId" value="{{ $item->id }}">
                                <input type="hidden" name="custo_variavel_unitario" :value="custo">
                                <input type="hidden" name="margem_contribuicao" :value="margem">
                                <input type="hidden" name="volume_projetado" :value="volume">

                                <div>
                                    <label for="justificativa" class="block text-sm font-medium text-slate-700">Justificativa do preço</label>
                                    <textarea id="justificativa" name="justificativa" rows="3"
                                              placeholder="Ex.: reajuste por aumento de 12% no custo do insumo em julho/2026, conforme nota fiscal 4521."
                                              class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">{{ old('justificativa') }}</textarea>
                                    <p class="mt-1.5 text-sm text-slate-500">Reajustes relevantes exigem justa causa documentada (CDC, art. 39, X).</p>
                                </div>

                                <x-botao variante="sucesso" icone="fa-floppy-disk" class="w-full">
                                    Registrar precificação
                                </x-botao>
                            </form>
                        </x-card>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endsection
