<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema do inbox de notificacoes agrupadas. Migration unica e consolidada com
 * TODAS as mudancas de banco da feature:
 *
 * 1. notifications: colunas e indices de agrupamento. Em vez de uma linha por
 *    evento (que deixa o contador do sino errado e infla o payload), o canal
 *    agrupavel mantem UMA linha por assunto enquanto nao lida, incrementando
 *    group_count.
 * 2. notifications.data: text -> jsonb, para o historico poder filtrar por
 *    severidade (data->>'type') com indice, em vez de varrer texto.
 * 3. users.notification_update_mode: corrige o campo que ja estava em $fillable
 *    do model User sem existir no banco.
 * 4. user_notification_preferences.module: remove o CHECK que travava a coluna
 *    em 5 modulos, para a lista passar a viver em config/notificacoes.php.
 *
 * Nao consolidada dentro de 2025_01_27_000000_create_notifications_table nem de
 * 2014_10_12_000000_create_users_table porque ambas ja estao aplicadas em dev e
 * producao: editar migration ja executada entregaria as colunas apenas em
 * instalacao limpa e nunca nos bancos existentes.
 *
 * ---
 * SOBRE O AGRUPAMENTO ATOMICO (Postgres)
 *
 * group_bucket e a janela de agrupamento discretizada: floor(epoch / janela).
 * Com ela, o par (destinatario, group_key, group_bucket) identifica o agrupamento
 * de forma estavel, sem depender de now() em tempo de consulta. Isso permite um
 * indice UNIQUE PARCIAL (apenas sobre nao lidas) e, com ele, um unico
 * INSERT ... ON CONFLICT DO UPDATE que soma o contador de forma atomica no
 * proprio banco. Dois eventos simultaneos nao geram linhas duplicadas e nao ha
 * lock de aplicacao no caminho quente.
 *
 * Colunas com group_key NULL nunca colidem (NULLS DISTINCT, default do btree),
 * portanto notificacoes nao agrupaveis convivem no mesmo indice sem conflito.
 *
 * MySQL/MariaDB nao tem indice parcial: a migration cria indices completos e o
 * canal de entrega usa o caminho alternativo com lock no Redis. Producao roda
 * Postgres, este e o caminho otimizado.
 */
