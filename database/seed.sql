-- =====================================================================
-- MONSTROBOLSO - seed.sql  (DML: INSERT / UPDATE)
-- Rodar depois do schema.sql.
-- Usuários de teste:
--   jogador@teste.com / jogador123   (perfil jogador)
--   admin@teste.com   / admin123     (perfil admin)
-- =====================================================================
USE monstrobolso;

-- ---------------------------------------------------------------------
-- Usuários de teste (hash gerado com password_hash do PHP)
-- ---------------------------------------------------------------------
INSERT INTO
    usuarios (nome, email, senha_hash, perfil, pokedollar)
VALUES
    (
        'Ash',
        'jogador@teste.com',
        '$2y$10$baMBSBVbZwQ0ynDWYSDDfutm7/Kl0C1fvcUdQYxv4uYBcXCSdcri2',
        'jogador',
        5000
    ),
    (
        'Oak',
        'admin@teste.com',
        '$2y$10$7waTRHdm2lIFB9lD5SdaFO0ns/bmjEhxloaS0cB2yGJt6uD3Cb3vO',
        'admin',
        9999
    );

-- ---------------------------------------------------------------------
-- Tipos
-- ---------------------------------------------------------------------
INSERT INTO
    tipos (nome)
VALUES
    ('Normal'),
    ('Fogo'),
    ('Água'),
    ('Planta'),
    ('Elétrico'),
    ('Terra'),
    ('Gelo'),
    ('Voador');

-- Vantagens (2.0 = super efetivo, 0.5 = pouco efetivo, 0.0 = imune)
INSERT INTO
    tipo_vantagens (tipo_atacante_id, tipo_defensor_id, multiplicador)
SELECT
    a.id,
    d.id,
    v.m
FROM
    (
        SELECT
            'Fogo' AS a,
            'Planta' AS d,
            2.0 AS m
        UNION ALL
        SELECT
            'Fogo',
            'Gelo',
            2.0
        UNION ALL
        SELECT
            'Fogo',
            'Água',
            0.5
        UNION ALL
        SELECT
            'Fogo',
            'Fogo',
            0.5
        UNION ALL
        SELECT
            'Água',
            'Fogo',
            2.0
        UNION ALL
        SELECT
            'Água',
            'Terra',
            2.0
        UNION ALL
        SELECT
            'Água',
            'Água',
            0.5
        UNION ALL
        SELECT
            'Água',
            'Planta',
            0.5
        UNION ALL
        SELECT
            'Planta',
            'Água',
            2.0
        UNION ALL
        SELECT
            'Planta',
            'Terra',
            2.0
        UNION ALL
        SELECT
            'Planta',
            'Fogo',
            0.5
        UNION ALL
        SELECT
            'Planta',
            'Planta',
            0.5
        UNION ALL
        SELECT
            'Planta',
            'Voador',
            0.5
        UNION ALL
        SELECT
            'Elétrico',
            'Água',
            2.0
        UNION ALL
        SELECT
            'Elétrico',
            'Voador',
            2.0
        UNION ALL
        SELECT
            'Elétrico',
            'Planta',
            0.5
        UNION ALL
        SELECT
            'Elétrico',
            'Elétrico',
            0.5
        UNION ALL
        SELECT
            'Elétrico',
            'Terra',
            0.0
        UNION ALL
        SELECT
            'Terra',
            'Fogo',
            2.0
        UNION ALL
        SELECT
            'Terra',
            'Elétrico',
            2.0
        UNION ALL
        SELECT
            'Terra',
            'Planta',
            0.5
        UNION ALL
        SELECT
            'Terra',
            'Voador',
            0.0
        UNION ALL
        SELECT
            'Gelo',
            'Planta',
            2.0
        UNION ALL
        SELECT
            'Gelo',
            'Terra',
            2.0
        UNION ALL
        SELECT
            'Gelo',
            'Voador',
            2.0
        UNION ALL
        SELECT
            'Gelo',
            'Fogo',
            0.5
        UNION ALL
        SELECT
            'Gelo',
            'Água',
            0.5
        UNION ALL
        SELECT
            'Voador',
            'Planta',
            2.0
        UNION ALL
        SELECT
            'Voador',
            'Elétrico',
            0.5
    ) v
    JOIN tipos a ON a.nome = v.a
    JOIN tipos d ON d.nome = v.d;

-- ---------------------------------------------------------------------
-- Itens da loja (todos os exigidos no enunciado)
-- ---------------------------------------------------------------------
INSERT INTO
    itens (
        nome,
        categoria,
        descricao,
        preco,
        cura_hp,
        cura_percentual,
        status_curado,
        cura_todos_status
    )
