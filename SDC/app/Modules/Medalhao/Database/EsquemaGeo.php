<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Database;

use App\Support\Database\Geo\DominioGeo;

/**
 * DDL do geoespacial compartilhado entre a migration de fase 1 (no sdc) e as
 * migrations do banco dedicado: o mesmo SQL cria as mesmas estruturas nos dois
 * lugares, entao o codigo roda igual com a chave de conexao ligada ou nao.
 *
 * So devolve SQL: quem executa (e em qual conexao) e a migration.
 */
final class EsquemaGeo
{
    /** Janela do mapa: so leitura recente interessa, o historico fica na silver. */
    public const JANELA_MAPA_HORAS = 48;

    /** Fonte do bronze -> dominio dono do payload. */
    public const FONTES = [
        'inmet-api' => DominioGeo::Meteorologia,
        'cemaden-pluviometria' => DominioGeo::Meteorologia,
        'usp-fdsn' => DominioGeo::Sismo,
        'unb-obsis' => DominioGeo::Sismo,
        'geo-upload' => DominioGeo::Camadas,
    ];

    /**
     * @return list<string> todos os schemas do geoespacial
     */
    public static function schemas(): array
    {
        $schemas = [DominioGeo::REFERENCIA];

        foreach (DominioGeo::cases() as $dominio) {
            array_push($schemas, ...$dominio->schemas());
        }

        return $schemas;
    }

    /**
     * @return list<string> fontes que pertencem ao dominio
     */
    public static function fontesDe(DominioGeo $dominio): array
    {
        return array_keys(array_filter(self::FONTES, static fn (DominioGeo $d): bool => $d === $dominio));
    }

