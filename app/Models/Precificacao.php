<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Precificacao extends Model
{
    use HasFactory;

    protected $table = 'precificacoes';

    protected $fillable = [
        'empresa_id',
        'item_id',
        'user_id',
        'item_nome',
        'item_sku',
        'item_tipo',
        'custo_variavel_unitario',
        'custo_fixo_total',
        'volume_projetado',
        'rateio_fixo_unitario',
        'custo_total_unitario',
        'soma_aliquotas_efetivas',
        'margem_contribuicao',
        'divisor',
        'preco_venda',
        'valor_tributos',
        'valor_margem_contribuicao',
        'markup',
        'memoria_calculo',
        'tributos_aplicados',
        'composicao_aplicada',
        'justificativa',
        'observacoes',
        'hash_auditoria',
        'calculado_em',
    ];

    protected function casts(): array
    {
        return [
            'custo_variavel_unitario' => 'decimal:4',
            'custo_fixo_total' => 'decimal:2',
            'volume_projetado' => 'decimal:4',
            'rateio_fixo_unitario' => 'decimal:4',
            'custo_total_unitario' => 'decimal:4',
            'soma_aliquotas_efetivas' => 'decimal:4',
            'margem_contribuicao' => 'decimal:4',
            'divisor' => 'decimal:8',
            'preco_venda' => 'decimal:4',
            'valor_tributos' => 'decimal:4',
            'valor_margem_contribuicao' => 'decimal:4',
            'markup' => 'decimal:6',
            'memoria_calculo' => 'array',
            'tributos_aplicados' => 'array',
            'composicao_aplicada' => 'array',
            'calculado_em' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecentes(Builder $query): Builder
    {
        return $query->orderByDesc('calculado_em');
    }
}
