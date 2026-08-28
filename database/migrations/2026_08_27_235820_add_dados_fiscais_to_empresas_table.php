<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('inscricao_estadual', 20)->nullable()->after('cnpj');
            $table->string('inscricao_municipal', 20)->nullable()->after('inscricao_estadual');
            $table->string('cnae_principal', 10)->nullable()->after('inscricao_municipal');

            // Define quais tributos fazem sentido: ICMS em mercadoria, ISS em serviço.
            $table->enum('atividade', ['comercio', 'industria', 'servicos', 'misto'])
                ->default('comercio')
                ->after('cnae_principal');

            $table->char('uf', 2)->nullable()->after('atividade');
            $table->string('municipio')->nullable()->after('uf');

            // Base para enquadrar a faixa do Simples Nacional.
            $table->decimal('faturamento_12_meses', 15, 2)->default(0)->after('municipio');

            // A partir de quando o regime atual vale, para o histórico fazer sentido.
            $table->date('regime_vigente_desde')->nullable()->after('regime_tributario');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'inscricao_estadual',
                'inscricao_municipal',
                'cnae_principal',
                'atividade',
                'uf',
                'municipio',
                'faturamento_12_meses',
                'regime_vigente_desde',
            ]);
        });
    }
};
