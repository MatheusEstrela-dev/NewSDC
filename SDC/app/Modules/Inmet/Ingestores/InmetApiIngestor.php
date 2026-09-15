<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Ingestores;

use App\Modules\Inmet\Services\InmetApiClient;
use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Illuminate\Support\Carbon;

/**
 * Coleta o INMET: um inventario de estacoes e uma leitura por estacao.
 *
 * O contrato devolve UM PayloadBruto, e nao existe endpoint de todas as
 * estacoes, entao as 68 chamadas de MG sao feitas concorrentemente e
 * consolidadas num JSON so. Medido em 2026-09-01: 12 chamadas concorrentes em
 * menos de 1 segundo, o que deixa as 68 muito abaixo dos 300s de timeout do
 * worker da fila medalhao. Sequencial daria ~340s e estouraria, alem de prender
 * a fila que os sismos compartilham.
 */
final class InmetApiIngestor implements FonteIngestor
{
    public function __construct(
        private readonly InmetApiClient $cliente,
    ) {
    }

    public function chave(): string
    {
        return 'inmet-api';
    }

    public function grupo(): string
    {
        return 'inmet';
    }

    public function formato(): string
    {
        return 'inmet-json';
    }

    public function coletar(): PayloadBruto
    {
        $inicio = microtime(true);

        $uf = (string) config('medalhao.inmet.uf', 'MG');
        $somenteOperantes = (bool) config('medalhao.inmet.somente_operantes', true);
        $dia = Carbon::now('America/Sao_Paulo')->format('Y-m-d');

        $estacoes = $this->estacoesDoRecorte($uf, $somenteOperantes);

        $codigos = array_values(array_filter(array_map(
            static fn (array $e): string => (string) ($e['CD_ESTACAO'] ?? ''),
            $estacoes
        )));

        $resultado = $this->cliente->leiturasEmLote($codigos, $dia);

        $conteudo = json_encode([
            'dia' => $dia,
            'estacoes' => $estacoes,
            'leituras' => $resultado['leituras'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new PayloadBruto($conteudo, $this->formato(), [
            'dia' => $dia,
            'uf' => $uf,
            'estacoes_no_inventario' => count($estacoes),
            'estacoes_com_resposta' => count($codigos) - count($resultado['falhas']),
            'leituras' => count($resultado['leituras']),
            'falhas' => $resultado['falhas'],
            'duracao_ms' => (int) round((microtime(true) - $inicio) * 1000),
        ]);
    }

    /**
     * O recorte e por UF, nao por bbox: o inventario do INMET traz SG_ESTADO
     * confiavel, o que e mais preciso e mais barato que filtro geometrico.
     *
     * @return array<int, array<string, mixed>>
     */
    private function estacoesDoRecorte(string $uf, bool $somenteOperantes): array
    {
        return array_values(array_filter(
            $this->cliente->inventario(),
            static function (array $e) use ($uf, $somenteOperantes): bool {
                if (($e['SG_ESTADO'] ?? $e['UF'] ?? '') !== $uf) {
                    return false;
                }

                // Estacao em pane nao mede: plota-la sugeriria dado onde nao ha.
                return ! $somenteOperantes || ($e['CD_SITUACAO'] ?? '') === 'Operante';
            }
        ));
    }
}
