<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use App\Models\Empresa;
use App\Models\Insumo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InsumoRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:255'],
            'codigo' => [
                'nullable', 'string', 'max:60',
                Rule::unique('insumos', 'codigo')
                    ->where('empresa_id', Empresa::atual()?->id)
                    ->ignore($this->route('insumo')),
            ],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'grupo' => ['required', Rule::in(array_keys(Insumo::GRUPOS))],
            'unidade_compra' => ['required', 'string', 'max:20'],
            'preco_compra' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rendimento' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'unidade_uso' => ['required', 'string', 'max:20'],
            'perda_percentual' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'unidade_compra' => 'unidade de compra',
            'preco_compra' => 'preço de compra',
            'unidade_uso' => 'unidade de uso',
            'perda_percentual' => 'perda',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rendimento.gt' => 'O rendimento precisa ser maior que zero: é ele que converte a unidade de compra na unidade de uso.',
            'perda_percentual.max' => 'Uma perda de 100% significaria que nada do insumo vira produto.',
            'codigo.unique' => 'Já existe um insumo com este código.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_merge(
            $this->decimais(['preco_compra', 'rendimento', 'perda_percentual']),
            [
                'ativo' => $this->boolean('ativo'),
                'codigo' => $this->input('codigo') ?: null,
            ],
        ));
    }
}
