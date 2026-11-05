<?php

declare(strict_types=1);

use App\Support\Database\GuardaDeBancoDedicado;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DDL completa do banco sdc_powerbi numa migration so: as tabelas bi_* nao
 * dependem entre si e entrada/controle/funcoes de particao nascem juntas;
 * separar so espalharia o mesmo DDL (regra de consolidar migrations).
 *
 * Schemas:
 *  - entrada: caixa de entrada assincrona (mensagens particionadas por mes).
 *  - controle: cursores, execucoes, reconciliacoes e arquivos Parquet.
 *  - bi_*: tabelas achatadas no formato dos services de export, alimentadas
 *    pelo materializador. LGPD: nenhuma coluna de CPF, nome de pessoa,
 *    telefone, e-mail ou endereco de pessoa; so omissao ou hash.
 *
 * Aplica so na conexao `pgsql_powerbi`:
 *   php artisan migrate --database=pgsql_powerbi --path=database/migrations/powerbi --force
 */
return new class extends Migration
{
    protected $connection = 'pgsql_powerbi';

    /** Meses futuros mantidos prontos; a rotina de retencao repoe. */
    private const MESES_A_FRENTE = 2;

    private const SCHEMAS_BI = [
        'bi_rat', 'bi_decretacoes', 'bi_tdap', 'bi_ajuda', 'bi_estoque', 'bi_cisternas', 'bi_pae',
    ];

    public function up(): void
    {
        $db = DB::connection($this->connection);

        if ($db->getDriverName() !== 'pgsql') {
            return;
        }

        GuardaDeBancoDedicado::validar($db, 'powerbi');

        foreach ($this->schemas() as $schema) {
            $db->statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
        }

        $this->executar($db, $this->entrada());
        $db->statement(sprintf('SELECT entrada.garantir_particoes(%d)', self::MESES_A_FRENTE));
        $this->executar($db, $this->controle());

        foreach ($this->tabelasBi() as [$schema, $nome, $pk, $colunas, $comentario]) {
            $this->executar($db, $this->tabelaBi($schema, $nome, $pk, $colunas, $comentario));
        }

        $this->executar($db, $this->views());
    }

    public function down(): void
    {
        $db = DB::connection($this->connection);

        if ($db->getDriverName() !== 'pgsql') {
            return;
        }

        GuardaDeBancoDedicado::validar($db, 'powerbi');

        foreach ($this->schemas() as $schema) {
            $db->statement("DROP SCHEMA IF EXISTS {$schema} CASCADE");
        }
    }

    /** @return list<string> */
    private function schemas(): array
    {
        return ['entrada', 'controle', ...self::SCHEMAS_BI];
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
                    payload              jsonb       NOT NULL,
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
                    payload              jsonb       NOT NULL,
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

    /** @return list<string> */
    private function controle(): array
    {
        return [
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS controle.cursores (
                    modulo               text        NOT NULL,
                    tabela               text        NOT NULL,
                    ultimo_atualizado_em timestamptz NULL,
                    ultimo_id            text        NULL,
                    fonte_replay_em      timestamptz NULL,
                    atualizado_em        timestamptz NOT NULL DEFAULT now(),
                    PRIMARY KEY (modulo, tabela)
                )
            SQL,
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS controle.execucoes (
                    id              bigserial   PRIMARY KEY,
                    modulo          text        NOT NULL,
                    tabela          text        NULL,
                    tipo            text        NOT NULL DEFAULT 'incremental',
                    iniciada_em     timestamptz NOT NULL DEFAULT now(),
                    finalizada_em   timestamptz NULL,
                    status          text        NOT NULL DEFAULT 'executando',
                    linhas_lidas    bigint      NOT NULL DEFAULT 0,
                    linhas_gravadas bigint      NOT NULL DEFAULT 0,
                    erro            text        NULL,
                    detalhe         jsonb       NULL
                )
            SQL,
            'CREATE INDEX IF NOT EXISTS idx_controle_execucoes_modulo ON controle.execucoes (modulo, iniciada_em DESC)',
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS controle.reconciliacoes (
                    id             bigserial   PRIMARY KEY,
                    modulo         text        NOT NULL,
                    tabela         text        NOT NULL,
                    executada_em   timestamptz NOT NULL DEFAULT now(),
                    linhas_origem  bigint      NULL,
                    linhas_destino bigint      NULL,
                    removidas      bigint      NOT NULL DEFAULT 0,
                    divergentes    bigint      NOT NULL DEFAULT 0,
                    detalhe        jsonb       NULL
                )
            SQL,
            'CREATE INDEX IF NOT EXISTS idx_controle_reconciliacoes_tabela ON controle.reconciliacoes (modulo, tabela, executada_em DESC)',
            <<<'SQL'
                CREATE TABLE IF NOT EXISTS controle.arquivos (
                    id        bigserial   PRIMARY KEY,
                    modulo    text        NOT NULL,
                    tabela    text        NOT NULL,
                    caminho   text        NOT NULL,
                    linhas    bigint      NOT NULL DEFAULT 0,
                    dados_ate timestamptz NULL,
                    formato   text        NOT NULL DEFAULT 'parquet',
                    gerado_em timestamptz NOT NULL DEFAULT now(),
                    CONSTRAINT uq_controle_arquivos_caminho UNIQUE (caminho)
                )
            SQL,
            'CREATE INDEX IF NOT EXISTS idx_controle_arquivos_tabela ON controle.arquivos (modulo, tabela, gerado_em DESC)',
        ];
    }

    /**
     * Colunas de controle comuns a toda tabela bi_*, mais o indice de
     * paginacao por keyset (materializado_em, <pk>) usado pela API.
     *
     * @param  list<string>  $pk
     * @return list<string>
     */
    private function tabelaBi(string $schema, string $nome, array $pk, string $colunas, string $comentario): array
    {
        $chave = implode(', ', $pk);
        $comentario = str_replace("'", "''", $comentario);

        return [
            <<<SQL
                CREATE TABLE IF NOT EXISTS {$schema}.{$nome} (
                    {$colunas},
                    origem_atualizado_em timestamptz NULL,
                    materializado_em     timestamptz NOT NULL DEFAULT now(),
                    hash                 bytea       NULL,
                    removido             boolean     NOT NULL DEFAULT false,
                    PRIMARY KEY ({$chave})
                )
            SQL,
            "CREATE INDEX IF NOT EXISTS idx_{$nome}_materializado ON {$schema}.{$nome} (materializado_em, {$chave})",
            "COMMENT ON TABLE {$schema}.{$nome} IS '{$comentario}'",
        ];
    }

    /**
     * Definicao das tabelas bi_*: [schema, nome, colunas da PK, colunas, comentario].
     * Colunas no formato dos services de export de hoje, sem PII (LGPD).
     *
     * @return list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string}>
     */
    private function tabelasBi(): array
    {
        return [
            ...$this->tabelasRat(),
            ...$this->tabelasDecretacoes(),
            ...$this->tabelasTdap(),
            ...$this->tabelasAjuda(),
            ...$this->tabelasEstoque(),
            ...$this->tabelasCisternas(),
            ...$this->tabelasPae(),
        ];
    }

    /** @return list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string}> */
    private function tabelasRat(): array
    {
        return [
            ['bi_rat', 'ocorrencias', ['id'], <<<'SQL'
                id                      uuid        NOT NULL,
                numero_bos              text        NULL,
                status                  smallint    NOT NULL DEFAULT 0,
                status_descricao        text        NULL,
                uf                      text        NULL,
                municipio               text        NULL,
                data_fato               timestamp   NULL,
                ano                     smallint    NULL,
                mes                     smallint    NULL,
                cod_cobrade             text        NULL,
                cobrade_descricao       text        NULL,
                cod_ocorrencia          text        NULL,
                nome_operacao           text        NULL,
                nome_unidade            text        NULL,
                municipio_responsavel   text        NULL,
                codigo_unidade          text        NULL,
                latitude                double precision NULL,
                longitude               double precision NULL,
                local_pais              text        NULL,
                local_bairro            text        NULL,
                local_tipo_ocorrencia   text        NULL,
                data_inicio_atividade   timestamp   NULL,
                data_termino_atividade  timestamp   NULL,
                comunicacao_data        timestamp   NULL,
                comunicacao_atendimento text        NULL,
                criado_em               timestamp   NULL,
                atualizado_em           timestamp   NULL,
                prazo_edicao            timestamp   NULL,
                qtd_recursos            integer     NOT NULL DEFAULT 0,
                qtd_envolvidos          integer     NOT NULL DEFAULT 0,
                documento               jsonb       NULL
                SQL,
                'RAT (dados_gerais achatados). LGPD: sem logradouro, numero, complemento, CEP, criador (nome/e-mail) nem envolvidos; documento jsonb e o dados_gerais sem campo pessoal e sem texto livre.'],
            ['bi_rat', 'recursos', ['ocorrencia_id', 'seq'], <<<'SQL'
                ocorrencia_id       uuid     NOT NULL,
                seq                 integer  NOT NULL,
                tipo_recurso        text     NULL,
                categoria           text     NULL,
                numero_viatura      text     NULL,
                placa               text     NULL,
                orgao               text     NULL,
                descricao           text     NULL
                SQL,
                'Recursos empregados no RAT (viaturas e pessoal por tipo). Sem dado de pessoa.'],
            ['bi_rat', 'vistorias', ['ocorrencia_id'], <<<'SQL'
                ocorrencia_id               uuid     NOT NULL,
                tipo_area                   text     NULL,
                tipo_imovel                 text     NULL,
                tipo_construcao             text     NULL,
                tipo_edificacao             text     NULL,
                tipo_terreno_relevo         text     NULL,
                sistema_estrutural          text     NULL,
                numero_pavimentos           integer  NULL,
                estado_conservacao          text     NULL,
                regime_ocupacao             text     NULL,
                bairro                      text     NULL,
                municipio                   text     NULL,
                numero_moradores            integer  NULL,
                ha_idosos                   text     NULL,
                ha_criancas                 text     NULL,
                ha_dificuldade_locomocao    text     NULL,
                abastecimento_agua          text     NULL,
                esgotamento_sanitario       text     NULL,
                drenagem_superficial        text     NULL,
                sistema_viario_acesso       text     NULL,
                tipo_revestimento           text     NULL,
                condicoes_acesso            text     NULL,
                numero_moradias_terreno     integer  NULL,
                distancia_encosta           text     NULL,
                material_construtivo        text     NULL,
                conservacao_estrutural      text     NULL,
                elementos_estruturais       text     NULL,
                elementos_construtivos      text     NULL,
                agentes_potencializadores   text     NULL,
                processos_geodinamicos      text     NULL,
                patologias                  text[]   NULL,
                bens_afetados               text[]   NULL,
                orgaos_acionados            text[]   NULL,
                encaminhamentos             text[]   NULL,
                tipo_destinacao             text     NULL,
                tipo_localizacao            text     NULL
                SQL,
                'Vistoria do RAT. LGPD: sem solicitante, proprietario/morador, telefone, endereco, CEP e coordenadas do imovel.'],
            ['bi_rat', 'envolvidos_agregado', ['ocorrencia_id', 'tipo_pessoa', 'sexo', 'condicao_fisica', 'lesao_grau'], <<<'SQL'
                ocorrencia_id    uuid    NOT NULL,
                tipo_pessoa      text    NOT NULL DEFAULT '',
                sexo             text    NOT NULL DEFAULT '',
                condicao_fisica  text    NOT NULL DEFAULT '',
                lesao_grau       text    NOT NULL DEFAULT '',
                quantidade       integer NOT NULL DEFAULT 0,
                qtd_morador_rua  integer NOT NULL DEFAULT 0,
                qtd_estrangeiro  integer NOT NULL DEFAULT 0,
                qtd_turista      integer NOT NULL DEFAULT 0
                SQL,
                'Envolvidos do RAT apenas agregados por categoria (contagem). LGPD: sem nome, CPF, e-mail, telefone, nascimento, endereco nem matricula.'],
        ];
    }

    /** @return list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string}> */
    private function tabelasDecretacoes(): array
    {
        $ambientais = '';
        foreach (['agua', 'ar', 'solo', 'hidrico', 'incendio'] as $chave) {
            $ambientais .= ",\n                danos_ambientais_{$chave} text NULL,\n                danos_ambientais_{$chave}_intensidade text NULL";
        }

        // municipio_id 0 representa processo sem municipio (uma linha por processo).
        return [
            ['bi_decretacoes', 'processos', ['id', 'municipio_id'], <<<SQL
                id                          bigint   NOT NULL,
                municipio_id                bigint   NOT NULL DEFAULT 0,
                uf                          text     NULL,
                municipio                   text     NULL,
                codigo_ibge                 text     NULL,
                macroregiao                 text     NULL,
                latitude_dec                double precision NULL,
                longitude_dec               double precision NULL,
                data_registro               date     NULL,
                data_criacao                timestamptz NULL,
                deletado                    boolean  NOT NULL DEFAULT false,
                data_delecao                timestamptz NULL,
                redec_id                    integer  NULL,
                redec                       text     NULL,
                protocolo                   text     NULL,
                cobrade                     text     NULL,
                tipo_desastre               text     NULL,
                status                      text     NULL,
                data_fato                   date     NULL,
                data_decreto_municipal      date     NULL,
                data_publicacao_mg          date     NULL,
                prazo_vigencia_dias         integer  NULL,
                data_vencimento             date     NULL,
                tipo_decreto                text     NULL,
                processo                    text     NULL,
                analista                    text     NULL,
                obitos                      bigint   NOT NULL DEFAULT 0,
                feridos                     bigint   NOT NULL DEFAULT 0,
                desalojados                 bigint   NOT NULL DEFAULT 0,
                desabrigados                bigint   NOT NULL DEFAULT 0,
                desaparecidos               bigint   NOT NULL DEFAULT 0,
                outros_afetados             bigint   NOT NULL DEFAULT 0,
                danos_humanos_quantidade    bigint   NOT NULL DEFAULT 0,
                danos_materiais_danificadas numeric  NOT NULL DEFAULT 0,
                danos_materiais_destruidas  numeric  NOT NULL DEFAULT 0,
                danos_materiais_valor       numeric(18,2) NOT NULL DEFAULT 0,
                prejuizos_publicos_valor    numeric(18,2) NOT NULL DEFAULT 0,
                prejuizos_privados_valor    numeric(18,2) NOT NULL DEFAULT 0,
                danos_ambientais_marcados   integer  NOT NULL DEFAULT 0{$ambientais}
                SQL,
                'Processos de decretacao por municipio (formato ProcessoExportBIService). dias_restantes nao e gravado: use bi_decretacoes.v_processos. Danos humanos sao totais agregados, sem pessoa. analista e o servidor do orgao responsavel pelo processo (campo de trabalho, mantido).'],
        ];
    }

    /** @return list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string}> */
    private function tabelasTdap(): array
    {
        return [
            ['bi_tdap', 'viagens', ['viagem_id'], <<<'SQL'
                viagem_id          bigint   NOT NULL,
                data_registro      timestamptz NULL,
                data_aprovacao     timestamptz NULL,
                cronograma_id      bigint   NOT NULL,
                cronograma_numero  text     NULL,
                empenho            text     NULL,
                nota_empenho       text     NULL,
                cronograma_inicio  date     NULL,
                cronograma_final   date     NULL,
                ata_id             bigint   NULL,
                ata_numero         text     NULL,
                lote_id            bigint   NULL,
                lote_numero        text     NULL,
                municipio_id       bigint   NULL,
                municipio_nome     text     NULL,
                municipio_uf       text     NULL,
                prestador_id       bigint   NULL,
                prestador_nome     text     NULL,
                prestador_cnpj     text     NULL,
                caminhao_id        bigint   NULL,
                caminhao_placa     text     NULL,
                capacidade_m3      numeric(10,2) NOT NULL DEFAULT 0,
                valor_m3           numeric(14,2) NOT NULL DEFAULT 0,
                valor_viagem       numeric(14,2) NOT NULL DEFAULT 0
                SQL,
                'Viagens validadas do TDAP (formato TdapExportBiService). Prestador e pessoa juridica (CNPJ); sem motorista nem dado de pessoa fisica.'],
        ];
    }

    /** @return list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string}> */
    private function tabelasAjuda(): array
    {
        return [
            ['bi_ajuda', 'liberacoes', ['id'], <<<'SQL'
                id               bigint   NOT NULL,
                codigo_legado    bigint   NULL,
                data_liberacao   date     NULL,
                hora_liberacao   text     NULL,
                evento           text     NULL,
                status           smallint NOT NULL DEFAULT 0,
                situacao         text     NULL,
                municipio_id     bigint   NULL,
                codmundv         text     NULL,
                municipio        text     NULL,
                deposito_id      bigint   NULL,
                deposito         text     NULL,
                items_quant      numeric  NOT NULL DEFAULT 0
                SQL,
                'Liberacoes de ajuda humanitaria (LiberacaoApiService). Dado por municipio, sem beneficiario.'],
            ['bi_ajuda', 'liberacao_itens', ['id'], <<<'SQL'
                id               bigint   NOT NULL,
                liberacao_id     bigint   NOT NULL,
                municipio_id     bigint   NULL,
                codmundv         text     NULL,
                municipio        text     NULL,
                data_libera      date     NULL,
                quantidade       numeric  NOT NULL DEFAULT 0,
                id_material      text     NULL,
                material         text     NULL,
                evento           text     NULL,
                deposito         text     NULL,
                status           smallint NOT NULL DEFAULT 0
                SQL,
                'Itens de liberacao em formato plano (pubajudahCedec). Sem dado de pessoa.'],
            ['bi_ajuda', 'pedidos_itens', ['id'], <<<'SQL'
                id               bigint   NOT NULL,
                pedido_id        bigint   NOT NULL,
                status           smallint NULL,
                status_nome      text     NULL,
                descricao_item   text     NULL,
                tp_item          text     NULL,
                municipio_id     bigint   NULL,
                municipio        text     NULL,
                num_decreto      text     NULL,
                qtd              numeric  NOT NULL DEFAULT 0
                SQL,
                'Itens de pedido de AH por municipio; o consolidado do PedidoConsolidadoApiService sai por agregacao sobre esta tabela. Sem solicitante.'],
            ['bi_ajuda', 'saldos_cesta', ['deposito_id', 'material_ah_id'], <<<'SQL'
                deposito_id      bigint   NOT NULL,
                material_ah_id   bigint   NOT NULL,
                deposito         text     NULL,
                material         text     NULL,
                saldo            numeric  NOT NULL DEFAULT 0,
                valor            numeric  NULL,
                peso             numeric  NULL
                SQL,
                'Saldo de cesta basica por deposito e material (SaldoCestaApiService).'],
        ];
    }

    /** @return list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string}> */
    private function tabelasEstoque(): array
    {
        return [
            ['bi_estoque', 'produtos', ['id'], <<<'SQL'
                id                bigint  NOT NULL,
                sku               text    NULL,
                nome              text    NULL,
                categoria         text    NULL,
                unidade_base      text    NULL,
                patrimonio        boolean NULL,
                estoque_minimo    integer NULL,
                controla_validade boolean NULL,
                ativo             boolean NULL,
                saldo_total       bigint  NOT NULL DEFAULT 0,
                saldo_disponivel  bigint  NOT NULL DEFAULT 0
                SQL,
                'Produtos do estoque com saldo (linhaDeProduto).'],
            ['bi_estoque', 'lotes', ['id'], <<<'SQL'
                id               bigint  NOT NULL,
                produto_id       bigint  NOT NULL,
                lote             text    NULL,
                validade         date    NULL,
                fornecedor       text    NULL,
                endereco         text    NULL,
                saldo            bigint  NOT NULL DEFAULT 0,
                almoxarifado_id  bigint  NULL,
                almoxarifado     text    NULL,
                status           text    NULL,
                bloqueado        boolean NULL
                SQL,
                'Lotes com saldo (linhaDeLote). endereco e a posicao no almoxarifado, nao de pessoa; fornecedor e pessoa juridica.'],
            ['bi_estoque', 'movimentacoes', ['id'], <<<'SQL'
                id                   bigint  NOT NULL,
                data                 timestamptz NULL,
                tipo                 text    NULL,
                produto_id           bigint  NULL,
                quantidade           bigint  NOT NULL DEFAULT 0,
                lote_id              bigint  NULL,
                almoxarifado_id      bigint  NULL,
                saldo_apos           bigint  NULL,
                romaneio             text    NULL,
                destino_tipo         text    NULL,
                destino_municipio_id bigint  NULL
                SQL,
                'Movimentacoes de estoque (linhaDeMovimentacao). LGPD: sem usuario (nome) e sem texto livre de destino.'],
        ];
    }

    /** @return list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string}> */
    private function tabelasCisternas(): array
    {
        return [
            ['bi_cisternas', 'beneficiarios', ['id'], <<<'SQL'
                id                          bigint   NOT NULL,
                municipio_id                bigint   NULL,
                municipio                   text     NULL,
                comunidade                  text     NULL,
                situacao_analise            text     NULL,
                cpf_hash                    bytea    NULL,
                cadastro_unico_hash         bytea    NULL,
                qtd_pessoas                 integer  NULL,
                renda                       numeric  NULL,
                renda_per_capita            numeric  NULL,
                tipo_moradia                text     NULL,
                comprimento_telhado         numeric  NULL,
                largura_telhado             numeric  NULL,
                area_telhado                numeric  NULL,
                comprimento_testada         numeric  NULL,
                num_caidas_telhado          integer  NULL,
                cobertura_telhado           text     NULL,
                possui_fogao_lenha          boolean  NULL,
                medida_telhado_area_fogao   numeric  NULL,
                testada_disp_parte_fogao    numeric  NULL,
                atendido_por_pipa           boolean  NULL,
                pipa_defesa_civil           boolean  NOT NULL DEFAULT false,
                pipa_exercito               boolean  NOT NULL DEFAULT false,
                pipa_particular             boolean  NOT NULL DEFAULT false,
                pipa_prefeitura             boolean  NOT NULL DEFAULT false,
                pipa_outros                 boolean  NOT NULL DEFAULT false
                SQL,
                'Beneficiarios de cisterna (BeneficiarioExportService). LGPD: sem nome, endereco, latitude/longitude, data de nascimento, observacoes e agente/engenheiro (nome, CPF, CREA); cpf_hash e cadastro_unico_hash (hash com sal) so servem para contagem de distintos.'],
        ];
    }

    /** @return list<array{0: string, 1: string, 2: list<string>, 3: string, 4: string}> */
    private function tabelasPae(): array
    {
        return [
            ['bi_pae', 'empreendimentos', ['id'], <<<'SQL'
                id                bigint  NOT NULL,
                nome              text    NULL,
                status            text    NULL,
                municipio_id      bigint  NULL,
                municipio         text    NULL,
                empreendedor_id   bigint  NULL,
                empreendedor      text    NULL,
                empreendedor_cnpj text    NULL,
                regiao_id         bigint  NULL,
                m_construcao      text    NULL,
                material          text    NULL,
                finalidade        text    NULL,
                volume            numeric(18,2) NULL,
                pop_zas           integer NULL,
                orgao_fisc        text    NULL,
                mina              text    NULL,
                protocolo_status  text    NULL,
                protocolo_entrada date    NULL,
                criado_em         timestamptz NULL,
                atualizado_em     timestamptz NULL
                SQL,
                'Empreendimentos do PAE (EmpreendimentoApiService). Empreendedor e pessoa juridica. LGPD: sem coordenador e coordenador substituto (nome, telefone, e-mail).'],
        ];
    }

    /** @return list<string> */
    private function views(): array
    {
        return [
            // dias_restantes assinado: negativo = vencido, 0 = vence hoje, NULL = sem vencimento.
            <<<'SQL'
                CREATE OR REPLACE VIEW bi_decretacoes.v_processos AS
                SELECT p.*,
                       CASE WHEN p.data_vencimento IS NULL THEN NULL
                            ELSE (p.data_vencimento - CURRENT_DATE)
                       END AS dias_restantes
                FROM bi_decretacoes.processos p
                WHERE NOT p.removido
            SQL,
        ];
    }
};
