@extends('layouts.app')

@section('titulo', 'Painel')
@section('subtitulo', $empresa->nome_fantasia ?: $empresa->razao_social)

@section('acoes')
    <x-botao href="{{ route('precificacao.simulador') }}" icone="fa-calculator">Simular preço</x-botao>
@endsection

@section('conteudo')
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi rotulo="Custo fixo mensal" icone="fa-building-columns" cor="marca"
               valor="R$ {{ number_format($custoFixoTotal, 2, ',', '.') }}"
               detalhe="Rateado entre {{ number_format((float) $empresa->volume_projetado_mensal, 0, ',', '.') }} un/mês por padrão" />

        <x-kpi rotulo="Carga tributária efetiva" icone="fa-percent" cor="ambar"
               valor="{{ number_format($cargaProduto, 2, ',', '.') }}%"
               detalhe="Produtos · serviços em {{ number_format($cargaServico, 2, ',', '.') }}%" />

        <x-kpi rotulo="Itens ativos" icone="fa-box-open" cor="slate"
               valor="{{ $totalItens }}"
               detalhe="{{ $totalTributos }} tributos configurados" />

        <x-kpi rotulo="Margem média praticada" icone="fa-chart-line" cor="verde"
               valor="{{ number_format($margemMedia, 2, ',', '.') }}%"
               detalhe="{{ $totalPrecificacoes }} {{ $totalPrecificacoes === 1 ? 'preço registrado' : 'preços registrados' }}" />
    </div>

    @if ($itensSemPreco > 0)
        <x-alerta tipo="atencao" class="mt-6" titulo="{{ $itensSemPreco }} {{ $itensSemPreco === 1 ? 'item ainda não foi precificado' : 'itens ainda não foram precificados' }}">
            Sem um preço registrado, não existe memória de cálculo para justificar o valor cobrado.
            <a href="{{ route('itens.index') }}" class="font-medium underline">Ver itens</a>.
        </x-alerta>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <x-card titulo="Últimos preços registrados" icone="fa-clock-rotate-left"
                    descricao="Cada registro guarda a fórmula, as alíquotas e o volume usados no momento do cálculo.">
                <x-slot:acoes>
                    <x-botao href="{{ route('precificacao.index') }}" variante="fantasma" class="!px-2 !py-1 text-sm">
                        ver tudo <i class="fa-solid fa-arrow-right text-xs"></i>
                    </x-botao>
                </x-slot:acoes>

                @if ($precificacoes->isEmpty())
                    <x-vazio icone="fa-receipt" titulo="Nenhum preço registrado ainda"
                             descricao="Use o simulador para formar o primeiro preço e gravar sua memória de cálculo.">
                        <x-botao href="{{ route('precificacao.simulador') }}" icone="fa-calculator">Abrir simulador</x-botao>
                    </x-vazio>
                @else
                    <div class="-mx-5 overflow-x-auto sm:-mx-6">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-5 pb-2 sm:px-6">Item</th>
                                    <th class="px-3 pb-2 text-right">Custo</th>
                                    <th class="px-3 pb-2 text-right">Tributos</th>
                                    <th class="px-3 pb-2 text-right">Margem</th>
                                    <th class="px-5 pb-2 text-right sm:px-6">Preço</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($precificacoes as $p)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-5 py-3 sm:px-6">
                                            <a href="{{ route('precificacao.show', $p) }}" class="font-medium text-slate-900 hover:text-marca-700">
                                                {{ $p->item_nome }}
                                            </a>
                                            <p class="text-xs text-slate-500">{{ $p->calculado_em->format('d/m/Y H:i') }}</p>
                                        </td>
                                        <td class="tabular px-3 py-3 text-right text-slate-600">@moeda($p->custo_total_unitario)</td>
                                        <td class="tabular px-3 py-3 text-right text-amber-700">@pct($p->soma_aliquotas_efetivas)</td>
                                        <td class="tabular px-3 py-3 text-right text-emerald-700">@pct($p->margem_contribuicao)</td>
                                        <td class="tabular px-5 py-3 text-right font-semibold text-slate-900 sm:px-6">@moeda($p->preco_venda)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="A fórmula em uso" icone="fa-square-root-variable">
                <div class="rounded-xl bg-slate-900 p-4 text-center font-mono text-xs leading-relaxed text-slate-100">
                    <p class="text-emerald-300">Preço de Venda</p>
                    <p class="my-1 text-slate-400">=</p>
                    <p>Custo Variável + Rateio Fixo</p>
                    <p class="my-1 border-t border-slate-700 pt-1">1 − (Alíquotas Efetivas + Margem)</p>
                </div>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-600">
                    <li class="flex gap-2">
                        <i class="fa-solid fa-check mt-1 text-emerald-600"></i>
                        <span>Alíquotas <strong>efetivas</strong>, não nominais — é o que sai do caixa.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="fa-solid fa-check mt-1 text-emerald-600"></i>
                        <span>Margem sobre o <strong>preço final</strong>, por isso entra no divisor.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="fa-solid fa-check mt-1 text-emerald-600"></i>
                        <span>Todo preço fica com <strong>memória de cálculo</strong> registrada.</span>
                    </li>
                </ul>
            </x-card>

            <x-card titulo="Base do cálculo" icone="fa-database">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between py-2.5">
                        <dt class="text-slate-500">Regime tributário</dt>
                        <dd><x-badge cor="marca">{{ $empresa->regime_label }}</x-badge></dd>
                    </div>
                    <div class="flex items-center justify-between py-2.5">
                        <dt class="text-slate-500">Custos fixos</dt>
                        <dd class="tabular font-medium text-slate-900">@moeda($custoFixoTotal)</dd>
                    </div>
                    <div class="flex items-center justify-between py-2.5">
                        <dt class="text-slate-500">Preço médio registrado</dt>
                        <dd class="tabular font-medium text-slate-900">@moeda($precoMedio)</dd>
                    </div>
                </dl>
                @can('administrar')
                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-botao href="{{ route('custos-fixos.index') }}" variante="neutro" icone="fa-building-columns" class="!px-3 !py-2 text-xs">Custos fixos</x-botao>
                        <x-botao href="{{ route('tributos.index') }}" variante="neutro" icone="fa-percent" class="!px-3 !py-2 text-xs">Tributos</x-botao>
                    </div>
                @endcan
            </x-card>
        </div>
    </div>
@endsection
