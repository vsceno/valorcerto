<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use App\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComposicaoRequest extends FormRequest
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
            'insumo_id' => [
                'required',
                Rule::exists('insumos', 'id')->where('empresa_id', Empresa::atual()?->id),
                // Um insumo entra uma vez na ficha; para dobrar, aumenta-se a quantidade.
                Rule::unique('composicoes', 'insumo_id')
                    ->where('item_id', $this->route('item')?->id)
                    ->ignore($this->route('composicao')),
            ],
            'quantidade' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['insumo_id' => 'insumo'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'insumo_id.unique' => 'Este insumo já está na ficha técnica. Edite a quantidade da linha existente.',
            'quantidade.gt' => 'A quantidade consumida precisa ser maior que zero.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->decimais(['quantidade']));
    }
}
