<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Item;
use App\Models\Tributo;
use App\Models\User;
use App\Services\PrecificacaoService;
use App\Support\RegimeTributario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReformaTributariaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrador()->create());

        $this->empresa = Empresa::create([
            'razao_social' => 'Empresa Teste LTDA',
            'regime_tributario' => 'lucro_presumido',
            'atividade' => 'comercio',
            'faturamento_12_meses' => 2000000,
            'volume_projetado_mensal' => 100,
            'ativo' => true,
        ]);
    }

    private function item(): Item
    {
        return Item::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'produto',
            'nome' => 'Produto',
            'sku' => 'P-1',
            'unidade_medida' => 'UN',
            'custo_variavel_unitario' => 100,
            'margem_contribuicao_desejada' => 20,
            'volume_projetado_mensal' => 100,
            'ativo' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function tributo(string $sigla, float $aliquota, array $extra = []): Tributo
    {
        return Tributo::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nome' => $sigla,
            'sigla' => $sigla,
            'aliquota_nominal' => $aliquota,
            'aliquota_efetiva' => $aliquota,
            'aplica_a' => 'ambos',
            'base_calculo' => 'por_dentro',
            'ativo' => true,
        ], $extra));
    }

    #[Test]
    public function tributo_por_fora_nao_entra_no_divisor(): void
    {
        $servico = new PrecificacaoService;

        $resultado = $servico->calcular(
            custoVariavelUnitario: 100,
            custoFixoTotal: 0,
            volumeProjetado: 1,
            tributos: [
                ['sigla' => 'CBS', 'nome' => 'CBS', 'aliquota_efetiva' => 8.8, 'base_calculo' => 'por_fora'],
                ['sigla' => 'IBS', 'nome' => 'IBS', 'aliquota_efetiva' => 17.7, 'base_calculo' => 'por_fora'],
            ],
            margemContribuicao: 20,
        );

        // Só a margem entra no divisor: 1 - 20% = 0,80.
        $this->assertEqualsWithDelta(0.80, $resultado->divisor, 0.0001);
        $this->assertEqualsWithDelta(0.0, $resultado->somaAliquotasEfetivas, 0.0001);
        $this->assertEqualsWithDelta(26.5, $resultado->somaAliquotasPorFora, 0.0001);
    }

    #[Test]
    public function tributo_por_fora_e_somado_ao_preco_liquido(): void
    {
        $servico = new PrecificacaoService;

        $resultado = $servico->calcular(
            custoVariavelUnitario: 100,
            custoFixoTotal: 0,
            volumeProjetado: 1,
            tributos: [['sigla' => 'CBS', 'nome' => 'CBS', 'aliquota_efetiva' => 10.0, 'base_calculo' => 'por_fora']],
            margemContribuicao: 20,
        );

        // Líquido 100 / 0,80 = 125,00; mais 10% por fora = 137,50 cobrados.
        $this->assertEqualsWithDelta(125.0, $resultado->precoVenda, 0.0001);
        $this->assertEqualsWithDelta(12.5, $resultado->valorTributosPorFora, 0.0001);
        $this->assertEqualsWithDelta(137.5, $resultado->precoFinal(), 0.0001);
    }

    #[Test]
    public function a_margem_continua_exata_sobre_o_preco_liquido(): void
    {
        $servico = new PrecificacaoService;

        $resultado = $servico->calcular(
            custoVariavelUnitario: 100,
            custoFixoTotal: 0,
            volumeProjetado: 1,
            tributos: [['sigla' => 'IBS', 'nome' => 'IBS', 'aliquota_efetiva' => 17.7, 'base_calculo' => 'por_fora']],
            margemContribuicao: 25,
        );

        // A receita da empresa é o líquido; a margem é 25% dele, não do cobrado.
        $this->assertEqualsWithDelta(
            25.0,
            ($resultado->valorMargemContribuicao / $resultado->precoVenda) * 100,
            0.0001
        );
    }

    #[Test]
    public function a_memoria_ganha_um_setimo_passo_com_tributo_por_fora(): void
    {
        $servico = new PrecificacaoService;

        $semPorFora = $servico->calcular(100, 0, 1, [
            ['sigla' => 'ICMS', 'nome' => 'ICMS', 'aliquota_efetiva' => 18.0, 'base_calculo' => 'por_dentro'],
        ], 20);

        $comPorFora = $servico->calcular(100, 0, 1, [
            ['sigla' => 'CBS', 'nome' => 'CBS', 'aliquota_efetiva' => 8.8, 'base_calculo' => 'por_fora'],
        ], 20);

        $this->assertCount(6, $semPorFora->memoriaCalculo);
        $this->assertCount(7, $comPorFora->memoriaCalculo);
        $this->assertSame('Tributos calculados por fora', $comPorFora->memoriaCalculo[6]['titulo']);
    }

    #[Test]
    public function a_vigencia_separa_o_cenario_atual_do_pos_reforma(): void
    {
        $item = $this->item();

        $this->tributo('PIS', 1.65, ['vigencia_fim' => '2026-12-31']);
        $this->tributo('CBS', 8.8, ['base_calculo' => 'por_fora', 'vigencia_inicio' => '2027-01-01']);

        $servico = new PrecificacaoService;

        $hoje = $servico->tributosDoItem($item, Carbon::parse('2026-06-01'))->pluck('sigla')->all();
        $futuro = $servico->tributosDoItem($item, Carbon::parse('2027-06-01'))->pluck('sigla')->all();

        $this->assertSame(['PIS'], $hoje);
        $this->assertSame(['CBS'], $futuro);
    }

    #[Test]
    public function comparar_cenarios_devolve_os_dois_resultados(): void
    {
        $item = $this->item();

        $this->tributo('PIS', 10.0, ['vigencia_fim' => '2026-12-31']);
        $this->tributo('CBS', 10.0, ['base_calculo' => 'por_fora', 'vigencia_inicio' => '2027-01-01']);

        $cenarios = (new PrecificacaoService)->compararCenarios($item, Carbon::parse('2027-01-01'));

        // Atual: 10% por dentro -> divisor 0,70.
        $this->assertEqualsWithDelta(0.70, $cenarios['atual']->divisor, 0.0001);
        $this->assertFalse($cenarios['atual']->temTributosPorFora());

        // Futuro: nada por dentro -> divisor 0,80, e 10% somados depois.
        $this->assertEqualsWithDelta(0.80, $cenarios['futuro']->divisor, 0.0001);
        $this->assertTrue($cenarios['futuro']->temTributosPorFora());
    }

    #[Test]
    public function o_regime_filtra_os_tributos_aplicados(): void
    {
        $item = $this->item();

        $this->tributo('ICMS', 18.0, ['regimes' => ['lucro_presumido', 'lucro_real']]);
        $this->tributo('DAS', 6.0, ['regimes' => ['simples_nacional']]);

        $servico = new PrecificacaoService;

        $this->assertSame(['ICMS'], $servico->tributosDoItem($item)->pluck('sigla')->all());

        $this->empresa->update(['regime_tributario' => 'simples_nacional']);
        $item->refresh()->load('empresa');

        $this->assertSame(['DAS'], $servico->tributosDoItem($item)->pluck('sigla')->all());
    }

    #[Test]
    public function o_simples_nacional_sugere_apenas_o_das(): void
    {
        $sugeridos = RegimeTributario::tributosSugeridos('simples_nacional', 'comercio');

        $this->assertCount(1, $sugeridos);
        $this->assertSame('DAS', $sugeridos[0]['sigla']);
    }

    #[Test]
    public function o_mei_nao_tem_aliquota_sobre_receita(): void
    {
        $this->assertSame([], RegimeTributario::tributosSugeridos('mei', 'servicos'));
    }

    #[Test]
    public function comercio_recebe_icms_e_servico_recebe_iss(): void
    {
        $comercio = array_column(RegimeTributario::tributosSugeridos('lucro_presumido', 'comercio'), 'sigla');
        $servicos = array_column(RegimeTributario::tributosSugeridos('lucro_presumido', 'servicos'), 'sigla');

        $this->assertContains('ICMS', $comercio);
        $this->assertNotContains('ISS', $comercio);

        $this->assertContains('ISS', $servicos);
        $this->assertNotContains('ICMS', $servicos);
    }

    #[Test]
    public function o_ipi_da_industria_e_calculado_por_fora(): void
    {
        $industria = collect(RegimeTributario::tributosSugeridos('lucro_presumido', 'industria'));
        $ipi = $industria->firstWhere('sigla', 'IPI');

        $this->assertNotNull($ipi);
        $this->assertSame('por_fora', $ipi['base_calculo']);
    }

    #[Test]
    public function aponta_tributo_incompativel_com_o_regime(): void
    {
        $this->empresa->update(['regime_tributario' => 'simples_nacional', 'atividade' => 'comercio']);
        $this->tributo('ICMS', 18.0);

        $this->assertContains('ICMS', $this->empresa->fresh()->tributosIncompativeis());
    }

    #[Test]
    public function cbs_e_ibs_nao_sao_apontados_como_incompativeis(): void
    {
        $this->tributo('CBS', 8.8, ['base_calculo' => 'por_fora', 'vigencia_inicio' => '2020-01-01']);

        $this->assertNotContains('CBS', $this->empresa->fresh()->tributosIncompativeis());
    }
}
