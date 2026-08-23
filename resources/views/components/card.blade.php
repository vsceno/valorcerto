@props(['titulo' => null, 'descricao' => null, 'icone' => null])

<section {{ $attributes->class('rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/50') }}>
    @if ($titulo)
        <header class="flex items-start gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            @if ($icone)
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-marca-50 text-marca-600">
                    <i class="fa-solid {{ $icone }}"></i>
                </span>
            @endif
            <div class="min-w-0">
                <h2 class="font-semibold text-slate-900">{{ $titulo }}</h2>
                @if ($descricao)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $descricao }}</p>
                @endif
            </div>
            @isset($acoes)
                <div class="ml-auto flex shrink-0 items-center gap-2">{{ $acoes }}</div>
            @endisset
        </header>
    @endif

    <div class="px-5 py-5 sm:px-6">
        {{ $slot }}
    </div>

    @isset($rodape)
        <footer class="border-t border-slate-100 bg-slate-50/60 px-5 py-3 text-sm text-slate-600 sm:px-6">
            {{ $rodape }}
        </footer>
    @endisset
</section>
