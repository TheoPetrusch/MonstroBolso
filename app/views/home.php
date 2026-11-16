<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Início</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/team.css">
</head>

<body>
    <main class="cartao">
        <h1>Olá, <?= htmlspecialchars($usuario['nome']) ?>!</h1>
        <p>Perfil: <strong><?= htmlspecialchars($usuario['perfil']) ?></strong></p>
        <p>Pokédollar: <strong>$ <?= (int) $usuario['pokedollar'] ?></strong></p>

        <nav class="menu">
            <a class="botao" href="<?= $base ?>/time">Team Builder</a>
            <span class="botao desativado">Batalha (em breve)</span>
            <span class="botao desativado">Poké Mart (em breve)</span>
        </nav>

        <?php if ($usuario['perfil'] === 'admin'): ?>
            <div class="menu">
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
    <script src="<?= $base ?>/assets/js/home.js"></script>
</body>

</html>