@extends('layouts.app')

@section('titulo', 'Memória de cálculo')
@section('subtitulo', $precificacao->item_nome.' · '.$precificacao->calculado_em->format('d/m/Y \à\s H:i'))

@section('acoes')
    <x-botao href="{{ route('precificacao.index') }}" variante="neutro" icone="fa-arrow-left">Voltar</x-botao>
    <x-botao onclick="window.print()" variante="neutro" icone="fa-print" tipo="button">Imprimir</x-botao>
@endsection

@section('conteudo')
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">

            {{-- Resultado --}}
            <div class="overflow-hidden rounded-2xl bg-marca-950 shadow-lg shadow-marca-950/20">
                <div class="flex flex-wrap items-end justify-between gap-4 px-6 py-6">
                    <div>
                        <p class="text-sm font-medium text-marca-100/70">Preço formado</p>
                        <p class="tabular mt-1 text-4xl font-bold text-white">@moeda($precificacao->preco_venda)</p>
                        <p class="mt-1 text-sm text-marca-100/60">
                            {{ $precificacao->item_tipo === 'servico' ? 'Serviço' : 'Produto' }}
                            @if ($precificacao->item_sku) · {{ $precificacao->item_sku }} @endif
                            · markup @num($precificacao->markup)x
                        </p>
                    </div>

                    @if ($anterior)
                        @php
                            $variacao = (float) $anterior->preco_venda > 0
                                ? (((float) $precificacao->preco_venda - (float) $anterior->preco_venda) / (float) $anterior->preco_venda) * 100
                                : 0;
                        @endphp
                        <div class="rounded-xl bg-white/10 px-4 py-3 text-right">
                            <p class="text-xs text-marca-100/60">Preço anterior</p>
                            <p class="tabular text-sm font-medium text-white">@moeda($anterior->preco_venda)</p>
                            <p class="tabular mt-0.5 text-sm font-semibold {{ $variacao >= 0 ? 'text-amber-300' : 'text-emerald-300' }}">
                                <i class="fa-solid {{ $variacao >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
                                @pct($variacao)
                            </p>
                        </div>
                    @endif
                </div>

                @php
                    $preco = (float) $precificacao->preco_venda;
                    $fCusto = $preco > 0 ? ((float) $precificacao->custo_total_unitario / $preco) * 100 : 0;
                    $fTrib = $preco > 0 ? ((float) $precificacao->valor_tributos / $preco) * 100 : 0;
                    $fMarg = $preco > 0 ? ((float) $precificacao->valor_margem_contribuicao / $preco) * 100 : 0;
                @endphp
                <div class="border-t border-white/10 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-marca-100/50">Composição do preço</p>
                    <div class="mt-3 flex h-3 overflow-hidden rounded-full bg-white/10">
                        <div class="bg-slate-300" style="width: {{ $fCusto }}%"></div>
                        <div class="bg-amber-400" style="width: {{ $fTrib }}%"></div>
                        <div class="bg-emerald-400" style="width: {{ $fMarg }}%"></div>
                    </div>
                    <dl class="mt-4 space-y-2.5 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                            <dt class="text-marca-100/70">Custo total</dt>
                            <dd class="tabular ml-auto font-medium text-white">@moeda($precificacao->custo_total_unitario)</dd>
                            <dd class="tabular w-16 text-right text-marca-100/50">@pct($fCusto)</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                            <dt class="text-marca-100/70">Tributos</dt>
                            <dd class="tabular ml-auto font-medium text-white">@moeda($precificacao->valor_tributos)</dd>
                            <dd class="tabular w-16 text-right text-marca-100/50">@pct($fTrib)</dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            <dt class="text-marca-100/70">Margem de contribuição</dt>
                            <dd class="tabular ml-auto font-medium text-white">@moeda($precificacao->valor_margem_contribuicao)</dd>
                            <dd class="tabular w-16 text-right text-marca-100/50">@pct($fMarg)</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Passo a passo --}}
            <x-card titulo="Passo a passo do cálculo" icone="fa-list-ol"
                    descricao="Reprodução exata das operações realizadas, com os valores vigentes na data do cálculo.">
                <ol class="space-y-3">
                    @foreach ($precificacao->memoria_calculo as $passo)
                        <x-passo :ordem="$passo['ordem']"
                                 :titulo="$passo['titulo']"
                                 :formula="$passo['formula']"
                                 :substituicao="$passo['substituicao']"
                                 :resultado="$passo['resultado']"
                                 :explicacao="$passo['explicacao'] ?? null"
                                 :destaque="$passo['ordem'] === 5" />
                    @endforeach
                </ol>
            </x-card>

            {{-- Ficha técnica congelada no momento do cálculo --}}
            @if (! empty($precificacao->composicao_aplicada))
                @php
                    $totalFicha = collect($precificacao->composicao_aplicada)->sum('custo_total');
                    $custoUsado = (float) $precificacao->custo_variavel_unitario;
                @endphp
                <x-card titulo="Ficha técnica aplicada" icone="fa-clipboard-list"
                        descricao="Insumos e preços congelados na data do cálculo — este registro não muda. Para alterar quantidades, edite a ficha do item e gere um novo cálculo.">
                    @if ($precificacao->item)
                        <x-slot:acoes>
                            <x-botao href="{{ route('ficha-tecnica.edit', $precificacao->item) }}" variante="neutro"
                                     icone="fa-pen" class="!px-3 !py-2 text-xs">
                                Editar ficha atual
                            </x-botao>
                        </x-slot:acoes>
                    @endif

                    @if (abs($totalFicha - $custoUsado) > 0.01)
                        <x-alerta tipo="atencao" class="mb-4" titulo="Custo simulado diferente da ficha">
                            A ficha somava @moeda($totalFicha), mas o cálculo foi feito com @moeda($custoUsado),
                            informado manualmente no simulador.
                        </x-alerta>
                    @endif

                    <div class="-mx-5 overflow-x-auto sm:-mx-6">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-5 pb-2 sm:px-6">Insumo</th>
                                    <th class="px-3 pb-2">Conversão</th>
                                    <th class="px-3 pb-2 text-right">Qtd.</th>
                                    <th class="px-3 pb-2 text-right">Custo unit.</th>
                                    <th class="px-5 pb-2 text-right sm:px-6">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($precificacao->composicao_aplicada as $linha)
                                    <tr>
                                        <td class="px-5 py-2.5 sm:px-6">
                                            <p class="font-medium text-slate-800">{{ $linha['insumo'] }}</p>
                                            @if (! empty($linha['observacao']))
                                                <p class="text-xs italic text-slate-400">{{ $linha['observacao'] }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 text-xs text-slate-500">
                                            @if (($linha['rendimento'] ?? 1) != 1)
                                                1 {{ $linha['unidade_compra'] }} = @num($linha['rendimento'], 0) {{ $linha['unidade_uso'] }}
                                                <span class="block">@moeda($linha['preco_compra']) / {{ $linha['unidade_compra'] }}</span>
                                            @else
                                                direta
                                            @endif
                                            @if (($linha['perda_percentual'] ?? 0) > 0)
                                                <span class="block text-amber-600">perda @pct($linha['perda_percentual'])</span>
                                            @endif
                                        </td>
                                        <td class="tabular px-3 py-2.5 text-right text-slate-600">
                                            @num($linha['quantidade'], 2) {{ $linha['unidade_uso'] }}
                                        </td>
                                        <td class="tabular px-3 py-2.5 text-right text-slate-600">
                                            R$ {{ number_format($linha['custo_unitario_uso'], 4, ',', '.') }}
                                        </td>
                                        <td class="tabular px-5 py-2.5 text-right font-medium text-slate-900 sm:px-6">
                                            @moeda($linha['custo_total'])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-slate-200">
                                    <td colspan="4" class="px-5 pt-3 font-semibold text-slate-700 sm:px-6">Custo de produção por unidade</td>
                                    <td class="tabular px-5 pt-3 text-right text-lg font-bold text-emerald-700 sm:px-6">@moeda($totalFicha)</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-card>
            @endif

            {{-- Justificativa --}}
            @if ($precificacao->justificativa || $precificacao->observacoes)
                <x-card titulo="Justificativa registrada" icone="fa-file-signature"
                        descricao="Fundamento do preço praticado, exigível em caso de questionamento sobre reajuste (CDC, art. 39, X).">
                    @if ($precificacao->justificativa)
                        <p class="whitespace-pre-line text-sm text-slate-700">{{ $precificacao->justificativa }}</p>
                    @endif
                    @if ($precificacao->observacoes)
                        <p class="mt-4 whitespace-pre-line border-t border-slate-100 pt-4 text-sm text-slate-500">{{ $precificacao->observacoes }}</p>
                    @endif
                </x-card>
            @endif
        </div>

        {{-- Coluna lateral --}}
        <div class="space-y-6">
            <x-card titulo="Variáveis usadas" icone="fa-sliders">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Custo variável unitário</dt>
                        <dd class="tabular font-medium text-slate-900">@moeda($precificacao->custo_variavel_unitario)</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Custo fixo total</dt>
                        <dd class="tabular font-medium text-slate-900">@moeda($precificacao->custo_fixo_total)</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Volume projetado</dt>
                        <dd class="tabular font-medium text-slate-900">@num($precificacao->volume_projetado, 0)</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Rateio fixo unitário</dt>
                        <dd class="tabular font-medium text-slate-900">@moeda($precificacao->rateio_fixo_unitario)</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Margem desejada</dt>
                        <dd class="tabular font-medium text-slate-900">@pct($precificacao->margem_contribuicao)</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Divisor</dt>
                        <dd class="tabular font-medium text-slate-900">@num($precificacao->divisor, 6)</dd>
                    </div>
                </dl>
            </x-card>

            <x-card titulo="Tributos aplicados" icone="fa-percent"
                    descricao="Alíquotas efetivas congeladas no momento do cálculo.">
                @forelse ($precificacao->tributos_aplicados as $tributo)
                    <div class="border-b border-slate-100 py-3 last:border-0 last:pb-0 first:pt-0">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="font-medium text-slate-900">{{ $tributo['sigla'] }}</p>
                            <p class="tabular font-semibold text-amber-700">@pct($tributo['aliquota_efetiva'])</p>
                        </div>
                        <p class="text-xs text-slate-500">{{ $tributo['nome'] }}</p>
                        @if (($tributo['aliquota_nominal'] ?? 0) > ($tributo['aliquota_efetiva'] ?? 0))
                            <p class="mt-1 text-xs text-slate-400">
                                nominal @pct($tributo['aliquota_nominal']) · economia de
                                @pct($tributo['aliquota_nominal'] - $tributo['aliquota_efetiva'])
                            </p>
                        @endif
                        @if (! empty($tributo['base_legal']))
                            <p class="mt-1 text-xs text-slate-400">{{ $tributo['base_legal'] }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Nenhum tributo foi aplicado neste cálculo.</p>
                @endforelse

                <x-slot:rodape>
                    <div class="flex justify-between">
                        <span>Carga tributária efetiva total</span>
                        <span class="tabular font-semibold text-slate-900">@pct($precificacao->soma_aliquotas_efetivas)</span>
                    </div>
                </x-slot:rodape>
            </x-card>

            <x-card titulo="Autenticidade" icone="fa-fingerprint"
                    descricao="Assinatura do conteúdo do cálculo, para comprovar que o registro não foi alterado.">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">Registrado em</dt>
                        <dd class="font-medium text-slate-900">{{ $precificacao->calculado_em->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Responsável</dt>
                        <dd class="font-medium text-slate-900">{{ $precificacao->user?->name ?? 'Não identificado' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Hash SHA-256</dt>
                        <dd class="mt-1 break-all rounded-lg bg-slate-900 px-3 py-2 font-mono text-[11px] text-slate-100">
                            {{ $precificacao->hash_auditoria ?? 'não gerado' }}
                        </dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
@endsection
