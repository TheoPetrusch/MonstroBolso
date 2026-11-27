/** Escapa texto antes de ir para o innerHTML (evita XSS: nome com <script> vira texto). */
const esc = (texto) => String(texto ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

/**
 * TeamBuilder - tela do Team Builder (POO em JavaScript).
 * Atributos com # são PRIVADOS (encapsulamento). Toda ação chama a API
 * com fetch assíncrono (via ApiClient), recebe o time atualizado em JSON
 * e redesenha a tela (#desenharTudo).
 */
class TeamBuilder {
    #api;          // ApiClient: conversa com o nosso back-end (PHP)
    #pokeApi;      // PokeApi: consulta a PokéAPI externa (janela de detalhes)
    #especies = []; // catálogo (todas as espécies, com "disponivel")
    #time = { maximo: 6, pokemons: [] };
    #evolucoes = []; // botões "Evoluir" possíveis (vêm da vw_evolucoes)
    #mochila = [];   // itens de cura do jogador
    #shinies = []; // ids das espécies com shiny desbloqueado   
    #filtroTipo = ''; 

    constructor(api, pokeApi) {
        this.#api = api;
        this.#pokeApi = pokeApi;
        // eventos pelas propriedades on... (oninput, onclick) de cada elemento
        document.getElementById('busca').oninput = () => this.#desenharCatalogo();
        document.getElementById('btn-limpar').onclick = () => this.#limpar();
        document.getElementById('btn-centro').onclick = () => this.#centroPokemon();
        document.getElementById('fechar-detalhes').onclick = () => document.getElementById('janela-detalhes').close();
    }

    /** Carrega catálogo e time AO MESMO TEMPO (Promise.all: duas requisições em paralelo). */
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
            this.#shinies = time.shinies;
            this.#desenharTudo();
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    /** Adiciona a espécie ao time; o servidor sorteia se ela nasce shiny. */
    async #adicionar(especieId) {
        try {
            const r = await this.#api.post('/api/time/adicionar', { especie_id: especieId });
            this.#time = r.time;
            this.#evolucoes = r.evolucoes;
            this.#mochila = r.mochila;
            this.#shinies = r.shinies;
            this.#desenharTudo();
            if (r.shiny) {
                const nome = this.#especies.find(e => e.id === especieId)?.nome ?? 'Pokémon';
                this.#aviso(`✨ Uau! Seu ${nome} é shiny! Agora o shiny dele está desbloqueado para sempre.`, true);
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
            this.#shinies = r.shinies;
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
            this.#shinies = r.shinies;
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
            this.#shinies = r.shinies;
            this.#desenharTudo();
            this.#aviso(r.curados > 0
                ? `Centro Pokémon: ${r.curados} Pokémon curados!`
                : 'Seu time já estava 100%.', true);
        } catch (e) {
            this.#aviso(e.message);
        }
    }

    /** Deixa shiny ou volta ao normal (só para espécies com shiny desbloqueado). */
    async #alternarShiny(pokemon) {
        try {
            const r = await this.#api.post('/api/time/shiny', { pokemon_id: pokemon.id });
            this.#time = r.time;
            this.#evolucoes = r.evolucoes;
            this.#mochila = r.mochila;
            this.#shinies = r.shinies;
            this.#desenharTudo();
            this.#aviso(r.shiny ? `✨ ${pokemon.nome} agora é shiny!` : `${pokemon.nome} voltou à cor normal.`, true);
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
            this.#shinies = r.shinies;
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
            this.#shinies = r.shinies;
            this.#desenharTudo();
            this.#aviso(`Usou ${item?.nome ?? 'o item'} em ${pokemon.nome}!`, true);
        } catch (e) {
            this.#aviso(e.message); 
        }
    }

    /** Janela de detalhes: consome a PokéAPI direto do navegador (arte, altura, peso, habilidades). */
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
                    <div>${d.tipos.map(t => `<span class="chip-tipo" data-tipo="${esc(t)}">${esc(t)}</span>`).join(' ')}</div>
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

    

    // ---------- desenho da tela ----------

    /** Redesenha tudo a partir dos dados atuais (chamado depois de cada ação). */
    #desenharTudo() {
        this.#desenharTime();
        this.#desenharResumo();
        this.#desenharFiltros();
        this.#desenharCatalogo();
    }

    /** Os 6 slots do time: ocupados (com botões) ou vazios. */
    #desenharTime() {
        document.getElementById('contagem').textContent = this.#time.pokemons.length;
        document.getElementById('btn-limpar').disabled = this.#time.pokemons.length === 0;
        const slots = document.getElementById('slots');

        // monta o HTML dos 6 slots como texto (template string) e põe de uma vez
        let html = '';
        for (let i = 1; i <= this.#time.maximo; i++) {
            const p = this.#time.pokemons.find(x => x.posicao === i);
            if (!p) {
                html += `<div class="slot"><span class="vazio"><span class="mais">+</span>Slot ${i} vazio</span></div>`;
                continue;
            }
            html += `
                <div class="slot ocupado${p.shiny ? ' shiny' : ''}" data-tipo="${esc(p.especie.tipo)}">
                    <span class="numero">${i}</span>
                    <img src="${esc(p.shiny ? TeamBuilder.#spriteShiny(p.especie.pokeapi_id) : p.especie.sprite_url)}"
                         alt="${esc(p.nome)}">
                    <strong>${p.shiny ? '✨ ' : ''}${esc(p.nome)}</strong>
                    <span class="chip-tipo">${esc(p.especie.tipo)}</span>
                    ${TeamBuilder.#barraHp(p.hp_atual, p.hp_max)}
                    ${TeamBuilder.#estado(p)}
                    <div class="evolucoes">${this.#htmlEvolucoes(p)}${this.#htmlShiny(p)}</div>
                    <div class="usar-item">${this.#htmlUsarItem(p)}</div>
                    <button type="button" class="perigo" data-acao="remover" data-pokemon="${p.id}">Remover</button>
                </div>`;
        }
        slots.innerHTML = html;

        // liga cada botão à sua ação pela propriedade onclick
        // (data-pokemon diz de qual Pokémon é o botão)
        const pokemonDo = (botao) => this.#time.pokemons.find(x => x.id === Number(botao.dataset.pokemon));
        slots.querySelectorAll('[data-acao="remover"]').forEach(b => {
            b.onclick = () => this.#remover(Number(b.dataset.pokemon));
        });
        slots.querySelectorAll('[data-acao="evoluir"]').forEach(b => {
            b.onclick = () => this.#evoluir(pokemonDo(b), this.#evolucoes[Number(b.dataset.opcao)]);
        });
        slots.querySelectorAll('[data-acao="shiny"]').forEach(b => {
            b.onclick = () => this.#alternarShiny(pokemonDo(b));
        });
        slots.querySelectorAll('[data-acao="usar"]').forEach(b => {
            const select = b.parentElement.querySelector('select');
            b.onclick = () => this.#usarItem(pokemonDo(b), Number(select.value));
        });
    }

    /** Botões "Evoluir: X" do Pokémon (data-opcao = posição da opção em #evolucoes). */
    #htmlEvolucoes(pokemon) {
        return this.#evolucoes
            .map((o, indice) => ({ o, indice }))
            .filter(({ o }) => o.pokemon_id === pokemon.id)
            .map(({ o, indice }) => o.quantidade > 0
                ? `<button type="button" class="sec" data-acao="evoluir" data-pokemon="${pokemon.id}"
                           data-opcao="${indice}" title="Usa 1 ${esc(o.item_nome)} (você tem ${Number(o.quantidade)})">Evoluir: ${esc(o.para_nome)}</button>`
                : `<button type="button" class="sec" disabled
                           title="Precisa de ${esc(o.item_nome)}: compre no Poké Mart">Evoluir: ${esc(o.para_nome)}</button>`)
            .join('');
    }

    /** Botão "Deixar shiny" / "Voltar ao normal", igual aos botões de evoluir. */
    #htmlShiny(pokemon) {
        if (!this.#shinies.includes(pokemon.especie.id)) return '';
        return `<button type="button" class="sec shiny-btn" data-acao="shiny" data-pokemon="${pokemon.id}">
                    ${pokemon.shiny ? 'Voltar ao normal' : 'Deixar shiny'}</button>`;
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

    /**
     * Resumo do time: média de cada atributo, ponto forte e fraco, tipos que
     * faltam e uma SUGESTÃO de Pokémon disponível que reforça o ponto fraco.
     */
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

        const sugestao = this.#especies
            .filter(e => e.disponivel && !this.#linhasNoTime().has(e.linha))
            .sort((a, b) => Number(b[fraco.campo]) - Number(a[fraco.campo]))[0];

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
            </div>
            ${sugestao ? `<p class="dica">Pra reforçar ${fraco.nome}, que tal um <strong>${esc(sugestao.nome)}</strong> (${fraco.nome} ${Number(sugestao[fraco.campo])})?</p>` : ''}`;
    }

    /** Botões de filtro por tipo (Todos, Fogo, Água...). */
    #desenharFiltros() {
        const alvo = document.getElementById('filtros');
        const tipos = [...new Set(this.#especies.map(e => e.tipo))];
        alvo.innerHTML = ['', ...tipos]
            .map(tipo => `<button type="button" data-filtro="${esc(tipo)}"
                                  ${tipo ? `data-tipo="${esc(tipo)}"` : ''}
                                  class="${tipo === this.#filtroTipo ? 'ativo' : ''}">${esc(tipo || 'Todos')}</button>`)
            .join('');
        alvo.querySelectorAll('button').forEach(b => {
            b.onclick = () => {
                this.#filtroTipo = b.dataset.filtro;
                this.#desenharFiltros();
                this.#desenharCatalogo();
            };
        });
    }

    static #estado(p) {
        if (p.desmaiado) return '<small class="estado">Desmaiado</small>';
        if (p.status !== 'normal') return `<small class="estado">${esc(p.status)}</small>`;
        return '';
    }

    
    /** Select com os itens de cura da mochila + botão "Usar" em cada slot. */
    #htmlUsarItem(pokemon) {
        if (this.#mochila.length === 0) return '';
        const opcoes = this.#mochila
            .map(i => `<option value="${Number(i.item_id)}" title="${esc(i.descricao)}">${esc(i.nome)} (${Number(i.quantidade)})</option>`)
            .join('');
        return `<select>${opcoes}</select>
                <button type="button" class="sec" data-acao="usar" data-pokemon="${pokemon.id}">Usar</button>`;
    }

    /**
     * Catálogo: filtra por tipo e pela busca. Espécies bloqueadas (evoluções
     * e lendários) aparecem escuras e sem botão "Adicionar".
     */
    #desenharCatalogo() {
        const termo = document.getElementById('busca').value.trim().toLowerCase();
        const cheio = this.#time.pokemons.length >= this.#time.maximo;
        const linhasNoTime = this.#linhasNoTime();
        const catalogo = document.getElementById('catalogo');

        const visiveis = this.#especies
            .filter(e => !this.#filtroTipo || e.tipo === this.#filtroTipo)
            .filter(e => !termo || e.nome.toLowerCase().includes(termo)
                || e.tipo.toLowerCase().includes(termo));

        // cada card vira texto HTML; data-especie diz de qual espécie é o botão
        catalogo.innerHTML = visiveis.map(e => {
                const shiny = TeamBuilder.#spriteShiny(e.pokeapi_id);
                const barra = (valor) => `<div class="barra"><span style="width:${Math.min(valor / 1.5, 100)}%"></span></div>`;

                return `
                <div class="card-poke${e.disponivel ? '' : ' bloqueado'}" data-tipo="${esc(e.tipo)}">
                    <div class="sprite">
                        <img class="normal" src="${esc(e.sprite_url)}" alt="${esc(e.nome)}">
                        <img class="shiny" src="${shiny}" alt="">
                    </div>
                    <strong>${esc(e.nome)}</strong>
                    <span class="chip-tipo">${esc(e.tipo)}</span>
                    ${e.disponivel
                        ? `<small>HP ${e.hp} &middot; Vel ${e.velocidade}</small>`
                        : (e.lendario
                            ? '<span class="dica">Lendário: vença-o numa batalha lendária para desbloqueá-lo</span>'
                            : '<span class="dica">Evolua o Pokémon para desbloqueá-lo</span>')}
                    <div class="previa">
                        HP ${e.hp} ${barra(e.hp)}
                        Ataque ${e.ataque} ${barra(e.ataque)}
                        Defesa ${e.defesa} ${barra(e.defesa)}
                        Vel ${e.velocidade} ${barra(e.velocidade)}
                    </div>
                    <div class="acoes">
                        <button type="button" class="sec" data-acao="detalhes" data-especie="${e.id}">Detalhes</button>
                        ${e.disponivel
                            ? `<button type="button" class="add" data-acao="adicionar" data-especie="${e.id}"
                                       ${cheio || linhasNoTime.has(e.linha) ? 'disabled' : ''}
                                       ${linhasNoTime.has(e.linha)
                                           ? 'title="Seu time já tem um Pokémon dessa linha evolutiva"'
                                           : (e.lendario ? 'title="Lendário desbloqueado em batalha"' : (e.basica ? '' : 'title="Desbloqueado por evolução"'))}>Adicionar</button>`
                            : ''}
                    </div>
                </div>`;
            }).join('');

        const especieDo = (botao) => this.#especies.find(x => x.id === Number(botao.dataset.especie));
        catalogo.querySelectorAll('[data-acao="detalhes"]').forEach(b => {
            b.onclick = () => this.#mostrarDetalhes(especieDo(b));
        });
        catalogo.querySelectorAll('[data-acao="adicionar"]').forEach(b => {
            b.onclick = () => this.#adicionar(Number(b.dataset.especie));
        });
    }

    /** Linhas evolutivas que já estão no time (ex.: a do Charmander). */
    #linhasNoTime() {
        return new Set(this.#time.pokemons.map(p =>
            this.#especies.find(e => e.id === p.especie.id)?.linha ?? p.especie.id));
    }

    /** Mensagem flutuante (toast) por 3,5 s: vermelha = erro, verde = sucesso. */
    #aviso(texto, sucesso = false) {
        const el = document.getElementById('mensagem');
        el.textContent = texto;
        el.classList.toggle('sucesso', sucesso);
        el.classList.add('visivel');
        setTimeout(() => el.classList.remove('visivel'), 3500);
    }
}

// cria a tela e já carrega os dados (window.BASE vem da view team.php)
new TeamBuilder(new ApiClient(window.BASE), new PokeApi()).iniciar();
