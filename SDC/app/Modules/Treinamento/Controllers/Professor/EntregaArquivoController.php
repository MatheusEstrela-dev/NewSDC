<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\AcompanhamentoService;
use App\Modules\Treinamento\Services\ArquivoCursoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EntregaArquivoController extends Controller
{
    public function __construct(
        private readonly AcompanhamentoService $acompanhamento,
        private readonly ArquivoCursoService $arquivos,
    ) {}

    public function __invoke(Request $request, Treinamento $treinamento, int $entrega): StreamedResponse
    {
        $registro = $this->acompanhamento->entregaDoCurso($treinamento, $entrega);
        abort_if($registro->arquivo_path === null || $registro->arquivo_path === '', 404);

        return $this->arquivos->responder(
            $registro->arquivo_path,
            $registro->arquivo_nome ?? 'entrega',
            $request->boolean('download'),
            $registro->arquivo_mime,
        );
    }
}
