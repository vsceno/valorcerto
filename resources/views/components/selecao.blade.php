@props([
    'nome',
    'rotulo',
    'opcoes' => [],
    'valor' => null,
    'ajuda' => null,
    'vazio' => null,
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

    <select id="{{ $nome }}" name="{{ $nome }}"
            {{ $attributes->class([
                'mt-1.5 block w-full rounded-lg border px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-inset',
                'border-rose-300 focus:border-rose-500 focus:ring-rose-200' => $erro,
                'border-slate-300 focus:border-marca-500 focus:ring-marca-200' => ! $erro,
            ]) }}>
        @if ($vazio !== null)
            <option value="">{{ $vazio }}</option>
        @endif
        @foreach ($opcoes as $chave => $texto)
            <option value="{{ $chave }}" @selected((string) $valorFinal === (string) $chave)>{{ $texto }}</option>
        @endforeach
    </select>

    @if ($erro)
        <p class="mt-1.5 flex items-center gap-1.5 text-sm text-rose-600">
            <i class="fa-solid fa-circle-exclamation text-xs"></i>{{ $erro }}
        </p>
    @elseif ($ajuda)
        <p class="mt-1.5 text-sm text-slate-500">{{ $ajuda }}</p>
    @endif
</div>
