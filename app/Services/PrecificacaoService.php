<?php

namespace App\Services;

use App\DTO\ResultadoPrecificacao;
use App\Exceptions\PrecificacaoInviavelException;
use App\Models\Composicao;
use App\Models\Item;
use App\Models\Precificacao;
use App\Models\Tributo;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Implementa a fórmula oficial de formação de preço:
 *
 *   Preço de Venda = (Custo Variável Unitário + Rateio Fixo Unitário)
 *                    ÷ (1 - (Soma das Alíquotas Efetivas + Margem de Contribuição))
 *
 * A margem de contribuição incide sobre o PREÇO FINAL, nunca sobre o custo -
 * por isso ela entra no divisor, e não como multiplicador de markup.
 */
class PrecificacaoService
{
    /** Abaixo deste divisor (em %), o preço dispara e o negócio fica frágil. */
    public const DIVISOR_CRITICO = 15.0;

    /** Variação de preço a partir da qual o CDC exige justa causa documentada. */
    public const VARIACAO_RELEVANTE = 10.0;

    /**
     * @param  array<int, array<string, mixed>>  $tributos
     */
    public function calcular(
        float $custoVariavelUnitario,
        float $custoFixoTotal,
        float $volumeProjetado,
        array $tributos,
        float $margemContribuicao,
        ?float $precoAnterior = null,
        ?string $cenario = null,
    ): ResultadoPrecificacao {
        $this->validarEntradas($custoVariavelUnitario, $custoFixoTotal, $volumeProjetado, $margemContribuicao);

        $memoria = [];

        // Passo 1 - Rateio do custo fixo pelo volume projetado.
        $rateioFixoUnitario = $custoFixoTotal / $volumeProjetado;
        $memoria[] = [
            'ordem' => 1,
            'titulo' => 'Rateio do custo fixo por unidade',
            'formula' => 'Rateio Fixo Unitário = Custo Fixo Total ÷ Volume Projetado',
            'substituicao' => sprintf('%s ÷ %s', $this->moeda($custoFixoTotal), $this->numero($volumeProjetado)),
            'resultado' => $this->moeda($rateioFixoUnitario),
            'resultado_valor' => round($rateioFixoUnitario, 4),
            'explicacao' => 'Cada unidade vendida precisa carregar uma fatia dos custos que existem independentemente da venda (aluguel, folha, contabilidade).',
        ];

        // Passo 2 - Custo total unitário: o que sai do caixa por unidade.
        $custoTotalUnitario = $custoVariavelUnitario + $rateioFixoUnitario;
        $memoria[] = [
            'ordem' => 2,
            'titulo' => 'Custo total por unidade',
            'formula' => 'Custo Total Unitário = Custo Variável Unitário + Rateio Fixo Unitário',
            'substituicao' => sprintf('%s + %s', $this->moeda($custoVariavelUnitario), $this->moeda($rateioFixoUnitario)),
            'resultado' => $this->moeda($custoTotalUnitario),
            'resultado_valor' => round($custoTotalUnitario, 4),
            'explicacao' => 'É o numerador da fórmula: o custo real que o preço precisa cobrir antes de tributos e margem.',
        ];

        // Passo 3 - Soma das alíquotas EFETIVAS que incidem POR DENTRO.
        // Tributos por fora (IPI hoje; CBS e IBS na reforma) não entram aqui:
        // eles não estão embutidos no preço, e sim somados a ele no passo 7.
        $tributosNormalizados = $this->normalizarTributos($tributos);
        $porDentro = array_values(array_filter(
            $tributosNormalizados,
            fn (array $t): bool => $t['base_calculo'] !== 'por_fora'
        ));
        $porFora = array_values(array_filter(
            $tributosNormalizados,
            fn (array $t): bool => $t['base_calculo'] === 'por_fora'
        ));

        $somaAliquotas = array_sum(array_column($porDentro, 'aliquota_efetiva'));
        $somaPorFora = array_sum(array_column($porFora, 'aliquota_efetiva'));

        $memoria[] = [
            'ordem' => 3,
            'titulo' => 'Soma das alíquotas efetivas embutidas no preço',
            'formula' => 'Soma das Alíquotas = ICMS + PIS + COFINS + ISS + outros (efetivos, por dentro)',
            'substituicao' => $porDentro === []
                ? 'Nenhum tributo por dentro aplicável'
                : implode(' + ', array_map(
                    fn (array $t): string => sprintf('%s %s', $t['sigla'], $this->percentual($t['aliquota_efetiva'])),
                    $porDentro
                )),
            'resultado' => $this->percentual($somaAliquotas),
            'resultado_valor' => round($somaAliquotas, 4),
            'explicacao' => 'Usa-se a alíquota efetiva (já descontados créditos e reduções de base), porque é ela '
                .'que de fato sai do caixa. Só entram aqui os tributos embutidos no preço.',
        ];

        // Passo 4 - Divisor: a fatia do preço que NÃO é tributo nem margem.
        $percentualRetido = $somaAliquotas + $margemContribuicao;
        $divisor = 1 - ($percentualRetido / 100);

        if ($divisor <= 0) {
            throw new PrecificacaoInviavelException(sprintf(
                'Cálculo inviável: tributos (%s) somados à margem (%s) consomem %s do preço. '
                .'Reduza a margem desejada ou revise as alíquotas efetivas.',
                $this->percentual($somaAliquotas),
                $this->percentual($margemContribuicao),
                $this->percentual($percentualRetido)
            ));
        }

        $memoria[] = [
            'ordem' => 4,
            'titulo' => 'Divisor da fórmula',
            'formula' => 'Divisor = 1 - (Soma das Alíquotas + Margem de Contribuição)',
            'substituicao' => sprintf(
                '1 - (%s + %s) = 1 - %s',
                $this->percentual($somaAliquotas),
                $this->percentual($margemContribuicao),
                $this->percentual($percentualRetido)
            ),
            'resultado' => $this->numero($divisor, 6),
            'resultado_valor' => round($divisor, 8),
            'explicacao' => sprintf(
                'De cada R$ 1,00 vendido, %s ficam comprometidos com tributos e margem; sobram %s para cobrir o custo.',
                $this->percentual($percentualRetido),
                $this->percentual($divisor * 100)
            ),
        ];

        // Passo 5 - Preço de venda.
        $precoVenda = $custoTotalUnitario / $divisor;
        $memoria[] = [
            'ordem' => 5,
            'titulo' => 'Preço de venda',
            'formula' => 'Preço = (Custo Variável + Rateio Fixo) ÷ (1 - (Alíquotas + Margem))',
            'substituicao' => sprintf('%s ÷ %s', $this->moeda($custoTotalUnitario), $this->numero($divisor, 6)),
            'resultado' => $this->moeda($precoVenda),
            'resultado_valor' => round($precoVenda, 4),
            'explicacao' => 'Dividir pelo divisor (e não multiplicar por markup) garante que a margem seja exatamente o percentual desejado sobre o preço final.',
        ];

        // Passo 6 - Prova real: a soma das partes tem que reconstruir o preço.
        $valorTributos = $precoVenda * ($somaAliquotas / 100);
        $valorMargem = $precoVenda * ($margemContribuicao / 100);
        $memoria[] = [
            'ordem' => 6,
            'titulo' => 'Prova real da decomposição',
            'formula' => 'Preço = Custo Total + Tributos + Margem de Contribuição',
            'substituicao' => sprintf(
                '%s + %s + %s',
                $this->moeda($custoTotalUnitario),
                $this->moeda($valorTributos),
                $this->moeda($valorMargem)
            ),
            'resultado' => $this->moeda($custoTotalUnitario + $valorTributos + $valorMargem),
            'resultado_valor' => round($custoTotalUnitario + $valorTributos + $valorMargem, 4),
            'explicacao' => 'Conferência de auditoria: as três parcelas reconstroem o preço, comprovando que a margem foi apurada sobre o preço final.',
        ];

        // Passo 7 - Tributos por fora, quando existirem: incidem sobre o preço
        // já formado e são somados a ele. É a mecânica da CBS e do IBS.
        $valorPorFora = $precoVenda * ($somaPorFora / 100);

        if ($porFora !== []) {
            $memoria[] = [
                'ordem' => 7,
                'titulo' => 'Tributos calculados por fora',
                'formula' => 'Preço Final = Preço Líquido + (Preço Líquido × Alíquotas por Fora)',
                'substituicao' => sprintf(
                    '%s + (%s × %s)',
                    $this->moeda($precoVenda),
                    $this->moeda($precoVenda),
                    $this->percentual($somaPorFora)
                ),
                'resultado' => $this->moeda($precoVenda + $valorPorFora),
                'resultado_valor' => round($precoVenda + $valorPorFora, 4),
                'explicacao' => sprintf(
                    'Estes tributos (%s) não ficam embutidos no preço: são acrescentados a ele. '
                    .'A receita da empresa continua sendo o preço líquido; o cliente paga o total.',
                    implode(', ', array_column($porFora, 'sigla'))
                ),
            ];
        }

        $markup = $custoTotalUnitario > 0 ? $precoVenda / $custoTotalUnitario : 0.0;

        return new ResultadoPrecificacao(
            custoVariavelUnitario: round($custoVariavelUnitario, 4),
            custoFixoTotal: round($custoFixoTotal, 2),
            volumeProjetado: round($volumeProjetado, 4),
            rateioFixoUnitario: round($rateioFixoUnitario, 4),
            custoTotalUnitario: round($custoTotalUnitario, 4),
            somaAliquotasEfetivas: round($somaAliquotas, 4),
            margemContribuicao: round($margemContribuicao, 4),
            divisor: round($divisor, 8),
            precoVenda: round($precoVenda, 4),
            valorTributos: round($valorTributos, 4),
            valorMargemContribuicao: round($valorMargem, 4),
            markup: round($markup, 6),
            tributosAplicados: $tributosNormalizados,
            memoriaCalculo: $memoria,
            alertas: $this->gerarAlertas(
                divisor: $divisor,
                margem: $margemContribuicao,
                precoVenda: $precoVenda,
                custoTotalUnitario: $custoTotalUnitario,
                tributos: $tributosNormalizados,
                precoAnterior: $precoAnterior,
            ),
            somaAliquotasPorFora: round($somaPorFora, 4),
            valorTributosPorFora: round($valorPorFora, 4),
            cenario: $cenario,
        );
    }

