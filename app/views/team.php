<?php
/**
 * VIEW do Team Builder. Só a estrutura da página: slots, resumo, catálogo e a
 * janela de detalhes (<dialog>) são preenchidos pelo team.js com dados da API.
 * $base = caminho do site (ex.: /MonstroBolso/public), usado nos links.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Team Builder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/team.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/time.css">
</head>

<body class="pagina time">
    <header class="topo">
        <a class="voltar" href="<?= $base ?>/home">&larr; Início</a>
        <h1>TEAM BUILDER</h1>
    </header>

    <div class="layout">
        <section class="coluna-time">
            <div class="painel-time">
                <div class="titulo-painel">
                    <h2>SEU TIME</h2>
                    <span class="contador"><span id="contagem">0</span>/6</span>
                    <button type="button" id="btn-centro" title="Cura HP, condições e desmaiados de graça">Centro
                        Pokémon</button>
                    <button type="button" id="btn-limpar" class="perigo">Limpar time</button>
                </div>
                <div id="slots" class="slots"></div>
            </div>

            <div class="painel-resumo">
                <h2>RESUMO DO TIME</h2>
                <div id="resumo"></div>
            </div>
        </section>

        <section class="painel-catalogo">
            <div class="titulo-painel">
                <h2>POKÉMON DISPONÍVEIS</h2>
                <label class="campo-busca">
                    <span class="sr-only">Buscar</span>
                    <input id="busca" type="search" placeholder="Buscar por nome ou tipo">
                </label>
            </div>
            <div id="filtros" class="filtros"></div>
            <div id="catalogo" class="catalogo"></div>
        </section>
    </div>

    <dialog id="janela-detalhes" class="janela">
        <div class="janela-topo">
            <h2 id="janela-titulo">Detalhes</h2>
            <button type="button" id="fechar-detalhes" aria-label="Fechar">&times;</button>
        </div>
        <div id="detalhes" class="detalhes"></div>
    </dialog>

    <p id="mensagem" role="alert" class="toast"></p>

    <!-- passa o caminho do site para o JavaScript (o ApiClient usa nas URLs) -->
    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/PokeApi.js"></script>
    <script src="<?= $base ?>/assets/js/team.js"></script>
</body>

</html>