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
        99999
    );

-- Cada usuário precisa de um time (1 por usuário). No cadastro pelo site
-- isso é feito pelo UsuarioRepository::criar(); aqui, para os de teste.
INSERT INTO
    times (usuario_id)
SELECT
    id
FROM
    usuarios;

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
        'Revive um Pokémon desmaiado com metade do HP. Funciona no meio da batalha.',
        4000,
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
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra da Água',
        'evolucao',
        'Evolui Pokémon da linha de Água.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra da Folha',
        'evolucao',
        'Evolui Pokémon da linha de Planta.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra do Trovão',
        'evolucao',
        'Evolui Pokémon da linha Elétrica.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra Rochosa',
        'evolucao',
        'Evolui Pokémon da linha de Pedra.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra da Terra',
        'evolucao',
        'Evolui Pokémon da linha de Terra.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra do Inseto',
        'evolucao',
        'Evolui Pokémon da linha de Inseto.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra Normal',
        'evolucao',
        'Evolui Pokémon da linha de Normal.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra Venenosa',
        'evolucao',
        'Evolui Pokémon da linha de Venenoso.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra da Lua',
        'evolucao',
        'Evolui Pokémon da linha de Fada.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra da Luta',
        'evolucao',
        'Evolui Pokémon da linha de Lutador.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra Psíquica',
        'evolucao',
        'Evolui Pokémon da linha de Psíquico.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra Fantasma',
        'evolucao',
        'Evolui Pokémon da linha de Fantasma.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra do Dragão',
        'evolucao',
        'Evolui Pokémon da linha de Dragão.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra do Vento',
        'evolucao',
        'Evolui Pokémon da linha de Voador.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra do Gelo',
        'evolucao',
        'Evolui Pokémon da linha de Gelo.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra Metálica',
        'evolucao',
        'Evolui Pokémon da linha de Aço.',
        1900,
        0,
        0,
        NULL,
        0
    ),
    (
        'Pedra Sombria',
        'evolucao',
        'Evolui Pokémon da linha de Sombrio.',
        1900,
        0,
        0,
        NULL,
        0
    );

-- Cada pedra evolui as espécies de um tipo (itens.tipo_id).
-- O importador usa essa ligação para achar a pedra de cada evolução.
UPDATE itens i
JOIN (
    SELECT 'Pedra do Fogo' item, 'Fogo' tipo
    UNION ALL SELECT 'Pedra da Água', 'Água'
    UNION ALL SELECT 'Pedra da Folha', 'Planta'
    UNION ALL SELECT 'Pedra do Trovão', 'Elétrico'
    UNION ALL SELECT 'Pedra Rochosa', 'Pedra'
    UNION ALL SELECT 'Pedra da Terra', 'Terra'
    UNION ALL SELECT 'Pedra do Inseto', 'Inseto'
    UNION ALL SELECT 'Pedra Normal', 'Normal'
    UNION ALL SELECT 'Pedra Venenosa', 'Venenoso'
    UNION ALL SELECT 'Pedra da Lua', 'Fada'
    UNION ALL SELECT 'Pedra da Luta', 'Lutador'
    UNION ALL SELECT 'Pedra Psíquica', 'Psíquico'
    UNION ALL SELECT 'Pedra Fantasma', 'Fantasma'
    UNION ALL SELECT 'Pedra do Dragão', 'Dragão'
    UNION ALL SELECT 'Pedra do Vento', 'Voador'
    UNION ALL SELECT 'Pedra do Gelo', 'Gelo'
    UNION ALL SELECT 'Pedra Metálica', 'Aço'
    UNION ALL SELECT 'Pedra Sombria', 'Sombrio'
) m ON m.item = i.nome
JOIN tipos t ON t.nome = m.tipo
SET
    i.tipo_id = t.id;

-- ---------------------------------------------------------------------
-- Espécies iniciais (stats base atuais da PokéAPI)
-- Tipo único = 1º tipo da PokéAPI: Charizard = Fogo, Geodude = Pedra etc.
-- Exceção: quem é Normal/Voador na PokéAPI (Pidgey, Spearow...) fica Voador.
-- As outras espécies entram pelo importador: na Home, qualquer jogador
-- escolhe uma geração (1ª a 9ª) e clica em "Importar geração".
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
        SELECT   1 pid, 'Bulbasaur' nome, 'Planta' tipo, 45 hp, 49 atk, 49 def, 65 spa, 65 spd, 45 vel
        UNION ALL SELECT   2, 'Ivysaur'    , 'Planta'   ,  60,  62,  63,  80,  80,  60
        UNION ALL SELECT   3, 'Venusaur'   , 'Planta'   ,  80,  82,  83, 100, 100,  80
        UNION ALL SELECT   4, 'Charmander' , 'Fogo'     ,  39,  52,  43,  60,  50,  65
        UNION ALL SELECT   5, 'Charmeleon' , 'Fogo'     ,  58,  64,  58,  80,  65,  80
        UNION ALL SELECT   6, 'Charizard'  , 'Fogo'     ,  78,  84,  78, 109,  85, 100
        UNION ALL SELECT   7, 'Squirtle'   , 'Água'     ,  44,  48,  65,  50,  64,  43
        UNION ALL SELECT   8, 'Wartortle'  , 'Água'     ,  59,  63,  80,  65,  80,  58
        UNION ALL SELECT   9, 'Blastoise'  , 'Água'     ,  79,  83, 100,  85, 105,  78
        UNION ALL SELECT  25, 'Pikachu'    , 'Elétrico' ,  35,  55,  40,  50,  50,  90
        UNION ALL SELECT  26, 'Raichu'     , 'Elétrico' ,  60,  90,  55,  90,  80, 110
        UNION ALL SELECT  74, 'Geodude'    , 'Pedra'    ,  40,  80, 100,  30,  30,  20
        UNION ALL SELECT  75, 'Graveler'   , 'Pedra'    ,  55,  95, 115,  45,  45,  35
        UNION ALL SELECT  76, 'Golem'      , 'Pedra'    ,  80, 120, 130,  55,  65,  45
    ) s
    JOIN tipos t ON t.nome = s.tipo;

