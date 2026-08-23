@extends('layouts.app')

@section('titulo', $item->nome)
@section('subtitulo', ($item->sku ? $item->sku.' · ' : '').$item->tipo_label.' · '.($item->categoria?->nome ?? 'sem categoria'))

@section('acoes')
    <x-botao href="{{ route('precificacao.simulador', ['item' => $item->id]) }}" icone="fa-calculator">Simular preço</x-botao>
    <x-botao href="{{ route('ficha-tecnica.edit', $item) }}" variante="neutro" icone="fa-clipboard-list">Ficha técnica</x-botao>
    <x-botao href="{{ route('itens.edit', $item) }}" variante="neutro" icone="fa-pen">Editar</x-botao>
@endsection

@section('conteudo')
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card titulo="Evolução do preço" icone="fa-chart-line"
                    descricao="Cada registro é imutável e guarda a memória de cálculo do momento em que foi criado.">
                @if ($item->precificacoes->isEmpty())
                    <x-vazio icone="fa-receipt" titulo="Este item ainda não foi precificado"
                             descricao="Sem preço registrado não há memória de cálculo para justificar o valor cobrado.">
                        <x-botao href="{{ route('precificacao.simulador', ['item' => $item->id]) }}" icone="fa-calculator">
                            Formar preço agora
                        </x-botao>
                    </x-vazio>
                @else
                    <div class="-mx-5 overflow-x-auto sm:-mx-6">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-5 pb-2 sm:px-6">Data</th>
                                    <th class="px-3 pb-2 text-right">Custo unit.</th>
                                    <th class="px-3 pb-2 text-right">Tributos</th>
                                    <th class="px-3 pb-2 text-right">Margem</th>
                                    <th class="px-3 pb-2 text-right">Preço</th>
                                    <th class="px-5 pb-2 text-right sm:px-6"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($item->precificacoes as $p)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-5 py-3 text-slate-600 sm:px-6">{{ $p->calculado_em->format('d/m/Y H:i') }}</td>
                                        <td class="tabular px-3 py-3 text-right text-slate-600">@moeda($p->custo_total_unitario)</td>
                                        <td class="tabular px-3 py-3 text-right text-amber-700">@pct($p->soma_aliquotas_efetivas)</td>
                                        <td class="tabular px-3 py-3 text-right text-emerald-700">@pct($p->margem_contribuicao)</td>
                                        <td class="tabular px-3 py-3 text-right font-semibold text-slate-900">@moeda($p->preco_venda)</td>
                                        <td class="px-5 py-3 text-right sm:px-6">
                                            <a href="{{ route('precificacao.show', $p) }}"
                                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-marca-700 hover:bg-marca-50">
                                                <i class="fa-solid fa-list-ol"></i> Memória
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            @if ($item->descricao)
                <x-card titulo="Descrição" icone="fa-align-left">
                    <p class="whitespace-pre-line text-sm text-slate-700">{{ $item->descricao }}</p>
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card titulo="Cadastro" icone="fa-clipboard-list">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Tipo</dt>
                        <dd><x-badge :cor="$item->tipo === 'servico' ? 'ambar' : 'marca'">{{ $item->tipo_label }}</x-badge></dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Unidade</dt>
                        <dd class="font-medium text-slate-900">{{ $item->unidade_medida }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Custo variável unitário</dt>
                        <dd class="text-right">
                            <span class="tabular font-medium text-slate-900">@moeda($item->custoVariavelEfetivo())</span>
                            @if ($item->temFichaTecnica())
                                <span class="mt-0.5 block text-xs text-emerald-600">
                                    <i class="fa-solid fa-clipboard-list"></i> apurado na ficha técnica
                                </span>
                            @else
                                <span class="mt-0.5 block text-xs text-slate-400">informado no cadastro</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Margem desejada</dt>
                        <dd class="tabular font-medium text-emerald-700">@pct($item->margem_contribuicao_desejada)</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Volume de rateio</dt>
                        <dd class="tabular font-medium text-slate-900">@num($item->volumeParaRateio(), 0) {{ $item->unidade_medida }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Situação</dt>
                        <dd>
                            <x-badge :cor="$item->ativo ? 'verde' : 'rosa'">
                                {{ $item->ativo ? 'Ativo' : 'Inativo' }}
                            </x-badge>
                        </dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>
@endsection
