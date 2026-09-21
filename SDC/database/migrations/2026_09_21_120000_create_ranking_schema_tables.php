<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Modulo Ranking - schema proprio, livro de pontos e projecoes de leitura.
 *
 * Plano: docs/superpowers/plans/2026-09-21-ranqueamento-ipcm.md
 *
 * POR QUE SCHEMA E NAO DATABASE SEPARADA
 * O plano original pedia a database `sdc_ranking` com tres conexoes proprias.
 * O teto de conexoes documentado em config/database.php (SWOOLE_PG_POOL_SIZE x
 * OCTANE_WORKERS x instancias <= max_connections) e restricao dura on-premise,
 * e tres conexoes novas por worker sairiam caro por um isolamento que, no mesmo
 * cluster, e logico de qualquer forma. Schema da o mesmo isolamento de tabelas
 * e de privilegios (GRANT por schema), reaproveita a conexao `pgsql` e mantem a
 * promocao futura para database propria como troca de env var - nenhuma tabela
 * aqui referencia o schema public por FK, justamente para isso.
 *
 * POR QUE NAO HA FK PARA users / compdec_orgaos / municipios
 * O livro e append-only e sobrevive ao ciclo de vida do cadastro. Remover um
 * usuario nao pode cascatear no ledger nem abortar por RESTRICT: o ponto ja foi
 * conquistado e a auditoria precisa continuar legivel. As colunas ficam como
 * bigint indexado e a integridade e verificada na ingestao.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE SCHEMA IF NOT EXISTS ranking');

        // Catalogo versionado. A regra vigente na COMPETENCIA do fato e que
        // vale - por isso vigencia e versao ficam na linha, e uma regra nunca
        // e editada no lugar: publica-se outra versao. Replay de evento antigo
        // reaplica a versao antiga e nao gera segunda premiacao.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.regras (
                id                  bigserial PRIMARY KEY,
                rule_key            varchar(120) NOT NULL,
                versao              integer      NOT NULL DEFAULT 1,
                modulo              varchar(40)  NOT NULL,

                -- Familia agrupa marcos que disputam o MESMO premio. Compdec e
                -- PlanCon compartilham 'plano_municipal_revisao': a revisao
                -- aceita do plano 123/ciclo 2026 e um fato so, chegue ela pelo
                -- evento de um modulo ou do outro.
                familia             varchar(60)  NOT NULL,

                pontos_base         integer      NOT NULL,
                bonus_percentual    smallint     NOT NULL DEFAULT 20,
                aceita_bonus        boolean      NOT NULL DEFAULT false,

                -- Nasce desabilitada de proposito. So habilita o marco que tem
                -- fonte de evidencia comprovada; o motivo abaixo documenta por
                -- que os demais continuam fora, em vez de sumirem do catalogo.
                habilitada          boolean      NOT NULL DEFAULT false,
                motivo_desabilitada varchar(60)  NULL,

                vigente_de          timestamptz  NOT NULL,
                vigente_ate         timestamptz  NULL,
                criado_em           timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT ck_ranking_regras_pontos CHECK (pontos_base >= 0),
                CONSTRAINT ck_ranking_regras_bonus  CHECK (bonus_percentual BETWEEN 0 AND 100),
                CONSTRAINT ck_ranking_regras_motivo CHECK (
                    habilitada = true OR motivo_desabilitada IS NOT NULL
                ),
                CONSTRAINT uq_ranking_regras_key_versao UNIQUE (rule_key, versao)
            )
        SQL);

        // Uma unica versao vigente (sem data de fim) por rule_key.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS uq_ranking_regras_vigente
                ON ranking.regras (rule_key) WHERE vigente_ate IS NULL
        SQL);

        // Decisao explicita por fato avaliado. Todo fato que chega recebe uma
        // linha aqui, inclusive quando a decisao e zero ou apuracao: ausencia
        // de linha significa que o fato nunca foi avaliado, o que e diferente
        // de ter sido avaliado e nao pontuado.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.transacoes (
                id                 bigserial PRIMARY KEY,

                -- BARREIRA TECNICA. event_id vem do DomainEvent da origem. O
                -- listener ja e protegido por processed_events, mas a unicidade
                -- aqui cobre o caso de o mesmo evento chegar por outro caminho
                -- (replay manual, reprocessamento de fila).
                event_id           uuid         NOT NULL,
                event_name         varchar(150) NOT NULL,

                -- BARREIRA DE NEGOCIO. Mesmo marco, mesmo ciclo, mesmo recurso
                -- = um premio, ainda que com outro event_id, outra rota ou
                -- outro usuario repetindo a acao.
                chave_canonica     varchar(200) NOT NULL,
                familia            varchar(60)  NOT NULL,

                regra_id           bigint       NULL REFERENCES ranking.regras (id) ON DELETE RESTRICT,
                decisao            varchar(20)  NOT NULL,
                motivo             varchar(80)  NULL,

                -- Executor, creditado e validador sao papeis distintos. O
                -- validador nao herda o premio; em treinamento o creditado e o
                -- participante e o executor e quem registrou a conclusao.
                actor_user_id      bigint       NULL,
                credited_user_id   bigint       NULL,
                validador_user_id  bigint       NULL,

                -- Vinculo no INSTANTE do fato, resolvido pelo contexto do
                -- evento. Copiar o orgao atual do usuario nao prova vinculo
                -- historico e por isso nunca preenche estas colunas.
                orgao_id           bigint       NULL,
                municipio_id       bigint       NULL,

                ocorrido_em        timestamptz  NOT NULL,
                competencia_em     timestamptz  NOT NULL,
                contexto           jsonb        NOT NULL DEFAULT '{}'::jsonb,
                criado_em          timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT uq_ranking_transacoes_event     UNIQUE (event_id),
                CONSTRAINT uq_ranking_transacoes_canonica  UNIQUE (chave_canonica, familia),

                -- Regra nula so e aceitavel quando nao houve pontuacao.
                CONSTRAINT ck_ranking_transacoes_regra CHECK (
                    regra_id IS NOT NULL OR decisao IN ('zero', 'em_apuracao')
                ),
                CONSTRAINT ck_ranking_transacoes_motivo CHECK (
                    decisao NOT IN ('zero', 'em_apuracao') OR motivo IS NOT NULL
                )
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_transacoes_decisao ON ranking.transacoes (decisao, competencia_em)');
        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_transacoes_modulo ON ranking.transacoes (event_name, competencia_em)');

        // Livro de pontos. APPEND-ONLY: nada aqui e alterado ou removido.
        // Correcao vira lancamento novo apontando para o original.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.lancamentos (
                id               bigserial PRIMARY KEY,
                transacao_id     bigint       NOT NULL REFERENCES ranking.transacoes (id) ON DELETE RESTRICT,

                -- Um lancamento por transacao por natureza (credito ou estorno).
                entry_key        varchar(220) NOT NULL,

                credited_user_id bigint       NULL,
                orgao_id         bigint       NULL,
                municipio_id     bigint       NULL,
                modulo           varchar(40)  NOT NULL,

                regra_id         bigint       NULL REFERENCES ranking.regras (id) ON DELETE RESTRICT,
                regra_versao     integer      NULL,

                pontos_base      integer      NOT NULL DEFAULT 0,
                pontos_bonus     integer      NOT NULL DEFAULT 0,

                -- Assinado: negativo em estorno. O saldo e a soma desta coluna,
                -- nunca uma subtracao feita na leitura.
                pontos           integer      NOT NULL,

                competencia_em   timestamptz  NOT NULL,

                -- Estorno referencia o credito que anula. O servico limita o
                -- total estornado ao valor original; a auto-referencia aqui
                -- permite auditar a cadeia de correcao.
                estorno_de_id    bigint       NULL REFERENCES ranking.lancamentos (id) ON DELETE RESTRICT,

                criado_em        timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT uq_ranking_lancamentos_entry UNIQUE (entry_key),
                CONSTRAINT ck_ranking_lancamentos_soma  CHECK (pontos = pontos_base + pontos_bonus),
                CONSTRAINT ck_ranking_lancamentos_sinal CHECK (
                    (estorno_de_id IS NULL AND pontos >= 0)
                    OR (estorno_de_id IS NOT NULL AND pontos <= 0)
                )
            )
        SQL);

        // Indices de extrato: as tres dimensoes sempre filtram por competencia.
        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_lanc_usuario ON ranking.lancamentos (credited_user_id, competencia_em) WHERE credited_user_id IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_lanc_orgao ON ranking.lancamentos (orgao_id, competencia_em) WHERE orgao_id IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_lanc_municipio ON ranking.lancamentos (municipio_id, competencia_em) WHERE municipio_id IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_lanc_transacao ON ranking.lancamentos (transacao_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_lanc_estorno ON ranking.lancamentos (estorno_de_id) WHERE estorno_de_id IS NOT NULL');

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.periodos (
                id          bigserial PRIMARY KEY,
                tipo        varchar(12) NOT NULL,

                -- Chave estavel: 'mes:2026-09', 'ano:2026', 'acumulado'.
                chave       varchar(30) NOT NULL,

                -- Instantes em UTC; os limites de calendario sao recortados em
                -- America/Sao_Paulo e o intervalo e [inicia_em, termina_em).
                -- Acumulado nao tem limite: ambas as colunas ficam nulas.
                inicia_em   timestamptz NULL,
                termina_em  timestamptz NULL,

                CONSTRAINT uq_ranking_periodos_chave UNIQUE (chave)
            )
        SQL);

        // Projecao de leitura. O placar consulta SO esta tabela.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.saldos (
                id            bigserial PRIMARY KEY,

                -- Geracao permite reconstruir o placar em paralelo ao que esta
                -- em uso e trocar o ponteiro ativo no fim, sem truncar nada.
                geracao       integer      NOT NULL DEFAULT 1,

                periodo_id    bigint       NOT NULL REFERENCES ranking.periodos (id) ON DELETE CASCADE,
                escopo        varchar(12)  NOT NULL,
                entidade_id   bigint       NOT NULL,

                -- Valor explicito 'all' para o total, nunca NULL: NULL nao
                -- participa de UNIQUE no Postgres e permitiria linhas de total
                -- duplicadas passando pela constraint.
                modulo        varchar(40)  NOT NULL DEFAULT 'all',

                pontos        bigint       NOT NULL DEFAULT 0,
                faixa         varchar(12)  NOT NULL DEFAULT 'bronze',
                atualizado_em timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT uq_ranking_saldos UNIQUE (geracao, periodo_id, escopo, entidade_id, modulo)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_saldos_placar ON ranking.saldos (geracao, periodo_id, escopo, modulo, pontos DESC, entidade_id)');

        // Participante elegivel aparece no placar mesmo com saldo zero; sem
        // isso o placar mostraria apenas quem ja pontuou e esconderia a base
        // de comparacao. Regiao e tipo ficam congelados no periodo para que
        // reorganizacao administrativa nao reescreva ranking antigo.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.participantes (
                id          bigserial PRIMARY KEY,
                periodo_id  bigint      NOT NULL REFERENCES ranking.periodos (id) ON DELETE CASCADE,
                escopo      varchar(12) NOT NULL,
                entidade_id bigint      NOT NULL,
                regiao      varchar(60) NULL,
                tipo        varchar(40) NULL,

                -- Vinculo em apuracao fica fora da classificacao, mas continua
                -- registrado: some do placar, nao do historico.
                elegivel    boolean     NOT NULL DEFAULT true,

                CONSTRAINT uq_ranking_participantes UNIQUE (periodo_id, escopo, entidade_id)
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.snapshots (
                id               bigserial PRIMARY KEY,
                periodo_id       bigint      NOT NULL REFERENCES ranking.periodos (id) ON DELETE CASCADE,
                escopo           varchar(12) NOT NULL,

                -- Correcao tardia publica nova revisao preservando a anterior.
                revisao          integer     NOT NULL DEFAULT 1,
                geracao          integer     NOT NULL DEFAULT 1,

                -- Ultimo lancamento considerado. Rebuild concorrente compara o
                -- watermark para incorporar o delta em vez de perde-lo.
                ledger_watermark bigint      NOT NULL DEFAULT 0,

                motivo           varchar(160) NULL,
                criado_em        timestamptz NOT NULL DEFAULT now(),

                CONSTRAINT uq_ranking_snapshots UNIQUE (periodo_id, escopo, revisao)
            )
        SQL);

        // escopo repetido no item de proposito: sem ele, usuario 7 e orgao 7
        // colidiriam na unicidade abaixo.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.snapshot_itens (
                id          bigserial PRIMARY KEY,
                snapshot_id bigint      NOT NULL REFERENCES ranking.snapshots (id) ON DELETE CASCADE,
                escopo      varchar(12) NOT NULL,
                entidade_id bigint      NOT NULL,
                pontos      bigint      NOT NULL DEFAULT 0,

                -- Posicao densa: 100, 90, 90, 80 produz 1, 2, 2, 3.
                posicao     integer     NOT NULL,
                faixa       varchar(12) NOT NULL DEFAULT 'bronze',

                CONSTRAINT uq_ranking_snapshot_itens UNIQUE (snapshot_id, escopo, entidade_id)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_snapshot_itens_posicao ON ranking.snapshot_itens (snapshot_id, posicao)');

        // Vinculo historico usuario -> orgao -> municipio. Copia local com
        // intervalo de validade, porque a pivot compdec_orgao_user guarda o
        // estado ATUAL e nao permite responder "a que orgao Ana pertencia em
        // marco". Data desconhecida fica explicita em `evidencia`.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.vinculos (
                id           bigserial PRIMARY KEY,
                user_id      bigint      NOT NULL,
                orgao_id     bigint      NOT NULL,
                municipio_id bigint      NULL,
                valido_de    timestamptz NOT NULL,
                valido_ate   timestamptz NULL,

                -- 'comprovado' | 'inferido' | 'em_apuracao'. So o comprovado
                -- autoriza credito competitivo.
                evidencia    varchar(20) NOT NULL DEFAULT 'em_apuracao',
                criado_em    timestamptz NOT NULL DEFAULT now(),

                CONSTRAINT uq_ranking_vinculos UNIQUE (user_id, orgao_id, valido_de)
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_vinculos_janela ON ranking.vinculos (user_id, valido_de, valido_ate)');

        // Ajuste nunca edita saldo direto: gera pedido, decisao e lancamento
        // corretivo no livro. Sem este caminho, a unica forma de corrigir seria
        // UPDATE no ledger, que quebraria a auditoria.
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS ranking.pedidos_ajuste (
                id                  bigserial PRIMARY KEY,
                lancamento_id       bigint       NULL REFERENCES ranking.lancamentos (id) ON DELETE RESTRICT,
                solicitante_user_id bigint       NOT NULL,
                aprovador_user_id   bigint       NULL,
                motivo              text         NOT NULL,
                decisao             varchar(20)  NOT NULL DEFAULT 'pendente',
                justificativa       text         NULL,
                decidido_em         timestamptz  NULL,
                criado_em           timestamptz  NOT NULL DEFAULT now(),

                CONSTRAINT ck_ranking_ajuste_decisao CHECK (
                    decisao <> 'pendente' OR (aprovador_user_id IS NULL AND decidido_em IS NULL)
                )
            )
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS ix_ranking_ajuste_pendentes ON ranking.pedidos_ajuste (decisao, criado_em)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // CASCADE derruba as tabelas do schema de uma vez. Seguro aqui porque
        // nenhum objeto fora de `ranking` referencia estas tabelas - o modulo
        // nao cria FK a partir do schema public.
        DB::statement('DROP SCHEMA IF EXISTS ranking CASCADE');
    }
};