VALUES
    -- Restauradores de HP
    (
        'Potion',
        'cura_hp',
        'Recupera 20 HP.',
        200,
        20,
        0,
        NULL,
        0
    ),
    (
        'Super Potion',
        'cura_hp',
        'Recupera 50 HP.',
        700,
        50,
        0,
        NULL,
        0
    ),
    (
        'Hyper Potion',
        'cura_hp',
        'Recupera 200 HP.',
        1500,
        200,
        0,
        NULL,
        0
    ),
    (
        'Max Potion',
        'cura_hp',
        'Recupera 100% do HP.',
        2500,
        0,
        100,
        NULL,
        0
    ),
    (
        'Full Restore',
        'cura_hp',
        'Recupera 100% do HP e cura todas as condições.',
        3000,
        0,
        100,
        NULL,
        1
    ),
    -- Cura de condição
    (
        'Antidote',
        'cura_status',
        'Cura envenenamento.',
        100,
        0,
        0,
        'veneno',
        0
    ),
    (
        'Burn Heal',
        'cura_status',
        'Cura queimadura.',
        250,
        0,
        0,
        'queimado',
        0
    ),
    (
        'Ice Heal',
        'cura_status',
        'Cura congelamento.',
        250,
        0,
        0,
        'congelado',
        0
    ),
    (
        'Awakening',
        'cura_status',
        'Acorda um Pokémon dormindo.',
        250,
        0,
        0,
        'dormindo',
        0
    ),
    (
        'Paralyze Heal',
        'cura_status',
        'Cura paralisia.',
        200,
        0,
        0,
        'paralisado',
        0
    ),
    (
        'Full Heal',
        'cura_status',
        'Cura todas as condições de status.',
        600,
        0,
        0,
        NULL,
        1
    ),
    -- Reviver (revive com 50% do HP)
    (
        'Revive',
        'reviver',
        'Revive um Pokémon desmaiado com metade do HP.',
        1500,
        0,
        50,
        NULL,
        0
    ),
    -- Evolução
    (
        'Pedra do Fogo',
        'evolucao',
        'Evolui Pokémon da linha de Fogo.',
        2100,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra da Água',
        'evolucao',
        'Evolui Pokémon da linha de Água.',
        2100,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra da Folha',
        'evolucao',
        'Evolui Pokémon da linha de Planta.',
        2100,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra do Trovão',
        'evolucao',
        'Evolui Pokémon da linha Elétrica.',
        2100,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra Rochosa',
        'evolucao',
        'Evolui Pokémon da linha de Terra.',
        2100,
        0,
        0,
        NULL,
        0
    );

-- ---------------------------------------------------------------------
-- Espécies (stats base da PokéAPI; o importador pode complementar)
-- Tipo único: Charizard = Fogo, Raichu = Elétrico etc.
-- ---------------------------------------------------------------------
INSERT INTO
    especies (
        pokeapi_id,
        nome,
        tipo_id,
        hp,
        ataque,
        defesa,
        sp_atk,
        sp_def,
        velocidade,
        sprite_url
    )
SELECT
    s.pid,
    s.nome,
    t.id,
    s.hp,
    s.atk,
    s.def,
    s.spa,
    s.spd,
    s.vel,
    CONCAT (
        'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/',
        s.pid,
        '.png'
    )
FROM
    (
        SELECT
            1 pid,
            'Bulbasaur' nome,
            'Planta' tipo,
            45 hp,
            49 atk,
            49 def,
            65 spa,
            65 spd,
            45 vel
        UNION ALL
        SELECT
            2,
            'Ivysaur',
            'Planta',
            60,
            62,
            63,
            80,
            80,
            60
        UNION ALL
        SELECT
            3,
            'Venusaur',
            'Planta',
            80,
            82,
            83,
            100,
            100,
            80
        UNION ALL
        SELECT
            4,
            'Charmander',
            'Fogo',
            39,
            52,
            43,
            60,
            50,
            65
        UNION ALL
        SELECT
            5,
            'Charmeleon',
            'Fogo',
            58,
            64,
            58,
            80,
            65,
            80
        UNION ALL
        SELECT
            6,
            'Charizard',
            'Fogo',
            78,
            84,
            78,
            109,
            85,
            100
        UNION ALL
        SELECT
            7,
            'Squirtle',
            'Água',
            44,
            48,
            65,
            50,
            64,
            43
        UNION ALL
        SELECT
            8,
            'Wartortle',
            'Água',
            59,
            63,
            80,
            65,
            80,
            58
        UNION ALL
        SELECT
            9,
            'Blastoise',
            'Água',
            79,
            83,
            100,
            85,
            105,
            78
        UNION ALL
        SELECT
            25,
            'Pikachu',
            'Elétrico',
            35,
            55,
            40,
            50,
            50,
            90
        UNION ALL
        SELECT
            26,
            'Raichu',
            'Elétrico',
            60,
            90,
            55,
            90,
            80,
            110
        UNION ALL
        SELECT
            74,
            'Geodude',
            'Terra',
            40,
            80,
            100,
            30,
            30,
            20
        UNION ALL
        SELECT
            75,
            'Graveler',
            'Terra',
            55,
            95,
            115,
            45,
            45,
            35
        UNION ALL
        SELECT
            76,
            'Golem',
            'Terra',
            80,
            120,
            130,
            55,
            65,
            45
    ) s
    JOIN tipos t ON t.nome = s.tipo;

