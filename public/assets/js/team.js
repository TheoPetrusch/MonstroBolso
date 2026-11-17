/** Escapa texto antes de ir para o innerHTML (evita XSS, ex.: apelido com <script>). */
const esc = (texto) => String(texto ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

/**
 * TeamBuilder - tela de montagem de time.
 * Usa ApiClient (back-end próprio) e PokeApi (API externa), ambos assíncronos.
 */
class TeamBuilder {
    #api;
    #pokeApi;
    #especies = [];
    #time = { maximo: 6, pokemons: [] };

    constructor(api, pokeApi) {
        this.#api = api;
        this.#pokeApi = pokeApi;
        document.getElementById('busca')
            .addEventListener('input', () => this.#desenharCatalogo());
    }

    async iniciar() {
        try {
            const [esp, time] = await Promise.all([
                this.#api.get('/api/especies'),
                this.#api.get('/api/time'),
            ]);
            this.#especies = esp.especies;
            this.#time = time.time;
            this.#desenharTudo();
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    // ---------- ações ----------

    async #adicionar(especieId) {
        try {
            const r = await this.#api.post('/api/time/adicionar', { especie_id: especieId });
            this.#time = r.time;
            this.#desenharTudo();
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    async #remover(pokemonId) {
        try {
            const r = await this.#api.post('/api/time/remover', { pokemon_id: pokemonId });
            this.#time = r.time;
            this.#desenharTudo();
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    async #mostrarDetalhes(especie) {
        const alvo = document.getElementById('detalhes');
        alvo.textContent = 'Consultando a PokéAPI...';
        try {
            const d = await this.#pokeApi.buscar(especie.pokeapi_id);
            alvo.innerHTML = `
                <img src="${esc(d.arte)}" alt="${esc(especie.nome)}" width="140">
                <div>
                    <h3>${esc(especie.nome)}</h3>
                    <p>Tipos (PokéAPI): ${esc(d.tipos.join(', '))}</p>
                    <p>Altura: ${d.altura} m &middot; Peso: ${d.peso} kg</p>
                    <p>Habilidades: ${esc(d.habilidades.join(', '))}</p>
                    <p>HP ${especie.hp} &middot; Atq ${especie.ataque} &middot; Def ${especie.defesa}
                       &middot; SpA ${especie.sp_atk} &middot; SpD ${especie.sp_def}
                       &middot; Vel ${especie.velocidade}</p>
                </div>`;
        } catch (e) {
            alvo.textContent = e.message;
        }
    }

    // ---------- desenho ----------

    #desenharTudo() {
        this.#desenharTime();
        this.#desenharCatalogo();
    }

    #desenharTime() {
        document.getElementById('contagem').textContent = this.#time.pokemons.length;
        const slots = document.getElementById('slots');
        slots.innerHTML = '';

        for (let i = 1; i <= this.#time.maximo; i++) {
            const p = this.#time.pokemons.find(x => x.posicao === i);
            const div = document.createElement('div');
            div.className = 'slot' + (p ? ' ocupado' : '');
            if (p) {
                div.innerHTML = `
                    <img src="${esc(p.especie.sprite_url)}" alt="${esc(p.nome)}">
                    <strong>${esc(p.nome)}</strong>
                    <small>${esc(p.especie.tipo)} &middot; HP ${p.hp_atual}/${p.hp_max}</small>
                    <button type="button" class="perigo">Remover</button>`;
                div.querySelector('button').addEventListener('click', () => this.#remover(p.id));
            } else {
                div.innerHTML = `<span class="vazio">Slot ${i} vazio</span>`;
            }
            slots.appendChild(div);
        }
    }

    #desenharCatalogo() {
        const termo = document.getElementById('busca').value.trim().toLowerCase();
        const cheio = this.#time.pokemons.length >= this.#time.maximo;
        const catalogo = document.getElementById('catalogo');
        catalogo.innerHTML = '';

        this.#especies
            .filter(e => !termo || e.nome.toLowerCase().includes(termo)
                || e.tipo.toLowerCase().includes(termo))
                       .forEach(e => {
                const card = document.createElement('div');
                card.className = 'card-poke';
                card.dataset.tipo = e.tipo;

                const shiny = `https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/${Number(e.pokeapi_id)}.png`;
                const barra = (valor) => `<div class="barra"><span style="width:${Math.min(valor / 1.5, 100)}%"></span></div>`;

                card.innerHTML = `
                    <div class="sprite">
                        <img class="normal" src="${esc(e.sprite_url)}" alt="${esc(e.nome)}">
                        <img class="shiny" src="${shiny}" alt="">
                    </div>
                    <strong>${esc(e.nome)}</strong>
                    <small>${esc(e.tipo)} &middot; HP ${e.hp} &middot; Vel ${e.velocidade}</small>
                    <div class="previa">
                        HP ${e.hp} ${barra(e.hp)}
                        Ataque ${e.ataque} ${barra(e.ataque)}
                        Defesa ${e.defesa} ${barra(e.defesa)}
                        Vel ${e.velocidade} ${barra(e.velocidade)}
                    </div>
                    <div class="acoes">
                        <button type="button" class="sec">Detalhes</button>
                        <button type="button" class="add" ${cheio ? 'disabled' : ''}>Adicionar</button>
                    </div>`;

                card.querySelector('.sec').addEventListener('click', () => this.#mostrarDetalhes(e));
                card.querySelector('.add').addEventListener('click', () => this.#adicionar(e.id));
                catalogo.appendChild(card);
            });
    }

    #aviso(texto) {
        const el = document.getElementById('mensagem');
        el.textContent = texto;
        el.classList.add('visivel');
        setTimeout(() => el.classList.remove('visivel'), 3500);
    }
}

new TeamBuilder(new ApiClient(window.BASE), new PokeApi()).iniciar();
