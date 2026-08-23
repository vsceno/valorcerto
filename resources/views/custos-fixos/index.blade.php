@extends('layouts.app')

@section('titulo', 'Custos fixos')
@section('subtitulo', 'Despesas que existem independentemente da venda — rateadas por unidade no cálculo do preço')

@section('acoes')
    <x-botao href="{{ route('custos-fixos.create') }}" icone="fa-plus">Novo custo fixo</x-botao>
@endsection

@section('conteudo')
    @php $volumePadrao = (float) ($empresa?->volume_projetado_mensal ?? 1); @endphp

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <x-kpi rotulo="Total mensal" icone="fa-building-columns" cor="marca"
               valor="R$ {{ number_format($total, 2, ',', '.') }}"
               detalhe="{{ $custos->where('ativo', true)->count() }} lançamentos ativos" />

        <x-kpi rotulo="Rateio por unidade" icone="fa-divide" cor="ambar"
               valor="R$ {{ number_format($volumePadrao > 0 ? $total / $volumePadrao : 0, 2, ',', '.') }}"
               detalhe="Sobre o volume padrão de {{ number_format($volumePadrao, 0, ',', '.') }} un/mês" />

        <x-kpi rotulo="Maior grupo" icone="fa-chart-pie" cor="slate"
               valor="R$ {{ number_format($porGrupo->first() ?? 0, 2, ',', '.') }}"
               detalhe="{{ $porGrupo->keys()->first() ? \App\Models\CustoFixo::GRUPOS[$porGrupo->keys()->first()] : 'sem lançamentos' }}" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <x-card titulo="Lançamentos" icone="fa-list">
                @if ($custos->isEmpty())
                    <x-vazio icone="fa-building-columns" titulo="Nenhum custo fixo cadastrado"
                             descricao="Sem custos fixos, o preço cobre apenas o custo variável — e a estrutura da empresa fica descoberta.">
                        <x-botao href="{{ route('custos-fixos.create') }}" icone="fa-plus">Cadastrar custo fixo</x-botao>
                    </x-vazio>
                @else
                    <div class="-mx-5 overflow-x-auto sm:-mx-6">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-5 pb-2 sm:px-6">Descrição</th>
                                    <th class="px-3 pb-2">Grupo</th>
                                    <th class="px-3 pb-2 text-right">Valor mensal</th>
                                    <th class="px-3 pb-2 text-right">% do total</th>
                                    <th class="px-5 pb-2 text-right sm:px-6">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($custos as $custo)
                                    <tr class="transition hover:bg-slate-50 {{ $custo->ativo ? '' : 'opacity-50' }}">
                                        <td class="px-5 py-3 font-medium text-slate-900 sm:px-6">
                                            {{ $custo->descricao }}
                                            @unless ($custo->ativo)
                                                <span class="ml-1 text-xs font-normal text-rose-600">inativo</span>
                                            @endunless
                                        </td>
                                        <td class="px-3 py-3">
                                            <x-badge cor="slate">{{ \Illuminate\Support\Str::before($custo->grupo_label, ' (') }}</x-badge>
                                        </td>
                                        <td class="tabular px-3 py-3 text-right font-semibold text-slate-900">@moeda($custo->valor_mensal)</td>
                                        <td class="tabular px-3 py-3 text-right text-slate-500">
                                            @pct($total > 0 && $custo->ativo ? ((float) $custo->valor_mensal / $total) * 100 : 0)
                                        </td>
                                        <td class="px-5 py-3 text-right sm:px-6">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('custos-fixos.edit', $custo) }}"
                                                   class="rounded-lg px-2 py-1.5 text-slate-500 hover:bg-slate-100" title="Editar">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <form method="POST" action="{{ route('custos-fixos.destroy', $custo) }}"
                                                      onsubmit="return confirm('Remover {{ $custo->descricao }}?')">
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
                            <tfoot>
                                <tr class="border-t-2 border-slate-200">
                                    <td colspan="2" class="px-5 pt-3 font-semibold text-slate-700 sm:px-6">Total ativo</td>
                                    <td class="tabular px-3 pt-3 text-right text-lg font-bold text-slate-900">@moeda($total)</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Distribuição por grupo" icone="fa-chart-pie">
                @forelse ($porGrupo as $grupo => $valor)
                    @php $fatia = $total > 0 ? ($valor / $total) * 100 : 0; @endphp
                    <div class="py-2.5 first:pt-0">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="truncate text-slate-600">{{ \Illuminate\Support\Str::before(\App\Models\CustoFixo::GRUPOS[$grupo], ' (') }}</span>
                            <span class="tabular shrink-0 font-medium text-slate-900">@moeda($valor)</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-marca-500" style="width: {{ $fatia }}%"></div>
                        </div>
                        <p class="tabular mt-1 text-xs text-slate-400">@pct($fatia) do total</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Nenhum custo ativo para distribuir.</p>
                @endforelse
            </x-card>

            <x-alerta tipo="info" titulo="Por que o volume importa tanto">
                O mesmo custo fixo dividido por um volume maior derruba o preço por unidade.
                Projetar volume acima do realizado subestima o custo e corrói a margem.
            </x-alerta>
        </div>
    </div>
@endsection
