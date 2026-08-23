@props(['ordem', 'titulo', 'formula', 'substituicao', 'resultado', 'explicacao' => null, 'destaque' => false])

<li @class([
    'relative flex gap-4 rounded-xl border p-4 transition',
    'border-emerald-200 bg-emerald-50/60' => $destaque,
    'border-slate-200 bg-white' => ! $destaque,
])>
    <span @class([
        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
        'bg-emerald-600 text-white' => $destaque,
        'bg-marca-100 text-marca-700' => ! $destaque,
    ])>{{ $ordem }}</span>

    <div class="min-w-0 flex-1">
        <p class="font-medium text-slate-900">{{ $titulo }}</p>

        <p class="mt-2 rounded-lg bg-slate-900 px-3 py-2 font-mono text-xs text-slate-100 overflow-x-auto">{{ $formula }}</p>

        <div class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-1 text-sm">
            <span class="tabular font-mono text-slate-600">{{ $substituicao }}</span>
            <span class="text-slate-400">=</span>
            <span @class(['tabular font-semibold', 'text-emerald-700 text-base' => $destaque, 'text-slate-900' => ! $destaque])>{{ $resultado }}</span>
        </div>

        @if ($explicacao)
            <p class="mt-2 text-sm text-slate-500">{{ $explicacao }}</p>
        @endif
    </div>
</li>
