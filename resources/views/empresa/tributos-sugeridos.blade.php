@extends('layouts.app')

@section('titulo', 'Tributos do regime')
@section('subtitulo', $empresa->regime_label.' · '.\Illuminate\Support\Str::before($empresa->atividade_label, ' ('))

@section('acoes')
    <x-botao href="{{ route('tributos.index') }}" variante="neutro" icone="fa-percent">Ver tributos cadastrados</x-botao>
@endsection

@section('conteudo')
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @if ($empresa->regime_tributario === 'mei')
                <x-alerta tipo="info" titulo="MEI não recolhe percentual sobre a receita">
                    O MEI paga um valor fixo mensal no DAS, independentemente do faturamento. Por isso não
                    há alíquota para entrar no divisor da fórmula. Lance o DAS como
                    <a href="{{ route('custos-fixos.index') }}" class="font-medium underline">custo fixo mensal</a>
                    — assim ele é rateado pelo volume e o preço fica correto.
                </x-alerta>
            @endif

            @if ($incompativeis !== [])
                <x-alerta tipo="critico" titulo="Tributos incompatíveis com o regime">
                    <p>
                        {{ implode(', ', $incompativeis) }}
                        {{ count($incompativeis) === 1 ? 'está cadastrado' : 'estão cadastrados' }}
                        mas não {{ count($incompativeis) === 1 ? 'pertence' : 'pertencem' }} ao
                        {{ $empresa->regime_label }} com atividade de
                        {{ \Illuminate\Support\Str::before($empresa->atividade_label, ' (') }}.
                    </p>
                    <p class="mt-1">
                        Se {{ count($incompativeis) === 1 ? 'ele continuar ativo' : 'eles continuarem ativos' }},
                        a carga tributária do preço fica maior que a real.
                        <a href="{{ route('tributos.index') }}" class="font-medium underline">Revisar cadastro</a>.
                    </p>
                </x-alerta>
            @endif

            <x-card titulo="O que este regime comporta" icone="fa-list-check"
                    descricao="Alíquotas de referência para partida. A efetiva depende de créditos, faixa e legislação local.">
                @if ($sugeridos === [])
                    <x-vazio icone="fa-circle-info" titulo="Nenhum tributo percentual neste regime"
                             descricao="Não há alíquota sobre receita a cadastrar. O custo tributário entra como custo fixo." />
                @else
                    <div class="-mx-5 overflow-x-auto sm:-mx-6">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-5 pb-2 sm:px-6">Tributo</th>
                                    <th class="px-3 pb-2">Incide sobre</th>
                                    <th class="px-3 pb-2">Base</th>
                                    <th class="px-3 pb-2 text-right">Referência</th>
                                    <th class="px-5 pb-2 text-right sm:px-6">Situação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($sugeridos as $tributo)
                                    @php $jaExiste = $cadastrados->has($tributo['sigla']); @endphp
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-5 py-3 sm:px-6">
                                            <p class="font-medium text-slate-900">{{ $tributo['sigla'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $tributo['nome'] }}</p>
                                            <p class="mt-0.5 text-xs text-slate-400">{{ $tributo['base_legal'] }}</p>
                                            @if (! empty($tributo['observacoes']))
                                                <p class="mt-1 text-xs italic text-amber-700">{{ $tributo['observacoes'] }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <x-badge :cor="match ($tributo['aplica_a']) { 'produto' => 'marca', 'servico' => 'ambar', default => 'slate' }">
                                                {{ \App\Models\Tributo::APLICACOES[$tributo['aplica_a']] }}
                                            </x-badge>
                                        </td>
                                        <td class="px-3 py-3">
                                            <x-badge :cor="$tributo['base_calculo'] === 'por_fora' ? 'verde' : 'slate'">
                                                {{ $tributo['base_calculo'] === 'por_fora' ? 'por fora' : 'por dentro' }}
                                            </x-badge>
                                        </td>
                                        <td class="tabular px-3 py-3 text-right font-semibold text-amber-700">
                                            @pct($tributo['aliquota_efetiva'])
                                        </td>
                                        <td class="px-5 py-3 text-right sm:px-6">
                                            @if ($jaExiste)
                                                <span class="text-xs text-emerald-600">
                                                    <i class="fa-solid fa-check"></i> cadastrado a
                                                    @pct($cadastrados[$tributo['sigla']]->aliquota_efetiva)
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">não cadastrado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('empresa.aplicar-sugestao', $empresa) }}"
                          class="mt-5 border-t border-slate-100 pt-5">
                        @csrf
                        <x-botao variante="sucesso" icone="fa-wand-magic-sparkles">
                            Cadastrar os que faltam
                        </x-botao>
                        <p class="mt-2 text-sm text-slate-500">
                            Cria apenas os tributos ausentes. Os já cadastrados mantêm as alíquotas que você ajustou.
                        </p>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Por dentro x por fora" icone="fa-scale-unbalanced">
                <div class="space-y-4 text-sm">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="font-medium text-slate-900">Por dentro</p>
                        <p class="mt-1 text-slate-500">
                            O tributo está <strong>embutido</strong> no preço. Vendendo a R$ 100,00 com
                            ICMS de 18%, R$ 18,00 já estão dentro desses R$ 100,00. Entra no divisor da fórmula.
                        </p>
                        <p class="mt-2 text-xs text-slate-400">ICMS, PIS, COFINS, ISS</p>
                    </div>

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
                        <p class="font-medium text-emerald-900">Por fora</p>
                        <p class="mt-1 text-emerald-800">
                            O tributo é <strong>somado</strong> ao preço. Preço líquido de R$ 100,00 com
                            26,5% por fora resulta em R$ 126,50 cobrados. A receita da empresa continua sendo R$ 100,00.
                        </p>
                        <p class="mt-2 text-xs text-emerald-700">IPI hoje · CBS e IBS na reforma</p>
                    </div>
                </div>
            </x-card>

            <x-card titulo="Enquadramento atual" icone="fa-building">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Regime</dt>
                        <dd class="font-medium text-slate-900">{{ $empresa->regime_label }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Atividade</dt>
                        <dd class="font-medium text-slate-900">
                            {{ \Illuminate\Support\Str::before($empresa->atividade_label, ' (') }}
                        </dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Receita 12 meses</dt>
                        <dd class="tabular font-medium text-slate-900">@moeda($empresa->faturamento_12_meses)</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Vigente desde</dt>
                        <dd class="font-medium text-slate-900">
                            {{ $empresa->regime_vigente_desde?->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                </dl>
                <x-botao href="{{ route('empresa.edit', $empresa) }}" variante="neutro" icone="fa-pen" class="mt-4 w-full">
                    Alterar enquadramento
                </x-botao>
            </x-card>
        </div>
    </div>
@endsection
