<?php

namespace Tests\Feature;

use App\Models\CustoFixo;
use App\Models\Empresa;
use App\Models\Item;
use App\Models\Precificacao;
use App\Models\Tributo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrecificacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Todas as telas exigem sessão autenticada.
        $this->actingAs(User::factory()->administrador()->create());
    }

    private function cenario(): Item
    {
        $empresa = Empresa::create([
            'razao_social' => 'Empresa Teste LTDA',
            'regime_tributario' => 'lucro_presumido',
            'volume_projetado_mensal' => 500,
            'ativo' => true,
        ]);

        CustoFixo::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'Aluguel',
            'grupo' => 'ocupacao',
            'valor_mensal' => 20000,
            'ativo' => true,
        ]);

        foreach ([['ICMS', 18.0, 'produto'], ['PIS', 1.65, 'ambos'], ['COFINS', 7.6, 'ambos']] as [$sigla, $aliquota, $aplica]) {
            Tributo::create([
                'empresa_id' => $empresa->id,
                'nome' => $sigla,
                'sigla' => $sigla,
                'aliquota_nominal' => $aliquota,
                'aliquota_efetiva' => $aliquota,
                'aplica_a' => $aplica,
                'ativo' => true,
            ]);
        }

        return Item::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'produto',
            'nome' => 'Produto Teste',
            'sku' => 'TST-001',
            'unidade_medida' => 'UN',
            'custo_variavel_unitario' => 30,
            'margem_contribuicao_desejada' => 20,
            'volume_projetado_mensal' => 500,
            'ativo' => true,
        ]);
    }

    #[Test]
    public function o_simulador_abre_com_o_preco_ja_calculado(): void
    {
        $this->cenario();

        $this->get(route('precificacao.simulador'))
            ->assertOk()
            ->assertSee('Simulador de preço');
    }

    #[Test]
    public function o_endpoint_de_calculo_devolve_a_memoria_completa(): void
    {
        $item = $this->cenario();

        $resposta = $this->postJson(route('precificacao.calcular'), [
            'item_id' => $item->id,
            'custo_variavel_unitario' => 30,
            'margem_contribuicao' => 20,
            'volume_projetado' => 500,
        ]);

        $resposta->assertOk()
            ->assertJsonPath('preco_venda', 132.7014)
            ->assertJsonPath('divisor', 0.5275)
            ->assertJsonCount(6, 'memoria_calculo');
    }

    #[Test]
    public function o_endpoint_recusa_margem_que_inviabiliza_o_preco(): void
    {
        $item = $this->cenario();

        $this->postJson(route('precificacao.calcular'), [
            'item_id' => $item->id,
            'custo_variavel_unitario' => 30,
            'margem_contribuicao' => 90,
            'volume_projetado' => 500,
        ])->assertStatus(422)->assertJsonStructure(['erro']);
    }

    #[Test]
    public function registrar_grava_o_snapshot_completo_para_auditoria(): void
    {
        $item = $this->cenario();

        $this->post(route('precificacao.store'), [
            'item_id' => $item->id,
            'custo_variavel_unitario' => 30,
            'margem_contribuicao' => 20,
            'volume_projetado' => 500,
            'justificativa' => 'Formação inicial de preço.',
        ])->assertRedirect();

        $registro = Precificacao::first();

        $this->assertNotNull($registro);
        $this->assertSame('Produto Teste', $registro->item_nome);
        $this->assertSame('TST-001', $registro->item_sku);
        $this->assertEqualsWithDelta(132.7014, (float) $registro->preco_venda, 0.0001);
        $this->assertCount(6, $registro->memoria_calculo);
        $this->assertCount(3, $registro->tributos_aplicados);
        $this->assertNotNull($registro->hash_auditoria);
    }

    #[Test]
    public function o_registro_de_auditoria_sobrevive_a_exclusao_do_item(): void
    {
        $item = $this->cenario();

        $this->post(route('precificacao.store'), [
            'item_id' => $item->id,
            'custo_variavel_unitario' => 30,
            'margem_contribuicao' => 20,
            'volume_projetado' => 500,
        ]);

        $item->delete();

        $registro = Precificacao::first();

        $this->assertNotNull($registro);
        $this->assertNull($registro->item_id);
        $this->assertSame('Produto Teste', $registro->item_nome);
        $this->assertEqualsWithDelta(132.7014, (float) $registro->preco_venda, 0.0001);
    }

    #[Test]
    public function a_tela_de_memoria_mostra_os_seis_passos(): void
    {
        $item = $this->cenario();

        $this->post(route('precificacao.store'), [
            'item_id' => $item->id,
            'custo_variavel_unitario' => 30,
            'margem_contribuicao' => 20,
            'volume_projetado' => 500,
        ]);

        $this->get(route('precificacao.show', Precificacao::first()))
            ->assertOk()
            ->assertSee('Rateio do custo fixo por unidade')
            ->assertSee('Prova real da decomposição')
            ->assertSee('Hash SHA-256');
    }
}
