<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Repositories;

use App\Modules\Geoespacial\DTOs\CamadaGeoDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GeoCamadaRepository
{
    /**
     * Contrato exigido pelo kernel: upsertLote(iterable, ?int): int.
     *
     * @param iterable<CamadaGeoDTO> $dtos
     */
    public function upsertLote(iterable $dtos, ?int $ingestaoId = null): int
    {
        $total = 0;

        foreach ($dtos as $dto) {
            $total += $this->gravarCamada($dto, $ingestaoId);
        }

        return $total;
    }

    private function gravarCamada(CamadaGeoDTO $dto, ?int $ingestaoId): int
    {
        // DO NOTHING e nao DO UPDATE: camada e imutavel. O mesmo arquivo
        // reenviado nao deve reescrever nem reimportar feicao -- e por isso que
        // hash_arquivo e unico.
        $id = DB::scalar(
            'INSERT INTO silver.geo_camadas
                (dominio, nome, arquivo_nome, emitido_em, valido_ate, nivel, hash_arquivo, ingestao_id,
                 origem, municipio_id, orgao_id, enviado_por, status, arquivo_caminho, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), now())
             ON CONFLICT (hash_arquivo) DO NOTHING
             RETURNING id',
            [
                $dto->dominio, $dto->nome, $dto->arquivoNome,
                $dto->emitidoEm, $dto->validoAte, $dto->nivel,
                $dto->hashArquivo, $ingestaoId,
                $dto->origem, $dto->municipioId, $dto->orgaoId,
                $dto->enviadoPor, $dto->status, $dto->arquivoCaminho,
            ]
        );

        // null significa conflito: a camada ja existia e nada foi inserido.
        if ($id === null) {
            return 0;
        }

        // Avisa os revisores AQUI, e nao no controller: o NormalizarSilverJob
        // e assincrono, e no momento do upload esta linha ainda nao existia.
        // Este e o ponto em que a camada pendente passa a existir de verdade.
        //
        // Repositorio despachando notificacao e um desvio de camada, aceito
        // conscientemente: a alternativa seria um evento de dominio so para
        // este caso, e o kernel do medalhao -- que chama este upsertLote --
        // nao conhece dominio nenhum de proposito.
        if ($dto->status === 'pendente') {
            app(\App\Modules\Geoespacial\Services\RevisaoDeCamadas::class)
                ->avisarRevisores((int) $id);
        }

        foreach ($dto->feicoes as $indice => $feicao) {
            // Nome de verdade no BANCO, e nao rotulo calculado em cada tela.
            //
            // O KML de alerta traz <name>0</name> em todo Placemark -- que e
            // placeholder do gerador, nao nome -- e o extrator converte isso em
            // null por fidelidade ao arquivo. Mas gravar null deixava a coluna
            // vazia para 19 feicoes, e cada consumidor (tela, export, API)
            // teria de inventar o proprio rotulo, cada um do seu jeito.
            //
            // "Area N" e derivado, nao inventado: e a posicao da feicao DENTRO
            // da camada, que e a unica identificacao que o arquivo permite. Se
            // o KML trouxer nome de verdade, ele vence.
            $nome = $feicao->nome ?? sprintf('Area %d', $indice + 1);

            DB::statement(
                'INSERT INTO silver.geo_feicoes (camada_id, nome, propriedades, geom, created_at, updated_at)
                 VALUES (?, ?, ?::jsonb, ST_MakeValid(ST_Force2D(ST_GeomFromKML(?))), now(), now())',
                [(int) $id, $nome, '{}', $feicao->kmlGeometria]
            );
        }

        return count($dto->feicoes);
    }

    /**
     * Camada ja importada com esta geometria, ou null.
     *
     * Serve para o upload responder na hora em vez de aceitar, enfileirar e
     * deixar o ON CONFLICT recusar em silencio no worker.
     */
    public function camadaDoHash(string $hashArquivo): ?object
    {
        return DB::table('silver.geo_camadas')
            ->select(['id', 'nome', 'emitido_em'])
            ->where('hash_arquivo', $hashArquivo)
            ->first();
    }

    /**
     * As camadas do proprio remetente, para a tela de envio.
     *
     * Casa por municipio OU por quem enviou. Os dois porque a COMPDEC pode ter
     * mais de uma pessoa: o agente que enviou ontem quer ver o envio do colega
     * do mesmo municipio, e o usuario estadual que nao tem municipio ainda
     * precisa ver o que ele mesmo mandou.
     *
     * @return Collection<int, object>
     */
    public function minhasCamadas(?int $municipioId, int $enviadoPor): Collection
    {
        return DB::table('silver.geo_camadas')
            ->select([
                'id', 'nome', 'dominio', 'nivel', 'emitido_em', 'valido_ate',
                'arquivo_nome', 'origem', 'status', 'motivo_recusa',
                'revisado_em', 'created_at',
            ])
            ->where(function ($q) use ($municipioId, $enviadoPor): void {
                $q->where('enviado_por', $enviadoPor);

                if ($municipioId !== null) {
                    $q->orWhere('municipio_id', $municipioId);
                }
            })
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Camadas visiveis para quem esta olhando.
     *
     * A regra acordada: o municipio ve as PROPRIAS em qualquer status --
     * inclusive pendente e recusada, para acompanhar -- e as aprovadas de
     * qualquer origem. Quem revisa ve tudo.
     *
     * Sem este recorte, o municipio A lia as pendentes e recusadas do B,
     * inclusive o texto da recusa. O indice (municipio_id, status) existe
     * exatamente para esta consulta.
     *
     * @return Collection<int, object>
     */
    public function camadas(?int $municipioId = null, bool $veTudo = false): Collection
    {
        $query = DB::table('silver.geo_camadas');

        if (! $veTudo) {
            $query->where(function ($q) use ($municipioId): void {
                $q->where('status', 'aprovada');

                if ($municipioId !== null) {
                    $q->orWhere('municipio_id', $municipioId);
                }
            });
        }

        return $query
            ->select(['id', 'dominio', 'nome', 'arquivo_nome', 'emitido_em', 'valido_ate', 'nivel', 'created_at', 'origem', 'status', 'municipio_id', 'motivo_recusa'])
            ->orderByDesc('emitido_em')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Le a camada Gold. Nenhuma serializacao aqui: a matview ja entrega o
     * GeoJSON pronto.
     *
     * @return Collection<int, object>
     */
    public function mapa(?int $camadaId = null): Collection
    {
        $query = DB::table('gold.geo_feicao_mapa')
            ->select(['id', 'camada_id', 'dominio', 'camada_nome', 'nivel', 'emitido_em', 'feicao_nome', 'tipo_geometria', 'area_km2', 'geojson']);

        if ($camadaId !== null) {
            $query->where('camada_id', $camadaId);
        }

        return $query->orderByDesc('area_km2')->get();
    }

    /**
     * Cruzamento espacial da camada com o dado que o sistema ja tem.
     *
     * ATENCAO: 'municipios' vem de gold.geo_camada_municipios, que cruza por
     * CENTROIDE -- a tabela municipios nao tem geometria de area. O numero e
     * piso, nao total, e a tela precisa dizer isso.
     *
     * @return array{municipios: int, estacoes: int, chuva_media: float, chuva_maxima: float, estacoes_com_leitura: int, altimetria: array{minima: ?float, maxima: ?float, media: ?float, estacoes_com_cota: int, fonte: string}}
     */
    public function cruzamento(int $camadaId): array
    {
        $municipios = (int) DB::scalar(
            'SELECT count(*) FROM gold.geo_camada_municipios WHERE camada_id = ?',
            [$camadaId]
        );

        // As DUAS redes, e nao so o CEMADEN: as 61 do INMET vivem em
        // estacoes_meteorologicas e ficavam de fora da conta. Hoje nenhuma cai
        // nas areas carregadas, entao o numero nao mudava -- mas isso era
        // coincidencia da geometria, nao correcao do codigo.
        //
        // ST_Intersects e nao ST_Contains: estacao exatamente sobre a divisa
        // entra. Errar incluindo e melhor que errar excluindo num alerta.
        $estacoes = (int) DB::scalar(
            'SELECT
                (SELECT count(*) FROM silver.estacoes_cemaden e
                  WHERE EXISTS (SELECT 1 FROM silver.geo_feicoes f
                                 WHERE f.camada_id = ? AND ST_Intersects(f.geom, e.geom)))
              + (SELECT count(*) FROM estacoes_meteorologicas m
                  WHERE m.geom IS NOT NULL
                    AND EXISTS (SELECT 1 FROM silver.geo_feicoes f
                                 WHERE f.camada_id = ? AND ST_Intersects(f.geom, m.geom)))',
            [$camadaId, $camadaId]
        );

        // Chuva das duas redes numa serie so: as duas medem acumulado de 24h
        // com as mesmas faixas, entao separa-las na estatistica diria que
        // choveu duas coisas diferentes na mesma area.
        $chuva = DB::selectOne(
            'SELECT round(avg(mm)::numeric, 2) AS media,
                    max(mm)                    AS maxima,
                    count(*)                   AS com_leitura
               FROM (
                 SELECT g.acumulado_24h::float8 AS mm
                   FROM gold.cemaden_mapa g
                  WHERE g.acumulado_24h IS NOT NULL
                    AND EXISTS (SELECT 1 FROM silver.geo_feicoes f
                                 WHERE f.camada_id = ? AND ST_Intersects(f.geom, g.geom))
                 UNION ALL
                 SELECT i.precipitacao::float8
                   FROM gold.inmet_mapa i
                  WHERE i.precipitacao IS NOT NULL
                    AND EXISTS (SELECT 1 FROM silver.geo_feicoes f
                                 WHERE f.camada_id = ? AND ST_Intersects(f.geom, i.geom))
               ) AS leituras',
            [$camadaId, $camadaId]
        );

        // Relevo da area, hoje pela cota das estacoes que caem dentro dela.
        //
        // E amostragem, nao o relevo do terreno: sao 4 a 61 pontos medidos, e
        // nao o MDE. A pergunta que governa deslizamento -- qual a declividade
        // da encosta -- so o raster responde, com
        // ST_SummaryStats(ST_Clip(rast, geom)) e ST_Slope. A extensao
        // postgis_raster esta disponivel no banco e ainda nao instalada; quando
        // o modulo Hidro entrar, esta consulta ganha as duas fontes e a tela
        // passa a dizer de qual delas o numero veio.
        //
        // So o INMET publica cota (61 de 61); o feed do CEMADEN nao traz.
        $relevo = DB::selectOne(
            'SELECT min(i.altitude)               AS minima,
                    max(i.altitude)               AS maxima,
                    round(avg(i.altitude), 0)     AS media,
                    count(*)                      AS com_cota
               FROM gold.inmet_mapa i
              WHERE i.altitude IS NOT NULL
                AND EXISTS (SELECT 1 FROM silver.geo_feicoes f
                             WHERE f.camada_id = ? AND ST_Intersects(f.geom, i.geom))',
            [$camadaId]
        );

        return [
            'municipios' => $municipios,
            'estacoes' => $estacoes,
            'chuva_media' => (float) ($chuva->media ?? 0),
            'chuva_maxima' => (float) ($chuva->maxima ?? 0),
            'estacoes_com_leitura' => (int) ($chuva->com_leitura ?? 0),
            'altimetria' => [
                'minima' => $relevo?->minima !== null ? (float) $relevo->minima : null,
                'maxima' => $relevo?->maxima !== null ? (float) $relevo->maxima : null,
                'media' => $relevo?->media !== null ? (float) $relevo->media : null,
                'estacoes_com_cota' => (int) ($relevo->com_cota ?? 0),
                // 'estacao' hoje; 'mde' quando o raster entrar. A tela usa isto
                // para nao apresentar amostragem como se fosse o terreno.
                'fonte' => 'estacao',
            ],
        ];
    }
}
