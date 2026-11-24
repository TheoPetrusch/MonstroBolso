/** Escapa texto antes de ir para o innerHTML (evita XSS). Igual ao do team.js. */
const esc = (texto) => String(texto ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

/**
 * TelaAdmin - controle de usuário: o admin troca o perfil (jogador <-> admin)
 * dos outros usuários e vê o log das trocas. Tudo por fetch (ApiClient) e JSON.
 */
class TelaAdmin {
    #api;

    constructor(api) {
        this.#api = api;
    }

    async iniciar() {
        try {
            this.#desenhar(await this.#api.get('/api/admin/usuarios'));
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    async #trocar(usuario, novoPerfil) {
        if (!confirm(`Tornar ${usuario.nome} ${novoPerfil}?`)) return;
        try {
            await this.#api.post('/api/perfil/trocar', { usuario_id: usuario.id, perfil: novoPerfil });
            this.#desenhar(await this.#api.get('/api/admin/usuarios')); // recarrega lista e log
            this.#aviso(`${usuario.nome} agora é ${novoPerfil}.`, true);
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    #desenhar(r) {
        this.#desenharUsuarios(r.usuarios, r.eu);
        this.#desenharHistorico(r.historico);
    }

    #desenharUsuarios(usuarios, eu) {
        const alvo = document.getElementById('usuarios');
        alvo.innerHTML = `
            <table class="tabela-admin">
                <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Pokédollar</th><th></th></tr>
            </table>`;
        const tabela = alvo.querySelector('table');

        usuarios.forEach(u => {
            const novo = u.perfil === 'admin' ? 'jogador' : 'admin';
            const linha = document.createElement('tr');
            linha.innerHTML = `
                <td>${esc(u.nome)}${u.id === eu ? ' <small>(você)</small>' : ''}</td>
                <td>${esc(u.email)}</td>
                <td><span class="perfil ${esc(u.perfil)}">${esc(u.perfil)}</span></td>
                <td>$ ${Number(u.pokedollar)}</td>
                <td></td>`;
            if (u.id !== eu) { // o admin não troca o próprio perfil
                const botao = document.createElement('button');
                botao.type = 'button';
                botao.className = 'sec';
                botao.textContent = `Tornar ${novo}`;
                botao.addEventListener('click', () => this.#trocar(u, novo));
                linha.lastElementChild.appendChild(botao);
            }
            tabela.appendChild(linha);
        });
    }

    #desenharHistorico(historico) {
        const alvo = document.getElementById('historico');
        if (!historico.length) {
            alvo.innerHTML = '<p class="dica">Nenhuma troca de perfil ainda.</p>';
            return;
        }
        alvo.innerHTML = `
            <table class="tabela-admin">
                <tr><th>Quando</th><th>Usuário</th><th>De</th><th>Para</th></tr>
                ${historico.map(h => `
                    <tr>
                        <td>${esc(h.data_troca)}</td>
                        <td>${esc(h.nome)}</td>
                        <td>${esc(h.perfil_antigo)}</td>
                        <td>${esc(h.perfil_novo)}</td>
                    </tr>`).join('')}
            </table>`;
    }

    #aviso(texto, sucesso = false) {
        const el = document.getElementById('mensagem');
        el.textContent = texto;
        el.classList.toggle('sucesso', sucesso);
        el.classList.add('visivel');
        setTimeout(() => el.classList.remove('visivel'), 3500);
    }
}

new TelaAdmin(new ApiClient(window.BASE)).iniciar();