    /**
     * Calcula usando os cadastros da empresa: custos fixos ativos, tributos
     * aplicáveis ao tipo do item e o volume de rateio do próprio item.
     */
    public function calcularParaItem(
        Item $item,
        ?float $margemContribuicao = null,
        ?float $custoVariavelUnitario = null,
        ?float $volumeProjetado = null,
        ?CarbonInterface $data = null,
        ?string $cenario = null,
    ): ResultadoPrecificacao {
        $precoAnterior = $item->ultimaPrecificacao?->preco_venda;

        return $this->calcular(
            // Com ficha técnica montada, o custo vem da soma dos insumos.
            custoVariavelUnitario: $custoVariavelUnitario ?? $item->custoVariavelEfetivo(),
            custoFixoTotal: $item->empresa?->custoFixoTotalMensal() ?? 0.0,
            volumeProjetado: $volumeProjetado ?? $item->volumeParaRateio(),
            tributos: $this->tributosParaArray($this->tributosDoItem($item, $data)),
            margemContribuicao: $margemContribuicao ?? (float) $item->margem_contribuicao_desejada,
            precoAnterior: $precoAnterior !== null ? (float) $precoAnterior : null,
            cenario: $cenario,
        );
    }

    /**
     * Tributos que incidem sobre o item: filtrados pelo tipo, pela vigência na
     * data e pelo regime tributário da empresa.
     *
     * @return Collection<int, Tributo>
     */
    public function tributosDoItem(Item $item, ?CarbonInterface $data = null): Collection
    {
        return Tributo::query()
            ->where('empresa_id', $item->empresa_id)
            ->ativos()
            ->aplicaveisA($item->tipo)
            ->vigentesEm($data)
            ->paraRegime($item->empresa?->regime_tributario)
            ->orderBy('base_calculo')
            ->orderBy('sigla')
            ->get();
    }

