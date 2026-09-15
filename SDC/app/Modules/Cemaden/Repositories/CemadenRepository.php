<?php

declare(strict_types=1);

namespace App\Modules\Cemaden\Repositories;

use App\Modules\Cemaden\DTOs\EstacaoCemadenDTO;
use App\Modules\Cemaden\DTOs\LeituraCemadenDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CemadenRepository
{
    private const CHUNK = 500;

    /**
     * Le a camada Gold para o mapa. Nenhuma agregacao aqui: a matview entrega
     * lat/lon extraidos da geometria e a classe de precipitacao calculada no
     * banco.
     *
     * @return Collection<int, object>
     */
    /**
     * Le a camada Gold para o mapa.
     *
     * Com $camadaGeoId, devolve APENAS as estacoes dentro das feicoes daquela
     * camada de risco. O recorte acontece no banco, por ST_Intersects sobre o
     * indice GIST: trazer as 890 e filtrar em PHP jogaria fora 290 KB de JSON
     * por request para mostrar algumas dezenas.
     *
     * ST_Intersects e nao ST_Contains: estacao exatamente sobre a divisa da
     * area entra. Num sistema de Defesa Civil, errar incluindo e melhor que
     * errar excluindo.
     *
     * @return Collection<int, object>
     */
    public function mapa(?int $camadaGeoId = null): Collection
    {
        $query = DB::table('gold.cemaden_mapa')
            ->select([
                'id', 'codigo_estacao', 'nome_estacao', 'municipio', 'codigo_ibge',
                'uf', 'tipo', 'medido_em', 'latitude', 'longitude',
                'acumulado_24h', 'classe_precipitacao',
            ]);

        if ($camadaGeoId !== null) {
            // EXISTS e nao JOIN: estacao dentro de duas feicoes da mesma camada
            // apareceria duplicada, e o mapa desenharia dois marcadores no
            // mesmo ponto.
            $query->whereRaw(
                'EXISTS (SELECT 1 FROM silver.geo_feicoes f
                          WHERE f.camada_id = ? AND ST_Intersects(f.geom, gold.cemaden_mapa.geom))',
                [$camadaGeoId]
            );
        }

        // NULLS LAST para que estacao sem telemetria nao ocupe o topo da
        // tabela: com 476 das 830 sem transmitir, o default do Postgres
        // (NULLS FIRST no DESC) esconderia toda a chuva na ultima pagina.
        return $query->orderByRaw('acumulado_24h DESC NULLS LAST')->get();
    }

    /**
     * @return array{total_estacoes: int, precipitacao_media: float, precipitacao_maxima: float, estacoes_com_chuva: int, estacoes_sem_telemetria: int, ultima_atualizacao: ?string}
     */
    public function estatisticas(): array
    {
        $linha = DB::table('gold.cemaden_estatisticas')->first();

        return [
            'total_estacoes' => (int) ($linha->total_estacoes ?? 0),
            'precipitacao_media' => (float) ($linha->precipitacao_media ?? 0),
            'precipitacao_maxima' => (float) ($linha->precipitacao_maxima ?? 0),
            'estacoes_com_chuva' => (int) ($linha->estacoes_com_chuva ?? 0),
            'estacoes_sem_telemetria' => (int) ($linha->estacoes_sem_telemetria ?? 0),
            'ultima_atualizacao' => $linha->ultima_atualizacao ?? null,
        ];
    }

    /**
     * Contrato exigido pelo kernel: upsertLote(iterable, ?int): int.
     *
     * Aceita os dois tipos de DTO no mesmo iteravel porque uma coleta traz
     * dimensao e fato juntos, e o kernel chama o persistidor uma vez.
     *
     * @param iterable<EstacaoCemadenDTO|LeituraCemadenDTO> $dtos
     */
    public function upsertLote(iterable $dtos, ?int $ingestaoId = null): int
    {
        $estacoes = [];
        $leituras = [];
        $total = 0;

        foreach ($dtos as $dto) {
            if ($dto instanceof EstacaoCemadenDTO) {
                $estacoes[] = $dto;
            } else {
                $leituras[] = $dto;
            }

            if (count($estacoes) >= self::CHUNK) {
                $total += $this->gravarEstacoes($estacoes);
                $estacoes = [];
            }

            if (count($leituras) >= self::CHUNK) {
                $total += $this->gravarLeituras($leituras, $ingestaoId);
                $leituras = [];
            }
        }

        if ($estacoes !== []) {
            $total += $this->gravarEstacoes($estacoes);
        }

        if ($leituras !== []) {
            $total += $this->gravarLeituras($leituras, $ingestaoId);
        }

        return $total;
    }

    public function totalLeituras(): int
    {
        return (int) DB::scalar('SELECT count(*) FROM silver.leituras_cemaden');
    }

    /**
     * SQL cru porque geom exige ST_SetSRID(ST_MakePoint(...)), que o upsert()
     * do Eloquent nao expressa.
     *
     * @param list<EstacaoCemadenDTO> $lote
     */
    private function gravarEstacoes(array $lote): int
    {
        $lote = $this->deduplicar($lote, static fn (EstacaoCemadenDTO $d): string => $d->codigo);

        $placeholders = [];
        $bindings = [];

        foreach ($lote as $dto) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), now(), now())';

            array_push(
                $bindings,
                $dto->codigo,
                $dto->idExterno,
                $dto->nome,
                $dto->municipio,
                $dto->codigoIbge,
                $dto->uf,
                $dto->tipo,
                $dto->rede,
                $dto->longitude,    // PostGIS espera X (longitude) antes de Y
                $dto->latitude,
            );
        }

        DB::statement(
            'INSERT INTO silver.estacoes_cemaden
                (codigo, id_externo, nome, municipio, codigo_ibge, uf, tipo, rede, geom, created_at, updated_at)
             VALUES ' . implode(', ', $placeholders) . '
             ON CONFLICT (codigo) DO UPDATE SET
                id_externo  = EXCLUDED.id_externo,
                nome        = EXCLUDED.nome,
                municipio   = EXCLUDED.municipio,
                codigo_ibge = EXCLUDED.codigo_ibge,
                uf          = EXCLUDED.uf,
                tipo        = EXCLUDED.tipo,
                rede        = EXCLUDED.rede,
                geom        = EXCLUDED.geom,
                updated_at  = now()',
            $bindings
        );

        return count($lote);
    }

    /**
     * Remove duplicata de chave DENTRO do lote, mantendo a ultima ocorrencia.
     *
     * Sem isso o Postgres recusa o statement inteiro com "ON CONFLICT DO UPDATE
     * command cannot affect row a second time": o ON CONFLICT resolve conflito
     * com linha JA existente na tabela, nao entre linhas propostas no mesmo
     * INSERT.
     *
     * @template T of object
     * @param list<T> $lote
     * @param callable(T): string $chave
     * @return list<T>
     */
    private function deduplicar(array $lote, callable $chave): array
    {
        $porChave = [];

        foreach ($lote as $dto) {
            $porChave[$chave($dto)] = $dto;
        }

        return array_values($porChave);
    }

    /** @param list<LeituraCemadenDTO> $lote */
    private function gravarLeituras(array $lote, ?int $ingestaoId): int
    {
        $lote = $this->deduplicar(
            $lote,
            static fn (LeituraCemadenDTO $d): string => $d->codigoEstacao . '|' . $d->medidoEm->utc()->toIso8601String()
        );

        $placeholders = [];
        $bindings = [];

        foreach ($lote as $dto) {
            $placeholders[] = '(?, ?, ?, ?, now(), now())';

            array_push(
                $bindings,
                $dto->codigoEstacao,
                $dto->medidoEm->toIso8601String(),
                $dto->acumulado24h,
                $ingestaoId,
            );
        }

        DB::statement(
            'INSERT INTO silver.leituras_cemaden
                (codigo_estacao, medido_em, acumulado_24h, ingestao_id, created_at, updated_at)
             VALUES ' . implode(', ', $placeholders) . '
             ON CONFLICT (codigo_estacao, medido_em) DO UPDATE SET
                acumulado_24h = EXCLUDED.acumulado_24h,
                ingestao_id   = EXCLUDED.ingestao_id,
                updated_at    = now()',
            $bindings
        );

        return count($lote);
    }
}
