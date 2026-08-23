<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('precificacoes', function (Blueprint $table) {
            // Congela a ficha técnica usada, para o custo poder ser reconstruído
            // linha a linha mesmo depois de os insumos mudarem de preço.
            $table->json('composicao_aplicada')->nullable()->after('tributos_aplicados');
        });
    }

    public function down(): void
    {
        Schema::table('precificacoes', function (Blueprint $table) {
            $table->dropColumn('composicao_aplicada');
        });
    }
};
