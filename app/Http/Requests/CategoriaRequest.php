<?php

namespace App\Http\Requests;

use App\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => [
                'required', 'string', 'max:255',
                Rule::unique('categorias', 'nome')
                    ->where('empresa_id', Empresa::atual()?->id)
                    ->ignore($this->route('categoria')),
            ],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe uma categoria com este nome nesta empresa.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ativo' => $this->boolean('ativo')]);
    }
}
