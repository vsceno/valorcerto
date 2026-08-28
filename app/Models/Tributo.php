<?php

namespace App\Models;

use Carbon\CarbonInterface;
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
        'base_calculo',
        'vigencia_inicio',
        'vigencia_fim',
        'aplica_a',
        'regimes',
        'base_legal',
        'observacoes',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'aliquota_nominal' => 'decimal:4',
            'aliquota_efetiva' => 'decimal:4',
            'vigencia_inicio' => 'date',
            'vigencia_fim' => 'date',
            'regimes' => 'array',
            'ativo' => 'boolean',
        ];
    }

    public const BASES = [
        'por_dentro' => 'Por dentro (embutido no preço)',
        'por_fora' => 'Por fora (somado ao preço)',
    ];

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
     * Tributos em vigor na data informada. Permite que o cadastro atual e o
     * pós-reforma convivam sem se misturarem no cálculo.
     */
    public function scopeVigentesEm(Builder $query, ?CarbonInterface $data = null): Builder
    {
        $data = $data ?? now();

        return $query
            ->where(fn (Builder $q) => $q->whereNull('vigencia_inicio')->orWhere('vigencia_inicio', '<=', $data))
            ->where(fn (Builder $q) => $q->whereNull('vigencia_fim')->orWhere('vigencia_fim', '>=', $data));
    }

    /**
     * Tributos compatíveis com o regime da empresa. Sem restrição cadastrada,
     * o tributo vale para todos.
     */
    public function scopeParaRegime(Builder $query, ?string $regime): Builder
    {
        if ($regime === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($regime): void {
            $q->whereNull('regimes')->orWhereJsonContains('regimes', $regime);
        });
    }

    /**
     * Embutido no preço (ICMS, PIS, COFINS, ISS): entra no divisor da fórmula.
     */
    public function ehPorDentro(): bool
    {
        return $this->base_calculo !== 'por_fora';
    }

    /**
     * Somado ao preço (IPI hoje; CBS e IBS na reforma): não entra no divisor,
     * incide sobre o preço líquido já formado.
     */
    public function ehPorFora(): bool
    {
        return $this->base_calculo === 'por_fora';
    }

    public function getBaseCalculoLabelAttribute(): string
    {
        return self::BASES[$this->base_calculo] ?? $this->base_calculo;
    }

    /**
     * Descreve a vigência em texto, para a interface.
     */
    public function getVigenciaLabelAttribute(): string
    {
        $inicio = $this->vigencia_inicio?->format('d/m/Y');
        $fim = $this->vigencia_fim?->format('d/m/Y');

        return match (true) {
            $inicio && $fim => "de {$inicio} a {$fim}",
            (bool) $inicio => "a partir de {$inicio}",
            (bool) $fim => "até {$fim}",
            default => 'sem prazo definido',
        };
    }

    /**
     * Diferença entre a alíquota de tabela e a efetivamente suportada.
     */
    public function getEconomiaFiscalAttribute(): float
    {
        return (float) $this->aliquota_nominal - (float) $this->aliquota_efetiva;
    }
}
