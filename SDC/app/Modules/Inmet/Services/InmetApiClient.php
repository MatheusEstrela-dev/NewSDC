<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Cliente da API do INMET (apitempo.inmet.gov.br).
 *
 * Duas particularidades da fonte, medidas em 2026-09-01, que a versao anterior
 * desta classe nao atendia — e que juntas a deixavam devolver lista vazia
 * sempre, em silencio:
 *
 *   1. User-Agent de navegador e OBRIGATORIO. Sem ele o servidor completa o
 *      handshake TLS e corta a conexao na leitura da resposta (curl 56,
 *      "unexpected eof while reading"). Http::get() do Laravel nao envia
 *      User-Agent por padrao.
 *   2. A rota de leituras exige o codigo da estacao ANTES do token:
 *      /token/estacao/{inicio}/{fim}/{codigo}/{token}. A versao anterior
 *      montava /token/estacao/{inicio}/{fim}/{token}, que a API responde com
 *      404 HttpException: E_ROUTE_NOT_FOUND.
 *
 * Nao ha endpoint de todas as estacoes: leitura e uma chamada por estacao.
 * O antigo Cache::remember de 900s tambem saiu — quem guarda historico agora e
 * a camada Bronze, e cachear aqui so esconderia a coleta do pipeline.
 */
class InmetApiClient
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    /** @return array<int, array<string, mixed>> */
    public function inventario(): array
    {
        $url = (string) config('medalhao.inmet.inventario_url');

        $resposta = $this->requisicao()->get($url);

        // Estoura em vez de devolver []: a versao anterior engolia a falha num
        // catch, e foi por isso que o defeito passou meses invisivel.
        if ($resposta->failed()) {
            throw new RuntimeException("Falha no inventario do INMET: HTTP {$resposta->status()}");
        }

        return is_array($resposta->json()) ? $resposta->json() : [];
    }

    /** @return array<int, array<string, mixed>> */
    public function leiturasDaEstacao(string $codigo, string $dia): array
    {
        $resposta = $this->requisicao()->get($this->urlLeituras($codigo, $dia));

        if ($resposta->failed()) {
            throw new RuntimeException("Falha nas leituras de {$codigo}: HTTP {$resposta->status()}");
        }

        return is_array($resposta->json()) ? $resposta->json() : [];
    }

    /**
     * Busca varias estacoes concorrentemente. Falha de estacao NAO aborta o
     * lote: Bronze e historico bruto, um ciclo parcial serve, e o proximo
     * recoleta. As falhas voltam nomeadas para virar meta da ingestao.
     *
     * @param list<string> $codigos
     * @return array{leituras: array<int, array<string, mixed>>, falhas: array<string, string>}
     */
    public function leiturasEmLote(array $codigos, string $dia): array
    {
        $leituras = [];
        $falhas = [];
        $tamanho = max(1, (int) config('medalhao.inmet.concorrencia', 20));

        foreach (array_chunk($codigos, $tamanho) as $fatia) {
            $respostas = Http::pool(fn (Pool $pool) => array_map(
                fn (string $codigo) => $pool->as($codigo)
                    ->withHeaders(['User-Agent' => self::USER_AGENT])
                    ->timeout(60)
                    ->get($this->urlLeituras($codigo, $dia)),
                $fatia
            ));

            foreach ($fatia as $codigo) {
                $resposta = $respostas[$codigo] ?? null;

                // Http::pool devolve a excecao no lugar da resposta quando a
                // requisicao nem completa (DNS, TLS, timeout).
                if ($resposta === null || $resposta instanceof Throwable) {
                    $falhas[$codigo] = $resposta instanceof Throwable
                        ? $resposta->getMessage()
                        : 'sem resposta';
                    continue;
                }

                if ($resposta->failed()) {
                    $falhas[$codigo] = "HTTP {$resposta->status()}";
                    continue;
                }

                $corpo = $resposta->json();

                if (! is_array($corpo)) {
                    $falhas[$codigo] = 'corpo nao e json';
                    continue;
                }

                foreach ($corpo as $linha) {
                    $leituras[] = $linha;
                }
            }
        }

        return ['leituras' => $leituras, 'falhas' => $falhas];
    }

    private function urlLeituras(string $codigo, string $dia): string
    {
        $base = rtrim((string) config('medalhao.inmet.leituras_url'), '/');
        $token = (string) config('medalhao.inmet.token');

        // Ordem obrigatoria: codigo da estacao antes do token.
        return "{$base}/{$dia}/{$dia}/{$codigo}/{$token}";
    }

    private function requisicao(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout(60)
            ->retry(3, 500, throw: false);
    }
}