-- Evoluções (UPDATE = DML): espécie atual -> próxima, pelo item da loja
UPDATE especies e
JOIN (
    SELECT
        'Bulbasaur' de,
        'Ivysaur' para,
        'Pedra da Folha' item
    UNION ALL
    SELECT
        'Ivysaur',
        'Venusaur',
        'Pedra da Folha'
    UNION ALL
    SELECT
        'Charmander',
        'Charmeleon',
        'Pedra do Fogo'
    UNION ALL
    SELECT
        'Charmeleon',
        'Charizard',
        'Pedra do Fogo'
    UNION ALL
    SELECT
        'Squirtle',
        'Wartortle',
        'Pedra da Água'
    UNION ALL
    SELECT
        'Wartortle',
        'Blastoise',
        'Pedra da Água'
    UNION ALL
    SELECT
        'Pikachu',
        'Raichu',
        'Pedra do Trovão'
    UNION ALL
    SELECT
        'Geodude',
        'Graveler',
        'Pedra Rochosa'
    UNION ALL
    SELECT
        'Graveler',
        'Golem',
        'Pedra Rochosa'
) m ON m.de = e.nome
JOIN especies prox ON prox.nome = m.para
JOIN itens i ON i.nome = m.item
SET
    e.evolui_para_id = prox.id,
    e.item_evolucao_id = i.id;

-- ---------------------------------------------------------------------
-- Golpes
-- ---------------------------------------------------------------------
INSERT INTO
    golpes (nome, tipo_id, poder, precisao, categoria)
SELECT
    g.nome,
    t.id,
    g.poder,
    g.prec,
    g.cat
FROM
    (
        SELECT
            'Tackle' nome,
            'Normal' tipo,
            40 poder,
            100 prec,
            'fisico' cat
        UNION ALL
        SELECT
            'Quick Attack',
            'Normal',
            40,
            100,
            'fisico'
        UNION ALL
        SELECT
            'Slash',
            'Normal',
            70,
            100,
            'fisico'
        UNION ALL
        SELECT
            'Body Slam',
            'Normal',
            85,
            100,
            'fisico'
        UNION ALL
        SELECT
            'Ember',
            'Fogo',
            40,
            100,
            'especial'
        UNION ALL
        SELECT
            'Flamethrower',
            'Fogo',
            90,
            100,
            'especial'
        UNION ALL
        SELECT
            'Water Gun',
            'Água',
            40,
            100,
            'especial'
        UNION ALL
        SELECT
            'Surf',
            'Água',
            90,
            100,
            'especial'
        UNION ALL
        SELECT
            'Vine Whip',
            'Planta',
            45,
            100,
            'fisico'
        UNION ALL
        SELECT
            'Razor Leaf',
            'Planta',
            55,
            95,
            'fisico'
        UNION ALL
        SELECT
            'Thunder Shock',
            'Elétrico',
            40,
            100,
            'especial'
        UNION ALL
        SELECT
            'Thunderbolt',
            'Elétrico',
            90,
            100,
            'especial'
        UNION ALL
        SELECT
            'Rock Throw',
            'Terra',
            50,
            90,
            'fisico'
        UNION ALL
        SELECT
            'Earthquake',
            'Terra',
            100,
            100,
            'fisico'
        UNION ALL
        SELECT
            'Wing Attack',
            'Voador',
            60,
            100,
            'fisico'
        UNION ALL
        SELECT
            'Ice Beam',
            'Gelo',
            90,
            100,
            'especial'
    ) g
    JOIN tipos t ON t.nome = g.tipo;

-- Golpes que cada espécie conhece (4 por espécie)
INSERT INTO
    especie_golpes (especie_id, golpe_id)
SELECT
    e.id,
    g.id
