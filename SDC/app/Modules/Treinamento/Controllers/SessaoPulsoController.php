<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Pulso de sessao do player de video. As rotas /videos/* usam sessao somente
 * leitura e nao renovam o tempo de vida da sessao; o player chama este endpoint
 * (grupo web, guard servidor ou cidadao conforme a rota) para o pipeline normal
 * regravar a sessao enquanto o video toca. Nao grava nada alem do StartSession.
 */
class SessaoPulsoController extends Controller
{
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
