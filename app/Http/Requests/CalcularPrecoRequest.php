<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalcularPrecoRequest extends FormRequest
{
    use NormalizaNumeros;

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
            'item_id' => ['required', Rule::exists('itens', 'id')],
            'custo_variavel_unitario' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'margem_contribuicao' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'volume_projetado' => ['required', 'numeric', 'gt:0'],
            'justificativa' => ['nullable', 'string', 'max:2000'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'margem_contribuicao.max' => 'A margem incide sobre o preço final, então precisa ficar abaixo de 100%.',
            'volume_projetado.gt' => 'O volume precisa ser maior que zero para ratear os custos fixos.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->decimais([
            'custo_variavel_unitario',
            'margem_contribuicao',
            'volume_projetado',
        ]));
    }
}
