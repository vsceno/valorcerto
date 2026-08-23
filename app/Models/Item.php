<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    use HasFactory;

    protected $table = 'itens';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'tipo',
        'nome',
        'sku',
        'unidade_medida',
        'descricao',
        'custo_variavel_unitario',
        'margem_contribuicao_desejada',
        'volume_projetado_mensal',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'custo_variavel_unitario' => 'decimal:4',
            'margem_contribuicao_desejada' => 'decimal:4',
            'volume_projetado_mensal' => 'decimal:4',
            'ativo' => 'boolean',
        ];
    }

    public const TIPOS = [
        'produto' => 'Produto',
        'servico' => 'Serviço',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function precificacoes(): HasMany
    {
        return $this->hasMany(Precificacao::class);
    }

    /**
     * Ficha técnica: as linhas de insumo que formam uma unidade do produto.
     */
    public function composicoes(): HasMany
    {
        return $this->hasMany(Composicao::class)->orderBy('ordem')->orderBy('id');
    }

    public function insumos(): BelongsToMany
    {
        return $this->belongsToMany(Insumo::class, 'composicoes')
            ->withPivot(['quantidade', 'observacao'])
            ->withTimestamps();
    }

    public function ultimaPrecificacao(): HasOne
    {
        return $this->hasOne(Precificacao::class)->latestOfMany('calculado_em');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeDoTipo(Builder $query, ?string $tipo): Builder
    {
        return $tipo ? $query->where('tipo', $tipo) : $query;
    }

    /**
     * O produto tem ficha técnica montada?
     */
    public function temFichaTecnica(): bool
    {
        return $this->composicoes()->exists();
    }

    /**
     * Soma das linhas da ficha técnica: o custo de produzir uma unidade.
     */
    public function custoDaFichaTecnica(): float
    {
        return (float) $this->composicoes
            ->loadMissing('insumo')
            ->sum(fn (Composicao $linha): float => $linha->custoTotal());
    }

    /**
     * Custo variável que a precificação deve usar: quando existe ficha técnica,
     * ela manda — o custo é calculado, não digitado.
     */
    public function custoVariavelEfetivo(): float
    {
        if ($this->temFichaTecnica()) {
            return round($this->custoDaFichaTecnica(), 4);
        }

        return (float) $this->custo_variavel_unitario;
    }

    /**
     * Volume do próprio item; se não informado, cai no volume padrão da empresa.
     */
    public function volumeParaRateio(): float
    {
        $volume = (float) ($this->volume_projetado_mensal ?? 0);

        if ($volume > 0) {
            return $volume;
        }

        return (float) ($this->empresa?->volume_projetado_mensal ?? 1);
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
