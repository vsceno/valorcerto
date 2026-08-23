<?php

namespace App\Providers;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registrarDiretivasDeFormatacao();
        $this->compartilharEmpresaAtual();
        $this->registrarPermissoes();
    }

    /**
     * Alterar a base do cálculo (tributos, custos fixos, empresa) e gerenciar
     * usuários é privativo do administrador: são as variáveis que mudam todos
     * os preços de uma vez.
     */
    private function registrarPermissoes(): void
    {
        Gate::define('administrar', fn (User $user): bool => $user->ehAdministrador());
    }

    /**
     * Formatação brasileira de moeda, percentual e número direto no Blade.
     */
    private function registrarDiretivasDeFormatacao(): void
    {
        Blade::directive('moeda', function (string $expressao): string {
            return "<?php echo 'R$ '.number_format((float) ($expressao), 2, ',', '.'); ?>";
        });

        Blade::directive('pct', function (string $expressao): string {
            return "<?php echo number_format((float) ($expressao), 2, ',', '.').'%'; ?>";
        });

        Blade::directive('num', function (string $expressao): string {
            $partes = explode(',', $expressao);
            $valor = trim($partes[0]);
            $casas = isset($partes[1]) ? trim($partes[1]) : '2';

            return "<?php echo number_format((float) ($valor), (int) ($casas), ',', '.'); ?>";
        });
    }

    /**
     * Deixa a empresa em uso disponível em todas as telas (cabeçalho, menu).
     */
    private function compartilharEmpresaAtual(): void
    {
        View::composer('*', function ($view): void {
            if (! Schema::hasTable('empresas')) {
                $view->with('empresaAtual', null);

                return;
            }

            $view->with('empresaAtual', Empresa::atual());
        });
    }
}
