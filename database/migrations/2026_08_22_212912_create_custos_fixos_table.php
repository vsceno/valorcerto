<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custos_fixos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('descricao');

            $table->enum('grupo', [
                'ocupacao',
                'pessoal',
                'administrativo',
                'comercial',
                'financeiro',
                'outros',
            ])->default('outros');

            $table->decimal('valor_mensal', 15, 2)->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custos_fixos');
    }
};
