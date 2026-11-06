<?php

declare(strict_types=1);

use App\Support\Database\GuardaDeBancoDedicado;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DDL completa do banco sdc_entrada numa migration so: caixa de entrada
 * assincrona (mensagens particionadas por mes), chaves de idempotencia, mortas
 * e funcoes de particao nascem juntas (regra de consolidar migrations).
 *
 * O banco vive NO CLUSTER do sdc (durabilidade sincrona): a mensagem aceita
 * com 202 nao pode se perder, ao contrario do sdc_powerbi, que e derivado e
 * reconstruivel e fica em instancia propria. Antes a caixa morava no
 * sdc_powerbi; esta migration e o novo ponto unico do DDL dela.
 *
 * Aplica so na conexao `pgsql_entrada`:
 *   php artisan migrate --database=pgsql_entrada --path=database/migrations/entrada --force
 */
return new class extends Migration
{
    protected $connection = 'pgsql_entrada';

    /** Meses futuros mantidos prontos; a rotina de retencao repoe. */
    private const MESES_A_FRENTE = 2;

    public function up(): void
    {
        $db = DB::connection($this->connection);

        if ($db->getDriverName() !== 'pgsql') {
            return;
        }

        GuardaDeBancoDedicado::validar($db, 'entrada');

        $db->statement('CREATE SCHEMA IF NOT EXISTS entrada');
        $this->executar($db, $this->entrada());
        $db->statement(sprintf('SELECT entrada.garantir_particoes(%d)', self::MESES_A_FRENTE));
    }

    public function down(): void
    {
        $db = DB::connection($this->connection);

        if ($db->getDriverName() !== 'pgsql') {
            return;
        }

        GuardaDeBancoDedicado::validar($db, 'entrada');

        $db->statement('DROP SCHEMA IF EXISTS entrada CASCADE');
    }

    /** @param list<string> $statements */
    private function executar(Connection $db, array $statements): void
    {
        foreach ($statements as $statement) {
            $db->statement($statement);
        }
    }

    /**
     * Caixa de entrada. id uuidv7 (PG18) ordena por tempo e mantem o indice
     * da PK compacto; a PK inclui recebido_em por causa da particao.
     *
     * payload e text: vai cifrado (Crypt::encryptString, APP_KEY) porque pode
     * trazer CPF/nome; vira NULL quando a mensagem termina. So entrada.mortas
     * guarda o cifrado (NOT NULL) para reprocessamento manual. rota guarda
     * metodo, caminho e parametros crus da rota (ids), sem dado pessoal.
     * Acesso exclusivo por App\Modules\Integracao\Support\CaixaDeEntrada.
     *
     * @return list<string>
     */
    private function entrada(): array
    {
        return [
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS entrada.mensagens (
                    id                   uuid        NOT NULL DEFAULT uuidv7(),
                    recebido_em          timestamptz NOT NULL DEFAULT now(),
                    fonte                text        NOT NULL,
                    operacao             text        NOT NULL,
                    recurso_id           text        NULL,
                    recurso_chave        text        NULL,
                    token_id             bigint      NULL,
                    user_id              bigint      NULL,
                    ip                   inet        NULL,
                    chave_idempotente    text        NULL,
                    hash_payload         bytea       NULL,
                    payload              text        NULL,
                    rota                 jsonb       NULL,
                    cabecalhos           jsonb       NULL,
                    status               text        NOT NULL DEFAULT 'recebida',
                    tentativas           integer     NOT NULL DEFAULT 0,
                    trava_ate            timestamptz NULL,
                    proxima_tentativa_em timestamptz NULL,
                    erro                 text        NULL,
                    erro_detalhe         jsonb       NULL,
                    resultado            jsonb       NULL,
                    aplicado_em          timestamptz NULL,
                    trace_id             text        NULL,
                    PRIMARY KEY (id, recebido_em),
                    CONSTRAINT ck_entrada_mensagens_status CHECK (
                        status IN ('recebida', 'processando', 'aplicada', 'rejeitada', 'falhou', 'morta')
                    )
                ) PARTITION BY RANGE (recebido_em)
            SQL,
            // Varredura do despachante: so mensagens nao terminais.
            <<<'SQL'
                CREATE INDEX IF NOT EXISTS idx_entrada_mensagens_pendentes
                ON entrada.mensagens (status, proxima_tentativa_em)
                WHERE status IN ('recebida', 'falhou', 'processando')
            SQL,
            'CREATE INDEX IF NOT EXISTS idx_entrada_mensagens_token ON entrada.mensagens (token_id, recebido_em DESC)',
            // Ordem por recurso: so a mais antiga nao terminal de cada chave roda.
            <<<'SQL'
                CREATE INDEX IF NOT EXISTS idx_entrada_mensagens_recurso
                ON entrada.mensagens (recurso_chave, recebido_em)
                WHERE recurso_chave IS NOT NULL AND status IN ('recebida', 'falhou', 'processando')
            SQL,
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS entrada.chaves_idempotentes (
                    fonte        text        NOT NULL,
                    chave        text        NOT NULL,
                    mensagem_id  uuid        NOT NULL,
                    recebido_em  timestamptz NOT NULL,
                    hash_payload bytea       NULL,
                    expira_em    timestamptz NOT NULL,
                    PRIMARY KEY (fonte, chave)
                )
            SQL,
            // chave = escopo do cliente (t<token_id>: ou u<user_id>:) + Idempotency-Key.
            'CREATE INDEX IF NOT EXISTS idx_entrada_chaves_expira ON entrada.chaves_idempotentes (expira_em)',
            // Copia da mensagem que esgotou as tentativas (dead-letter); sem particao, volume baixo.
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS entrada.mortas (
                    id                   uuid        NOT NULL,
                    recebido_em          timestamptz NOT NULL,
                    fonte                text        NOT NULL,
                    operacao             text        NOT NULL,
                    recurso_id           text        NULL,
                    recurso_chave        text        NULL,
                    token_id             bigint      NULL,
                    user_id              bigint      NULL,
                    ip                   inet        NULL,
                    chave_idempotente    text        NULL,
                    hash_payload         bytea       NULL,
                    payload              text        NOT NULL,
                    rota                 jsonb       NULL,
                    cabecalhos           jsonb       NULL,
                    status               text        NOT NULL DEFAULT 'morta',
                    tentativas           integer     NOT NULL DEFAULT 0,
                    trava_ate            timestamptz NULL,
                    proxima_tentativa_em timestamptz NULL,
                    erro                 text        NULL,
                    erro_detalhe         jsonb       NULL,
                    resultado            jsonb       NULL,
                    aplicado_em          timestamptz NULL,
                    trace_id             text        NULL,
                    morta_em             timestamptz NOT NULL DEFAULT now(),
                    PRIMARY KEY (id, recebido_em)
                )
            SQL,
            <<<'SQL'
                CREATE OR REPLACE FUNCTION entrada.criar_particao_mensal(p_mes date)
                RETURNS text LANGUAGE plpgsql AS $fn$
                DECLARE
                    v_inicio date := date_trunc('month', p_mes)::date;
                    v_fim    date := (date_trunc('month', p_mes) + interval '1 month')::date;
                    v_nome   text := format('mensagens_%s', to_char(v_inicio, 'YYYY_MM'));
                BEGIN
                    IF to_regclass(format('entrada.%I', v_nome)) IS NULL THEN
                        EXECUTE format(
                            'CREATE TABLE entrada.%I PARTITION OF entrada.mensagens FOR VALUES FROM (%L) TO (%L)',
                            v_nome,
                            v_inicio::timestamp AT TIME ZONE 'UTC',
                            v_fim::timestamp AT TIME ZONE 'UTC'
                        );
                    END IF;
                    RETURN v_nome;
                END
                $fn$
            SQL,
            <<<'SQL'
                CREATE OR REPLACE FUNCTION entrada.garantir_particoes(p_meses integer DEFAULT 2)
                RETURNS void LANGUAGE plpgsql AS $fn$
                DECLARE
                    v_i integer;
                BEGIN
                    IF to_regclass('entrada.mensagens_default') IS NULL THEN
                        CREATE TABLE entrada.mensagens_default PARTITION OF entrada.mensagens DEFAULT;
                    END IF;
                    FOR v_i IN 0..p_meses LOOP
                        PERFORM entrada.criar_particao_mensal((date_trunc('month', now()) + make_interval(months => v_i))::date);
                    END LOOP;
                END
                $fn$
            SQL,
        ];
    }
};
