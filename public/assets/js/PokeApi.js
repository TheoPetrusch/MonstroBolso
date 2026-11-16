/**
 * PokeApi - consumo de API EXTERNA (https://pokeapi.co) direto do navegador.
 * Traz arte oficial, altura, peso, tipos e habilidades de um Pokémon.
 * Guarda as respostas em memória para não chamar a API duas vezes.
 */
class PokeApi {
    static #URL = 'https://pokeapi.co/api/v2/pokemon/';

    /** Nomes dos 18 tipos em português (iguais aos da tabela "tipos" do banco). */
    static #TIPOS = {
        normal: 'Normal', fighting: 'Lutador', flying: 'Voador', poison: 'Venenoso',
        ground: 'Terra', rock: 'Pedra', bug: 'Inseto', ghost: 'Fantasma',
        steel: 'Aço', fire: 'Fogo', water: 'Água', grass: 'Planta',
        electric: 'Elétrico', psychic: 'Psíquico', ice: 'Gelo', dragon: 'Dragão',
        dark: 'Sombrio', fairy: 'Fada',
    };

    #cache = new Map();

    async buscar(pokeapiId) {
        if (this.#cache.has(pokeapiId)) {
            return this.#cache.get(pokeapiId);
        }
        const resposta = await fetch(PokeApi.#URL + pokeapiId);
        if (!resposta.ok) {
            throw new Error('Não foi possível consultar a PokéAPI.');
        }
        const d = await resposta.json();
        const resumo = {
            arte: d.sprites?.other?.['official-artwork']?.front_default ?? d.sprites?.front_default,
            altura: d.height / 10,   // decímetros -> metros
            peso: d.weight / 10,     // hectogramas -> kg
            tipos: d.types.map(t => PokeApi.#TIPOS[t.type.name] ?? t.type.name),
            habilidades: d.abilities.map(a => a.ability.name),
        };
        this.#cache.set(pokeapiId, resumo);
        return resumo;
    }
}
