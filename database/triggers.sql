-- =====================================================================
-- MONSTROBOLSO - triggers.sql  (triggers + procedure com DTL)
-- Rodar por último (depois de schema, seed e views).
--
-- COMO TRIGGER FUNCIONA (para a banca):
--   É um bloco de SQL que o banco executa AUTOMATICAMENTE antes (BEFORE)
--   ou depois (AFTER) de um INSERT, UPDATE ou DELETE em uma tabela.
--   Dentro dele, NEW.coluna é o valor novo da linha e OLD.coluna o valor
--   antigo. Em BEFORE é possível alterar NEW; SIGNAL SQLSTATE '45000'
--   aborta a operação com uma mensagem de erro (vira PDOException no PHP).
--
-- DELIMITER é necessário para o cliente não cortar o corpo no primeiro ';'.
-- (phpMyAdmin e o cliente mysql entendem DELIMITER ao importar o arquivo.)
-- =====================================================================
USE monstrobolso;

DROP TRIGGER IF EXISTS trg_batalhas_premio;
DROP TRIGGER IF EXISTS trg_pokemons_hp_insert;
DROP TRIGGER IF EXISTS trg_pokemons_hp_update;
DROP TRIGGER IF EXISTS trg_usuarios_log_perfil;
DROP TRIGGER IF EXISTS trg_log_loja_valida;
DROP TRIGGER IF EXISTS trg_time_pokemon_dono;
DROP TRIGGER IF EXISTS trg_pokemons_desbloqueia;
DROP PROCEDURE IF EXISTS sp_comprar_item;

DELIMITER $$

-- 1) AFTER INSERT em batalhas: vitória do jogador paga o prêmio ao banco
CREATE TRIGGER trg_batalhas_premio
AFTER INSERT ON batalhas
FOR EACH ROW
BEGIN
    IF NEW.vencedor = 'jogador' AND NEW.dinheiro_ganho > 0 THEN
        UPDATE usuarios
           SET pokedollar = pokedollar + NEW.dinheiro_ganho
         WHERE id = NEW.usuario_id;
    END IF;
END$$

-- 2) BEFORE INSERT em pokemons: HP nunca passa do máximo
CREATE TRIGGER trg_pokemons_hp_insert
BEFORE INSERT ON pokemons
FOR EACH ROW
BEGIN
    IF NEW.hp_atual > NEW.hp_max THEN
        SET NEW.hp_atual = NEW.hp_max;
    END IF;
    SET NEW.desmaiado = (NEW.hp_atual = 0);
END$$

-- 3) BEFORE UPDATE em pokemons: limita HP e controla desmaio/revive
CREATE TRIGGER trg_pokemons_hp_update
BEFORE UPDATE ON pokemons
FOR EACH ROW
BEGIN
    IF NEW.hp_atual > NEW.hp_max THEN
        SET NEW.hp_atual = NEW.hp_max;
    END IF;

    IF NEW.hp_atual = 0 THEN
        SET NEW.desmaiado = 1;
        SET NEW.status = 'normal';     -- desmaiou: condição some
    ELSE
        SET NEW.desmaiado = 0;         -- tem HP (ex.: Revive): está de pé
    END IF;
END$$

-- 4) AFTER UPDATE em usuarios: troca de perfil vai para log_perfis
CREATE TRIGGER trg_usuarios_log_perfil
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
    IF OLD.perfil <> NEW.perfil THEN
        INSERT INTO log_perfis (usuario_id, perfil_antigo, perfil_novo)
        VALUES (NEW.id, OLD.perfil, NEW.perfil);
    END IF;
END$$

-- 5) BEFORE INSERT em log_loja: não aceita compra sem quantidade
CREATE TRIGGER trg_log_loja_valida
BEFORE INSERT ON log_loja
FOR EACH ROW
BEGIN
    IF NEW.quantidade < 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Quantidade de compra deve ser maior que zero.';
    END IF;
END$$

-- 6) BEFORE INSERT em time_pokemon: Pokémon precisa ser do dono do time
CREATE TRIGGER trg_time_pokemon_dono
BEFORE INSERT ON time_pokemon
FOR EACH ROW
BEGIN
    DECLARE v_dono_time INT;
    DECLARE v_dono_poke INT;

    SELECT usuario_id INTO v_dono_time FROM times    WHERE id = NEW.time_id;
    SELECT usuario_id INTO v_dono_poke FROM pokemons WHERE id = NEW.pokemon_id;

    IF v_dono_time IS NULL OR v_dono_poke IS NULL OR v_dono_time <> v_dono_poke THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Este Pokémon não pertence ao dono do time.';
    END IF;
END$$

-- 7) AFTER UPDATE em pokemons: evoluiu (mudou de espécie)? A nova espécie
--    fica desbloqueada para o jogador, que pode pegá-la de novo no Team
--    Builder mesmo depois de remover o Pokémon. INSERT IGNORE: se já estava
--    desbloqueada (chave primária repetida), não faz nada.
CREATE TRIGGER trg_pokemons_desbloqueia
AFTER UPDATE ON pokemons
FOR EACH ROW
BEGIN
    IF NEW.especie_id <> OLD.especie_id THEN
        INSERT IGNORE INTO especies_desbloqueadas (usuario_id, especie_id)
        VALUES (NEW.usuario_id, NEW.especie_id);
    END IF;
END$$

-- =====================================================================
-- Procedure com TRANSAÇÃO (DTL: START TRANSACTION / COMMIT / ROLLBACK)
-- Compra de item: debita saldo, entrega ao inventário e grava o log.
-- Se QUALQUER passo falhar, tudo é desfeito (ROLLBACK).
-- Uso no PHP:  CALL sp_comprar_item(:usuario, :item, :qtd)
-- =====================================================================
CREATE PROCEDURE sp_comprar_item(
    IN p_usuario_id INT UNSIGNED,
    IN p_item_id    INT UNSIGNED,
    IN p_quantidade INT UNSIGNED
)
BEGIN
    DECLARE v_preco  INT UNSIGNED;
    DECLARE v_saldo  INT UNSIGNED;
    DECLARE v_total  BIGINT UNSIGNED;

    -- Qualquer erro SQL: desfaz tudo e repassa o erro para o PHP
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_quantidade < 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quantidade inválida.';
    END IF;

    START TRANSACTION;

    SELECT preco INTO v_preco FROM itens WHERE id = p_item_id;
    IF v_preco IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Item não encontrado.';
    END IF;

    -- FOR UPDATE trava a linha do usuário durante a compra
    SELECT pokedollar INTO v_saldo FROM usuarios WHERE id = p_usuario_id FOR UPDATE;
    IF v_saldo IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuário não encontrado.';
    END IF;

    SET v_total = v_preco * p_quantidade;
    IF v_saldo < v_total THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Saldo insuficiente.';
    END IF;

    UPDATE usuarios SET pokedollar = pokedollar - v_total WHERE id = p_usuario_id;

    INSERT INTO inventario (usuario_id, item_id, quantidade)
    VALUES (p_usuario_id, p_item_id, p_quantidade)
    ON DUPLICATE KEY UPDATE quantidade = quantidade + p_quantidade;

    INSERT INTO log_loja (usuario_id, item_id, quantidade, valor_total)
    VALUES (p_usuario_id, p_item_id, p_quantidade, v_total);

    COMMIT;
END$$

DELIMITER ;
