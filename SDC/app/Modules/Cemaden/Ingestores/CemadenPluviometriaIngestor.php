<?php

declare(strict_types=1);

namespace App\Modules\Cemaden\Ingestores;

use App\Modules\Cemaden\Services\CemadenApiClient;
use App\Modules\Medalhao\Contracts\FonteIngestor;
use App\Modules\Medalhao\DTOs\PayloadBruto;

/**
 * Coleta a rede automatica do CEMADEN, recortada em MG.
 *
 * Uma requisicao por ciclo, contra as 68 do InmetApiIngestor: o feed e agregado
 * nacional, entao nao ha Http::pool aqui.
 *
 * O recorte por UF acontece ANTES de virar Bronze, seguindo o precedente do
 * INMET. Duas razoes: o payload nacional tem 2,1 MB e, a 144 coletas por dia,
 * guardar o Brasil inteiro custaria ~300 MB/dia de Bronze para descartar 86%
 * na normalizacao. Recortado em MG cai para ~43 MB/dia.
 */
final class CemadenPluviometriaIngestor implements FonteIngestor
{
    public function __construct(
        private readonly CemadenApiClient $cliente,
    ) {
    }

    public function chave(): string
    {
        return 'cemaden-pluviometria';
    }

    public function grupo(): string
    {
        return 'cemaden';
    }

    public function formato(): string
    {
        return 'cemaden-json';
    }

    public function coletar(): PayloadBruto
    {
        $inicio = microtime(true);

        $uf = (string) config('medalhao.cemaden.uf', 'MG');
        $snapshot = $this->cliente->snapshot();
        $estacoes = $this->doRecorte($snapshot['estacoes'], $uf);

        $conteudo = json_encode([
            'atualizado' => $snapshot['atualizado'],
            'estacoes' => $estacoes,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $comTelemetria = count(array_filter(
            $estacoes,
            static fn (array $e): bool => ($e['acumulado'] ?? null) !== null
        ));

        return new PayloadBruto($conteudo, $this->formato(), [
            'atualizado' => $snapshot['atualizado'],
            'uf' => $uf,
            'estacoes_no_feed' => count($snapshot['estacoes']),
            'estacoes_no_recorte' => count($estacoes),
            // Sem este numero, uma queda de telemetria da rede seria
            // indistinguivel de um dia sem chuva.
            'estacoes_com_telemetria' => $comTelemetria,
            'duracao_ms' => (int) round((microtime(true) - $inicio) * 1000),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $estacoes
     * @return array<int, array<string, mixed>>
     */
    private function doRecorte(array $estacoes, string $uf): array
    {
        return array_values(array_filter(
            $estacoes,
            static function (mixed $e) use ($uf): bool {
                return is_array($e) && strtoupper((string) ($e['uf'] ?? '')) === $uf;
            }
        ));
    }
}
