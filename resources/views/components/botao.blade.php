@props(['variante' => 'primario', 'icone' => null, 'href' => null, 'tipo' => 'submit'])

@php
    $variantes = [
        'primario' => 'bg-marca-600 text-white hover:bg-marca-700 focus-visible:outline-marca-600 shadow-sm',
        'sucesso' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus-visible:outline-emerald-600 shadow-sm',
        'neutro' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus-visible:outline-slate-400',
        'perigo' => 'bg-white text-rose-700 ring-1 ring-inset ring-rose-200 hover:bg-rose-50 focus-visible:outline-rose-500',
        'fantasma' => 'text-slate-600 hover:bg-slate-100 focus-visible:outline-slate-400',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60 '
        .($variantes[$variante] ?? $variantes['primario']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icone)<i class="fa-solid {{ $icone }}"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $tipo }}" {{ $attributes->class($classes) }}>
        @if ($icone)<i class="fa-solid {{ $icone }}"></i>@endif
        {{ $slot }}
    </button>
@endif
