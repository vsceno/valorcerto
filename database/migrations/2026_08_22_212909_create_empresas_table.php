<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj', 18)->nullable();

            // Define quais tributos incidem e como a alíquota efetiva é apurada.
            $table->enum('regime_tributario', [
                'simples_nacional',
                'lucro_presumido',
                'lucro_real',
                'mei',
            ])->default('simples_nacional');

            // Volume padrão usado no rateio de custos fixos quando o item não informa o seu.
            $table->decimal('volume_projetado_mensal', 15, 4)->default(1);

            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
