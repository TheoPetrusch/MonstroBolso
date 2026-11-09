<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MonstroBolso - Login</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>

<body>
    <main class="cartao">
        <h1>MonstroBolso</h1>

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