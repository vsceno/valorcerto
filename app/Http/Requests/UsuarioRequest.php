<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($usuario),
            ],
            'perfil' => ['required', Rule::in(array_keys(User::PERFIS))],
            'password' => [
                $usuario ? 'nullable' : 'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'perfil' => 'perfil',
            'password' => 'senha',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'A confirmação da senha não confere.',
            'email.unique' => 'Já existe um usuário com este e-mail.',
        ];
    }

    /**
     * Impede que o administrador logado se rebaixe ou se desative sozinho,
     * o que poderia deixar o sistema sem ninguém capaz de administrá-lo.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $usuario = $this->route('usuario');

            if (! $usuario instanceof User || $usuario->id !== $this->user()?->id) {
                return;
            }

            if ($this->input('perfil') !== 'administrador') {
                $validator->errors()->add('perfil', 'Você não pode remover o próprio acesso de administrador.');
            }

            if (! $this->boolean('ativo')) {
                $validator->errors()->add('ativo', 'Você não pode desativar o próprio usuário.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ativo' => $this->boolean('ativo')]);
    }

    /**
     * Dados prontos para gravação: a senha só entra quando foi informada.
     *
     * @return array<string, mixed>
     */
    public function paraGravar(): array
    {
        $dados = $this->safe()->except('password');

        if ($this->filled('password')) {
            $dados['password'] = $this->input('password');
        }

        return $dados;
    }
}
