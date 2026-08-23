@props([
    'nome',
    'rotulo',
    'tipo' => 'text',
    'valor' => null,
    'ajuda' => null,
    'prefixo' => null,
    'sufixo' => null,
    'obrigatorio' => false,
])

@php
    $erro = $errors->first($nome);
    $valorFinal = old($nome, $valor);
@endphp

<div>
    <label for="{{ $nome }}" class="block text-sm font-medium text-slate-700">
        {{ $rotulo }}
        @if ($obrigatorio)<span class="text-rose-500">*</span>@endif
    </label>

    <div class="mt-1.5 flex rounded-lg shadow-sm">
        @if ($prefixo)
            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">{{ $prefixo }}</span>
        @endif

        <input type="{{ $tipo }}"
               id="{{ $nome }}"
               name="{{ $nome }}"
               value="{{ $valorFinal }}"
               {{ $attributes->class([
                   'block w-full min-w-0 border px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-inset',
                   'tabular' => in_array($tipo, ['number'], true),
                   'rounded-l-lg' => ! $prefixo,
                   'rounded-r-lg' => ! $sufixo,
                   'border-rose-300 focus:border-rose-500 focus:ring-rose-200' => $erro,
                   'border-slate-300 focus:border-marca-500 focus:ring-marca-200' => ! $erro,
               ]) }}>

        @if ($sufixo)
            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">{{ $sufixo }}</span>
        @endif
    </div>

    @if ($erro)
        <p class="mt-1.5 flex items-center gap-1.5 text-sm text-rose-600">
            <i class="fa-solid fa-circle-exclamation text-xs"></i>{{ $erro }}
        </p>
    @elseif ($ajuda)
        <p class="mt-1.5 text-sm text-slate-500">{{ $ajuda }}</p>
    @endif
</div>
