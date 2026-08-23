<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustoFixo extends Model
{
    use HasFactory;

    protected $table = 'custos_fixos';

    protected $fillable = [
        'empresa_id',
        'descricao',
        'grupo',
        'valor_mensal',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'valor_mensal' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public const GRUPOS = [
        'ocupacao' => 'Ocupação (aluguel, condomínio, IPTU)',
        'pessoal' => 'Pessoal (salários, encargos, pró-labore)',
        'administrativo' => 'Administrativo (contabilidade, software, material)',
        'comercial' => 'Comercial (marketing, publicidade)',
        'financeiro' => 'Financeiro (tarifas, juros)',
        'outros' => 'Outros',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function getGrupoLabelAttribute(): string
    {
        return self::GRUPOS[$this->grupo] ?? $this->grupo;
    }
}
