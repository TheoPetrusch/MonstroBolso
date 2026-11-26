<?php

namespace App\Controllers;

use App\Core\BaseController;

class PingController extends BaseController
{
    public function ping(): void
    {
        $this->json(['ok' => true]);
    }
}
