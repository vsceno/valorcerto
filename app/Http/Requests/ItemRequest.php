<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use App\Models\Empresa;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
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
        $empresaId = Empresa::atual()?->id;

        return [
            'tipo' => ['required', Rule::in(array_keys(Item::TIPOS))],
            'nome' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable', 'string', 'max:60',
                Rule::unique('itens', 'sku')
                    ->where('empresa_id', $empresaId)
                    ->ignore($this->route('item')),
            ],
            'categoria_id' => [
                'nullable',
                Rule::exists('categorias', 'id')->where('empresa_id', $empresaId),
            ],
            'unidade_medida' => ['required', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'custo_variavel_unitario' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'margem_contribuicao_desejada' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'volume_projetado_mensal' => ['nullable', 'numeric', 'gt:0'],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'margem_contribuicao_desejada.max' => 'A margem incide sobre o preço final, então precisa ficar abaixo de 100%.',
            'volume_projetado_mensal.gt' => 'O volume precisa ser maior que zero para ratear os custos fixos.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_merge(
            $this->decimais([
                'custo_variavel_unitario',
                'margem_contribuicao_desejada',
                'volume_projetado_mensal',
            ]),
            [
                'ativo' => $this->boolean('ativo'),
                'categoria_id' => $this->input('categoria_id') ?: null,
                'sku' => $this->input('sku') ?: null,
            ],
        ));
    }
}
