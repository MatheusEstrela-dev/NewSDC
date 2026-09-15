<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Ingestores;

use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Consome o FDSN Event Web Service da USP (moho.iag.usp.br).
 *
 * A pagina publica de "ultimos terremotos" renderiza esta mesma consulta via
 * JavaScript; ir direto ao servico dispensa navegador e devolve dado tipado,
 * ja filtrado por quadrante no servidor.
 */
final class UspFdsnIngestor implements FonteIngestor
{
    public function chave(): string
    {
        return 'usp-fdsn';
    }

    public function grupo(): string
    {
        return 'sismos';
    }

    public function formato(): string
    {
        return 'fdsn-text';
    }

    public function coletar(): PayloadBruto
    {
        $url = (string) config('medalhao.sismos.usp_fdsn_url');
        $bbox = config('medalhao.sismos.bbox');
        $dias = (int) config('medalhao.sismos.janela_coleta_dias');

        $params = [
            'starttime' => Carbon::now('UTC')->subDays($dias)->format('Y-m-d'),
            'minlatitude' => $bbox['min_lat'],
            'maxlatitude' => $bbox['max_lat'],
            'minlongitude' => $bbox['min_lon'],
            'maxlongitude' => $bbox['max_lon'],
            'format' => 'text',
        ];

        $inicio = microtime(true);
        $resposta = Http::timeout(30)->retry(3, 500, throw: false)->get($url, $params);
        $duracao = (int) round((microtime(true) - $inicio) * 1000);

        $meta = [
            'url' => $url,
            'params' => $params,
            'status' => $resposta->status(),
            'duracao_ms' => $duracao,
        ];

        // No padrao FDSN, 204 e 404 significam "nenhum evento no criterio", nao
        // falha. Tratar como erro geraria alarme falso em periodo sem sismos em
        // MG — que e a situacao normal.
        if (in_array($resposta->status(), [204, 404], true)) {
            return new PayloadBruto('', $this->formato(), $meta);
        }

        if ($resposta->failed()) {
            throw new RuntimeException(
                "Falha ao consultar o FDSN da USP: HTTP {$resposta->status()}"
            );
        }

        return new PayloadBruto($resposta->body(), $this->formato(), $meta);
    }
}
