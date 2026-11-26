<?php

namespace App\Controllers;

use App\Core\BaseController;

/** Rota de teste (GET /api/ping): confirma que o PHP e o roteador estão funcionando. */
class PingController extends BaseController
{
    public function ping(): void
    {
        $this->json(['ok' => true]);
    }
}
