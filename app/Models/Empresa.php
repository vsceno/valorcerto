<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'regime_tributario',
        'volume_projetado_mensal',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'volume_projetado_mensal' => 'decimal:4',
            'ativo' => 'boolean',
        ];
    }

    public const REGIMES = [
        'simples_nacional' => 'Simples Nacional',
        'lucro_presumido' => 'Lucro Presumido',
        'lucro_real' => 'Lucro Real',
        'mei' => 'MEI',
    ];

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public function tributos(): HasMany
    {
        return $this->hasMany(Tributo::class);
    }

    public function custosFixos(): HasMany
    {
        return $this->hasMany(CustoFixo::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function precificacoes(): HasMany
    {
        return $this->hasMany(Precificacao::class);
    }

    /**
     * Soma dos custos fixos mensais ativos - numerador do rateio.
     */
    public function custoFixoTotalMensal(): float
    {
        return (float) $this->custosFixos()->where('ativo', true)->sum('valor_mensal');
    }

    public function getRegimeLabelAttribute(): string
    {
        return self::REGIMES[$this->regime_tributario] ?? $this->regime_tributario;
    }

    /**
     * Empresa em uso pelo sistema. O modelo já suporta várias, mas a interface
     * opera sobre uma de cada vez.
     */
    public static function atual(): ?self
    {
        return static::query()->where('ativo', true)->orderBy('id')->first();
    }
}
