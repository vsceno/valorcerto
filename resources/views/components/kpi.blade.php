@props(['rotulo', 'valor', 'icone' => 'fa-chart-simple', 'cor' => 'marca', 'detalhe' => null])

@php
    $cores = [
        'marca' => 'bg-marca-50 text-marca-600',
        'verde' => 'bg-emerald-50 text-emerald-600',
        'ambar' => 'bg-amber-50 text-amber-600',
        'rosa' => 'bg-rose-50 text-rose-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div {{ $attributes->class('rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/50') }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-slate-500">{{ $rotulo }}</p>
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $cores[$cor] ?? $cores['marca'] }}">
            <i class="fa-solid {{ $icone }}"></i>
        </span>
    </div>
    <p class="tabular mt-3 text-2xl font-semibold text-slate-900">{{ $valor }}</p>
    @if ($detalhe)
        <p class="mt-1 text-sm text-slate-500">{{ $detalhe }}</p>
    @endif
</div>
