<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\CredencialQrService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Destino da URL contida no QR e da busca manual (CPF ou codigo de certificado):
 * consulta (e registra) as capacitacoes do servidor.
 */
class ValidarParticipanteConsultarController extends Controller
{
    public function __construct(private readonly CredencialQrService $credenciais) {}

    public function __invoke(Request $request, string $token): Response
    {
        $resultado = $this->credenciais->consultar($token, $request->user());

        return Inertia::render('Treinamento/ValidarParticipante/Index', [
            'resultado' => $resultado,
            'erro' => $resultado === null ? 'Nenhum servidor encontrado para este QR, CPF ou codigo de certificado.' : null,
        ]);
    }
}
