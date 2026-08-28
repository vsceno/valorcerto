@extends('layouts.app')

@section('titulo', 'Empresas')
@section('subtitulo', 'Cada empresa tem seu regime, seus tributos e seus custos fixos')

@section('acoes')
    <x-botao href="{{ route('empresa.create') }}" icone="fa-plus">Nova empresa</x-botao>
@endsection

@section('conteudo')
    @if ($empresas->isEmpty())
        <x-vazio icone="fa-building" titulo="Nenhuma empresa cadastrada"
                 descricao="O regime tributário e a atividade definem quais tributos entram no preço. Sem empresa, não há como calcular.">
            <x-botao href="{{ route('empresa.create') }}" icone="fa-plus">Cadastrar empresa</x-botao>
        </x-vazio>
    @else
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach ($empresas as $empresa)
                @php
                    $ehAtual = $atual?->id === $empresa->id;
                    $incompativeis = $empresa->tributosIncompativeis();
                @endphp

                <x-card @class(['ring-2 ring-marca-500' => $ehAtual])>
                    <div class="flex items-start gap-4">
                        <span @class([
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                            'bg-marca-600 text-white' => $ehAtual,
                            'bg-slate-100 text-slate-500' => ! $ehAtual,
                        ])>
                            <i class="fa-solid fa-building"></i>
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">
                                    {{ $empresa->nome_fantasia ?: $empresa->razao_social }}
                                </p>
                                @if ($ehAtual)
                                    <x-badge cor="marca" icone="fa-circle-check">em uso</x-badge>
                                @endif
                                @unless ($empresa->ativo)
                                    <x-badge cor="rosa">inativa</x-badge>
                                @endunless
                            </div>

                            <p class="truncate text-sm text-slate-500">{{ $empresa->razao_social }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $empresa->cnpj ?: 'sem CNPJ' }}
                                @if ($empresa->municipio) · {{ $empresa->municipio }}/{{ $empresa->uf }} @endif
                            </p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <x-badge cor="marca" icone="fa-scale-balanced">{{ $empresa->regime_label }}</x-badge>
                                <x-badge cor="slate">{{ \Illuminate\Support\Str::before($empresa->atividade_label, ' (') }}</x-badge>
                            </div>

                            <dl class="mt-4 grid grid-cols-3 gap-3 border-t border-slate-100 pt-3 text-center text-sm">
                                <div>
                                    <dt class="text-xs text-slate-500">Itens</dt>
                                    <dd class="tabular font-semibold text-slate-900">{{ $empresa->itens_count }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Tributos</dt>
                                    <dd class="tabular font-semibold text-slate-900">{{ $empresa->tributos_count }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Preços</dt>
                                    <dd class="tabular font-semibold text-slate-900">{{ $empresa->precificacoes_count }}</dd>
                                </div>
                            </dl>

                            @if ($incompativeis !== [])
                                <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    {{ implode(', ', $incompativeis) }}
                                    {{ count($incompativeis) === 1 ? 'não pertence' : 'não pertencem' }}
                                    ao regime {{ $empresa->regime_label }}.
                                    <a href="{{ route('empresa.tributos-sugeridos', $empresa) }}" class="font-medium underline">Revisar</a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <x-slot:rodape>
                        <div class="flex flex-wrap items-center gap-2">
                            @unless ($ehAtual)
                                @if ($empresa->ativo)
                                    <form method="POST" action="{{ route('empresa.selecionar', $empresa) }}">
                                        @csrf
                                        <x-botao icone="fa-arrow-right-arrow-left" class="!px-3 !py-1.5 text-xs">
                                            Operar sobre esta
                                        </x-botao>
                                    </form>
                                @endif
                            @endunless

                            <x-botao href="{{ route('empresa.tributos-sugeridos', $empresa) }}" variante="neutro"
                                     icone="fa-percent" class="!px-3 !py-1.5 text-xs">
                                Tributos do regime
                            </x-botao>

                            <x-botao href="{{ route('empresa.edit', $empresa) }}" variante="neutro"
                                     icone="fa-pen" class="!px-3 !py-1.5 text-xs">
                                Editar
                            </x-botao>

                            <form method="POST" action="{{ route('empresa.destroy', $empresa) }}" class="ml-auto"
                                  onsubmit="return confirm('Remover esta empresa?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-lg px-2 py-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Remover">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </x-slot:rodape>
                </x-card>
            @endforeach
        </div>
    @endif
@endsection
