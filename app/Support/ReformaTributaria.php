<?php

namespace App\Support;

/**
 * Cronograma e tributos da reforma tributária sobre o consumo (EC 132/2023,
 * regulamentada pela LC 214/2025).
 *
 * A mudança que mais afeta a precificação não é a alíquota, e sim a MECÂNICA:
 * CBS e IBS são apurados POR FORA — somam-se ao preço em vez de ficarem
 * embutidos nele, como acontece hoje com ICMS, PIS, COFINS e ISS.
 *
 * ATENÇÃO: as alíquotas de referência ainda serão fixadas por lei específica e
 * resolução do Senado. Os percentuais abaixo são a ESTIMATIVA divulgada pelo
 * Ministério da Fazenda e servem apenas para simulação. Confirme com a
 * contabilidade antes de praticar qualquer preço baseado neles.
 */
final class ReformaTributaria
{
    /** Estimativa da alíquota de referência da CBS (federal). */
    public const CBS_REFERENCIA = 8.8;

    /** Estimativa da alíquota de referência do IBS (estadual + municipal). */
    public const IBS_REFERENCIA = 17.7;

    /** Ano em que a CBS substitui integralmente PIS e COFINS. */
    public const ANO_CBS_INTEGRAL = 2027;

    /** Ano em que ICMS e ISS são extintos e o IBS fica pleno. */
    public const ANO_IBS_PLENO = 2033;

    /**
     * Etapas da transição, para a interface explicar onde estamos.
     *
     * @return array<int, array<string, string>>
     */
    public static function cronograma(): array
    {
        return [
            [
                'periodo' => '2026',
                'titulo' => 'Ano-teste',
                'descricao' => 'CBS a 0,9% e IBS a 0,1%, compensáveis com PIS/COFINS. '
                    .'Serve para calibrar sistemas; o impacto no caixa é próximo de zero.',
            ],
            [
                'periodo' => '2027',
                'titulo' => 'CBS substitui PIS e COFINS',
                'descricao' => 'A CBS entra com alíquota cheia e PIS/COFINS são extintos. '
                    .'O IPI é zerado (exceto Zona Franca de Manaus) e o Imposto Seletivo começa a incidir.',
            ],
            [
                'periodo' => '2029 a 2032',
                'titulo' => 'Transição do ICMS e do ISS',
                'descricao' => 'ICMS e ISS caem gradualmente (9/10, 8/10, 7/10 e 6/10 das alíquotas atuais) '
                    .'enquanto o IBS sobe na mesma proporção.',
            ],
            [
                'periodo' => '2033',
                'titulo' => 'Modelo pleno',
                'descricao' => 'ICMS e ISS são extintos. Restam CBS e IBS, ambos calculados por fora, '
                    .'com crédito amplo e não cumulatividade plena.',
            ],
        ];
    }

    /**
     * Tributos da reforma vigentes a partir do ano informado.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tributosSugeridos(int $ano = self::ANO_CBS_INTEGRAL): array
    {
        [$cbs, $ibs] = self::aliquotasDoAno($ano);

        return [
            [
                'sigla' => 'CBS',
                'nome' => 'Contribuição sobre Bens e Serviços',
                'aliquota_nominal' => self::CBS_REFERENCIA,
                'aliquota_efetiva' => $cbs,
                'aplica_a' => 'ambos',
                'base_calculo' => 'por_fora',
                'vigencia_inicio' => sprintf('%d-01-01', $ano),
                'base_legal' => 'EC 132/2023 e LC 214/2025 - substitui PIS e COFINS',
                'observacoes' => 'Calculada por fora: soma-se ao preço. Alíquota de referência estimada, '
                    .'ainda pendente de fixação por lei específica.',
            ],
            [
                'sigla' => 'IBS',
                'nome' => 'Imposto sobre Bens e Serviços',
                'aliquota_nominal' => self::IBS_REFERENCIA,
                'aliquota_efetiva' => $ibs,
                'aplica_a' => 'ambos',
                'base_calculo' => 'por_fora',
                'vigencia_inicio' => sprintf('%d-01-01', $ano),
                'base_legal' => 'EC 132/2023 e LC 214/2025 - substitui ICMS e ISS',
                'observacoes' => 'Calculado por fora, com crédito amplo. Composto por parcela estadual e '
                    .'municipal, fixadas por resolução do Senado e lei local.',
            ],
        ];
    }

    /**
     * Alíquotas conforme a etapa da transição.
     *
     * @return array{0: float, 1: float} [CBS, IBS]
     */
    public static function aliquotasDoAno(int $ano): array
    {
        // Ano-teste: percentuais simbólicos, compensáveis com PIS/COFINS.
        if ($ano <= 2026) {
            return [0.9, 0.1];
        }

        // CBS já integral; IBS ainda simbólico até 2028.
        if ($ano <= 2028) {
            return [self::CBS_REFERENCIA, 0.1];
        }

        // 2029 a 2032: IBS sobe na fração em que ICMS/ISS caem.
        if ($ano < self::ANO_IBS_PLENO) {
            $fracao = min(($ano - 2028) / 10, 1.0);

            return [self::CBS_REFERENCIA, round(self::IBS_REFERENCIA * $fracao, 4)];
        }

        return [self::CBS_REFERENCIA, self::IBS_REFERENCIA];
    }

    /**
     * Fração das alíquotas de ICMS e ISS que ainda vigora no ano.
     * Em 2033 os dois são extintos.
     */
    public static function fatorIcmsIss(int $ano): float
    {
        if ($ano <= 2028) {
            return 1.0;
        }

        if ($ano >= self::ANO_IBS_PLENO) {
            return 0.0;
        }

        // 2029 -> 9/10, 2030 -> 8/10, 2031 -> 7/10, 2032 -> 6/10.
        return round((10 - ($ano - 2028)) / 10, 4);
    }
}
