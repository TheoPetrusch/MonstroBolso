/** Escapa texto antes de ir para o innerHTML (evita XSS). Igual ao do team.js. */
const esc = (texto) =>
  String(texto ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");

/**
 * TelaBatalha - tela de batalha.
 * Cada clique manda UMA ação para o back-end (fetch assíncrono via ApiClient);
 * o PHP calcula o turno inteiro e devolve o estado novo em JSON.
 */
class TelaBatalha {
  static #SPRITES =
    "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/";

  #api;
  #ocupado = false; // evita mandar duas ações ao mesmo tempo
  #ultimoItem = null; // último item usado: já vem selecionado na próxima vez
  #necessarias = 10; // vitórias para o próximo líder (vem do servidor: GinasioRepository)

  constructor(api) {
    this.#api = api;
    document
      .getElementById("btn-iniciar")
      .addEventListener("click", () => this.#enviar("/api/batalha/iniciar"));
    document
      .getElementById("btn-centro")
      .addEventListener("click", () => this.#centroPokemon());
    document.getElementById("btn-fugir").addEventListener("click", () => {
      if (confirm("Fugir conta como derrota. Tem certeza?"))
        this.#enviar("/api/batalha/fugir");
    });
    document.getElementById("btn-usar").addEventListener("click", () => {
      this.#ultimoItem = Number(document.getElementById("item").value);
      this.#acao({
        tipo: "item",
        id: this.#ultimoItem,
        alvo_id: Number(document.getElementById("alvo").value),
      });
    });
  }

  /** Ao abrir a tela: pega a batalha em andamento (se houver), progresso e histórico. */
  async iniciar() {
    try {
      this.#desenhar(await this.#api.get("/api/batalha"));
    } catch (e) {
      this.#aviso(e.message);
    }
  }

  // ---------- comunicação ----------

  /** Manda UMA ação (golpe, item ou troca); o servidor joga o turno inteiro. */
  #acao(dados) {
    return this.#enviar("/api/batalha/acao", dados);
  }

  /** POST assíncrono; #ocupado evita dois cliques mandarem duas ações. */
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
      const r = await this.#api.post("/api/centro-pokemon");
      this.#aviso(
        r.curados > 0
          ? `Centro Pokémon: ${r.curados} Pokémon curados!`
          : "Seu time já estava 100%.",
        true,
      );
    } catch (e) {
      this.#aviso(e.message);
    }
  }

  // ---------- desenho ----------

  /** Redesenha a tela inteira com o estado que veio do servidor (JSON). */
  #desenhar(r) {
    const b = r.batalha;
    // o bloco de início (nova batalha, Centro Pokémon, ginásio) aparece
    // antes de começar E depois que a batalha termina
    document.getElementById("inicio").hidden = b !== null && !b.encerrada;
    document.getElementById("campo").hidden = b === null;
    document.getElementById("acoes").hidden = b === null || b.encerrada;
    this.#desenharHistorico(r.historico);
    this.#necessarias = r.progresso?.necessarias ?? this.#necessarias;
    this.#desenharGinasio(r.progresso);
    this.#desenharLendario(r.lendario);

    if (b === null) {
      document.getElementById("resultado").hidden = true;
      document.getElementById("log").innerHTML = "";
      return;
    }

    const meu = b.jogador.pokemons[b.jogador.ativo];
    const dele = b.oponente.pokemons[b.oponente.ativo];
    const deleDePe = b.oponente.pokemons.filter((p) => !p.desmaiado).length;

    const quem = b.lider
      ? `Líder de Ginásio · ${esc(b.oponente_nome)}`
      : esc(b.oponente_nome);
    // (na batalha lendária o nome já vem como "Lendário Mewtwo")
    document.getElementById("lado-oponente").innerHTML = this.#lado(
      dele,
      false,
      `${quem} · ${deleDePe}/${b.oponente.pokemons.length} de pé`,
    );
    document.getElementById("lado-jogador").innerHTML = this.#lado(
      meu,
      true,
      "Seu Pokémon",
    );

    this.#desenharLog(b.log);
    this.#desenharResultado(b, r.lendario);
    if (!b.encerrada) {
      this.#desenharGolpes(b.golpes);
      this.#desenharMochila(r.mochila, b.jogador.pokemons, meu);
      this.#desenharReservas(b.jogador);
    }
  }

  /** Um lado do campo: sprite, nome, tipo, barra de HP e condição. */
  #lado(p, costas, legenda) {
    const pasta = (costas ? "back/" : "") + (p.shiny ? "shiny/" : "");
    const pct = p.hp_max > 0 ? Math.round((p.hp_atual / p.hp_max) * 100) : 0;
    const cor = pct > 50 ? "" : pct > 20 ? " media" : " baixa";
    const estado = p.desmaiado
      ? "Desmaiado"
      : p.status !== "normal"
        ? p.status
        : "";
    return `
            <img src="${TelaBatalha.#SPRITES}${pasta}${Number(p.pokeapi_id)}.png" alt="${esc(p.nome)}"
                 class="${p.desmaiado ? "desmaiado" : ""}">
            <div class="info" data-tipo="${esc(p.tipo)}">
                <span class="reserva">${legenda}</span>
                <strong>${p.shiny ? "✨ " : ""}${esc(p.nome)}</strong>
                <span><span class="chip-tipo">${esc(p.tipo)}</span>
                      ${estado ? `<small class="estado">${esc(estado)}</small>` : ""}</span>
                <div class="hp">
                    <span>HP</span>
                    <div class="trilho"><span class="vida${cor}" style="width:${pct}%"></span></div>
                    <span>${Number(p.hp_atual)}/${Number(p.hp_max)}</span>
                </div>
            </div>`;
  }

  /** Um botão por golpe do Pokémon que está lutando (cor do tipo do golpe). */
  #desenharGolpes(golpes) {
    const alvo = document.getElementById("golpes");
    alvo.innerHTML = "";
    golpes.forEach((g) => {
      const botao = document.createElement("button");
      botao.type = "button";
      botao.dataset.tipo = g.tipo;
      botao.innerHTML = `${esc(g.nome)}
                <small>${esc(g.tipo)} · poder ${Number(g.poder)} · precisão ${Number(g.precisao)}%</small>`;
      botao.addEventListener("click", () =>
        this.#acao({ tipo: "golpe", id: g.id }),
      );
      alvo.appendChild(botao);
    });
  }

  /**
   * Já deixa selecionados o Pokémon que está lutando e o último item usado
   * (se ainda tiver na mochila), para não precisar escolher toda vez.
   */
  #desenharMochila(mochila, pokemons, ativo) {
    const item = document.getElementById("item");
    const alvo = document.getElementById("alvo");
    item.innerHTML = mochila.length
      ? mochila
          .map(
            (i) =>
              `<option value="${i.item_id}">${esc(i.nome)} (${i.quantidade})</option>`,
          )
          .join("")
      : '<option value="0">Mochila vazia</option>';
    alvo.innerHTML = pokemons
      .map(
        (p) =>
          `<option value="${p.id}">${esc(p.nome)} (${p.hp_atual}/${p.hp_max})</option>`,
      )
      .join("");
    alvo.value = ativo.id;
    if (mochila.some((i) => Number(i.item_id) === this.#ultimoItem)) {
      item.value = this.#ultimoItem;
    }
    document.getElementById("btn-usar").disabled = mochila.length === 0;
  }

  /** Botões para trocar pelo Pokémon reserva (desmaiados ficam desativados). */
  #desenharReservas(jogador) {
    const alvo = document.getElementById("reservas");
    alvo.innerHTML = "";
    jogador.pokemons.forEach((p) => {
      if (p.indice === jogador.ativo) return;
      const botao = document.createElement("button");
      botao.type = "button";
      botao.className = "sec";
      botao.textContent = `${p.nome} (${p.hp_atual}/${p.hp_max})`;
      botao.disabled = p.desmaiado;
      botao.addEventListener("click", () =>
        this.#acao({ tipo: "trocar", id: p.id }),
      );
      alvo.appendChild(botao);
    });
    if (!alvo.children.length)
      alvo.innerHTML = "<small>Ninguém para trocar.</small>";
  }

  /** Log da batalha: o que aconteceu em cada turno (vem da composição Batalha -> Turno). */
  #desenharLog(log) {
    const lista = document.getElementById("log");
    lista.innerHTML = log
      .map(
        (t) =>
          `<li class="${esc(t.ator)}" value="${Number(t.turno)}">${esc(t.mensagem)}</li>`,
      )
      .join("");
    lista.scrollTop = lista.scrollHeight; // mostra sempre o mais recente
  }

  /** Mensagem de vitória/derrota: prêmio, insígnia ou lendário desbloqueado. */
  #desenharResultado(b, lendario) {
    const alvo = document.getElementById("resultado");
    alvo.hidden = !b.encerrada;
    if (!b.encerrada) return;

    const venceu = b.vencedor === "jogador";
    let texto = venceu
      ? `Você venceu! Ganhou $ ${Number(b.premio)}.`
      : "Você perdeu a batalha.";
    if (b.lider && venceu) {
      texto += ` Você conquistou a ${esc(b.lider.insignia)}!`;
    } else if (b.lider) {
      texto += ` Vença mais ${this.#necessarias} treinadores para desafiar o líder de novo.`;
    } else if (b.lendario && venceu) {
      texto +=
        b.lendario_desbloqueia !== b.lendario
          ? ` ${esc(b.lendario_desbloqueia)} foi desbloqueado: coloque-o no time e evolua com pedra até ${esc(b.lendario)}!`
          : ` ${esc(b.lendario)} foi desbloqueado: coloque-o no time pelo Team Builder!`;
    } else if (b.lendario) {
      texto += ` ${esc(b.lendario)} escapou. Vença mais ${Number(lendario?.necessarias ?? 8)} treinadores para tentar outro lendário.`;
    }
    alvo.className = "resultado " + (venceu ? "vitoria" : "derrota");
    alvo.innerHTML = `
            <span>${texto}</span>
            <span>Seus Pokémon saem com o HP que sobrou: cure de graça no Centro Pokémon antes da próxima.</span>`;
  }

  /** Insígnias, vitórias até o próximo líder e o botão de desafiar. */
  #desenharGinasio(p) {
    const alvo = document.getElementById("ginasio");
    if (!p) return;

    let html = `<p><strong>Insígnias: ${Number(p.insignias)}/${Number(p.total_lideres)}</strong></p>`;
    if (!p.proximo) {
      alvo.innerHTML =
        html +
        "<p>Você venceu todos os líderes de ginásio. Parabéns, campeão!</p>";
      return;
    }
    const pct = Math.round((p.vitorias / p.necessarias) * 100);
    html += `
            <p>Próximo ginásio: <strong>${esc(p.proximo.nome)}</strong>
               <span class="chip-tipo" data-tipo="${esc(p.proximo.tipo)}">${esc(p.proximo.tipo)}</span>
               · ${Number(p.proximo.quantidade)} Pokémon · prêmio $ ${Number(p.proximo.premio)}</p>
            <p class="dica">Contra o líder, lutam só os ${Number(p.proximo.quantidade)} primeiros Pokémon
               de pé do seu time (na ordem dos slots).</p>
            <div class="hp">
                <span>Vitórias</span>
                <div class="trilho"><span class="vida" style="width:${pct}%"></span></div>
                <span>${Number(p.vitorias)}/${Number(p.necessarias)}</span>
            </div>
            <button type="button" id="btn-lider" ${p.liberado ? "" : "disabled"}
                    title="${p.liberado ? "" : "Vença " + Number(p.necessarias) + " treinadores para liberar"}">
                Desafiar ${esc(p.proximo.nome)}
            </button>`;
    alvo.innerHTML = html;
    alvo
      .querySelector("#btn-lider")
      .addEventListener("click", () =>
        this.#enviar("/api/batalha/iniciar", { lider: true }),
      );
  }

  /** Lendários desbloqueados, vitórias até a próxima batalha lendária e o botão de desafiar. */
  #desenharLendario(p) {
    const alvo = document.getElementById("lendario");
    if (!p) return;

    let html = `<p><strong>Lendários desbloqueados: ${Number(p.capturados)}/${Number(p.total)}</strong></p>`;
    if (p.total === 0) {
      alvo.innerHTML =
        html +
        '<p class="dica">Nenhum lendário no jogo ainda: importe uma geração na Home.</p>';
      return;
    }
    if (p.restantes === 0) {
      alvo.innerHTML =
        html + "<p>Você desbloqueou todos os lendários importados!</p>";
      return;
    }
    const pct = Math.round((p.vitorias / p.necessarias) * 100);
    html += `
            <p class="dica">A cada ${Number(p.necessarias)} vitórias contra treinadores, enfrente um lendário
               sorteado (com HP extra) contra o seu time inteiro. Vencendo, ele fica desbloqueado.</p>
            <div class="hp">
                <span>Vitórias</span>
                <div class="trilho"><span class="vida" style="width:${pct}%"></span></div>
                <span>${Number(p.vitorias)}/${Number(p.necessarias)}</span>
            </div>
            <button type="button" id="btn-lendario" ${p.liberado ? "" : "disabled"}
                    title="${p.liberado ? "" : "Vença " + Number(p.necessarias) + " treinadores para liberar"}">
                Desafiar um lendário
            </button>`;
    alvo.innerHTML = html;
    alvo
      .querySelector("#btn-lendario")
      .addEventListener("click", () =>
        this.#enviar("/api/batalha/iniciar", { lendario: true }),
      );
  }

  /** Tabela das últimas batalhas (vem da view vw_historico_batalhas). */
  #desenharHistorico(historico) {
    const alvo = document.getElementById("historico");
    if (!historico.length) {
      alvo.innerHTML = '<p class="dica">Nenhuma batalha ainda.</p>';
      return;
    }
    alvo.innerHTML = `
            <table class="tabela-historico">
                <tr><th>Quando</th><th>Oponente</th><th>Resultado</th><th>Prêmio</th><th>Turnos</th></tr>
                ${historico
                  .map(
                    (h) => `
                    <tr>
                        <td>${esc(h.data_batalha)}</td>
                        <td>${esc(h.oponente_nome)}</td>
                        <td>${h.vencedor === "jogador" ? "Vitória" : "Derrota"}</td>
                        <td>$ ${Number(h.dinheiro_ganho)}</td>
                        <td>${Number(h.total_turnos)}</td>
                    </tr>`,
                  )
                  .join("")}
            </table>`;
  }

  #aviso(texto, sucesso = false) {
    const el = document.getElementById("mensagem");
    el.textContent = texto;
    el.classList.toggle("sucesso", sucesso);
    el.classList.add("visivel");
    setTimeout(() => el.classList.remove("visivel"), 3500);
  }
}

new TelaBatalha(new ApiClient(window.BASE)).iniciar();
