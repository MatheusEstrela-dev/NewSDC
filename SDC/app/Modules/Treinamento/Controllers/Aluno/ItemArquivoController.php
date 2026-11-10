<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ArquivoCursoService;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemArquivoController extends Controller
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly ArquivoCursoService $arquivos,
    ) {}

    public function __invoke(Request $request, Treinamento $treinamento, int $item): StreamedResponse
    {
        $inscricao = InscricaoDoAluno::aprovada($treinamento, $request->user());
        $moduloItem = InscricaoDoAluno::item($treinamento, $item);

        abort_unless($this->trilha->itemLiberado($inscricao, $moduloItem), 403);
        abort_unless($moduloItem->temArquivo(), 404);

        return $this->arquivos->responder($moduloItem->arquivo_path, $moduloItem->arquivo_nome ?? 'arquivo', $request->boolean('download'));
    }
}
