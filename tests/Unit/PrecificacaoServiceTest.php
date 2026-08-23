<?php

namespace Tests\Unit;

use App\Exceptions\PrecificacaoInviavelException;
use App\Services\PrecificacaoService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PrecificacaoServiceTest extends TestCase
{
    private PrecificacaoService $servico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servico = new PrecificacaoService;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tributos(float ...$aliquotas): array
    {
        $lista = [];

        foreach ($aliquotas as $i => $aliquota) {
            $lista[] = ['sigla' => "T{$i}", 'nome' => "Tributo {$i}", 'aliquota_efetiva' => $aliquota];
        }

        return $lista;
    }

    #[Test]
    public function aplica_a_formula_oficial_de_precificacao(): void
    {
        // Custo fixo 20.000 / 500 un = 40 de rateio; + 30 de variável = 70 de custo.
        // Divisor = 1 - (27,25% + 20%) = 0,5275 -> 70 / 0,5275 = 132,7014...
        $resultado = $this->servico->calcular(
            custoVariavelUnitario: 30,
            custoFixoTotal: 20000,
            volumeProjetado: 500,
            tributos: $this->tributos(18.0, 1.65, 7.6),
            margemContribuicao: 20,
        );

        $this->assertSame(40.0, $resultado->rateioFixoUnitario);
        $this->assertSame(70.0, $resultado->custoTotalUnitario);
        $this->assertSame(27.25, $resultado->somaAliquotasEfetivas);
        $this->assertSame(0.5275, $resultado->divisor);
        $this->assertSame(132.7014, $resultado->precoVenda);
    }

    #[Test]
    public function a_margem_incide_sobre_o_preco_final_e_nao_sobre_o_custo(): void
    {
        $resultado = $this->servico->calcular(
            custoVariavelUnitario: 100,
            custoFixoTotal: 0,
            volumeProjetado: 1,
            tributos: [],
            margemContribuicao: 30,
        );

        // 30% do preço final, não 30% de markup sobre o custo (que daria 130).
        $this->assertEqualsWithDelta(142.857142, $resultado->precoVenda, 0.0001);
        $this->assertEqualsWithDelta(
            30.0,
            ($resultado->valorMargemContribuicao / $resultado->precoVenda) * 100,
            0.0001
        );
    }

    #[Test]
    public function a_decomposicao_do_preco_reconstroi_o_valor_final(): void
    {
        $resultado = $this->servico->calcular(
            custoVariavelUnitario: 12.4,
            custoFixoTotal: 28540,
            volumeProjetado: 900,
            tributos: $this->tributos(11.5, 1.2, 5.4, 1.2, 1.08),
            margemContribuicao: 25,
        );

        $soma = $resultado->custoTotalUnitario
            + $resultado->valorTributos
            + $resultado->valorMargemContribuicao;

        $this->assertEqualsWithDelta($resultado->precoVenda, $soma, 0.001);
    }

    #[Test]
    public function registra_seis_passos_na_memoria_de_calculo(): void
    {
        $resultado = $this->servico->calcular(
            custoVariavelUnitario: 10,
            custoFixoTotal: 1000,
            volumeProjetado: 100,
            tributos: $this->tributos(10.0),
            margemContribuicao: 20,
        );

        $this->assertCount(6, $resultado->memoriaCalculo);

        foreach ($resultado->memoriaCalculo as $passo) {
            $this->assertArrayHasKey('formula', $passo);
            $this->assertArrayHasKey('substituicao', $passo);
            $this->assertArrayHasKey('resultado', $passo);
            $this->assertNotEmpty($passo['formula']);
        }
    }

    #[Test]
    public function recusa_calculo_quando_tributos_e_margem_consomem_todo_o_preco(): void
    {
        $this->expectException(PrecificacaoInviavelException::class);
        $this->expectExceptionMessageMatches('/invi\x{00E1}vel/u');

        $this->servico->calcular(
            custoVariavelUnitario: 10,
            custoFixoTotal: 0,
            volumeProjetado: 1,
            tributos: $this->tributos(40.0),
            margemContribuicao: 60,
        );
    }

    #[Test]
    public function recusa_volume_projetado_zerado(): void
    {
        $this->expectException(PrecificacaoInviavelException::class);

        $this->servico->calcular(
            custoVariavelUnitario: 10,
            custoFixoTotal: 1000,
            volumeProjetado: 0,
            tributos: [],
            margemContribuicao: 20,
        );
    }

    #[Test]
    public function recusa_margem_negativa(): void
    {
        $this->expectException(PrecificacaoInviavelException::class);

        $this->servico->calcular(
            custoVariavelUnitario: 10,
            custoFixoTotal: 0,
            volumeProjetado: 1,
            tributos: [],
            margemContribuicao: -5,
        );
    }

    #[Test]
    public function alerta_quando_o_divisor_fica_critico(): void
    {
        $resultado = $this->servico->calcular(
            custoVariavelUnitario: 10,
            custoFixoTotal: 0,
            volumeProjetado: 1,
            tributos: $this->tributos(20.0),
            margemContribuicao: 70,
        );

        $titulos = array_column($resultado->alertas, 'titulo');

        $this->assertContains('Divisor muito baixo', $titulos);
    }

    #[Test]
    public function alerta_reajuste_relevante_exigindo_justa_causa(): void
    {
        $resultado = $this->servico->calcular(
            custoVariavelUnitario: 50,
            custoFixoTotal: 0,
            volumeProjetado: 1,
            tributos: [],
            margemContribuicao: 20,
            precoAnterior: 50.0,
        );

        $mensagens = implode(' ', array_column($resultado->alertas, 'mensagem'));

        $this->assertStringContainsString('art. 39, X', $mensagens);
    }

    #[Test]
    public function alerta_quando_nenhum_tributo_foi_aplicado(): void
    {
        $resultado = $this->servico->calcular(
            custoVariavelUnitario: 10,
            custoFixoTotal: 0,
            volumeProjetado: 1,
            tributos: [],
            margemContribuicao: 20,
        );

        $this->assertContains('Nenhum tributo aplicado', array_column($resultado->alertas, 'titulo'));
    }

    #[Test]
    public function o_hash_de_auditoria_muda_quando_uma_variavel_muda(): void
    {
        $base = fn (float $margem) => $this->servico->calcular(
            custoVariavelUnitario: 10,
            custoFixoTotal: 1000,
            volumeProjetado: 100,
            tributos: $this->tributos(10.0),
            margemContribuicao: $margem,
        )->hashAuditoria();

        $this->assertSame($base(20), $base(20));
        $this->assertNotSame($base(20), $base(21));
    }
}
