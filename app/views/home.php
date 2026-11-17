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
            <a class="botao" href="<?= $base ?>/loja">Poké Mart</a>
        </nav>

        <div class="menu">
            <p class="aviso">Importar Pokémon da PokéAPI</p>
            <select id="geracao" aria-label="Geração">
                <?php foreach ([1 => 'Kanto', 2 => 'Johto', 3 => 'Hoenn', 4 => 'Sinnoh', 5 => 'Unova',
                                6 => 'Kalos', 7 => 'Alola', 8 => 'Galar', 9 => 'Paldea'] as $n => $regiao): ?>
                    <option value="<?= $n ?>"><?= $n ?>ª geração (<?= $regiao ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="btn-importar">Importar geração</button>
            <p id="status-importacao" class="aviso" role="status"></p>
        </div>

        <button type="button" id="btn-sair">Sair</button>
    </main>

    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/home.js"></script>
</body>

</html>