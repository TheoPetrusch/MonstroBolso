const esc = (texto) => String(texto ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

class TeamBuilder {
    #api;
    #pokeApi;
    #especies = [];
    #time = { maximo: 6, pokemons: [] };
    #evolucoes = []; 
    #mochila = [];   
    #filtroTipo = ''; 

    constructor(api, pokeApi) {
        this.#api = api;
        this.#pokeApi = pokeApi;
        document.getElementById('busca')
            .addEventListener('input', () => this.#desenharCatalogo());
        document.getElementById('btn-limpar')
            .addEventListener('click', () => this.#limpar());
        document.getElementById('btn-centro')
            .addEventListener('click', () => this.#centroPokemon());
        document.getElementById('fechar-detalhes')
            .addEventListener('click', () => document.getElementById('janela-detalhes').close());
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

    async #limpar() {
        const total = this.#time.pokemons.length;
        if (total === 0) return;
        if (!confirm(`Remover os ${total} Pokémon do time? Isso não pode ser desfeito.`)) return;
        try {
            const r = await this.#api.post('/api/time/limpar');
            this.#time = r.time;
            this.#evolucoes = r.evolucoes;
            this.#mochila = r.mochila;
            this.#desenharTudo();
            this.#aviso(`Time limpo: ${r.removidos} Pokémon removidos.`, true);
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    /** Centro Pokémon: cura o time inteiro de graça. */
    async #centroPokemon() {
        try {
            const r = await this.#api.post('/api/centro-pokemon');
            this.#time = r.time;
            this.#evolucoes = r.evolucoes;
            this.#mochila = r.mochila;
            this.#desenharTudo();
            this.#aviso(r.curados > 0
                ? `Centro Pokémon: ${r.curados} Pokémon curados!`
                : 'Seu time já estava 100%.', true);
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
            this.#aviso(e.message); 
        }
    }

    async #mostrarDetalhes(especie) {
        const janela = document.getElementById('janela-detalhes');
        const alvo = document.getElementById('detalhes');
        janela.dataset.tipo = especie.tipo;
        document.getElementById('janela-titulo').textContent = especie.nome.toUpperCase();
        alvo.textContent = 'Consultando a PokéAPI...';
        if (!janela.open) janela.showModal();
        try {
            const d = await this.#pokeApi.buscar(especie.pokeapi_id);
            const stats = [
                ['HP', especie.hp], ['Ataque', especie.ataque], ['Defesa', especie.defesa],
                ['Sp. Atq', especie.sp_atk], ['Sp. Def', especie.sp_def], ['Vel', especie.velocidade],
            ];
            alvo.innerHTML = `
                <img class="arte" src="${esc(d.arte)}" alt="${esc(especie.nome)}">
                <div class="info">
                    <div>${d.tipos.map(t => `<span class="chip-tipo">${esc(t)}</span>`).join(' ')}</div>
                    <p>Altura: <strong>${d.altura} m</strong> &middot; Peso: <strong>${d.peso} kg</strong></p>
                    <p>Habilidades: <strong>${esc(d.habilidades.join(', '))}</strong></p>
                    ${stats.map(([nome, valor]) => `
                        <div class="stat">
                            <span>${nome}</span><span>${Number(valor)}</span>
                            <div class="hp"><div class="trilho"><span class="vida" style="width:${Math.min(valor / 1.5, 100)}%"></span></div></div>
                        </div>`).join('')}
                </div>`;
        } catch (e) {
            alvo.textContent = e.message;
        }
    }

    

    #desenharTudo() {
        this.#desenharTime();
        this.#desenharResumo();
        this.#desenharFiltros();
        this.#desenharCatalogo();
    }

    #desenharTime() {
        document.getElementById('contagem').textContent = this.#time.pokemons.length;
        document.getElementById('btn-limpar').disabled = this.#time.pokemons.length === 0;
        const slots = document.getElementById('slots');
        slots.innerHTML = '';

        for (let i = 1; i <= this.#time.maximo; i++) {
            const p = this.#time.pokemons.find(x => x.posicao === i);
            const div = document.createElement('div');
            div.className = 'slot' + (p ? ' ocupado' : '') + (p?.shiny ? ' shiny' : '');
            if (p) {
                div.dataset.tipo = p.especie.tipo;
                div.innerHTML = `
                    <span class="numero">${i}</span>
                    <img src="${esc(p.shiny ? TeamBuilder.#spriteShiny(p.especie.pokeapi_id) : p.especie.sprite_url)}"
                         alt="${esc(p.nome)}">
                    <strong>${p.shiny ? '✨ ' : ''}${esc(p.nome)}</strong>
                    <span class="chip-tipo">${esc(p.especie.tipo)}</span>
                    ${TeamBuilder.#barraHp(p.hp_atual, p.hp_max)}
                    ${TeamBuilder.#estado(p)}
                    <div class="evolucoes"></div>
                    <div class="usar-item"></div>
                    <button type="button" class="perigo">Remover</button>`;
                div.querySelector('.perigo').addEventListener('click', () => this.#remover(p.id));
                this.#desenharEvolucoes(div.querySelector('.evolucoes'), p);
                this.#desenharUsarItem(div.querySelector('.usar-item'), p);
            } else {
                div.innerHTML = `<span class="vazio"><span class="mais">+</span>Slot ${i} vazio</span>`;
            }
            slots.appendChild(div);
        }
    }

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

    /** Sprite shiny da PokéAPI (o mesmo do hover do catálogo). */
    static #spriteShiny(pokeapiId) {
        return `https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/${Number(pokeapiId)}.png`;
    }

    static #barraHp(atual, max) {
        const pct = max > 0 ? Math.round(atual / max * 100) : 0;
        const nivel = pct > 50 ? '' : (pct > 20 ? ' media' : ' baixa');
        return `
            <div class="hp">
                <span>HP</span>
                <div class="trilho"><span class="vida${nivel}" style="width:${pct}%"></span></div>
                <span>${Number(atual)}/${Number(max)}</span>
            </div>`;
    }

    #desenharResumo() {
        const alvo = document.getElementById('resumo');
        const pokes = this.#time.pokemons;
        if (pokes.length === 0) {
            alvo.innerHTML = '<p class="dica">Adicione Pokémon pra ver o resumo do time.</p>';
            return;
        }

        const STATUS = [
            ['hp', 'HP'], ['ataque', 'Ataque'], ['defesa', 'Defesa'],
            ['sp_atk', 'Sp. Atq'], ['sp_def', 'Sp. Def'], ['velocidade', 'Vel'],
        ];
        const media = (campo) => Math.round(
            pokes.reduce((t, p) => t + Number(p.especie[campo] || 0), 0) / pokes.length);
        const medias = STATUS.map(([campo, nome]) => ({ campo, nome, valor: media(campo) }));
        const geral = Math.round(medias.reduce((t, m) => t + m.valor, 0) / medias.length);
        const fraco = medias.reduce((a, b) => (b.valor < a.valor ? b : a));
        const forte = medias.reduce((a, b) => (b.valor > a.valor ? b : a));

        const noTime = new Set(pokes.map(p => p.especie.tipo));
        const todos = [...new Set(this.#especies.map(e => e.tipo))];

        alvo.innerHTML = `
            <div class="media-geral">
                <span>Média geral</span>
                <strong>${geral}</strong>
            </div>
            <div class="grafico">
                ${medias.map(m => `
                    <div class="linha${m === fraco ? ' fraco' : ''}${m === forte ? ' forte' : ''}">
                        <span class="nome">${m.nome}</span>
                        <div class="trilho"><span style="width:${Math.min(m.valor / 1.5, 100)}%"></span></div>
                        <span class="valor">${m.valor}</span>
                    </div>`).join('')}
            </div>
            <p class="destaque">
                Ponto forte: <strong>${forte.nome}</strong> &middot;
                Ponto fraco: <strong>${fraco.nome}</strong>
            </p>
            <div class="resumo-tipos">Tipos:
                ${todos.map(tipo => noTime.has(tipo)
                    ? `<span class="chip-tipo" data-tipo="${esc(tipo)}">${esc(tipo)}</span>`
                    : `<span class="chip-tipo falta" title="Nenhum Pokémon desse tipo no time">${esc(tipo)}</span>`).join('')}
            </div>`;
    }

    #desenharFiltros() {
        const alvo = document.getElementById('filtros');
        const tipos = [...new Set(this.#especies.map(e => e.tipo))];
        alvo.innerHTML = '';
        ['', ...tipos].forEach(tipo => {
            const botao = document.createElement('button');
            botao.type = 'button';
            botao.textContent = tipo || 'Todos';
            if (tipo) botao.dataset.tipo = tipo;
            if (tipo === this.#filtroTipo) botao.classList.add('ativo');
            botao.addEventListener('click', () => {
                this.#filtroTipo = tipo;
                this.#desenharFiltros();
                this.#desenharCatalogo();
            });
            alvo.appendChild(botao);
        });
    }

    static #estado(p) {
        if (p.desmaiado) return '<small class="estado">Desmaiado</small>';
        if (p.status !== 'normal') return `<small class="estado">${esc(p.status)}</small>`;
        return '';
    }

    
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
            .filter(e => !this.#filtroTipo || e.tipo === this.#filtroTipo)
            .filter(e => !termo || e.nome.toLowerCase().includes(termo)
                || e.tipo.toLowerCase().includes(termo))
                       .forEach(e => {
                const card = document.createElement('div');
                card.className = 'card-poke' + (e.disponivel ? '' : ' bloqueado');
                card.dataset.tipo = e.tipo;

                const shiny = TeamBuilder.#spriteShiny(e.pokeapi_id);
                const barra = (valor) => `<div class="barra"><span style="width:${Math.min(valor / 1.5, 100)}%"></span></div>`;

                card.innerHTML = `
                    <div class="sprite">
                        <img class="normal" src="${esc(e.sprite_url)}" alt="${esc(e.nome)}">
                        <img class="shiny" src="${shiny}" alt="">
                    </div>
                    <strong>${esc(e.nome)}</strong>
                    <span class="chip-tipo">${esc(e.tipo)}</span>
                    ${e.disponivel
                        ? `<small>HP ${e.hp} &middot; Vel ${e.velocidade}</small>`
                        : '<span class="dica">Evolua um Pokémon com pedra do Poké Mart</span>'}
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
                            : ''}
                    </div>`;

                card.querySelector('.sec').addEventListener('click', () => this.#mostrarDetalhes(e));
                card.querySelector('.add')?.addEventListener('click', () => this.#adicionar(e.id));
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
