-- =====================================================================
-- MONSTROBOLSO - schema.sql  (DDL)
-- Compatível com MariaDB 10.4 / MySQL 8 (XAMPP)
-- Decisões: 1 tipo por espécie; golpes ligados à espécie.
-- Ordem de execução: schema.sql -> seed.sql -> views.sql -> triggers.sql
-- =====================================================================
CREATE DATABASE IF NOT EXISTS monstrobolso CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_general_ci;

USE monstrobolso;

SET
    FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS batalha_turnos,
batalhas,
log_loja,
log_perfis,
inventario,
especies_desbloqueadas,
time_pokemon,
times,
pokemons,
especie_golpes,
golpes,
especies,
itens,
tipo_vantagens,
tipos,
usuarios;

SET
    FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Usuários e perfis (controle de usuário / troca de perfil)
-- ---------------------------------------------------------------------
CREATE TABLE
    usuarios (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(60) NOT NULL,
        email VARCHAR(120) NOT NULL UNIQUE,
        senha_hash VARCHAR(255) NOT NULL,
        perfil ENUM ('jogador', 'admin') NOT NULL DEFAULT 'jogador',
        pokedollar INT UNSIGNED NOT NULL DEFAULT 1000,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Tipos e tabela de vantagens (base da trait de tipos no PHP)
-- ---------------------------------------------------------------------
CREATE TABLE
    tipos (
        id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(20) NOT NULL UNIQUE
    ) ENGINE = InnoDB;

CREATE TABLE
    tipo_vantagens (
        tipo_atacante_id TINYINT UNSIGNED NOT NULL,
        tipo_defensor_id TINYINT UNSIGNED NOT NULL,
        multiplicador DECIMAL(2, 1) NOT NULL, -- 0.0, 0.5, 2.0
        PRIMARY KEY (tipo_atacante_id, tipo_defensor_id),
        FOREIGN KEY (tipo_atacante_id) REFERENCES tipos (id),
        FOREIGN KEY (tipo_defensor_id) REFERENCES tipos (id)
    ) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Itens da loja (criado antes de especies, pois evolução usa item)
-- ---------------------------------------------------------------------
CREATE TABLE
    itens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(40) NOT NULL UNIQUE,
        categoria ENUM ('cura_hp', 'cura_status', 'reviver', 'evolucao') NOT NULL,
        descricao VARCHAR(200) NOT NULL,
        preco INT UNSIGNED NOT NULL,
        cura_hp INT UNSIGNED NOT NULL DEFAULT 0, -- cura fixa (Potion = 20)
        cura_percentual TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0..100 (Max Potion = 100)
        status_curado ENUM (
            'veneno',
            'queimado',
            'congelado',
            'dormindo',
            'paralisado'
        ) NULL,
        cura_todos_status TINYINT (1) NOT NULL DEFAULT 0, -- Full Heal / Full Restore
        tipo_id TINYINT UNSIGNED NULL, -- pedras: tipo da linha que ela evolui
        CHECK (cura_percentual <= 100),
        FOREIGN KEY (tipo_id) REFERENCES tipos (id)
    ) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Espécies (dados base, vindos da PokéAPI) - >= 10 atributos
-- nome, hp e velocidade são obrigatórios no enunciado
-- ---------------------------------------------------------------------
CREATE TABLE
    especies (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        pokeapi_id INT UNSIGNED NOT NULL UNIQUE,
        nome VARCHAR(40) NOT NULL,
        tipo_id TINYINT UNSIGNED NOT NULL,
        hp SMALLINT UNSIGNED NOT NULL,
        ataque SMALLINT UNSIGNED NOT NULL,
        defesa SMALLINT UNSIGNED NOT NULL,
        sp_atk SMALLINT UNSIGNED NOT NULL,
        sp_def SMALLINT UNSIGNED NOT NULL,
        velocidade SMALLINT UNSIGNED NOT NULL,
        sprite_url VARCHAR(255) NULL,
        evolui_para_id INT UNSIGNED NULL,
        item_evolucao_id INT UNSIGNED NULL,
        -- de quem ela evolui. NULL = espécie básica (pode entrar no time).
        -- Separado de evolui_para_id porque um pai pode ter várias evoluções
        -- (Eevee -> Vaporeon, Jolteon, Flareon) e evolui_para_id guarda só uma.
        evolui_de_id INT UNSIGNED NULL,
        -- pokeapi_id do pai, guardado mesmo se o pai ainda não foi importado
        -- (ex.: Pikachu vem do Pichu, da 2ª geração). Quando o pai entra,
        -- o importador preenche evolui_de_id a partir daqui.
        evolui_de_pokeapi_id INT UNSIGNED NULL,
        FOREIGN KEY (tipo_id) REFERENCES tipos (id),
        FOREIGN KEY (evolui_para_id) REFERENCES especies (id) ON DELETE SET NULL,
        FOREIGN KEY (evolui_de_id) REFERENCES especies (id) ON DELETE SET NULL,
        FOREIGN KEY (item_evolucao_id) REFERENCES itens (id) ON DELETE SET NULL
    ) ENGINE = InnoDB;

