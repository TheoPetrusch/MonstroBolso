<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Início</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
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
            <span class="botao desativado">Batalha (em breve)</span>
            <a class="botao laranja" href="<?= $base ?>/loja">Poké Mart</a>
        </nav>

        <?php if ($usuario['perfil'] === 'admin'): ?>
            <div class="admin">
                <p class="aviso">Área do admin</p>
                <button type="button" id="btn-importar">Importar 1ª geração (PokéAPI)</button>
                <p id="status-importacao" class="aviso" role="status"></p>
            </div>
        <?php endif; ?>

        <button type="button" id="btn-sair">Sair</button>
    </main>

    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/Home.js"></script>
</body>

</html>
