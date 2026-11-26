/** Escapa texto antes de ir para o innerHTML (evita XSS). Igual ao do team.js. */
const esc = (texto) =>
  String(texto ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");

/** Ícones pixelados: frasco (poções e curas) e pedra (evolução). */
const ICONE_FRASCO = `<svg width="30" height="30" viewBox="0 0 26 26" aria-hidden="true">
    <rect x="10" y="2" width="6" height="5" fill="#3b2a1a"></rect>
    <rect x="5" y="7" width="16" height="17" rx="3" fill="var(--cor-cat)" stroke="#3b2a1a" stroke-width="2"></rect>
    <rect x="8" y="11" width="4" height="6" fill="#ffffff" opacity=".7"></rect></svg>`;
const ICONE_PEDRA = `<svg width="30" height="30" viewBox="0 0 34 34" aria-hidden="true">
    <path d="M17 3 L29 13 L24 30 H10 L5 13 Z" fill="var(--cor-cat)" stroke="#3b2a1a" stroke-width="2.5"></path>
    <path d="M11 13 L17 8 L20 13" stroke="#ffffff" stroke-width="2" fill="none" opacity=".8"></path></svg>`;

/**
 * Sprites dos itens no repositório da PokéAPI.
 * Chave = nome do item no nosso banco; valor = nome do arquivo na PokéAPI.
 * As pedras que não existem nos jogos usam a "gema" (cristal) daquele tipo.
 * Se alguma imagem não carregar, o card volta pro ícone desenhado.
 */
const SPRITES_ITENS =
  "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/";
const ITEM_POKEAPI = {
  Potion: "potion",
  "Super Potion": "super-potion",
  "Hyper Potion": "hyper-potion",
  "Max Potion": "max-potion",
  "Full Restore": "full-restore",
  Antidote: "antidote",
  "Burn Heal": "burn-heal",
  "Ice Heal": "ice-heal",
  Awakening: "awakening",
  "Paralyze Heal": "paralyze-heal",
  "Full Heal": "full-heal",
  Revive: "revive",
  "Pedra do Fogo": "fire-stone",
  "Pedra da Água": "water-stone",
  "Pedra da Folha": "leaf-stone",
  "Pedra do Trovão": "thunder-stone",
  "Pedra da Lua": "moon-stone",
  "Pedra do Gelo": "ice-stone",
  "Pedra Sombria": "dusk-stone",
  "Pedra Rochosa": "rock-gem",
  "Pedra da Terra": "ground-gem",
  "Pedra do Inseto": "bug-gem",
  "Pedra Normal": "normal-gem",
  "Pedra Venenosa": "poison-gem",
  "Pedra da Luta": "fighting-gem",
  "Pedra Psíquica": "psychic-gem",
  "Pedra Fantasma": "ghost-gem",
  "Pedra do Dragão": "dragon-gem",
  "Pedra do Vento": "flying-gem",
  "Pedra Metálica": "steel-gem",
};

/**
 * TelaLoja - Poké Mart.
 * Busca saldo, itens e mochila no back-end e compra com fetch (via ApiClient).
 */
class TelaLoja {
  static #CATEGORIAS = {
    cura_hp: "Cura de HP",
    cura_status: "Cura de condição",
    reviver: "Reviver",
    evolucao: "Evolução",
  };

  #api;
  #dados = { saldo: 0, itens: [], inventario: [] };
  #filtro = ""; // '' = todas as categorias

  constructor(api) {
    this.#api = api;
  }

  async iniciar() {
    try {
      this.#desenhar(await this.#api.get("/api/loja"));
    } catch (e) {
      this.#aviso(e.message, false);
    }
  }

  /** Compra assíncrona: o servidor devolve saldo e mochila já atualizados. */
  async #comprar(item, quantidade) {
    try {
      const dados = await this.#api.post("/api/loja/comprar", {
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

  /** Redesenha saldo, mochila, filtros e catálogo com os dados do servidor. */
  #desenhar(dados) {
    this.#dados = dados;
    document.getElementById("saldo").textContent = dados.saldo;
    this.#desenharMochila(dados.inventario);
    this.#desenharFiltros(dados.itens);
    this.#desenharLoja(dados.itens, dados.saldo);
  }

  static #nomeCategoria(cat) {
    return TelaLoja.#CATEGORIAS[cat] ?? cat;
  }

  static #icone(cat) {
    return cat === "evolucao" ? ICONE_PEDRA : ICONE_FRASCO;
  }

  /** Imagem do item na PokéAPI; sem sprite conhecido, usa o ícone desenhado. */
  static #imagem(item) {
    const arquivo = ITEM_POKEAPI[item.nome];
    if (!arquivo) return TelaLoja.#icone(item.categoria);
    return `<img class="sprite-item" src="${SPRITES_ITENS}${arquivo}.png" alt="">`;
  }

  /** Se a imagem da PokéAPI falhar (sem internet, arquivo inexistente), troca pelo ícone. */
  static #garantirIcones(alvo) {
    alvo.querySelectorAll("img.sprite-item").forEach((img) => {
      img.addEventListener(
        "error",
        () => {
          const cat = img.closest("[data-cat]")?.dataset.cat;
          img.outerHTML = TelaLoja.#icone(cat);
        },
        { once: true },
      );
    });
  }

  #desenharMochila(inventario) {
    const lista = document.getElementById("mochila");
    if (inventario.length === 0) {
      lista.innerHTML =
        '<li class="vazio">Sua mochila está vazia. Que tal começar com uma Potion?</li>';
      return;
    }
    lista.innerHTML = inventario
      .map(
        (i) => `
                <li data-cat="${esc(i.categoria)}" title="${esc(i.descricao)}">
                    <span class="icone">${TelaLoja.#imagem(i)}</span>
                    <span class="texto">
                        <strong>${esc(i.nome)}</strong>
                        <small>${esc(TelaLoja.#nomeCategoria(i.categoria))}</small>
                    </span>
                    <span class="qtd-mochila">×${Number(i.quantidade)}</span>
                </li>`,
      )
      .join("");
    TelaLoja.#garantirIcones(lista);
  }

  /** Botões de filtro: Todos + uma por categoria que existe na loja. */
  #desenharFiltros(itens) {
    const alvo = document.getElementById("filtros");
    if (!alvo) return; // página sem a área de filtros (loja.php antigo)
    const cats = [...new Set(itens.map((i) => i.categoria))];
    alvo.innerHTML = "";
    ["", ...cats].forEach((cat) => {
      const botao = document.createElement("button");
      botao.type = "button";
      botao.textContent = cat ? TelaLoja.#nomeCategoria(cat) : "Todos";
      if (cat) botao.dataset.cat = cat;
      if (cat === this.#filtro) botao.classList.add("ativo");
      botao.addEventListener("click", () => {
        this.#filtro = cat;
        this.#desenharFiltros(this.#dados.itens);
        this.#desenharLoja(this.#dados.itens, this.#dados.saldo);
      });
      alvo.appendChild(botao);
    });
  }

  /** Cards dos itens com seletor de quantidade; mostra "Faltam $ X" se não tiver saldo. */
  #desenharLoja(itens, saldo) {
    const loja = document.getElementById("loja");
    loja.innerHTML = "";

    itens
      .filter((item) => !this.#filtro || item.categoria === this.#filtro)
      .forEach((item) => {
        const card = document.createElement("div");
        card.className = "card-item";
        card.dataset.cat = item.categoria;
        card.innerHTML = `
                    <span class="icone">${TelaLoja.#imagem(item)}</span>
                    <strong>${esc(item.nome)}</strong>
                    <span class="chip-cat">${esc(TelaLoja.#nomeCategoria(item.categoria))}</span>
                    <p class="descricao">${esc(item.descricao)}</p>
                    <span class="preco"><span class="moeda" aria-hidden="true"></span>${Number(item.preco)}</span>
                    <div class="compra">
                        <div class="seletor">
                            <button type="button" class="menos" aria-label="Diminuir quantidade">−</button>
                            <input type="number" min="1" max="99" value="1" class="qtd" aria-label="Quantidade de ${esc(item.nome)}">
                            <button type="button" class="mais" aria-label="Aumentar quantidade">+</button>
                        </div>
                        <button type="button" class="comprar">Comprar</button>
                        <span class="falta" hidden></span>
                    </div>`;

        const campo = card.querySelector(".qtd");
        const comprar = card.querySelector(".comprar");
        const falta = card.querySelector(".falta");

        // mostra "Faltam $ X" quando a quantidade escolhida passa do saldo
        const atualizar = () => {
          const qtd = Math.min(99, Math.max(1, parseInt(campo.value, 10) || 1));
          campo.value = qtd;
          const total = qtd * item.preco;
          const semDinheiro = total > saldo;
          comprar.hidden = semDinheiro;
          falta.hidden = !semDinheiro;
          falta.textContent = `Faltam $ ${total - saldo}`;
          card.classList.toggle("caro", semDinheiro);
        };

        card.querySelector(".menos").addEventListener("click", () => {
          campo.value = Number(campo.value) - 1;
          atualizar();
        });
        card.querySelector(".mais").addEventListener("click", () => {
          campo.value = Number(campo.value) + 1;
          atualizar();
        });
        campo.addEventListener("input", atualizar);
        comprar.addEventListener("click", () =>
          this.#comprar(item, Number(campo.value)),
        );

        atualizar();
        TelaLoja.#garantirIcones(card);
        loja.appendChild(card);
      });
  }

  #aviso(texto, sucesso) {
    const el = document.getElementById("mensagem");
    el.textContent = texto;
    el.classList.toggle("sucesso", sucesso);
    el.classList.add("visivel");
    setTimeout(() => el.classList.remove("visivel"), 3500);
  }
}

new TelaLoja(new ApiClient(window.BASE)).iniciar();