    /**
     * Controle da coleta: um registro por execucao e um cursor por fonte.
     *
     * @return list<string>
     */
    public static function ingestao(DominioGeo $dominio): array
    {
        $s = $dominio->ingestao();
        $d = $dominio->value;

        return [
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$s}.execucoes (
                    id          bigserial PRIMARY KEY,
                    fonte       varchar(64)  NOT NULL,
                    inicio      timestamptz  NOT NULL DEFAULT now(),
                    fim         timestamptz  NULL,
                    status      varchar(16)  NOT NULL DEFAULT 'executando',
                    registros   integer      NULL,
                    erro        text         NULL,
                    hash        char(64)     NULL,
                    CONSTRAINT ck_{$d}_execucoes_status
                        CHECK (status IN ('executando', 'sucesso', 'falha', 'sem_mudanca'))
                )
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_{$d}_execucoes_fonte ON {$s}.execucoes (fonte, inicio DESC)",
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$s}.cursores (
                    fonte         varchar(64) PRIMARY KEY,
                    cursor        text        NULL,
                    atualizado_em timestamptz NOT NULL DEFAULT now()
                )
            SQL,
        ];
    }

    /**
     * Payload bruto do dominio, com o mesmo formato do antigo bronze.ingestao_bruta.
     *
     * @return list<string>
     */
    public static function bronze(DominioGeo $dominio): array
    {
        $s = $dominio->bronze();
        $d = $dominio->value;

        return [
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$s}.ingestao_bruta (
                    id             bigserial PRIMARY KEY,
                    fonte          varchar(64) NOT NULL,
                    conteudo_bruto text        NOT NULL,
                    formato        varchar(32) NOT NULL,
                    hash_conteudo  char(64)    NOT NULL,
                    meta           jsonb       NOT NULL DEFAULT '{}'::jsonb,
                    coletado_em    timestamptz NOT NULL DEFAULT now(),
                    processado_em  timestamptz NULL,
                    verificado_em  timestamptz NULL
                )
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_{$d}_bronze_fonte_hash ON {$s}.ingestao_bruta (fonte, hash_conteudo)",
            "CREATE INDEX IF NOT EXISTS idx_{$d}_bronze_fonte_coletado ON {$s}.ingestao_bruta (fonte, coletado_em DESC)",
            "CREATE INDEX IF NOT EXISTS idx_{$d}_bronze_nao_processado ON {$s}.ingestao_bruta (fonte) WHERE processado_em IS NULL",
            "CREATE INDEX IF NOT EXISTS idx_{$d}_bronze_fonte_verificado ON {$s}.ingestao_bruta (fonte, verificado_em DESC)",
        ];
    }

    /**
     * Copia local dos municipios do sdc. geom e gerada para o sincronizador so
     * enviar latitude/longitude.
     *
     * @return list<string>
     */
    public static function referencia(): array
    {
        $r = DominioGeo::REFERENCIA;

        return [
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$r}.municipios (
                    id            bigint        PRIMARY KEY,
                    codigo_ibge   varchar(7)    NOT NULL,
                    nome          varchar(255)  NOT NULL,
                    uf            varchar(2)    NOT NULL,
                    latitude      numeric(10,7) NULL,
                    longitude     numeric(10,7) NULL,
                    geom          geometry(Point, 4326) GENERATED ALWAYS AS (
                        CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL
                             THEN ST_SetSRID(ST_MakePoint(longitude::double precision, latitude::double precision), 4326)
                        END
                    ) STORED,
                    atualizado_em timestamptz   NOT NULL DEFAULT now(),
                    CONSTRAINT uq_referencia_municipios_ibge UNIQUE (codigo_ibge)
                )
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_referencia_municipios_geom ON {$r}.municipios USING GIST (geom)",
            "CREATE INDEX IF NOT EXISTS idx_referencia_municipios_uf_nome ON {$r}.municipios (uf, nome)",
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$r}.sincronizacao (
                    tabela          varchar(64) PRIMARY KEY,
                    sincronizado_em timestamptz NOT NULL DEFAULT now(),
                    registros       integer     NOT NULL DEFAULT 0,
                    hash            char(32)    NULL
                )
            SQL,
        ];
    }

    /**
     * Matviews gold, em ordem de dependencia. Os mapas de estacao mostram so a
     * janela recente (JANELA_MAPA_HORAS); sem ela o DISTINCT ON relia o
     * historico inteiro a cada refresh.
     *
     * @return array<string, array{schema: string, sql: string, indices: list<string>}>
     */
    public static function matviews(): array
    {
        $met = DominioGeo::Meteorologia;
        $sis = DominioGeo::Sismo;
        $cam = DominioGeo::Camadas;
        $ref = DominioGeo::REFERENCIA;
        $horas = self::JANELA_MAPA_HORAS;
        $dias = (int) config('medalhao.sismos.janela_mapa_dias', 90);

        // Faixas LHASA_RIO adaptadas para MG; classificar no banco evita agregar no PHP.
        $classe = static fn (string $coluna): string => <<<SQL
            CASE
                WHEN {$coluna} IS NULL THEN 'desconhecido'
                WHEN {$coluna} =   0   THEN 'sem_chuva'
                WHEN {$coluna} <   5   THEN 'muito_fraca'
                WHEN {$coluna} <  15   THEN 'fraca'
                WHEN {$coluna} <  35   THEN 'moderada'
                WHEN {$coluna} <  60   THEN 'forte'
                WHEN {$coluna} < 100   THEN 'muito_forte'
                WHEN {$coluna} < 140   THEN 'intensa'
                ELSE 'extrema'
            END
        SQL;

        return [
            'sismos_mapa' => [
                'schema' => $sis->gold(),
                'sql' => <<<SQL
                    SELECT s.id, s.fonte, s.evento_id, s.origem_utc,
                           ST_Y(s.geom) AS latitude, ST_X(s.geom) AS longitude, s.geom,
                           s.magnitude, s.escala_magnitude, s.profundidade_km, s.regiao,
                           CASE
                               WHEN s.magnitude IS NULL THEN 'desconhecido'
                               WHEN s.magnitude < 2.0   THEN 'micro'
                               WHEN s.magnitude < 4.0   THEN 'leve'
                               WHEN s.magnitude < 5.0   THEN 'moderado'
                               ELSE 'forte'
                           END AS classe_magnitude
                    FROM {$sis->tabela('silver', 'sismos')} s
                    WHERE s.origem_utc >= now() - INTERVAL '{$dias} days'
                SQL,
                'indices' => [
                    'CREATE UNIQUE INDEX uq_gold_sismos_mapa_id ON {self} (id)',
                    'CREATE INDEX idx_gold_sismos_mapa_geom ON {self} USING GIST (geom)',
                ],
            ],
            'sismos_estatisticas' => [
                'schema' => $sis->gold(),
                'sql' => <<<SQL
                    SELECT 1 AS id, count(*) AS total_eventos,
                           round(avg(magnitude), 2) AS magnitude_media,
                           max(magnitude) AS magnitude_maxima,
                           now() AS ultima_atualizacao
                    FROM {$sis->tabela('gold', 'sismos_mapa')}
                SQL,
                'indices' => ['CREATE UNIQUE INDEX uq_gold_sismos_estatisticas ON {self} (id)'],
            ],
            'inmet_mapa' => [
                'schema' => $met->gold(),
                'sql' => <<<SQL
                    SELECT DISTINCT ON (l.codigo_estacao)
                           l.id, l.codigo_estacao, e.nome AS nome_estacao, e.municipio, e.uf, e.altitude,
                           l.medido_em, ST_Y(e.geom) AS latitude, ST_X(e.geom) AS longitude, e.geom,
                           l.temperatura, l.umidade, l.precipitacao, l.velocidade_vento, l.pressao,
                           {$classe('l.precipitacao')} AS classe_precipitacao
                    FROM {$met->tabela('silver', 'leituras_inmet')} l
                    JOIN {$met->tabela('silver', 'estacoes_inmet')} e ON e.codigo = l.codigo_estacao
                    WHERE e.geom IS NOT NULL
                      AND l.medido_em >= now() - INTERVAL '{$horas} hours'
                    ORDER BY l.codigo_estacao, l.medido_em DESC
                SQL,
                'indices' => [
                    'CREATE UNIQUE INDEX uq_gold_inmet_mapa_id ON {self} (id)',
                    'CREATE INDEX idx_gold_inmet_mapa_geom ON {self} USING GIST (geom)',
                ],
            ],
            'inmet_estatisticas' => [
                'schema' => $met->gold(),
                'sql' => <<<SQL
                    SELECT 1 AS id, count(*) AS total_estacoes,
                           round(avg(precipitacao), 2) AS precipitacao_media,
                           max(precipitacao) AS precipitacao_maxima,
                           count(*) FILTER (WHERE precipitacao > 0) AS estacoes_com_chuva,
                           round(avg(temperatura), 2) AS temperatura_media,
                           max(medido_em) AS ultima_atualizacao
                    FROM {$met->tabela('gold', 'inmet_mapa')}
                SQL,
                'indices' => ['CREATE UNIQUE INDEX uq_gold_inmet_estatisticas ON {self} (id)'],
            ],
            'cemaden_mapa' => [
                'schema' => $met->gold(),
                'sql' => <<<SQL
                    SELECT DISTINCT ON (l.codigo_estacao)
                           l.id, l.codigo_estacao, e.nome AS nome_estacao, e.municipio, e.codigo_ibge,
                           e.uf, e.tipo, l.medido_em, ST_Y(e.geom) AS latitude, ST_X(e.geom) AS longitude,
                           e.geom, l.acumulado_24h,
                           {$classe('l.acumulado_24h')} AS classe_precipitacao
                    FROM {$met->tabela('silver', 'leituras_cemaden')} l
                    JOIN {$met->tabela('silver', 'estacoes_cemaden')} e ON e.codigo = l.codigo_estacao
                    WHERE l.medido_em >= now() - INTERVAL '{$horas} hours'
                    ORDER BY l.codigo_estacao, l.medido_em DESC
                SQL,
                'indices' => [
                    'CREATE UNIQUE INDEX uq_gold_cemaden_mapa_id ON {self} (id)',
                    'CREATE INDEX idx_gold_cemaden_mapa_geom ON {self} USING GIST (geom)',
                ],
            ],
            'cemaden_estatisticas' => [
                'schema' => $met->gold(),
                'sql' => <<<SQL
                    SELECT 1 AS id, count(*) AS total_estacoes,
                           round(avg(acumulado_24h), 2) AS precipitacao_media,
                           max(acumulado_24h) AS precipitacao_maxima,
                           count(*) FILTER (WHERE acumulado_24h > 0) AS estacoes_com_chuva,
                           count(*) FILTER (WHERE acumulado_24h IS NULL) AS estacoes_sem_telemetria,
                           max(medido_em) AS ultima_atualizacao
                    FROM {$met->tabela('gold', 'cemaden_mapa')}
                SQL,
                'indices' => ['CREATE UNIQUE INDEX uq_gold_cemaden_estatisticas ON {self} (id)'],
            ],
            'geo_feicao_mapa' => [
                'schema' => $cam->gold(),
                'sql' => <<<SQL
                    SELECT f.id, f.camada_id, c.dominio, c.nome AS camada_nome, c.nivel, c.emitido_em,
                           c.origem, c.municipio_id, m.nome AS municipio_nome,
                           f.nome AS feicao_nome, f.propriedades,
                           ST_GeometryType(f.geom) AS tipo_geometria,
                           round((ST_Area(f.geom::geography) / 1000000)::numeric, 2) AS area_km2,
                           ST_AsGeoJSON(f.geom)::jsonb AS geojson,
                           f.geom
                    FROM {$cam->tabela('silver', 'geo_feicoes')} f
                    JOIN {$cam->tabela('silver', 'geo_camadas')} c ON c.id = f.camada_id
                    LEFT JOIN {$ref}.municipios m ON m.id = c.municipio_id
                    WHERE c.status = 'aprovada'
                SQL,
                'indices' => [
                    'CREATE UNIQUE INDEX uq_gold_geo_feicao_mapa_id ON {self} (id)',
                    'CREATE INDEX idx_gold_geo_feicao_mapa_camada ON {self} (camada_id)',
                    'CREATE INDEX idx_gold_geo_feicao_mapa_geom ON {self} USING GIST (geom)',
                ],
            ],
            'geo_camada_municipios' => [
                'schema' => $cam->gold(),
                'sql' => <<<SQL
                    SELECT row_number() OVER (ORDER BY c.id, m.nome) AS id,
                           c.id AS camada_id, m.id AS municipio_id, m.nome AS municipio_nome, m.uf
                    FROM {$cam->tabela('silver', 'geo_camadas')} c
                    JOIN {$cam->tabela('silver', 'geo_feicoes')} f ON f.camada_id = c.id
                    JOIN {$ref}.municipios m ON ST_Contains(f.geom, m.geom)
                    WHERE m.geom IS NOT NULL AND c.status = 'aprovada'
                    GROUP BY c.id, m.id, m.nome, m.uf
                SQL,
                'indices' => [
                    'CREATE UNIQUE INDEX uq_gold_geo_camada_municipios ON {self} (id)',
                    'CREATE INDEX idx_gold_geo_camada_municipios_camada ON {self} (camada_id)',
                ],
            ],
        ];
    }

    /**
     * @param list<string>|null $apenas nomes de matview; null = todas
     * @return list<string> CREATE MATERIALIZED VIEW + indices, em ordem
     */
    public static function criarMatviews(?array $apenas = null): array
    {
        $statements = [];

        foreach (self::matviews() as $nome => $def) {
            if ($apenas !== null && ! in_array($nome, $apenas, true)) {
                continue;
            }

            $qualificado = $def['schema'].'.'.$nome;
            $statements[] = "CREATE MATERIALIZED VIEW IF NOT EXISTS {$qualificado} AS {$def['sql']}";

            foreach ($def['indices'] as $indice) {
                // IF NOT EXISTS no indice: reexecucao nao falha.
                $statements[] = (string) preg_replace(
                    '/^(CREATE (?:UNIQUE )?INDEX) /',
                    '$1 IF NOT EXISTS ',
                    str_replace('{self}', $qualificado, $indice),
                );
            }
        }

        return $statements;
    }

    /**
     * @return list<string> nomes de matview na ordem de criacao
     */
    public static function nomesMatviews(): array
    {
        return array_keys(self::matviews());
    }
}