FROM
    (
        SELECT
            'Bulbasaur' esp,
            'Tackle' gol
        UNION ALL
        SELECT
            'Bulbasaur',
            'Vine Whip'
        UNION ALL
        SELECT
            'Bulbasaur',
            'Razor Leaf'
        UNION ALL
        SELECT
            'Bulbasaur',
            'Body Slam'
        UNION ALL
        SELECT
            'Ivysaur',
            'Tackle'
        UNION ALL
        SELECT
            'Ivysaur',
            'Vine Whip'
        UNION ALL
        SELECT
            'Ivysaur',
            'Razor Leaf'
        UNION ALL
        SELECT
            'Ivysaur',
            'Body Slam'
        UNION ALL
        SELECT
            'Venusaur',
            'Vine Whip'
        UNION ALL
        SELECT
            'Venusaur',
            'Razor Leaf'
        UNION ALL
        SELECT
            'Venusaur',
            'Body Slam'
        UNION ALL
        SELECT
            'Venusaur',
            'Earthquake'
        UNION ALL
        SELECT
            'Charmander',
            'Tackle'
        UNION ALL
        SELECT
            'Charmander',
            'Ember'
        UNION ALL
        SELECT
            'Charmander',
            'Slash'
        UNION ALL
        SELECT
            'Charmander',
            'Quick Attack'
        UNION ALL
        SELECT
            'Charmeleon',
            'Ember'
        UNION ALL
        SELECT
            'Charmeleon',
            'Slash'
        UNION ALL
        SELECT
            'Charmeleon',
            'Flamethrower'
        UNION ALL
        SELECT
            'Charmeleon',
            'Quick Attack'
        UNION ALL
        SELECT
            'Charizard',
            'Flamethrower'
        UNION ALL
        SELECT
            'Charizard',
            'Wing Attack'
        UNION ALL
        SELECT
            'Charizard',
            'Slash'
        UNION ALL
        SELECT
            'Charizard',
            'Earthquake'
        UNION ALL
        SELECT
            'Squirtle',
            'Tackle'
        UNION ALL
        SELECT
            'Squirtle',
            'Water Gun'
        UNION ALL
        SELECT
            'Squirtle',
            'Quick Attack'
        UNION ALL
        SELECT
            'Squirtle',
            'Body Slam'
        UNION ALL
        SELECT
            'Wartortle',
            'Water Gun'
        UNION ALL
        SELECT
            'Wartortle',
            'Surf'
        UNION ALL
        SELECT
            'Wartortle',
            'Body Slam'
        UNION ALL
        SELECT
            'Wartortle',
            'Ice Beam'
        UNION ALL
        SELECT
            'Blastoise',
            'Surf'
        UNION ALL
        SELECT
            'Blastoise',
            'Ice Beam'
        UNION ALL
        SELECT
            'Blastoise',
            'Body Slam'
        UNION ALL
        SELECT
            'Blastoise',
            'Earthquake'
        UNION ALL
        SELECT
            'Pikachu',
            'Thunder Shock'
        UNION ALL
        SELECT
            'Pikachu',
            'Quick Attack'
        UNION ALL
        SELECT
            'Pikachu',
            'Tackle'
        UNION ALL
        SELECT
            'Pikachu',
            'Thunderbolt'
        UNION ALL
        SELECT
            'Raichu',
            'Thunderbolt'
        UNION ALL
        SELECT
            'Raichu',
            'Quick Attack'
        UNION ALL
        SELECT
            'Raichu',
            'Slash'
        UNION ALL
        SELECT
            'Raichu',
            'Body Slam'
        UNION ALL
        SELECT
            'Geodude',
            'Tackle'
        UNION ALL
        SELECT
            'Geodude',
            'Rock Throw'
        UNION ALL
        SELECT
            'Geodude',
            'Body Slam'
        UNION ALL
        SELECT
            'Geodude',
            'Earthquake'
        UNION ALL
        SELECT
            'Graveler',
            'Rock Throw'
        UNION ALL
        SELECT
            'Graveler',
            'Body Slam'
        UNION ALL
        SELECT
            'Graveler',
            'Earthquake'
        UNION ALL
        SELECT
            'Graveler',
            'Slash'
        UNION ALL
        SELECT
            'Golem',
            'Rock Throw'
        UNION ALL
        SELECT
            'Golem',
            'Earthquake'
        UNION ALL
        SELECT
            'Golem',
            'Body Slam'
        UNION ALL
        SELECT
            'Golem',
            'Slash'
    ) m
    JOIN especies e ON e.nome = m.esp
    JOIN golpes g ON g.nome = m.gol;

-- ---------------------------------------------------------------------
-- Inventário inicial do jogador de teste (DML)
-- ---------------------------------------------------------------------
INSERT INTO
    inventario (usuario_id, item_id, quantidade)
SELECT
    u.id,
    i.id,
    3
FROM
    usuarios u
    JOIN itens i ON i.nome = 'Potion'
WHERE
    u.email = 'jogador@teste.com';