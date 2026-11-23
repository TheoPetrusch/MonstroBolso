<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Batalha</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;800&family=Press+Start+2P&display=swap">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/team.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/time.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/batalha.css">
</head>

<body class="pagina time">
    <header class="topo">
        <a class="voltar" href="<?= $base ?>/home">&larr; Início</a>
        <h1>Batalha</h1>
    </header>

    <div class="layout">
        <section class="painel-time arena">
            <!-- antes de começar -->
            <div id="inicio" class="inicio">
                <p>Enfrente um treinador com 1 a 6 Pokémon de força parecida com a do seu time.
                   Vencer dá <strong>150 Pokédollar por Pokémon</strong> derrotado.
                   A cada <strong>10 vitórias</strong>, você pode desafiar o próximo líder de ginásio.</p>
                <button type="button" id="btn-iniciar">Começar batalha</button>
                <button type="button" id="btn-centro" class="sec">Curar time no Centro Pokémon (grátis)</button>

                <!-- ginásios: preenchido pelo batalha.js -->
                <div id="ginasio" class="ginasio"></div>
            </div>

            <!-- durante e depois da batalha -->
            <div id="campo" class="campo" hidden>
                <div id="lado-oponente" class="lado oponente"></div>
                <div id="lado-jogador" class="lado jogador"></div>
            </div>

            <div id="resultado" class="resultado" hidden></div>

            <div id="acoes" class="acoes-batalha" hidden>
                <h2>Golpes</h2>
                <div id="golpes" class="golpes"></div>

                <h2>Mochila</h2>
                <div class="usar-item">
                    <select id="item"></select>
                    <select id="alvo"></select>
                    <button type="button" id="btn-usar" class="sec">Usar</button>
                </div>

                <h2>Trocar Pokémon</h2>
                <div id="reservas" class="reservas"></div>

                <button type="button" id="btn-fugir" class="perigo">Fugir (conta como derrota)</button>
            </div>
        </section>

        <section class="coluna-time">
            <div class="painel-resumo">
                <h2>O que aconteceu</h2>
                <ol id="log" class="log"></ol>
            </div>
            <div class="painel-resumo">
                <h2>Últimas batalhas</h2>
                <div id="historico"></div>
            </div>
        </section>
    </div>

    <p id="mensagem" role="alert" class="toast"></p>

    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/batalha.js"></script>
</body>

</html>
