<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela do leitor de QR Code (sem resultado ate ler uma credencial).
 */
class ValidarParticipanteController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Treinamento/ValidarParticipante/Index', [
            'resultado' => null,
            'erro' => null,
        ]);
    }
}
