const moedaBR = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    minimumFractionDigits: 2,
});

const numeroBR = (casas) =>
    new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: casas,
        maximumFractionDigits: casas,
    });

/**
 * Componente Alpine do simulador: mantém as entradas em sincronia com o
 * cálculo feito no servidor, para que a tela nunca mostre um preço apurado
 * por uma regra diferente da que será registrada na auditoria.
 */
export default function simulador(config) {
    return {
        itemId: config.itemId,
        custo: config.custo,
        volume: config.volume,
        margem: config.margem,
        resultado: config.resultado,
        erro: config.erro,
        carregando: false,
        pulsando: false,
        temporizador: null,

        /** Aguarda o usuário parar de digitar antes de recalcular. */
        agendar() {
            clearTimeout(this.temporizador);
            this.temporizador = setTimeout(() => this.calcular(), 350);
        },

        async calcular() {
            if (!this.itemId) {
                return;
            }

            this.carregando = true;

            try {
                const resposta = await fetch(config.rotaCalculo, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        item_id: this.itemId,
                        custo_variavel_unitario: this.custo,
                        margem_contribuicao: this.margem,
                        volume_projetado: this.volume,
                    }),
                });

                const dados = await resposta.json();

                if (!resposta.ok) {
                    this.erro = dados.erro ?? this.primeiraMensagem(dados);
                    this.carregando = false;

                    return;
                }

                this.resultado = dados;
                this.erro = null;
                this.destacar();
            } catch (e) {
                this.erro = 'Não foi possível recalcular agora. Verifique a conexão e tente de novo.';
            }

            this.carregando = false;
        },

        /** Extrai a primeira mensagem de um erro 422 de validação. */
        primeiraMensagem(dados) {
            const erros = dados.errors ?? {};
            const primeira = Object.values(erros)[0];

            return Array.isArray(primeira) ? primeira[0] : (dados.message ?? 'Não foi possível calcular.');
        },

        /** Pisca o preço para deixar claro que o valor mudou. */
        destacar() {
            this.pulsando = false;
            requestAnimationFrame(() => {
                this.pulsando = true;
                setTimeout(() => (this.pulsando = false), 500);
            });
        },

        // --- Fatias do preço, para a barra de composição ---
        get fatiaCusto() {
            return this.percentualDoPreco(this.resultado?.custo_total_unitario);
        },

        get fatiaTributos() {
            return this.percentualDoPreco(this.resultado?.valor_tributos);
        },

        get fatiaMargem() {
            return this.percentualDoPreco(this.resultado?.valor_margem_contribuicao);
        },

        percentualDoPreco(valor) {
            const preco = this.resultado?.preco_venda ?? 0;

            return preco > 0 ? ((valor ?? 0) / preco) * 100 : 0;
        },

        moeda(valor) {
            return moedaBR.format(Number(valor ?? 0));
        },

        pct(valor, casas = 2) {
            return `${numeroBR(casas).format(Number(valor ?? 0))}%`;
        },

        num(valor, casas = 2) {
            return numeroBR(casas).format(Number(valor ?? 0));
        },
    };
}
