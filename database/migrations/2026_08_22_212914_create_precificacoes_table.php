<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro imutável de auditoria: guarda o preço e TODAS as variáveis que o
     * produziram, para que o cálculo possa ser justificado mesmo que os
     * cadastros de origem mudem depois.
     */
    public function up(): void
    {
        Schema::create('precificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('itens')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Snapshot da identificação, para o registro sobreviver à exclusão do item.
            $table->string('item_nome');
            $table->string('item_sku', 60)->nullable();
            $table->enum('item_tipo', ['produto', 'servico'])->default('produto');

            // --- Entradas da fórmula ---
            $table->decimal('custo_variavel_unitario', 15, 4);
            $table->decimal('custo_fixo_total', 15, 2);
            $table->decimal('volume_projetado', 15, 4);
            $table->decimal('rateio_fixo_unitario', 15, 4);
            $table->decimal('custo_total_unitario', 15, 4);
            $table->decimal('soma_aliquotas_efetivas', 8, 4);
            $table->decimal('margem_contribuicao', 8, 4);

            // --- Resultado ---
            $table->decimal('divisor', 12, 8);
            $table->decimal('preco_venda', 15, 4);
            $table->decimal('valor_tributos', 15, 4);
            $table->decimal('valor_margem_contribuicao', 15, 4);
            $table->decimal('markup', 12, 6)->default(0);

            // --- Rastro legal ---
            $table->json('memoria_calculo');
            $table->json('tributos_aplicados');
            $table->text('justificativa')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('hash_auditoria', 64)->nullable();
            $table->timestamp('calculado_em')->useCurrent();
            $table->timestamps();

            $table->index(['empresa_id', 'calculado_em']);
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precificacoes');
    }
};
