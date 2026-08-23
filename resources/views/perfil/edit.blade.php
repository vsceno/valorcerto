@extends('layouts.app')

@section('titulo', 'Meu perfil')
@section('subtitulo', 'Seus dados de acesso ao sistema')

@section('conteudo')
    <form method="POST" action="{{ route('perfil.update') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf @method('PUT')

        <div class="space-y-6 xl:col-span-2">
            <x-card titulo="Dados pessoais" icone="fa-user">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo nome="name" rotulo="Nome completo" :valor="$usuario->name" obrigatorio />
                    <x-campo nome="email" rotulo="E-mail" tipo="email" :valor="$usuario->email" obrigatorio />
                </div>
            </x-card>

            <x-card titulo="Trocar senha" icone="fa-key"
                    descricao="Deixe em branco para manter a senha atual.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="senha_atual" class="block text-sm font-medium text-slate-700">Senha atual</label>
                        <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password"
                               @class([
                                   'mt-1.5 block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-inset sm:max-w-xs',
                                   'border-rose-300 focus:border-rose-500 focus:ring-rose-200' => $errors->has('senha_atual'),
                                   'border-slate-300 focus:border-marca-500 focus:ring-marca-200' => ! $errors->has('senha_atual'),
                               ])>
                        @error('senha_atual')
                            <p class="mt-1.5 flex items-center gap-1.5 text-sm text-rose-600">
                                <i class="fa-solid fa-circle-exclamation text-xs"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Nova senha</label>
                        <input type="password" id="password" name="password" autocomplete="new-password"
                               @class([
                                   'mt-1.5 block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-inset',
                                   'border-rose-300 focus:border-rose-500 focus:ring-rose-200' => $errors->has('password'),
                                   'border-slate-300 focus:border-marca-500 focus:ring-marca-200' => ! $errors->has('password'),
                               ])>
                        @error('password')
                            <p class="mt-1.5 flex items-center gap-1.5 text-sm text-rose-600">
                                <i class="fa-solid fa-circle-exclamation text-xs"></i>{{ $message }}
                            </p>
                        @else
                            <p class="mt-1.5 text-sm text-slate-500">Mínimo de 8 caracteres, com letras e números.</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirmar nova senha</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Seu acesso" icone="fa-shield-halved">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-marca-600 text-sm font-semibold text-white">
                        {{ $usuario->iniciais }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-900">{{ $usuario->name }}</p>
                        <x-badge :cor="$usuario->ehAdministrador() ? 'marca' : 'slate'">{{ $usuario->perfil_label }}</x-badge>
                    </div>
                </div>

                <dl class="mt-4 divide-y divide-slate-100 border-t border-slate-100 text-sm">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Preços registrados</dt>
                        <dd class="tabular font-medium text-slate-900">{{ $usuario->precificacoes()->count() }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500">Último acesso</dt>
                        <dd class="font-medium text-slate-900">{{ $usuario->ultimo_acesso_em?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>

                <p class="mt-4 text-sm text-slate-500">
                    O perfil de acesso só pode ser alterado por um administrador.
                </p>
            </x-card>

            <x-botao variante="sucesso" icone="fa-floppy-disk" class="w-full">Salvar alterações</x-botao>
        </div>
    </form>
@endsection
