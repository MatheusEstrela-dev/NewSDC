<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Cabecalho da camada: um registro por arquivo enviado. O dominio e
        // coluna e nao tabela porque o que varia entre hidro, geologico e
        // meteorologico e legenda e vocabulario, nao estrutura.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.geo_camadas (
                id           bigserial PRIMARY KEY,
                dominio      varchar(20)  NOT NULL,
                nome         varchar(255) NOT NULL,
                arquivo_nome varchar(255) NOT NULL,
                emitido_em   date         NULL,
                valido_ate   date         NULL,
                nivel        varchar(40)  NULL,
                hash_arquivo char(64)     NOT NULL,
                ingestao_id  bigint       NULL REFERENCES bronze.ingestao_bruta (id) ON DELETE SET NULL,

                -- Procedencia. O municipio NAO informa municipio_id no
                -- formulario: ele e derivado do usuario autenticado, via
                -- compdec_orgao_user -> compdec_orgaos.municipio_id. Campo em
                -- formulario permitiria o municipio A enviar como B.
                origem       varchar(12)  NOT NULL DEFAULT 'estadual',
                -- RESTRICT e nao SET NULL: com SET NULL, apagar um municipio
                -- que tem camada municipal zeraria municipio_id e a linha
                -- passaria a violar ck_silver_geo_camadas_municipal -- o
                -- DELETE abortaria com erro de check, incompreensivel para
                -- quem so quis remover um municipio.
                municipio_id bigint       NULL REFERENCES municipios (id) ON DELETE RESTRICT,
                orgao_id     bigint       NULL,
                enviado_por  bigint       NULL REFERENCES users (id) ON DELETE SET NULL,

                -- Moderacao. Default 'aprovada' para nao invalidar as camadas
                -- estaduais que ja existem: envio da CEDEC publica direto, e o
                -- envio municipal nasce 'pendente' por regra do controller.
                -- Colocar o default em 'pendente' faria toda camada estadual
                -- existente desaparecer do mapa no momento da migration.
                status        varchar(12) NOT NULL DEFAULT 'aprovada',
                revisado_por  bigint      NULL REFERENCES users (id) ON DELETE SET NULL,
                revisado_em   timestamptz NULL,
                motivo_recusa text        NULL,

                -- Caminho no disco geo_municipal, do arquivo COMO O MUNICIPIO
                -- ENVIOU. O Bronze guarda o KML extraido; este guarda o
                -- documento original, inclusive KMZ compactado, que e o
                -- artefato auditavel.
                arquivo_caminho varchar(500) NULL,

                created_at   timestamptz  NOT NULL DEFAULT now(),
                updated_at   timestamptz  NOT NULL DEFAULT now(),
                CONSTRAINT uq_silver_geo_camadas_hash UNIQUE (hash_arquivo),
                CONSTRAINT ck_silver_geo_camadas_origem CHECK (origem IN ('estadual', 'municipal')),
                CONSTRAINT ck_silver_geo_camadas_status CHECK (status IN ('pendente', 'aprovada', 'recusada')),
                -- Camada municipal sem municipio nao tem como ser revisada nem
                -- atribuida a ninguem.
                CONSTRAINT ck_silver_geo_camadas_municipal CHECK (
                    origem <> 'municipal' OR municipio_id IS NOT NULL
                )
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_dominio ON silver.geo_camadas (dominio, emitido_em DESC)');

        // Os dois recortes que as telas fazem: o mapa filtra por status, e a
        // lista do municipio filtra pelo proprio municipio.
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_status ON silver.geo_camadas (origem, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_camadas_municipio ON silver.geo_camadas (municipio_id, status)');

        // Uma linha por Placemark. geometry(Geometry,4326) e nao MultiPolygon:
        // verificado que um campo unico com um GIST serve poligono, linha e
        // ponto, e hidro traz rio como linha.
        //
        // propriedades jsonb porque ExtendedData varia por fonte. O arquivo de
        // 28/02 nao tem nenhum, mas aviso meteorologico carrega atributos, e sem
        // o jsonb cada fonte nova pediria migration.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.geo_feicoes (
                id           bigserial PRIMARY KEY,
                camada_id    bigint       NOT NULL REFERENCES silver.geo_camadas (id) ON DELETE CASCADE,
                nome         varchar(255) NULL,
                propriedades jsonb        NOT NULL DEFAULT '{}'::jsonb,
                geom         geometry(Geometry, 4326) NOT NULL,
                created_at   timestamptz  NOT NULL DEFAULT now(),
                updated_at   timestamptz  NOT NULL DEFAULT now()
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_feicoes_geom ON silver.geo_feicoes USING GIST (geom)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_geo_feicoes_camada ON silver.geo_feicoes (camada_id)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS silver.geo_feicoes');
        DB::statement('DROP TABLE IF EXISTS silver.geo_camadas');
    }
};
