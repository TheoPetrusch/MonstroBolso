/**
 * TelaHome - menu inicial do jogador.
 * Qualquer jogador pode importar uma geração de Pokémon da PokéAPI.
 */
class TelaHome {
  #api;

  constructor(api) {
    this.#api = api;
    document
      .getElementById("btn-sair")
      .addEventListener("click", () => this.#sair());

    document
      .getElementById("btn-importar")
      .addEventListener("click", (e) => this.#importar(e.currentTarget));
  }

  async #sair() {
    try {
      await this.#api.post("/api/logout");
    } finally {
      location.href = window.BASE + "/";
    }
  }

  async #importar(botao) {
    const status = document.getElementById("status-importacao");
    const select = document.getElementById("geracao");
    const geracao = Number(select.value);

    botao.disabled = select.disabled = true;
    status.textContent =
      `Importando a ${geracao}ª geração da PokéAPI... ` +
      "pode levar alguns minutos, não feche a página.";
    try {
      const r = await this.#api.post("/api/importar", { geracao });
      let texto =
        `${geracao}ª geração: ${r.importadas} espécies importadas. ` +
        `${r.evolucoes} evoluções no jogo.`;
      if (r.pre_evolucoes.length) {
        texto += ` Pré-evoluções de outras gerações que vieram junto: ${r.pre_evolucoes.join(", ")}.`;
      }
      status.textContent = texto;
    } catch (erro) {
      status.textContent = "Erro: " + erro.message;
    } finally {
      botao.disabled = select.disabled = false;
    }
  }
}

new TelaHome(new ApiClient(window.BASE));