-- Evoluções (UPDATE = DML): espécie atual -> próxima.
-- A pedra é a do tipo da espécie (itens.tipo_id = especies.tipo_id).
UPDATE especies e
JOIN (
    SELECT 'Bulbasaur' de, 'Ivysaur' para
    UNION ALL SELECT 'Ivysaur'    , 'Venusaur'
    UNION ALL SELECT 'Charmander' , 'Charmeleon'
    UNION ALL SELECT 'Charmeleon' , 'Charizard'
    UNION ALL SELECT 'Squirtle'   , 'Wartortle'
    UNION ALL SELECT 'Wartortle'  , 'Blastoise'
    UNION ALL SELECT 'Pikachu'    , 'Raichu'
    UNION ALL SELECT 'Geodude'    , 'Graveler'
    UNION ALL SELECT 'Graveler'   , 'Golem'
) m ON m.de = e.nome
JOIN especies prox ON prox.nome = m.para
JOIN itens i ON i.categoria = 'evolucao' AND i.tipo_id = e.tipo_id
SET
    e.evolui_para_id = prox.id,
    e.item_evolucao_id = i.id;

-- Caminho inverso: cada evolução guarda de quem ela veio (evolui_de_id).
-- Quem fica com evolui_de_id NULL é espécie básica.
UPDATE especies filho
JOIN especies pai ON pai.evolui_para_id = filho.id
SET
    filho.evolui_de_id = pai.id,
    filho.evolui_de_pokeapi_id = pai.pokeapi_id;

-- ---------------------------------------------------------------------
-- Golpes: 2 físicos e 2 especiais (um fraco e um forte) de cada tipo
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
        SELECT 'Tackle' nome, 'Normal' tipo, 40 poder, 100 prec, 'fisico' cat
        UNION ALL SELECT 'Body Slam'     , 'Normal'    ,  85, 100, 'fisico'
        UNION ALL SELECT 'Swift'         , 'Normal'    ,  60, 100, 'especial'
        UNION ALL SELECT 'Hyper Voice'   , 'Normal'    ,  90, 100, 'especial'
        UNION ALL SELECT 'Karate Chop'   , 'Lutador'   ,  50, 100, 'fisico'
        UNION ALL SELECT 'Submission'    , 'Lutador'   ,  80,  80, 'fisico'
        UNION ALL SELECT 'Vacuum Wave'   , 'Lutador'   ,  40, 100, 'especial'
        UNION ALL SELECT 'Aura Sphere'   , 'Lutador'   ,  80, 100, 'especial'
        UNION ALL SELECT 'Wing Attack'   , 'Voador'    ,  60, 100, 'fisico'
        UNION ALL SELECT 'Drill Peck'    , 'Voador'    ,  80, 100, 'fisico'
        UNION ALL SELECT 'Gust'          , 'Voador'    ,  40, 100, 'especial'
        UNION ALL SELECT 'Air Slash'     , 'Voador'    ,  75,  95, 'especial'
        UNION ALL SELECT 'Poison Fang'   , 'Venenoso'  ,  50, 100, 'fisico'
        UNION ALL SELECT 'Poison Jab'    , 'Venenoso'  ,  80, 100, 'fisico'
        UNION ALL SELECT 'Acid'          , 'Venenoso'  ,  40, 100, 'especial'
        UNION ALL SELECT 'Sludge Bomb'   , 'Venenoso'  ,  90, 100, 'especial'
        UNION ALL SELECT 'Bone Club'     , 'Terra'     ,  65,  85, 'fisico'
        UNION ALL SELECT 'Earthquake'    , 'Terra'     , 100, 100, 'fisico'
        UNION ALL SELECT 'Mud Shot'      , 'Terra'     ,  55,  95, 'especial'
        UNION ALL SELECT 'Earth Power'   , 'Terra'     ,  90, 100, 'especial'
        UNION ALL SELECT 'Rock Throw'    , 'Pedra'     ,  50,  90, 'fisico'
        UNION ALL SELECT 'Rock Slide'    , 'Pedra'     ,  75,  90, 'fisico'
        UNION ALL SELECT 'Ancient Power' , 'Pedra'     ,  60, 100, 'especial'
        UNION ALL SELECT 'Power Gem'     , 'Pedra'     ,  80, 100, 'especial'
        UNION ALL SELECT 'Fury Cutter'   , 'Inseto'    ,  40,  95, 'fisico'
        UNION ALL SELECT 'X-Scissor'     , 'Inseto'    ,  80, 100, 'fisico'
        UNION ALL SELECT 'Silver Wind'   , 'Inseto'    ,  60, 100, 'especial'
        UNION ALL SELECT 'Bug Buzz'      , 'Inseto'    ,  90, 100, 'especial'
        UNION ALL SELECT 'Lick'          , 'Fantasma'  ,  30, 100, 'fisico'
        UNION ALL SELECT 'Shadow Claw'   , 'Fantasma'  ,  70, 100, 'fisico'
        UNION ALL SELECT 'Ominous Wind'  , 'Fantasma'  ,  60, 100, 'especial'
        UNION ALL SELECT 'Shadow Ball'   , 'Fantasma'  ,  80, 100, 'especial'
        UNION ALL SELECT 'Metal Claw'    , 'Aço'       ,  50,  95, 'fisico'
        UNION ALL SELECT 'Iron Head'     , 'Aço'       ,  80, 100, 'fisico'
        UNION ALL SELECT 'Mirror Shot'   , 'Aço'       ,  65,  85, 'especial'
        UNION ALL SELECT 'Flash Cannon'  , 'Aço'       ,  80, 100, 'especial'
        UNION ALL SELECT 'Fire Fang'     , 'Fogo'      ,  65,  95, 'fisico'
        UNION ALL SELECT 'Fire Punch'    , 'Fogo'      ,  75, 100, 'fisico'
        UNION ALL SELECT 'Ember'         , 'Fogo'      ,  40, 100, 'especial'
        UNION ALL SELECT 'Flamethrower'  , 'Fogo'      ,  90, 100, 'especial'
        UNION ALL SELECT 'Aqua Jet'      , 'Água'      ,  40, 100, 'fisico'
        UNION ALL SELECT 'Waterfall'     , 'Água'      ,  80, 100, 'fisico'
        UNION ALL SELECT 'Water Gun'     , 'Água'      ,  40, 100, 'especial'
        UNION ALL SELECT 'Surf'          , 'Água'      ,  90, 100, 'especial'
        UNION ALL SELECT 'Vine Whip'     , 'Planta'    ,  45, 100, 'fisico'
        UNION ALL SELECT 'Razor Leaf'    , 'Planta'    ,  55,  95, 'fisico'
        UNION ALL SELECT 'Mega Drain'    , 'Planta'    ,  40, 100, 'especial'
        UNION ALL SELECT 'Energy Ball'   , 'Planta'    ,  90, 100, 'especial'
        UNION ALL SELECT 'Spark'         , 'Elétrico'  ,  65, 100, 'fisico'
        UNION ALL SELECT 'Thunder Punch' , 'Elétrico'  ,  75, 100, 'fisico'
        UNION ALL SELECT 'Thunder Shock' , 'Elétrico'  ,  40, 100, 'especial'
        UNION ALL SELECT 'Thunderbolt'   , 'Elétrico'  ,  90, 100, 'especial'
        UNION ALL SELECT 'Psycho Cut'    , 'Psíquico'  ,  70, 100, 'fisico'
        UNION ALL SELECT 'Zen Headbutt'  , 'Psíquico'  ,  80,  90, 'fisico'
        UNION ALL SELECT 'Confusion'     , 'Psíquico'  ,  50, 100, 'especial'
        UNION ALL SELECT 'Psychic'       , 'Psíquico'  ,  90, 100, 'especial'
        UNION ALL SELECT 'Ice Shard'     , 'Gelo'      ,  40, 100, 'fisico'
        UNION ALL SELECT 'Ice Punch'     , 'Gelo'      ,  75, 100, 'fisico'
        UNION ALL SELECT 'Powder Snow'   , 'Gelo'      ,  40, 100, 'especial'
        UNION ALL SELECT 'Ice Beam'      , 'Gelo'      ,  90, 100, 'especial'
        UNION ALL SELECT 'Dragon Tail'   , 'Dragão'    ,  60,  90, 'fisico'
        UNION ALL SELECT 'Dragon Claw'   , 'Dragão'    ,  80, 100, 'fisico'
        UNION ALL SELECT 'Twister'       , 'Dragão'    ,  40, 100, 'especial'
        UNION ALL SELECT 'Dragon Pulse'  , 'Dragão'    ,  85, 100, 'especial'
        UNION ALL SELECT 'Bite'          , 'Sombrio'   ,  60, 100, 'fisico'
        UNION ALL SELECT 'Crunch'        , 'Sombrio'   ,  80, 100, 'fisico'
        UNION ALL SELECT 'Snarl'         , 'Sombrio'   ,  55,  95, 'especial'
        UNION ALL SELECT 'Dark Pulse'    , 'Sombrio'   ,  80, 100, 'especial'
        UNION ALL SELECT 'Fairy Wind'    , 'Fada'      ,  40, 100, 'especial'
        UNION ALL SELECT 'Play Rough'    , 'Fada'      ,  90,  90, 'fisico'
        UNION ALL SELECT 'Moonblast'     , 'Fada'      ,  95, 100, 'especial'
        UNION ALL SELECT 'Quick Attack'  , 'Normal'    ,  40, 100, 'fisico'
        UNION ALL SELECT 'Slash'         , 'Normal'    ,  70, 100, 'fisico'
    ) g
    JOIN tipos t ON t.nome = g.tipo;

