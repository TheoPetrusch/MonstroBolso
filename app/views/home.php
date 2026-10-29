<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Início</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<main class="cartao">
    <h1>Olá, <?= htmlspecialchars($usuario['nome']) ?>!</h1>
    <p>Perfil: <strong><?= htmlspecialchars($usuario['perfil']) ?></strong></p>
    <p>Pokédollar: <strong>$ <?= (int) $usuario['pokedollar'] ?></strong></p>
    <p class="aviso">Aqui entrarão os links: Team Builder, Batalha e Poké Mart.</p>
    <button type="button" id="btn-sair">Sair</button>
</main>

<script>window.BASE = <?= json_encode($base) ?>;</script>
<script src="<?= $base ?>/assets/js/ApiClient.js"></script>
<script>
    document.getElementById('btn-sair').addEventListener('click', async () => {
        await new ApiClient(window.BASE).post('/api/logout');
        location.href = window.BASE + '/';
    });
</script>
</body>
</html>
