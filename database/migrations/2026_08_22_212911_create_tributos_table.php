<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tributos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome');
            $table->string('sigla', 20);

            // Alíquota de tabela, guardada apenas para comparação e auditoria.
            $table->decimal('aliquota_nominal', 8, 4)->default(0);

            // Alíquota EFETIVA (já líquida de créditos/reduções de base) - é ela que entra na fórmula.
            $table->decimal('aliquota_efetiva', 8, 4)->default(0);

            // Produto e serviço têm incidências diferentes (ICMS x ISS).
            $table->enum('aplica_a', ['produto', 'servico', 'ambos'])->default('ambos');

            $table->string('base_legal')->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'ativo', 'aplica_a']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tributos');
    }
};