return new class extends Migration
{
    private const UNIQUE_UPSERT = 'notifications_group_upsert_uidx';

    private const INDEX_NAO_LIDAS = 'notifications_unread_idx';

    private const INDEX_HISTORICO = 'notifications_historico_idx';

    private const INDEX_BRIN_CRIACAO = 'notifications_created_brin';

    private const CHECK_MODULE = 'user_notification_preferences_module_check';

    /**
     * Modulos originais do CHECK, restaurados no rollback.
     */
    private const MODULOS_ORIGINAIS = ['rat', 'pae', 'meteorologia', 'demandas', 'decretacoes'];

    public function up(): void
    {
        $this->criarColunasDeAgrupamento();
        $this->converterDataParaJsonb();
        $this->criarIndices();
        $this->criarTabelaDeArquivo();
        $this->criarModoDeAtualizacaoNoUser();
        $this->liberarModulosDasPreferencias();
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_archive');

        $this->restaurarCheckDeModulos();

        if (Schema::hasColumn('users', 'notification_update_mode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('notification_update_mode');
            });
        }

        foreach ([self::UNIQUE_UPSERT, self::INDEX_NAO_LIDAS, self::INDEX_HISTORICO, self::INDEX_BRIN_CRIACAO] as $indice) {
            $this->dropIndex($indice);
        }

        $this->reverterDataParaText();

        $colunas = array_values(array_filter(
            ['group_key', 'group_bucket', 'group_count', 'last_event_at'],
            fn (string $coluna): bool => Schema::hasColumn('notifications', $coluna)
        ));

        if ($colunas !== []) {
            Schema::table('notifications', function (Blueprint $table) use ($colunas) {
                $table->dropColumn($colunas);
            });
        }
    }

    private function criarColunasDeAgrupamento(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'group_key')) {
                $table->string('group_key')->nullable()->after('data');
            }

            // Janela discretizada: floor(unix_timestamp / janela_em_segundos).
            // Participa do indice unico, o que torna o upsert de agrupamento
            // atomico sem consultar now() dentro do predicado do indice.
            if (!Schema::hasColumn('notifications', 'group_bucket')) {
                $table->unsignedBigInteger('group_bucket')->nullable()->after('group_key');
            }

            if (!Schema::hasColumn('notifications', 'group_count')) {
                $table->unsignedInteger('group_count')->default(1)->after('group_bucket');
            }

            // Momento do ultimo evento absorvido pela linha. Ordena o inbox pelo
            // fato mais recente, e nao pela criacao do agrupamento.
            if (!Schema::hasColumn('notifications', 'last_event_at')) {
                $table->timestamp('last_event_at')->nullable()->after('group_count');
            }
        });
    }

    /**
     * data nasceu como text (default do Laravel). Em jsonb o Postgres valida o
     * conteudo, armazena de forma mais compacta e permite indexar chaves internas
     * com o btree_gin que ja esta instalado.
     */
    private function converterDataParaJsonb(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $tipo = DB::selectOne(
            "SELECT data_type FROM information_schema.columns
             WHERE table_name = 'notifications' AND column_name = 'data'"
        );

        if ($tipo === null || $tipo->data_type === 'jsonb') {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE jsonb USING data::jsonb');
    }

    private function reverterDataParaText(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $tipo = DB::selectOne(
            "SELECT data_type FROM information_schema.columns
             WHERE table_name = 'notifications' AND column_name = 'data'"
        );

        if ($tipo === null || $tipo->data_type !== 'jsonb') {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');
    }

    private function criarIndices(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            $this->criarIndicesPostgres();

            return;
        }

        $this->criarIndicesPortaveis();
    }

    private function criarIndicesPostgres(): void
    {
        // Chave do upsert atomico. Parcial: so as nao lidas entram, mantendo o
        // indice pequeno e permitindo que o mesmo assunto volte a agrupar depois
        // que o usuario limpa o inbox.
        $this->executarSeIndiceAusente(self::UNIQUE_UPSERT, sprintf(
            'CREATE UNIQUE INDEX %s ON notifications
                (notifiable_type, notifiable_id, group_key, group_bucket)
                WHERE read_at IS NULL',
            self::UNIQUE_UPSERT
        ));

        // Badge do sino e listagem de nao lidas. Parcial e ordenado, entao a
        // contagem e a primeira pagina saem do indice, sem tocar a heap.
        $this->executarSeIndiceAusente(self::INDEX_NAO_LIDAS, sprintf(
            'CREATE INDEX %s ON notifications
                (notifiable_type, notifiable_id, created_at DESC)
                WHERE read_at IS NULL',
            self::INDEX_NAO_LIDAS
        ));

        // Historico completo: lidas e nao lidas do destinatario, ordem de exibicao.
        $this->executarSeIndiceAusente(self::INDEX_HISTORICO, sprintf(
            'CREATE INDEX %s ON notifications
                (notifiable_type, notifiable_id, created_at DESC)',
            self::INDEX_HISTORICO
        ));

        // Poda diaria varre por faixa de data numa tabela append-only: BRIN custa
        // uma fracao do btree em espaco e resolve o range scan.
        $this->executarSeIndiceAusente(self::INDEX_BRIN_CRIACAO, sprintf(
            'CREATE INDEX %s ON notifications USING brin (created_at)',
            self::INDEX_BRIN_CRIACAO
        ));
    }

    private function criarIndicesPortaveis(): void
    {
        // Sem indice parcial: o unico completo nao serve como chave de upsert
        // (linhas lidas ocupariam a chave), entao aqui so ha indices de leitura
        // e o agrupamento cai no caminho com lock no Redis.
        $this->addIndexIfNotExists(
            'notifications',
            ['notifiable_type', 'notifiable_id', 'group_key', 'group_bucket', 'read_at'],
            self::UNIQUE_UPSERT
        );

        $this->addIndexIfNotExists(
            'notifications',
            ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'],
            self::INDEX_NAO_LIDAS
        );

        $this->addIndexIfNotExists(
            'notifications',
            ['notifiable_type', 'notifiable_id', 'created_at'],
            self::INDEX_HISTORICO
        );
    }

    /**
     * Tabela de arquivo morto do inbox.
     *
     * Mesma tratativa do webhook_events_archive: passados N dias, a linha sai da
     * tabela operacional e vai para ca, preservando o historico para auditoria sem
     * deixar a tabela quente crescer sem limite. O inbox e o badge tocam apenas
     * notifications, que fica pequena e com indices enxutos.
     *
     * Espelha as colunas de notifications e acrescenta archived_at. Nao tem indice
     * de leitura por destinatario alem do minimo: arquivo e para consulta pontual e
     * auditoria, nao para caminho quente.
     */
    private function criarTabelaDeArquivo(): void
    {
        if (Schema::hasTable('notifications_archive')) {
            return;
        }

        Schema::create('notifications_archive', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->json('data');
            $table->string('group_key')->nullable();
            $table->unsignedBigInteger('group_bucket')->nullable();
            $table->unsignedInteger('group_count')->default(1);
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->nullable();

            // Auditoria pergunta por destinatario ou por periodo de arquivamento.
            $table->index(['notifiable_type', 'notifiable_id'], 'notif_archive_destinatario');
            $table->index('archived_at', 'notif_archive_archived_at');
        });
    }

    private function criarModoDeAtualizacaoNoUser(): void
    {
        if (Schema::hasColumn('users', 'notification_update_mode')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // auto: tenta websocket e cai para polling se o Reverb nao responder.
            // realtime e polling forcam um dos dois caminhos.
            $table->string('notification_update_mode', 20)->default('auto');
        });
    }

    /**
     * O enum do Laravel virou varchar + CHECK no Postgres e ENUM nativo no MySQL.
     * Remover a restricao permite que config/notificacoes.php seja a fonte da
     * lista de modulos, sem migration nova a cada modulo que ganhe notificacao.
     */
    private function liberarModulosDasPreferencias(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(sprintf(
                'ALTER TABLE user_notification_preferences DROP CONSTRAINT IF EXISTS %s',
                self::CHECK_MODULE
            ));

            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE user_notification_preferences MODIFY module VARCHAR(40) NOT NULL');
        }

        // sqlite nao aplica a restricao vinda do enum: nao ha o que liberar.
    }

    private function restaurarCheckDeModulos(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $lista = implode(', ', array_map(
            fn (string $modulo): string => "'{$modulo}'",
            self::MODULOS_ORIGINAIS
        ));

        DB::statement(sprintf(
            'ALTER TABLE user_notification_preferences ADD CONSTRAINT %s CHECK (module::text = ANY (ARRAY[%s]::text[]))',
            self::CHECK_MODULE,
            $lista
        ));
    }

    private function executarSeIndiceAusente(string $nome, string $sql): void
    {
        if ($this->indexExists('notifications', $nome)) {
            return;
        }

        DB::statement($sql);
    }

    private function addIndexIfNotExists(string $tabela, array $colunas, string $nome): void
    {
        if ($this->indexExists($tabela, $nome)) {
            return;
        }

        Schema::table($tabela, function (Blueprint $table) use ($colunas, $nome) {
            $table->index($colunas, $nome);
        });
    }

    private function dropIndex(string $nome): void
    {
        if (!$this->indexExists('notifications', $nome)) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS {$nome}");

            return;
        }

        Schema::table('notifications', function (Blueprint $table) use ($nome) {
            $table->dropIndex($nome);
        });
    }

    private function indexExists(string $tabela, string $nome): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        $indices = match ($driver) {
            'sqlite' => collect(DB::select("PRAGMA index_list({$tabela})"))->pluck('name'),
            'pgsql' => collect(DB::select(
                "SELECT indexname AS name FROM pg_indexes WHERE tablename = ? AND schemaname = 'public'",
                [$tabela]
            ))->pluck('name'),
            default => collect(DB::select("SHOW INDEX FROM {$tabela}"))->pluck('Key_name'),
        };

        return $indices->contains($nome);
    }
};
