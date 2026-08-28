<?php

namespace App\Support;

/**
 * Catálogo dos regimes e do conjunto de tributos que cada um comporta.
 *
 * Serve para o sistema sugerir a configuração correta ao cadastrar a empresa e
 * para apontar divergências depois — antes, o regime era apenas decorativo.
 *
 * ATENÇÃO: as alíquotas aqui são REFERÊNCIAS para partida, não apuração fiscal.
 * A efetiva de cada empresa depende de créditos, faixa, anexo, benefícios e
 * legislação estadual/municipal. Confirme com a contabilidade.
 */
final class RegimeTributario
{
    /**
     * Tributos típicos por regime, no modelo vigente até a reforma.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tributosSugeridos(string $regime, string $atividade): array
    {
        return match ($regime) {
            'mei' => self::mei(),
            'simples_nacional' => self::simplesNacional($atividade),
            'lucro_presumido' => self::lucroPresumido($atividade),
            'lucro_real' => self::lucroReal($atividade),
            default => [],
        };
    }

    /**
     * O MEI recolhe valor fixo mensal, não percentual sobre a receita. O custo
     * do DAS entra como custo fixo, e não como alíquota no divisor.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function mei(): array
    {
        return [];
    }

    /**
     * No Simples o recolhimento é unificado no DAS: cadastrar ICMS, PIS e
     * COFINS separadamente dobraria a carga no cálculo.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function simplesNacional(string $atividade): array
    {
        return [[
            'sigla' => 'DAS',
            'nome' => 'Simples Nacional - alíquota efetiva do DAS',
            'aliquota_nominal' => 6.0,
            'aliquota_efetiva' => 6.0,
            'aplica_a' => 'ambos',
            'base_calculo' => 'por_dentro',
            'base_legal' => 'LC 123/2006 - alíquota efetiva conforme anexo e faixa de receita bruta em 12 meses',
            'observacoes' => 'Substitua pela alíquota efetiva da sua faixa. O DAS unifica IRPJ, CSLL, PIS, '
                .'COFINS, IPI, ICMS, ISS e CPP — não cadastre esses tributos separadamente.',
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function lucroPresumido(string $atividade): array
    {
        $tributos = [
            self::pis(1.65, 'Lei 9.715/1998 - regime cumulativo'),
            self::cofins(3.0, 'Lei 9.718/1998 - regime cumulativo'),
            self::irpj($atividade === 'servicos' ? 4.8 : 1.2, $atividade),
            self::csll($atividade === 'servicos' ? 2.88 : 1.08, $atividade),
        ];

        return array_merge($tributos, self::porAtividade($atividade));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function lucroReal(string $atividade): array
    {
        $tributos = [
            self::pis(1.65, 'Lei 10.637/2002 - regime não cumulativo, líquido de créditos'),
            self::cofins(7.6, 'Lei 10.833/2003 - regime não cumulativo, líquido de créditos'),
        ];

        return array_merge($tributos, self::porAtividade($atividade));
    }

    /**
     * ICMS incide sobre mercadoria; ISS sobre serviço.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function porAtividade(string $atividade): array
    {
        $tributos = [];

        if (in_array($atividade, ['comercio', 'industria', 'misto'], true)) {
            $tributos[] = [
                'sigla' => 'ICMS',
                'nome' => 'ICMS sobre circulação de mercadorias',
                'aliquota_nominal' => 18.0,
                'aliquota_efetiva' => 18.0,
                'aplica_a' => 'produto',
                'base_calculo' => 'por_dentro',
                'base_legal' => 'LC 87/1996 - alíquota interna do seu estado',
                'observacoes' => 'Reduza a efetiva pelos créditos das compras.',
            ];
        }

        if ($atividade === 'industria') {
            $tributos[] = [
                'sigla' => 'IPI',
                'nome' => 'IPI sobre produtos industrializados',
                'aliquota_nominal' => 5.0,
                'aliquota_efetiva' => 5.0,
                'aplica_a' => 'produto',
                'base_calculo' => 'por_fora',
                'base_legal' => 'Decreto 7.212/2010 - alíquota conforme TIPI',
                'observacoes' => 'O IPI é calculado por fora: soma-se ao preço, não fica embutido nele.',
            ];
        }

        if (in_array($atividade, ['servicos', 'misto'], true)) {
            $tributos[] = [
                'sigla' => 'ISS',
                'nome' => 'ISS sobre serviços',
                'aliquota_nominal' => 5.0,
                'aliquota_efetiva' => 5.0,
                'aplica_a' => 'servico',
                'base_calculo' => 'por_dentro',
                'base_legal' => 'LC 116/2003 - alíquota do seu município (2% a 5%)',
                'observacoes' => null,
            ];
        }

        return $tributos;
    }

    /**
     * @return array<string, mixed>
     */
    private static function pis(float $aliquota, string $base): array
    {
        return [
            'sigla' => 'PIS',
            'nome' => 'PIS sobre faturamento',
            'aliquota_nominal' => $aliquota,
            'aliquota_efetiva' => $aliquota,
            'aplica_a' => 'ambos',
            'base_calculo' => 'por_dentro',
            'base_legal' => $base,
            'observacoes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cofins(float $aliquota, string $base): array
    {
        return [
            'sigla' => 'COFINS',
            'nome' => 'COFINS sobre faturamento',
            'aliquota_nominal' => $aliquota,
            'aliquota_efetiva' => $aliquota,
            'aplica_a' => 'ambos',
            'base_calculo' => 'por_dentro',
            'base_legal' => $base,
            'observacoes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function irpj(float $aliquota, string $atividade): array
    {
        return [
            'sigla' => 'IRPJ',
            'nome' => 'IRPJ sobre lucro presumido',
            'aliquota_nominal' => 15.0,
            'aliquota_efetiva' => $aliquota,
            'aplica_a' => 'ambos',
            'base_calculo' => 'por_dentro',
            'base_legal' => sprintf(
                'Lei 9.430/1996 - 15%% sobre base presumida de %s',
                $atividade === 'servicos' ? '32%' : '8%'
            ),
            'observacoes' => 'Não inclui o adicional de 10% sobre o lucro que exceder R$ 20.000/mês.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function csll(float $aliquota, string $atividade): array
    {
        return [
            'sigla' => 'CSLL',
            'nome' => 'CSLL sobre lucro presumido',
            'aliquota_nominal' => 9.0,
            'aliquota_efetiva' => $aliquota,
            'aplica_a' => 'ambos',
            'base_calculo' => 'por_dentro',
            'base_legal' => sprintf(
                'Lei 7.689/1988 - 9%% sobre base presumida de %s',
                $atividade === 'servicos' ? '32%' : '12%'
            ),
            'observacoes' => null,
        ];
    }
}