    /**
     * Compara o preço no modelo vigente hoje com o preço na data futura
     * informada, quando os tributos da reforma já estiverem em vigor.
     *
     * @return array{atual: ResultadoPrecificacao, futuro: ResultadoPrecificacao}
     */
    public function compararCenarios(
        Item $item,
        CarbonInterface $dataFutura,
        ?float $margemContribuicao = null,
        ?float $custoVariavelUnitario = null,
        ?float $volumeProjetado = null,
    ): array {
        return [
            'atual' => $this->calcularParaItem(
                item: $item,
                margemContribuicao: $margemContribuicao,
                custoVariavelUnitario: $custoVariavelUnitario,
                volumeProjetado: $volumeProjetado,
                data: now(),
                cenario: 'Modelo atual',
            ),
            'futuro' => $this->calcularParaItem(
                item: $item,
                margemContribuicao: $margemContribuicao,
                custoVariavelUnitario: $custoVariavelUnitario,
                volumeProjetado: $volumeProjetado,
                data: $dataFutura,
                cenario: 'Reforma em '.$dataFutura->format('d/m/Y'),
            ),
        ];
    }

    /**
     * Persiste o cálculo como registro imutável de auditoria.
     */
    public function registrar(
        ResultadoPrecificacao $resultado,
        Item $item,
        ?string $justificativa = null,
        ?string $observacoes = null,
        ?int $userId = null,
    ): Precificacao {
        return Precificacao::create([
            'empresa_id' => $item->empresa_id,
            'item_id' => $item->id,
            'user_id' => $userId,
            'item_nome' => $item->nome,
            'item_sku' => $item->sku,
            'item_tipo' => $item->tipo,
            'custo_variavel_unitario' => $resultado->custoVariavelUnitario,
            'custo_fixo_total' => $resultado->custoFixoTotal,
            'volume_projetado' => $resultado->volumeProjetado,
            'rateio_fixo_unitario' => $resultado->rateioFixoUnitario,
            'custo_total_unitario' => $resultado->custoTotalUnitario,
            'soma_aliquotas_efetivas' => $resultado->somaAliquotasEfetivas,
            'margem_contribuicao' => $resultado->margemContribuicao,
            'divisor' => $resultado->divisor,
            'preco_venda' => $resultado->precoVenda,
            'valor_tributos' => $resultado->valorTributos,
            'valor_margem_contribuicao' => $resultado->valorMargemContribuicao,
            'markup' => $resultado->markup,
            'memoria_calculo' => $resultado->memoriaCalculo,
            'tributos_aplicados' => $resultado->tributosAplicados,
            'composicao_aplicada' => $this->snapshotDaFichaTecnica($item),
            'justificativa' => $justificativa,
            'observacoes' => $observacoes,
            'hash_auditoria' => $resultado->hashAuditoria(),
            'calculado_em' => now(),
        ]);
    }

