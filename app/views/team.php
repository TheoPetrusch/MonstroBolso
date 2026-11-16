<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Team Builder</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/team.css">
</head>

<body class="pagina">
    <header class="topo">
        <a href="<?= $base ?>/home">&larr; Início</a>
        <h1>Team Builder</h1>
    </header>

    <div class="layout">
        <section>
            <h2>Seu time (<span id="contagem">0</span>/6)</h2>
            <div id="slots" class="slots"></div>

            <h2>Detalhes (PokéAPI)</h2>
            <div id="detalhes" class="detalhes">Clique em "Detalhes" num Pokémon.</div>
        </section>

        <section>
            <h2>Pokémon disponíveis</h2>
            <input id="busca" type="search" placeholder="Buscar por nome ou tipo...">
            <div id="catalogo" class="catalogo"></div>
        </section>
    </div>

    <p id="mensagem" role="alert" class="toast"></p>

    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/PokeApi.js"></script>
    <script src="<?= $base ?>/assets/js/team.js"></script>
</body>

</html>