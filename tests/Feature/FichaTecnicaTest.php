<?php

namespace Tests\Feature;

use App\Models\CustoFixo;
use App\Models\Empresa;
use App\Models\Insumo;
use App\Models\Item;
use App\Models\Precificacao;
use App\Models\Tributo;
use App\Models\User;
use App\Services\PrecificacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FichaTecnicaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrador()->create());

        $this->empresa = Empresa::create([
            'razao_social' => 'Metalúrgica Teste LTDA',
            'regime_tributario' => 'lucro_presumido',
            'volume_projetado_mensal' => 25,
            'ativo' => true,
        ]);
    }

    private function catraca(): Item
    {
        return Item::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'produto',
            'nome' => 'Catraca tripé',
            'sku' => 'CAT-01',
            'unidade_medida' => 'UN',
            'custo_variavel_unitario' => 0,
            'margem_contribuicao_desejada' => 25,
            'volume_projetado_mensal' => 25,
            'ativo' => true,
        ]);
    }

    /**
     * Vara de metalon de 6 m por R$ 265,00 com 8% de perda de corte.
     */
    private function metalon(): Insumo
    {
        return Insumo::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Metalon 100x40x2mm',
            'codigo' => 'MET-01',
            'grupo' => 'metalurgia',
            'unidade_compra' => 'VARA',
            'preco_compra' => 265.00,
            'rendimento' => 6,
            'unidade_uso' => 'M',
            'perda_percentual' => 8,
            'ativo' => true,
        ]);
    }

    #[Test]
    public function converte_a_unidade_de_compra_para_a_de_uso(): void
    {
        // 265 / 6 = 44,1667 por metro, antes da perda.
        $this->assertEqualsWithDelta(44.1667, $this->metalon()->custoUnitarioSemPerda(), 0.0001);
    }

    #[Test]
    public function embute_a_perda_no_custo_da_unidade_de_uso(): void
    {
        // (265 / 6) x 1,08 = 47,70 por metro aproveitado.
        $this->assertEqualsWithDelta(47.70, $this->metalon()->custoUnitarioUso(), 0.0001);
    }

    #[Test]
    public function chapa_de_dois_metros_quadrados_converte_por_area(): void
    {
        $chapa = Insumo::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Chapa 1,5mm 2m x 1m',
            'grupo' => 'metalurgia',
            'unidade_compra' => 'CHAPA',
            'preco_compra' => 245.00,
            'rendimento' => 2,
            'unidade_uso' => 'M2',
            'perda_percentual' => 12,
            'ativo' => true,
        ]);

        // 245 / 2 = 122,50 por m²; com 12% de perda -> 137,20.
        $this->assertEqualsWithDelta(137.20, $chapa->custoUnitarioUso(), 0.0001);
        $this->assertTrue($chapa->exigeConversao());
    }

    #[Test]
    public function insumo_sem_conversao_usa_o_proprio_preco(): void
    {
        $mecanismo = Insumo::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Mecanismo de giro',
            'grupo' => 'mecanica',
            'unidade_compra' => 'UN',
            'preco_compra' => 890.00,
            'rendimento' => 1,
            'unidade_uso' => 'UN',
            'perda_percentual' => 0,
            'ativo' => true,
        ]);

        $this->assertEqualsWithDelta(890.00, $mecanismo->custoUnitarioUso(), 0.0001);
        $this->assertFalse($mecanismo->exigeConversao());
    }

    #[Test]
    public function a_ficha_tecnica_soma_o_custo_de_producao(): void
    {
        $catraca = $this->catraca();

        $catraca->composicoes()->create(['insumo_id' => $this->metalon()->id, 'quantidade' => 4.2]);

        $mecanismo = Insumo::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Mecanismo',
            'grupo' => 'mecanica',
            'unidade_compra' => 'UN',
            'preco_compra' => 890.00,
            'rendimento' => 1,
            'unidade_uso' => 'UN',
            'ativo' => true,
        ]);
        $catraca->composicoes()->create(['insumo_id' => $mecanismo->id, 'quantidade' => 1]);

        $catraca->load('composicoes.insumo');

        // 4,2 m x 47,70 = 200,34; mais 890,00 do mecanismo.
        $this->assertEqualsWithDelta(1090.34, $catraca->custoDaFichaTecnica(), 0.01);
    }

    #[Test]
    public function a_ficha_tecnica_manda_no_custo_variavel_da_precificacao(): void
    {
        $catraca = $this->catraca();
        $catraca->update(['custo_variavel_unitario' => 999.99]);
        $catraca->composicoes()->create(['insumo_id' => $this->metalon()->id, 'quantidade' => 4.2]);

        $catraca->refresh()->load('composicoes.insumo');

        // O valor digitado no cadastro é ignorado quando existe ficha técnica.
        $this->assertEqualsWithDelta(200.34, $catraca->custoVariavelEfetivo(), 0.01);
    }

    #[Test]
    public function sem_ficha_tecnica_vale_o_custo_do_cadastro(): void
    {
        $catraca = $this->catraca();
        $catraca->update(['custo_variavel_unitario' => 750.00]);

        $this->assertFalse($catraca->temFichaTecnica());
        $this->assertEqualsWithDelta(750.00, $catraca->custoVariavelEfetivo(), 0.01);
    }

    #[Test]
    public function a_precificacao_congela_a_ficha_tecnica_para_auditoria(): void
    {
        CustoFixo::create([
            'empresa_id' => $this->empresa->id,
            'descricao' => 'Aluguel',
            'grupo' => 'ocupacao',
            'valor_mensal' => 28540,
            'ativo' => true,
        ]);

        Tributo::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'ICMS',
            'sigla' => 'ICMS',
            'aliquota_nominal' => 18,
            'aliquota_efetiva' => 18,
            'aplica_a' => 'produto',
            'ativo' => true,
        ]);

        $catraca = $this->catraca();
        $metalon = $this->metalon();
        $catraca->composicoes()->create(['insumo_id' => $metalon->id, 'quantidade' => 4.2]);
        $catraca->refresh();

        $servico = app(PrecificacaoService::class);
        $servico->registrar($servico->calcularParaItem($catraca), $catraca);

        $registro = Precificacao::first();
        $linha = $registro->composicao_aplicada[0];

        $this->assertCount(1, $registro->composicao_aplicada);
        $this->assertSame('Metalon 100x40x2mm', $linha['insumo']);
        $this->assertSame('VARA', $linha['unidade_compra']);
        $this->assertSame('M', $linha['unidade_uso']);
        $this->assertEqualsWithDelta(265.0, $linha['preco_compra'], 0.01);
        $this->assertEqualsWithDelta(6.0, $linha['rendimento'], 0.01);
        $this->assertEqualsWithDelta(47.70, $linha['custo_unitario_uso'], 0.0001);
        $this->assertEqualsWithDelta(200.34, $linha['custo_total'], 0.01);
    }

    #[Test]
    public function o_snapshot_nao_muda_quando_o_insumo_sobe_de_preco(): void
    {
        $catraca = $this->catraca();
        $metalon = $this->metalon();
        $catraca->composicoes()->create(['insumo_id' => $metalon->id, 'quantidade' => 4.2]);
        $catraca->refresh();

        $servico = app(PrecificacaoService::class);
        $servico->registrar($servico->calcularParaItem($catraca), $catraca);

        // O fornecedor reajusta a vara em 20%.
        $metalon->update(['preco_compra' => 318.00]);

        $registro = Precificacao::first();

        $this->assertEqualsWithDelta(265.0, $registro->composicao_aplicada[0]['preco_compra'], 0.01);
        $this->assertEqualsWithDelta(200.34, (float) $registro->custo_variavel_unitario, 0.01);
    }

    #[Test]
    public function a_tela_da_ficha_tecnica_permite_montar_e_remover_linhas(): void
    {
        $catraca = $this->catraca();
        $metalon = $this->metalon();

        $this->post(route('ficha-tecnica.store', $catraca), [
            'insumo_id' => $metalon->id,
            'quantidade' => '4,2',
            'observacao' => 'Coluna e base',
        ])->assertRedirect();

        $this->assertDatabaseCount('composicoes', 1);

        $linha = $catraca->composicoes()->first();
        $this->assertEqualsWithDelta(4.2, (float) $linha->quantidade, 0.001);

        $this->delete(route('ficha-tecnica.destroy', [$catraca, $linha]))->assertRedirect();

        $this->assertDatabaseCount('composicoes', 0);
    }

    #[Test]
    public function altera_as_quantidades_de_varias_linhas_de_uma_vez(): void
    {
        $catraca = $this->catraca();

        $chapa = Insumo::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Chapa 1,5mm',
            'grupo' => 'metalurgia',
            'unidade_compra' => 'CHAPA',
            'preco_compra' => 245.00,
            'rendimento' => 2,
            'unidade_uso' => 'M2',
            'perda_percentual' => 12,
            'ativo' => true,
        ]);

        $linhaMetalon = $catraca->composicoes()->create(['insumo_id' => $this->metalon()->id, 'quantidade' => 4.2]);
        $linhaChapa = $catraca->composicoes()->create(['insumo_id' => $chapa->id, 'quantidade' => 1.1]);

        // Metalon cai de 4,2 m para 0,8 m; chapa cai de 1,1 m² para 0,5 m².
        $this->put(route('ficha-tecnica.atualizar', $catraca), [
            'linhas' => [
                $linhaMetalon->id => ['quantidade' => '0,8', 'observacao' => 'Somente a coluna'],
                $linhaChapa->id => ['quantidade' => '0.5'],
            ],
        ])->assertRedirect();

        $catraca->refresh()->load('composicoes.insumo');

        $this->assertEqualsWithDelta(0.8, (float) $linhaMetalon->fresh()->quantidade, 0.001);
        $this->assertEqualsWithDelta(0.5, (float) $linhaChapa->fresh()->quantidade, 0.001);
        $this->assertSame('Somente a coluna', $linhaMetalon->fresh()->observacao);

        // 0,8 x 47,70 = 38,16; 0,5 x 137,20 = 68,60.
        $this->assertEqualsWithDelta(106.76, $catraca->custoDaFichaTecnica(), 0.01);
    }

    #[Test]
    public function a_edicao_em_lote_recusa_quantidade_zerada(): void
    {
        $catraca = $this->catraca();
        $linha = $catraca->composicoes()->create(['insumo_id' => $this->metalon()->id, 'quantidade' => 4.2]);

        $this->put(route('ficha-tecnica.atualizar', $catraca), [
            'linhas' => [$linha->id => ['quantidade' => '0']],
        ])->assertSessionHasErrors("linhas.{$linha->id}.quantidade");

        $this->assertEqualsWithDelta(4.2, (float) $linha->fresh()->quantidade, 0.001);
    }

    #[Test]
    public function a_edicao_em_lote_nao_altera_linhas_de_outro_item(): void
    {
        $catraca = $this->catraca();
        $metalon = $this->metalon();
        $linhaCatraca = $catraca->composicoes()->create(['insumo_id' => $metalon->id, 'quantidade' => 4.2]);

        $outro = Item::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'produto',
            'nome' => 'Portão',
            'sku' => 'PRT-01',
            'unidade_medida' => 'UN',
            'custo_variavel_unitario' => 0,
            'margem_contribuicao_desejada' => 20,
            'volume_projetado_mensal' => 10,
            'ativo' => true,
        ]);
        $linhaOutro = $outro->composicoes()->create(['insumo_id' => $metalon->id, 'quantidade' => 9]);

        // Tenta alterar, pela ficha da catraca, uma linha que pertence ao portão.
        $this->put(route('ficha-tecnica.atualizar', $catraca), [
            'linhas' => [
                $linhaCatraca->id => ['quantidade' => '0,8'],
                $linhaOutro->id => ['quantidade' => '1'],
            ],
        ])->assertRedirect();

        $this->assertEqualsWithDelta(0.8, (float) $linhaCatraca->fresh()->quantidade, 0.001);
        $this->assertEqualsWithDelta(9.0, (float) $linhaOutro->fresh()->quantidade, 0.001);
    }

    #[Test]
    public function o_mesmo_insumo_nao_entra_duas_vezes_na_ficha(): void
    {
        $catraca = $this->catraca();
        $metalon = $this->metalon();

        $this->post(route('ficha-tecnica.store', $catraca), ['insumo_id' => $metalon->id, 'quantidade' => 4.2]);

        $this->post(route('ficha-tecnica.store', $catraca), ['insumo_id' => $metalon->id, 'quantidade' => 1])
            ->assertSessionHasErrors('insumo_id');

        $this->assertDatabaseCount('composicoes', 1);
    }

    #[Test]
    public function insumo_em_uso_nao_pode_ser_excluido(): void
    {
        $catraca = $this->catraca();
        $metalon = $this->metalon();
        $catraca->composicoes()->create(['insumo_id' => $metalon->id, 'quantidade' => 4.2]);

        $this->delete(route('insumos.destroy', $metalon))->assertSessionHas('erro');

        $this->assertDatabaseHas('insumos', ['id' => $metalon->id]);
    }

    #[Test]
    public function o_rendimento_precisa_ser_maior_que_zero(): void
    {
        $this->post(route('insumos.store'), [
            'nome' => 'Insumo inválido',
            'grupo' => 'outros',
            'unidade_compra' => 'VARA',
            'preco_compra' => 100,
            'rendimento' => 0,
            'unidade_uso' => 'M',
            'perda_percentual' => 0,
            'ativo' => '1',
        ])->assertSessionHasErrors('rendimento');

        $this->assertDatabaseCount('insumos', 0);
    }

    #[Test]
    public function sincronizar_grava_o_custo_da_ficha_no_cadastro(): void
    {
        $catraca = $this->catraca();
        $catraca->update(['custo_variavel_unitario' => 0]);
        $catraca->composicoes()->create(['insumo_id' => $this->metalon()->id, 'quantidade' => 4.2]);

        $this->post(route('ficha-tecnica.sincronizar', $catraca))->assertRedirect();

        $this->assertEqualsWithDelta(200.34, (float) $catraca->fresh()->custo_variavel_unitario, 0.01);
    }
}