    /**
     * Congela a ficha técnica do item para o registro de auditoria, de modo que
     * o custo possa ser reconstruído linha a linha mesmo que os insumos mudem
     * de preço depois.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function snapshotDaFichaTecnica(Item $item): ?array
    {
        if (! $item->temFichaTecnica()) {
            return null;
        }

        return $item->composicoes()
            ->with('insumo')
            ->get()
            ->map(fn (Composicao $linha): array => $linha->paraSnapshot())
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Tributo>  $tributos
     * @return array<int, array<string, mixed>>
     */
    public function tributosParaArray(Collection $tributos): array
    {
        return $tributos->map(fn (Tributo $t): array => [
            'sigla' => $t->sigla,
            'nome' => $t->nome,
            'aliquota_nominal' => (float) $t->aliquota_nominal,
            'aliquota_efetiva' => (float) $t->aliquota_efetiva,
            'base_calculo' => $t->base_calculo,
            'base_legal' => $t->base_legal,
        ])->values()->all();
    }

    private function validarEntradas(
        float $custoVariavelUnitario,
        float $custoFixoTotal,
        float $volumeProjetado,
        float $margemContribuicao,
    ): void {
        if ($volumeProjetado <= 0) {
            throw new PrecificacaoInviavelException(
                'O volume projetado precisa ser maior que zero para ratear os custos fixos.'
            );
        }

        if ($custoVariavelUnitario < 0 || $custoFixoTotal < 0) {
            throw new PrecificacaoInviavelException('Os custos não podem ser negativos.');
        }

        if ($margemContribuicao < 0) {
            throw new PrecificacaoInviavelException(
                'A margem de contribuição não pode ser negativa: isso significaria vender com prejuízo planejado.'
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $tributos
     * @return array<int, array<string, mixed>>
     */
    private function normalizarTributos(array $tributos): array
    {
        $normalizados = [];

        foreach ($tributos as $tributo) {
            $aliquota = (float) ($tributo['aliquota_efetiva'] ?? 0);

            if ($aliquota < 0) {
                throw new PrecificacaoInviavelException('Alíquota efetiva não pode ser negativa.');
            }

            $normalizados[] = [
                'sigla' => (string) ($tributo['sigla'] ?? 'N/D'),
                'nome' => (string) ($tributo['nome'] ?? ''),
                'aliquota_nominal' => (float) ($tributo['aliquota_nominal'] ?? $aliquota),
                'aliquota_efetiva' => $aliquota,
                'base_calculo' => ($tributo['base_calculo'] ?? 'por_dentro') === 'por_fora' ? 'por_fora' : 'por_dentro',
                'base_legal' => $tributo['base_legal'] ?? null,
            ];
        }

        return $normalizados;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tributos
     * @return array<int, array<string, string>>
     */
    private function gerarAlertas(
        float $divisor,
        float $margem,
        float $precoVenda,
        float $custoTotalUnitario,
        array $tributos,
        ?float $precoAnterior,
    ): array {
        $alertas = [];

        if ($divisor * 100 < self::DIVISOR_CRITICO) {
            $alertas[] = [
                'nivel' => 'critico',
                'titulo' => 'Divisor muito baixo',
                'mensagem' => sprintf(
                    'Sobram apenas %s do preço para cobrir o custo. Pequenas variações de custo provocam saltos grandes no preço.',
                    $this->percentual($divisor * 100)
                ),
            ];
        }

        if ($tributos === []) {
            $alertas[] = [
                'nivel' => 'atencao',
                'titulo' => 'Nenhum tributo aplicado',
                'mensagem' => 'O preço foi formado sem carga tributária. Confirme o enquadramento fiscal antes de praticar este valor.',
            ];
        }

        if ($margem <= 0) {
            $alertas[] = [
                'nivel' => 'atencao',
                'titulo' => 'Margem de contribuição zerada',
                'mensagem' => 'O preço cobre custos e tributos, mas não gera contribuição para o lucro.',
            ];
        }

        if ($precoVenda < $custoTotalUnitario) {
            $alertas[] = [
                'nivel' => 'critico',
                'titulo' => 'Preço abaixo do custo',
                'mensagem' => 'Venda sistemática abaixo do custo pode configurar infração à ordem econômica (Lei 12.529/2011, art. 36, § 3º, XV).',
            ];
        }

        if ($precoAnterior !== null && $precoAnterior > 0) {
            $variacao = (($precoVenda - $precoAnterior) / $precoAnterior) * 100;

            if (abs($variacao) >= self::VARIACAO_RELEVANTE) {
                $alertas[] = [
                    'nivel' => $variacao > 0 ? 'atencao' : 'info',
                    'titulo' => sprintf('Variação de %s sobre o preço anterior', $this->percentual($variacao)),
                    'mensagem' => $variacao > 0
                        ? 'Elevação relevante de preço. Registre a justa causa do reajuste (CDC, art. 39, X) no campo de justificativa.'
                        : 'Redução relevante de preço. Confirme se o novo valor ainda cobre custos e tributos.',
                ];
            }
        }

        return $alertas;
    }

    private function moeda(float $valor): string
    {
        return 'R$ '.number_format($valor, 2, ',', '.');
    }

    private function percentual(float $valor): string
    {
        return number_format($valor, 2, ',', '.').'%';
    }

    private function numero(float $valor, int $casas = 2): string
    {
        return number_format($valor, $casas, ',', '.');
    }
}
