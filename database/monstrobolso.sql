-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 23/11/2026 às 00:52
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `monstrobolso`
--

-- Cria e seleciona o banco (assim a importação funciona sem escolher o banco antes).
-- Para reinstalar do zero, exclua o banco "monstrobolso" antes de importar.
CREATE DATABASE IF NOT EXISTS `monstrobolso` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `monstrobolso`;

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_comprar_item` (IN `p_usuario_id` INT UNSIGNED, IN `p_item_id` INT UNSIGNED, IN `p_quantidade` INT UNSIGNED)   BEGIN
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

-- --------------------------------------------------------

--
-- Estrutura para tabela `batalhas`
--

CREATE TABLE `batalhas` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `oponente_nome` varchar(60) NOT NULL,
  `vencedor` enum('jogador','oponente') NOT NULL,
  `dinheiro_ganho` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_turnos` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `data_batalha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `batalhas`
--
DELIMITER $$
CREATE TRIGGER `trg_batalhas_premio` AFTER INSERT ON `batalhas` FOR EACH ROW BEGIN
    IF NEW.vencedor = 'jogador' AND NEW.dinheiro_ganho > 0 THEN
        UPDATE usuarios
           SET pokedollar = pokedollar + NEW.dinheiro_ganho
         WHERE id = NEW.usuario_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `batalha_turnos`
--

CREATE TABLE `batalha_turnos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batalha_id` int(10) UNSIGNED NOT NULL,
  `turno` smallint(5) UNSIGNED NOT NULL,
  `ator` enum('jogador','oponente') NOT NULL,
  `acao` varchar(60) NOT NULL,
  `dano` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `hp_restante` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `especies`
--

CREATE TABLE `especies` (
  `id` int(10) UNSIGNED NOT NULL,
  `pokeapi_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(40) NOT NULL,
  `tipo_id` tinyint(3) UNSIGNED NOT NULL,
  `hp` smallint(5) UNSIGNED NOT NULL,
  `ataque` smallint(5) UNSIGNED NOT NULL,
  `defesa` smallint(5) UNSIGNED NOT NULL,
  `sp_atk` smallint(5) UNSIGNED NOT NULL,
  `sp_def` smallint(5) UNSIGNED NOT NULL,
  `velocidade` smallint(5) UNSIGNED NOT NULL,
  `sprite_url` varchar(255) DEFAULT NULL,
  `evolui_para_id` int(10) UNSIGNED DEFAULT NULL,
  `item_evolucao_id` int(10) UNSIGNED DEFAULT NULL,
  `evolui_de_id` int(10) UNSIGNED DEFAULT NULL,
  `evolui_de_pokeapi_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `especies`
--

INSERT INTO `especies` (`id`, `pokeapi_id`, `nome`, `tipo_id`, `hp`, `ataque`, `defesa`, `sp_atk`, `sp_def`, `velocidade`, `sprite_url`, `evolui_para_id`, `item_evolucao_id`, `evolui_de_id`, `evolui_de_pokeapi_id`) VALUES
(1, 1, 'Bulbasaur', 12, 45, 49, 49, 65, 65, 45, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/1.png', 2, 15, NULL, NULL),
(2, 2, 'Ivysaur', 12, 60, 62, 63, 80, 80, 60, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/2.png', 3, 15, 1, 1),
(3, 3, 'Venusaur', 12, 80, 82, 83, 100, 100, 80, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/3.png', NULL, NULL, 2, 2),
(4, 4, 'Charmander', 10, 39, 52, 43, 60, 50, 65, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/4.png', 5, 13, NULL, NULL),
(5, 5, 'Charmeleon', 10, 58, 64, 58, 80, 65, 80, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/5.png', 6, 13, 4, 4),
(6, 6, 'Charizard', 10, 78, 84, 78, 109, 85, 100, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/6.png', NULL, NULL, 5, 5),
(7, 7, 'Squirtle', 11, 44, 48, 65, 50, 64, 43, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/7.png', 8, 14, NULL, NULL),
(8, 8, 'Wartortle', 11, 59, 63, 80, 65, 80, 58, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/8.png', 9, 14, 7, 7),
(9, 9, 'Blastoise', 11, 79, 83, 100, 85, 105, 78, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/9.png', NULL, NULL, 8, 8),
(10, 25, 'Pikachu', 13, 35, 55, 40, 50, 50, 90, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png', 11, 16, NULL, NULL),
(11, 26, 'Raichu', 13, 60, 90, 55, 90, 80, 110, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/26.png', NULL, NULL, 10, 25),
(12, 74, 'Geodude', 6, 40, 80, 100, 30, 30, 20, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/74.png', 13, 17, NULL, NULL),
(13, 75, 'Graveler', 6, 55, 95, 115, 45, 45, 35, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/75.png', 14, 17, 12, 74),
(14, 76, 'Golem', 6, 80, 120, 130, 55, 65, 45, 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/76.png', NULL, NULL, 13, 75);

-- --------------------------------------------------------

--
-- Estrutura para tabela `especies_desbloqueadas`
--

CREATE TABLE `especies_desbloqueadas` (
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `especie_id` int(10) UNSIGNED NOT NULL,
  `desbloqueada_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `especie_golpes`
--

