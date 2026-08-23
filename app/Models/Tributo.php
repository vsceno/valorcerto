<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tributo extends Model
{
    use HasFactory;

    protected $table = 'tributos';

    protected $fillable = [
        'empresa_id',
        'nome',
        'sigla',
        'aliquota_nominal',
        'aliquota_efetiva',
        'aplica_a',
        'base_legal',
        'observacoes',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'aliquota_nominal' => 'decimal:4',
            'aliquota_efetiva' => 'decimal:4',
            'ativo' => 'boolean',
        ];
    }

    public const APLICACOES = [
        'produto' => 'Somente produtos',
        'servico' => 'Somente serviços',
        'ambos' => 'Produtos e serviços',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /**
     * Filtra os tributos que incidem sobre o tipo de item informado.
     */
    public function scopeAplicaveisA(Builder $query, string $tipo): Builder
    {
        return $query->whereIn('aplica_a', [$tipo, 'ambos']);
    }

    /**
     * Diferença entre a alíquota de tabela e a efetivamente suportada.
     */
    public function getEconomiaFiscalAttribute(): float
    {
        return (float) $this->aliquota_nominal - (float) $this->aliquota_efetiva;
    }
}
