<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Matéria-prima e serviços comprados. O ponto central é separar a UNIDADE
     * DE COMPRA (vara de 6 m, chapa de 2 m², rolo de 100 m) da UNIDADE DE USO
     * (metro, metro quadrado), porque é na unidade de uso que o produto
     * consome o insumo.
     */
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('nome');
            $table->string('codigo', 60)->nullable();
            $table->string('fornecedor')->nullable();

            $table->enum('grupo', [
                'metalurgia',
                'eletronica',
                'mecanica',
                'acabamento',
                'fixacao',
                'mao_de_obra',
                'outros',
            ])->default('outros');

            // Como você compra: VARA, CHAPA, ROLO, KG, UN, HR...
            $table->string('unidade_compra', 20)->default('UN');
            $table->decimal('preco_compra', 15, 4)->default(0);

            // Quantas unidades de uso saem de uma unidade de compra.
            // Vara de 6 m -> rendimento 6, unidade de uso M.
            $table->decimal('rendimento', 15, 4)->default(1);
            $table->string('unidade_uso', 20)->default('UN');

            // Perda de corte, refugo e sobra que não vira produto.
            $table->decimal('perda_percentual', 8, 4)->default(0);

            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
            $table->index(['empresa_id', 'ativo', 'grupo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
