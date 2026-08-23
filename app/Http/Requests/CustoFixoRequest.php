<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use App\Models\CustoFixo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustoFixoRequest extends FormRequest
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
            'descricao' => ['required', 'string', 'max:255'],
            'grupo' => ['required', Rule::in(array_keys(CustoFixo::GRUPOS))],
            'valor_mensal' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'ativo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_merge(
            $this->decimais(['valor_mensal']),
            ['ativo' => $this->boolean('ativo')],
        ));
    }
}
