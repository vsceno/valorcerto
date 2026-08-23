@extends('layouts.app')

@section('titulo', $usuario->exists ? 'Editar usuário' : 'Novo usuário')
@section('subtitulo', 'O perfil define o que a pessoa pode alterar no sistema')

@section('conteudo')
    <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}"
          class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($usuario->exists) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <x-card titulo="Identificação" icone="fa-user">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-campo nome="name" rotulo="Nome completo" :valor="$usuario->name" obrigatorio />
                    <x-campo nome="email" rotulo="E-mail" tipo="email" :valor="$usuario->email" obrigatorio
                             autocomplete="off" ajuda="É com este e-mail que a pessoa faz login." />
                </div>
            </x-card>

            <x-card titulo="Senha" icone="fa-key"
                    :descricao="$usuario->exists
                        ? 'Preencha apenas se quiser trocar a senha desta pessoa.'
                        : 'Mínimo de 8 caracteres, com letras e números.'">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">
                            {{ $usuario->exists ? 'Nova senha' : 'Senha' }}
                            @unless ($usuario->exists)<span class="text-rose-500">*</span>@endunless
                        </label>
                        <input type="password" id="password" name="password" autocomplete="new-password"
                               @class([
                                   'mt-1.5 block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-inset',
                                   'border-rose-300 focus:border-rose-500 focus:ring-rose-200' => $errors->has('password'),
                                   'border-slate-300 focus:border-marca-500 focus:ring-marca-200' => ! $errors->has('password'),
                               ])>
                        @if ($errors->has('password'))
                            <p class="mt-1.5 flex items-center gap-1.5 text-sm text-rose-600">
                                <i class="fa-solid fa-circle-exclamation text-xs"></i>{{ $errors->first('password') }}
                            </p>
                        @endif
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">
                            Confirmar senha
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                    </div>
                </div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card titulo="Perfil de acesso" icone="fa-shield-halved">
                <div class="space-y-3">
                    @foreach (\App\Models\User::PERFIS as $chave => $rotulo)
                        <label @class([
                            'flex cursor-pointer gap-3 rounded-xl border p-4 transition',
                            'border-marca-300 bg-marca-50' => old('perfil', $usuario->perfil) === $chave,
                            'border-slate-200 hover:bg-slate-50' => old('perfil', $usuario->perfil) !== $chave,
                        ])>
                            <input type="radio" name="perfil" value="{{ $chave }}"
                                   @checked(old('perfil', $usuario->perfil) === $chave)
                                   class="mt-1 h-4 w-4 border-slate-300 text-marca-600 focus:ring-marca-500">
                            <span class="text-sm">
                                <span class="font-medium text-slate-900">{{ $rotulo }}</span>
                                <span class="mt-0.5 block text-slate-500">{{ \App\Models\User::DESCRICAO_PERFIS[$chave] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('perfil')
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-rose-600">
                        <i class="fa-solid fa-circle-exclamation text-xs"></i>{{ $message }}
                    </p>
                @enderror
            </x-card>

            <x-card titulo="Situação" icone="fa-toggle-on">
                <x-interruptor nome="ativo" rotulo="Usuário ativo" :valor="$usuario->ativo ?? true"
                               ajuda="Usuários inativos não conseguem entrar no sistema." />
                @error('ativo')
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-rose-600">
                        <i class="fa-solid fa-circle-exclamation text-xs"></i>{{ $message }}
                    </p>
                @enderror
            </x-card>

            <x-alerta tipo="info" titulo="Rastro de auditoria">
                Toda precificação registra quem a criou. Por isso usuários removidos preservam
                o histórico: o registro permanece, apenas sem vínculo ativo.
            </x-alerta>

            <div class="flex flex-wrap gap-3">
                <x-botao variante="sucesso" icone="fa-floppy-disk" class="flex-1">
                    {{ $usuario->exists ? 'Salvar alterações' : 'Cadastrar usuário' }}
                </x-botao>
                <x-botao href="{{ route('usuarios.index') }}" variante="neutro">Cancelar</x-botao>
            </div>
        </div>
    </form>
@endsection
