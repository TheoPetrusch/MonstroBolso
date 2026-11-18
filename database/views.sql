-- =====================================================================
-- MONSTROBOLSO - views.sql  (DDL de views + DQL)
-- Rodar depois do schema.sql e do seed.sql.
-- =====================================================================
USE monstrobolso;

-- 1) Time completo do jogador (JOIN de 5 tabelas) -> Team Builder / Batalha
--    Traz tudo o que o PHP precisa para montar Pokemon + Especie,
--    então o TimeRepository só faz SELECT * FROM vw_time_completo.
CREATE
OR REPLACE VIEW vw_time_completo AS
SELECT
    t.usuario_id,
    tp.posicao,
    -- dados do Pokémon (instância)
    p.id AS pokemon_id,
    p.apelido,
    COALESCE(p.apelido, e.nome) AS nome_exibicao,
    p.nivel,
    p.hp_atual,
    p.hp_max,
    p.status,
    p.desmaiado,
    p.shiny,
    -- dados da espécie (stats base)
    e.id AS especie_id,
    e.pokeapi_id,
    e.nome AS especie,
    e.evolui_de_id, -- NULL = espécie básica
    ti.nome AS tipo,
    e.hp,
    e.ataque,
    e.defesa,
    e.sp_atk,
    e.sp_def,
    e.velocidade,
    e.sprite_url
FROM
    time_pokemon tp
    JOIN times t ON t.id = tp.time_id
    JOIN pokemons p ON p.id = tp.pokemon_id
    JOIN especies e ON e.id = p.especie_id
    JOIN tipos ti ON ti.id = e.tipo_id;

-- 2) Histórico de batalhas (log) com nome do jogador
CREATE
OR REPLACE VIEW vw_historico_batalhas AS
SELECT
    b.id,
    u.nome AS jogador,
    b.usuario_id,
    b.oponente_nome,
    b.vencedor,
    b.dinheiro_ganho,
    b.total_turnos,
    b.data_batalha
FROM
    batalhas b
    JOIN usuarios u ON u.id = b.usuario_id;

-- 3) Ranking: vitórias, derrotas e dinheiro (GROUP BY + agregação)
CREATE
OR REPLACE VIEW vw_ranking AS
SELECT
    u.id AS usuario_id,
    u.nome,
    u.pokedollar,
    COUNT(b.id) AS total_batalhas,
    COALESCE(SUM(b.vencedor = 'jogador'), 0) AS vitorias,
    COALESCE(SUM(b.vencedor = 'oponente'), 0) AS derrotas,
    COALESCE(SUM(b.dinheiro_ganho), 0) AS total_ganho
FROM
    usuarios u
    LEFT JOIN batalhas b ON b.usuario_id = u.id
WHERE
    u.perfil = 'jogador'
GROUP BY
    u.id,
    u.nome,
    u.pokedollar
ORDER BY
    vitorias DESC,
    total_ganho DESC;

-- 4) Inventário do usuário com dados do item
CREATE
OR REPLACE VIEW vw_inventario AS
SELECT
    inv.usuario_id,
    i.id AS item_id,
    i.nome,
    i.categoria,
    i.descricao,
    inv.quantidade
FROM
    inventario inv
    JOIN itens i ON i.id = inv.item_id
WHERE
    inv.quantidade > 0;

-- 5) Catálogo da loja (Poké Mart)
CREATE
OR REPLACE VIEW vw_loja AS
SELECT
    id,
    nome,
    categoria,
    descricao,
    preco
FROM
    itens
ORDER BY
    FIELD (
        categoria,
        'cura_hp',
        'cura_status',
        'reviver',
        'evolucao'
    ),
    preco;

-- 6) Gastos por usuário na loja (log_loja)
CREATE
OR REPLACE VIEW vw_gastos_loja AS
SELECT
    u.id AS usuario_id,
    u.nome,
    COUNT(l.id) AS compras,
    COALESCE(SUM(l.valor_total), 0) AS total_gasto
FROM
    usuarios u
    LEFT JOIN log_loja l ON l.usuario_id = u.id
GROUP BY
    u.id,
    u.nome;

-- 7) Evoluções possíveis: qual pedra leva cada espécie a qual evolução.
--    Regra: a pedra é a do TIPO DA EVOLUÇÃO (itens.tipo_id = tipo do filho).
--    Ex.: Charmander -> Charmeleon (Fogo): Pedra do Fogo.
--         Eevee -> Vaporeon (Água): Pedra da Água; -> Jolteon: Pedra do Trovão...
--    Se duas evoluções têm o mesmo tipo (Gloom -> Vileplume e Bellossom,
--    ambos Planta), a mesma pedra serve para as duas e o jogador escolhe.
CREATE
OR REPLACE VIEW vw_evolucoes AS
SELECT
    pai.id AS especie_id,
    filho.id AS para_especie_id,
    filho.nome AS para_nome,
    i.id AS item_id,
    i.nome AS item_nome
FROM
    especies filho
    JOIN especies pai ON pai.id = filho.evolui_de_id
    JOIN itens i ON i.categoria = 'evolucao'
    AND i.tipo_id = filho.tipo_id;
