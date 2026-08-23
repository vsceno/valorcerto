@props(['icone' => 'fa-inbox', 'titulo', 'descricao' => null])

<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white/60 px-6 py-14 text-center">
    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <i class="fa-solid {{ $icone }} text-xl"></i>
    </span>
    <p class="mt-4 font-semibold text-slate-800">{{ $titulo }}</p>
    @if ($descricao)
        <p class="mt-1 max-w-md text-sm text-slate-500">{{ $descricao }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">{{ $slot }}</div>
    @endif
</div>
