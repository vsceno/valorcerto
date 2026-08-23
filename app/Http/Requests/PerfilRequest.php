<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user()),
            ],
            'senha_atual' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
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
            'senha_atual' => 'senha atual',
            'password' => 'nova senha',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'senha_atual.current_password' => 'A senha atual informada está incorreta.',
            'senha_atual.required_with' => 'Informe a senha atual para definir uma nova.',
            'password.confirmed' => 'A confirmação da nova senha não confere.',
        ];
    }
}
