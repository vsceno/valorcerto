<?php

namespace App\Http\Requests;

use App\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpresaRequest extends FormRequest
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
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'regime_tributario' => ['required', Rule::in(array_keys(Empresa::REGIMES))],
            'volume_projetado_mensal' => ['required', 'numeric', 'gt:0'],
            'ativo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ativo' => $this->boolean('ativo')]);
    }
}
