<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Painel') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
<div x-data="{ menuAberto: false }" class="min-h-full lg:flex">

    {{-- Fundo escurecido no mobile quando o menu está aberto --}}
    <div x-show="menuAberto" x-cloak @click="menuAberto = false"
         class="fixed inset-0 z-30 bg-slate-900/60 lg:hidden"></div>

    {{-- Menu lateral --}}
    <aside x-cloak
           :class="menuAberto ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-marca-950 transition-transform duration-200 lg:static lg:translate-x-0">

        <div class="flex items-center gap-3 px-6 py-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-marca-600 text-white shadow-lg shadow-marca-950/40">
                <i class="fa-solid fa-scale-balanced text-lg"></i>
            </span>
            <div class="leading-tight">
                <p class="text-lg font-semibold text-white">ValorCerto</p>
                <p class="text-xs text-marca-100/70">Formação de preços</p>
            </div>
            <button @click="menuAberto = false" class="ml-auto text-marca-100/70 lg:hidden" aria-label="Fechar menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-4 pb-6">
            <x-nav-grupo titulo="Principal">
                <x-nav-item :href="route('dashboard')" :ativo="request()->routeIs('dashboard')" icone="fa-chart-pie">
                    Painel
                </x-nav-item>
                <x-nav-item :href="route('precificacao.simulador')" :ativo="request()->routeIs('precificacao.simulador')" icone="fa-calculator">
                    Simulador de preço
                </x-nav-item>
                <x-nav-item :href="route('precificacao.index')" :ativo="request()->routeIs('precificacao.index') || request()->routeIs('precificacao.show')" icone="fa-clock-rotate-left">
                    Histórico
                </x-nav-item>
            </x-nav-grupo>

            <x-nav-grupo titulo="Cadastros">
                <x-nav-item :href="route('itens.index')" :ativo="request()->routeIs('itens.*')" icone="fa-box-open">
                    Produtos e serviços
                </x-nav-item>
                <x-nav-item :href="route('insumos.index')" :ativo="request()->routeIs('insumos.*')" icone="fa-cubes-stacked">
                    Insumos
                </x-nav-item>
                <x-nav-item :href="route('categorias.index')" :ativo="request()->routeIs('categorias.*')" icone="fa-tags">
                    Categorias
                </x-nav-item>
            </x-nav-grupo>

            @can('administrar')
                <x-nav-grupo titulo="Base do cálculo">
                    <x-nav-item :href="route('custos-fixos.index')" :ativo="request()->routeIs('custos-fixos.*')" icone="fa-building-columns">
                        Custos fixos
                    </x-nav-item>
                    <x-nav-item :href="route('tributos.index')" :ativo="request()->routeIs('tributos.*')" icone="fa-percent">
                        Tributos
                    </x-nav-item>
                    <x-nav-item :href="route('empresa.edit')" :ativo="request()->routeIs('empresa.*')" icone="fa-gear">
                        Empresa
                    </x-nav-item>
                </x-nav-grupo>

                <x-nav-grupo titulo="Administração">
                    <x-nav-item :href="route('usuarios.index')" :ativo="request()->routeIs('usuarios.*')" icone="fa-users">
                        Usuários
                    </x-nav-item>
                </x-nav-grupo>
            @endcan
        </nav>

        @if ($empresaAtual ?? null)
            <div class="border-t border-white/10 px-6 py-3">
                <p class="truncate text-sm font-medium text-white">{{ $empresaAtual->nome_fantasia ?: $empresaAtual->razao_social }}</p>
                <p class="text-xs text-marca-100/60">{{ $empresaAtual->regime_label }}</p>
            </div>
        @endif

        @auth
            <div x-data="{ aberto: false }" class="relative border-t border-white/10 p-3">
                <button @click="aberto = ! aberto"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-white/5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-marca-600 text-sm font-semibold text-white">
                        {{ auth()->user()->iniciais }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-white">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-marca-100/60">{{ auth()->user()->perfil_label }}</span>
                    </span>
                    <i class="fa-solid fa-chevron-up text-xs text-marca-100/50 transition" :class="aberto && 'rotate-180'"></i>
                </button>

                <div x-show="aberto" x-cloak @click.outside="aberto = false"
                     class="absolute inset-x-3 bottom-full mb-1 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                    <a href="{{ route('perfil.edit') }}"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-user w-4 text-slate-400"></i> Meu perfil
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-rose-700 hover:bg-rose-50">
                            <i class="fa-solid fa-right-from-bracket w-4 text-rose-400"></i> Sair
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </aside>

    {{-- Conteúdo --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="flex items-center gap-4 px-4 py-4 sm:px-8">
                <button @click="menuAberto = true" class="text-slate-500 lg:hidden" aria-label="Abrir menu">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="min-w-0">
                    <h1 class="truncate text-xl font-semibold text-slate-900">@yield('titulo', 'Painel')</h1>
                    @hasSection('subtitulo')
                        <p class="truncate text-sm text-slate-500">@yield('subtitulo')</p>
                    @endif
                </div>
                <div class="ml-auto flex items-center gap-3">
                    @yield('acoes')
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-6 sm:px-8">
            @if (session('sucesso'))
                <x-alerta tipo="sucesso" class="mb-6">{{ session('sucesso') }}</x-alerta>
            @endif

            @if (session('erro'))
                <x-alerta tipo="critico" class="mb-6">{{ session('erro') }}</x-alerta>
            @endif

            @if ($errors->any() && ! $errors->has('_ignorar'))
                <x-alerta tipo="critico" class="mb-6" titulo="Confira os campos destacados">
                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </x-alerta>
            @endif

            @yield('conteudo')
        </main>

        <footer class="border-t border-slate-200 px-4 py-4 text-center text-xs text-slate-400 sm:px-8">
            ValorCerto · Preços formados sobre custo real, carga tributária efetiva e margem sobre o preço final.
        </footer>
    </div>
</div>
</body>
</html>
