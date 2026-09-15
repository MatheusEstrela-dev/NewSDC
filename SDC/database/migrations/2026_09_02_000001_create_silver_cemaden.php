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

        // Dimensao propria em vez de reaproveitar estacoes_meteorologicas: a
        // rede do CEMADEN tem atributos que o INMET nao tem (codigo_ibge,
        // id_externo, tipo de rede) e, principalmente, sao 830 estacoes contra
        // 60. Misturar as duas redes numa tabela so faria o join do
        // gold.inmet_mapa varrer 14x mais linhas para nada.
        //
        // A grande diferenca em relacao ao INMET: o feed do CEMADEN traz
        // codibge e coordenada em TODA estacao, entao o municipio vem do codigo
        // IBGE e nao do centroide mais proximo. E resolucao exata, nao chute.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.estacoes_cemaden (
                id           bigserial PRIMARY KEY,
                codigo       varchar(32)  NOT NULL,
                id_externo   integer      NULL,
                nome         varchar(255) NOT NULL,
                municipio    varchar(255) NOT NULL,
                codigo_ibge  integer      NULL,
                uf           varchar(2)   NOT NULL,
                tipo         varchar(40)  NOT NULL,
                rede         varchar(160) NULL,
                geom         geometry(Point, 4326) NOT NULL,
                created_at   timestamptz  NOT NULL DEFAULT now(),
                updated_at   timestamptz  NOT NULL DEFAULT now(),
                CONSTRAINT uq_silver_estacoes_cemaden UNIQUE (codigo)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_estacoes_cemaden_geom ON silver.estacoes_cemaden USING GIST (geom)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_estacoes_cemaden_ibge ON silver.estacoes_cemaden (codigo_ibge)');

        // Fato puro, mesma razao de silver.leituras_inmet: nome e coordenada
        // vivem na dimensao.
        //
        // medido_em vem do campo "atualizado" do feed, que e GLOBAL para todas
        // as estacoes do snapshot e avanca de ~10 em 10 minutos. E isso que faz
        // a tela andar em 16:10, 16:20 -- diferente do INMET, cujo HR_MEDICAO e
        // horario e so muda em 16:00, 17:00.
        //
        // acumulado_24h e chuva acumulada em janela movel de 24h: e a unica
        // janela que o CEMADEN publica (311_1, 311_3, 311_6 e 311_12 respondem
        // 404). Guardar a serie de snapshots e o que permite derivar 1h e 3h
        // por diferenca depois, sem depender de endpoint que nao existe.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS silver.leituras_cemaden (
                id             bigserial PRIMARY KEY,
                codigo_estacao varchar(32)  NOT NULL,
                medido_em      timestamptz  NOT NULL,
                acumulado_24h  numeric(8,2) NULL,
                ingestao_id    bigint       NULL REFERENCES bronze.ingestao_bruta (id) ON DELETE SET NULL,
                created_at     timestamptz  NOT NULL DEFAULT now(),
                updated_at     timestamptz  NOT NULL DEFAULT now(),
                CONSTRAINT uq_silver_leituras_cemaden UNIQUE (codigo_estacao, medido_em)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_leituras_cemaden_medido ON silver.leituras_cemaden (medido_em DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_silver_leituras_cemaden_estacao ON silver.leituras_cemaden (codigo_estacao, medido_em DESC)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS silver.leituras_cemaden');
        DB::statement('DROP TABLE IF EXISTS silver.estacoes_cemaden');
    }
};
