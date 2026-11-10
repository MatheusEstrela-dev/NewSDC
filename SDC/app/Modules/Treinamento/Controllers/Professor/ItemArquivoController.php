<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ArquivoCursoService;
use App\Modules\Treinamento\Services\ConteudoCursoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemArquivoController extends Controller
{
    public function __construct(
        private readonly ConteudoCursoService $conteudo,
        private readonly ArquivoCursoService $arquivos,
    ) {}

    public function __invoke(Request $request, Treinamento $treinamento, ModuloItem $item): StreamedResponse
    {
        $this->conteudo->garantirDoCurso($treinamento, $item);
        abort_unless($item->temArquivo(), 404);

        return $this->arquivos->responder($item->arquivo_path, $item->arquivo_nome ?? 'arquivo', $request->boolean('download'));
    }
}
