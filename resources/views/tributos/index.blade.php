@extends('layouts.app')

@section('titulo', 'Tributos')
@section('subtitulo', 'Alíquotas efetivas que entram no divisor da fórmula — é o que sai do caixa, não o que está na tabela')

@section('acoes')
    <x-botao href="{{ route('tributos.create') }}" icone="fa-plus">Novo tributo</x-botao>
@endsection

@section('conteudo')
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi rotulo="Carga sobre produtos" icone="fa-box" cor="marca"
               valor="{{ number_format($cargaProduto, 2, ',', '.') }}%"
               detalhe="ICMS, PIS, COFINS e demais incidências" />

        <x-kpi rotulo="Carga sobre serviços" icone="fa-screwdriver-wrench" cor="ambar"
               valor="{{ number_format($cargaServico, 2, ',', '.') }}%"
               detalhe="ISS, PIS, COFINS e demais incidências" />

        <x-kpi rotulo="Tributos ativos" icone="fa-percent" cor="slate"
               valor="{{ $tributos->where('ativo', true)->count() }}"
               detalhe="de {{ $tributos->count() }} cadastrados" />

        <x-kpi rotulo="Regime" icone="fa-scale-balanced" cor="verde"
               valor="{{ $empresa?->regime_label ?? '—' }}"
               detalhe="Define quais tributos incidem" />
    </div>

    @if ($cargaProduto >= 85 || $cargaServico >= 85)
        <x-alerta tipo="critico" class="mt-6" titulo="Carga tributária muito alta">
            Com alíquotas somando perto de 100%, o divisor da fórmula tende a zero e o preço dispara.
            Revise se as alíquotas cadastradas são realmente as efetivas.
        </x-alerta>
    @endif

    <x-card class="mt-6" titulo="Alíquotas cadastradas" icone="fa-percent"
            descricao="A alíquota efetiva é a nominal já líquida de créditos e reduções de base de cálculo.">
        @if ($tributos->isEmpty())
            <x-vazio icone="fa-percent" titulo="Nenhum tributo cadastrado"
                     descricao="Sem alíquotas, o preço é formado apenas sobre custo e margem — o que não reflete a realidade fiscal.">
                <x-botao href="{{ route('tributos.create') }}" icone="fa-plus">Cadastrar tributo</x-botao>
            </x-vazio>
        @else
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 pb-2 sm:px-6">Tributo</th>
                            <th class="px-3 pb-2">Incide sobre</th>
                            <th class="px-3 pb-2 text-right">Nominal</th>
                            <th class="px-3 pb-2 text-right">Efetiva</th>
                            <th class="px-3 pb-2 text-right">Diferença</th>
                            <th class="px-5 pb-2 text-right sm:px-6">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tributos as $tributo)
                            <tr class="transition hover:bg-slate-50 {{ $tributo->ativo ? '' : 'opacity-50' }}">
                                <td class="px-5 py-3 sm:px-6">
                                    <p class="font-medium text-slate-900">{{ $tributo->sigla }}</p>
                                    <p class="text-xs text-slate-500">{{ $tributo->nome }}</p>
                                    @if ($tributo->base_legal)
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $tributo->base_legal }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <x-badge :cor="match ($tributo->aplica_a) { 'produto' => 'marca', 'servico' => 'ambar', default => 'slate' }">
                                        {{ \App\Models\Tributo::APLICACOES[$tributo->aplica_a] }}
                                    </x-badge>
                                </td>
                                <td class="tabular px-3 py-3 text-right text-slate-400 line-through">@pct($tributo->aliquota_nominal)</td>
                                <td class="tabular px-3 py-3 text-right font-semibold text-amber-700">@pct($tributo->aliquota_efetiva)</td>
                                <td class="tabular px-3 py-3 text-right text-emerald-600">
                                    @if ($tributo->economia_fiscal > 0)
                                        −@pct($tributo->economia_fiscal)
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('tributos.edit', $tributo) }}"
                                           class="rounded-lg px-2 py-1.5 text-slate-500 hover:bg-slate-100" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" action="{{ route('tributos.destroy', $tributo) }}"
                                              onsubmit="return confirm('Remover {{ $tributo->sigla }}? Cálculos já registrados não são afetados.')">
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
                </table>
            </div>
        @endif
    </x-card>
@endsection