CREATE TABLE
    golpes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(40) NOT NULL UNIQUE,
        tipo_id TINYINT UNSIGNED NOT NULL,
        poder SMALLINT UNSIGNED NOT NULL,
        precisao TINYINT UNSIGNED NOT NULL DEFAULT 100,
        categoria ENUM ('fisico', 'especial') NOT NULL,
        FOREIGN KEY (tipo_id) REFERENCES tipos (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    especie_golpes (
        especie_id INT UNSIGNED NOT NULL,
        golpe_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (especie_id, golpe_id),
        FOREIGN KEY (especie_id) REFERENCES especies (id) ON DELETE CASCADE,
        FOREIGN KEY (golpe_id) REFERENCES golpes (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Pokémon do jogador (instância)
-- ---------------------------------------------------------------------
CREATE TABLE
    pokemons (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL,
        especie_id INT UNSIGNED NOT NULL,
        apelido VARCHAR(40) NULL,
        nivel TINYINT UNSIGNED NOT NULL DEFAULT 5,
        hp_max SMALLINT UNSIGNED NOT NULL,
        hp_atual SMALLINT UNSIGNED NOT NULL,
        status ENUM (
            'normal',
            'veneno',
            'queimado',
            'congelado',
            'dormindo',
            'paralisado'
        ) NOT NULL DEFAULT 'normal',
        desmaiado TINYINT (1) NOT NULL DEFAULT 0,
        shiny TINYINT (1) NOT NULL DEFAULT 0, -- 1 chance em 64 ao entrar no time
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
        FOREIGN KEY (especie_id) REFERENCES especies (id)
    ) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Time (até 6) - Team Builder
-- ---------------------------------------------------------------------
CREATE TABLE
    times (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL UNIQUE, -- 1 time ativo por usuário
        nome VARCHAR(40) NOT NULL DEFAULT 'Meu time',
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    time_pokemon (
        time_id INT UNSIGNED NOT NULL,
        posicao TINYINT UNSIGNED NOT NULL, -- 1..6 => limite de 6 garantido
        pokemon_id INT UNSIGNED NOT NULL UNIQUE, -- um Pokémon só em um slot
        PRIMARY KEY (time_id, posicao),
        CHECK (posicao BETWEEN 1 AND 6),
        FOREIGN KEY (time_id) REFERENCES times (id) ON DELETE CASCADE,
        FOREIGN KEY (pokemon_id) REFERENCES pokemons (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Espécies evoluídas que o jogador já conseguiu (por pedra).
-- Básicas não precisam estar aqui: elas estão sempre liberadas.
-- Preenchida pelo trigger trg_pokemons_desbloqueia (triggers.sql).
-- ---------------------------------------------------------------------
CREATE TABLE
    especies_desbloqueadas (
        usuario_id INT UNSIGNED NOT NULL,
        especie_id INT UNSIGNED NOT NULL,
        desbloqueada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (usuario_id, especie_id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
        FOREIGN KEY (especie_id) REFERENCES especies (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Inventário
-- ---------------------------------------------------------------------
CREATE TABLE
    inventario (
        usuario_id INT UNSIGNED NOT NULL,
        item_id INT UNSIGNED NOT NULL,
        quantidade INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (usuario_id, item_id),
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES itens (id)
    ) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- LOGS: batalhas, turnos, loja e perfis
-- ---------------------------------------------------------------------
CREATE TABLE
    batalhas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL,
        oponente_nome VARCHAR(60) NOT NULL,
        vencedor ENUM ('jogador', 'oponente') NOT NULL,
        dinheiro_ganho INT UNSIGNED NOT NULL DEFAULT 0,
        total_turnos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        data_batalha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    batalha_turnos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        batalha_id INT UNSIGNED NOT NULL,
        turno SMALLINT UNSIGNED NOT NULL,
        ator ENUM ('jogador', 'oponente') NOT NULL,
        acao VARCHAR(60) NOT NULL, -- nome do golpe / item / troca
        dano SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        hp_restante SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        FOREIGN KEY (batalha_id) REFERENCES batalhas (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    log_loja (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL,
        item_id INT UNSIGNED NOT NULL,
        quantidade INT UNSIGNED NOT NULL,
        valor_total INT UNSIGNED NOT NULL,
        data_compra TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES itens (id)
    ) ENGINE = InnoDB;

CREATE TABLE
    log_perfis (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT UNSIGNED NOT NULL,
        perfil_antigo ENUM ('jogador', 'admin') NOT NULL,
        perfil_novo ENUM ('jogador', 'admin') NOT NULL,
        data_troca TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
    ) ENGINE = InnoDB;

-- Índices úteis para as consultas das views
CREATE INDEX idx_pokemons_usuario ON pokemons (usuario_id);

CREATE INDEX idx_batalhas_usuario ON batalhas (usuario_id, data_batalha);

CREATE INDEX idx_log_loja_usuario ON log_loja (usuario_id, data_compra);