CREATE TABLE `especie_golpes` (
  `especie_id` int(10) UNSIGNED NOT NULL,
  `golpe_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `especie_golpes`
--

INSERT INTO `especie_golpes` (`especie_id`, `golpe_id`) VALUES
(1, 1),
(1, 15),
(1, 47),
(1, 72),
(2, 4),
(2, 16),
(2, 47),
(2, 48),
(3, 4),
(3, 16),
(3, 47),
(3, 48),
(4, 1),
(4, 19),
(4, 39),
(4, 72),
(5, 4),
(5, 20),
(5, 39),
(5, 40),
(6, 4),
(6, 12),
(6, 39),
(6, 40),
(7, 1),
(7, 43),
(7, 59),
(7, 72),
(8, 4),
(8, 43),
(8, 44),
(8, 60),
(9, 4),
(9, 43),
(9, 44),
(9, 60),
(10, 1),
(10, 33),
(10, 49),
(10, 72),
(11, 2),
(11, 34),
(11, 49),
(11, 50),
(12, 1),
(12, 17),
(12, 21),
(12, 72),
(13, 2),
(13, 18),
(13, 21),
(13, 22),
(14, 2),
(14, 18),
(14, 21),
(14, 22);

-- --------------------------------------------------------

--
-- Estrutura para tabela `golpes`
--

CREATE TABLE `golpes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(40) NOT NULL,
  `tipo_id` tinyint(3) UNSIGNED NOT NULL,
  `poder` smallint(5) UNSIGNED NOT NULL,
  `precisao` tinyint(3) UNSIGNED NOT NULL DEFAULT 100,
  `categoria` enum('fisico','especial') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `golpes`
--

INSERT INTO `golpes` (`id`, `nome`, `tipo_id`, `poder`, `precisao`, `categoria`) VALUES
(1, 'Tackle', 1, 40, 100, 'fisico'),
(2, 'Body Slam', 1, 85, 100, 'fisico'),
(3, 'Swift', 1, 60, 100, 'especial'),
(4, 'Hyper Voice', 1, 90, 100, 'especial'),
(5, 'Karate Chop', 2, 50, 100, 'fisico'),
(6, 'Submission', 2, 80, 80, 'fisico'),
(7, 'Vacuum Wave', 2, 40, 100, 'especial'),
(8, 'Aura Sphere', 2, 80, 100, 'especial'),
(9, 'Wing Attack', 3, 60, 100, 'fisico'),
(10, 'Drill Peck', 3, 80, 100, 'fisico'),
(11, 'Gust', 3, 40, 100, 'especial'),
(12, 'Air Slash', 3, 75, 95, 'especial'),
(13, 'Poison Fang', 4, 50, 100, 'fisico'),
(14, 'Poison Jab', 4, 80, 100, 'fisico'),
(15, 'Acid', 4, 40, 100, 'especial'),
(16, 'Sludge Bomb', 4, 90, 100, 'especial'),
(17, 'Bone Club', 5, 65, 85, 'fisico'),
(18, 'Earthquake', 5, 100, 100, 'fisico'),
(19, 'Mud Shot', 5, 55, 95, 'especial'),
(20, 'Earth Power', 5, 90, 100, 'especial'),
(21, 'Rock Throw', 6, 50, 90, 'fisico'),
(22, 'Rock Slide', 6, 75, 90, 'fisico'),
(23, 'Ancient Power', 6, 60, 100, 'especial'),
(24, 'Power Gem', 6, 80, 100, 'especial'),
(25, 'Fury Cutter', 7, 40, 95, 'fisico'),
(26, 'X-Scissor', 7, 80, 100, 'fisico'),
(27, 'Silver Wind', 7, 60, 100, 'especial'),
(28, 'Bug Buzz', 7, 90, 100, 'especial'),
(29, 'Lick', 8, 30, 100, 'fisico'),
(30, 'Shadow Claw', 8, 70, 100, 'fisico'),
(31, 'Ominous Wind', 8, 60, 100, 'especial'),
(32, 'Shadow Ball', 8, 80, 100, 'especial'),
(33, 'Metal Claw', 9, 50, 95, 'fisico'),
(34, 'Iron Head', 9, 80, 100, 'fisico'),
(35, 'Mirror Shot', 9, 65, 85, 'especial'),
(36, 'Flash Cannon', 9, 80, 100, 'especial'),
(37, 'Fire Fang', 10, 65, 95, 'fisico'),
(38, 'Fire Punch', 10, 75, 100, 'fisico'),
(39, 'Ember', 10, 40, 100, 'especial'),
(40, 'Flamethrower', 10, 90, 100, 'especial'),
(41, 'Aqua Jet', 11, 40, 100, 'fisico'),
(42, 'Waterfall', 11, 80, 100, 'fisico'),
(43, 'Water Gun', 11, 40, 100, 'especial'),
(44, 'Surf', 11, 90, 100, 'especial'),
(45, 'Vine Whip', 12, 45, 100, 'fisico'),
(46, 'Razor Leaf', 12, 55, 95, 'fisico'),
(47, 'Mega Drain', 12, 40, 100, 'especial'),
(48, 'Energy Ball', 12, 90, 100, 'especial'),
(49, 'Spark', 13, 65, 100, 'fisico'),
(50, 'Thunder Punch', 13, 75, 100, 'fisico'),
(51, 'Thunder Shock', 13, 40, 100, 'especial'),
(52, 'Thunderbolt', 13, 90, 100, 'especial'),
(53, 'Psycho Cut', 14, 70, 100, 'fisico'),
(54, 'Zen Headbutt', 14, 80, 90, 'fisico'),
(55, 'Confusion', 14, 50, 100, 'especial'),
(56, 'Psychic', 14, 90, 100, 'especial'),
(57, 'Ice Shard', 15, 40, 100, 'fisico'),
(58, 'Ice Punch', 15, 75, 100, 'fisico'),
(59, 'Powder Snow', 15, 40, 100, 'especial'),
(60, 'Ice Beam', 15, 90, 100, 'especial'),
(61, 'Dragon Tail', 16, 60, 90, 'fisico'),
(62, 'Dragon Claw', 16, 80, 100, 'fisico'),
(63, 'Twister', 16, 40, 100, 'especial'),
(64, 'Dragon Pulse', 16, 85, 100, 'especial'),
(65, 'Bite', 17, 60, 100, 'fisico'),
(66, 'Crunch', 17, 80, 100, 'fisico'),
(67, 'Snarl', 17, 55, 95, 'especial'),
(68, 'Dark Pulse', 17, 80, 100, 'especial'),
(69, 'Fairy Wind', 18, 40, 100, 'especial'),
(70, 'Play Rough', 18, 90, 90, 'fisico'),
(71, 'Moonblast', 18, 95, 100, 'especial'),
(72, 'Quick Attack', 1, 40, 100, 'fisico'),
(73, 'Slash', 1, 70, 100, 'fisico');

-- --------------------------------------------------------

--
-- Estrutura para tabela `inventario`
--

CREATE TABLE `inventario` (
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `quantidade` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `inventario`
--

INSERT INTO `inventario` (`usuario_id`, `item_id`, `quantidade`) VALUES
(1, 1, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens`
--

CREATE TABLE `itens` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(40) NOT NULL,
  `categoria` enum('cura_hp','cura_status','reviver','evolucao') NOT NULL,
  `descricao` varchar(200) NOT NULL,
  `preco` int(10) UNSIGNED NOT NULL,
  `cura_hp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cura_percentual` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `status_curado` enum('veneno','queimado','congelado','dormindo','paralisado') DEFAULT NULL,
  `cura_todos_status` tinyint(1) NOT NULL DEFAULT 0,
  `tipo_id` tinyint(3) UNSIGNED DEFAULT NULL
) ;

--
-- Despejando dados para a tabela `itens`
--

INSERT INTO `itens` (`id`, `nome`, `categoria`, `descricao`, `preco`, `cura_hp`, `cura_percentual`, `status_curado`, `cura_todos_status`, `tipo_id`) VALUES
(1, 'Potion', 'cura_hp', 'Recupera 20 HP.', 200, 20, 0, NULL, 0, NULL),
(2, 'Super Potion', 'cura_hp', 'Recupera 50 HP.', 700, 50, 0, NULL, 0, NULL),
(3, 'Hyper Potion', 'cura_hp', 'Recupera 200 HP.', 1500, 200, 0, NULL, 0, NULL),
(4, 'Max Potion', 'cura_hp', 'Recupera 100% do HP.', 2500, 0, 100, NULL, 0, NULL),
(5, 'Full Restore', 'cura_hp', 'Recupera 100% do HP e cura todas as condições.', 3000, 0, 100, NULL, 1, NULL),
(6, 'Antidote', 'cura_status', 'Cura envenenamento.', 100, 0, 0, 'veneno', 0, NULL),
(7, 'Burn Heal', 'cura_status', 'Cura queimadura.', 250, 0, 0, 'queimado', 0, NULL),
(8, 'Ice Heal', 'cura_status', 'Cura congelamento.', 250, 0, 0, 'congelado', 0, NULL),
(9, 'Awakening', 'cura_status', 'Acorda um Pokémon dormindo.', 250, 0, 0, 'dormindo', 0, NULL),
(10, 'Paralyze Heal', 'cura_status', 'Cura paralisia.', 200, 0, 0, 'paralisado', 0, NULL),
(11, 'Full Heal', 'cura_status', 'Cura todas as condições de status.', 600, 0, 0, NULL, 1, NULL),
(12, 'Revive', 'reviver', 'Revive um Pokémon desmaiado com metade do HP. Funciona no meio da batalha.', 4000, 0, 50, NULL, 0, NULL),
(13, 'Pedra do Fogo', 'evolucao', 'Evolui Pokémon da linha de Fogo.', 2100, 0, 0, NULL, 0, 10),
(14, 'Pedra da Água', 'evolucao', 'Evolui Pokémon da linha de Água.', 2100, 0, 0, NULL, 0, 11),
(15, 'Pedra da Folha', 'evolucao', 'Evolui Pokémon da linha de Planta.', 2100, 0, 0, NULL, 0, 12),
(16, 'Pedra do Trovão', 'evolucao', 'Evolui Pokémon da linha Elétrica.', 2100, 0, 0, NULL, 0, 13),
(17, 'Pedra Rochosa', 'evolucao', 'Evolui Pokémon da linha de Pedra.', 2100, 0, 0, NULL, 0, 6),
(18, 'Pedra da Terra', 'evolucao', 'Evolui Pokémon da linha de Terra.', 2100, 0, 0, NULL, 0, 5),
(19, 'Pedra do Inseto', 'evolucao', 'Evolui Pokémon da linha de Inseto.', 2100, 0, 0, NULL, 0, 7),
(20, 'Pedra Normal', 'evolucao', 'Evolui Pokémon da linha de Normal.', 2100, 0, 0, NULL, 0, 1),
(21, 'Pedra Venenosa', 'evolucao', 'Evolui Pokémon da linha de Venenoso.', 2100, 0, 0, NULL, 0, 4),
(22, 'Pedra da Lua', 'evolucao', 'Evolui Pokémon da linha de Fada.', 2100, 0, 0, NULL, 0, 18),
(23, 'Pedra da Luta', 'evolucao', 'Evolui Pokémon da linha de Lutador.', 2100, 0, 0, NULL, 0, 2),
(24, 'Pedra Psíquica', 'evolucao', 'Evolui Pokémon da linha de Psíquico.', 2100, 0, 0, NULL, 0, 14),
(25, 'Pedra Fantasma', 'evolucao', 'Evolui Pokémon da linha de Fantasma.', 2100, 0, 0, NULL, 0, 8),
(26, 'Pedra do Dragão', 'evolucao', 'Evolui Pokémon da linha de Dragão.', 2100, 0, 0, NULL, 0, 16),
(27, 'Pedra do Vento', 'evolucao', 'Evolui Pokémon da linha de Voador.', 2100, 0, 0, NULL, 0, 3),
(28, 'Pedra do Gelo', 'evolucao', 'Evolui Pokémon da linha de Gelo.', 2100, 0, 0, NULL, 0, 15),
(29, 'Pedra Metálica', 'evolucao', 'Evolui Pokémon da linha de Aço.', 2100, 0, 0, NULL, 0, 9),
(30, 'Pedra Sombria', 'evolucao', 'Evolui Pokémon da linha de Sombrio.', 2100, 0, 0, NULL, 0, 17);

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_loja`
--

CREATE TABLE `log_loja` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `quantidade` int(10) UNSIGNED NOT NULL,
  `valor_total` int(10) UNSIGNED NOT NULL,
  `data_compra` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `log_loja`
--
DELIMITER $$
CREATE TRIGGER `trg_log_loja_valida` BEFORE INSERT ON `log_loja` FOR EACH ROW BEGIN
    IF NEW.quantidade < 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Quantidade de compra deve ser maior que zero.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_perfis`
--

CREATE TABLE `log_perfis` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `perfil_antigo` enum('jogador','admin') NOT NULL,
  `perfil_novo` enum('jogador','admin') NOT NULL,
  `data_troca` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pokemons`
--

CREATE TABLE `pokemons` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `especie_id` int(10) UNSIGNED NOT NULL,
  `apelido` varchar(40) DEFAULT NULL,
  `nivel` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `hp_max` smallint(5) UNSIGNED NOT NULL,
  `hp_atual` smallint(5) UNSIGNED NOT NULL,
  `status` enum('normal','veneno','queimado','congelado','dormindo','paralisado') NOT NULL DEFAULT 'normal',
  `desmaiado` tinyint(1) NOT NULL DEFAULT 0,
  `shiny` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `pokemons`
--
DELIMITER $$
CREATE TRIGGER `trg_pokemons_desbloqueia` AFTER UPDATE ON `pokemons` FOR EACH ROW BEGIN
    IF NEW.especie_id <> OLD.especie_id THEN
        INSERT IGNORE INTO especies_desbloqueadas (usuario_id, especie_id)
        VALUES (NEW.usuario_id, NEW.especie_id);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pokemons_hp_insert` BEFORE INSERT ON `pokemons` FOR EACH ROW BEGIN
    IF NEW.hp_atual > NEW.hp_max THEN
        SET NEW.hp_atual = NEW.hp_max;
    END IF;
    SET NEW.desmaiado = (NEW.hp_atual = 0);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pokemons_hp_update` BEFORE UPDATE ON `pokemons` FOR EACH ROW BEGIN
    IF NEW.hp_atual > NEW.hp_max THEN
        SET NEW.hp_atual = NEW.hp_max;
    END IF;

    IF NEW.hp_atual = 0 THEN
        SET NEW.desmaiado = 1;
        SET NEW.status = 'normal';     -- desmaiou: condição some
    ELSE
        SET NEW.desmaiado = 0;         -- tem HP (ex.: Revive): está de pé
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `times`
--

CREATE TABLE `times` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(40) NOT NULL DEFAULT 'Meu time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `times`
--

INSERT INTO `times` (`id`, `usuario_id`, `nome`) VALUES
(1, 2, 'Meu time'),
(2, 1, 'Meu time');

-- --------------------------------------------------------

--
-- Estrutura para tabela `time_pokemon`
--

CREATE TABLE `time_pokemon` (
  `time_id` int(10) UNSIGNED NOT NULL,
  `posicao` tinyint(3) UNSIGNED NOT NULL,
  `pokemon_id` int(10) UNSIGNED NOT NULL
) ;

--
-- Acionadores `time_pokemon`
--
DELIMITER $$
CREATE TRIGGER `trg_time_pokemon_dono` BEFORE INSERT ON `time_pokemon` FOR EACH ROW BEGIN
    DECLARE v_dono_time INT;
    DECLARE v_dono_poke INT;

    SELECT usuario_id INTO v_dono_time FROM times    WHERE id = NEW.time_id;
    SELECT usuario_id INTO v_dono_poke FROM pokemons WHERE id = NEW.pokemon_id;

    IF v_dono_time IS NULL OR v_dono_poke IS NULL OR v_dono_time <> v_dono_poke THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Este Pokémon não pertence ao dono do time.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos`
--

CREATE TABLE `tipos` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nome` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tipos`
--

INSERT INTO `tipos` (`id`, `nome`) VALUES
(9, 'Aço'),
(11, 'Água'),
(16, 'Dragão'),
(13, 'Elétrico'),
(18, 'Fada'),
(8, 'Fantasma'),
(10, 'Fogo'),
(15, 'Gelo'),
(7, 'Inseto'),
(2, 'Lutador'),
(1, 'Normal'),
(6, 'Pedra'),
(12, 'Planta'),
(14, 'Psíquico'),
(17, 'Sombrio'),
(5, 'Terra'),
(4, 'Venenoso'),
(3, 'Voador');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_vantagens`
--

CREATE TABLE `tipo_vantagens` (
  `tipo_atacante_id` tinyint(3) UNSIGNED NOT NULL,
  `tipo_defensor_id` tinyint(3) UNSIGNED NOT NULL,
  `multiplicador` decimal(2,1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tipo_vantagens`
--

INSERT INTO `tipo_vantagens` (`tipo_atacante_id`, `tipo_defensor_id`, `multiplicador`) VALUES
(1, 6, 0.5),
(1, 8, 0.0),
(1, 9, 0.5),
(2, 1, 2.0),
(2, 3, 0.5),
(2, 4, 0.5),
(2, 6, 2.0),
(2, 7, 0.5),
(2, 8, 0.0),
(2, 9, 2.0),
(2, 14, 0.5),
(2, 15, 2.0),
(2, 17, 2.0),
(2, 18, 0.5),
(3, 2, 2.0),
(3, 6, 0.5),
(3, 7, 2.0),
(3, 9, 0.5),
(3, 12, 2.0),
(3, 13, 0.5),
(4, 4, 0.5),
(4, 5, 0.5),
(4, 6, 0.5),
(4, 8, 0.5),
(4, 9, 0.0),
(4, 12, 2.0),
(4, 18, 2.0),
(5, 3, 0.0),
(5, 4, 2.0),
(5, 6, 2.0),
(5, 7, 0.5),
(5, 9, 2.0),
(5, 10, 2.0),
(5, 12, 0.5),
(5, 13, 2.0),
(6, 2, 0.5),
(6, 3, 2.0),
(6, 5, 0.5),
(6, 7, 2.0),
(6, 9, 0.5),
(6, 10, 2.0),
(6, 15, 2.0),
(7, 2, 0.5),
(7, 3, 0.5),
(7, 4, 0.5),
(7, 8, 0.5),
(7, 9, 0.5),
(7, 10, 0.5),
(7, 12, 2.0),
(7, 14, 2.0),
(7, 17, 2.0),
(7, 18, 0.5),
(8, 1, 0.0),
(8, 8, 2.0),
(8, 14, 2.0),
(8, 17, 0.5),
(9, 6, 2.0),
(9, 9, 0.5),
(9, 10, 0.5),
(9, 11, 0.5),
(9, 13, 0.5),
(9, 15, 2.0),
(9, 18, 2.0),
(10, 6, 0.5),
(10, 7, 2.0),
(10, 9, 2.0),
(10, 10, 0.5),
(10, 11, 0.5),
(10, 12, 2.0),
(10, 15, 2.0),
(10, 16, 0.5),
(11, 5, 2.0),
(11, 6, 2.0),
(11, 10, 2.0),
(11, 11, 0.5),
(11, 12, 0.5),
(11, 16, 0.5),
(12, 3, 0.5),
(12, 4, 0.5),
(12, 5, 2.0),
(12, 6, 2.0),
(12, 7, 0.5),
(12, 9, 0.5),
(12, 10, 0.5),
(12, 11, 2.0),
(12, 12, 0.5),
(12, 16, 0.5),
(13, 3, 2.0),
(13, 5, 0.0),
(13, 11, 2.0),
(13, 12, 0.5),
(13, 13, 0.5),
(13, 16, 0.5),
(14, 2, 2.0),
(14, 4, 2.0),
(14, 9, 0.5),
(14, 14, 0.5),
(14, 17, 0.0),
(15, 3, 2.0),
(15, 5, 2.0),
(15, 9, 0.5),
(15, 10, 0.5),
(15, 11, 0.5),
(15, 12, 2.0),
(15, 15, 0.5),
(15, 16, 2.0),
(16, 9, 0.5),
(16, 16, 2.0),
(16, 18, 0.0),
(17, 2, 0.5),
(17, 8, 2.0),
(17, 14, 2.0),
(17, 17, 0.5),
(17, 18, 0.5),
(18, 2, 2.0),
(18, 4, 0.5),
(18, 9, 0.5),
(18, 10, 0.5),
(18, 16, 2.0),
(18, 17, 2.0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(60) NOT NULL,
  `email` varchar(120) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `perfil` enum('jogador','admin') NOT NULL DEFAULT 'jogador',
  `pokedollar` int(10) UNSIGNED NOT NULL DEFAULT 1000,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `perfil`, `pokedollar`, `criado_em`) VALUES
(1, 'Ash', 'jogador@teste.com', '$2y$10$baMBSBVbZwQ0ynDWYSDDfutm7/Kl0C1fvcUdQYxv4uYBcXCSdcri2', 'jogador', 5000, '2026-11-22 23:48:41'),
(2, 'Oak', 'admin@teste.com', '$2y$10$7waTRHdm2lIFB9lD5SdaFO0ns/bmjEhxloaS0cB2yGJt6uD3Cb3vO', 'admin', 99999, '2026-11-22 23:48:41');

--
-- Acionadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `trg_usuarios_log_perfil` AFTER UPDATE ON `usuarios` FOR EACH ROW BEGIN
    IF OLD.perfil <> NEW.perfil THEN
        INSERT INTO log_perfis (usuario_id, perfil_antigo, perfil_novo)
        VALUES (NEW.id, OLD.perfil, NEW.perfil);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_evolucoes`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_evolucoes` (
`especie_id` int(10) unsigned
,`para_especie_id` int(10) unsigned
,`para_nome` varchar(40)
,`item_id` int(10) unsigned
,`item_nome` varchar(40)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_gastos_loja`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_gastos_loja` (
`usuario_id` int(10) unsigned
,`nome` varchar(60)
,`compras` bigint(21)
,`total_gasto` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_historico_batalhas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_historico_batalhas` (
`id` int(10) unsigned
,`jogador` varchar(60)
,`usuario_id` int(10) unsigned
,`oponente_nome` varchar(60)
,`vencedor` enum('jogador','oponente')
,`dinheiro_ganho` int(10) unsigned
,`total_turnos` smallint(5) unsigned
,`data_batalha` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_inventario`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_inventario` (
`usuario_id` int(10) unsigned
,`item_id` int(10) unsigned
,`nome` varchar(40)
,`categoria` enum('cura_hp','cura_status','reviver','evolucao')
,`descricao` varchar(200)
,`quantidade` int(10) unsigned
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_loja`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_loja` (
`id` int(10) unsigned
,`nome` varchar(40)
,`categoria` enum('cura_hp','cura_status','reviver','evolucao')
,`descricao` varchar(200)
,`preco` int(10) unsigned
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_ranking`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_ranking` (
`usuario_id` int(10) unsigned
,`nome` varchar(60)
,`pokedollar` int(10) unsigned
,`total_batalhas` bigint(21)
,`vitorias` decimal(23,0)
,`derrotas` decimal(23,0)
,`total_ganho` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_time_completo`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_time_completo` (
`usuario_id` int(10) unsigned
,`posicao` tinyint(3) unsigned
,`pokemon_id` int(10) unsigned
,`apelido` varchar(40)
,`nome_exibicao` varchar(40)
,`nivel` tinyint(3) unsigned
,`hp_atual` smallint(5) unsigned
,`hp_max` smallint(5) unsigned
,`status` enum('normal','veneno','queimado','congelado','dormindo','paralisado')
,`desmaiado` tinyint(1)
,`shiny` tinyint(1)
,`especie_id` int(10) unsigned
,`pokeapi_id` int(10) unsigned
,`especie` varchar(40)
,`evolui_de_id` int(10) unsigned
,`tipo` varchar(20)
,`hp` smallint(5) unsigned
,`ataque` smallint(5) unsigned
,`defesa` smallint(5) unsigned
,`sp_atk` smallint(5) unsigned
,`sp_def` smallint(5) unsigned
,`velocidade` smallint(5) unsigned
,`sprite_url` varchar(255)
);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `batalhas`
--
ALTER TABLE `batalhas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_batalhas_usuario` (`usuario_id`,`data_batalha`);

--
-- Índices de tabela `batalha_turnos`
--
ALTER TABLE `batalha_turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batalha_id` (`batalha_id`);

--
-- Índices de tabela `especies`
--
ALTER TABLE `especies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pokeapi_id` (`pokeapi_id`),
  ADD KEY `tipo_id` (`tipo_id`),
  ADD KEY `evolui_para_id` (`evolui_para_id`),
  ADD KEY `evolui_de_id` (`evolui_de_id`),
  ADD KEY `item_evolucao_id` (`item_evolucao_id`);

--
-- Índices de tabela `especies_desbloqueadas`
--
ALTER TABLE `especies_desbloqueadas`
  ADD PRIMARY KEY (`usuario_id`,`especie_id`),
  ADD KEY `especie_id` (`especie_id`);

--
-- Índices de tabela `especie_golpes`
--
ALTER TABLE `especie_golpes`
  ADD PRIMARY KEY (`especie_id`,`golpe_id`),
  ADD KEY `golpe_id` (`golpe_id`);

--
-- Índices de tabela `golpes`
--
ALTER TABLE `golpes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD KEY `tipo_id` (`tipo_id`);

--
-- Índices de tabela `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`usuario_id`,`item_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Índices de tabela `itens`
--
ALTER TABLE `itens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD KEY `tipo_id` (`tipo_id`);

--
-- Índices de tabela `log_loja`
--
ALTER TABLE `log_loja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_log_loja_usuario` (`usuario_id`,`data_compra`);

--
-- Índices de tabela `log_perfis`
--
ALTER TABLE `log_perfis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pokemons`
--
ALTER TABLE `pokemons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `especie_id` (`especie_id`),
  ADD KEY `idx_pokemons_usuario` (`usuario_id`);

--
-- Índices de tabela `times`
--
ALTER TABLE `times`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `time_pokemon`
--
ALTER TABLE `time_pokemon`
  ADD PRIMARY KEY (`time_id`,`posicao`),
  ADD UNIQUE KEY `pokemon_id` (`pokemon_id`);

--
-- Índices de tabela `tipos`
--
ALTER TABLE `tipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `tipo_vantagens`
--
ALTER TABLE `tipo_vantagens`
  ADD PRIMARY KEY (`tipo_atacante_id`,`tipo_defensor_id`),
  ADD KEY `tipo_defensor_id` (`tipo_defensor_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `batalhas`
--
ALTER TABLE `batalhas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `batalha_turnos`
--
ALTER TABLE `batalha_turnos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `especies`
--
ALTER TABLE `especies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `golpes`
--
ALTER TABLE `golpes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT de tabela `itens`
--
ALTER TABLE `itens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `log_loja`
--
ALTER TABLE `log_loja`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `log_perfis`
--
ALTER TABLE `log_perfis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pokemons`
--
ALTER TABLE `pokemons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `times`
--
ALTER TABLE `times`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `tipos`
--
ALTER TABLE `tipos`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_evolucoes`
--
DROP TABLE IF EXISTS `vw_evolucoes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_evolucoes`  AS SELECT `pai`.`id` AS `especie_id`, `filho`.`id` AS `para_especie_id`, `filho`.`nome` AS `para_nome`, `i`.`id` AS `item_id`, `i`.`nome` AS `item_nome` FROM ((`especies` `filho` join `especies` `pai` on(`pai`.`id` = `filho`.`evolui_de_id`)) join `itens` `i` on(`i`.`categoria` = 'evolucao' and `i`.`tipo_id` = `filho`.`tipo_id`)) ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_gastos_loja`
--
DROP TABLE IF EXISTS `vw_gastos_loja`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_gastos_loja`  AS SELECT `u`.`id` AS `usuario_id`, `u`.`nome` AS `nome`, count(`l`.`id`) AS `compras`, coalesce(sum(`l`.`valor_total`),0) AS `total_gasto` FROM (`usuarios` `u` left join `log_loja` `l` on(`l`.`usuario_id` = `u`.`id`)) GROUP BY `u`.`id`, `u`.`nome` ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_historico_batalhas`
--
DROP TABLE IF EXISTS `vw_historico_batalhas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_historico_batalhas`  AS SELECT `b`.`id` AS `id`, `u`.`nome` AS `jogador`, `b`.`usuario_id` AS `usuario_id`, `b`.`oponente_nome` AS `oponente_nome`, `b`.`vencedor` AS `vencedor`, `b`.`dinheiro_ganho` AS `dinheiro_ganho`, `b`.`total_turnos` AS `total_turnos`, `b`.`data_batalha` AS `data_batalha` FROM (`batalhas` `b` join `usuarios` `u` on(`u`.`id` = `b`.`usuario_id`)) ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_inventario`
--
DROP TABLE IF EXISTS `vw_inventario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_inventario`  AS SELECT `inv`.`usuario_id` AS `usuario_id`, `i`.`id` AS `item_id`, `i`.`nome` AS `nome`, `i`.`categoria` AS `categoria`, `i`.`descricao` AS `descricao`, `inv`.`quantidade` AS `quantidade` FROM (`inventario` `inv` join `itens` `i` on(`i`.`id` = `inv`.`item_id`)) WHERE `inv`.`quantidade` > 0 ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_loja`
--
DROP TABLE IF EXISTS `vw_loja`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_loja`  AS SELECT `itens`.`id` AS `id`, `itens`.`nome` AS `nome`, `itens`.`categoria` AS `categoria`, `itens`.`descricao` AS `descricao`, `itens`.`preco` AS `preco` FROM `itens` ORDER BY field(`itens`.`categoria`,'cura_hp','cura_status','reviver','evolucao') ASC, `itens`.`preco` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_ranking`
--
DROP TABLE IF EXISTS `vw_ranking`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_ranking`  AS SELECT `u`.`id` AS `usuario_id`, `u`.`nome` AS `nome`, `u`.`pokedollar` AS `pokedollar`, count(`b`.`id`) AS `total_batalhas`, coalesce(sum(`b`.`vencedor` = 'jogador'),0) AS `vitorias`, coalesce(sum(`b`.`vencedor` = 'oponente'),0) AS `derrotas`, coalesce(sum(`b`.`dinheiro_ganho`),0) AS `total_ganho` FROM (`usuarios` `u` left join `batalhas` `b` on(`b`.`usuario_id` = `u`.`id`)) WHERE `u`.`perfil` = 'jogador' GROUP BY `u`.`id`, `u`.`nome`, `u`.`pokedollar` ORDER BY coalesce(sum(`b`.`vencedor` = 'jogador'),0) DESC, coalesce(sum(`b`.`dinheiro_ganho`),0) DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_time_completo`
--
DROP TABLE IF EXISTS `vw_time_completo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_time_completo`  AS SELECT `t`.`usuario_id` AS `usuario_id`, `tp`.`posicao` AS `posicao`, `p`.`id` AS `pokemon_id`, `p`.`apelido` AS `apelido`, coalesce(`p`.`apelido`,`e`.`nome`) AS `nome_exibicao`, `p`.`nivel` AS `nivel`, `p`.`hp_atual` AS `hp_atual`, `p`.`hp_max` AS `hp_max`, `p`.`status` AS `status`, `p`.`desmaiado` AS `desmaiado`, `p`.`shiny` AS `shiny`, `e`.`id` AS `especie_id`, `e`.`pokeapi_id` AS `pokeapi_id`, `e`.`nome` AS `especie`, `e`.`evolui_de_id` AS `evolui_de_id`, `ti`.`nome` AS `tipo`, `e`.`hp` AS `hp`, `e`.`ataque` AS `ataque`, `e`.`defesa` AS `defesa`, `e`.`sp_atk` AS `sp_atk`, `e`.`sp_def` AS `sp_def`, `e`.`velocidade` AS `velocidade`, `e`.`sprite_url` AS `sprite_url` FROM ((((`time_pokemon` `tp` join `times` `t` on(`t`.`id` = `tp`.`time_id`)) join `pokemons` `p` on(`p`.`id` = `tp`.`pokemon_id`)) join `especies` `e` on(`e`.`id` = `p`.`especie_id`)) join `tipos` `ti` on(`ti`.`id` = `e`.`tipo_id`)) ;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `batalhas`
--
ALTER TABLE `batalhas`
  ADD CONSTRAINT `batalhas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `batalha_turnos`
--
ALTER TABLE `batalha_turnos`
  ADD CONSTRAINT `batalha_turnos_ibfk_1` FOREIGN KEY (`batalha_id`) REFERENCES `batalhas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `especies`
--
ALTER TABLE `especies`
  ADD CONSTRAINT `especies_ibfk_1` FOREIGN KEY (`tipo_id`) REFERENCES `tipos` (`id`),
  ADD CONSTRAINT `especies_ibfk_2` FOREIGN KEY (`evolui_para_id`) REFERENCES `especies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `especies_ibfk_3` FOREIGN KEY (`evolui_de_id`) REFERENCES `especies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `especies_ibfk_4` FOREIGN KEY (`item_evolucao_id`) REFERENCES `itens` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `especies_desbloqueadas`
--
ALTER TABLE `especies_desbloqueadas`
  ADD CONSTRAINT `especies_desbloqueadas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `especies_desbloqueadas_ibfk_2` FOREIGN KEY (`especie_id`) REFERENCES `especies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `especie_golpes`
--
ALTER TABLE `especie_golpes`
  ADD CONSTRAINT `especie_golpes_ibfk_1` FOREIGN KEY (`especie_id`) REFERENCES `especies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `especie_golpes_ibfk_2` FOREIGN KEY (`golpe_id`) REFERENCES `golpes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `golpes`
--
ALTER TABLE `golpes`
  ADD CONSTRAINT `golpes_ibfk_1` FOREIGN KEY (`tipo_id`) REFERENCES `tipos` (`id`);

--
-- Restrições para tabelas `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventario_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`);

--
-- Restrições para tabelas `itens`
--
ALTER TABLE `itens`
  ADD CONSTRAINT `itens_ibfk_1` FOREIGN KEY (`tipo_id`) REFERENCES `tipos` (`id`);

--
-- Restrições para tabelas `log_loja`
--
ALTER TABLE `log_loja`
  ADD CONSTRAINT `log_loja_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `log_loja_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`);

--
-- Restrições para tabelas `log_perfis`
--
ALTER TABLE `log_perfis`
  ADD CONSTRAINT `log_perfis_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pokemons`
--
ALTER TABLE `pokemons`
  ADD CONSTRAINT `pokemons_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pokemons_ibfk_2` FOREIGN KEY (`especie_id`) REFERENCES `especies` (`id`);

--
-- Restrições para tabelas `times`
--
ALTER TABLE `times`
  ADD CONSTRAINT `times_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `time_pokemon`
--
ALTER TABLE `time_pokemon`
  ADD CONSTRAINT `time_pokemon_ibfk_1` FOREIGN KEY (`time_id`) REFERENCES `times` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_pokemon_ibfk_2` FOREIGN KEY (`pokemon_id`) REFERENCES `pokemons` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tipo_vantagens`
--
ALTER TABLE `tipo_vantagens`
  ADD CONSTRAINT `tipo_vantagens_ibfk_1` FOREIGN KEY (`tipo_atacante_id`) REFERENCES `tipos` (`id`),
  ADD CONSTRAINT `tipo_vantagens_ibfk_2` FOREIGN KEY (`tipo_defensor_id`) REFERENCES `tipos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
