<?php
/**
 * VIEW do menu inicial. Recebe $usuario do HomeController (via extract no
 * BaseController::view) e escapa o nome com htmlspecialchars (contra XSS).
 * $base = caminho do site (ex.: /MonstroBolso/public), usado nos links.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Início</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/team.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/home.css">
</head>

<body class="home">
    <div class="status">
        <span class="perfil"><?= htmlspecialchars($usuario['perfil']) ?></span>
        <span class="dinheiro">$ <?= (int) $usuario['pokedollar'] ?></span>
    </div>

    <main class="painel">
        <h1>Olá, <?= htmlspecialchars($usuario['nome']) ?>!</h1>

        <nav class="menu">
            <a class="botao azul" href="<?= $base ?>/time">Team Builder</a>
            <a class="botao vermelho" href="<?= $base ?>/batalha">Batalha</a>
            <a class="botao laranja" href="<?= $base ?>/loja">Poké Mart</a>
        </nav>

        <div class="importar">
            <p class="aviso">Importar Pokémon da PokéAPI</p>
            <select id="geracao" aria-label="Geração">
                <?php foreach ([
                    1 => 'Kanto',
                    2 => 'Johto',
                    3 => 'Hoenn',
                    4 => 'Sinnoh',
                    5 => 'Unova',
                    6 => 'Kalos',
                    7 => 'Alola',
                    8 => 'Galar',
                    9 => 'Paldea'
                ] as $n => $regiao): ?>
                    <option value="<?= $n ?>"><?= $n ?>ª geração (<?= $regiao ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="btn-importar">Importar geração</button>
            <p id="status-importacao" class="aviso" role="status"></p>
        </div>

        <button type="button" id="btn-sair">Sair</button>
    </main>

    <!-- passa o caminho do site para o JavaScript (o ApiClient usa nas URLs) -->
    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/Home.js"></script>
</body>

</html>