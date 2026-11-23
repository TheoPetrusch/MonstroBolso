/** Escapa texto antes de ir para o innerHTML (evita XSS). Igual ao do team.js. */
const esc = (texto) => String(texto ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

/**
 * TelaBatalha - tela de batalha.
 * Cada clique manda UMA ação para o back-end (fetch assíncrono via ApiClient);
 * o PHP calcula o turno inteiro e devolve o estado novo em JSON.
 */
class TelaBatalha {
    static #SPRITES = 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/';

    #api;
    #ocupado = false; // evita mandar duas ações ao mesmo tempo

    constructor(api) {
        this.#api = api;
        document.getElementById('btn-iniciar').addEventListener('click', () => this.#enviar('/api/batalha/iniciar'));
        document.getElementById('btn-centro').addEventListener('click', () => this.#centroPokemon());
        document.getElementById('btn-fugir').addEventListener('click', () => {
            if (confirm('Fugir conta como derrota. Tem certeza?')) this.#enviar('/api/batalha/fugir');
        });
        document.getElementById('btn-usar').addEventListener('click', () => this.#acao({
            tipo: 'item',
            id: Number(document.getElementById('item').value),
            alvo_id: Number(document.getElementById('alvo').value),
        }));
    }

    async iniciar() {
        try {
            this.#desenhar(await this.#api.get('/api/batalha'));
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    // ---------- comunicação ----------

    #acao(dados) {
        return this.#enviar('/api/batalha/acao', dados);
    }

    async #enviar(rota, dados = {}) {
        if (this.#ocupado) return;
        this.#ocupado = true;
        try {
            this.#desenhar(await this.#api.post(rota, dados));
        } catch (e) {
            this.#aviso(e.message); // ex.: "Potion não teria efeito em Pikachu."
        } finally {
            this.#ocupado = false;
        }
    }

    /** Centro Pokémon: cura o time de graça (só fora da batalha). */
    async #centroPokemon() {
        try {
            const r = await this.#api.post('/api/centro-pokemon');
            this.#aviso(r.curados > 0 ? `Centro Pokémon: ${r.curados} Pokémon curados!` : 'Seu time já estava 100%.', true);
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    // ---------- desenho ----------

    #desenhar(r) {
        const b = r.batalha;
        document.getElementById('inicio').hidden = b !== null;
        document.getElementById('campo').hidden = b === null;
        document.getElementById('acoes').hidden = b === null || b.encerrada;
        this.#desenharHistorico(r.historico);

        if (b === null) {
            document.getElementById('resultado').hidden = true;
            document.getElementById('log').innerHTML = '';
            return;
        }

        const meu = b.jogador.pokemons[b.jogador.ativo];
        const dele = b.oponente.pokemons[b.oponente.ativo];
        const deleDePe = b.oponente.pokemons.filter(p => !p.desmaiado).length;

        document.getElementById('lado-oponente').innerHTML =
            this.#lado(dele, false, `${esc(b.oponente_nome)} · ${deleDePe}/${b.oponente.pokemons.length} de pé`);
        document.getElementById('lado-jogador').innerHTML = this.#lado(meu, true, 'Seu Pokémon');

        this.#desenharLog(b.log);
        this.#desenharResultado(b);
        if (!b.encerrada) {
            this.#desenharGolpes(b.golpes);
            this.#desenharMochila(r.mochila, b.jogador.pokemons);
            this.#desenharReservas(b.jogador);
        }
    }

    /** Um lado do campo: sprite, nome, tipo, barra de HP e condição. */
    #lado(p, costas, legenda) {
        const pasta = (costas ? 'back/' : '') + (p.shiny ? 'shiny/' : '');
        const pct = p.hp_max > 0 ? Math.round(p.hp_atual / p.hp_max * 100) : 0;
        const cor = pct > 50 ? '' : (pct > 20 ? ' media' : ' baixa');
        const estado = p.desmaiado ? 'Desmaiado' : (p.status !== 'normal' ? p.status : '');
        return `
            <img src="${TelaBatalha.#SPRITES}${pasta}${Number(p.pokeapi_id)}.png" alt="${esc(p.nome)}"
                 class="${p.desmaiado ? 'desmaiado' : ''}">
            <div class="info" data-tipo="${esc(p.tipo)}">
                <span class="reserva">${legenda}</span>
                <strong>${p.shiny ? '✨ ' : ''}${esc(p.nome)}</strong>
                <span><span class="chip-tipo">${esc(p.tipo)}</span>
                      ${estado ? `<small class="estado">${esc(estado)}</small>` : ''}</span>
                <div class="hp">
                    <span>HP</span>
                    <div class="trilho"><span class="vida${cor}" style="width:${pct}%"></span></div>
                    <span>${Number(p.hp_atual)}/${Number(p.hp_max)}</span>
                </div>
            </div>`;
    }

    #desenharGolpes(golpes) {
        const alvo = document.getElementById('golpes');
        alvo.innerHTML = '';
        golpes.forEach(g => {
            const botao = document.createElement('button');
            botao.type = 'button';
            botao.dataset.tipo = g.tipo;
            botao.innerHTML = `${esc(g.nome)}
                <small>${esc(g.tipo)} · poder ${Number(g.poder)} · precisão ${Number(g.precisao)}%</small>`;
            botao.addEventListener('click', () => this.#acao({ tipo: 'golpe', id: g.id }));
            alvo.appendChild(botao);
        });
    }

    #desenharMochila(mochila, pokemons) {
        const item = document.getElementById('item');
        const alvo = document.getElementById('alvo');
        item.innerHTML = mochila.length
            ? mochila.map(i => `<option value="${i.item_id}">${esc(i.nome)} (${i.quantidade})</option>`).join('')
            : '<option value="0">Mochila vazia</option>';
        alvo.innerHTML = pokemons
            .map(p => `<option value="${p.id}">${esc(p.nome)} (${p.hp_atual}/${p.hp_max})</option>`)
            .join('');
        alvo.value = pokemons.find(p => !p.desmaiado)?.id ?? '';
        document.getElementById('btn-usar').disabled = mochila.length === 0;
    }

    #desenharReservas(jogador) {
        const alvo = document.getElementById('reservas');
        alvo.innerHTML = '';
        jogador.pokemons.forEach(p => {
            if (p.indice === jogador.ativo) return;
            const botao = document.createElement('button');
            botao.type = 'button';
            botao.className = 'sec';
            botao.textContent = `${p.nome} (${p.hp_atual}/${p.hp_max})`;
            botao.disabled = p.desmaiado;
            botao.addEventListener('click', () => this.#acao({ tipo: 'trocar', id: p.id }));
            alvo.appendChild(botao);
        });
        if (!alvo.children.length) alvo.innerHTML = '<small>Ninguém para trocar.</small>';
    }

    #desenharLog(log) {
        const lista = document.getElementById('log');
        lista.innerHTML = log
            .map(t => `<li class="${esc(t.ator)}" value="${Number(t.turno)}">${esc(t.mensagem)}</li>`)
            .join('');
        lista.scrollTop = lista.scrollHeight; // mostra sempre o mais recente
    }

    #desenharResultado(b) {
        const alvo = document.getElementById('resultado');
        alvo.hidden = !b.encerrada;
        if (!b.encerrada) return;

        const venceu = b.vencedor === 'jogador';
        alvo.className = 'resultado ' + (venceu ? 'vitoria' : 'derrota');
        alvo.innerHTML = `
            <span>${venceu ? `Você venceu! Ganhou $ ${Number(b.premio)}.` : 'Você perdeu a batalha.'}</span>
            <span>Seus Pokémon saem com o HP que sobrou: cure de graça no Centro Pokémon antes da próxima.</span>
            <span>
                <button type="button" class="nova">Nova batalha</button>
                <button type="button" class="sec centro">Centro Pokémon (grátis)</button>
            </span>`;
        alvo.querySelector('.nova').addEventListener('click', () => this.#enviar('/api/batalha/iniciar'));
        alvo.querySelector('.centro').addEventListener('click', () => this.#centroPokemon());
    }

    #desenharHistorico(historico) {
        const alvo = document.getElementById('historico');
        if (!historico.length) {
            alvo.innerHTML = '<p class="dica">Nenhuma batalha ainda.</p>';
            return;
        }
        alvo.innerHTML = `
            <table class="tabela-historico">
                <tr><th>Quando</th><th>Oponente</th><th>Resultado</th><th>Prêmio</th><th>Turnos</th></tr>
                ${historico.map(h => `
                    <tr>
                        <td>${esc(h.data_batalha)}</td>
                        <td>${esc(h.oponente_nome)}</td>
                        <td>${h.vencedor === 'jogador' ? 'Vitória' : 'Derrota'}</td>
                        <td>$ ${Number(h.dinheiro_ganho)}</td>
                        <td>${Number(h.total_turnos)}</td>
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

new TelaBatalha(new ApiClient(window.BASE)).iniciar();
