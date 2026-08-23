<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 antialiased">
<div class="flex min-h-full">

    {{-- Painel de apresentação --}}
    <div class="hidden w-1/2 flex-col justify-between bg-marca-950 p-12 lg:flex">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-marca-600 text-white shadow-lg shadow-marca-950/40">
                <i class="fa-solid fa-scale-balanced text-lg"></i>
            </span>
            <div class="leading-tight">
                <p class="text-lg font-semibold text-white">ValorCerto</p>
                <p class="text-xs text-marca-100/70">Formação de preços</p>
            </div>
        </div>

        <div class="max-w-md">
            <h2 class="text-3xl font-bold leading-tight text-white">
                Todo preço com memória de cálculo.
            </h2>
            <p class="mt-4 text-marca-100/70">
                Custo real, carga tributária efetiva e margem sobre o preço final — cada valor
                praticado fica registrado com a fórmula que o produziu.
            </p>

            <div class="mt-8 rounded-xl bg-white/5 p-5 text-center font-mono text-xs leading-relaxed text-slate-200">
                <p class="text-emerald-300">Preço de Venda</p>
                <p class="my-1 text-slate-500">=</p>
                <p>Custo Variável + Rateio Fixo</p>
                <p class="my-1 border-t border-white/20 pt-1">1 − (Alíquotas Efetivas + Margem)</p>
            </div>
        </div>

        <p class="text-xs text-marca-100/40">
            Registros de auditoria assinados com SHA-256 · CDC · Lei 12.529/2011
        </p>
    </div>

    {{-- Formulário --}}
    <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16">
        <div class="mx-auto w-full max-w-sm">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-marca-600 text-white">
                    <i class="fa-solid fa-scale-balanced text-lg"></i>
                </span>
                <div class="leading-tight">
                    <p class="text-lg font-semibold text-slate-900">ValorCerto</p>
                    <p class="text-xs text-slate-500">Formação de preços</p>
                </div>
            </div>

            <h1 class="text-2xl font-semibold text-slate-900">Entrar</h1>
            <p class="mt-1 text-sm text-slate-500">Informe suas credenciais para acessar o sistema.</p>

            @if (session('sucesso'))
                <x-alerta tipo="sucesso" class="mt-6">{{ session('sucesso') }}</x-alerta>
            @endif

            @if ($errors->any())
                <x-alerta tipo="critico" class="mt-6">
                    {{ $errors->first() }}
                </x-alerta>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf

                <x-campo nome="email" rotulo="E-mail" tipo="email" autofocus autocomplete="username" obrigatorio />

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">
                        Senha <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" autocomplete="current-password"
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                </div>

                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                    <input type="checkbox" name="lembrar" value="1"
                           class="h-4 w-4 rounded border-slate-300 text-marca-600 focus:ring-marca-500">
                    Manter conectado neste dispositivo
                </label>

                <x-botao icone="fa-right-to-bracket" class="w-full">Entrar</x-botao>
            </form>

            <p class="mt-8 text-center text-xs text-slate-400">
                Acesso restrito. Solicite credenciais ao administrador do sistema.
            </p>
        </div>
    </div>
</div>
</body>
</html>
