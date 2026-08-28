<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use App\Models\Tributo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TributoRequest extends FormRequest
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
            'sigla' => ['required', 'string', 'max:20'],
            'aliquota_nominal' => ['required', 'numeric', 'min:0', 'max:100'],
            'aliquota_efetiva' => ['required', 'numeric', 'min:0', 'max:100'],
            'base_calculo' => ['required', Rule::in(array_keys(Tributo::BASES))],
            'aplica_a' => ['required', Rule::in(array_keys(Tributo::APLICACOES))],
            'vigencia_inicio' => ['nullable', 'date'],
            'vigencia_fim' => ['nullable', 'date', 'after_or_equal:vigencia_inicio'],
            'base_legal' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vigencia_fim.after_or_equal' => 'O fim da vigência não pode ser anterior ao início.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $nominal = (float) $this->input('aliquota_nominal');
            $efetiva = (float) $this->input('aliquota_efetiva');

            if ($efetiva > $nominal) {
                $validator->errors()->add(
                    'aliquota_efetiva',
                    'A alíquota efetiva não pode superar a nominal: a efetiva é a nominal já líquida de créditos e reduções de base.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_merge(
            $this->decimais(['aliquota_nominal', 'aliquota_efetiva']),
            [
                'ativo' => $this->boolean('ativo'),
                'vigencia_inicio' => $this->input('vigencia_inicio') ?: null,
                'vigencia_fim' => $this->input('vigencia_fim') ?: null,
            ],
        ));
    }
}
