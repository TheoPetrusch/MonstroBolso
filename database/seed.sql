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
-- Tipos (os 18 oficiais). A ordem segue a PokéAPI, então tipos.id é igual
-- ao id do tipo na PokéAPI (1 = normal, 2 = fighting ... 18 = fairy).
-- ---------------------------------------------------------------------
INSERT INTO
    tipos (nome)
VALUES
    ('Normal'),
    ('Lutador'),
    ('Voador'),
    ('Venenoso'),
    ('Terra'),
    ('Pedra'),
    ('Inseto'),
    ('Fantasma'),
    ('Aço'),
    ('Fogo'),
    ('Água'),
    ('Planta'),
    ('Elétrico'),
    ('Psíquico'),
    ('Gelo'),
    ('Dragão'),
    ('Sombrio'),
    ('Fada');

-- Tabela de vantagens completa (120 pares).
-- 2.0 = super efetivo, 0.5 = pouco efetivo, 0.0 = imune.
-- Pares que não aparecem aqui valem 1.0 (dano normal): o PHP deve usar
-- COALESCE(multiplicador, 1.0) ou tratar a ausência como 1.
INSERT INTO
    tipo_vantagens (tipo_atacante_id, tipo_defensor_id, multiplicador)
SELECT
    a.id,
    d.id,
    v.m
