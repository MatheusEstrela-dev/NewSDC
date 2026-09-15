<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Ingestores;

use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Consome o portal do Observatorio Sismologico da UnB.
 *
 * A pagina entrega o CSV de eventos dentro de um <textarea>, ja no corpo da
 * resposta HTTP — nao ha renderizacao por JavaScript, entao o Selenium do
 * notebook original era desnecessario.
 */
final class UnbObsisIngestor implements FonteIngestor
{
    public function chave(): string
    {
        return 'unb-obsis';
    }

    public function grupo(): string
    {
        return 'sismos';
    }

    public function formato(): string
    {
        return 'obsis-csv';
    }

    public function coletar(): PayloadBruto
    {
        $url = (string) config('medalhao.sismos.unb_obsis_url');

        $inicio = microtime(true);
        $resposta = Http::timeout(30)->retry(3, 500, throw: false)->get($url);
        $duracao = (int) round((microtime(true) - $inicio) * 1000);

        if ($resposta->failed()) {
            throw new RuntimeException(
                "Falha ao consultar o obsis da UnB: HTTP {$resposta->status()}"
            );
        }

        return new PayloadBruto(
            $this->extrairTextarea($resposta->body()),
            $this->formato(),
            [
                'url' => $url,
                'status' => $resposta->status(),
                'duracao_ms' => $duracao,
            ],
        );
    }

    private function extrairTextarea(string $html): string
    {
        if (preg_match('/<textarea[^>]*>(.*?)<\/textarea>/is', $html, $m) !== 1) {
            throw new RuntimeException('Textarea de eventos nao encontrado na pagina do obsis.');
        }

        // O portal serializa as quebras de linha como entidade &#10;. O
        // get_attribute("value") do WebDriver decodificava isso sozinho; no HTTP
        // puro e preciso fazer explicitamente, senao o CSV chega como uma unica
        // linha gigante.
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
