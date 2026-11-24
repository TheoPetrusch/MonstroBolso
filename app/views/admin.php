<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/team.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/time.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>

<body class="pagina time">
    <header class="topo">
        <a class="voltar" href="<?= $base ?>/home">&larr; Início</a>
        <h1>ADMIN</h1>
    </header>

    <div class="layout">
        <section class="painel-time">
            <h2>USUÁRIOS E PERFIS</h2>
            <p class="dica">Troque o perfil de um usuário entre jogador e admin.
                Cada troca fica registrada no log de perfis.</p>
            <div id="usuarios"></div>
        </section>

        <section class="painel-resumo">
            <h2>LOG DE TROCAS DE PERFIL</h2>
            <div id="historico"></div>
        </section>
    </div>

    <p id="mensagem" role="alert" class="toast"></p>

    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/admin.js"></script>
</body>

</html>
