/**
 * ApiClient - POO em JavaScript.
 * Concentra todas as chamadas assíncronas (fetch + async/await) ao back-end
 * e o transporte de dados em JSON. As outras telas reutilizam esta classe.
 */
class ApiClient {
    #base; // atributo privado (encapsulamento)

    constructor(base = '') {
        this.#base = base;
    }

    async get(caminho) {
        return this.#requisitar(caminho, { method: 'GET' });
    }

    async post(caminho, dados = {}) {
        return this.#requisitar(caminho, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados),
        });
    }

    async #requisitar(caminho, opcoes) {
        const resposta = await fetch(this.#base + caminho, opcoes);
        const json = await resposta.json().catch(() => ({}));
        if (!resposta.ok || json.ok === false) {
            throw new Error(json.erro || `Erro ${resposta.status}`);
        }
        return json;
    }
}
