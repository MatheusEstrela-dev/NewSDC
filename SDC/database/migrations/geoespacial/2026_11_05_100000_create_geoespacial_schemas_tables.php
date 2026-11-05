<?php

declare(strict_types=1);

use App\Modules\Medalhao\Database\EsquemaGeo;
use App\Support\Database\Geo\DominioGeo;
use App\Support\Database\GuardaDeBancoDedicado;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DDL completa e final do banco sdc_geoespacial, numa migration so (matviews
 * dependem das tabelas e as funcoes de particao das leituras: nada precisa vir
 * antes ou depois, entao separar so espalharia o mesmo DDL).
 *
 * Os mesmos nomes de schema e tabela existem no sdc (migration de fase 1), o
 * que deixa o codigo igual com DB_GEO_CONNECTION ligada ou desligada. A unica
 * diferenca e que aqui as leituras silver sao particionadas por mes, pois e
 * por particao que a retencao de 12 meses descarta dado velho.
 *
 * Aplica so na conexao `pgsql_geo`, via entrypoint:
 *   php artisan migrate --database=pgsql_geo --path=database/migrations/geoespacial --force
 */
return new class extends Migration
{
    protected $connection = 'pgsql_geo';


    /** Meses futuros mantidos prontos; a rotina diaria de retencao repoe. */
    private const MESES_A_FRENTE = 2;

    public function up(): void
    {
        $db = DB::connection($this->connection);

        if ($db->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino($db);

        $this->executar($db, array_map(
            static fn (string $schema): string => "CREATE SCHEMA IF NOT EXISTS {$schema}",
            EsquemaGeo::schemas(),
        ));

        $this->executar($db, EsquemaGeo::referencia());

        foreach (DominioGeo::cases() as $dominio) {
            $this->executar($db, EsquemaGeo::ingestao($dominio));
            $this->executar($db, EsquemaGeo::bronze($dominio));
        }

        $this->executar($db, $this->silverSismo());
        $this->executar($db, $this->silverMeteorologia());
        $this->executar($db, $this->funcoesDeParticao());
        $this->executar($db, $this->silverCamadas());

        // As leituras nascem sem dado; as particoes do mes corrente e dos
        // proximos ja ficam prontas para a primeira ingestao.
        $db->statement(sprintf(
            'SELECT %s.garantir_particoes(%d)',
            DominioGeo::Meteorologia->silver(),
            self::MESES_A_FRENTE,
        ));

        $this->executar($db, EsquemaGeo::criarMatviews());
    }

    public function down(): void
    {
        $db = DB::connection($this->connection);

        if ($db->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino($db);

        foreach (EsquemaGeo::schemas() as $schema) {
            $db->statement("DROP SCHEMA IF EXISTS {$schema} CASCADE");
        }
    }

    /** @param list<string> $statements */
    private function executar(Connection $db, array $statements): void
    {
        foreach ($statements as $statement) {
            $db->statement($statement);
        }
    }

    /** @return list<string> */
    private function silverSismo(): array
    {
        $d = DominioGeo::Sismo;
        $silver = $d->silver();
        $bronze = $d->tabela('bronze', 'ingestao_bruta');

        return [
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$silver}.sismos (
                    id               bigserial PRIMARY KEY,
                    fonte            varchar(64)  NOT NULL,
                    evento_id        varchar(64)  NOT NULL,
                    origem_utc       timestamptz  NOT NULL,
                    geom             geometry(Point, 4326) NOT NULL,
                    profundidade_km  numeric(8,3) NULL,
                    magnitude        numeric(5,3) NULL,
                    escala_magnitude varchar(16)  NULL,
                    modo             varchar(16)  NULL,
                    regiao           text         NULL,
                    tipo_evento      varchar(32)  NULL,
                    autor            varchar(64)  NULL,
                    ingestao_id      bigint       NULL REFERENCES {$bronze} (id) ON DELETE SET NULL,
                    created_at       timestamptz  NOT NULL DEFAULT now(),
                    updated_at       timestamptz  NOT NULL DEFAULT now(),
                    CONSTRAINT uq_silver_sismos_fonte_evento UNIQUE (fonte, evento_id)
                )
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_silver_sismos_geom ON {$silver}.sismos USING GIST (geom)",
            "CREATE INDEX IF NOT EXISTS idx_silver_sismos_origem ON {$silver}.sismos (origem_utc DESC)",
        ];
    }

    /**
     * Estacoes (dimensao) e leituras (fato). As leituras sao particionadas por
     * mes em medido_em: PK e UNIQUE incluem a chave de particao, o que mantem o
     * ON CONFLICT (codigo_estacao, medido_em) do upsert funcionando.
     *
     * @return list<string>
     */
    private function silverMeteorologia(): array
    {
        $d = DominioGeo::Meteorologia;
        $silver = $d->silver();
        $bronze = $d->tabela('bronze', 'ingestao_bruta');

        return [
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$silver}.estacoes_inmet (
                    id         bigserial PRIMARY KEY,
                    codigo     varchar(10)   NOT NULL,
                    nome       varchar(255)  NOT NULL,
                    municipio  varchar(255)  NOT NULL,
                    uf         varchar(2)    NOT NULL,
                    latitude   numeric(10,7) NOT NULL,
                    longitude  numeric(10,7) NOT NULL,
                    altitude   numeric(8,2)  NULL,
                    tipo       varchar(255)  NOT NULL DEFAULT 'automatica',
                    status     varchar(255)  NOT NULL DEFAULT 'ativo',
                    created_at timestamp(0)  NULL,
                    updated_at timestamp(0)  NULL,
                    deleted_at timestamp(0)  NULL,
                    geom       geometry(Point, 4326) NULL,
                    situacao   varchar(32)   NULL,
                    CONSTRAINT estacoes_inmet_codigo_unique UNIQUE (codigo)
                )
            SQL,
            "CREATE INDEX IF NOT EXISTS estacoes_inmet_uf_index ON {$silver}.estacoes_inmet (uf)",
            "CREATE INDEX IF NOT EXISTS idx_estacoes_inmet_geom ON {$silver}.estacoes_inmet USING GIST (geom)",
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$silver}.estacoes_cemaden (
                    id          bigserial PRIMARY KEY,
                    codigo      varchar(32)  NOT NULL,
                    id_externo  integer      NULL,
                    nome        varchar(255) NOT NULL,
                    municipio   varchar(255) NOT NULL,
                    codigo_ibge integer      NULL,
                    uf          varchar(2)   NOT NULL,
                    tipo        varchar(40)  NOT NULL,
                    rede        varchar(160) NULL,
                    geom        geometry(Point, 4326) NOT NULL,
                    created_at  timestamptz  NOT NULL DEFAULT now(),
                    updated_at  timestamptz  NOT NULL DEFAULT now(),
                    CONSTRAINT uq_silver_estacoes_cemaden UNIQUE (codigo)
                )
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_silver_estacoes_cemaden_geom ON {$silver}.estacoes_cemaden USING GIST (geom)",
            "CREATE INDEX IF NOT EXISTS idx_silver_estacoes_cemaden_ibge ON {$silver}.estacoes_cemaden (codigo_ibge)",
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$silver}.leituras_inmet (
                    id               bigserial,
                    codigo_estacao   varchar(16)  NOT NULL,
                    medido_em        timestamptz  NOT NULL,
                    temperatura      numeric(6,2) NULL,
                    umidade          numeric(6,2) NULL,
                    precipitacao     numeric(8,2) NULL,
                    velocidade_vento numeric(6,2) NULL,
                    pressao          numeric(8,2) NULL,
                    ingestao_id      bigint       NULL REFERENCES {$bronze} (id) ON DELETE SET NULL,
                    created_at       timestamptz  NOT NULL DEFAULT now(),
                    updated_at       timestamptz  NOT NULL DEFAULT now(),
                    PRIMARY KEY (id, medido_em),
                    CONSTRAINT uq_silver_leituras_inmet UNIQUE (codigo_estacao, medido_em)
                ) PARTITION BY RANGE (medido_em)
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_silver_leituras_inmet_medido ON {$silver}.leituras_inmet (medido_em DESC)",
            "CREATE INDEX IF NOT EXISTS idx_silver_leituras_inmet_estacao ON {$silver}.leituras_inmet (codigo_estacao, medido_em DESC)",
            // O bronze apaga payload vencido; sem indice, cada DELETE varreria a leitura inteira.
            "CREATE INDEX IF NOT EXISTS idx_leituras_inmet_ingestao ON {$silver}.leituras_inmet (ingestao_id) WHERE ingestao_id IS NOT NULL",
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$silver}.leituras_cemaden (
                    id             bigserial,
                    codigo_estacao varchar(32)  NOT NULL,
                    medido_em      timestamptz  NOT NULL,
                    acumulado_24h  numeric(8,2) NULL,
                    ingestao_id    bigint       NULL REFERENCES {$bronze} (id) ON DELETE SET NULL,
                    created_at     timestamptz  NOT NULL DEFAULT now(),
                    updated_at     timestamptz  NOT NULL DEFAULT now(),
                    PRIMARY KEY (id, medido_em),
                    CONSTRAINT uq_silver_leituras_cemaden UNIQUE (codigo_estacao, medido_em)
                ) PARTITION BY RANGE (medido_em)
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_silver_leituras_cemaden_medido ON {$silver}.leituras_cemaden (medido_em DESC)",
            "CREATE INDEX IF NOT EXISTS idx_silver_leituras_cemaden_estacao ON {$silver}.leituras_cemaden (codigo_estacao, medido_em DESC)",
            "CREATE INDEX IF NOT EXISTS idx_leituras_cemaden_ingestao ON {$silver}.leituras_cemaden (ingestao_id) WHERE ingestao_id IS NOT NULL",
        ];
    }

    /**
     * criar_particao_mensal cria uma particao (idempotente) e garantir_particoes
     * repoe o mes corrente mais os proximos para as duas tabelas. Limites em UTC
     * para o nome do mes valer igual em qualquer timezone de sessao.
     * A particao DEFAULT segura linha fora de qualquer mes, em vez de recusar o
     * insert e perder a leitura.
     *
     * @return list<string>
     */
    private function funcoesDeParticao(): array
    {
        $s = DominioGeo::Meteorologia->silver();

        return [
            <<<SQL
                CREATE OR REPLACE FUNCTION {$s}.criar_particao_mensal(p_tabela text, p_mes date)
                RETURNS text LANGUAGE plpgsql AS \$fn\$
                DECLARE
                    v_inicio date := date_trunc('month', p_mes)::date;
                    v_fim    date := (date_trunc('month', p_mes) + interval '1 month')::date;
                    v_nome   text := format('%s_%s', p_tabela, to_char(v_inicio, 'YYYY_MM'));
                BEGIN
                    IF to_regclass(format('{$s}.%I', v_nome)) IS NULL THEN
                        EXECUTE format(
                            'CREATE TABLE {$s}.%I PARTITION OF {$s}.%I FOR VALUES FROM (%L) TO (%L)',
                            v_nome, p_tabela,
                            v_inicio::timestamp AT TIME ZONE 'UTC',
                            v_fim::timestamp AT TIME ZONE 'UTC'
                        );
                    END IF;
                    RETURN v_nome;
                END
                \$fn\$
            SQL,
            <<<SQL
                CREATE OR REPLACE FUNCTION {$s}.garantir_particoes(p_a_frente integer DEFAULT 2)
                RETURNS void LANGUAGE plpgsql AS \$fn\$
                DECLARE
                    v_tabela text;
                    v_i      integer;
                BEGIN
                    FOREACH v_tabela IN ARRAY ARRAY['leituras_inmet', 'leituras_cemaden'] LOOP
                        IF to_regclass(format('{$s}.%I', v_tabela || '_default')) IS NULL THEN
                            EXECUTE format('CREATE TABLE {$s}.%I PARTITION OF {$s}.%I DEFAULT', v_tabela || '_default', v_tabela);
                        END IF;
                        FOR v_i IN 0..p_a_frente LOOP
                            PERFORM {$s}.criar_particao_mensal(v_tabela, (date_trunc('month', now()) + make_interval(months => v_i))::date);
                        END LOOP;
                    END LOOP;
                END
                \$fn\$
            SQL,
        ];
    }

    /** @return list<string> */
    private function silverCamadas(): array
    {
        $d = DominioGeo::Camadas;
        $silver = $d->silver();
        $bronze = $d->tabela('bronze', 'ingestao_bruta');

        // enviado_por, revisado_por, arquivado_por e municipio_id apontam para
        // users e municipios do sdc: sem FK, so indice (o nome vem de consulta
        // posterior; municipio_id tem copia em referencia.municipios).
        return [
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$silver}.geo_camadas (
                    id                  bigserial PRIMARY KEY,
                    dominio             varchar(20)  NOT NULL,
                    nome                varchar(255) NOT NULL,
                    arquivo_nome        varchar(255) NOT NULL,
                    emitido_em          date         NULL,
                    valido_ate          date         NULL,
                    nivel               varchar(40)  NULL,
                    hash_arquivo        char(64)     NOT NULL,
                    ingestao_id         bigint       NULL REFERENCES {$bronze} (id) ON DELETE SET NULL,
                    created_at          timestamptz  NOT NULL DEFAULT now(),
                    updated_at          timestamptz  NOT NULL DEFAULT now(),
                    origem              varchar(12)  NOT NULL DEFAULT 'estadual',
                    municipio_id        bigint       NULL,
                    orgao_id            bigint       NULL,
                    enviado_por         bigint       NULL,
                    status              varchar(12)  NOT NULL DEFAULT 'aprovada',
                    revisado_por        bigint       NULL,
                    revisado_em         timestamptz  NULL,
                    motivo_recusa       text         NULL,
                    arquivo_caminho     varchar(500) NULL,
                    arquivado_por       bigint       NULL,
                    arquivado_em        timestamptz  NULL,
                    motivo_arquivamento text         NULL,
                    CONSTRAINT uq_silver_geo_camadas_hash UNIQUE (hash_arquivo),
                    CONSTRAINT ck_silver_geo_camadas_origem CHECK (origem IN ('estadual', 'municipal')),
                    CONSTRAINT ck_silver_geo_camadas_status CHECK (status IN ('pendente', 'aprovada', 'recusada', 'arquivada')),
                    CONSTRAINT ck_silver_geo_camadas_municipal CHECK (origem <> 'municipal' OR municipio_id IS NOT NULL)
                )
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_dominio ON {$silver}.geo_camadas (dominio, emitido_em DESC)",
            "CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_municipio ON {$silver}.geo_camadas (municipio_id, status)",
            "CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_status ON {$silver}.geo_camadas (origem, status)",
            "CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_validade ON {$silver}.geo_camadas (status, valido_ate)",
            "CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_enviado_por ON {$silver}.geo_camadas (enviado_por)",
            "CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_revisado_por ON {$silver}.geo_camadas (revisado_por)",
            "CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_arquivado_por ON {$silver}.geo_camadas (arquivado_por)",
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$silver}.geo_feicoes (
                    id           bigserial PRIMARY KEY,
                    camada_id    bigint       NOT NULL REFERENCES {$silver}.geo_camadas (id) ON DELETE CASCADE,
                    nome         varchar(255) NULL,
                    propriedades jsonb        NOT NULL DEFAULT '{}'::jsonb,
                    geom         geometry(Geometry, 4326) NOT NULL,
                    created_at   timestamptz  NOT NULL DEFAULT now(),
                    updated_at   timestamptz  NOT NULL DEFAULT now()
                )
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_silver_geo_feicoes_camada ON {$silver}.geo_feicoes (camada_id)",
            "CREATE INDEX IF NOT EXISTS idx_silver_geo_feicoes_geom ON {$silver}.geo_feicoes USING GIST (geom)",
        ];
    }

    private function validarDestino(Connection $db): void
    {
        GuardaDeBancoDedicado::validar($db, 'geoespacial');
    }
};
