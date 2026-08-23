<?php

/**
 * Mensagens de validação em português. Chaves ausentes caem automaticamente
 * no idioma de fallback definido em APP_FALLBACK_LOCALE.
 */
return [
    'accepted' => 'O campo :attribute deve ser aceito.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'between' => [
        'array' => 'O campo :attribute deve ter entre :min e :max itens.',
        'file' => 'O arquivo :attribute deve ter entre :min e :max kilobytes.',
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'date' => 'O campo :attribute não é uma data válida.',
    'decimal' => 'O campo :attribute deve ter :decimal casas decimais.',
    'different' => 'Os campos :attribute e :other devem ser diferentes.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'exists' => 'O :attribute selecionado é inválido.',
    'gt' => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
    ],
    'gte' => [
        'numeric' => 'O campo :attribute deve ser maior ou igual a :value.',
    ],
    'in' => 'O :attribute selecionado é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'lt' => [
        'numeric' => 'O campo :attribute deve ser menor que :value.',
    ],
    'lte' => [
        'numeric' => 'O campo :attribute deve ser menor ou igual a :value.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_if' => 'O campo :attribute é obrigatório quando :other for :value.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',

    'attributes' => [
        'razao_social' => 'razão social',
        'nome_fantasia' => 'nome fantasia',
        'cnpj' => 'CNPJ',
        'regime_tributario' => 'regime tributário',
        'volume_projetado_mensal' => 'volume projetado mensal',
        'nome' => 'nome',
        'sigla' => 'sigla',
        'descricao' => 'descrição',
        'grupo' => 'grupo',
        'valor_mensal' => 'valor mensal',
        'aliquota_nominal' => 'alíquota nominal',
        'aliquota_efetiva' => 'alíquota efetiva',
        'aplica_a' => 'aplicação',
        'base_legal' => 'base legal',
        'categoria_id' => 'categoria',
        'item_id' => 'item',
        'tipo' => 'tipo',
        'sku' => 'SKU',
        'unidade_medida' => 'unidade de medida',
        'custo_variavel_unitario' => 'custo variável unitário',
        'margem_contribuicao_desejada' => 'margem de contribuição desejada',
        'margem_contribuicao' => 'margem de contribuição',
        'volume_projetado' => 'volume projetado',
        'justificativa' => 'justificativa',
        'observacoes' => 'observações',
        'ativo' => 'situação',
    ],
];
