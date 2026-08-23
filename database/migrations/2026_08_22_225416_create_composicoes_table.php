<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ficha técnica: quanto de cada insumo entra em uma unidade do produto.
     * A quantidade é sempre expressa na unidade de USO do insumo.
     */
    public function up(): void
    {
        Schema::create('composicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('itens')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();

            $table->decimal('quantidade', 15, 4)->default(0);
            $table->string('observacao')->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['item_id', 'insumo_id']);
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composicoes');
    }
};
