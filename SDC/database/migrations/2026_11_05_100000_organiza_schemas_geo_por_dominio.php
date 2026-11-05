<?php

declare(strict_types=1);

use App\Modules\Medalhao\Database\EsquemaGeo;
use App\Support\Database\Geo\DominioGeo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Fase 1 do banco geoespacial, dentro do sdc: so metadado (SET SCHEMA, RENAME,
 * FKs, matviews). Nenhuma linha de fato (leituras, sismos, camadas) e copiada;
 * so o bronze, que e dividido por dominio, e os municipios para referencia.
 *
 * Depois desta migration o sdc tem os mesmos schemas do sdc_geoespacial, e o
 * codigo funciona igual com DB_GEO_CONNECTION ligada ou desligada.
 */
return new class extends Migration
{
    /** @var array<string, array{0: DominioGeo, 1: string, 2: string}> origem => [dominio, camada, nome novo] */
    private const TABELAS = [
        'silver.sismos' => [DominioGeo::Sismo, 'silver', 'sismos'],
        'silver.leituras_inmet' => [DominioGeo::Meteorologia, 'silver', 'leituras_inmet'],
        'silver.leituras_cemaden' => [DominioGeo::Meteorologia, 'silver', 'leituras_cemaden'],
        'silver.estacoes_cemaden' => [DominioGeo::Meteorologia, 'silver', 'estacoes_cemaden'],
        'public.estacoes_meteorologicas' => [DominioGeo::Meteorologia, 'silver', 'estacoes_inmet'],
        'silver.geo_camadas' => [DominioGeo::Camadas, 'silver', 'geo_camadas'],
        'silver.geo_feicoes' => [DominioGeo::Camadas, 'silver', 'geo_feicoes'],
    ];

    /** Matviews que so trocam de schema; as demais sao recriadas com a nova definicao. */
    private const MATVIEWS_MOVIDAS = ['sismos_mapa', 'sismos_estatisticas'];

    private const MATVIEWS_ANTIGAS = [
        'geo_camada_municipios', 'geo_feicao_mapa', 'cemaden_estatisticas', 'cemaden_mapa',
        'inmet_estatisticas', 'inmet_mapa',
    ];

    /** Renomes de objetos da estacao INMET que acompanham o rename da tabela. */
    private const RENOMES_ESTACAO = [
        'SEQUENCE' => ['estacoes_meteorologicas_id_seq' => 'estacoes_inmet_id_seq'],
        'INDEX' => [
            'estacoes_meteorologicas_pkey' => 'estacoes_inmet_pkey',
            'estacoes_meteorologicas_codigo_unique' => 'estacoes_inmet_codigo_unique',
            'estacoes_meteorologicas_uf_index' => 'estacoes_inmet_uf_index',
            'idx_estacoes_meteorologicas_geom' => 'idx_estacoes_inmet_geom',
        ],
    ];

    /** Role externa (projeto Hardness) que le as leituras meteorologicas por SELECT. */
    private const LEITOR_EXTERNO = 'hardness_leitor';

    /** @var list<string> colunas de geo_camadas que apontavam para users */
    private const COLUNAS_USUARIO = ['enviado_por', 'revisado_por', 'arquivado_por'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (EsquemaGeo::schemas() as $schema) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
        }

        foreach (DominioGeo::cases() as $dominio) {
            $this->executar(EsquemaGeo::ingestao($dominio));
        }

        // Os GRANTs de tabela acompanham o SET SCHEMA; o USAGE do schema nao.
        $this->concederUso(DominioGeo::Meteorologia->silver());

        // As matviews que dependem do legado caem antes de qualquer move.
        foreach (self::MATVIEWS_ANTIGAS as $nome) {
            DB::statement("DROP MATERIALIZED VIEW IF EXISTS gold.{$nome}");
            $this->dropNoSchemaNovo($nome);
        }

        $this->executar(EsquemaGeo::referencia());
        $this->popularReferencia();

        $this->dividirBronze();

        foreach (self::TABELAS as $de => [$dominio, $camada, $nome]) {
            $this->mover($de, $dominio->{$camada}(), $nome);
        }
        $this->renomearEstacao(self::RENOMES_ESTACAO, DominioGeo::Meteorologia->silver());

        $this->repontarFks();
        $this->removerFksExternas();

        foreach (self::MATVIEWS_MOVIDAS as $nome) {
            $this->moverMatview($nome, DominioGeo::Sismo->gold());
        }

        $this->executar(EsquemaGeo::criarMatviews(array_values(array_diff(
            EsquemaGeo::nomesMatviews(),
            self::MATVIEWS_MOVIDAS,
        ))));

        // So dropa se estiver vazio: DROP sem CASCADE recusa o que sobrou.
        foreach (['gold', 'silver', 'bronze'] as $legado) {
            DB::statement("DROP SCHEMA IF EXISTS {$legado}");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['bronze', 'silver', 'gold'] as $legado) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$legado}");
        }
        $this->concederUso('silver');

        // Ordem inversa: a estatistica depende do mapa.
        foreach (array_reverse(EsquemaGeo::nomesMatviews()) as $nome) {
            if (! in_array($nome, self::MATVIEWS_MOVIDAS, true)) {
                $this->dropNoSchemaNovo($nome);
            }
        }

        foreach (self::MATVIEWS_MOVIDAS as $nome) {
            $this->moverMatview($nome, 'gold');
        }

        $this->juntarBronze();

        $this->renomearEstacao(array_map('array_flip', self::RENOMES_ESTACAO), DominioGeo::Meteorologia->silver());

        foreach (array_reverse(self::TABELAS, true) as $de => [$dominio, $camada, $nome]) {
            [$schemaOrigem, $nomeOrigem] = explode('.', $de);
            $this->mover($dominio->{$camada}().'.'.$nome, $schemaOrigem, $nomeOrigem);
        }

        $this->restaurarFks();

        foreach ($this->matviewsAntigas() as $statement) {
            DB::statement($statement);
        }

        foreach (DominioGeo::cases() as $dominio) {
            DB::statement("DROP SCHEMA IF EXISTS {$dominio->bronze()} CASCADE");
            DB::statement("DROP SCHEMA IF EXISTS {$dominio->ingestao()} CASCADE");
            DB::statement("DROP SCHEMA IF EXISTS {$dominio->silver()}");
            DB::statement("DROP SCHEMA IF EXISTS {$dominio->gold()}");
        }
        DB::statement('DROP SCHEMA IF EXISTS '.DominioGeo::REFERENCIA.' CASCADE');
    }

    private function concederUso(string $schema): void
    {
        $leitor = self::LEITOR_EXTERNO;

        if (DB::selectOne('SELECT 1 AS ok FROM pg_roles WHERE rolname = ?', [$leitor]) !== null) {
            DB::statement("GRANT USAGE ON SCHEMA {$schema} TO {$leitor}");
        }
    }

    /** @param list<string> $statements */
    private function executar(array $statements): void
    {
        foreach ($statements as $statement) {
            DB::statement($statement);
        }
    }

    private function existe(string $qualificado): bool
    {
        return DB::selectOne('SELECT to_regclass(?) IS NOT NULL AS existe', [$qualificado])->existe;
    }

    private function dropNoSchemaNovo(string $nome): void
    {
        foreach (EsquemaGeo::matviews() as $chave => $def) {
            if ($chave === $nome) {
                DB::statement("DROP MATERIALIZED VIEW IF EXISTS {$def['schema']}.{$nome}");
            }
        }
    }

    /** Idempotente: so move se a origem existe e o destino ainda nao. */
    private function mover(string $de, string $schemaDestino, string $nomeDestino): void
    {
        if (! $this->existe($de)) {
            return;
        }

        [$schemaOrigem, $nomeOrigem] = explode('.', $de);

        if ($schemaOrigem !== $schemaDestino) {
            DB::statement("ALTER TABLE {$de} SET SCHEMA {$schemaDestino}");
        }

        if ($nomeOrigem !== $nomeDestino) {
            DB::statement("ALTER TABLE {$schemaDestino}.{$nomeOrigem} RENAME TO {$nomeDestino}");
        }
    }

    private function moverMatview(string $nome, string $schemaDestino): void
    {
        $origens = ['gold', ...array_map(static fn (DominioGeo $d): string => $d->gold(), DominioGeo::cases())];

        foreach ($origens as $origem) {
            if ($origem !== $schemaDestino && $this->existe("{$origem}.{$nome}")) {
                DB::statement("ALTER MATERIALIZED VIEW {$origem}.{$nome} SET SCHEMA {$schemaDestino}");

                return;
            }
        }
    }

    /**
     * @param array<string, array<string, string>> $renomes tipo => [de => para]
     */
    private function renomearEstacao(array $renomes, string $schema): void
    {
        foreach ($renomes as $tipo => $pares) {
            foreach ($pares as $de => $para) {
                if ($this->existe("{$schema}.{$de}") && ! $this->existe("{$schema}.{$para}")) {
                    DB::statement("ALTER {$tipo} {$schema}.{$de} RENAME TO {$para}");
                }
            }
        }
    }

    private function popularReferencia(): void
    {
        $r = DominioGeo::REFERENCIA;

        DB::statement(<<<SQL
            INSERT INTO {$r}.municipios (id, codigo_ibge, nome, uf, latitude, longitude)
            SELECT id, codigo_ibge, nome, uf, latitude, longitude FROM public.municipios
            ON CONFLICT (id) DO UPDATE SET
                codigo_ibge = EXCLUDED.codigo_ibge, nome = EXCLUDED.nome, uf = EXCLUDED.uf,
                latitude = EXCLUDED.latitude, longitude = EXCLUDED.longitude, atualizado_em = now()
        SQL);

        DB::statement(<<<SQL
            INSERT INTO {$r}.sincronizacao (tabela, registros)
            SELECT 'municipios', count(*) FROM {$r}.municipios
            ON CONFLICT (tabela) DO UPDATE SET sincronizado_em = now(), registros = EXCLUDED.registros
        SQL);
    }

    /**
     * Cada dominio fica com o bronze das suas fontes. Os ids sao preservados:
     * as FKs silver -> bronze continuam validas so trocando o alvo.
     */
    private function dividirBronze(): void
    {
        if (! $this->existe('bronze.ingestao_bruta')) {
            return;
        }

        $fontes = array_map(static fn (string $f): string => "'{$f}'", DominioGeo::fontes());
        $orfas = DB::selectOne('SELECT count(*) AS n FROM bronze.ingestao_bruta WHERE fonte NOT IN ('.implode(',', $fontes).')')->n;

        if ((int) $orfas > 0) {
            // Falha alto: dividir sem dono apagaria payload no DROP final.
            throw new RuntimeException("bronze.ingestao_bruta tem {$orfas} linha(s) de fonte sem dominio em DominioGeo::FONTES.");
        }

        $colunas = 'id, fonte, conteudo_bruto, formato, hash_conteudo, meta, coletado_em, processado_em, verificado_em';
        $total = 0;

        foreach (DominioGeo::cases() as $dominio) {
            $this->executar(EsquemaGeo::bronze($dominio));

            $lista = implode(',', array_map(static fn (string $f): string => "'{$f}'", EsquemaGeo::fontesDe($dominio)));
            if ($lista === '') {
                continue;
            }

            $destino = $dominio->tabela('bronze', 'ingestao_bruta');
            $total += DB::affectingStatement(
                "INSERT INTO {$destino} ({$colunas}) SELECT {$colunas} FROM bronze.ingestao_bruta WHERE fonte IN ({$lista}) ON CONFLICT (id) DO NOTHING"
            );
            $this->ajustarSequence($destino);
        }

        $origem = (int) DB::selectOne('SELECT count(*) AS n FROM bronze.ingestao_bruta')->n;
        $existentes = 0;
        foreach (DominioGeo::cases() as $dominio) {
            $existentes += (int) DB::selectOne("SELECT count(*) AS n FROM {$dominio->tabela('bronze', 'ingestao_bruta')}")->n;
        }

        if ($existentes < $origem) {
            throw new RuntimeException("Divisao do bronze perdeu linhas: origem {$origem}, destino {$existentes}.");
        }

        // Solta as FKs que seguram a tabela; o repontamento vem em repontarFks().
        foreach ($this->fksParaBronze() as $fk) {
            DB::statement("ALTER TABLE {$fk->tabela} DROP CONSTRAINT IF EXISTS {$fk->nome}");
        }

        DB::statement('DROP TABLE bronze.ingestao_bruta');
    }

    private function ajustarSequence(string $tabela): void
    {
        DB::statement(<<<SQL
            SELECT setval(pg_get_serial_sequence('{$tabela}', 'id'),
                          COALESCE((SELECT max(id) FROM {$tabela}), 1),
                          (SELECT count(*) > 0 FROM {$tabela}))
        SQL);
    }

    /** @return list<object{tabela: string, nome: string}> */
    private function fksParaBronze(): array
    {
        return DB::select(<<<'SQL'
            SELECT c.conrelid::regclass::text AS tabela, c.conname AS nome
            FROM pg_constraint c
            WHERE c.contype = 'f' AND c.confrelid = to_regclass('bronze.ingestao_bruta')
        SQL);
    }

    /** Tabelas silver e o dominio cujo bronze elas referenciam. */
    private function silverComIngestao(): array
    {
        return [
            [DominioGeo::Sismo, 'sismos'],
            [DominioGeo::Meteorologia, 'leituras_inmet'],
            [DominioGeo::Meteorologia, 'leituras_cemaden'],
            [DominioGeo::Camadas, 'geo_camadas'],
        ];
    }

    private function repontarFks(): void
    {
        foreach ($this->silverComIngestao() as [$dominio, $tabela]) {
            $silver = $dominio->tabela('silver', $tabela);
            $bronze = $dominio->tabela('bronze', 'ingestao_bruta');

            DB::statement("ALTER TABLE {$silver} DROP CONSTRAINT IF EXISTS {$tabela}_ingestao_id_fkey");
            DB::statement("ALTER TABLE {$silver} ADD CONSTRAINT {$tabela}_ingestao_id_fkey FOREIGN KEY (ingestao_id) REFERENCES {$bronze} (id) ON DELETE SET NULL");
        }

        // O bronze apaga payload vencido; sem indice, cada DELETE faria
        // varredura completa da leitura para zerar ingestao_id.
        foreach (['leituras_inmet', 'leituras_cemaden'] as $tabela) {
            DB::statement("CREATE INDEX IF NOT EXISTS idx_{$tabela}_ingestao ON ".DominioGeo::Meteorologia->tabela('silver', $tabela).' (ingestao_id) WHERE ingestao_id IS NOT NULL');
        }
    }

    /**
     * users e municipios ficam em outro banco quando o geo for separado: as
     * colunas viram bigint indexado e o nome e buscado numa segunda consulta.
     */
    private function removerFksExternas(): void
    {
        $camadas = DominioGeo::Camadas->tabela('silver', 'geo_camadas');

        $fks = DB::select(<<<'SQL'
            SELECT c.conname FROM pg_constraint c
            WHERE c.contype = 'f' AND c.conrelid = to_regclass(?)
              AND c.confrelid IN (to_regclass('public.users'), to_regclass('public.municipios'))
        SQL, [$camadas]);

        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE {$camadas} DROP CONSTRAINT {$fk->conname}");
        }

        foreach (self::COLUNAS_USUARIO as $coluna) {
            DB::statement("CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_{$coluna} ON {$camadas} ({$coluna})");
        }
    }

    private function restaurarFks(): void
    {
        $camadas = 'silver.geo_camadas';

        foreach (self::COLUNAS_USUARIO as $coluna) {
            DB::statement("DROP INDEX IF EXISTS silver.idx_silver_geo_camadas_{$coluna}");
            DB::statement("ALTER TABLE {$camadas} DROP CONSTRAINT IF EXISTS geo_camadas_{$coluna}_fkey");
            DB::statement("ALTER TABLE {$camadas} ADD CONSTRAINT geo_camadas_{$coluna}_fkey FOREIGN KEY ({$coluna}) REFERENCES public.users (id) ON DELETE SET NULL");
        }

        DB::statement("ALTER TABLE {$camadas} DROP CONSTRAINT IF EXISTS geo_camadas_municipio_id_foreign");
        DB::statement("ALTER TABLE {$camadas} ADD CONSTRAINT geo_camadas_municipio_id_foreign FOREIGN KEY (municipio_id) REFERENCES public.municipios (id) ON DELETE RESTRICT");

        foreach (['leituras_inmet', 'leituras_cemaden'] as $tabela) {
            DB::statement("DROP INDEX IF EXISTS silver.idx_{$tabela}_ingestao");
        }
    }

    /** Recompõe bronze.ingestao_bruta com o payload de todos os dominios. */
    private function juntarBronze(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS bronze.ingestao_bruta (
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
        SQL);
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_hash ON bronze.ingestao_bruta (fonte, hash_conteudo)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_coletado ON bronze.ingestao_bruta (fonte, coletado_em DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_nao_processado ON bronze.ingestao_bruta (fonte) WHERE processado_em IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bronze_fonte_verificado ON bronze.ingestao_bruta (fonte, verificado_em DESC)');

        $colunas = 'id, fonte, conteudo_bruto, formato, hash_conteudo, meta, coletado_em, processado_em, verificado_em';

        foreach (DominioGeo::cases() as $dominio) {
            $origem = $dominio->tabela('bronze', 'ingestao_bruta');
            if ($this->existe($origem)) {
                DB::statement("INSERT INTO bronze.ingestao_bruta ({$colunas}) SELECT {$colunas} FROM {$origem} ON CONFLICT (id) DO NOTHING");
            }
        }
        $this->ajustarSequence('bronze.ingestao_bruta');

        foreach ($this->silverComIngestao() as [$dominio, $tabela]) {
            $silver = $dominio->tabela('silver', $tabela);
            DB::statement("ALTER TABLE {$silver} DROP CONSTRAINT IF EXISTS {$tabela}_ingestao_id_fkey");
            DB::statement("ALTER TABLE {$silver} ADD CONSTRAINT {$tabela}_ingestao_id_fkey FOREIGN KEY (ingestao_id) REFERENCES bronze.ingestao_bruta (id) ON DELETE SET NULL");
        }
    }

    /**
     * Definicoes originais (antes da janela de 48h e da referencia), para o rollback.
     *
     * @return list<string>
     */
    private function matviewsAntigas(): array
    {
        $classe = static fn (string $c): string => "CASE WHEN {$c} IS NULL THEN 'desconhecido' WHEN {$c} = 0 THEN 'sem_chuva' WHEN {$c} < 5 THEN 'muito_fraca' WHEN {$c} < 15 THEN 'fraca' WHEN {$c} < 35 THEN 'moderada' WHEN {$c} < 60 THEN 'forte' WHEN {$c} < 100 THEN 'muito_forte' WHEN {$c} < 140 THEN 'intensa' ELSE 'extrema' END";

        return [
            'CREATE MATERIALIZED VIEW IF NOT EXISTS gold.inmet_mapa AS SELECT DISTINCT ON (l.codigo_estacao) l.id, l.codigo_estacao, e.nome AS nome_estacao, e.municipio, e.uf, e.altitude, l.medido_em, ST_Y(e.geom) AS latitude, ST_X(e.geom) AS longitude, e.geom, l.temperatura, l.umidade, l.precipitacao, l.velocidade_vento, l.pressao, '
                .$classe('l.precipitacao').' AS classe_precipitacao FROM silver.leituras_inmet l JOIN public.estacoes_meteorologicas e ON e.codigo = l.codigo_estacao WHERE e.geom IS NOT NULL ORDER BY l.codigo_estacao, l.medido_em DESC',
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_inmet_mapa_id ON gold.inmet_mapa (id)',
            'CREATE INDEX IF NOT EXISTS idx_gold_inmet_mapa_geom ON gold.inmet_mapa USING GIST (geom)',
            'CREATE MATERIALIZED VIEW IF NOT EXISTS gold.inmet_estatisticas AS SELECT 1 AS id, count(*) AS total_estacoes, round(avg(precipitacao), 2) AS precipitacao_media, max(precipitacao) AS precipitacao_maxima, count(*) FILTER (WHERE precipitacao > 0) AS estacoes_com_chuva, round(avg(temperatura), 2) AS temperatura_media, max(medido_em) AS ultima_atualizacao FROM gold.inmet_mapa',
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_inmet_estatisticas ON gold.inmet_estatisticas (id)',
            'CREATE MATERIALIZED VIEW IF NOT EXISTS gold.cemaden_mapa AS SELECT DISTINCT ON (l.codigo_estacao) l.id, l.codigo_estacao, e.nome AS nome_estacao, e.municipio, e.codigo_ibge, e.uf, e.tipo, l.medido_em, ST_Y(e.geom) AS latitude, ST_X(e.geom) AS longitude, e.geom, l.acumulado_24h, '
                .$classe('l.acumulado_24h').' AS classe_precipitacao FROM silver.leituras_cemaden l JOIN silver.estacoes_cemaden e ON e.codigo = l.codigo_estacao ORDER BY l.codigo_estacao, l.medido_em DESC',
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_cemaden_mapa_id ON gold.cemaden_mapa (id)',
            'CREATE INDEX IF NOT EXISTS idx_gold_cemaden_mapa_geom ON gold.cemaden_mapa USING GIST (geom)',
            'CREATE MATERIALIZED VIEW IF NOT EXISTS gold.cemaden_estatisticas AS SELECT 1 AS id, count(*) AS total_estacoes, round(avg(acumulado_24h), 2) AS precipitacao_media, max(acumulado_24h) AS precipitacao_maxima, count(*) FILTER (WHERE acumulado_24h > 0) AS estacoes_com_chuva, count(*) FILTER (WHERE acumulado_24h IS NULL) AS estacoes_sem_telemetria, max(medido_em) AS ultima_atualizacao FROM gold.cemaden_mapa',
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_cemaden_estatisticas ON gold.cemaden_estatisticas (id)',
            'CREATE MATERIALIZED VIEW IF NOT EXISTS gold.geo_feicao_mapa AS SELECT f.id, f.camada_id, c.dominio, c.nome AS camada_nome, c.nivel, c.emitido_em, c.origem, c.municipio_id, m.nome AS municipio_nome, f.nome AS feicao_nome, f.propriedades, ST_GeometryType(f.geom) AS tipo_geometria, round((ST_Area(f.geom::geography) / 1000000)::numeric, 2) AS area_km2, ST_AsGeoJSON(f.geom)::jsonb AS geojson, f.geom FROM silver.geo_feicoes f JOIN silver.geo_camadas c ON c.id = f.camada_id LEFT JOIN public.municipios m ON m.id = c.municipio_id WHERE c.status = \'aprovada\'',
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_geo_feicao_mapa_id ON gold.geo_feicao_mapa (id)',
            'CREATE INDEX IF NOT EXISTS idx_gold_geo_feicao_mapa_camada ON gold.geo_feicao_mapa (camada_id)',
            'CREATE INDEX IF NOT EXISTS idx_gold_geo_feicao_mapa_geom ON gold.geo_feicao_mapa USING GIST (geom)',
            'CREATE MATERIALIZED VIEW IF NOT EXISTS gold.geo_camada_municipios AS SELECT row_number() OVER (ORDER BY c.id, m.nome) AS id, c.id AS camada_id, m.id AS municipio_id, m.nome AS municipio_nome, m.uf FROM silver.geo_camadas c JOIN silver.geo_feicoes f ON f.camada_id = c.id JOIN public.municipios m ON ST_Contains(f.geom, ST_SetSRID(ST_MakePoint(m.longitude::double precision, m.latitude::double precision), 4326)) WHERE m.latitude IS NOT NULL AND m.longitude IS NOT NULL AND c.status = \'aprovada\' GROUP BY c.id, m.id, m.nome, m.uf',
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_gold_geo_camada_municipios ON gold.geo_camada_municipios (id)',
            'CREATE INDEX IF NOT EXISTS idx_gold_geo_camada_municipios_camada ON gold.geo_camada_municipios (camada_id)',
        ];
    }
};
