<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Insumo;
use App\Models\Item;
use Illuminate\Database\Seeder;

/**
 * Exemplo completo de produto fabricado: uma catraca de acesso tipo tripé.
 *
 * Mostra os três casos de conversão que aparecem em fabricação:
 *   - compra por VARA de 6 m, consumo em METROS;
 *   - compra por CHAPA de 2 m x 1 m, consumo em METROS QUADRADOS;
 *   - compra e consumo na mesma unidade (componentes, mão de obra).
 */
class CatracaSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::atual();

        if (! $empresa) {
            $this->command?->warn('Nenhuma empresa cadastrada. Rode o DatabaseSeeder primeiro.');

            return;
        }

        $categoria = Categoria::query()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'Controle de acesso'],
            ['descricao' => 'Catracas, torniquetes e portas de acesso', 'ativo' => true],
        );

        $insumos = $this->insumos($empresa);

        $catraca = Item::query()->updateOrCreate(
            ['empresa_id' => $empresa->id, 'sku' => 'CAT-TRP-01'],
            [
                'categoria_id' => $categoria->id,
                'tipo' => 'produto',
                'nome' => 'Catraca de acesso tripé com biometria',
                'unidade_medida' => 'UN',
                'descricao' => 'Catraca tipo tripé em estrutura de metalon 100x40x2mm com fechamento em '
                    .'chapa de aço 1,5mm, mecanismo de giro com solenoide, três braços em inox, '
                    .'controladora com leitor biométrico e RFID.',
                // Referência: será substituído pelo custo apurado na ficha técnica.
                'custo_variavel_unitario' => 0,
                'margem_contribuicao_desejada' => 25,
                'volume_projetado_mensal' => 25,
                'ativo' => true,
            ],
        );

        $this->fichaTecnica($catraca, $insumos);

        // Alinha o custo do cadastro ao valor apurado na ficha.
        $catraca->load('composicoes.insumo');
        $catraca->update(['custo_variavel_unitario' => round($catraca->custoDaFichaTecnica(), 4)]);

        $this->command?->info(sprintf(
            'Catraca cadastrada. Custo de produção apurado: R$ %s',
            number_format($catraca->custoDaFichaTecnica(), 2, ',', '.')
        ));
    }

    /**
     * @return array<string, Insumo>
     */
    private function insumos(Empresa $empresa): array
    {
        // [código, nome, grupo, un. compra, preço, rendimento, un. uso, perda %, fornecedor]
        $catalogo = [
            ['MET-10040', 'Metalon 100x40x2mm - barra 6 m', 'metalurgia', 'VARA', 265.00, 6, 'M', 8.0, 'Aço & Perfis Ltda'],
            ['CHP-15', 'Chapa de aço 1,5mm - 2m x 1m', 'metalurgia', 'CHAPA', 245.00, 2, 'M2', 12.0, 'Aço & Perfis Ltda'],
            ['MEC-TRP', 'Mecanismo de giro tripé com solenoide', 'mecanica', 'UN', 890.00, 1, 'UN', 0, 'MecanTurn'],
            ['BRC-INX', 'Braço em inox 1.1/2" polido', 'mecanica', 'UN', 118.00, 1, 'UN', 0, 'MecanTurn'],
            ['ROL-608', 'Rolamento blindado', 'mecanica', 'UN', 22.00, 1, 'UN', 0, 'Rolamentos Sul'],
            ['ELE-CTRL', 'Placa controladora de acesso', 'eletronica', 'UN', 340.00, 1, 'UN', 0, 'TecAcesso'],
            ['ELE-BIO', 'Leitor biométrico com RFID', 'eletronica', 'UN', 420.00, 1, 'UN', 0, 'TecAcesso'],
            ['ELE-DISP', 'Display LCD com teclado', 'eletronica', 'UN', 165.00, 1, 'UN', 0, 'TecAcesso'],
            ['ELE-FONT', 'Fonte chaveada 12V 5A', 'eletronica', 'UN', 95.00, 1, 'UN', 0, 'TecAcesso'],
            ['ELE-SENS', 'Sensor óptico de posição', 'eletronica', 'UN', 28.00, 1, 'UN', 2.0, 'TecAcesso'],
            ['ELE-CABO', 'Cabo de sinal blindado - rolo 100 m', 'eletronica', 'ROLO', 210.00, 100, 'M', 5.0, 'EletroCabos'],
            ['ACB-TINT', 'Tinta epóxi em pó', 'acabamento', 'KG', 62.00, 1, 'KG', 15.0, 'PinturaPro'],
            ['ACB-SOLD', 'Solda MIG (arame + gás)', 'acabamento', 'KG', 48.00, 1, 'KG', 10.0, 'Soldas RS'],
            ['FIX-KIT', 'Kit de parafusos e fixadores', 'fixacao', 'KIT', 34.00, 1, 'KIT', 0, 'Fixa Bem'],
            ['MDO-MONT', 'Mão de obra de montagem', 'mao_de_obra', 'HR', 38.00, 1, 'HR', 0, null],
        ];

        $insumos = [];

        foreach ($catalogo as [$codigo, $nome, $grupo, $unCompra, $preco, $rendimento, $unUso, $perda, $fornecedor]) {
            $insumos[$codigo] = Insumo::query()->updateOrCreate(
                ['empresa_id' => $empresa->id, 'codigo' => $codigo],
                [
                    'nome' => $nome,
                    'grupo' => $grupo,
                    'unidade_compra' => $unCompra,
                    'preco_compra' => $preco,
                    'rendimento' => $rendimento,
                    'unidade_uso' => $unUso,
                    'perda_percentual' => $perda,
                    'fornecedor' => $fornecedor,
                    'ativo' => true,
                ],
            );
        }

        return $insumos;
    }

    /**
     * @param  array<string, Insumo>  $insumos
     */
    private function fichaTecnica(Item $catraca, array $insumos): void
    {
        // [código do insumo, quantidade consumida por catraca, observação]
        $ficha = [
            ['MET-10040', 4.2, 'Coluna, base e travessas'],
            ['CHP-15', 1.1, 'Fechamento do gabinete e tampas'],
            ['MEC-TRP', 1, 'Conjunto de giro com amortecimento'],
            ['BRC-INX', 3, 'Três braços do tripé'],
            ['ROL-608', 2, 'Eixo do mecanismo'],
            ['ELE-CTRL', 1, null],
            ['ELE-BIO', 1, null],
            ['ELE-DISP', 1, null],
            ['ELE-FONT', 1, null],
            ['ELE-SENS', 3, 'Detecção das três posições do tripé'],
            ['ELE-CABO', 6, 'Chicote interno'],
            ['ACB-TINT', 0.45, 'Pintura eletrostática'],
            ['ACB-SOLD', 0.8, 'Solda da estrutura'],
            ['FIX-KIT', 1, null],
            ['MDO-MONT', 6.5, 'Corte, solda, pintura, montagem e teste'],
        ];

        foreach ($ficha as $ordem => [$codigo, $quantidade, $observacao]) {
            if (! isset($insumos[$codigo])) {
                continue;
            }

            $catraca->composicoes()->updateOrCreate(
                ['insumo_id' => $insumos[$codigo]->id],
                ['quantidade' => $quantidade, 'observacao' => $observacao, 'ordem' => $ordem + 1],
            );
        }
    }
}
