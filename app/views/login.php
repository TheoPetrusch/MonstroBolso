<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/login.css">
</head>

<body class="tela-login">
    <main class="cartao">
        <h1>MONSTRO<br>BOLSO</h1>
        <p class="subtitulo">Monte seu time e vença os ginásios!</p>

        <div class="abas">
            <button type="button" class="aba ativa" data-aba="login">Entrar</button>
            <button type="button" class="aba" data-aba="registro">Cadastrar</button>
        </div>

        <form id="form-login" autocomplete="on">
            <label>E-mail <input type="email" name="email" required></label>
            <label>Senha <input type="password" name="senha" required></label>
            <button type="submit">Entrar</button>
        </form>

        <form id="form-registro" hidden>
            <label>Nome <input type="text" name="nome" maxlength="60" required></label>
            <label>E-mail <input type="email" name="email" required></label>
            <label>Senha (mín. 6) <input type="password" name="senha" minlength="6" required></label>
            <button type="submit">Criar conta</button>
        </form>

        <p id="mensagem" role="alert"></p>
    </main>

    <script>
        window.BASE = <?= json_encode($base) ?>;
    </script>
    <script src="<?= $base ?>/assets/js/ApiClient.js"></script>
    <script src="<?= $base ?>/assets/js/login.js"></script>
</body>

</html>
