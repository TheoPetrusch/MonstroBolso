/**
 * TelaHome - menu inicial do jogador.
 * Admin também vê o botão que importa os Pokémon da PokéAPI.
 */
class TelaHome {
    #api;

    constructor(api) {
        this.#api = api;
        document.getElementById('btn-sair')
            .addEventListener('click', () => this.#sair());

        // o botão só existe na página quando o usuário é admin
        document.getElementById('btn-importar')
            ?.addEventListener('click', (e) => this.#importar(e.currentTarget));
    }

    async #sair() {
        try {
            await this.#api.post('/api/logout');
        } finally {
            location.href = window.BASE + '/';
        }
    }

    async #importar(botao) {
        const status = document.getElementById('status-importacao');
        botao.disabled = true;
        status.textContent = 'Importando da PokéAPI... pode levar alguns minutos, não feche a página.';
        try {
            const r = await this.#api.post('/api/admin/importar', { de: 1, ate: 151 });
            let texto = `${r.importadas} espécies importadas, ${r.evolucoes} evoluções.`;
            if (r.sem_evolucao.length) {
                texto += ` Sem evolução (mais de um caminho): ${r.sem_evolucao.join(', ')}.`;
            }
            status.textContent = texto;
        } catch (erro) {
            status.textContent = 'Erro: ' + erro.message;
        } finally {
            botao.disabled = false;
        }
    }
}

new TelaHome(new ApiClient(window.BASE));
