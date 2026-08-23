<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Resultado completo de uma precificação, incluindo a memória de cálculo
 * passo a passo exigida para auditoria e justificativa legal do preço.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class ResultadoPrecificacao implements Arrayable
{
    /**
     * @param  array<int, array<string, mixed>>  $tributosAplicados
     * @param  array<int, array<string, mixed>>  $memoriaCalculo
     * @param  array<int, array<string, string>>  $alertas
     */
    public function __construct(
        public float $custoVariavelUnitario,
        public float $custoFixoTotal,
        public float $volumeProjetado,
        public float $rateioFixoUnitario,
        public float $custoTotalUnitario,
        public float $somaAliquotasEfetivas,
        public float $margemContribuicao,
        public float $divisor,
        public float $precoVenda,
        public float $valorTributos,
        public float $valorMargemContribuicao,
        public float $markup,
        public array $tributosAplicados,
        public array $memoriaCalculo,
        public array $alertas = [],
    ) {}

    /**
     * Preço arredondado para duas casas, como será praticado no ponto de venda.
     */
    public function precoVendaComercial(): float
    {
        return round($this->precoVenda, 2);
    }

    /**
     * Percentual do preço que sobra depois de tributos e custos.
     */
    public function percentualCusto(): float
    {
        return $this->precoVenda > 0
            ? ($this->custoTotalUnitario / $this->precoVenda) * 100
            : 0.0;
    }

    /**
     * Assinatura do cálculo: permite provar que o registro não foi alterado.
     */
    public function hashAuditoria(): string
    {
        return hash('sha256', json_encode([
            $this->custoVariavelUnitario,
            $this->custoFixoTotal,
            $this->volumeProjetado,
            $this->somaAliquotasEfetivas,
            $this->margemContribuicao,
            $this->precoVenda,
            $this->tributosAplicados,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'custo_variavel_unitario' => $this->custoVariavelUnitario,
            'custo_fixo_total' => $this->custoFixoTotal,
            'volume_projetado' => $this->volumeProjetado,
            'rateio_fixo_unitario' => $this->rateioFixoUnitario,
            'custo_total_unitario' => $this->custoTotalUnitario,
            'soma_aliquotas_efetivas' => $this->somaAliquotasEfetivas,
            'margem_contribuicao' => $this->margemContribuicao,
            'divisor' => $this->divisor,
            'preco_venda' => $this->precoVenda,
            'preco_venda_comercial' => $this->precoVendaComercial(),
            'valor_tributos' => $this->valorTributos,
            'valor_margem_contribuicao' => $this->valorMargemContribuicao,
            'markup' => $this->markup,
            'percentual_custo' => $this->percentualCusto(),
            'tributos_aplicados' => $this->tributosAplicados,
            'memoria_calculo' => $this->memoriaCalculo,
            'alertas' => $this->alertas,
        ];
    }
}
