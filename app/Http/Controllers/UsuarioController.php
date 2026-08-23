<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        return view('usuarios.index', [
            'usuarios' => User::query()
                ->withCount('precificacoes')
                ->when($request->filled('busca'), function ($query) use ($request): void {
                    $busca = $request->string('busca')->toString();
                    $query->where(function ($sub) use ($busca): void {
                        $sub->where('name', 'like', "%{$busca}%")
                            ->orWhere('email', 'like', "%{$busca}%");
                    });
                })
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'busca' => $request->string('busca')->toString(),
            'totalAdministradores' => User::query()->ativos()->where('perfil', 'administrador')->count(),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.form', [
            'usuario' => new User(['perfil' => 'operador', 'ativo' => true]),
        ]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        User::create($request->paraGravar());

        return redirect()->route('usuarios.index')
            ->with('sucesso', 'Usuário cadastrado. Ele já pode acessar o sistema com o e-mail e a senha definidos.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.form', ['usuario' => $usuario]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $usuario->update($request->paraGravar());

        return redirect()->route('usuarios.index')
            ->with('sucesso', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->id === $request->user()?->id) {
            return back()->with('erro', 'Você não pode excluir o próprio usuário.');
        }

        // Sempre sobra ao menos um administrador ativo: quem executa a exclusão
        // é um deles, e não pode excluir a si mesmo (regra acima). A mesma
        // lógica protege o rebaixamento e a desativação em UsuarioRequest.
        //
        // O histórico de precificações preserva o vínculo como nulo, mantendo
        // o registro de auditoria íntegro.
        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('sucesso', 'Usuário removido. As precificações que ele registrou foram preservadas.');
    }
}
