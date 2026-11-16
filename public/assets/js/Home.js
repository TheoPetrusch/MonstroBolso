/**
 * TelaHome - menu inicial do jogador (por enquanto, só o botão de sair).
 */
class TelaHome {
    #api;

    constructor(api) {
        this.#api = api;
        document.getElementById('btn-sair')
            .addEventListener('click', () => this.#sair());
    }

    async #sair() {
        try {
            await this.#api.post('/api/logout');
        } finally {
            location.href = window.BASE + '/';
        }
    }
}

new TelaHome(new ApiClient(window.BASE));