<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use Illuminate\Foundation\Http\FormRequest;

class AtualizarFichaRequest extends FormRequest
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
            'linhas' => ['required', 'array', 'min:1'],
            'linhas.*.quantidade' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'linhas.*.observacao' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'linhas.*.quantidade.required' => 'Informe a quantidade consumida em todas as linhas.',
            'linhas.*.quantidade.gt' => 'A quantidade precisa ser maior que zero. Para zerar um insumo, remova a linha.',
            'linhas.*.quantidade.numeric' => 'A quantidade precisa ser um número.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $linhas = $this->input('linhas');

        if (! is_array($linhas)) {
            return;
        }

        foreach ($linhas as $id => $linha) {
            if (isset($linha['quantidade'])) {
                $linhas[$id]['quantidade'] = $this->paraDecimal($linha['quantidade']);
            }
        }

        $this->merge(['linhas' => $linhas]);
    }
}
