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
    #evolucoes = []; // [{pokemon_id, para_nome, item_id, item_nome, quantidade}]
    #mochila = [];   // itens de cura: [{item_id, nome, categoria, descricao, quantidade}]

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
            this.#evolucoes = time.evolucoes;
            this.#mochila = time.mochila;
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
            this.#evolucoes = r.evolucoes;
            this.#mochila = r.mochila;
            this.#desenharTudo();
            if (r.shiny) {
                const nome = this.#especies.find(e => e.id === especieId)?.nome ?? 'Pokémon';
                this.#aviso(`✨ Uau! Seu ${nome} é shiny! (chance de 1 em 64)`, true);
            }
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    async #remover(pokemonId) {
        try {
            const r = await this.#api.post('/api/time/remover', { pokemon_id: pokemonId });
            this.#time = r.time;
            this.#evolucoes = r.evolucoes;
            this.#mochila = r.mochila;
            this.#desenharTudo();
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    async #evoluir(pokemon, opcao) {
        try {
            const r = await this.#api.post('/api/time/evoluir', {
                pokemon_id: pokemon.id,
                item_id: opcao.item_id,
                para_especie_id: opcao.para_especie_id,
            });
            this.#time = r.time;
            this.#evolucoes = r.evolucoes;
            this.#mochila = r.mochila;
            // a evolução acabou de ser desbloqueada: recarrega o catálogo
            this.#especies = (await this.#api.get('/api/especies')).especies;
            this.#desenharTudo();
            this.#aviso(`${pokemon.nome} evoluiu para ${r.evoluiu_para}! Ele agora está desbloqueado.`, true);
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    async #usarItem(pokemon, itemId) {
        const item = this.#mochila.find(i => i.item_id === itemId);
        try {
            const r = await this.#api.post('/api/time/usar-item', {
                pokemon_id: pokemon.id,
                item_id: itemId,
            });
            this.#time = r.time;
            this.#evolucoes = r.evolucoes;
            this.#mochila = r.mochila;
            this.#desenharTudo();
            this.#aviso(`Usou ${item?.nome ?? 'o item'} em ${pokemon.nome}!`, true);
        } catch (e) {
            this.#aviso(e.message); // ex.: "Potion não teria efeito em Pikachu."
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
            div.className = 'slot' + (p ? ' ocupado' : '') + (p?.shiny ? ' shiny' : '');
            if (p) {
                div.innerHTML = `
                    <img src="${esc(p.shiny ? TeamBuilder.#spriteShiny(p.especie.pokeapi_id) : p.especie.sprite_url)}"
                         alt="${esc(p.nome)}">
                    <strong>${p.shiny ? '✨ ' : ''}${esc(p.nome)}</strong>
                    <small>${esc(p.especie.tipo)} &middot; HP ${p.hp_atual}/${p.hp_max}</small>
                    ${TeamBuilder.#estado(p)}
                    <div class="evolucoes"></div>
                    <div class="usar-item"></div>
                    <button type="button" class="perigo">Remover</button>`;
                div.querySelector('.perigo').addEventListener('click', () => this.#remover(p.id));
                this.#desenharEvolucoes(div.querySelector('.evolucoes'), p);
                this.#desenharUsarItem(div.querySelector('.usar-item'), p);
            } else {
                div.innerHTML = `<span class="vazio">Slot ${i} vazio</span>`;
            }
            slots.appendChild(div);
        }
    }

    /** Um botão por evolução possível; desativado se faltar a pedra na mochila. */
    #desenharEvolucoes(alvo, pokemon) {
        this.#evolucoes
            .filter(o => o.pokemon_id === pokemon.id)
            .forEach(o => {
                const botao = document.createElement('button');
                botao.type = 'button';
                botao.className = 'sec';
                botao.textContent = `Evoluir: ${o.para_nome}`;
                if (o.quantidade > 0) {
                    botao.title = `Usa 1 ${o.item_nome} (você tem ${o.quantidade})`;
                    botao.addEventListener('click', () => this.#evoluir(pokemon, o));
                } else {
                    botao.disabled = true;
                    botao.title = `Precisa de ${o.item_nome}: compre no Poké Mart`;
                }
                alvo.appendChild(botao);
            });
    }

    /** Sprite shiny da PokéAPI (mesmo usado no hover do catálogo). */
    static #spriteShiny(pokeapiId) {
        return `https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/${Number(pokeapiId)}.png`;
    }

    /** Desmaiado ou com condição (veneno, queimado...): mostra em destaque. */
    static #estado(p) {
        if (p.desmaiado) return '<small class="estado">Desmaiado</small>';
        if (p.status !== 'normal') return `<small class="estado">${esc(p.status)}</small>`;
        return '';
    }

    /** Lista de itens de cura da mochila + botão "Usar". */
    #desenharUsarItem(alvo, pokemon) {
        if (this.#mochila.length === 0) return;

        const select = document.createElement('select');
        this.#mochila.forEach(i => {
            const opcao = document.createElement('option');
            opcao.value = i.item_id;
            opcao.textContent = `${i.nome} (${i.quantidade})`;
            opcao.title = i.descricao;
            select.appendChild(opcao);
        });

        const botao = document.createElement('button');
        botao.type = 'button';
        botao.className = 'sec';
        botao.textContent = 'Usar';
        botao.addEventListener('click', () => this.#usarItem(pokemon, Number(select.value)));

        alvo.append(select, botao);
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

                const shiny = TeamBuilder.#spriteShiny(e.pokeapi_id);
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
                        ${e.disponivel
                            ? `<button type="button" class="add" ${cheio ? 'disabled' : ''}
                                       ${e.basica ? '' : 'title="Desbloqueado por evolução"'}>Adicionar</button>`
                            : `<button type="button" class="add" disabled
                                       title="Bloqueado: evolua um Pokémon com pedra do Poké Mart para desbloquear.">Bloqueado</button>`}
                    </div>`;

                card.querySelector('.sec').addEventListener('click', () => this.#mostrarDetalhes(e));
                card.querySelector('.add').addEventListener('click', () => this.#adicionar(e.id));
                catalogo.appendChild(card);
            });
    }

    #aviso(texto, sucesso = false) {
        const el = document.getElementById('mensagem');
        el.textContent = texto;
        el.classList.toggle('sucesso', sucesso);
        el.classList.add('visivel');
        setTimeout(() => el.classList.remove('visivel'), 3500);
    }
}

new TeamBuilder(new ApiClient(window.BASE), new PokeApi()).iniciar();
