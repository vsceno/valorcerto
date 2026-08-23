@extends('layouts.app')

@section('titulo', 'Usuários')
@section('subtitulo', 'Quem acessa o sistema e o que cada um pode alterar')

@section('acoes')
    <x-botao href="{{ route('usuarios.create') }}" icone="fa-user-plus">Novo usuário</x-botao>
@endsection

@section('conteudo')
    @if ($totalAdministradores === 1)
        <x-alerta tipo="atencao" class="mb-6" titulo="Existe apenas um administrador ativo">
            Se este acesso for perdido, ninguém poderá alterar tributos, custos fixos ou usuários.
            Vale cadastrar um segundo administrador.
        </x-alerta>
    @endif

    <x-card>
        <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
            <div class="min-w-64 flex-1">
                <label for="busca" class="block text-sm font-medium text-slate-700">Buscar</label>
                <div class="mt-1.5 flex rounded-lg shadow-sm">
                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </span>
                    <input type="search" name="busca" id="busca" value="{{ $busca }}" placeholder="Nome ou e-mail"
                           class="block w-full min-w-0 rounded-r-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-marca-500 focus:ring-2 focus:ring-inset focus:ring-marca-200">
                </div>
            </div>
            <x-botao variante="neutro" icone="fa-filter">Filtrar</x-botao>
            @if ($busca)
                <x-botao href="{{ route('usuarios.index') }}" variante="fantasma">Limpar</x-botao>
            @endif
        </form>

        @if ($usuarios->isEmpty())
            <x-vazio icone="fa-users" titulo="Nenhum usuário encontrado"
                     descricao="Cadastre quem vai formar preços e quem vai administrar a base do cálculo.">
                <x-botao href="{{ route('usuarios.create') }}" icone="fa-user-plus">Cadastrar usuário</x-botao>
            </x-vazio>
        @else
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 pb-2 sm:px-6">Usuário</th>
                            <th class="px-3 pb-2">Perfil</th>
                            <th class="px-3 pb-2 text-right">Preços registrados</th>
                            <th class="px-3 pb-2">Último acesso</th>
                            <th class="px-5 pb-2 text-right sm:px-6">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($usuarios as $usuario)
                            <tr class="transition hover:bg-slate-50 {{ $usuario->ativo ? '' : 'opacity-50' }}">
                                <td class="px-5 py-3 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <span @class([
                                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                            'bg-marca-600 text-white' => $usuario->ehAdministrador(),
                                            'bg-slate-200 text-slate-600' => ! $usuario->ehAdministrador(),
                                        ])>{{ $usuario->iniciais }}</span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-900">
                                                {{ $usuario->name }}
                                                @if ($usuario->id === auth()->id())
                                                    <span class="text-xs font-normal text-slate-400">(você)</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-slate-500">{{ $usuario->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <x-badge :cor="$usuario->ehAdministrador() ? 'marca' : 'slate'"
                                             :icone="$usuario->ehAdministrador() ? 'fa-shield-halved' : 'fa-user'">
                                        {{ $usuario->perfil_label }}
                                    </x-badge>
                                    @unless ($usuario->ativo)
                                        <x-badge cor="rosa" class="ml-1">Inativo</x-badge>
                                    @endunless
                                </td>
                                <td class="tabular px-3 py-3 text-right text-slate-600">{{ $usuario->precificacoes_count }}</td>
                                <td class="px-3 py-3 text-slate-600">
                                    {{ $usuario->ultimo_acesso_em?->format('d/m/Y H:i') ?? 'nunca acessou' }}
                                </td>
                                <td class="px-5 py-3 text-right sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('usuarios.edit', $usuario) }}"
                                           class="rounded-lg px-2 py-1.5 text-slate-500 hover:bg-slate-100" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        @if ($usuario->id !== auth()->id())
                                            <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}"
                                                  onsubmit="return confirm('Remover {{ $usuario->name }}? As precificações registradas por ele são preservadas.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="rounded-lg px-2 py-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Remover">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $usuarios->links() }}</div>
        @endif
    </x-card>
@endsection
