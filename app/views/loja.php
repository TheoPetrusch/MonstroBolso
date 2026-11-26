<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Poké Mart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/team.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/mart.css">
</head>

<body class="pagina mart">
    <header class="topo">
        <a class="voltar" href="<?= $base ?>/home">&larr; Início</a>
        <h1>POKÉ MART</h1>
        <span class="saldo"><span class="moeda" aria-hidden="true"></span>$ <span id="saldo">0</span></span>
    </header>

    <div class="layout">
        <aside class="painel-mochila">
            <h2><span class="icone-mochila" aria-hidden="true"></span>SUA MOCHILA</h2>
            <ul id="mochila" class="mochila"></ul>
            <p class="lembrete">Use os itens no Team Builder, em cada Pokémon do time.</p>
        </aside>

        <section class="painel-loja">
            <div class="titulo-painel">
                <h2>ITENS À VENDA</h2>
                <div id="filtros" class="filtros"></div>
            </div>
            <div id="loja" class="catalogo"></div>
        </section>
    </div>

    <!-- barraca decorativa no canto de baixo, em cima do chão de madeira -->
    <img class="atendente" src="<?= $base ?>/assets/imagens/vendedor.png" alt="">
    <img class="barraca" src="<?= $base ?>/assets/imagens/barraca.png" alt="">

    <p id="mensagem" role="alert" class="toast"></p>

    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/loja.js"></script>
</body>

</html>