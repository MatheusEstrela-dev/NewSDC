<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\TreinamentoService;

class TreinamentoPublicarController extends Controller
{
    public function __construct(
        private readonly TreinamentoService $treinamentoService
    ) {
    }

    public function __invoke(Treinamento $treinamento)
    {
        // O EAD publica pelo conteudo do curso, que valida item, prova, changelog e
        // aviso; esta rota pularia essas verificacoes.
        if ($treinamento->isEad()) {
            return back()->withErrors(['status' => 'Cursos EAD são publicados pelo conteúdo do curso (área do professor), que confere itens, prova e notas da versão.']);
        }

        try {
            $this->treinamentoService->publicar($treinamento);
        } catch (\DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Treinamento publicado no Portal do Cidadao!');
    }
}
