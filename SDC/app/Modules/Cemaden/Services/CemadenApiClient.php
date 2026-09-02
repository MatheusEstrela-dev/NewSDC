<?php

declare(strict_types=1);

namespace App\Modules\Cemaden\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Le o feed publico de estacoes automaticas do CEMADEN.
 *
 * Uma requisicao unica devolve o Brasil inteiro (5789 estacoes, ~2,1 MB), o que
 * e o oposto do INMET, onde nao existe endpoint agregado e cada estacao custa
 * uma chamada. Aqui nao ha Http::pool, nem token, nem login.
 *
 * O caminho alternativo, que o algoritmo de referencia em Python usa, e raspar
 * salvar.cemaden.gov.br com Selenium: exige login j_security_check e abre uma
 * pagina por estacao com tabela renderizada em JS. Este feed entrega o mesmo
 * dado de chuva sem navegador e sem credencial.
 */
final class CemadenApiClient
{
    /**
     * O feed responde 404 sem User-Agent de navegador, mesmo sintoma que o
     * InmetApiClient enfrenta.
     */
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    /**
     * Devolve o snapshot cru: o timestamp global e a lista de estacoes.
     *
     * @return array{atualizado: ?string, estacoes: array<int, array<string, mixed>>}
     */
    public function snapshot(): array
    {
        $url = (string) config('medalhao.cemaden.feed_url');

        $resposta = Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout((int) config('medalhao.cemaden.timeout', 60))
            ->get($url);

        if ($resposta->failed()) {
            throw new RuntimeException("CEMADEN respondeu {$resposta->status()} em {$url}");
        }

        $dados = $this->desembrulhar($resposta->body());

        // O feed vem como lista de um elemento; o formato ja mudou de forma
        // antes, entao aceita-se tanto a lista quanto o objeto direto.
        $bloco = array_is_list($dados) ? ($dados[0] ?? []) : $dados;

        return [
            'atualizado' => isset($bloco['atualizado']) ? (string) $bloco['atualizado'] : null,
            'estacoes' => is_array($bloco['estacao'] ?? null) ? $bloco['estacao'] : [],
        ];
    }

    /**
     * O corpo e JSONP: `estacoes([{...}])`. Nao e JSON valido, entao json_decode
     * direto devolve null e o pipeline ficaria vazio em silencio.
     *
     * @return array<mixed>
     */
    private function desembrulhar(string $corpo): array
    {
        $corpo = trim($corpo);

        $abre = strpos($corpo, '(');
        $fecha = strrpos($corpo, ')');

        if ($abre !== false && $fecha !== false && $fecha > $abre) {
            $corpo = substr($corpo, $abre + 1, $fecha - $abre - 1);
        }

        $dados = json_decode($corpo, true);

        if (! is_array($dados)) {
            throw new RuntimeException('CEMADEN devolveu corpo que nao e JSON nem JSONP reconhecivel');
        }

        return $dados;
    }
}
