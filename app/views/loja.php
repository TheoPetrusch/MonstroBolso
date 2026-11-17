<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Poké Mart</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/team.css">
</head>

<body class="pagina">
    <header class="topo">
        <a href="<?= $base ?>/home">&larr; Início</a>
        <h1>Poké Mart</h1>
        <span class="saldo">$ <span id="saldo">0</span></span>
    </header>

    <div class="layout">
        <section>
            <h2>Sua mochila</h2>
            <ul id="mochila" class="mochila"></ul>
        </section>

        <section>
            <h2>Itens à venda</h2>
            <div id="loja" class="catalogo"></div>
        </section>
    </div>

    <p id="mensagem" role="alert" class="toast"></p>

    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/loja.js"></script>
</body>

</html>
