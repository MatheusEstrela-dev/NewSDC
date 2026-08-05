<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\GeradorImagemDivulgacaoService;
use Illuminate\Http\Response;

/**
 * RF02 - imagem de divulgacao gerada sob demanda (sempre reflete os dados
 * atuais do treinamento, sem precisar invalidar cache quando o evento muda).
 * Rota publica de proposito: e pensada para ser compartilhada/postada.
 */
class TreinamentoDivulgacaoImagemController extends Controller
{
    public function __invoke(GeradorImagemDivulgacaoService $service, Treinamento $treinamento): Response
    {
        abort_unless($treinamento->estaPublicado(), 404);

        $png = $service->gerar($treinamento);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
