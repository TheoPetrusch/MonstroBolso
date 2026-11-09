<?php
namespace App\Models;

use InvalidArgumentException;

class Usuario
{
    public const PERFIS = ['jogador', 'admin'];

    private ?int $id;
    private string $nome;
    private string $email;
    private string $senhaHash;
    private string $perfil;
    private int $pokedollar;

    public function __construct(
        ?int $id,
        string $nome,
        string $email,
        string $senhaHash,
        string $perfil = 'jogador',
        int $pokedollar = 1000
    ) {
        $this->id = $id;
        $this->setNome($nome);
        $this->setEmail($email);
        $this->senhaHash = $senhaHash;
        $this->setPerfil($perfil);
        $this->pokedollar = $pokedollar;
    }

    public static function fromRow(array $r): self
    {
        return new self(
            (int) $r['id'], $r['nome'], $r['email'], $r['senha_hash'],
            $r['perfil'], (int) $r['pokedollar']
        );
    }

    public static function novo(string $nome, string $email, string $senha): self
    {
        if (strlen($senha) < 6) {
            throw new InvalidArgumentException('A senha precisa ter ao menos 6 caracteres.');
        }
        return new self(null, $nome, $email, password_hash($senha, PASSWORD_DEFAULT));
    }

    public function verificarSenha(string $senha): bool
    {
        return password_verify($senha, $this->senhaHash);
    }

    //getters
    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getEmail(): string { return $this->email; }
    public function getSenhaHash(): string { return $this->senhaHash; }
    public function getPerfil(): string { return $this->perfil; }
    public function getPokedollar(): int { return $this->pokedollar; }
    public function isAdmin(): bool { return $this->perfil === 'admin'; }

    // setters com validacao
    public function setNome(string $nome): void
    {
        $nome = trim($nome);
        if ($nome === '' || mb_strlen($nome) > 60) {
            throw new InvalidArgumentException('Nome inválido (1 a 60 caracteres).');
        }
        $this->nome = $nome;
    }

    public function setEmail(string $email): void
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }
        $this->email = $email;
    }

    public function setPerfil(string $perfil): void
    {
        if (!in_array($perfil, self::PERFIS, true)) {
            throw new InvalidArgumentException('Perfil inválido.');
        }
        $this->perfil = $perfil;
    }

    public function paraArray(): array
    {
        return [
            'id'         => $this->id,
            'nome'       => $this->nome,
            'email'      => $this->email,
            'perfil'     => $this->perfil,
            'pokedollar' => $this->pokedollar,
        ];
    }
}
