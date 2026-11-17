/** Escapa texto antes de ir para o innerHTML (evita XSS). Igual ao do team.js. */
const esc = (texto) => String(texto ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

/**
 * TelaLoja - Poké Mart.
 * Busca saldo, itens e mochila no back-end e compra com fetch (via ApiClient).
 */
class TelaLoja {
    static #CATEGORIAS = {
        cura_hp: 'Cura de HP',
        cura_status: 'Cura de condição',
        reviver: 'Reviver',
        evolucao: 'Evolução',
    };

    #api;

    constructor(api) {
        this.#api = api;
    }

    async iniciar() {
        try {
            this.#desenhar(await this.#api.get('/api/loja'));
        } catch (e) {
            this.#aviso(e.message, false);
        }
    }

    async #comprar(item, quantidade) {
        try {
            const dados = await this.#api.post('/api/loja/comprar', {
                item_id: item.id,
                quantidade: quantidade,
            });
            this.#desenhar(dados);
            this.#aviso(`Comprou ${quantidade}x ${item.nome}!`, true);
        } catch (e) {
            this.#aviso(e.message, false); // ex.: "Saldo insuficiente."
        }
    }

    // ---------- desenho ----------

    #desenhar(dados) {
        document.getElementById('saldo').textContent = dados.saldo;
        this.#desenharMochila(dados.inventario);
        this.#desenharLoja(dados.itens, dados.saldo);
    }

    #desenharMochila(inventario) {
        const lista = document.getElementById('mochila');
        if (inventario.length === 0) {
            lista.innerHTML = '<li class="vazio">Mochila vazia.</li>';
            return;
        }
        lista.innerHTML = inventario
            .map(i => `<li><strong>${i.quantidade}x</strong> ${esc(i.nome)}
                       <small>${esc(i.descricao)}</small></li>`)
            .join('');
    }

    #desenharLoja(itens, saldo) {
        const loja = document.getElementById('loja');
        loja.innerHTML = '';

        itens.forEach(item => {
            const card = document.createElement('div');
            card.className = 'card-poke';
            card.innerHTML = `
                <strong>${esc(item.nome)}</strong>
                <small>${esc(TelaLoja.#CATEGORIAS[item.categoria] ?? item.categoria)}</small>
                <p class="descricao">${esc(item.descricao)}</p>
                <span class="preco">$ ${item.preco}</span>
                <div class="acoes">
                    <input type="number" min="1" max="99" value="1" class="qtd">
                    <button type="button" ${item.preco > saldo ? 'disabled' : ''}>Comprar</button>
                </div>`;

            card.querySelector('button').addEventListener('click', () => {
                const qtd = parseInt(card.querySelector('.qtd').value, 10) || 1;
                this.#comprar(item, qtd);
            });
            loja.appendChild(card);
        });
    }

    #aviso(texto, sucesso) {
        const el = document.getElementById('mensagem');
        el.textContent = texto;
        el.classList.toggle('sucesso', sucesso);
        el.classList.add('visivel');
        setTimeout(() => el.classList.remove('visivel'), 3500);
    }
}

new TelaLoja(new ApiClient(window.BASE)).iniciar();
