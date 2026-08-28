<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\CustoFixo;
use App\Models\Empresa;
use App\Models\Item;
use App\Models\Tributo;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // updateOrCreate para o seeder ser determinístico: reexecutar sempre
        // devolve os acessos de referência a um estado conhecido.
        User::query()->updateOrCreate(
            ['email' => 'admin@valorcerto.test'],
            [
                'name' => 'Administrador do Sistema',
                'password' => 'admin1234',
                'perfil' => 'administrador',
                'ativo' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'operador@valorcerto.test'],
            [
                'name' => 'Operador de Preços',
                'password' => 'operador1234',
                'perfil' => 'operador',
                'ativo' => true,
            ],
        );

        $empresa = Empresa::query()->firstOrCreate(
            ['cnpj' => '12.345.678/0001-90'],
            [
                'razao_social' => 'ValorCerto Comércio e Serviços LTDA',
                'nome_fantasia' => 'ValorCerto',
                'regime_tributario' => 'lucro_presumido',
                'atividade' => 'misto',
                'uf' => 'SP',
                'municipio' => 'São Paulo',
                'faturamento_12_meses' => 2400000,
                'regime_vigente_desde' => '2026-01-01',
                'volume_projetado_mensal' => 500,
                'ativo' => true,
            ],
        );

        $this->tributos($empresa);
        $this->custosFixos($empresa);
        $this->itens($empresa);

        $this->call(CatracaSeeder::class);
        $this->call(ReformaTributariaSeeder::class);
    }

    /**
     * Alíquotas efetivas de referência para Lucro Presumido (comércio/serviço).
     * A efetiva vem menor que a nominal por créditos e reduções de base.
     */
    private function tributos(Empresa $empresa): void
    {
        $tributos = [
            ['nome' => 'ICMS', 'sigla' => 'ICMS', 'nominal' => 18.0, 'efetiva' => 11.5, 'aplica' => 'produto', 'base' => 'LC 87/1996 (Lei Kandir) - alíquota interna estadual, líquida de créditos'],
            ['nome' => 'PIS sobre faturamento', 'sigla' => 'PIS', 'nominal' => 1.65, 'efetiva' => 1.2, 'aplica' => 'ambos', 'base' => 'Lei 10.637/2002 - regime não cumulativo'],
            ['nome' => 'COFINS sobre faturamento', 'sigla' => 'COFINS', 'nominal' => 7.6, 'efetiva' => 5.4, 'aplica' => 'ambos', 'base' => 'Lei 10.833/2003 - regime não cumulativo'],
            ['nome' => 'ISS sobre serviços', 'sigla' => 'ISS', 'nominal' => 5.0, 'efetiva' => 5.0, 'aplica' => 'servico', 'base' => 'LC 116/2003 - alíquota municipal'],
            ['nome' => 'IRPJ sobre presumido', 'sigla' => 'IRPJ', 'nominal' => 15.0, 'efetiva' => 1.2, 'aplica' => 'ambos', 'base' => 'Lei 9.430/1996 - 15% sobre base presumida de 8%'],
            ['nome' => 'CSLL sobre presumido', 'sigla' => 'CSLL', 'nominal' => 9.0, 'efetiva' => 1.08, 'aplica' => 'ambos', 'base' => 'Lei 7.689/1988 - 9% sobre base presumida de 12%'],
        ];

        foreach ($tributos as $t) {
            Tributo::query()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'sigla' => $t['sigla']],
                [
                    'nome' => $t['nome'],
                    'aliquota_nominal' => $t['nominal'],
                    'aliquota_efetiva' => $t['efetiva'],
                    'aplica_a' => $t['aplica'],
                    'base_legal' => $t['base'],
                    'ativo' => true,
                ],
            );
        }
    }

    private function custosFixos(Empresa $empresa): void
    {
        $custos = [
            ['descricao' => 'Aluguel do ponto comercial', 'grupo' => 'ocupacao', 'valor' => 4500.00],
            ['descricao' => 'Condomínio e IPTU', 'grupo' => 'ocupacao', 'valor' => 780.00],
            ['descricao' => 'Folha de pagamento e encargos', 'grupo' => 'pessoal', 'valor' => 12400.00],
            ['descricao' => 'Pró-labore dos sócios', 'grupo' => 'pessoal', 'valor' => 6000.00],
            ['descricao' => 'Honorários contábeis', 'grupo' => 'administrativo', 'valor' => 950.00],
            ['descricao' => 'Energia elétrica e água', 'grupo' => 'administrativo', 'valor' => 1150.00],
            ['descricao' => 'Internet, telefonia e software de gestão', 'grupo' => 'administrativo', 'valor' => 620.00],
            ['descricao' => 'Marketing e anúncios', 'grupo' => 'comercial', 'valor' => 1800.00],
            ['descricao' => 'Tarifas bancárias e maquininha (fixo)', 'grupo' => 'financeiro', 'valor' => 340.00],
        ];

        foreach ($custos as $c) {
            CustoFixo::query()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'descricao' => $c['descricao']],
                ['grupo' => $c['grupo'], 'valor_mensal' => $c['valor'], 'ativo' => true],
            );
        }
    }

    private function itens(Empresa $empresa): void
    {
        $categorias = [];

        foreach (['Alimentos', 'Bebidas', 'Serviços técnicos'] as $nome) {
            $categorias[$nome] = Categoria::query()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'nome' => $nome],
                ['ativo' => true],
            );
        }

        $itens = [
            ['nome' => 'Café torrado e moído 500g', 'sku' => 'CAF-500', 'tipo' => 'produto', 'cat' => 'Alimentos', 'un' => 'PC', 'custo' => 12.4000, 'margem' => 20.0, 'volume' => 900],
            ['nome' => 'Cesta de café da manhã', 'sku' => 'CST-001', 'tipo' => 'produto', 'cat' => 'Alimentos', 'un' => 'UN', 'custo' => 58.9000, 'margem' => 25.0, 'volume' => 180],
            ['nome' => 'Refrigerante lata 350ml', 'sku' => 'REF-350', 'tipo' => 'produto', 'cat' => 'Bebidas', 'un' => 'UN', 'custo' => 2.3500, 'margem' => 18.0, 'volume' => 3200],
            ['nome' => 'Instalação e configuração de rede', 'sku' => 'SRV-NET', 'tipo' => 'servico', 'cat' => 'Serviços técnicos', 'un' => 'HR', 'custo' => 45.0000, 'margem' => 30.0, 'volume' => 160],
            ['nome' => 'Manutenção preventiva mensal', 'sku' => 'SRV-MNT', 'tipo' => 'servico', 'cat' => 'Serviços técnicos', 'un' => 'MES', 'custo' => 120.0000, 'margem' => 28.0, 'volume' => 60],
        ];

        foreach ($itens as $i) {
            Item::query()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'sku' => $i['sku']],
                [
                    'categoria_id' => $categorias[$i['cat']]->id,
                    'tipo' => $i['tipo'],
                    'nome' => $i['nome'],
                    'unidade_medida' => $i['un'],
                    'custo_variavel_unitario' => $i['custo'],
                    'margem_contribuicao_desejada' => $i['margem'],
                    'volume_projetado_mensal' => $i['volume'],
                    'ativo' => true,
                ],
            );
        }
    }
}