FROM
    (
        -- Normal
        SELECT 'Normal'    AS a, 'Pedra'     AS d, 0.5 AS m
        UNION ALL SELECT 'Normal'   , 'Aço'      , 0.5
        UNION ALL SELECT 'Normal'   , 'Fantasma' , 0.0
        -- Lutador
        UNION ALL SELECT 'Lutador'  , 'Normal'   , 2.0
        UNION ALL SELECT 'Lutador'  , 'Gelo'     , 2.0
        UNION ALL SELECT 'Lutador'  , 'Venenoso' , 0.5
        UNION ALL SELECT 'Lutador'  , 'Voador'   , 0.5
        UNION ALL SELECT 'Lutador'  , 'Psíquico' , 0.5
        UNION ALL SELECT 'Lutador'  , 'Inseto'   , 0.5
        UNION ALL SELECT 'Lutador'  , 'Pedra'    , 2.0
        UNION ALL SELECT 'Lutador'  , 'Fantasma' , 0.0
        UNION ALL SELECT 'Lutador'  , 'Sombrio'  , 2.0
        UNION ALL SELECT 'Lutador'  , 'Aço'      , 2.0
        UNION ALL SELECT 'Lutador'  , 'Fada'     , 0.5
        -- Voador
        UNION ALL SELECT 'Voador'   , 'Elétrico' , 0.5
        UNION ALL SELECT 'Voador'   , 'Planta'   , 2.0
        UNION ALL SELECT 'Voador'   , 'Lutador'  , 2.0
        UNION ALL SELECT 'Voador'   , 'Inseto'   , 2.0
        UNION ALL SELECT 'Voador'   , 'Pedra'    , 0.5
        UNION ALL SELECT 'Voador'   , 'Aço'      , 0.5
        -- Venenoso
        UNION ALL SELECT 'Venenoso' , 'Planta'   , 2.0
        UNION ALL SELECT 'Venenoso' , 'Venenoso' , 0.5
        UNION ALL SELECT 'Venenoso' , 'Terra'    , 0.5
        UNION ALL SELECT 'Venenoso' , 'Pedra'    , 0.5
        UNION ALL SELECT 'Venenoso' , 'Fantasma' , 0.5
        UNION ALL SELECT 'Venenoso' , 'Aço'      , 0.0
        UNION ALL SELECT 'Venenoso' , 'Fada'     , 2.0
        -- Terra
        UNION ALL SELECT 'Terra'    , 'Fogo'     , 2.0
        UNION ALL SELECT 'Terra'    , 'Elétrico' , 2.0
        UNION ALL SELECT 'Terra'    , 'Planta'   , 0.5
        UNION ALL SELECT 'Terra'    , 'Venenoso' , 2.0
        UNION ALL SELECT 'Terra'    , 'Voador'   , 0.0
        UNION ALL SELECT 'Terra'    , 'Inseto'   , 0.5
        UNION ALL SELECT 'Terra'    , 'Pedra'    , 2.0
        UNION ALL SELECT 'Terra'    , 'Aço'      , 2.0
        -- Pedra
        UNION ALL SELECT 'Pedra'    , 'Fogo'     , 2.0
        UNION ALL SELECT 'Pedra'    , 'Gelo'     , 2.0
        UNION ALL SELECT 'Pedra'    , 'Lutador'  , 0.5
        UNION ALL SELECT 'Pedra'    , 'Terra'    , 0.5
        UNION ALL SELECT 'Pedra'    , 'Voador'   , 2.0
        UNION ALL SELECT 'Pedra'    , 'Inseto'   , 2.0
        UNION ALL SELECT 'Pedra'    , 'Aço'      , 0.5
        -- Inseto
        UNION ALL SELECT 'Inseto'   , 'Fogo'     , 0.5
        UNION ALL SELECT 'Inseto'   , 'Planta'   , 2.0
        UNION ALL SELECT 'Inseto'   , 'Lutador'  , 0.5
        UNION ALL SELECT 'Inseto'   , 'Venenoso' , 0.5
        UNION ALL SELECT 'Inseto'   , 'Voador'   , 0.5
        UNION ALL SELECT 'Inseto'   , 'Psíquico' , 2.0
        UNION ALL SELECT 'Inseto'   , 'Fantasma' , 0.5
        UNION ALL SELECT 'Inseto'   , 'Sombrio'  , 2.0
        UNION ALL SELECT 'Inseto'   , 'Aço'      , 0.5
        UNION ALL SELECT 'Inseto'   , 'Fada'     , 0.5
        -- Fantasma
        UNION ALL SELECT 'Fantasma' , 'Normal'   , 0.0
        UNION ALL SELECT 'Fantasma' , 'Psíquico' , 2.0
        UNION ALL SELECT 'Fantasma' , 'Fantasma' , 2.0
        UNION ALL SELECT 'Fantasma' , 'Sombrio'  , 0.5
        -- Aço
        UNION ALL SELECT 'Aço'      , 'Fogo'     , 0.5
        UNION ALL SELECT 'Aço'      , 'Água'     , 0.5
        UNION ALL SELECT 'Aço'      , 'Elétrico' , 0.5
        UNION ALL SELECT 'Aço'      , 'Gelo'     , 2.0
        UNION ALL SELECT 'Aço'      , 'Pedra'    , 2.0
        UNION ALL SELECT 'Aço'      , 'Aço'      , 0.5
        UNION ALL SELECT 'Aço'      , 'Fada'     , 2.0
        -- Fogo
        UNION ALL SELECT 'Fogo'     , 'Fogo'     , 0.5
        UNION ALL SELECT 'Fogo'     , 'Água'     , 0.5
        UNION ALL SELECT 'Fogo'     , 'Planta'   , 2.0
        UNION ALL SELECT 'Fogo'     , 'Gelo'     , 2.0
        UNION ALL SELECT 'Fogo'     , 'Inseto'   , 2.0
        UNION ALL SELECT 'Fogo'     , 'Pedra'    , 0.5
        UNION ALL SELECT 'Fogo'     , 'Dragão'   , 0.5
        UNION ALL SELECT 'Fogo'     , 'Aço'      , 2.0
        -- Água
        UNION ALL SELECT 'Água'     , 'Fogo'     , 2.0
        UNION ALL SELECT 'Água'     , 'Água'     , 0.5
        UNION ALL SELECT 'Água'     , 'Planta'   , 0.5
        UNION ALL SELECT 'Água'     , 'Terra'    , 2.0
        UNION ALL SELECT 'Água'     , 'Pedra'    , 2.0
        UNION ALL SELECT 'Água'     , 'Dragão'   , 0.5
        -- Planta
        UNION ALL SELECT 'Planta'   , 'Fogo'     , 0.5
        UNION ALL SELECT 'Planta'   , 'Água'     , 2.0
        UNION ALL SELECT 'Planta'   , 'Planta'   , 0.5
        UNION ALL SELECT 'Planta'   , 'Venenoso' , 0.5
        UNION ALL SELECT 'Planta'   , 'Terra'    , 2.0
        UNION ALL SELECT 'Planta'   , 'Voador'   , 0.5
        UNION ALL SELECT 'Planta'   , 'Inseto'   , 0.5
        UNION ALL SELECT 'Planta'   , 'Pedra'    , 2.0
        UNION ALL SELECT 'Planta'   , 'Dragão'   , 0.5
        UNION ALL SELECT 'Planta'   , 'Aço'      , 0.5
        -- Elétrico
        UNION ALL SELECT 'Elétrico' , 'Água'     , 2.0
        UNION ALL SELECT 'Elétrico' , 'Elétrico' , 0.5
        UNION ALL SELECT 'Elétrico' , 'Planta'   , 0.5
        UNION ALL SELECT 'Elétrico' , 'Terra'    , 0.0
        UNION ALL SELECT 'Elétrico' , 'Voador'   , 2.0
        UNION ALL SELECT 'Elétrico' , 'Dragão'   , 0.5
        -- Psíquico
        UNION ALL SELECT 'Psíquico' , 'Lutador'  , 2.0
        UNION ALL SELECT 'Psíquico' , 'Venenoso' , 2.0
        UNION ALL SELECT 'Psíquico' , 'Psíquico' , 0.5
        UNION ALL SELECT 'Psíquico' , 'Sombrio'  , 0.0
        UNION ALL SELECT 'Psíquico' , 'Aço'      , 0.5
        -- Gelo
        UNION ALL SELECT 'Gelo'     , 'Fogo'     , 0.5
        UNION ALL SELECT 'Gelo'     , 'Água'     , 0.5
        UNION ALL SELECT 'Gelo'     , 'Planta'   , 2.0
        UNION ALL SELECT 'Gelo'     , 'Gelo'     , 0.5
        UNION ALL SELECT 'Gelo'     , 'Terra'    , 2.0
        UNION ALL SELECT 'Gelo'     , 'Voador'   , 2.0
        UNION ALL SELECT 'Gelo'     , 'Dragão'   , 2.0
        UNION ALL SELECT 'Gelo'     , 'Aço'      , 0.5
        -- Dragão
        UNION ALL SELECT 'Dragão'   , 'Dragão'   , 2.0
        UNION ALL SELECT 'Dragão'   , 'Aço'      , 0.5
        UNION ALL SELECT 'Dragão'   , 'Fada'     , 0.0
        -- Sombrio
        UNION ALL SELECT 'Sombrio'  , 'Lutador'  , 0.5
        UNION ALL SELECT 'Sombrio'  , 'Psíquico' , 2.0
        UNION ALL SELECT 'Sombrio'  , 'Fantasma' , 2.0
        UNION ALL SELECT 'Sombrio'  , 'Sombrio'  , 0.5
        UNION ALL SELECT 'Sombrio'  , 'Fada'     , 0.5
        -- Fada
        UNION ALL SELECT 'Fada'     , 'Fogo'     , 0.5
        UNION ALL SELECT 'Fada'     , 'Lutador'  , 2.0
        UNION ALL SELECT 'Fada'     , 'Venenoso' , 0.5
        UNION ALL SELECT 'Fada'     , 'Dragão'   , 2.0
        UNION ALL SELECT 'Fada'     , 'Sombrio'  , 2.0
        UNION ALL SELECT 'Fada'     , 'Aço'      , 0.5
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
        'Evolui Pokémon da linha de Pedra.',
        2100,
        0,
        0,
        NULL,
        0
    );

-- ---------------------------------------------------------------------
-- Espécies (stats base da PokéAPI; o importador pode complementar)
-- Tipo único = 1º tipo da PokéAPI: Charizard = Fogo, Geodude = Pedra etc.
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
            'Pedra',
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
            'Pedra',
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
            'Pedra',
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
            'Pedra',
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