# ValorCerto

Sistema de precificação que forma preços a partir do **custo real**, da **carga tributária efetiva** e de uma **margem de contribuição sobre o preço final** — com memória de cálculo visível e registrada para auditoria.

## A fórmula

```
                    Custo Variável Unitário + Rateio Fixo Unitário
Preço de Venda = ─────────────────────────────────────────────────────
                 1 − (Soma das Alíquotas Efetivas + Margem de Contribuição)
```

Dois pontos que diferenciam este cálculo do markup tradicional:

- **A margem entra no divisor**, não como multiplicador sobre o custo. Assim ela é exatamente o percentual desejado *sobre o preço final*.
- **As alíquotas são efetivas**, não nominais — líquidas de créditos e reduções de base, porque é isso que sai do caixa.

## O que o sistema faz

### Custo de produção por ficha técnica

Insumos separam **unidade de compra** de **unidade de uso**, com percentual de perda:

| Insumo | Compra | Conversão | Custo por unidade de uso |
|---|---|---|---|
| Metalon 100x40x2mm | VARA a R$ 265,00 | 1 vara = 6 M, perda 8% | R$ 47,70 / M |
| Chapa de aço 1,5mm | CHAPA a R$ 245,00 | 1 chapa = 2 M², perda 12% | R$ 137,20 / M² |
| Mecanismo de giro | UN a R$ 890,00 | direta | R$ 890,00 / UN |

A ficha técnica multiplica o consumo de cada insumo e soma o custo de produção da peça. Quando existe ficha, ela manda no custo variável da precificação — o valor digitado no cadastro é ignorado.

### Simulador com o preço sendo construído

Ajuste custo, volume e margem e veja o preço se formar em tempo real: a memória de cálculo em 6 passos, a barra de composição (custo / tributos / margem) e os alertas se atualizam junto. O recálculo é feito no servidor, com a mesma regra que será registrada.

### Memória de cálculo auditável

Cada preço registrado congela um snapshot completo e imutável:

- as 6 etapas com fórmula, substituição numérica e resultado;
- os tributos aplicados, com alíquotas e base legal da data;
- a ficha técnica linha a linha, com preços de compra e perdas vigentes;
- assinatura **SHA-256** do conteúdo.

O registro sobrevive à exclusão do item e ao reajuste dos insumos.

### Conformidade legal

| Situação | Resposta do sistema |
|---|---|
| Tributos + margem ≥ 100% | Bloqueia o cálculo (divisor nulo ou negativo) |
| Divisor abaixo de 15% | Alerta crítico de fragilidade do preço |
| Preço abaixo do custo | Lei 12.529/2011, art. 36, § 3º, XV |
| Reajuste ≥ 10% | CDC art. 39, X — exige justa causa documentada |
| Alíquota efetiva > nominal | Recusado na validação |

### Acesso por perfil

| | Administrador | Operador |
|---|---|---|
| Simular e registrar preços | ✅ | ✅ |
| Produtos, insumos, fichas técnicas, categorias | ✅ | ✅ |
| Tributos, custos fixos, empresa | ✅ | ❌ |
| Usuários | ✅ | ❌ |

Login com bloqueio após 5 tentativas, usuários inativos sem acesso, e proteções que impedem o administrador de remover o próprio acesso.

## Stack

- PHP 8.5 · Laravel 13 · MySQL 8
- Blade + Tailwind CSS 4 + Alpine.js + FontAwesome
- PSR-12 (Laravel Pint) · PHPUnit

## Instalação

```bash
git clone https://github.com/vsceno/valorcerto.git
cd valorcerto

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Crie o banco e ajuste as credenciais no `.env`:

```env
DB_CONNECTION=mysql
DB_DATABASE=valorcerto
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

A aplicação sobe em `http://localhost:8000`.

### Dados de exemplo

O seeder popula uma empresa em Lucro Presumido com 6 tributos, 9 custos fixos (R$ 28.540/mês), itens de comércio e serviço, e um exemplo completo de produto fabricado: uma **catraca de acesso tripé** com 15 insumos em ficha técnica.

Acessos de referência:

```
admin@valorcerto.test     / admin1234      (administrador)
operador@valorcerto.test  / operador1234   (operador)
```

> **Estes acessos são apenas para desenvolvimento.** Troque as senhas antes de qualquer uso real — elas estão em texto claro no `DatabaseSeeder`.

> Os preços de insumos do seeder são estimativas de mercado para o exemplo ficar utilizável. Substitua pelos valores das suas notas fiscais antes de praticar qualquer preço.

## Testes

```bash
php artisan test
php vendor/bin/pint --test
```

A suíte cobre a fórmula oficial, a incidência da margem sobre o preço final, a prova real da decomposição, as travas de inviabilidade, os alertas legais, as conversões de unidade da ficha técnica, o congelamento dos snapshots de auditoria e a matriz de permissões.

## Estrutura

```
app/
├── DTO/ResultadoPrecificacao.php    Resultado imutável com memória de cálculo
├── Services/PrecificacaoService.php  A fórmula e os alertas legais
├── Models/                           Empresa, Tributo, CustoFixo, Item,
│                                     Insumo, Composicao, Precificacao
└── Http/
    ├── Controllers/                  Lógica de tela separada do cálculo
    └── Requests/                     Validações, com formato numérico BR
```

O motor de precificação não depende de HTTP nem de Eloquent para calcular: recebe números, devolve o resultado com a memória. Os models e controllers só o alimentam.
