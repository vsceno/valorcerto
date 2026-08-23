@props(['tipo' => 'info', 'titulo' => null])

@php
    $estilos = [
        'sucesso' => ['bg-emerald-50 border-emerald-200 text-emerald-900', 'fa-circle-check text-emerald-600'],
        'critico' => ['bg-rose-50 border-rose-200 text-rose-900', 'fa-triangle-exclamation text-rose-600'],
        'atencao' => ['bg-amber-50 border-amber-200 text-amber-900', 'fa-circle-exclamation text-amber-600'],
        'info' => ['bg-marca-50 border-marca-100 text-marca-900', 'fa-circle-info text-marca-600'],
    ];
    [$caixa, $icone] = $estilos[$tipo] ?? $estilos['info'];
@endphp

<div {{ $attributes->class(['flex gap-3 rounded-xl border p-4', $caixa]) }} role="alert">
    <i class="fa-solid {{ $icone }} mt-0.5"></i>
    <div class="min-w-0 text-sm">
        @if ($titulo)
            <p class="font-semibold">{{ $titulo }}</p>
        @endif
        <div @class(['mt-0.5' => $titulo])>{{ $slot }}</div>
    </div>
</div>
