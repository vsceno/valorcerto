<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function o_painel_orienta_o_cadastro_quando_nao_existe_empresa(): void
    {
        $this->actingAs(User::factory()->administrador()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Cadastre sua empresa para começar');
    }
}
