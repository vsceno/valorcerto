<?php

namespace App\Models;

use App\Support\RegimeTributario;
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
        'inscricao_estadual',
        'inscricao_municipal',
        'cnae_principal',
        'atividade',
        'uf',
        'municipio',
        'faturamento_12_meses',
        'regime_tributario',
        'regime_vigente_desde',
        'volume_projetado_mensal',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'volume_projetado_mensal' => 'decimal:4',
            'faturamento_12_meses' => 'decimal:2',
            'regime_vigente_desde' => 'date',
            'ativo' => 'boolean',
        ];
    }

    public const REGIMES = [
        'simples_nacional' => 'Simples Nacional',
        'lucro_presumido' => 'Lucro Presumido',
        'lucro_real' => 'Lucro Real',
        'mei' => 'MEI',
    ];

    public const ATIVIDADES = [
        'comercio' => 'Comércio (revenda de mercadorias)',
        'industria' => 'Indústria (fabricação)',
        'servicos' => 'Serviços',
        'misto' => 'Misto (mercadorias e serviços)',
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
        $selecionada = session('empresa_id');

        if ($selecionada) {
            $empresa = static::query()->where('ativo', true)->find($selecionada);

            if ($empresa) {
                return $empresa;
            }
        }

        return static::query()->where('ativo', true)->orderBy('id')->first();
    }

    public function getAtividadeLabelAttribute(): string
    {
        return self::ATIVIDADES[$this->atividade] ?? (string) $this->atividade;
    }

    /**
     * No Simples e no MEI a tributação é unificada; nos demais regimes cada
     * tributo é apurado em separado.
     */
    public function temTributacaoUnificada(): bool
    {
        return in_array($this->regime_tributario, ['simples_nacional', 'mei'], true);
    }

    /**
     * Siglas que o regime e a atividade da empresa comportam.
     *
     * @return array<int, string>
     */
    public function siglasEsperadas(): array
    {
        return array_column(
            RegimeTributario::tributosSugeridos($this->regime_tributario, $this->atividade ?? 'comercio'),
            'sigla'
        );
    }

    /**
     * Tributos cadastrados que não pertencem ao regime declarado — o caso
     * clássico é cadastrar ICMS separado numa empresa do Simples Nacional,
     * o que dobraria a carga no cálculo.
     *
     * @return array<int, string>
     */
    public function tributosIncompativeis(): array
    {
        $esperadas = $this->siglasEsperadas();

        if ($esperadas === []) {
            return [];
        }

        return $this->tributos()
            ->where('ativo', true)
            ->vigentesEm()
            ->pluck('sigla')
            ->reject(fn (string $sigla): bool => in_array($sigla, $esperadas, true))
            // CBS, IBS e IS são da reforma: convivem com qualquer regime.
            ->reject(fn (string $sigla): bool => in_array($sigla, ['CBS', 'IBS', 'IS'], true))
            ->values()
            ->all();
    }
}
