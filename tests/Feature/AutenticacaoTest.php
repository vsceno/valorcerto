<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Item;
use App\Models\Precificacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_tela_de_login_e_publica(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Entrar');
    }

    #[Test]
    public function visitante_e_redirecionado_para_o_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('precificacao.simulador'))->assertRedirect(route('login'));
        $this->get(route('itens.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function usuario_ativo_entra_com_credenciais_validas(): void
    {
        $usuario = User::factory()->create(['email' => 'teste@valorcerto.test']);

        $this->post(route('login'), [
            'email' => 'teste@valorcerto.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($usuario);
        $this->assertNotNull($usuario->fresh()->ultimo_acesso_em);
    }

    #[Test]
    public function senha_incorreta_e_recusada(): void
    {
        User::factory()->create(['email' => 'teste@valorcerto.test']);

        $this->post(route('login'), [
            'email' => 'teste@valorcerto.test',
            'password' => 'senha-errada',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function usuario_inativo_nao_consegue_entrar(): void
    {
        User::factory()->inativo()->create(['email' => 'desligado@valorcerto.test']);

        $this->post(route('login'), [
            'email' => 'desligado@valorcerto.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function o_logout_encerra_a_sessao(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    #[Test]
    public function operador_nao_acessa_a_base_do_calculo(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('tributos.index'))->assertForbidden();
        $this->get(route('custos-fixos.index'))->assertForbidden();
        $this->get(route('empresa.edit'))->assertForbidden();
        $this->get(route('usuarios.index'))->assertForbidden();
    }

    #[Test]
    public function operador_acessa_a_operacao_de_precos(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('precificacao.simulador'))->assertOk();
        $this->get(route('itens.index'))->assertOk();
        $this->get(route('categorias.index'))->assertOk();
        $this->get(route('perfil.edit'))->assertOk();
    }

    #[Test]
    public function administrador_acessa_tudo(): void
    {
        $this->actingAs(User::factory()->administrador()->create());

        $this->get(route('tributos.index'))->assertOk();
        $this->get(route('custos-fixos.index'))->assertOk();
        $this->get(route('empresa.edit'))->assertOk();
        $this->get(route('usuarios.index'))->assertOk();
    }

    #[Test]
    public function administrador_cadastra_um_novo_usuario(): void
    {
        $this->actingAs(User::factory()->administrador()->create());

        $this->post(route('usuarios.store'), [
            'name' => 'Maria Souza',
            'email' => 'maria@valorcerto.test',
            'perfil' => 'operador',
            'password' => 'segura1234',
            'password_confirmation' => 'segura1234',
            'ativo' => '1',
        ])->assertRedirect(route('usuarios.index'));

        $novo = User::query()->where('email', 'maria@valorcerto.test')->first();

        $this->assertNotNull($novo);
        $this->assertSame('operador', $novo->perfil);
        $this->assertTrue($novo->ativo);
        $this->assertTrue(Auth::validate(['email' => 'maria@valorcerto.test', 'password' => 'segura1234']));
    }

    #[Test]
    public function a_senha_precisa_de_letras_numeros_e_confirmacao(): void
    {
        $this->actingAs(User::factory()->administrador()->create());

        $this->post(route('usuarios.store'), [
            'name' => 'Fraca',
            'email' => 'fraca@valorcerto.test',
            'perfil' => 'operador',
            'password' => 'abc',
            'password_confirmation' => 'outra',
            'ativo' => '1',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'fraca@valorcerto.test']);
    }

    #[Test]
    public function editar_usuario_sem_informar_senha_mantem_a_atual(): void
    {
        $this->actingAs(User::factory()->administrador()->create());

        $usuario = User::factory()->create(['email' => 'antigo@valorcerto.test']);
        $hashOriginal = $usuario->password;

        $this->put(route('usuarios.update', $usuario), [
            'name' => 'Nome Alterado',
            'email' => 'antigo@valorcerto.test',
            'perfil' => 'operador',
            'ativo' => '1',
        ])->assertRedirect(route('usuarios.index'));

        $usuario->refresh();

        $this->assertSame('Nome Alterado', $usuario->name);
        $this->assertSame($hashOriginal, $usuario->password);
    }

    #[Test]
    public function administrador_nao_pode_remover_o_proprio_acesso(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->put(route('usuarios.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'perfil' => 'operador',
            'ativo' => '1',
        ])->assertSessionHasErrors('perfil');

        $this->assertSame('administrador', $admin->fresh()->perfil);
    }

    #[Test]
    public function administrador_nao_pode_desativar_o_proprio_usuario(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->put(route('usuarios.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'perfil' => 'administrador',
            'ativo' => '0',
        ])->assertSessionHasErrors('ativo');

        $this->assertTrue($admin->fresh()->ativo);
    }

    #[Test]
    public function nao_e_possivel_excluir_o_proprio_usuario(): void
    {
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $admin))
            ->assertSessionHas('erro');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    #[Test]
    public function excluir_outro_administrador_sempre_deixa_um_ativo(): void
    {
        $admin = User::factory()->administrador()->create();
        $outroAdmin = User::factory()->administrador()->create();

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $outroAdmin))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseMissing('users', ['id' => $outroAdmin->id]);

        // Quem executou a exclusão continua administrador ativo: o sistema
        // nunca fica sem alguém capaz de administrá-lo.
        $this->assertSame(1, User::query()->ativos()->where('perfil', 'administrador')->count());
    }

    #[Test]
    public function o_usuario_troca_a_propria_senha_informando_a_atual(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->put(route('perfil.update'), [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'senha_atual' => 'password',
            'password' => 'novasenha123',
            'password_confirmation' => 'novasenha123',
        ])->assertRedirect(route('perfil.edit'));

        $this->assertTrue(Auth::validate(['email' => $usuario->email, 'password' => 'novasenha123']));
    }

    #[Test]
    public function a_troca_de_senha_exige_a_senha_atual_correta(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->put(route('perfil.update'), [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'senha_atual' => 'errada',
            'password' => 'novasenha123',
            'password_confirmation' => 'novasenha123',
        ])->assertSessionHasErrors('senha_atual');

        $this->assertTrue(Auth::validate(['email' => $usuario->email, 'password' => 'password']));
    }

    #[Test]
    public function a_precificacao_registra_quem_a_criou(): void
    {
        $usuario = User::factory()->administrador()->create();

        $this->actingAs($usuario);

        $empresa = Empresa::create([
            'razao_social' => 'Teste LTDA',
            'regime_tributario' => 'simples_nacional',
            'volume_projetado_mensal' => 100,
            'ativo' => true,
        ]);

        $item = Item::create([
            'empresa_id' => $empresa->id,
            'tipo' => 'produto',
            'nome' => 'Item',
            'unidade_medida' => 'UN',
            'custo_variavel_unitario' => 10,
            'margem_contribuicao_desejada' => 20,
            'volume_projetado_mensal' => 100,
            'ativo' => true,
        ]);

        $this->post(route('precificacao.store'), [
            'item_id' => $item->id,
            'custo_variavel_unitario' => 10,
            'margem_contribuicao' => 20,
            'volume_projetado' => 100,
        ]);

        $this->assertSame($usuario->id, Precificacao::first()->user_id);
    }
}
