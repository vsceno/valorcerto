@props(['titulo'])

<div>
    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-marca-100/50">{{ $titulo }}</p>
    <div class="space-y-1">
        {{ $slot }}
    </div>
</div>
