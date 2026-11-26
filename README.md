# MonstroBolso

## Como rodar o jogo (XAMPP)

1. Copie a pasta do projeto para `C:\xampp\htdocs\MonstroBolso`.
2. Abra o XAMPP Control Panel e inicie o **Apache** e o **MySQL**.
3. Acesse **http://localhost/phpmyadmin**, clique em **Importar** e escolha o arquivo `database/monstrobolso.sql`.
   - Ele cria o banco `monstrobolso` do zero (se já existir, é apagado e recriado).
   - Avisos de "Tipo de declaração desconhecido" podem ser ignorados.
4. Abra **http://localhost/MonstroBolso/public** no navegador.

Se o MySQL não for o padrão (`localhost`, usuário `root`, sem senha), crie um arquivo `.env` na raiz do projeto:

```
DB_HOST=localhost
DB_NAME=monstrobolso
DB_USER=root
DB_PASS=
```

## Contas de teste

| E-mail | Senha | Perfil |
|---|---|---|
| jogador@teste.com | jogador123 | jogador |
| admin@teste.com | admin123 | admin |