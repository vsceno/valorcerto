@props(['cor' => 'slate', 'icone' => null])

@php
    $cores = [
        'slate' => 'bg-slate-100 text-slate-700',
        'marca' => 'bg-marca-50 text-marca-700',
        'verde' => 'bg-emerald-50 text-emerald-700',
        'ambar' => 'bg-amber-50 text-amber-700',
        'rosa' => 'bg-rose-50 text-rose-700',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium', $cores[$cor] ?? $cores['slate']]) }}>
    @if ($icone)<i class="fa-solid {{ $icone }} text-[10px]"></i>@endif
    {{ $slot }}
</span>
