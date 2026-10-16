<?php

// Se escolheu Opção A (Composer):
require_once __DIR__ . '/../vendor/autoload.php';

// Se escolheu Opção B (Sem Composer), comente a linha acima e use esta:
// require_once __DIR__ . '/../config/autoload.php';

use App\Models\Avemon;
use App\Models\Movimento;

$meuAvemon = new Avemon(1, 'Pikachu', 100, 90, 'ELE');
$golpe = new Movimento(1, 'Choque do Trovão', 'Causa dano elétrico', 'dano', 40, 0);

$meuAvemon->adicionarMovimento($golpe);

echo "Avemon criado: " . $meuAvemon->getNome() . "<br>";
echo "Golpe adicionado: " . $meuAvemon->getMovimentos()[0]->getNome();