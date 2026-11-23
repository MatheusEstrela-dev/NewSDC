<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\GeradorImagemDivulgacaoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * RF02 - imagem de divulgacao. O PNG e gerado uma vez por versao do conteudo
 * (hash dos campos que entram na imagem) e guardado em disco: mudou o evento,
 * o hash muda e a imagem e refeita, sem invalidar nada na mao. Rota publica de
 * proposito (pensada para ser compartilhada/postada), com throttle na rota.
 */
class TreinamentoDivulgacaoImagemController extends Controller
{
    public function __invoke(GeradorImagemDivulgacaoService $service, Treinamento $treinamento): Response
    {
        abort_unless($treinamento->estaPublicado(), 404);

        $png = $this->png($service, $treinamento);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** PNG do disco (por hash do conteudo) ou gerado e guardado; versoes antigas do mesmo treinamento saem. */
    private function png(GeradorImagemDivulgacaoService $service, Treinamento $treinamento): string
    {
        $disco = Storage::disk('local');
        $pasta = 'divulgacao-cache';
        $hash = hash('sha256', json_encode([
            $treinamento->titulo,
            $treinamento->descricao,
            $treinamento->categoria?->value,
            $treinamento->tipo?->value,
            $treinamento->local,
            $treinamento->data_inicio?->toDateString(),
            $treinamento->hora_inicio,
        ], JSON_UNESCAPED_UNICODE));
        $arquivo = "{$pasta}/{$treinamento->getKey()}-{$hash}.png";

        if ($disco->exists($arquivo)) {
            return $disco->get($arquivo);
        }

        $png = $service->gerar($treinamento);

        foreach ($disco->files($pasta) as $antigo) {
            if (str_starts_with(basename($antigo), $treinamento->getKey() . '-')) {
                $disco->delete($antigo);
            }
        }
        $disco->put($arquivo, $png);

        return $png;
    }
}
