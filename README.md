# MonstroBolso

Atividade final do terceiro trimestre de Linguagens para Internet II, Programação Orientada a Objetos e Banco de Dados II.

Jogo estilo Pokémon feito em PHP orientado a objetos (MVC) com MySQL: monte um time, batalhe contra treinadores e líderes de ginásio, ganhe Pokédollar e compre itens no Poké Mart.

## Como instalar (XAMPP)

1. Copie a pasta do projeto para `C:\xampp\htdocs\MonstroBolso`.
2. Inicie o **Apache** e o **MySQL** no XAMPP Control Panel.
3. No phpMyAdmin, clique em **Importar** e escolha `database/monstrobolso.sql`.
   - O arquivo cria o banco `monstrobolso` do zero (se já existir, ele é **apagado** e recriado).
   - Avisos de "Tipo de declaração desconhecido" do phpMyAdmin podem ser ignorados.
4. Abra **http://localhost/MonstroBolso/public**
5. (Opcional) Na Home, importe mais gerações de Pokémon da PokéAPI (precisa de internet).

A pasta `vendor/` (autoload do Composer) já vem no repositório. Se criar uma classe nova em uma pasta nova, rode `composer dump-autoload`.

Banco diferente do padrão (`localhost`, `root`, sem senha)? Crie um arquivo `.env` na raiz:

```
DB_HOST=localhost
DB_NAME=monstrobolso
DB_USER=root
DB_PASS=
```

### Contas de teste

| E-mail | Senha | Perfil |
|---|---|---|
| jogador@teste.com | jogador123 | jogador |
| admin@teste.com | admin123 | admin |

## Como jogar

- **Team Builder:** monte um time de até 6 Pokémon (sem repetir a mesma linha evolutiva, exceto as evoluções do Eevee). Evolua com pedras, use itens e cure o time de graça no **Centro Pokémon**. Shiny conseguido fica desbloqueado para sempre.
- **Batalha:** contra treinadores com força parecida com a do seu time. A cada 10 vitórias, desafie o próximo dos 8 **líderes de ginásio** (em ordem), cada um com time fixo e dificuldade própria. Perdeu para o líder? Vença mais 10 treinadores.
- **Poké Mart:** compre os itens com o Pokédollar ganho nas batalhas.

## Onde está cada requisito

| Requisito | Onde |
|---|---|
| Tela de login | `app/views/login.php`, `public/assets/js/login.js`, `AuthController` |
| Tela de Team Builder | `app/views/team.php`, `public/assets/js/team.js`, `TimeController` |
| Tela de batalha + log de batalhas | `app/views/batalha.php`, `BatalhaController`, tabelas `batalhas` e `batalha_turnos` |
| Tela da lojinha (Poké Mart) | `app/views/loja.php`, `LojaController`, procedure `sp_comprar_item` |
| Pokémon com 10+ variáveis (nome, HP, velocidade...) | `app/models/Especie.php` e `app/models/Pokemon.php` |
| Vencer batalha dá Pokédollar | `Batalha::premio()` + trigger `trg_batalhas_premio` |
| Itens da loja (todos os exigidos) | tabela `itens` (`database/monstrobolso.sql`), regra em `Pokemon::usarItem()` |
| Evolução por item da loja | `TimeRepository::evoluir()`, `Pokemon::evoluirPara()`, view `vw_evolucoes` |
| Pokémon "herda" os tipos | `app/models/Tipos/Tipo.php` (abstrata) + 18 classes filhas (`Fogo extends Tipo`...) |
| Consumo de APIs | PokéAPI no PHP (`app/services/PokeApiService.php`) e no JS (`public/assets/js/PokeApi.js`) |
| Requisições assíncronas | `public/assets/js/ApiClient.js` (`fetch` + `async/await`), usado por todas as telas |
| PDO | `app/core/Database.php` e todos os `*Repository.php` (prepared statements) |
| MVC | `app/models`, `app/views`, `app/controllers`, entrada única em `public/index.php` + `app/core/Router.php` |
| Transporte via JSON | `BaseController::json()` e `BaseController::corpoJson()` |
| Autoload | `composer.json` (PSR-4) + `vendor/autoload.php` |
| 3+ exceções, 1+ customizada | `app/exceptions/` (`CredenciaisInvalidasException`, `TimeCheioException`, `LinhaRepetidaException`) + `InvalidArgumentException`, `RuntimeException`, `PDOException` |
| Orientação a Objetos em PHP | todo o `app/` |
| Orientação a Objetos em JavaScript | classes `ApiClient`, `PokeApi`, `TelaLogin`, `TeamBuilder`, `TelaLoja`, `TelaBatalha`, `TelaHome` |
| Sobrecarga de métodos (simulada) | `Batalha::jogarTurno(Golpe\|Item\|Pokemon $acao)` |
| Composição | `Batalha` cria e guarda os `Turno`s |
| Agregação | `Time` agrupa `Pokemon` (os Pokémon existem sem o time) |
| Associação | `Pokemon` → `Especie` |
| Controle de usuário (troca de perfis) | login, cadastro e logout (`AuthController`) com perfis `jogador` e `admin`; trocas de perfil ficam no log `log_perfis` (trigger `trg_usuarios_log_perfil`) |
| Encapsulamento | atributos `private` + setters com validação (ex.: `Usuario::setPerfil`, `Pokemon::setHpAtual`) |
| Polimorfismo | `Tipo::multiplicadorContra()` e `condicaoQueCausa()` respondem diferente em cada tipo |
| DDL, DML, DTL e DQL | `database/monstrobolso.sql` (CREATE/ALTER, INSERT/UPDATE, START TRANSACTION/COMMIT, SELECT/VIEWs) |
| Tabelas de log (batalhas, perfis, loja) | `batalhas` + `batalha_turnos`, `log_perfis`, `log_loja` |
| Triggers (opcional) | prêmio da batalha, log de perfil, HP, dono do time, desbloqueios, shiny |
| 3+ views | `vw_time_completo`, `vw_loja`, `vw_inventario`, `vw_historico_batalhas`, `vw_ranking`, `vw_gastos_loja`, `vw_evolucoes`, `vw_progresso_ginasio` |
| **Ponto extra escolhido** | **Centro Pokémon** (`TimeRepository::centroPokemon()`, botão no Team Builder e na Batalha) |

## Ajustes rápidos (constantes)

| O quê | Onde |
|---|---|
| Chance de shiny | `Pokemon::CHANCE_SHINY` |
| Prêmio por Pokémon derrotado | `Batalha::PREMIO_POR_POKEMON` |
| Dificuldade dos treinadores | `BatalhaRepository::DIFICULDADE` |
| Dificuldade de cada líder (HP extra) | coluna `bonus_hp` da tabela `lideres` |
| Time de cada líder | tabela `lider_pokemons` |
| Vitórias para desafiar o líder | `GinasioRepository::VITORIAS_NECESSARIAS` |
| Preço dos itens | coluna `preco` da tabela `itens` |

## Segurança

- Senhas com `password_hash` / `password_verify`; consultas com prepared statements (sem SQL Injection).
- Textos escapados nas telas (`htmlspecialchars` no PHP, `esc()` no JS) contra XSS.
- Regras conferidas no servidor (dono do Pokémon, saldo, ordem dos ginásios).
- Cookie de sessão `httponly` + `samesite`; erros internos só no log do Apache.
