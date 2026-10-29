/**
 * TelaLogin - controla as abas e os dois formulários (entrar / cadastrar).
 */
class TelaLogin {
    #api;
    #msg = document.getElementById('mensagem');

    constructor(api) {
        this.#api = api;
        document.querySelectorAll('.aba').forEach(b =>
            b.addEventListener('click', () => this.#trocarAba(b.dataset.aba)));
        document.getElementById('form-login')
            .addEventListener('submit', e => this.#enviar(e, '/api/login'));
        document.getElementById('form-registro')
            .addEventListener('submit', e => this.#enviar(e, '/api/registrar'));
    }

    #trocarAba(nome) {
        document.querySelectorAll('.aba').forEach(b =>
            b.classList.toggle('ativa', b.dataset.aba === nome));
        document.getElementById('form-login').hidden = nome !== 'login';
        document.getElementById('form-registro').hidden = nome !== 'registro';
        this.#msg.textContent = '';
    }

    async #enviar(evento, rota) {
        evento.preventDefault();
        const dados = Object.fromEntries(new FormData(evento.target));
        this.#msg.textContent = 'Aguarde...';
        try {
            await this.#api.post(rota, dados);
            location.href = window.BASE + '/home';
        } catch (erro) {
            this.#msg.textContent = erro.message;
        }
    }
}

new TelaLogin(new ApiClient(window.BASE));
