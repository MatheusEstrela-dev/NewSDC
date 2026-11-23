<?php

declare(strict_types=1);

use App\Support\Database\GuardaDeBancoDedicado;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Banco sdc_treinamento: DDL final do Treinamento (migration principal).
 *
 * ESTA MIGRATION NAO RODA COM `php artisan migrate`
 * Vive em database/migrations/treinamento/, que o migrator padrao nao varre, e
 * se aplica so contra a conexao `pgsql_treinamento` (o entrypoint faz isso no
 * boot, apos db:provisionar):
 *
 *   php artisan migrate --database=pgsql_treinamento --path=database/migrations/treinamento
 *
 * Reaplicar em banco ja migrado
 * A migration e idempotente (SQL com IF NOT EXISTS / ADD COLUMN IF NOT EXISTS).
 * Em ambiente onde ela ja consta como rodada (homolog, producao), a DDL nova
 * so entra depois de apagar o registro dela no sdc_treinamento e rodar de novo:
 *
 *   DELETE FROM migrations WHERE migration = '2026_11_05_210000_create_treinamento_schemas_tables';
 *   php artisan migrate --database=pgsql_treinamento --path=database/migrations/treinamento --force
 *
 * Fazer isso ANTES de subir o codigo novo: sem publico_alvo o catalogo do
 * portal e as inscricoes quebram.
 *
 * Schemas
 * - cadastro: cidadaos, cidadao_email_verificacoes, cidadao_password_reset_tokens
 *   (login e dado pessoal do cidadao, LGPD).
 * - treinamento: treinamentos, modulos, inscricoes, frequencias, certificados;
 *   do EAD: modulo_itens, treinamento_professores, progresso_itens,
 *   progresso_pastas, projeto_entregas, videos, video_uploads, provas,
 *   prova_questoes, prova_alternativas, prova_tentativas, prova_respostas,
 *   prova_liberacoes.
 *
 * POR QUE NAO HA FK PARA users
 * users vive no sdc (outro banco). treinamentos.created_by,
 * treinamentos.presenca_liberada_por, inscricoes.aprovado_por_id,
 * frequencias.registrado_por_id, certificados.criado_por,
 * certificados.cancelado_por e prova_liberacoes.liberado_por ficam como bigint
 * indexado; o nome do usuario e buscado numa segunda consulta. Pela mesma razao
 * inscricoes.inscrito_id e polimorfico (User no sdc ou Cidadao aqui) e nunca
 * teve FK. As FKs internas ao banco seguem como no sdc.
 *
 * Espelha o estado das tabelas no sdc apos a fase 1
 * (2026_11_05_200000_organiza_schemas_treinamento): o comando
 * treinamento:copiar-para-banco depende de as colunas serem iguais.
 */
