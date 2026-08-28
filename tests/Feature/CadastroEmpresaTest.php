<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Tributo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CadastroEmpresaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrador()->create());
    }

    /**
     * @return array<string, mixed>
     */
    private function dados(array $extra = []): array
    {
        return array_merge([
            'razao_social' => 'Metalúrgica Alfa LTDA',
            'nome_fantasia' => 'Alfa',
            'cnpj' => '11.222.333/0001-44',
            'atividade' => 'industria',
            'uf' => 'sp',
            'municipio' => 'Campinas',
            'faturamento_12_meses' => '1.800.000,00',
            'regime_tributario' => 'lucro_presumido',
            'volume_projetado_mensal' => 250,
            'ativo' => '1',
        ], $extra);
    }

    #[Test]
    public function cadastra_uma_empresa_com_enquadramento(): void
    {
        $this->post(route('empresa.store'), $this->dados())->assertRedirect();

        $empresa = Empresa::query()->where('cnpj', '11.222.333/0001-44')->first();

        $this->assertNotNull($empresa);
        $this->assertSame('lucro_presumido', $empresa->regime_tributario);
        $this->assertSame('industria', $empresa->atividade);
        $this->assertSame('SP', $empresa->uf);
        $this->assertEqualsWithDelta(1800000.0, (float) $empresa->faturamento_12_meses, 0.01);
    }

    #[Test]
    public function o_mei_nao_aceita_faturamento_acima_do_limite(): void
    {
        $this->post(route('empresa.store'), $this->dados([
            'regime_tributario' => 'mei',
            'faturamento_12_meses' => '200000',
        ]))->assertSessionHasErrors('regime_tributario');

        $this->assertDatabaseCount('empresas', 0);
    }

    #[Test]
    public function o_simples_nao_aceita_faturamento_acima_do_limite(): void
    {
        $this->post(route('empresa.store'), $this->dados([
            'regime_tributario' => 'simples_nacional',
            'faturamento_12_meses' => '6000000',
        ]))->assertSessionHasErrors('regime_tributario');
    }

    #[Test]
    public function o_cnpj_nao_se_repete(): void
    {
        $this->post(route('empresa.store'), $this->dados());

        $this->post(route('empresa.store'), $this->dados(['razao_social' => 'Outra LTDA']))
            ->assertSessionHasErrors('cnpj');

        $this->assertDatabaseCount('empresas', 1);
    }

    #[Test]
    public function aplicar_sugestao_cria_os_tributos_do_regime(): void
    {
        $this->post(route('empresa.store'), $this->dados());
        $empresa = Empresa::first();

        $this->post(route('empresa.aplicar-sugestao', $empresa))->assertRedirect(route('tributos.index'));

        $siglas = $empresa->tributos()->pluck('sigla')->all();

        // Indústria no Lucro Presumido: PIS, COFINS, IRPJ, CSLL, ICMS e IPI.
        $this->assertContains('ICMS', $siglas);
        $this->assertContains('IPI', $siglas);
        $this->assertContains('COFINS', $siglas);
        $this->assertNotContains('ISS', $siglas);
    }

    #[Test]
    public function aplicar_sugestao_preserva_aliquotas_ja_ajustadas(): void
    {
        $this->post(route('empresa.store'), $this->dados());
        $empresa = Empresa::first();

        Tributo::create([
            'empresa_id' => $empresa->id,
            'nome' => 'ICMS',
            'sigla' => 'ICMS',
            'aliquota_nominal' => 18,
            'aliquota_efetiva' => 7.5,
            'aplica_a' => 'produto',
            'ativo' => true,
        ]);

        $this->post(route('empresa.aplicar-sugestao', $empresa));

        $icms = $empresa->tributos()->where('sigla', 'ICMS')->get();

        $this->assertCount(1, $icms);
        $this->assertEqualsWithDelta(7.5, (float) $icms->first()->aliquota_efetiva, 0.01);
    }

    #[Test]
    public function selecionar_troca_a_empresa_em_uso(): void
    {
        $primeira = Empresa::create([
            'razao_social' => 'Primeira LTDA',
            'regime_tributario' => 'lucro_real',
            'atividade' => 'comercio',
            'volume_projetado_mensal' => 10,
            'ativo' => true,
        ]);

        $segunda = Empresa::create([
            'razao_social' => 'Segunda LTDA',
            'regime_tributario' => 'simples_nacional',
            'atividade' => 'servicos',
            'volume_projetado_mensal' => 10,
            'ativo' => true,
        ]);

        $this->assertSame($primeira->id, Empresa::atual()->id);

        $this->post(route('empresa.selecionar', $segunda))->assertRedirect();

        $this->assertSame($segunda->id, session('empresa_id'));
    }

    #[Test]
    public function empresa_inativa_nao_pode_ser_selecionada(): void
    {
        Empresa::create([
            'razao_social' => 'Ativa LTDA',
            'regime_tributario' => 'lucro_real',
            'atividade' => 'comercio',
            'volume_projetado_mensal' => 10,
            'ativo' => true,
        ]);

        $inativa = Empresa::create([
            'razao_social' => 'Inativa LTDA',
            'regime_tributario' => 'lucro_real',
            'atividade' => 'comercio',
            'volume_projetado_mensal' => 10,
            'ativo' => false,
        ]);

        $this->post(route('empresa.selecionar', $inativa))->assertForbidden();
    }

    #[Test]
    public function a_unica_empresa_ativa_nao_pode_ser_excluida(): void
    {
        $this->post(route('empresa.store'), $this->dados());
        $empresa = Empresa::first();

        $this->delete(route('empresa.destroy', $empresa))->assertSessionHas('erro');

        $this->assertDatabaseCount('empresas', 1);
    }
}
