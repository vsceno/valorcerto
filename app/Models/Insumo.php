<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insumo extends Model
{
    use HasFactory;

    protected $table = 'insumos';

    protected $fillable = [
        'empresa_id',
        'nome',
        'codigo',
        'fornecedor',
        'grupo',
        'unidade_compra',
        'preco_compra',
        'rendimento',
        'unidade_uso',
        'perda_percentual',
        'observacoes',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'preco_compra' => 'decimal:4',
            'rendimento' => 'decimal:4',
            'perda_percentual' => 'decimal:4',
            'ativo' => 'boolean',
        ];
    }

    public const GRUPOS = [
        'metalurgia' => 'Metalurgia (perfis, chapas, tubos)',
        'eletronica' => 'Eletrônica (placas, sensores, fontes)',
        'mecanica' => 'Mecânica (mecanismos, rolamentos, molas)',
        'acabamento' => 'Acabamento (tinta, solda, tratamento)',
        'fixacao' => 'Fixação (parafusos, rebites, kits)',
        'mao_de_obra' => 'Mão de obra direta',
        'outros' => 'Outros',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function composicoes(): HasMany
    {
        return $this->hasMany(Composicao::class);
    }

    public function itens(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'composicoes')
            ->withPivot(['quantidade', 'observacao'])
            ->withTimestamps();
    }

    /**
     * Custo de uma unidade de USO, já com a perda embutida.
     *
     * Ex.: vara de metalon de 6 m por R$ 265,00 com 8% de perda de corte
     *      -> (265 / 6) x 1,08 = R$ 47,70 por metro aproveitado.
     */
    public function custoUnitarioUso(): float
    {
        $rendimento = (float) $this->rendimento;

        if ($rendimento <= 0) {
            return 0.0;
        }

        $custoBase = (float) $this->preco_compra / $rendimento;

        return $custoBase * (1 + ((float) $this->perda_percentual / 100));
    }

    /**
     * Custo por unidade de uso sem considerar a perda, para comparação.
     */
    public function custoUnitarioSemPerda(): float
    {
        $rendimento = (float) $this->rendimento;

        return $rendimento > 0 ? (float) $this->preco_compra / $rendimento : 0.0;
    }

    /**
     * True quando a unidade de compra difere da de uso (vara, chapa, rolo).
     */
    public function exigeConversao(): bool
    {
        return (float) $this->rendimento != 1.0 || $this->unidade_compra !== $this->unidade_uso;
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function getGrupoLabelAttribute(): string
    {
        return self::GRUPOS[$this->grupo] ?? $this->grupo;
    }

    /**
     * Descreve a conversão em texto, para a interface e a memória de cálculo.
     */
    public function getConversaoAttribute(): string
    {
        if (! $this->exigeConversao()) {
            return sprintf('1 %s', $this->unidade_uso);
        }

        return sprintf(
            '1 %s = %s %s',
            $this->unidade_compra,
            rtrim(rtrim(number_format((float) $this->rendimento, 4, ',', '.'), '0'), ','),
            $this->unidade_uso
        );
    }
}
