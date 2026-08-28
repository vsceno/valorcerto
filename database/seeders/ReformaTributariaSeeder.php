<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Tributo;
use App\Support\ReformaTributaria;
use Illuminate\Database\Seeder;

/**
 * Cadastra CBS e IBS com vigência futura, para o sistema poder comparar o preço
 * no modelo atual com o preço pós-reforma.
 *
 * Também encerra a vigência dos tributos que a reforma substitui, na data em
 * que cada um deixa de existir — assim os dois cenários não se somam.
 */
class ReformaTributariaSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::atual();

        if (! $empresa) {
            $this->command?->warn('Nenhuma empresa cadastrada. Rode o DatabaseSeeder primeiro.');

            return;
        }

        $this->encerrarTributosSubstituidos($empresa);
        $this->cadastrarCbsIbs($empresa);

        $this->command?->info(sprintf(
            'Reforma cadastrada: CBS %s%% e IBS %s%% a partir de 01/01/%d (estimativas).',
            number_format(ReformaTributaria::CBS_REFERENCIA, 2, ',', '.'),
            number_format(ReformaTributaria::IBS_REFERENCIA, 2, ',', '.'),
            ReformaTributaria::ANO_CBS_INTEGRAL
        ));
    }

    /**
     * PIS e COFINS são extintos ao fim de 2026; ICMS e ISS, ao fim de 2032.
     */
    private function encerrarTributosSubstituidos(Empresa $empresa): void
    {
        $encerramentos = [
            'PIS' => '2026-12-31',
            'COFINS' => '2026-12-31',
            'ICMS' => '2032-12-31',
            'ISS' => '2032-12-31',
        ];

        foreach ($encerramentos as $sigla => $data) {
            $empresa->tributos()
                ->where('sigla', $sigla)
                ->whereNull('vigencia_fim')
                ->update(['vigencia_fim' => $data]);
        }
    }

    private function cadastrarCbsIbs(Empresa $empresa): void
    {
        foreach (ReformaTributaria::tributosSugeridos() as $tributo) {
            Tributo::query()->updateOrCreate(
                ['empresa_id' => $empresa->id, 'sigla' => $tributo['sigla']],
                $tributo + ['ativo' => true],
            );
        }
    }
}
