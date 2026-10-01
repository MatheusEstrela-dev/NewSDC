<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Modulo Resgate - schema `resgate` dentro de `sdc_ranking`.
 *
 * Plano: docs/superpowers/plans/2026-09-25-resgate-pontos-catalogo.md
 *
 * MIGRATION PRINCIPAL DO SCHEMA. As fases seguintes do plano (catalogo,
 * pedidos, eventos, documentos, bloqueios) entram AQUI, consolidadas, e nao em
 * migrations novas.
 *
 * Aplica-se como a do ranking, so contra a conexao `ranking`:
 *
 *   php artisan migrate --database=ranking --path=database/migrations/ranking
 *
 * MESMA DATABASE DO LEDGER, DE PROPOSITO
 * A reserva de pontos le `ranking.lancamentos` e grava `resgate.movimentos` na
 * mesma transacao. Em outra base a consistencia dependeria de compensacao
 * distribuida.
 *
 * APPEND-ONLY NO BANCO
 * As tabelas de trilha recusam UPDATE e DELETE por trigger, e nao so pela
 * aplicacao: o historico de resgate precisa resistir a escrita direta, porque
 * pode virar prova em processo.
 */
return new class extends Migration
{
    protected $connection = 'ranking';

    private const DESTINOS_PROIBIDOS = ['sdc', 'forge'];

    public function up(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        $this->exec('CREATE SCHEMA IF NOT EXISTS resgate');

        // Trava de trilha: qualquer UPDATE ou DELETE aborta. Correcao e um
        // movimento novo (liberacao, estorno de debito), nunca edicao.
        $this->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION resgate.recusar_alteracao() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'resgate.%: registro append-only, % recusado', TG_TABLE_NAME, TG_OP
                    USING ERRCODE = 'insufficient_privilege';
            END;
            $$
        SQL);

        // Carteira do ente. O saldo resgatavel e derivado do ledger; aqui so
        // entra o que o RESGATE fez com ele: reservar, liberar, debitar e
        // estornar debito. Pontos com sinal: reserva e debito negativos.
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.movimentos (
                id            bigserial PRIMARY KEY,
                ente_escopo   varchar(12)  NOT NULL,
                ente_id       bigint       NOT NULL,
                tipo          varchar(20)  NOT NULL,
                pontos        integer      NOT NULL,
                pedido_id     bigint       NULL,
                -- Movimento de pedido de DEMONSTRACAO: consome so o saldo de
                -- demonstracao. Os dois saldos nunca se misturam.
                demonstracao  boolean      NOT NULL DEFAULT false,
                chave         varchar(160) NOT NULL,
                criado_em     timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT ck_resgate_movimentos_escopo CHECK (ente_escopo IN ('municipio', 'orgao')),
                CONSTRAINT ck_resgate_movimentos_tipo CHECK (tipo IN ('reserva', 'liberacao', 'debito', 'estorno_debito')),
                CONSTRAINT ck_resgate_movimentos_sinal CHECK (
                    (tipo IN ('reserva', 'debito') AND pontos < 0)
                    OR (tipo IN ('liberacao', 'estorno_debito') AND pontos > 0)
                ),
                -- Idempotencia: a mesma transicao reprocessada nao duplica movimento.
                CONSTRAINT uq_resgate_movimentos_chave UNIQUE (chave)
            )
        SQL);
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_movimentos_ente ON resgate.movimentos (ente_escopo, ente_id)');
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_movimentos_append_only ON resgate.movimentos');
        $this->exec('CREATE TRIGGER tg_resgate_movimentos_append_only BEFORE UPDATE OR DELETE ON resgate.movimentos FOR EACH ROW EXECUTE FUNCTION resgate.recusar_alteracao()');

        $this->criarCatalogo();
        $this->criarPedidos();
        $this->criarExecucao();
        $this->criarBloqueios();
    }

    /**
     * Fase 5 - bloqueio judicial ou administrativo (premissa P7).
     *
     * Suspende na hora um ENTE inteiro ou um PEDIDO, com o documento de origem
     * (processo, decisao). Enquanto vigente, nenhuma transicao avanca. O
     * bloqueio nunca e apagado: encerrar e gravar quando e por que, uma vez so.
     */
    private function criarBloqueios(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.bloqueios (
                id                   bigserial PRIMARY KEY,
                alvo                 varchar(10)  NOT NULL,
                ente_escopo          varchar(12)  NULL,
                ente_id              bigint       NULL,
                pedido_id            bigint       NULL REFERENCES resgate.pedidos (id) ON DELETE RESTRICT,
                tipo                 varchar(15)  NOT NULL,
                documento_origem     varchar(120) NOT NULL,
                motivo               text         NOT NULL,
                registrado_por       bigint       NOT NULL,
                registrado_em        timestamptz  NOT NULL DEFAULT now(),
                registrado_ip        varchar(45)  NULL,
                encerrado_por        bigint       NULL,
                encerrado_em         timestamptz  NULL,
                encerramento_motivo  text         NULL,

                CONSTRAINT ck_resgate_bloqueios_alvo CHECK (
                    (alvo = 'ente' AND ente_escopo IN ('municipio', 'orgao') AND ente_id IS NOT NULL AND pedido_id IS NULL)
                    OR (alvo = 'pedido' AND pedido_id IS NOT NULL AND ente_id IS NULL)
                ),
                CONSTRAINT ck_resgate_bloqueios_tipo CHECK (tipo IN ('judicial', 'administrativo')),
                CONSTRAINT ck_resgate_bloqueios_encerramento CHECK (
                    (encerrado_em IS NULL AND encerrado_por IS NULL AND encerramento_motivo IS NULL)
                    OR (encerrado_em IS NOT NULL AND encerrado_por IS NOT NULL AND encerramento_motivo IS NOT NULL)
                )
            )
        SQL);
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_bloqueios_ente ON resgate.bloqueios (ente_escopo, ente_id) WHERE encerrado_em IS NULL');
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_bloqueios_pedido ON resgate.bloqueios (pedido_id) WHERE encerrado_em IS NULL');

        // So o encerramento pode ser gravado, uma vez; o registro original
        // (alvo, tipo, documento, motivo, autor) nunca muda. DELETE nunca.
        $this->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION resgate.guardar_bloqueio() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'resgate.bloqueios: DELETE recusado' USING ERRCODE = 'insufficient_privilege';
                END IF;
                IF OLD.encerrado_em IS NOT NULL
                   OR (to_jsonb(NEW) - 'encerrado_por' - 'encerrado_em' - 'encerramento_motivo')
                      IS DISTINCT FROM (to_jsonb(OLD) - 'encerrado_por' - 'encerrado_em' - 'encerramento_motivo') THEN
                    RAISE EXCEPTION 'resgate.bloqueios: so o encerramento de um bloqueio vigente pode ser gravado'
                        USING ERRCODE = 'insufficient_privilege';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_bloqueios_guarda ON resgate.bloqueios');
        $this->exec('CREATE TRIGGER tg_resgate_bloqueios_guarda BEFORE UPDATE OR DELETE ON resgate.bloqueios FOR EACH ROW EXECUTE FUNCTION resgate.guardar_bloqueio()');
    }

    /**
     * Fase 4 - termo, SEI, entrega e confirmacao (plano, secao 4).
     *
     * Escrito para tambem ATUALIZAR um banco que ja esta na Fase 3: CHECKs e
     * indice sao recriados e as colunas entram com IF NOT EXISTS, entao
     * reaplicar o up() e seguro.
     */
    private function criarExecucao(): void
    {
        // Status e etapas da Fase 4 passam de 12 caracteres ('termo_assinado',
        // 'assinar_municipio'): alarga as colunas de banco ja criado na Fase 3.
        $this->exec('ALTER TABLE resgate.pedidos ALTER COLUMN status TYPE varchar(20)');
        $this->exec('ALTER TABLE resgate.pedido_eventos ALTER COLUMN etapa TYPE varchar(20), ALTER COLUMN de_status TYPE varchar(20), ALTER COLUMN para_status TYPE varchar(20)');

        $status = "'reservado', 'aprovado', 'termo_emitido', 'termo_assinado', 'entregue', 'contestado', 'concluido', 'recusado', 'cancelado', 'expirado', 'anulado'";
        $this->exec('ALTER TABLE resgate.pedidos DROP CONSTRAINT IF EXISTS ck_resgate_pedidos_status');
        $this->exec("ALTER TABLE resgate.pedidos ADD CONSTRAINT ck_resgate_pedidos_status CHECK (status IN ({$status}))");

        foreach ([
            'processo_sei varchar(30) NULL',
            'termo_documento_sei varchar(20) NULL',
            'assinado_estado_por bigint NULL',
            'assinado_estado_em timestamptz NULL',
            'assinado_municipio_por bigint NULL',
            'assinado_municipio_em timestamptz NULL',
            'entregue_em timestamptz NULL',
            'concluido_em timestamptz NULL',
            // Pontos debitados sem credito elegivel de origem (estorno ou ajuste
            // entre a reserva e a confirmacao): a carteira fica devendo e o
            // dossie mostra a pendencia, em vez de travar o pedido entregue.
            'pendencia_debito integer NOT NULL DEFAULT 0',
        ] as $coluna) {
            $this->exec("ALTER TABLE resgate.pedidos ADD COLUMN IF NOT EXISTS {$coluna}");
        }

        // Unidade presa a pedido que ainda nao terminou mal: inclui o
        // concluido, porque a unidade foi entregue e nao volta ao catalogo.
        $this->exec('DROP INDEX IF EXISTS resgate.uq_resgate_pedidos_unidade_ativa');
        $this->exec("CREATE UNIQUE INDEX uq_resgate_pedidos_unidade_ativa ON resgate.pedidos (unidade_id) WHERE status NOT IN ('recusado', 'cancelado', 'expirado', 'anulado') AND unidade_id IS NOT NULL");

        $this->exec('ALTER TABLE resgate.pedido_eventos DROP CONSTRAINT IF EXISTS ck_resgate_eventos_etapa');
        $this->exec("ALTER TABLE resgate.pedido_eventos ADD CONSTRAINT ck_resgate_eventos_etapa CHECK (etapa IN ('solicitar', 'aprovar', 'recusar', 'cancelar', 'expirar', 'termo', 'assinar_estado', 'assinar_municipio', 'entregar', 'contestar', 'confirmar', 'anular'))");

        // Anexos do pedido: termo assinado, evidencias de entrega. O arquivo
        // fica no disco com o hash no nome; a linha guarda o SHA-256, e a
        // leitura confere o hash antes de servir.
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.documentos (
                id            bigserial PRIMARY KEY,
                pedido_id     bigint       NOT NULL REFERENCES resgate.pedidos (id) ON DELETE RESTRICT,
                tipo          varchar(20)  NOT NULL,
                nome_original varchar(200) NOT NULL,
                caminho       varchar(300) NOT NULL,
                sha256        char(64)     NOT NULL,
                tamanho       integer      NOT NULL,
                mime          varchar(100) NOT NULL,
                enviado_por   bigint       NOT NULL,
                ip_address    varchar(45)  NULL,
                user_agent    text         NULL,
                criado_em     timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT ck_resgate_documentos_tipo CHECK (tipo IN ('termo', 'evidencia_entrega', 'contestacao'))
            )
        SQL);
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_documentos_pedido ON resgate.documentos (pedido_id)');
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_documentos_append_only ON resgate.documentos');
        $this->exec('CREATE TRIGGER tg_resgate_documentos_append_only BEFORE UPDATE OR DELETE ON resgate.documentos FOR EACH ROW EXECUTE FUNCTION resgate.recusar_alteracao()');

        // Rastreio do ponto consumido (plano, secao 2.3): cada debito aponta os
        // lancamentos do ledger que o compuseram (FIFO pela competencia).
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.consumos (
                id             bigserial PRIMARY KEY,
                movimento_id   bigint      NOT NULL REFERENCES resgate.movimentos (id) ON DELETE RESTRICT,
                pedido_id      bigint      NOT NULL REFERENCES resgate.pedidos (id) ON DELETE RESTRICT,
                lancamento_id  bigint      NOT NULL,
                pontos         integer     NOT NULL,
                criado_em      timestamptz NOT NULL DEFAULT now(),

                CONSTRAINT ck_resgate_consumos_pontos CHECK (pontos > 0),
                CONSTRAINT uq_resgate_consumos UNIQUE (movimento_id, lancamento_id)
            )
        SQL);
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_consumos_lancamento ON resgate.consumos (lancamento_id)');
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_consumos_append_only ON resgate.consumos');
        $this->exec('CREATE TRIGGER tg_resgate_consumos_append_only BEFORE UPDATE OR DELETE ON resgate.consumos FOR EACH ROW EXECUTE FUNCTION resgate.recusar_alteracao()');

        // Nenhum lancamento e consumido alem do seu valor liquido, nem sob
        // concorrencia: a linha do lancamento fica travada durante a checagem.
        $this->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION resgate.limitar_consumo() RETURNS trigger
            LANGUAGE plpgsql AS $$
            DECLARE
                liquido integer;
                ja integer;
            BEGIN
                SELECT l.pontos INTO liquido FROM ranking.lancamentos l WHERE l.id = NEW.lancamento_id FOR UPDATE;
                IF liquido IS NULL THEN
                    RAISE EXCEPTION 'resgate.consumos: lancamento % inexistente', NEW.lancamento_id USING ERRCODE = 'foreign_key_violation';
                END IF;
                liquido := liquido + COALESCE((SELECT SUM(e.pontos) FROM ranking.lancamentos e WHERE e.estorno_de_id = NEW.lancamento_id), 0);
                SELECT COALESCE(SUM(c.pontos), 0) INTO ja FROM resgate.consumos c WHERE c.lancamento_id = NEW.lancamento_id;
                IF ja + NEW.pontos > liquido THEN
                    RAISE EXCEPTION 'resgate.consumos: lancamento % ja consumido (% de %)', NEW.lancamento_id, ja, liquido
                        USING ERRCODE = 'check_violation';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_consumos_limite ON resgate.consumos');
        $this->exec('CREATE TRIGGER tg_resgate_consumos_limite BEFORE INSERT ON resgate.consumos FOR EACH ROW EXECUTE FUNCTION resgate.limitar_consumo()');

        // Pedido: os dados da solicitacao nunca mudam, e o status so segue as
        // transicoes do fluxo. Escrita direta que pule etapa ou troque ente,
        // item, custo ou unidade e recusada.
        $this->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION resgate.guardar_pedido() RETURNS trigger
            LANGUAGE plpgsql AS $$
            DECLARE
                mutaveis text[] := ARRAY['status', 'atualizado_em', 'processo_sei', 'termo_documento_sei',
                    'assinado_estado_por', 'assinado_estado_em', 'assinado_municipio_por', 'assinado_municipio_em',
                    'entregue_em', 'concluido_em', 'pendencia_debito'];
                permitido boolean;
            BEGIN
                IF (to_jsonb(NEW) - mutaveis) IS DISTINCT FROM (to_jsonb(OLD) - mutaveis) THEN
                    RAISE EXCEPTION 'resgate.pedidos: dados da solicitacao sao imutaveis' USING ERRCODE = 'insufficient_privilege';
                END IF;
                permitido := CASE OLD.status
                    WHEN 'reservado' THEN NEW.status IN ('aprovado', 'recusado', 'cancelado', 'expirado')
                    WHEN 'aprovado' THEN NEW.status IN ('termo_emitido', 'anulado')
                    WHEN 'termo_emitido' THEN NEW.status IN ('termo_emitido', 'termo_assinado', 'anulado')
                    WHEN 'termo_assinado' THEN NEW.status IN ('entregue', 'anulado')
                    WHEN 'entregue' THEN NEW.status IN ('concluido', 'contestado', 'anulado')
                    WHEN 'contestado' THEN NEW.status IN ('entregue', 'anulado')
                    ELSE false
                END;
                IF NOT permitido THEN
                    RAISE EXCEPTION 'resgate.pedidos: transicao % -> % nao existe no fluxo', OLD.status, NEW.status
                        USING ERRCODE = 'insufficient_privilege';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_pedidos_guarda ON resgate.pedidos');
        $this->exec('CREATE TRIGGER tg_resgate_pedidos_guarda BEFORE UPDATE ON resgate.pedidos FOR EACH ROW EXECUTE FUNCTION resgate.guardar_pedido()');
    }

    /**
     * Fase 3 - pedido de resgate, reserva e decisao da CEDEC (plano, secao 4).
     *
     * O pedido nasce RESERVADO (pontos e unidade presos) e fica assim ate a
     * CEDEC aprovar ou recusar. Cada passo e um evento append-only, com hash
     * encadeado e segregacao de funcoes garantida no banco.
     */
    private function criarPedidos(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.pedidos (
                id                  bigserial PRIMARY KEY,
                protocolo           varchar(20)  NOT NULL,
                ente_escopo         varchar(12)  NOT NULL,
                ente_id             bigint       NOT NULL,
                -- Versao do item CONGELADA na solicitacao: mudanca posterior no
                -- catalogo nao altera pedido ja feito.
                item_id             bigint       NOT NULL REFERENCES resgate.catalogo_itens (id) ON DELETE RESTRICT,
                item_codigo         varchar(40)  NOT NULL,
                item_versao         integer      NOT NULL,
                unidade_id          bigint       NULL REFERENCES resgate.unidades (id) ON DELETE RESTRICT,
                custo_pontos        integer      NOT NULL,
                faixa_exigida       varchar(12)  NOT NULL,
                faixa_do_ente       varchar(12)  NOT NULL,
                temporada_referencia varchar(20) NOT NULL,
                status              varchar(20)  NOT NULL,
                demonstracao        boolean      NOT NULL DEFAULT false,
                chave_idempotencia  varchar(80)  NOT NULL,
                solicitado_por      bigint       NOT NULL,
                solicitado_em       timestamptz  NOT NULL DEFAULT now(),
                expira_em           timestamptz  NOT NULL,
                atualizado_em       timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT uq_resgate_pedidos_protocolo UNIQUE (protocolo),
                CONSTRAINT uq_resgate_pedidos_idempotencia UNIQUE (chave_idempotencia),
                CONSTRAINT ck_resgate_pedidos_escopo CHECK (ente_escopo IN ('municipio', 'orgao')),
                CONSTRAINT ck_resgate_pedidos_status CHECK (status IN ('reservado', 'aprovado', 'recusado', 'cancelado', 'expirado')),
                CONSTRAINT ck_resgate_pedidos_custo CHECK (custo_pontos >= 0)
            )
        SQL);
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_pedidos_ente ON resgate.pedidos (ente_escopo, ente_id, status)');
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_pedidos_fila ON resgate.pedidos (status, solicitado_em)');
        // Uma unidade em no maximo UM pedido ativo: nao se promete a mesma
        // viatura a dois municipios, nem sob concorrencia.
        $this->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_resgate_pedidos_unidade_ativa ON resgate.pedidos (unidade_id) WHERE status IN ('reservado', 'aprovado') AND unidade_id IS NOT NULL");

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.pedido_eventos (
                id              bigserial PRIMARY KEY,
                pedido_id       bigint       NOT NULL REFERENCES resgate.pedidos (id) ON DELETE RESTRICT,
                sequencia       integer      NOT NULL,
                etapa           varchar(20)  NOT NULL,
                de_status       varchar(20)  NULL,
                para_status     varchar(20)  NOT NULL,
                -- Nulo so em ato de sistema (expiracao); ato humano exige autor.
                ator_user_id    bigint       NULL,
                permissao       varchar(60)  NULL,
                justificativa   text         NULL,
                ip_address      varchar(45)  NULL,
                user_agent      text         NULL,
                session_id      varchar(100) NULL,
                request_id      varchar(64)  NULL,
                hash            char(64)     NOT NULL,
                hash_anterior   char(64)     NULL,
                ocorrido_em     timestamptz  NOT NULL DEFAULT clock_timestamp(),

                CONSTRAINT uq_resgate_eventos_sequencia UNIQUE (pedido_id, sequencia),
                CONSTRAINT ck_resgate_eventos_etapa CHECK (etapa IN ('solicitar', 'aprovar', 'recusar', 'cancelar', 'expirar')),
                CONSTRAINT ck_resgate_eventos_autor CHECK (etapa = 'expirar' OR ator_user_id IS NOT NULL)
            )
        SQL);
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_eventos_append_only ON resgate.pedido_eventos');
        $this->exec('CREATE TRIGGER tg_resgate_eventos_append_only BEFORE UPDATE OR DELETE ON resgate.pedido_eventos FOR EACH ROW EXECUTE FUNCTION resgate.recusar_alteracao()');

        // Segregacao de funcoes NO BANCO (P4): cada pessoa ocupa UM papel por
        // pedido. Repetir a propria etapa pode (reentrega apos contestacao,
        // cancelar o que pediu); acumular papeis nao. Ato de sistema nao conta.
        $this->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION resgate.papel_da_etapa(etapa text) RETURNS text
            LANGUAGE sql IMMUTABLE AS $$
                SELECT CASE etapa
                    WHEN 'solicitar' THEN 'solicitante' WHEN 'cancelar' THEN 'solicitante'
                    WHEN 'aprovar' THEN 'decisor' WHEN 'recusar' THEN 'decisor' WHEN 'anular' THEN 'decisor'
                    WHEN 'termo' THEN 'formalizador' WHEN 'assinar_estado' THEN 'formalizador'
                    WHEN 'assinar_municipio' THEN 'assinante_municipio'
                    WHEN 'entregar' THEN 'entregador'
                    WHEN 'confirmar' THEN 'recebedor' WHEN 'contestar' THEN 'recebedor'
                END
            $$
        SQL);
        $this->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION resgate.segregar_funcoes() RETURNS trigger
            LANGUAGE plpgsql AS $$
            DECLARE
                anterior text;
            BEGIN
                IF NEW.ator_user_id IS NULL OR resgate.papel_da_etapa(NEW.etapa) IS NULL THEN
                    RETURN NEW;
                END IF;
                SELECT resgate.papel_da_etapa(e.etapa) INTO anterior
                  FROM resgate.pedido_eventos e
                 WHERE e.pedido_id = NEW.pedido_id
                   AND e.ator_user_id = NEW.ator_user_id
                   AND resgate.papel_da_etapa(e.etapa) IS NOT NULL
                   AND resgate.papel_da_etapa(e.etapa) <> resgate.papel_da_etapa(NEW.etapa)
                 LIMIT 1;
                IF anterior IS NOT NULL THEN
                    RAISE EXCEPTION 'resgate.pedido_eventos: quem atuou como % nao atua como % no mesmo pedido', anterior, resgate.papel_da_etapa(NEW.etapa)
                        USING ERRCODE = 'check_violation';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_eventos_segregacao ON resgate.pedido_eventos');
        $this->exec('CREATE TRIGGER tg_resgate_eventos_segregacao BEFORE INSERT ON resgate.pedido_eventos FOR EACH ROW EXECUTE FUNCTION resgate.segregar_funcoes()');

        // Pedido nunca e apagado; so o status (e atualizado_em) muda, sempre
        // acompanhado de evento.
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_pedidos_sem_delete ON resgate.pedidos');
        $this->exec('CREATE TRIGGER tg_resgate_pedidos_sem_delete BEFORE DELETE ON resgate.pedidos FOR EACH ROW EXECUTE FUNCTION resgate.recusar_alteracao()');
    }

    /**
     * Fase 2 - catalogo versionado com quatro olhos (plano, secao 3).
     *
     * Toda mudanca no catalogo nasce como PROPOSTA de uma pessoa e so vira
     * versao publicada com a APROVACAO de outra. A regra vale no banco, nao so
     * no servico: CHECK proposto_por <> decidido_por.
     */
    private function criarCatalogo(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.catalogo_propostas (
                id                  bigserial PRIMARY KEY,
                codigo              varchar(40)  NOT NULL,
                acao                varchar(12)  NOT NULL,
                dados               jsonb        NOT NULL DEFAULT '{}'::jsonb,
                justificativa       text         NOT NULL,
                status              varchar(10)  NOT NULL DEFAULT 'pendente',

                -- Rastro de quem propos (decisao D3): mesmo padrao do
                -- permission_audit_log.
                proposto_por        bigint       NOT NULL,
                proposto_em         timestamptz  NOT NULL DEFAULT now(),
                proposto_ip         varchar(45)  NULL,
                proposto_user_agent text         NULL,
                proposto_sessao     varchar(100) NULL,
                proposto_requisicao varchar(64)  NULL,

                decidido_por        bigint       NULL,
                decidido_em         timestamptz  NULL,
                decidido_ip         varchar(45)  NULL,
                decidido_user_agent text         NULL,
                decidido_sessao     varchar(100) NULL,
                decidido_requisicao varchar(64)  NULL,
                decisao_justificativa text       NULL,
                item_id             bigint       NULL,

                CONSTRAINT ck_resgate_propostas_acao CHECK (acao IN ('criar', 'nova_versao', 'encerrar')),
                CONSTRAINT ck_resgate_propostas_status CHECK (status IN ('pendente', 'aprovada', 'recusada')),
                CONSTRAINT ck_resgate_propostas_quatro_olhos CHECK (decidido_por IS NULL OR decidido_por <> proposto_por),
                CONSTRAINT ck_resgate_propostas_decisao CHECK (
                    (status = 'pendente' AND decidido_por IS NULL AND decidido_em IS NULL)
                    OR (status <> 'pendente' AND decidido_por IS NOT NULL AND decidido_em IS NOT NULL AND decisao_justificativa IS NOT NULL)
                )
            )
        SQL);
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_propostas_pendentes ON resgate.catalogo_propostas (status, proposto_em)');

        // Proposta so muda UMA vez: de pendente para decidida. Nem a proposta
        // nem a decisao podem ser reescritas depois; DELETE nunca.
        $this->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION resgate.guardar_proposta() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'resgate.catalogo_propostas: DELETE recusado' USING ERRCODE = 'insufficient_privilege';
                END IF;
                IF OLD.status <> 'pendente'
                   OR NEW.codigo IS DISTINCT FROM OLD.codigo
                   OR NEW.acao IS DISTINCT FROM OLD.acao
                   OR NEW.dados IS DISTINCT FROM OLD.dados
                   OR NEW.justificativa IS DISTINCT FROM OLD.justificativa
                   OR NEW.proposto_por IS DISTINCT FROM OLD.proposto_por
                   OR NEW.proposto_em IS DISTINCT FROM OLD.proposto_em
                   OR NEW.proposto_ip IS DISTINCT FROM OLD.proposto_ip THEN
                    RAISE EXCEPTION 'resgate.catalogo_propostas: so a decisao de uma proposta pendente pode ser gravada'
                        USING ERRCODE = 'insufficient_privilege';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_propostas_guarda ON resgate.catalogo_propostas');
        $this->exec('CREATE TRIGGER tg_resgate_propostas_guarda BEFORE UPDATE OR DELETE ON resgate.catalogo_propostas FOR EACH ROW EXECUTE FUNCTION resgate.guardar_proposta()');

        // Versao publicada. Nasce so da aprovacao de uma proposta; o pedido de
        // resgate congela a versao vigente no instante da solicitacao.
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.catalogo_itens (
                id                        bigserial PRIMARY KEY,
                codigo                    varchar(40)  NOT NULL,
                versao                    integer      NOT NULL,
                tipo                      varchar(16)  NOT NULL,
                titulo                    varchar(160) NOT NULL,
                descricao                 text         NOT NULL,
                beneficiario              varchar(12)  NOT NULL,
                faixa_minima              varchar(12)  NOT NULL,
                custo_pontos              integer      NOT NULL DEFAULT 0,
                quantidade                integer      NULL,
                limite_por_ente_temporada integer      NULL,
                prazo_reserva_dias        integer      NOT NULL DEFAULT 30,
                instrumento               varchar(40)  NOT NULL,
                base_normativa            varchar(200) NOT NULL,
                unidade_responsavel       varchar(160) NOT NULL,
                documentos_exigidos       jsonb        NOT NULL DEFAULT '[]'::jsonb,
                demonstracao              boolean      NOT NULL DEFAULT false,
                vigente_de                timestamptz  NOT NULL,
                vigente_ate               timestamptz  NULL,
                proposta_id               bigint       NOT NULL REFERENCES resgate.catalogo_propostas (id) ON DELETE RESTRICT,
                proposto_por              bigint       NOT NULL,
                aprovado_por              bigint       NOT NULL,
                criado_em                 timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT uq_resgate_itens_versao UNIQUE (codigo, versao),
                CONSTRAINT ck_resgate_itens_tipo CHECK (tipo IN ('servico', 'adesao', 'bem_consumo', 'bem_permanente')),
                CONSTRAINT ck_resgate_itens_beneficiario CHECK (beneficiario IN ('municipio', 'orgao')),
                CONSTRAINT ck_resgate_itens_faixa CHECK (faixa_minima IN ('bronze', 'prata', 'ouro', 'diamante')),
                CONSTRAINT ck_resgate_itens_custo CHECK (custo_pontos >= 0),
                CONSTRAINT ck_resgate_itens_quantidade CHECK (quantidade IS NULL OR quantidade >= 0),
                CONSTRAINT ck_resgate_itens_limite CHECK (limite_por_ente_temporada IS NULL OR limite_por_ente_temporada > 0),
                CONSTRAINT ck_resgate_itens_prazo CHECK (prazo_reserva_dias BETWEEN 1 AND 365),
                CONSTRAINT ck_resgate_itens_quatro_olhos CHECK (proposto_por <> aprovado_por),
                CONSTRAINT ck_resgate_itens_vigencia CHECK (vigente_ate IS NULL OR vigente_ate > vigente_de)
            )
        SQL);
        // No maximo uma versao vigente por codigo.
        $this->exec('CREATE UNIQUE INDEX IF NOT EXISTS uq_resgate_itens_vigente ON resgate.catalogo_itens (codigo) WHERE vigente_ate IS NULL');

        // Versao publicada e imutavel; o unico UPDATE aceito e ENCERRAR a
        // vigencia (vigente_ate de nulo para um instante), e uma vez so.
        $this->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION resgate.guardar_item() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'resgate.catalogo_itens: DELETE recusado' USING ERRCODE = 'insufficient_privilege';
                END IF;
                IF OLD.vigente_ate IS NOT NULL OR NEW.vigente_ate IS NULL
                   OR (to_jsonb(NEW) - 'vigente_ate') IS DISTINCT FROM (to_jsonb(OLD) - 'vigente_ate') THEN
                    RAISE EXCEPTION 'resgate.catalogo_itens: versao publicada e imutavel; so a vigencia pode ser encerrada'
                        USING ERRCODE = 'insufficient_privilege';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_itens_guarda ON resgate.catalogo_itens');
        $this->exec('CREATE TRIGGER tg_resgate_itens_guarda BEFORE UPDATE OR DELETE ON resgate.catalogo_itens FOR EACH ROW EXECUTE FUNCTION resgate.guardar_item()');

        // Unidades individualizadas de bem permanente (viatura, drone...). O
        // SDC nao tem cadastro de frota: o patrimonio e registrado aqui; o
        // vinculo com o Inventario de TI e opcional (equipamento ja inventariado).
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS resgate.unidades (
                id                        bigserial PRIMARY KEY,
                item_codigo               varchar(40)  NOT NULL,
                patrimonio                varchar(40)  NOT NULL,
                descricao                 varchar(200) NOT NULL,
                placa                     varchar(10)  NULL,
                renavam                   varchar(11)  NULL,
                chassi                    varchar(17)  NULL,
                numero_serie              varchar(80)  NULL,
                inventario_equipamento_id bigint       NULL,
                estado                    varchar(12)  NOT NULL DEFAULT 'disponivel',
                demonstracao              boolean      NOT NULL DEFAULT false,
                cadastrado_por            bigint       NOT NULL,
                cadastrado_em             timestamptz  NOT NULL DEFAULT now(),
                cadastrado_ip             varchar(45)  NULL,
                cadastrado_user_agent     text         NULL,

                CONSTRAINT uq_resgate_unidades_patrimonio UNIQUE (patrimonio),
                CONSTRAINT ck_resgate_unidades_estado CHECK (estado IN ('disponivel', 'reservada', 'entregue', 'baixada'))
            )
        SQL);
        $this->exec('CREATE INDEX IF NOT EXISTS ix_resgate_unidades_item ON resgate.unidades (item_codigo, estado)');
        // Unidade nunca e apagada: sai do catalogo por baixa. As transicoes de
        // estado sao da Fase 3 (reserva/entrega).
        $this->exec('DROP TRIGGER IF EXISTS tg_resgate_unidades_sem_delete ON resgate.unidades');
        $this->exec('CREATE TRIGGER tg_resgate_unidades_sem_delete BEFORE DELETE ON resgate.unidades FOR EACH ROW EXECUTE FUNCTION resgate.recusar_alteracao()');
    }

    public function down(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        // Nenhum objeto fora de `resgate` referencia estas tabelas.
        $this->exec('DROP SCHEMA IF EXISTS resgate CASCADE');
    }

    private function conexao(): \Illuminate\Database\Connection
    {
        return DB::connection($this->connection);
    }

    private function exec(string $sql): void
    {
        $this->conexao()->statement($sql);
    }

    /** Recusa aplicar o schema de resgate sobre a base operacional. */
    private function validarDestino(): void
    {
        if ($this->conexao()->pretending()) {
            return;
        }

        $atual = (string) $this->conexao()->selectOne('SELECT current_database() AS db')->db;

        if (in_array($atual, self::DESTINOS_PROIBIDOS, true)) {
            throw new RuntimeException(
                "Migration de resgate recusada: destino '{$atual}' e base operacional. "
                . 'Aplique com --database=ranking apontando para sdc_ranking.'
            );
        }
    }
};
