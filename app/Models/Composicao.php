<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha da ficha técnica: quanto de um insumo entra em uma unidade do item.
 */
class Composicao extends Model
{
    use HasFactory;

    protected $table = 'composicoes';

    protected $fillable = [
        'item_id',
        'insumo_id',
        'quantidade',
        'observacao',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:4',
            'ordem' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    /**
     * Custo desta linha em uma unidade do produto.
     */
    public function custoTotal(): float
    {
        return (float) $this->quantidade * ($this->insumo?->custoUnitarioUso() ?? 0.0);
    }

    /**
     * Fotografia da linha para o registro de auditoria: guarda os números do
     * insumo no momento do cálculo, não uma referência que pode mudar depois.
     *
     * @return array<string, mixed>
     */
    public function paraSnapshot(): array
    {
        return [
            'insumo' => $this->insumo?->nome,
            'codigo' => $this->insumo?->codigo,
            'grupo' => $this->insumo?->grupo,
            'quantidade' => (float) $this->quantidade,
            'unidade_uso' => $this->insumo?->unidade_uso,
            'unidade_compra' => $this->insumo?->unidade_compra,
            'preco_compra' => (float) ($this->insumo?->preco_compra ?? 0),
            'rendimento' => (float) ($this->insumo?->rendimento ?? 1),
            'perda_percentual' => (float) ($this->insumo?->perda_percentual ?? 0),
            'custo_unitario_uso' => round($this->insumo?->custoUnitarioUso() ?? 0.0, 6),
            'custo_total' => round($this->custoTotal(), 4),
            'observacao' => $this->observacao,
        ];
    }
}
