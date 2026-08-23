<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();

            $table->enum('tipo', ['produto', 'servico'])->default('produto');
            $table->string('nome');
            $table->string('sku', 60)->nullable();
            $table->string('unidade_medida', 20)->default('UN');
            $table->text('descricao')->nullable();

            // Custo direto por unidade (matéria-prima, insumos, comissão, frete unitário...).
            $table->decimal('custo_variavel_unitario', 15, 4)->default(0);

            // Margem de contribuição desejada em % SOBRE O PREÇO FINAL.
            $table->decimal('margem_contribuicao_desejada', 8, 4)->default(0);

            // Volume que serve de divisor no rateio dos custos fixos.
            $table->decimal('volume_projetado_mensal', 15, 4)->nullable();

            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'sku']);
            $table->index(['empresa_id', 'ativo', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens');
    }
};
