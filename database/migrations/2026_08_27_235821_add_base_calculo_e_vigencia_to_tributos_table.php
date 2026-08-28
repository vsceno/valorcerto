<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A reforma tributária (EC 132/2023) muda a mecânica do cálculo, não só as
     * alíquotas: CBS e IBS são apurados POR FORA (somados ao preço), enquanto
     * ICMS, PIS, COFINS e ISS são POR DENTRO (embutidos no preço). Sem essa
     * distinção o preço fica errado a partir da transição.
     */
    public function up(): void
    {
        Schema::table('tributos', function (Blueprint $table) {
            $table->enum('base_calculo', ['por_dentro', 'por_fora'])
                ->default('por_dentro')
                ->after('aliquota_efetiva');

            // Permite os cenários atual e pós-reforma conviverem no cadastro.
            $table->date('vigencia_inicio')->nullable()->after('base_calculo');
            $table->date('vigencia_fim')->nullable()->after('vigencia_inicio');

            // Regimes em que o tributo incide; nulo significa todos.
            $table->json('regimes')->nullable()->after('aplica_a');

            $table->index(['empresa_id', 'vigencia_inicio', 'vigencia_fim']);
        });
    }

    public function down(): void
    {
        Schema::table('tributos', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'vigencia_inicio', 'vigencia_fim']);
            $table->dropColumn(['base_calculo', 'vigencia_inicio', 'vigencia_fim', 'regimes']);
        });
    }
};
