<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\ArquivoCursoService;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Support\InscricaoDoAluno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalaItemArquivoController extends Controller
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly ArquivoCursoService $arquivos,
    ) {}

    public function __invoke(Request $request, string $slug, int $item): StreamedResponse
    {
        $treinamento = InscricaoDoAluno::cursoPublicado($slug);
        $inscricao = InscricaoDoAluno::aprovada($treinamento, Auth::guard('cidadao')->user());
        $moduloItem = InscricaoDoAluno::item($treinamento, $item);

        abort_unless($this->trilha->itemLiberado($inscricao, $moduloItem), 403);
        abort_unless($moduloItem->temArquivo(), 404);

        return $this->arquivos->responder($moduloItem->arquivo_path, $moduloItem->arquivo_nome ?? 'arquivo', $request->boolean('download'));
    }
}
