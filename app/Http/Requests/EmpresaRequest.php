<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use App\Models\Empresa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EmpresaRequest extends FormRequest
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
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => [
                'nullable', 'string', 'max:18',
                Rule::unique('empresas', 'cnpj')->ignore($this->route('empresa')),
            ],
            'inscricao_estadual' => ['nullable', 'string', 'max:20'],
            'inscricao_municipal' => ['nullable', 'string', 'max:20'],
            'cnae_principal' => ['nullable', 'string', 'max:10'],
            'atividade' => ['required', Rule::in(array_keys(Empresa::ATIVIDADES))],
            'uf' => ['nullable', 'string', 'size:2'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'faturamento_12_meses' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'regime_tributario' => ['required', Rule::in(array_keys(Empresa::REGIMES))],
            'regime_vigente_desde' => ['nullable', 'date'],
            'volume_projetado_mensal' => ['required', 'numeric', 'gt:0'],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cnpj.unique' => 'Já existe uma empresa cadastrada com este CNPJ.',
            'uf.size' => 'Use a sigla do estado com duas letras (ex.: SP).',
        ];
    }

    /**
     * Limites de receita que definem o enquadramento, para evitar cadastro
     * incoerente entre regime e faturamento.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $faturamento = (float) $this->input('faturamento_12_meses');
            $regime = $this->input('regime_tributario');

            // LC 123/2006: MEI até R$ 81 mil; Simples até R$ 4,8 milhões.
            if ($regime === 'mei' && $faturamento > 81000) {
                $validator->errors()->add(
                    'regime_tributario',
                    'O MEI tem limite de R$ 81.000,00 de receita em 12 meses. Com este faturamento, o enquadramento seria outro.'
                );
            }

            if ($regime === 'simples_nacional' && $faturamento > 4800000) {
                $validator->errors()->add(
                    'regime_tributario',
                    'O Simples Nacional tem limite de R$ 4.800.000,00 de receita em 12 meses. Confirme o enquadramento com a contabilidade.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_merge(
            $this->decimais(['faturamento_12_meses', 'volume_projetado_mensal']),
            [
                'ativo' => $this->boolean('ativo'),
                'uf' => $this->input('uf') ? mb_strtoupper((string) $this->input('uf')) : null,
                'cnpj' => $this->input('cnpj') ?: null,
            ],
        ));
    }
}
