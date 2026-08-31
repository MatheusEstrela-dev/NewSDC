<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Search\FonteDeBusca;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Busca global do sistema.
 *
 * Antes: quatro closures escritas a mao aqui dentro (PAE, Decretacoes, RAT,
 * Demandas), disparadas em paralelo. Acrescentar modulo exigia editar este
 * arquivo, e o resultado pratico foi que catorze modulos ficaram de fora --
 * inclusive os dois maiores conjuntos de dados do sistema, cisternas (8.099
 * registros) e ajuda humanitaria, enquanto as quatro fontes indexadas somavam
 * dezenove linhas.
 *
 * Agora as fontes vem de config('search.fontes'). Tres decisoes governam o
 * desenho, e todas olham para o banco CRESCENDO:
 *
 * 1. SEQUENCIAL, numa conexao. O paralelo pegava um PDO do pool Swoole por
 *    fonte; com dezoito fontes, doze workers e max_connections=100, a busca
 *    derrubaria o banco no caminho mais quente do sistema. Cada consulta e
 *    trigram-indexada e devolve no maximo sete linhas, entao o custo somado
 *    fica na casa de dezenas de milissegundos.
 *
 * 2. ORCAMENTO DE TEMPO. Ao estourar, as fontes restantes sao puladas. Uma
 *    tabela que cresceu demais degrada o proprio grupo, nao a caixa inteira.
 *
 * 3. PODA POR PERMISSAO antes de consultar. Quem nao enxerga o modulo nao paga
 *    a consulta nem recebe o registro -- desempenho e sigilo pelo mesmo
 *    mecanismo.
 */
class GlobalSearchService
{
    public function search(string $query): array
    {
        $normalized = $this->normalize($query);

        if (mb_strlen($normalized) < 2) {
            return $this->resultadoVazio();
        }

        return Cache::remember(
            $this->chaveDeCache($normalized),
            (int) config('search.cache_ttl', 60),
            fn (): array => $this->consultarFontes($normalized),
        );
    }

    /**
     * A chave inclui o USUARIO, e nao so o termo.
     *
     * Desde que as fontes passaram a respeitar escopo -- o COMPDEC enxerga
     * apenas o proprio municipio -- um cache por termo faria o primeiro
     * pesquisador encher a caixa para todos os outros: um usuario da CEDEC
     * pesquisaria "silva", e o COMPDEC de outro municipio receberia esse
     * resultado inteiro. Vazamento silencioso, sem erro nenhum no log.
     */
    private function chaveDeCache(string $termo): string
    {
        return 'global_search:'.(Auth::id() ?? 'anon').':'.md5($termo);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function consultarFontes(string $termo): array
    {
        $limite = (int) config('search.limite_por_fonte', 7);
        $orcamento = (int) config('search.orcamento_ms', 250);

        $resultado = [];
        $inicio = microtime(true);

        foreach ($this->fontes() as $fonte) {
            $decorrido = (microtime(true) - $inicio) * 1000;

            if ($decorrido >= $orcamento) {
                // Registrado porque e sintoma, nao rotina: se aparecer no log,
                // alguma fonte passou a custar caro e precisa de indice.
                Log::warning('Busca global: orcamento de tempo estourado', [
                    'fonte_interrompida' => $fonte->chave(),
                    'ms' => round($decorrido),
                ]);

                break;
            }

            $resultado[$fonte->chave()] = $fonte->buscar($termo, $limite);
        }

        return $resultado;
    }

    /**
     * Fontes que este usuario pode consultar.
     *
     * @return array<int, FonteDeBusca>
     */
    private function fontes(): array
    {
        $usuario = Auth::user();
        $fontes = [];

        foreach ((array) config('search.fontes', []) as $classe) {
            try {
                /** @var FonteDeBusca $fonte */
                $fonte = app($classe);
            } catch (Throwable $e) {
                // Classe mal registrada nao pode derrubar a busca.
                report($e);

                continue;
            }

            $permissao = $fonte->permissao();

            if ($permissao !== null && ! ($usuario?->can($permissao) ?? false)) {
                continue;
            }

            $fontes[] = $fonte;
        }

        return $fontes;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function resultadoVazio(): array
    {
        $vazio = [];

        foreach ((array) config('search.fontes', []) as $classe) {
            try {
                $vazio[app($classe)->chave()] = [];
            } catch (Throwable) {
                continue;
            }
        }

        return $vazio;
    }

    private function normalize(string $query): string
    {
        // Prefixos de atalho que o palette aceita.
        $clean = ltrim(trim($query), '#@');

        // Remove controle e NBSP, preservando acentos latinos.
        $clean = preg_replace('/[^\x20-\x7E\xC0-\xFF]/u', '', $clean) ?? $clean;

        // Espacos multiplos viram um. Ponto e barra ficam: sao significativos em
        // numero de protocolo e em CPF mascarado.
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        return trim($clean);
    }
}