return new class extends Migration
{
    protected $connection = 'pgsql_treinamento';

    /**
     * Bases operacionais que esta migration nunca pode tocar.
     */

    public function up(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        $this->exec('CREATE SCHEMA IF NOT EXISTS cadastro');
        $this->exec('CREATE SCHEMA IF NOT EXISTS treinamento');

        $this->criarCadastro();
        $this->criarTreinamento();
        $this->criarEad();
        $this->criarVideo();
        $this->ajustarTabelasExistentes();
        $this->criarComprovacao();
        $this->criarCicloDoCurso();
        $this->criarIndicadores();
        $this->criarCredencialQr();
        $this->criarCatalogo();
        $this->criarAreaDoGestor();
        $this->criarVersoes();
        $this->criarCertificadoInstrutor();
    }

    public function down(): void
    {
        if ($this->conexao()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->validarDestino();

        // Nenhum objeto fora destes schemas referencia as tabelas (sem FK para
        // fora), entao o CASCADE so leva o que e do Treinamento.
        $this->exec('DROP SCHEMA IF EXISTS treinamento CASCADE');
        $this->exec('DROP SCHEMA IF EXISTS cadastro CASCADE');
    }

    private function criarCadastro(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS cadastro.cidadaos (
                id bigserial PRIMARY KEY,
                name varchar(255) NOT NULL,
                email varchar(255) NOT NULL,
                cpf varchar(11) NOT NULL,
                telefone varchar(20),
                password varchar(255) NOT NULL,
                aceite_lgpd_em timestamp(0),
                ativo boolean NOT NULL DEFAULT true,
                last_login_at timestamp(0),
                remember_token varchar(100),
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                email_verified_at timestamp(0),
                CONSTRAINT cidadaos_cpf_unique UNIQUE (cpf),
                CONSTRAINT cidadaos_email_unique UNIQUE (email)
            )
            SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS cadastro.cidadao_email_verificacoes (
                id bigserial PRIMARY KEY,
                cidadao_id bigint NOT NULL,
                email varchar(191) NOT NULL,
                code_hash varchar(255) NOT NULL,
                code_attempts smallint NOT NULL DEFAULT 0,
                expires_at timestamp(0) NOT NULL,
                used_at timestamp(0),
                cancelled_at timestamp(0),
                requested_ip varchar(45),
                requested_user_agent varchar(255),
                resend_count smallint NOT NULL DEFAULT 0,
                last_resend_at timestamp(0),
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT cidadao_email_verificacoes_cidadao_id_foreign
                    FOREIGN KEY (cidadao_id) REFERENCES cadastro.cidadaos (id) ON DELETE CASCADE
            )
            SQL);
        $this->exec('CREATE INDEX IF NOT EXISTS idx_cev_cidadao_pending ON cadastro.cidadao_email_verificacoes (cidadao_id, used_at, cancelled_at)');

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS cadastro.cidadao_password_reset_tokens (
                email varchar(255) PRIMARY KEY,
                token varchar(255) NOT NULL,
                created_at timestamp(0)
            )
            SQL);
    }

    private function criarTreinamento(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.treinamentos (
                id bigserial PRIMARY KEY,
                titulo varchar(255) NOT NULL,
                descricao text,
                carga_horaria integer NOT NULL,
                tipo varchar(255) NOT NULL DEFAULT 'PRESENCIAL',
                status varchar(255) NOT NULL DEFAULT 'PLANEJADO',
                instrutor varchar(255),
                local varchar(255),
                data_inicio date,
                data_fim date,
                numero_vagas integer,
                percentual_frequencia_minimo numeric(5,2) NOT NULL DEFAULT 75,
                created_by bigint,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                categoria varchar(20) NOT NULL DEFAULT 'CURSO',
                link_publico_slug varchar(255),
                publicado_em timestamp(0),
                presenca_liberada boolean NOT NULL DEFAULT false,
                presenca_liberada_em timestamp(0),
                presenca_liberada_por bigint,
                finalizado_em timestamp(0),
                hora_inicio time(0),
                presenca_autoconfirmavel boolean NOT NULL DEFAULT false,
                CONSTRAINT treinamentos_link_publico_slug_unique UNIQUE (link_publico_slug),
                CONSTRAINT treinamentos_status_check CHECK (status IN ('PLANEJADO', 'EM_ANDAMENTO', 'CONCLUIDO', 'CANCELADO')),
                CONSTRAINT treinamentos_tipo_check CHECK (tipo IN ('PRESENCIAL', 'ONLINE'))
            )
            SQL);
        $this->exec("COMMENT ON COLUMN treinamento.treinamentos.carga_horaria IS 'Carga horaria total em horas'");
        $this->exec("COMMENT ON COLUMN treinamento.treinamentos.local IS 'Local fisico ou link para EAD'");
        $this->exec("COMMENT ON COLUMN treinamento.treinamentos.numero_vagas IS 'NULL = ilimitado'");
        $this->indices('treinamento.treinamentos', [
            'treinamentos_categoria_index' => 'categoria',
            'treinamentos_created_at_index' => 'created_at',
            'treinamentos_created_by_fkidx' => 'created_by',
            'treinamentos_presenca_liberada_por_fkidx' => 'presenca_liberada_por',
            'treinamentos_status_data_inicio_index' => 'status, data_inicio',
            'treinamentos_status_index' => 'status',
            'treinamentos_tipo_index' => 'tipo',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.modulos (
                id bigserial PRIMARY KEY,
                treinamento_id bigint NOT NULL,
                titulo varchar(255) NOT NULL,
                descricao text,
                ordem integer NOT NULL DEFAULT 0,
                carga_horaria integer NOT NULL,
                data_prevista date,
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT modulos_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON UPDATE CASCADE ON DELETE CASCADE
            )
            SQL);
        $this->exec("COMMENT ON COLUMN treinamento.modulos.ordem IS 'Ordem de exibicao'");
        $this->exec("COMMENT ON COLUMN treinamento.modulos.carga_horaria IS 'Carga horaria do modulo em horas'");
        $this->indices('treinamento.modulos', [
            'modulos_data_prevista_index' => 'data_prevista',
            'modulos_treinamento_id_ordem_index' => 'treinamento_id, ordem',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.inscricoes (
                id bigserial PRIMARY KEY,
                treinamento_id bigint NOT NULL,
                status varchar(255) NOT NULL DEFAULT 'PENDENTE',
                data_inscricao timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
                data_aprovacao timestamp(0),
                aprovado_por_id bigint,
                observacoes text,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                inscrito_type varchar(255) NOT NULL,
                inscrito_id bigint NOT NULL,
                qr_code_token uuid NOT NULL,
                CONSTRAINT idx_treinamento_inscrito_unique UNIQUE (treinamento_id, inscrito_type, inscrito_id),
                CONSTRAINT inscricoes_qr_code_token_unique UNIQUE (qr_code_token),
                CONSTRAINT inscricoes_status_check CHECK (status IN ('PENDENTE', 'APROVADA', 'REPROVADA', 'CANCELADA')),
                CONSTRAINT inscricoes_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON UPDATE CASCADE ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.inscricoes', [
            'idx_inscricoes_treinamento' => 'treinamento_id',
            'inscricoes_aprovado_por_id_fkidx' => 'aprovado_por_id',
            'inscricoes_created_at_index' => 'created_at',
            'inscricoes_inscrito_type_inscrito_id_index' => 'inscrito_type, inscrito_id',
            'inscricoes_status_index' => 'status',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.frequencias (
                id bigserial PRIMARY KEY,
                modulo_id bigint NOT NULL,
                status varchar(255) NOT NULL DEFAULT 'PRESENTE',
                data_aula date NOT NULL,
                observacoes text,
                registrado_por_id bigint,
                created_at timestamp(0),
                updated_at timestamp(0),
                inscricao_id bigint NOT NULL,
                origem varchar(30) NOT NULL DEFAULT 'manual',
                CONSTRAINT idx_frequencia_unique UNIQUE (modulo_id, inscricao_id, data_aula),
                CONSTRAINT frequencias_status_check CHECK (status IN ('PRESENTE', 'AUSENTE', 'JUSTIFICADA')),
                CONSTRAINT frequencias_modulo_id_foreign
                    FOREIGN KEY (modulo_id) REFERENCES treinamento.modulos (id) ON DELETE CASCADE,
                CONSTRAINT frequencias_inscricao_id_foreign
                    FOREIGN KEY (inscricao_id) REFERENCES treinamento.inscricoes (id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.frequencias', [
            'frequencias_inscricao_id_fkidx' => 'inscricao_id',
            'frequencias_modulo_id_data_aula_index' => 'modulo_id, data_aula',
            'frequencias_registrado_por_id_fkidx' => 'registrado_por_id',
            'frequencias_status_index' => 'status',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.certificados (
                id bigserial PRIMARY KEY,
                inscricao_id bigint NOT NULL,
                treinamento_id bigint NOT NULL,
                status varchar(255) NOT NULL DEFAULT 'PENDENTE',
                emitido_em timestamp(0),
                criado_por bigint,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                CONSTRAINT certificados_inscricao_id_unique UNIQUE (inscricao_id),
                CONSTRAINT certificados_status_check CHECK (status IN ('PENDENTE', 'GERADO')),
                CONSTRAINT certificados_inscricao_id_foreign
                    FOREIGN KEY (inscricao_id) REFERENCES treinamento.inscricoes (id) ON DELETE RESTRICT,
                CONSTRAINT certificados_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON DELETE RESTRICT
            )
            SQL);
        $this->indices('treinamento.certificados', [
            'certificados_criado_por_fkidx' => 'criado_por',
            'certificados_status_index' => 'status',
            'certificados_treinamento_id_fkidx' => 'treinamento_id',
        ]);
    }

    private function criarEad(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.modulo_itens (
                id bigserial PRIMARY KEY,
                modulo_id bigint NOT NULL,
                tipo varchar(20) NOT NULL,
                titulo varchar(255) NOT NULL,
                descricao text,
                ordem integer NOT NULL DEFAULT 0,
                obrigatorio boolean NOT NULL DEFAULT true,
                arquivo_path varchar(500),
                arquivo_nome varchar(255),
                arquivo_mime varchar(100),
                arquivo_tamanho bigint,
                total_paginas integer,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                CONSTRAINT modulo_itens_tipo_check CHECK (tipo IN ('PDF', 'PROJETO', 'PROVA', 'VIDEO')),
                CONSTRAINT modulo_itens_modulo_id_foreign
                    FOREIGN KEY (modulo_id) REFERENCES treinamento.modulos (id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.modulo_itens', [
            'modulo_itens_modulo_id_ordem_index' => 'modulo_id, ordem',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.treinamento_professores (
                id bigserial PRIMARY KEY,
                treinamento_id bigint NOT NULL,
                user_id bigint NOT NULL,
                created_at timestamp(0),
                CONSTRAINT treinamento_professores_treinamento_id_user_id_unique UNIQUE (treinamento_id, user_id),
                CONSTRAINT treinamento_professores_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.treinamento_professores', [
            'treinamento_professores_user_id_fkidx' => 'user_id',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.progresso_itens (
                id bigserial PRIMARY KEY,
                inscricao_id bigint NOT NULL,
                modulo_item_id bigint NOT NULL,
                status varchar(20) NOT NULL,
                posicao integer NOT NULL DEFAULT 0,
                concluido_em timestamp(0),
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT progresso_itens_inscricao_id_modulo_item_id_unique UNIQUE (inscricao_id, modulo_item_id),
                CONSTRAINT progresso_itens_status_check CHECK (status IN ('EM_ANDAMENTO', 'CONCLUIDO')),
                CONSTRAINT progresso_itens_inscricao_id_foreign
                    FOREIGN KEY (inscricao_id) REFERENCES treinamento.inscricoes (id) ON DELETE CASCADE,
                CONSTRAINT progresso_itens_modulo_item_id_foreign
                    FOREIGN KEY (modulo_item_id) REFERENCES treinamento.modulo_itens (id) ON DELETE CASCADE
            )
            SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.progresso_pastas (
                id bigserial PRIMARY KEY,
                inscricao_id bigint NOT NULL,
                modulo_id bigint NOT NULL,
                concluida_em timestamp(0) NOT NULL,
                CONSTRAINT progresso_pastas_inscricao_id_modulo_id_unique UNIQUE (inscricao_id, modulo_id),
                CONSTRAINT progresso_pastas_inscricao_id_foreign
                    FOREIGN KEY (inscricao_id) REFERENCES treinamento.inscricoes (id) ON DELETE CASCADE,
                CONSTRAINT progresso_pastas_modulo_id_foreign
                    FOREIGN KEY (modulo_id) REFERENCES treinamento.modulos (id) ON DELETE CASCADE
            )
            SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.projeto_entregas (
                id bigserial PRIMARY KEY,
                inscricao_id bigint NOT NULL,
                modulo_item_id bigint NOT NULL,
                arquivo_path varchar(500),
                arquivo_nome varchar(255),
                arquivo_mime varchar(100),
                arquivo_tamanho bigint,
                comentario text,
                status varchar(20) NOT NULL DEFAULT 'ENVIADA',
                parecer text,
                corrigido_por bigint,
                corrigido_em timestamp(0),
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT projeto_entregas_status_check CHECK (status IN ('ENVIADA', 'APROVADA', 'DEVOLVIDA')),
                CONSTRAINT projeto_entregas_inscricao_id_foreign
                    FOREIGN KEY (inscricao_id) REFERENCES treinamento.inscricoes (id) ON DELETE CASCADE,
                CONSTRAINT projeto_entregas_modulo_item_id_foreign
                    FOREIGN KEY (modulo_item_id) REFERENCES treinamento.modulo_itens (id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.projeto_entregas', [
            'projeto_entregas_modulo_item_id_status_index' => 'modulo_item_id, status',
            'projeto_entregas_inscricao_id_modulo_item_id_index' => 'inscricao_id, modulo_item_id',
            'projeto_entregas_corrigido_por_fkidx' => 'corrigido_por',
        ]);
    }

    /**
     * Biblioteca de videos on-premise: o video e um registro proprio (reusavel
     * em varios cursos); o item do curso apenas aponta para ele.
     */
    private function criarVideo(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.videos (
                id bigserial PRIMARY KEY,
                titulo varchar(255) NOT NULL,
                user_id bigint NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'SEM_VIDEO',
                original_path varchar(500),
                tamanho_bytes bigint,
                hls_versao varchar(40),
                duracao_segundos integer,
                largura integer,
                altura integer,
                processado_em timestamp(0),
                erro text,
                tentativas smallint NOT NULL DEFAULT 0,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                CONSTRAINT videos_status_check
                    CHECK (status IN ('SEM_VIDEO', 'UPLOAD_ABERTO', 'ENVIADO', 'PROCESSANDO', 'PRONTO', 'FALHOU'))
            )
            SQL);
        $this->indices('treinamento.videos', [
            'videos_user_id_fkidx' => 'user_id',
            'videos_status_index' => 'status',
        ]);

        $this->exec('ALTER TABLE treinamento.modulo_itens ADD COLUMN IF NOT EXISTS video_id bigint');
        $this->exec('ALTER TABLE treinamento.modulo_itens DROP CONSTRAINT IF EXISTS modulo_itens_video_id_foreign');
        $this->exec(<<<'SQL'
            ALTER TABLE treinamento.modulo_itens ADD CONSTRAINT modulo_itens_video_id_foreign
                FOREIGN KEY (video_id) REFERENCES treinamento.videos (id) ON DELETE RESTRICT
            SQL);
        $this->indices('treinamento.modulo_itens', [
            'modulo_itens_video_id_idx' => 'video_id',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.video_uploads (
                id uuid PRIMARY KEY,
                video_id bigint NOT NULL,
                user_id bigint NOT NULL,
                nome_original varchar(255),
                mime varchar(100),
                tamanho_total bigint NOT NULL,
                tamanho_parte integer NOT NULL,
                total_partes integer NOT NULL,
                partes_recebidas integer NOT NULL DEFAULT 0,
                tamanho_recebido bigint NOT NULL DEFAULT 0,
                status varchar(20) NOT NULL DEFAULT 'ABERTO',
                expira_em timestamp(0) NOT NULL,
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT video_uploads_status_check CHECK (status IN ('ABERTO', 'COMPLETO', 'CANCELADO')),
                CONSTRAINT video_uploads_video_id_foreign
                    FOREIGN KEY (video_id) REFERENCES treinamento.videos (id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.video_uploads', [
            'video_uploads_user_id_fkidx' => 'user_id',
        ]);
        $this->exec(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS video_uploads_um_aberto_por_video
                ON treinamento.video_uploads (video_id) WHERE status = 'ABERTO'
            SQL);
    }

    /**
     * ALTERs sobre tabelas criadas por criarTreinamento(), todos idempotentes:
     * colunas com IF NOT EXISTS e CHECKs recriados (DROP IF EXISTS + ADD).
     */
    private function ajustarTabelasExistentes(): void
    {
        $this->exec("ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS publico_alvo varchar(20) NOT NULL DEFAULT 'CIDADAOS'");
        $this->exec('ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS inscricao_aprovacao_automatica boolean NOT NULL DEFAULT false');

        $this->exec('ALTER TABLE treinamento.treinamentos DROP CONSTRAINT IF EXISTS treinamentos_publico_alvo_check');
        $this->exec("ALTER TABLE treinamento.treinamentos ADD CONSTRAINT treinamentos_publico_alvo_check CHECK (publico_alvo IN ('SERVIDORES', 'CIDADAOS'))");

        $this->exec('ALTER TABLE treinamento.treinamentos DROP CONSTRAINT IF EXISTS treinamentos_tipo_check');
        $this->exec("ALTER TABLE treinamento.treinamentos ADD CONSTRAINT treinamentos_tipo_check CHECK (tipo IN ('PRESENCIAL', 'SEMI_PRESENCIAL', 'ONLINE', 'EAD'))");

        $this->exec('ALTER TABLE treinamento.modulos ADD COLUMN IF NOT EXISTS deleted_at timestamp(0)');
    }

    /**
     * Comprovacao do EAD: provas por item, conclusao congelada na inscricao e
     * certificado verificavel. Autocontido e idempotente.
     *
     * - provas: 1:1 com o modulo_item do tipo PROVA (o tipo e garantido pela
     *   aplicacao). O item e excluido logicamente; a prova fica com ele.
     * - prova_questoes / prova_alternativas: exclusao logica (historico das
     *   tentativas). No maximo uma alternativa correta ativa por questao.
     * - prova_tentativas: so conta depois de enviada (finalizada_em). Uma aberta
     *   por (prova, inscricao). `ordem` guarda a ordem embaralhada sorteada ao
     *   iniciar: [{"questao_id": 7, "alternativas": [31, 29, 30]}, ...]; nota e
     *   aprovada sao gravadas no envio e nunca recalculadas (spec 8.7).
     * - prova_respostas: uma por (tentativa, questao); a alternativa precisa ser
     *   da questao (FK composta). `correta` e gravada na correcao.
     * - prova_liberacoes: tentativas extras dadas pelo professor (users.id do
     *   sdc, sem FK, como os demais autores).
     * - inscricoes: concluido_em + aproveitamento congelam a conclusao.
     * - certificados: codigo_verificacao (unico; nulo ate o comando de codigo
     *   retroativo), aproveitamento, reemissao e cancelamento.
     */
    private function criarComprovacao(): void
    {
        $this->exec('ALTER TABLE treinamento.inscricoes ADD COLUMN IF NOT EXISTS concluido_em timestamp(0)');
        $this->exec('ALTER TABLE treinamento.inscricoes ADD COLUMN IF NOT EXISTS aproveitamento numeric(5,2)');
        $this->exec('ALTER TABLE treinamento.inscricoes DROP CONSTRAINT IF EXISTS inscricoes_aproveitamento_check');
        $this->exec('ALTER TABLE treinamento.inscricoes ADD CONSTRAINT inscricoes_aproveitamento_check CHECK (aproveitamento BETWEEN 0 AND 100)');

        $this->exec('ALTER TABLE treinamento.certificados ADD COLUMN IF NOT EXISTS codigo_verificacao varchar(20)');
        $this->exec('ALTER TABLE treinamento.certificados ADD COLUMN IF NOT EXISTS aproveitamento numeric(5,2)');
        $this->exec('ALTER TABLE treinamento.certificados ADD COLUMN IF NOT EXISTS reemitido_em timestamp(0)');
        $this->exec('ALTER TABLE treinamento.certificados ADD COLUMN IF NOT EXISTS cancelado_em timestamp(0)');
        $this->exec('ALTER TABLE treinamento.certificados ADD COLUMN IF NOT EXISTS cancelado_por bigint');
        $this->exec('ALTER TABLE treinamento.certificados ADD COLUMN IF NOT EXISTS cancelamento_motivo text');
        $this->exec('ALTER TABLE treinamento.certificados DROP CONSTRAINT IF EXISTS certificados_codigo_verificacao_unique');
        $this->exec('ALTER TABLE treinamento.certificados ADD CONSTRAINT certificados_codigo_verificacao_unique UNIQUE (codigo_verificacao)');
        $this->exec('ALTER TABLE treinamento.certificados DROP CONSTRAINT IF EXISTS certificados_aproveitamento_check');
        $this->exec('ALTER TABLE treinamento.certificados ADD CONSTRAINT certificados_aproveitamento_check CHECK (aproveitamento BETWEEN 0 AND 100)');
        $this->exec('ALTER TABLE treinamento.certificados DROP CONSTRAINT IF EXISTS certificados_status_check');
        $this->exec("ALTER TABLE treinamento.certificados ADD CONSTRAINT certificados_status_check CHECK (status IN ('PENDENTE', 'GERADO', 'CANCELADO'))");
        $this->exec('ALTER TABLE treinamento.certificados DROP CONSTRAINT IF EXISTS certificados_cancelado_check');
        $this->exec("ALTER TABLE treinamento.certificados ADD CONSTRAINT certificados_cancelado_check CHECK (status <> 'CANCELADO' OR cancelado_em IS NOT NULL)");
        $this->indices('treinamento.certificados', [
            'certificados_cancelado_por_fkidx' => 'cancelado_por',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.provas (
                id bigserial PRIMARY KEY,
                modulo_item_id bigint NOT NULL,
                nota_minima numeric(5,2) NOT NULL DEFAULT 70,
                max_tentativas smallint NOT NULL DEFAULT 3,
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT provas_modulo_item_id_unique UNIQUE (modulo_item_id),
                CONSTRAINT provas_nota_minima_check CHECK (nota_minima BETWEEN 0 AND 100),
                CONSTRAINT provas_max_tentativas_check CHECK (max_tentativas >= 1),
                CONSTRAINT provas_modulo_item_id_foreign
                    FOREIGN KEY (modulo_item_id) REFERENCES treinamento.modulo_itens (id) ON DELETE CASCADE
            )
            SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.prova_questoes (
                id bigserial PRIMARY KEY,
                prova_id bigint NOT NULL,
                enunciado text NOT NULL,
                ordem integer NOT NULL DEFAULT 0,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                CONSTRAINT prova_questoes_prova_id_foreign
                    FOREIGN KEY (prova_id) REFERENCES treinamento.provas (id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.prova_questoes', [
            'prova_questoes_prova_id_ordem_index' => 'prova_id, ordem',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.prova_alternativas (
                id bigserial PRIMARY KEY,
                prova_questao_id bigint NOT NULL,
                texto text NOT NULL,
                correta boolean NOT NULL DEFAULT false,
                ordem integer NOT NULL DEFAULT 0,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                CONSTRAINT prova_alternativas_id_prova_questao_id_unique UNIQUE (id, prova_questao_id),
                CONSTRAINT prova_alternativas_prova_questao_id_foreign
                    FOREIGN KEY (prova_questao_id) REFERENCES treinamento.prova_questoes (id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.prova_alternativas', [
            'prova_alternativas_prova_questao_id_ordem_index' => 'prova_questao_id, ordem',
        ]);
        $this->exec(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS prova_alternativas_uma_correta_por_questao
                ON treinamento.prova_alternativas (prova_questao_id) WHERE correta AND deleted_at IS NULL
            SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.prova_tentativas (
                id bigserial PRIMARY KEY,
                prova_id bigint NOT NULL,
                inscricao_id bigint NOT NULL,
                numero smallint NOT NULL,
                ordem jsonb NOT NULL DEFAULT '[]'::jsonb,
                iniciada_em timestamp(0) NOT NULL,
                finalizada_em timestamp(0),
                nota numeric(5,2),
                aprovada boolean,
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT prova_tentativas_prova_id_inscricao_id_numero_unique UNIQUE (prova_id, inscricao_id, numero),
                CONSTRAINT prova_tentativas_numero_check CHECK (numero >= 1),
                CONSTRAINT prova_tentativas_nota_check CHECK (nota BETWEEN 0 AND 100),
                CONSTRAINT prova_tentativas_envio_check CHECK (
                    (finalizada_em IS NULL AND nota IS NULL AND aprovada IS NULL)
                    OR (finalizada_em IS NOT NULL AND nota IS NOT NULL AND aprovada IS NOT NULL)
                ),
                CONSTRAINT prova_tentativas_prova_id_foreign
                    FOREIGN KEY (prova_id) REFERENCES treinamento.provas (id) ON DELETE CASCADE,
                CONSTRAINT prova_tentativas_inscricao_id_foreign
                    FOREIGN KEY (inscricao_id) REFERENCES treinamento.inscricoes (id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.prova_tentativas', [
            'prova_tentativas_inscricao_id_fkidx' => 'inscricao_id',
        ]);
        $this->exec(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS prova_tentativas_uma_aberta
                ON treinamento.prova_tentativas (prova_id, inscricao_id) WHERE finalizada_em IS NULL
            SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.prova_respostas (
                id bigserial PRIMARY KEY,
                prova_tentativa_id bigint NOT NULL,
                prova_questao_id bigint NOT NULL,
                prova_alternativa_id bigint NOT NULL,
                correta boolean,
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT prova_respostas_tentativa_questao_unique UNIQUE (prova_tentativa_id, prova_questao_id),
                CONSTRAINT prova_respostas_prova_tentativa_id_foreign
                    FOREIGN KEY (prova_tentativa_id) REFERENCES treinamento.prova_tentativas (id) ON DELETE CASCADE,
                CONSTRAINT prova_respostas_prova_questao_id_foreign
                    FOREIGN KEY (prova_questao_id) REFERENCES treinamento.prova_questoes (id) ON DELETE CASCADE,
                CONSTRAINT prova_respostas_alternativa_da_questao_foreign
                    FOREIGN KEY (prova_alternativa_id, prova_questao_id)
                    REFERENCES treinamento.prova_alternativas (id, prova_questao_id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.prova_respostas', [
            'prova_respostas_prova_questao_id_fkidx' => 'prova_questao_id',
            'prova_respostas_alternativa_fkidx' => 'prova_alternativa_id, prova_questao_id',
        ]);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.prova_liberacoes (
                id bigserial PRIMARY KEY,
                prova_id bigint NOT NULL,
                inscricao_id bigint NOT NULL,
                quantidade smallint NOT NULL DEFAULT 1,
                liberado_por bigint NOT NULL,
                created_at timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT prova_liberacoes_quantidade_check CHECK (quantidade >= 1),
                CONSTRAINT prova_liberacoes_prova_id_foreign
                    FOREIGN KEY (prova_id) REFERENCES treinamento.provas (id) ON DELETE CASCADE,
                CONSTRAINT prova_liberacoes_inscricao_id_foreign
                    FOREIGN KEY (inscricao_id) REFERENCES treinamento.inscricoes (id) ON DELETE CASCADE
            )
            SQL);
        $this->indices('treinamento.prova_liberacoes', [
            'prova_liberacoes_prova_id_inscricao_id_index' => 'prova_id, inscricao_id',
            'prova_liberacoes_inscricao_id_fkidx' => 'inscricao_id',
            'prova_liberacoes_liberado_por_fkidx' => 'liberado_por',
        ]);
    }

    /**
     * Ciclo de vida do curso EAD (pausar/retomar, desativar/reativar). Autocontido
     * e idempotente.
     *
     * - situacao: ATIVO (padrao) | PAUSADO | DESATIVADO. Ortogonal a publicacao
     *   (publicado_em) e ao status (fluxo de eventos): nenhuma delas e alterada,
     *   entao reativar devolve o curso ao catalogo como estava.
     * - situacao_alterada_em / situacao_alterada_por: ultima mudanca (users.id do
     *   sdc, sem FK, como os demais autores).
     */
    private function criarCicloDoCurso(): void
    {
        $this->exec("ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS situacao varchar(12) NOT NULL DEFAULT 'ATIVO'");
        $this->exec('ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS situacao_alterada_em timestamp(0)');
        $this->exec('ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS situacao_alterada_por bigint');
        $this->exec('ALTER TABLE treinamento.treinamentos DROP CONSTRAINT IF EXISTS treinamentos_situacao_check');
        $this->exec("ALTER TABLE treinamento.treinamentos ADD CONSTRAINT treinamentos_situacao_check CHECK (situacao IN ('ATIVO', 'PAUSADO', 'DESATIVADO'))");
        // Capa do curso: caminho da imagem no disco privado `treinamentos`.
        // NULL = sem imagem propria, a tela gera uma capa pelo titulo.
        $this->exec('ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS capa_path varchar(255)');
        $this->indices('treinamento.treinamentos', [
            'treinamentos_situacao_index' => 'situacao',
            'treinamentos_situacao_alterada_por_fkidx' => 'situacao_alterada_por',
        ]);
    }

    /**
     * Dados do painel de indicadores das Capacitacoes. Autocontido e idempotente.
     *
     * - treinamentos.municipio_id: municipio de realizacao da turma (municipios.id
     *   do sdc, sem FK, como os demais ids de outro banco). NULL = sem municipio
     *   (EAD ou turma antiga); o painel mostra esse grupo a parte do mapa.
     * - inscricoes.sexo: declarado na inscricao (F/M/N/O). NULL = nao informado.
     *   Mora na inscricao para nao exigir coluna nova em users (outro banco).
     * - inscricoes.data_nascimento: declarada na inscricao (faixa etaria dos
     *   indicadores). NULL = nao informada.
     */
    private function criarIndicadores(): void
    {
        $this->exec('ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS municipio_id bigint');
        $this->exec('ALTER TABLE treinamento.inscricoes ADD COLUMN IF NOT EXISTS sexo char(1)');
        $this->exec('ALTER TABLE treinamento.inscricoes DROP CONSTRAINT IF EXISTS inscricoes_sexo_check');
        $this->exec("ALTER TABLE treinamento.inscricoes ADD CONSTRAINT inscricoes_sexo_check CHECK (sexo IN ('F', 'M', 'N', 'O'))");
        $this->exec('ALTER TABLE treinamento.inscricoes ADD COLUMN IF NOT EXISTS data_nascimento date');
        $this->indices('treinamento.treinamentos', [
            'treinamentos_municipio_id_fkidx' => 'municipio_id',
        ]);
        $this->indices('treinamento.inscricoes', [
            'inscricoes_concluido_em_index' => 'concluido_em',
        ]);
    }

    /**
     * Credencial por QR Code do servidor e trilha das consultas a ela. Autocontido
     * e idempotente. user_id e do sdc (sem FK, como os demais ids de outro banco).
     *
     * - credenciais_qr: um token por servidor (UNIQUE em user_id), trocavel.
     * - credencial_consultas: quem consultou quem e quando (dado pessoal lido).
     */
    private function criarCredencialQr(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.credenciais_qr (
                id bigserial PRIMARY KEY,
                user_id bigint NOT NULL UNIQUE,
                token varchar(64) NOT NULL UNIQUE,
                gerado_em timestamp(0) NOT NULL,
                created_at timestamp(0),
                updated_at timestamp(0)
            )
            SQL);
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.credencial_consultas (
                id bigserial PRIMARY KEY,
                user_id_consultado bigint NOT NULL,
                consultado_por bigint NOT NULL,
                created_at timestamp(0) NOT NULL
            )
            SQL);
        $this->indices('treinamento.credencial_consultas', [
            'credencial_consultas_user_id_consultado_index' => 'user_id_consultado',
            'credencial_consultas_consultado_por_index' => 'consultado_por',
        ]);
    }

    /**
     * Catalogo de cursos e regra de reinscricao. Autocontido e idempotente.
     *
     * - cursos: o "curso" acima das turmas (varias turmas do mesmo curso).
     *   Nome unico entre os ativos (sem caixa/acento de diferenca e o servico
     *   que garante; o indice so pega o nome exato em minusculas).
     * - treinamentos.curso_id: turma -> curso do catalogo (NULL = turma antiga
     *   ainda nao vinculada; o comando treinamento:vincular-catalogo preenche).
     * - inscricoes.municipio_inscrito_id: municipio DA PESSOA no momento da
     *   inscricao (municipios.id do sdc, sem FK). Servidor: municipio do orgao
     *   principal; cidadao: o do cadastro. Foto do momento, para o historico.
     * - cadastro.cidadaos.municipio_id: municipio informado no cadastro do portal.
     * - liberacoes_inscricao: gestor libera um CPF bloqueado pela regra das 3
     *   inscricoes no mesmo curso; zera a contagem a partir dali.
     */
    private function criarCatalogo(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.cursos (
                id bigserial PRIMARY KEY,
                nome varchar(255) NOT NULL,
                sigla varchar(30),
                descricao text,
                ativo boolean NOT NULL DEFAULT true,
                created_by bigint,
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0)
            )
            SQL);
        $this->exec('CREATE UNIQUE INDEX IF NOT EXISTS cursos_nome_unico ON treinamento.cursos (lower(nome)) WHERE deleted_at IS NULL');

        $this->exec('ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS curso_id bigint');
        $this->exec('ALTER TABLE treinamento.treinamentos DROP CONSTRAINT IF EXISTS treinamentos_curso_id_foreign');
        $this->exec('ALTER TABLE treinamento.treinamentos ADD CONSTRAINT treinamentos_curso_id_foreign FOREIGN KEY (curso_id) REFERENCES treinamento.cursos (id) ON DELETE SET NULL');

        $this->exec('ALTER TABLE treinamento.inscricoes ADD COLUMN IF NOT EXISTS municipio_inscrito_id bigint');
        $this->exec('ALTER TABLE cadastro.cidadaos ADD COLUMN IF NOT EXISTS municipio_id bigint');

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.liberacoes_inscricao (
                id bigserial PRIMARY KEY,
                cpf varchar(11) NOT NULL,
                curso_id bigint NOT NULL,
                liberado_por bigint NOT NULL,
                motivo text NOT NULL,
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT liberacoes_inscricao_curso_id_foreign
                    FOREIGN KEY (curso_id) REFERENCES treinamento.cursos (id) ON DELETE CASCADE
            )
            SQL);

        $this->indices('treinamento.treinamentos', ['treinamentos_curso_id_fkidx' => 'curso_id']);
        $this->indices('treinamento.inscricoes', ['inscricoes_municipio_inscrito_id_index' => 'municipio_inscrito_id']);
        $this->indices('cadastro.cidadaos', ['cidadaos_municipio_id_index' => 'municipio_id']);
        $this->indices('treinamento.liberacoes_inscricao', [
            'liberacoes_inscricao_cpf_curso_index' => 'cpf, curso_id',
            'liberacoes_inscricao_liberado_por_fkidx' => 'liberado_por',
        ]);
    }

    /**
     * Area do Gestor (issue #86): cronograma e historico do curso.
     *
     * - curso_cronogramas: eventos datados (aulas, provas, encontros) do curso,
     *   para o calendario do gestor;
     * - curso_eventos: historico append-only do fluxo do curso.
     *
     * POR QUE curso_eventos.tipo NAO TEM CHECK
     * O historico e append-only e a lista de tipos cresce a cada tela nova do
     * gestor. A fonte da verdade e o enum TipoEventoCurso; um CHECK aqui
     * obrigaria uma migration a cada tipo novo e derrubaria a gravacao do
     * historico (erro em transacao aborta o request inteiro no Postgres).
     *
     * autor_id (users.id no sdc) fica como bigint indexado, sem FK, como
     * treinamentos.created_by. SEMI_PRESENCIAL entra no CHECK de
     * treinamentos.tipo em ajustarTabelasExistentes().
     */
    private function criarAreaDoGestor(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.curso_cronogramas (
                id bigserial PRIMARY KEY,
                treinamento_id bigint NOT NULL,
                titulo varchar(160) NOT NULL,
                descricao text,
                tipo varchar(20) NOT NULL DEFAULT 'AULA',
                inicio timestamp(0) NOT NULL,
                fim timestamp(0),
                local varchar(255),
                created_at timestamp(0),
                updated_at timestamp(0),
                deleted_at timestamp(0),
                CONSTRAINT curso_cronogramas_tipo_check CHECK (tipo IN ('AULA', 'PROVA', 'ENTREGA', 'ENCONTRO', 'OUTRO')),
                CONSTRAINT curso_cronogramas_periodo_check CHECK (fim IS NULL OR fim >= inicio),
                CONSTRAINT curso_cronogramas_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON UPDATE CASCADE ON DELETE CASCADE
            )
            SQL);

        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.curso_eventos (
                id bigserial PRIMARY KEY,
                treinamento_id bigint NOT NULL,
                tipo varchar(40) NOT NULL,
                descricao varchar(255) NOT NULL,
                dados jsonb NOT NULL DEFAULT '{}'::jsonb,
                autor_id bigint,
                created_at timestamp(0) NOT NULL,
                CONSTRAINT curso_eventos_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON UPDATE CASCADE ON DELETE CASCADE
            )
            SQL);
        $this->exec("COMMENT ON COLUMN treinamento.curso_eventos.autor_id IS 'users.id no banco sdc; sem FK (outro banco). NULL = acao de sistema (job, comando)'");
        $this->exec("COMMENT ON COLUMN treinamento.curso_eventos.tipo IS 'Sem CHECK: a fonte da verdade e o enum TipoEventoCurso'");

        $this->indices('treinamento.curso_cronogramas', [
            'curso_cronogramas_treinamento_inicio_index' => 'treinamento_id, inicio',
            'curso_cronogramas_inicio_index' => 'inicio',
        ]);
        $this->indices('treinamento.curso_eventos', [
            'curso_eventos_treinamento_id_index' => 'treinamento_id, id',
            'curso_eventos_created_at_index' => 'created_at',
            'curso_eventos_autor_id_fkidx' => 'autor_id',
        ]);
    }

    /**
     * Versoes de curso EAD: cada versao e um treinamento proprio no mesmo
     * curso do catalogo. Autocontido e idempotente.
     *
     * - treinamentos.versao: numero principal da versao (1, 2, 3...). Todo
     *   treinamento existente vira 1 pelo DEFAULT.
     * - treinamentos.versao_origem_id: versao da qual esta foi copiada (NULL na
     *   primeira). SET NULL: excluir a origem nao leva a copia junto.
     * - treinamentos.versao_notas: "o que mudou", preenchido ao publicar.
     * - modulo_itens.origem_item_id: item de onde este foi copiado; a cadeia
     *   (3.0 -> 2.0 -> 1.0) decide o aproveitamento.
     * - modulo_itens.alterado_desde_copia: true quando o conteudo deixou de ser
     *   igual ao da origem (titulo, descricao e ordem nao contam).
     * - inscricoes.atualizacao_de_inscricao_id: inscricao concluida (versao
     *   anterior) que esta aproveitou; NOT NULL marca a inscricao de
     *   atualizacao, que nunca conta na regra das 3 inscricoes.
     *
     * POR QUE O UNICO PARCIAL (curso_id, versao)
     * Numero de versao repetido no mesmo curso deixaria a "vigente" ambigua. O
     * indice vale so para EAD: turmas presenciais do mesmo curso do catalogo
     * coexistem todas na versao 1 (versao so existe onde ha arvore de conteudo).
     * Ignora tambem treinamentos sem curso (turmas antigas ainda nao vinculadas)
     * e excluidos, para o numero poder ser reaproveitado apos excluir um rascunho.
     * O indice criado na primeira aplicacao (sem o filtro de tipo) e recriado.
     */
    private function criarVersoes(): void
    {
        $this->exec('ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS versao integer NOT NULL DEFAULT 1');
        $this->exec('ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS versao_origem_id bigint');
        $this->exec('ALTER TABLE treinamento.treinamentos ADD COLUMN IF NOT EXISTS versao_notas text');
        $this->chaveEstrangeira('treinamento.treinamentos', 'treinamentos_versao_origem_id_foreign', 'versao_origem_id', 'treinamento.treinamentos');

        $this->exec('ALTER TABLE treinamento.modulo_itens ADD COLUMN IF NOT EXISTS origem_item_id bigint');
        $this->exec('ALTER TABLE treinamento.modulo_itens ADD COLUMN IF NOT EXISTS alterado_desde_copia boolean NOT NULL DEFAULT false');
        $this->chaveEstrangeira('treinamento.modulo_itens', 'modulo_itens_origem_item_id_foreign', 'origem_item_id', 'treinamento.modulo_itens');

        $this->exec('ALTER TABLE treinamento.inscricoes ADD COLUMN IF NOT EXISTS atualizacao_de_inscricao_id bigint');
        $this->chaveEstrangeira('treinamento.inscricoes', 'inscricoes_atualizacao_de_inscricao_id_foreign', 'atualizacao_de_inscricao_id', 'treinamento.inscricoes');

        $this->indiceUnicoDeVersao();

        $this->indices('treinamento.treinamentos', ['treinamentos_versao_origem_id_fkidx' => 'versao_origem_id']);
        $this->indices('treinamento.modulo_itens', ['modulo_itens_origem_item_id_fkidx' => 'origem_item_id']);
        $this->indices('treinamento.inscricoes', ['inscricoes_atualizacao_de_inscricao_id_fkidx' => 'atualizacao_de_inscricao_id']);
    }

    /**
     * Certificado de quem ministrou o curso. No EAD e o professor designado
     * (user_id); no presencial o instrutor e so um nome digitado (user_id NULL).
     * Um por (curso, professor) e um por (curso, nome) sem usuario. O codigo
     * tem o formato do certificado do aluno e e unico entre as duas tabelas
     * (garantido no servico). user_id e emitido_por apontam para o banco sdc:
     * sem FK, como em treinamento_professores.
     */
    private function criarCertificadoInstrutor(): void
    {
        $this->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS treinamento.certificados_instrutor (
                id bigserial PRIMARY KEY,
                treinamento_id bigint NOT NULL,
                user_id bigint,
                nome varchar(255) NOT NULL,
                codigo_verificacao varchar(20) NOT NULL,
                emitido_em timestamp(0) NOT NULL,
                emitido_por bigint,
                created_at timestamp(0),
                updated_at timestamp(0),
                CONSTRAINT certificados_instrutor_codigo_unique UNIQUE (codigo_verificacao),
                CONSTRAINT certificados_instrutor_treinamento_id_foreign
                    FOREIGN KEY (treinamento_id) REFERENCES treinamento.treinamentos (id) ON DELETE RESTRICT
            )
            SQL);
        $this->exec('CREATE UNIQUE INDEX IF NOT EXISTS certificados_instrutor_usuario_unico ON treinamento.certificados_instrutor (treinamento_id, user_id) WHERE user_id IS NOT NULL');
        $this->exec('CREATE UNIQUE INDEX IF NOT EXISTS certificados_instrutor_nome_unico ON treinamento.certificados_instrutor (treinamento_id, lower(nome)) WHERE user_id IS NULL');
        $this->indices('treinamento.certificados_instrutor', [
            'certificados_instrutor_user_id_fkidx' => 'user_id',
        ]);
    }

    /** Cria a FK so se ela ainda nao existe: reaplicar nao revalida nem trava as tabelas. */
    private function chaveEstrangeira(string $tabela, string $nome, string $coluna, string $referencia): void
    {
        $existe = $this->conexao()->selectOne(
            'SELECT 1 FROM pg_constraint WHERE conname = ? AND conrelid = ?::regclass',
            [$nome, $tabela],
        );

        if ($existe === null) {
            $this->exec("ALTER TABLE {$tabela} ADD CONSTRAINT {$nome} FOREIGN KEY ({$coluna}) REFERENCES {$referencia} (id) ON DELETE SET NULL");
        }
    }

    /**
     * Uma versao EAD por (curso, numero). Duplicados ja existentes (varios EAD no
     * mesmo curso, todos na versao 1) sao numerados 1, 2, 3... por created_at, id
     * antes de o indice existir. Reaplicar nao derruba nem recria o indice.
     */
    private function indiceUnicoDeVersao(): void
    {
        $nome = 'treinamentos_curso_versao_unico';
        $existente = $this->conexao()->selectOne(
            "SELECT indexdef FROM pg_indexes WHERE schemaname = 'treinamento' AND indexname = ?",
            [$nome],
        );

        if ($existente !== null && str_contains((string) $existente->indexdef, "'EAD'")) {
            return;
        }

        $this->exec(<<<'SQL'
            WITH numerados AS (
                SELECT id, row_number() OVER (PARTITION BY curso_id ORDER BY created_at, id) AS numero
                FROM treinamento.treinamentos
                WHERE curso_id IS NOT NULL AND tipo = 'EAD' AND deleted_at IS NULL
            )
            UPDATE treinamento.treinamentos t
            SET versao = n.numero
            FROM numerados n
            WHERE t.id = n.id
              AND t.curso_id IN (
                  SELECT curso_id FROM treinamento.treinamentos
                  WHERE curso_id IS NOT NULL AND tipo = 'EAD' AND deleted_at IS NULL
                  GROUP BY curso_id, versao HAVING count(*) > 1
              )
              AND t.versao <> n.numero
            SQL);

        $this->exec("DROP INDEX IF EXISTS treinamento.{$nome}");
        $this->exec("CREATE UNIQUE INDEX {$nome} ON treinamento.treinamentos (curso_id, versao) WHERE curso_id IS NOT NULL AND tipo = 'EAD' AND deleted_at IS NULL");
    }

    /**
     * @param  array<string, string>  $indices  nome => colunas
     */
    private function indices(string $tabela, array $indices): void
    {
        foreach ($indices as $nome => $colunas) {
            $this->exec("CREATE INDEX IF NOT EXISTS {$nome} ON {$tabela} ({$colunas})");
        }
    }

    private function conexao(): Connection
    {
        return DB::connection($this->connection);
    }

    private function exec(string $sql): void
    {
        $this->conexao()->statement($sql);
    }

    /**
     * Recusa aplicar a DDL sobre a base operacional.
     *
     * Checa o nome real da database na conexao aberta, e nao so a config: um
     * TREINAMENTO_DB_DATABASE apontando para o sdc, ou um --database trocado,
     * passaria por qualquer verificacao feita so na configuracao. Recusa tambem
     * a database da conexao principal, seja qual for o nome dela no ambiente.
     */
    private function validarDestino(): void
    {
        GuardaDeBancoDedicado::validar($this->conexao(), 'treinamento');
    }
};