-- Golpes de cada espécie (4 por espécie), escolhidos pela mesma regra
-- da classe SeletorDeGolpes (app/models/SeletorDeGolpes.php), que o
-- importador usa para as demais espécies.
INSERT INTO
    especie_golpes (especie_id, golpe_id)
SELECT
    e.id,
    g.id
FROM
    (
        SELECT 'Bulbasaur' esp, 'Mega Drain' gol
        UNION ALL SELECT 'Bulbasaur'  , 'Tackle'
        UNION ALL SELECT 'Bulbasaur'  , 'Acid'
        UNION ALL SELECT 'Bulbasaur'  , 'Quick Attack'
        UNION ALL SELECT 'Ivysaur'    , 'Mega Drain'
        UNION ALL SELECT 'Ivysaur'    , 'Energy Ball'
        UNION ALL SELECT 'Ivysaur'    , 'Sludge Bomb'
        UNION ALL SELECT 'Ivysaur'    , 'Hyper Voice'
        UNION ALL SELECT 'Venusaur'   , 'Mega Drain'
        UNION ALL SELECT 'Venusaur'   , 'Energy Ball'
        UNION ALL SELECT 'Venusaur'   , 'Sludge Bomb'
        UNION ALL SELECT 'Venusaur'   , 'Hyper Voice'
        UNION ALL SELECT 'Charmander' , 'Ember'
        UNION ALL SELECT 'Charmander' , 'Quick Attack'
        UNION ALL SELECT 'Charmander' , 'Mud Shot'
        UNION ALL SELECT 'Charmander' , 'Tackle'
        UNION ALL SELECT 'Charmeleon' , 'Ember'
        UNION ALL SELECT 'Charmeleon' , 'Flamethrower'
        UNION ALL SELECT 'Charmeleon' , 'Earth Power'
        UNION ALL SELECT 'Charmeleon' , 'Hyper Voice'
        UNION ALL SELECT 'Charizard'  , 'Ember'
        UNION ALL SELECT 'Charizard'  , 'Flamethrower'
        UNION ALL SELECT 'Charizard'  , 'Air Slash'
        UNION ALL SELECT 'Charizard'  , 'Hyper Voice'
        UNION ALL SELECT 'Squirtle'   , 'Water Gun'
        UNION ALL SELECT 'Squirtle'   , 'Tackle'
        UNION ALL SELECT 'Squirtle'   , 'Powder Snow'
        UNION ALL SELECT 'Squirtle'   , 'Quick Attack'
        UNION ALL SELECT 'Wartortle'  , 'Water Gun'
        UNION ALL SELECT 'Wartortle'  , 'Surf'
        UNION ALL SELECT 'Wartortle'  , 'Ice Beam'
        UNION ALL SELECT 'Wartortle'  , 'Hyper Voice'
        UNION ALL SELECT 'Blastoise'  , 'Water Gun'
        UNION ALL SELECT 'Blastoise'  , 'Surf'
        UNION ALL SELECT 'Blastoise'  , 'Ice Beam'
        UNION ALL SELECT 'Blastoise'  , 'Hyper Voice'
        UNION ALL SELECT 'Pikachu'    , 'Spark'
        UNION ALL SELECT 'Pikachu'    , 'Quick Attack'
        UNION ALL SELECT 'Pikachu'    , 'Metal Claw'
        UNION ALL SELECT 'Pikachu'    , 'Tackle'
        UNION ALL SELECT 'Raichu'     , 'Spark'
        UNION ALL SELECT 'Raichu'     , 'Thunder Punch'
        UNION ALL SELECT 'Raichu'     , 'Iron Head'
        UNION ALL SELECT 'Raichu'     , 'Body Slam'
        UNION ALL SELECT 'Geodude'    , 'Rock Throw'
        UNION ALL SELECT 'Geodude'    , 'Tackle'
        UNION ALL SELECT 'Geodude'    , 'Bone Club'
        UNION ALL SELECT 'Geodude'    , 'Quick Attack'
        UNION ALL SELECT 'Graveler'   , 'Rock Throw'
        UNION ALL SELECT 'Graveler'   , 'Rock Slide'
        UNION ALL SELECT 'Graveler'   , 'Earthquake'
        UNION ALL SELECT 'Graveler'   , 'Body Slam'
        UNION ALL SELECT 'Golem'      , 'Rock Throw'
        UNION ALL SELECT 'Golem'      , 'Rock Slide'
        UNION ALL SELECT 'Golem'      , 'Earthquake'
        UNION ALL SELECT 'Golem'      , 'Body Slam'
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

-- =====================================================================
-- Líderes de ginásio (DML) - as tabelas estão no schema.sql
-- =====================================================================
-- DML: os 8 líderes
INSERT INTO `lideres` (`ordem`, `nome`, `tipo_id`, `premio`, `insignia`, `bonus_hp`)
SELECT l.ordem, l.nome, t.id, l.premio, l.insignia, l.bonus_hp
FROM (
    SELECT 1 AS ordem, 'Líder Brita' AS nome, 'Pedra' AS tipo, 500 AS premio, 'Insígnia Rocha' AS insignia, 1.50 AS bonus_hp
    UNION ALL SELECT 2, 'Líder Marina' , 'Água'     ,  700, 'Insígnia Onda'   , 1.40
    UNION ALL SELECT 3, 'Líder Volt'   , 'Elétrico' ,  900, 'Insígnia Trovão' , 2.10
    UNION ALL SELECT 4, 'Líder Flora'  , 'Planta'   , 1100, 'Insígnia Folha'  , 1.90
    UNION ALL SELECT 5, 'Líder Toxa'   , 'Venenoso' , 1300, 'Insígnia Veneno' , 1.40
    UNION ALL SELECT 6, 'Líder Sofia'  , 'Psíquico' , 1500, 'Insígnia Mente'  , 1.50
    UNION ALL SELECT 7, 'Líder Brasa'  , 'Fogo'     , 1800, 'Insígnia Chama'  , 2.00
    UNION ALL SELECT 8, 'Líder Duna'   , 'Terra'    , 2200, 'Insígnia Terra'  , 1.50
) l
JOIN `tipos` t ON t.nome = l.tipo
WHERE NOT EXISTS (SELECT 1 FROM `lideres` x WHERE x.ordem = l.ordem);

-- DML: espécies dos times dos líderes + a linha evolutiva delas
-- (Pokémon de várias gerações; INSERT IGNORE: se já foram importadas da
-- PokéAPI, ficam como estão)
INSERT IGNORE INTO `especies` (`pokeapi_id`, `nome`, `tipo_id`, `hp`, `ataque`, `defesa`, `sp_atk`, `sp_def`, `velocidade`, `sprite_url`, `evolui_de_pokeapi_id`)
SELECT s.pid, s.nome, t.id, s.hp, s.atk, s.def, s.spa, s.spd, s.vel,
       CONCAT('https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/', s.pid, '.png'), s.pai
FROM (
    SELECT 43 AS pid, 'Oddish' AS nome, 'Planta' AS tipo, 45 AS hp, 50 AS atk, 55 AS def, 75 AS spa, 65 AS spd, 30 AS vel, NULL AS pai
    UNION ALL SELECT   44, 'Gloom'     , 'Planta'   ,  60,  65,  70,  85,  75,  40, 43
    UNION ALL SELECT   45, 'Vileplume' , 'Planta'   ,  75,  80,  85, 110,  90,  50, 44
    UNION ALL SELECT   50, 'Diglett'   , 'Terra'    ,  10,  55,  25,  35,  45,  95, NULL
    UNION ALL SELECT   51, 'Dugtrio'   , 'Terra'    ,  35, 100,  50,  50,  70, 120, 50
    UNION ALL SELECT   58, 'Growlithe' , 'Fogo'     ,  55,  70,  45,  70,  50,  60, NULL
    UNION ALL SELECT   59, 'Arcanine'  , 'Fogo'     ,  90, 110,  80, 100,  80,  95, 58
    UNION ALL SELECT   63, 'Abra'      , 'Psíquico' ,  25,  20,  15, 105,  55,  90, NULL
    UNION ALL SELECT   64, 'Kadabra'   , 'Psíquico' ,  40,  35,  30, 120,  70, 105, 63
    UNION ALL SELECT   65, 'Alakazam'  , 'Psíquico' ,  55,  50,  45, 135,  95, 120, 64
    UNION ALL SELECT   81, 'Magnemite' , 'Elétrico' ,  25,  35,  70,  95,  55,  45, NULL
    UNION ALL SELECT   82, 'Magneton'  , 'Elétrico' ,  50,  60,  95, 120,  70,  70, 81
    UNION ALL SELECT   96, 'Drowzee'   , 'Psíquico' ,  60,  48,  45,  43,  90,  42, NULL
    UNION ALL SELECT   97, 'Hypno'     , 'Psíquico' ,  85,  73,  70,  73, 115,  67, 96
    UNION ALL SELECT  109, 'Koffing'   , 'Venenoso' ,  40,  65,  95,  60,  45,  35, NULL
    UNION ALL SELECT  110, 'Weezing'   , 'Venenoso' ,  65,  90, 120,  85,  70,  60, 109
    UNION ALL SELECT  111, 'Rhyhorn'   , 'Terra'    ,  80,  85,  95,  30,  30,  25, NULL
    UNION ALL SELECT  112, 'Rhydon'    , 'Terra'    , 105, 130, 120,  45,  45,  40, 111
    UNION ALL SELECT  114, 'Tangela'   , 'Planta'   ,  65,  55, 115, 100,  40,  60, NULL
    UNION ALL SELECT  120, 'Staryu'    , 'Água'     ,  30,  45,  55,  70,  55,  85, NULL
    UNION ALL SELECT  121, 'Starmie'   , 'Água'     ,  60,  75,  85, 100,  85, 115, 120
    UNION ALL SELECT  177, 'Natu'      , 'Psíquico' ,  40,  50,  45,  70,  45,  70, NULL
    UNION ALL SELECT  178, 'Xatu'      , 'Psíquico' ,  65,  75,  70,  95,  70,  95, 177
    UNION ALL SELECT  179, 'Mareep'    , 'Elétrico' ,  55,  40,  40,  65,  45,  35, NULL
    UNION ALL SELECT  180, 'Flaaffy'   , 'Elétrico' ,  70,  55,  55,  80,  60,  45, 179
    UNION ALL SELECT  181, 'Ampharos'  , 'Elétrico' ,  90,  75,  85, 115,  90,  55, 180
    UNION ALL SELECT  207, 'Gligar'    , 'Terra'    ,  65,  75, 105,  35,  65,  85, NULL
    UNION ALL SELECT  252, 'Treecko'   , 'Planta'   ,  40,  45,  35,  65,  55,  70, NULL
    UNION ALL SELECT  253, 'Grovyle'   , 'Planta'   ,  50,  65,  45,  85,  65,  95, 252
    UNION ALL SELECT  254, 'Sceptile'  , 'Planta'   ,  70,  85,  65, 105,  85, 120, 253
    UNION ALL SELECT  280, 'Ralts'     , 'Psíquico' ,  28,  25,  25,  45,  35,  40, NULL
    UNION ALL SELECT  281, 'Kirlia'    , 'Psíquico' ,  38,  35,  35,  65,  55,  50, 280
    UNION ALL SELECT  282, 'Gardevoir' , 'Psíquico' ,  68,  65,  65, 125, 115,  80, 281
    UNION ALL SELECT  299, 'Nosepass'  , 'Pedra'    ,  30,  45, 135,  45,  90,  30, NULL
    UNION ALL SELECT  324, 'Torkoal'   , 'Fogo'     ,  70,  85, 140,  85,  70,  20, NULL
    UNION ALL SELECT  390, 'Chimchar'  , 'Fogo'     ,  44,  58,  44,  58,  44,  61, NULL
    UNION ALL SELECT  391, 'Monferno'  , 'Fogo'     ,  64,  78,  52,  78,  52,  81, 390
    UNION ALL SELECT  392, 'Infernape' , 'Fogo'     ,  76, 104,  71, 104,  71, 108, 391
    UNION ALL SELECT  418, 'Buizel'    , 'Água'     ,  55,  65,  35,  60,  30,  85, NULL
    UNION ALL SELECT  419, 'Floatzel'  , 'Água'     ,  85, 105,  55,  85,  50, 115, 418
    UNION ALL SELECT  449, 'Hippopotas', 'Terra'    ,  68,  72,  78,  38,  42,  32, NULL
    UNION ALL SELECT  450, 'Hippowdon' , 'Terra'    , 108, 112, 118,  68,  72,  47, 449
    UNION ALL SELECT  451, 'Skorupi'   , 'Venenoso' ,  40,  50,  90,  30,  55,  65, NULL
    UNION ALL SELECT  452, 'Drapion'   , 'Venenoso' ,  70,  90, 110,  60,  75,  95, 451
    UNION ALL SELECT  453, 'Croagunk'  , 'Venenoso' ,  48,  61,  40,  61,  40,  50, NULL
    UNION ALL SELECT  454, 'Toxicroak' , 'Venenoso' ,  83, 106,  65,  86,  65,  85, 453
    UNION ALL SELECT  524, 'Roggenrola', 'Pedra'    ,  55,  75,  85,  25,  25,  15, NULL
    UNION ALL SELECT  525, 'Boldore'   , 'Pedra'    ,  70, 105, 105,  50,  40,  20, 524
    UNION ALL SELECT  526, 'Gigalith'  , 'Pedra'    ,  85, 135, 130,  60,  80,  25, 525
    UNION ALL SELECT  529, 'Drilbur'   , 'Terra'    ,  60,  85,  40,  30,  45,  68, NULL
    UNION ALL SELECT  530, 'Excadrill' , 'Terra'    , 110, 135,  60,  50,  65,  88, 529
    UNION ALL SELECT  548, 'Petilil'   , 'Planta'   ,  45,  35,  50,  70,  50,  30, NULL
    UNION ALL SELECT  549, 'Lilligant' , 'Planta'   ,  70,  60,  75, 110,  75,  90, 548
    UNION ALL SELECT  568, 'Trubbish'  , 'Venenoso' ,  50,  50,  62,  40,  62,  65, NULL
    UNION ALL SELECT  569, 'Garbodor'  , 'Venenoso' ,  80,  95,  82,  60,  82,  75, 568
    UNION ALL SELECT  656, 'Froakie'   , 'Água'     ,  41,  56,  40,  62,  44,  71, NULL
    UNION ALL SELECT  657, 'Frogadier' , 'Água'     ,  54,  63,  52,  83,  56,  97, 656
    UNION ALL SELECT  658, 'Greninja'  , 'Água'     ,  72,  95,  67, 103,  71, 122, 657
    UNION ALL SELECT  667, 'Litleo'    , 'Fogo'     ,  62,  50,  58,  73,  54,  72, NULL
    UNION ALL SELECT  668, 'Pyroar'    , 'Fogo'     ,  86,  68,  72, 109,  66, 106, 667
    UNION ALL SELECT  677, 'Espurr'    , 'Psíquico' ,  62,  48,  54,  63,  60,  68, NULL
    UNION ALL SELECT  678, 'Meowstic'  , 'Psíquico' ,  74,  48,  76,  83,  81, 104, 677
    UNION ALL SELECT  749, 'Mudbray'   , 'Terra'    ,  70, 100,  70,  45,  55,  45, NULL
    UNION ALL SELECT  750, 'Mudsdale'  , 'Terra'    , 100, 125, 100,  55,  85,  35, 749
) s
JOIN `tipos` t ON t.nome = s.tipo;

-- liga cada espécie à pré-evolução (evolui_de_id), para as evoluções não virarem "básicas"
UPDATE `especies` filho
  JOIN `especies` pai ON pai.pokeapi_id = filho.evolui_de_pokeapi_id
   SET filho.evolui_de_id = pai.id
 WHERE filho.evolui_de_id IS NULL;

-- DML: 4 golpes de cada espécie nova (mesma regra do SeletorDeGolpes);
-- só para quem ainda não tem golpes
INSERT INTO `especie_golpes` (`especie_id`, `golpe_id`)
SELECT e.id, g.id
FROM (
    SELECT 43 AS pid, 'Mega Drain' AS golpe
    UNION ALL SELECT 43, 'Tackle'
    UNION ALL SELECT 43, 'Acid'
    UNION ALL SELECT 43, 'Quick Attack'
    UNION ALL SELECT 44, 'Mega Drain'
    UNION ALL SELECT 44, 'Energy Ball'
    UNION ALL SELECT 44, 'Sludge Bomb'
    UNION ALL SELECT 44, 'Hyper Voice'
    UNION ALL SELECT 45, 'Mega Drain'
    UNION ALL SELECT 45, 'Energy Ball'
    UNION ALL SELECT 45, 'Sludge Bomb'
    UNION ALL SELECT 45, 'Hyper Voice'
    UNION ALL SELECT 50, 'Bone Club'
    UNION ALL SELECT 50, 'Quick Attack'
    UNION ALL SELECT 50, 'Rock Throw'
    UNION ALL SELECT 50, 'Tackle'
    UNION ALL SELECT 51, 'Bone Club'
    UNION ALL SELECT 51, 'Earthquake'
    UNION ALL SELECT 51, 'Rock Slide'
    UNION ALL SELECT 51, 'Body Slam'
    UNION ALL SELECT 58, 'Fire Fang'
    UNION ALL SELECT 58, 'Quick Attack'
    UNION ALL SELECT 58, 'Bone Club'
    UNION ALL SELECT 58, 'Tackle'
    UNION ALL SELECT 59, 'Fire Fang'
    UNION ALL SELECT 59, 'Fire Punch'
    UNION ALL SELECT 59, 'Earthquake'
    UNION ALL SELECT 59, 'Body Slam'
    UNION ALL SELECT 63, 'Confusion'
    UNION ALL SELECT 63, 'Quick Attack'
    UNION ALL SELECT 63, 'Fairy Wind'
    UNION ALL SELECT 63, 'Tackle'
    UNION ALL SELECT 64, 'Confusion'
    UNION ALL SELECT 64, 'Psychic'
    UNION ALL SELECT 64, 'Moonblast'
    UNION ALL SELECT 64, 'Hyper Voice'
    UNION ALL SELECT 65, 'Confusion'
    UNION ALL SELECT 65, 'Psychic'
    UNION ALL SELECT 65, 'Moonblast'
    UNION ALL SELECT 65, 'Hyper Voice'
    UNION ALL SELECT 81, 'Thunder Shock'
    UNION ALL SELECT 81, 'Tackle'
    UNION ALL SELECT 81, 'Mirror Shot'
    UNION ALL SELECT 81, 'Quick Attack'
    UNION ALL SELECT 82, 'Thunder Shock'
    UNION ALL SELECT 82, 'Thunderbolt'
    UNION ALL SELECT 82, 'Flash Cannon'
    UNION ALL SELECT 82, 'Hyper Voice'
    UNION ALL SELECT 96, 'Psycho Cut'
    UNION ALL SELECT 96, 'Tackle'
    UNION ALL SELECT 96, 'Play Rough'
    UNION ALL SELECT 96, 'Quick Attack'
    UNION ALL SELECT 97, 'Psycho Cut'
    UNION ALL SELECT 97, 'Zen Headbutt'
    UNION ALL SELECT 97, 'Play Rough'
    UNION ALL SELECT 97, 'Body Slam'
    UNION ALL SELECT 109, 'Poison Fang'
    UNION ALL SELECT 109, 'Tackle'
    UNION ALL SELECT 109, 'Bite'
    UNION ALL SELECT 109, 'Quick Attack'
    UNION ALL SELECT 110, 'Poison Fang'
    UNION ALL SELECT 110, 'Poison Jab'
    UNION ALL SELECT 110, 'Crunch'
    UNION ALL SELECT 110, 'Body Slam'
    UNION ALL SELECT 111, 'Bone Club'
    UNION ALL SELECT 111, 'Tackle'
    UNION ALL SELECT 111, 'Rock Throw'
    UNION ALL SELECT 111, 'Quick Attack'
    UNION ALL SELECT 112, 'Bone Club'
    UNION ALL SELECT 112, 'Earthquake'
    UNION ALL SELECT 112, 'Rock Slide'
    UNION ALL SELECT 112, 'Body Slam'
    UNION ALL SELECT 114, 'Mega Drain'
    UNION ALL SELECT 114, 'Energy Ball'
    UNION ALL SELECT 114, 'Sludge Bomb'
    UNION ALL SELECT 114, 'Hyper Voice'
    UNION ALL SELECT 120, 'Water Gun'
    UNION ALL SELECT 120, 'Quick Attack'
    UNION ALL SELECT 120, 'Powder Snow'
    UNION ALL SELECT 120, 'Tackle'
    UNION ALL SELECT 121, 'Water Gun'
    UNION ALL SELECT 121, 'Surf'
    UNION ALL SELECT 121, 'Psychic'
    UNION ALL SELECT 121, 'Hyper Voice'
    UNION ALL SELECT 177, 'Confusion'
    UNION ALL SELECT 177, 'Quick Attack'
    UNION ALL SELECT 177, 'Gust'
    UNION ALL SELECT 177, 'Tackle'
    UNION ALL SELECT 178, 'Confusion'
    UNION ALL SELECT 178, 'Psychic'
    UNION ALL SELECT 178, 'Air Slash'
    UNION ALL SELECT 178, 'Hyper Voice'
    UNION ALL SELECT 179, 'Thunder Shock'
    UNION ALL SELECT 179, 'Tackle'
    UNION ALL SELECT 179, 'Mirror Shot'
    UNION ALL SELECT 179, 'Quick Attack'
    UNION ALL SELECT 180, 'Thunder Shock'
    UNION ALL SELECT 180, 'Thunderbolt'
    UNION ALL SELECT 180, 'Flash Cannon'
    UNION ALL SELECT 180, 'Hyper Voice'
    UNION ALL SELECT 181, 'Thunder Shock'
    UNION ALL SELECT 181, 'Thunderbolt'
    UNION ALL SELECT 181, 'Flash Cannon'
    UNION ALL SELECT 181, 'Hyper Voice'
    UNION ALL SELECT 207, 'Bone Club'
    UNION ALL SELECT 207, 'Earthquake'
    UNION ALL SELECT 207, 'Drill Peck'
    UNION ALL SELECT 207, 'Body Slam'
    UNION ALL SELECT 252, 'Mega Drain'
    UNION ALL SELECT 252, 'Quick Attack'
    UNION ALL SELECT 252, 'Acid'
    UNION ALL SELECT 252, 'Tackle'
    UNION ALL SELECT 253, 'Mega Drain'
    UNION ALL SELECT 253, 'Energy Ball'
    UNION ALL SELECT 253, 'Sludge Bomb'
    UNION ALL SELECT 253, 'Hyper Voice'
    UNION ALL SELECT 254, 'Mega Drain'
    UNION ALL SELECT 254, 'Energy Ball'
    UNION ALL SELECT 254, 'Sludge Bomb'
    UNION ALL SELECT 254, 'Hyper Voice'
    UNION ALL SELECT 280, 'Confusion'
    UNION ALL SELECT 280, 'Tackle'
    UNION ALL SELECT 280, 'Fairy Wind'
    UNION ALL SELECT 280, 'Quick Attack'
    UNION ALL SELECT 281, 'Confusion'
    UNION ALL SELECT 281, 'Psychic'
    UNION ALL SELECT 281, 'Moonblast'
    UNION ALL SELECT 281, 'Hyper Voice'
    UNION ALL SELECT 282, 'Confusion'
    UNION ALL SELECT 282, 'Psychic'
    UNION ALL SELECT 282, 'Moonblast'
    UNION ALL SELECT 282, 'Hyper Voice'
    UNION ALL SELECT 299, 'Rock Throw'
    UNION ALL SELECT 299, 'Rock Slide'
    UNION ALL SELECT 299, 'Earthquake'
    UNION ALL SELECT 299, 'Body Slam'
    UNION ALL SELECT 324, 'Fire Fang'
    UNION ALL SELECT 324, 'Fire Punch'
    UNION ALL SELECT 324, 'Earthquake'
    UNION ALL SELECT 324, 'Body Slam'
    UNION ALL SELECT 390, 'Fire Fang'
    UNION ALL SELECT 390, 'Quick Attack'
    UNION ALL SELECT 390, 'Bone Club'
    UNION ALL SELECT 390, 'Tackle'
    UNION ALL SELECT 391, 'Fire Fang'
    UNION ALL SELECT 391, 'Fire Punch'
    UNION ALL SELECT 391, 'Submission'
    UNION ALL SELECT 391, 'Body Slam'
    UNION ALL SELECT 392, 'Fire Fang'
    UNION ALL SELECT 392, 'Fire Punch'
    UNION ALL SELECT 392, 'Submission'
    UNION ALL SELECT 392, 'Body Slam'
    UNION ALL SELECT 418, 'Aqua Jet'
    UNION ALL SELECT 418, 'Quick Attack'
    UNION ALL SELECT 418, 'Ice Shard'
    UNION ALL SELECT 418, 'Tackle'
    UNION ALL SELECT 419, 'Aqua Jet'
    UNION ALL SELECT 419, 'Waterfall'
    UNION ALL SELECT 419, 'Ice Punch'
    UNION ALL SELECT 419, 'Body Slam'
    UNION ALL SELECT 449, 'Bone Club'
    UNION ALL SELECT 449, 'Tackle'
    UNION ALL SELECT 449, 'Rock Throw'
    UNION ALL SELECT 449, 'Quick Attack'
    UNION ALL SELECT 450, 'Bone Club'
    UNION ALL SELECT 450, 'Earthquake'
    UNION ALL SELECT 450, 'Rock Slide'
    UNION ALL SELECT 450, 'Body Slam'
    UNION ALL SELECT 451, 'Poison Fang'
    UNION ALL SELECT 451, 'Quick Attack'
    UNION ALL SELECT 451, 'Fury Cutter'
    UNION ALL SELECT 451, 'Tackle'
    UNION ALL SELECT 452, 'Poison Fang'
    UNION ALL SELECT 452, 'Poison Jab'
    UNION ALL SELECT 452, 'Crunch'
    UNION ALL SELECT 452, 'Body Slam'
    UNION ALL SELECT 453, 'Poison Fang'
    UNION ALL SELECT 453, 'Tackle'
    UNION ALL SELECT 453, 'Karate Chop'
    UNION ALL SELECT 453, 'Quick Attack'
    UNION ALL SELECT 454, 'Poison Fang'
    UNION ALL SELECT 454, 'Poison Jab'
    UNION ALL SELECT 454, 'Submission'
    UNION ALL SELECT 454, 'Body Slam'
    UNION ALL SELECT 524, 'Rock Throw'
    UNION ALL SELECT 524, 'Tackle'
    UNION ALL SELECT 524, 'Bone Club'
    UNION ALL SELECT 524, 'Quick Attack'
    UNION ALL SELECT 525, 'Rock Throw'
    UNION ALL SELECT 525, 'Rock Slide'
    UNION ALL SELECT 525, 'Earthquake'
    UNION ALL SELECT 525, 'Body Slam'
    UNION ALL SELECT 526, 'Rock Throw'
    UNION ALL SELECT 526, 'Rock Slide'
    UNION ALL SELECT 526, 'Earthquake'
    UNION ALL SELECT 526, 'Body Slam'
    UNION ALL SELECT 529, 'Bone Club'
    UNION ALL SELECT 529, 'Quick Attack'
    UNION ALL SELECT 529, 'Rock Throw'
    UNION ALL SELECT 529, 'Tackle'
    UNION ALL SELECT 530, 'Bone Club'
    UNION ALL SELECT 530, 'Earthquake'
    UNION ALL SELECT 530, 'Iron Head'
    UNION ALL SELECT 530, 'Body Slam'
    UNION ALL SELECT 548, 'Mega Drain'
    UNION ALL SELECT 548, 'Tackle'
    UNION ALL SELECT 548, 'Acid'
    UNION ALL SELECT 548, 'Quick Attack'
    UNION ALL SELECT 549, 'Mega Drain'
    UNION ALL SELECT 549, 'Energy Ball'
    UNION ALL SELECT 549, 'Sludge Bomb'
    UNION ALL SELECT 549, 'Hyper Voice'
    UNION ALL SELECT 568, 'Poison Fang'
    UNION ALL SELECT 568, 'Quick Attack'
    UNION ALL SELECT 568, 'Bite'
    UNION ALL SELECT 568, 'Tackle'
    UNION ALL SELECT 569, 'Poison Fang'
    UNION ALL SELECT 569, 'Poison Jab'
    UNION ALL SELECT 569, 'Crunch'
    UNION ALL SELECT 569, 'Body Slam'
    UNION ALL SELECT 656, 'Water Gun'
    UNION ALL SELECT 656, 'Quick Attack'
    UNION ALL SELECT 656, 'Powder Snow'
    UNION ALL SELECT 656, 'Tackle'
    UNION ALL SELECT 657, 'Water Gun'
    UNION ALL SELECT 657, 'Surf'
    UNION ALL SELECT 657, 'Ice Beam'
    UNION ALL SELECT 657, 'Hyper Voice'
    UNION ALL SELECT 658, 'Water Gun'
    UNION ALL SELECT 658, 'Surf'
    UNION ALL SELECT 658, 'Dark Pulse'
    UNION ALL SELECT 658, 'Hyper Voice'
    UNION ALL SELECT 667, 'Ember'
    UNION ALL SELECT 667, 'Quick Attack'
    UNION ALL SELECT 667, 'Swift'
    UNION ALL SELECT 667, 'Tackle'
    UNION ALL SELECT 668, 'Ember'
    UNION ALL SELECT 668, 'Flamethrower'
    UNION ALL SELECT 668, 'Hyper Voice'
    UNION ALL SELECT 668, 'Quick Attack'
    UNION ALL SELECT 677, 'Confusion'
    UNION ALL SELECT 677, 'Quick Attack'
    UNION ALL SELECT 677, 'Fairy Wind'
    UNION ALL SELECT 677, 'Tackle'
    UNION ALL SELECT 678, 'Confusion'
    UNION ALL SELECT 678, 'Psychic'
    UNION ALL SELECT 678, 'Moonblast'
    UNION ALL SELECT 678, 'Hyper Voice'
    UNION ALL SELECT 749, 'Bone Club'
    UNION ALL SELECT 749, 'Tackle'
    UNION ALL SELECT 749, 'Rock Throw'
    UNION ALL SELECT 749, 'Quick Attack'
    UNION ALL SELECT 750, 'Bone Club'
    UNION ALL SELECT 750, 'Earthquake'
    UNION ALL SELECT 750, 'Rock Slide'
    UNION ALL SELECT 750, 'Body Slam'
) m
JOIN `especies` e ON e.pokeapi_id = m.pid
JOIN `golpes` g ON g.nome = m.golpe
WHERE NOT EXISTS (SELECT 1 FROM `especie_golpes` x WHERE x.especie_id = e.id);

-- DML: os times (pela ordem do líder e pelo número da PokéAPI).
-- Times variados, misturando gerações (a geração está no comentário).
-- O DELETE deixa rodar de novo para trocar os times de um banco que já existe.
DELETE FROM `lider_pokemons`;
INSERT IGNORE INTO `lider_pokemons` (`lider_id`, `posicao`, `especie_id`)
SELECT l.id, m.posicao, e.id
FROM (
    SELECT 1 AS ordem, 1 AS posicao, 524 AS pid  -- Roggenrola (5ª geração)
    UNION ALL SELECT 1, 2, 299  -- Nosepass (3ª geração)
    UNION ALL SELECT 2, 1, 418  -- Buizel (4ª geração)
    UNION ALL SELECT 2, 2, 656  -- Froakie (6ª geração)
    UNION ALL SELECT 2, 3, 121  -- Starmie (1ª geração)
    UNION ALL SELECT 3, 1,  81  -- Magnemite (1ª geração)
    UNION ALL SELECT 3, 2, 180  -- Flaaffy (2ª geração)
    UNION ALL SELECT 3, 3, 181  -- Ampharos (2ª geração)
    UNION ALL SELECT 4, 1, 114  -- Tangela (1ª geração)
    UNION ALL SELECT 4, 2, 253  -- Grovyle (3ª geração)
    UNION ALL SELECT 4, 3,  44  -- Gloom (1ª geração)
    UNION ALL SELECT 4, 4, 549  -- Lilligant (5ª geração)
    UNION ALL SELECT 5, 1, 109  -- Koffing (1ª geração)
    UNION ALL SELECT 5, 2, 569  -- Garbodor (5ª geração)
    UNION ALL SELECT 5, 3, 452  -- Drapion (4ª geração)
    UNION ALL SELECT 5, 4, 454  -- Toxicroak (4ª geração)
    UNION ALL SELECT 6, 1, 177  -- Natu (2ª geração)
    UNION ALL SELECT 6, 2,  64  -- Kadabra (1ª geração)
    UNION ALL SELECT 6, 3, 678  -- Meowstic (6ª geração)
    UNION ALL SELECT 6, 4,  97  -- Hypno (1ª geração)
    UNION ALL SELECT 6, 5, 282  -- Gardevoir (3ª geração)
    UNION ALL SELECT 7, 1,  58  -- Growlithe (1ª geração)
    UNION ALL SELECT 7, 2, 391  -- Monferno (4ª geração)
    UNION ALL SELECT 7, 3, 668  -- Pyroar (6ª geração)
    UNION ALL SELECT 7, 4, 324  -- Torkoal (3ª geração)
    UNION ALL SELECT 7, 5,  59  -- Arcanine (1ª geração)
    UNION ALL SELECT 8, 1,  51  -- Dugtrio (1ª geração)
    UNION ALL SELECT 8, 2, 207  -- Gligar (2ª geração)
    UNION ALL SELECT 8, 3, 749  -- Mudbray (7ª geração)
    UNION ALL SELECT 8, 4, 449  -- Hippopotas (4ª geração)
    UNION ALL SELECT 8, 5, 530  -- Excadrill (5ª geração)
    UNION ALL SELECT 8, 6, 112  -- Rhydon (1ª geração)
) m
JOIN `lideres` l ON l.ordem = m.ordem
JOIN `especies` e ON e.pokeapi_id = m.pid;

-- =====================================================================
-- Lendários
-- =====================================================================
-- DML: marca os lendários e míticos de todas as gerações (pelo número da PokéAPI).
-- O importador também marca (is_legendary / is_mythical da PokéAPI); este UPDATE
-- serve para espécies que já estavam no banco.
-- Inclui os paradoxos lendários (Gouging Fire, Raging Bolt...) e as Ultra
-- Beasts (Nihilego, Buzzwole...), que a PokéAPI não marca como lendários (ImportadorPokemon::PARADOXOS_LENDARIOS).
UPDATE especies SET lendario = 1
 WHERE pokeapi_id IN (
    144, 145, 146, 150, 243, 244, 245, 249, 250, 377, 378, 379,
    380, 381, 382, 383, 384, 480, 481, 482, 483, 484, 485, 486,
    487, 488, 638, 639, 640, 641, 642, 643, 644, 645, 646, 716,
    717, 718, 772, 773, 785, 786, 787, 788, 789, 790, 791, 792,
    800, 888, 889, 890, 891, 892, 894, 895, 896, 897, 898, 905,
    1001, 1002, 1003, 1004, 1007, 1008, 1014, 1015, 1016, 1017, 1024,
    1009, 1010, 1020, 1021, 1022, 1023, -- paradoxos lendários
    151, 251, 385, 386, 489, 490, 491, 492, 493, 494, 647, 648,
    649, 719, 720, 721, 801, 802, 807, 808, 809, 893, 1025, -- míticos
    793, 794, 795, 796, 797, 798, 799, 803, 804, 805, 806 -- Ultra Beasts
 );
