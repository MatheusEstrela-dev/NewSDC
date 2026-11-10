<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Prepares emulados: pre-requisito do PgBouncer em transaction mode
|--------------------------------------------------------------------------
|
| Prepared statement tem escopo de SESSAO. Em transaction mode o pooler
| entrega cada transacao a uma conexao de servidor possivelmente diferente,
| entao o PREPARE acontece numa conexao e o EXECUTE seguinte pode cair em
| outra -- que nao o conhece. O sintoma e
|
|     SQLSTATE[26000]: prepared statement "pdo_stmt_00000001" does not exist
|
| intermitente, sob carga, e so em producao. Nao e defeito do pooler: e o que
| transaction mode faz de proposito.
|
| DEFAULT false = comportamento atual, inalterado. A flag existe para que o
| dia de ligar o PgBouncer seja uma variavel de ambiente e nao um deploy de
| codigo. Ligar TAMBEM muda o bind de parametros (passa a ser feito pelo
| cliente, com escaping do driver), entao vale medir junto.
|
| ALTERNATIVA PREFERIVEL: PgBouncer 1.21+ com max_prepared_statements > 0,
| que rastreia os prepares e os refaz na conexao nova. Ai esta flag continua
| false e nao se perde nada.
*/
$emularPrepares = filter_var(env('DB_EMULATE_PREPARES', false), FILTER_VALIDATE_BOOLEAN);

$pgConnectTimeout = max(1, (int) env('DB_CONNECT_TIMEOUT', 2));

// Tabelas de log vivem no schema `log` do proprio sdc (migration
// move_tabelas_de_log_para_schema_log). `log` entra sempre DEPOIS do schema
// principal: nome sem schema continua resolvendo, e tabela nova continua
// nascendo em public. Nenhum codigo precisa qualificar `log.`.
$searchPathComLog = implode(',', array_values(array_unique(array_merge(
    array_filter(array_map('trim', explode(',', (string) env('DB_SEARCH_PATH', 'public')))),
    ['log'],
))));

$readHosts = array_values(array_filter(array_map('trim', explode(',', (string) env('DB_READ_HOSTS', '')))));
$replicaRouting = filter_var(env('DB_READ_ENABLED', false), FILTER_VALIDATE_BOOLEAN) && $readHosts !== [];
$readWriteConfig = $replicaRouting ? [
    'replica_routing' => true,
    'sticky' => true,
    'read' => [
        'host' => $readHosts,
        'read_only' => true,
        'port' => env('DB_READ_PORT', env('DB_PORT', '5432')),
        'application_name' => env('APP_NAME', 'sdc-laravel').'-reader',
    ],
    'write' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
    ],
] : [];

// Conexao de um banco dedicado (geo, treinamento): host, porta e credenciais
// por prefixo de env com heranca de DB_* (mesmo servidor por padrao); a
// database NUNCA herda, para nao gravar dominio dedicado dentro do sdc. Sem
// 'url' de proposito: DATABASE_URL aponta para o sdc e venceria os overrides.
// Tabelas sempre com schema qualificado no codigo; search_path so public
// (extensoes, ex.: PostGIS).
$bancoDedicado = static fn (string $prefixo, string $database, string $sufixoApp, array $extra = []): array => array_replace([
    'driver' => 'pgsql',
    'host' => env("{$prefixo}_DB_HOST", env('DB_HOST', '127.0.0.1')),
    'port' => env("{$prefixo}_DB_PORT", env('DB_PORT', '5432')),
    'database' => env("{$prefixo}_DB_DATABASE", $database),
    'username' => env("{$prefixo}_DB_USERNAME", env('DB_USERNAME', 'sdc')),
    'password' => env("{$prefixo}_DB_PASSWORD", env('DB_PASSWORD', '')),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => env("{$prefixo}_DB_SSLMODE", env('DB_SSLMODE', 'prefer')),
    'sslrootcert' => env("{$prefixo}_DB_SSL_CA", env('DB_SSL_CA')) ?: null,
    'application_name' => env('APP_NAME', 'sdc-laravel').'-'.$sufixoApp,
    'timezone' => env('DB_TIMEZONE', 'America/Sao_Paulo'),
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => $emularPrepares,
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_TIMEOUT => $pgConnectTimeout,
    ],
], $extra);

// Leitura do geo nas replicas: so quando o banco geo esta no mesmo cluster do
// sdc (as replicas fisicas carregam todos os bancos). Servidor proprio liga
// com GEO_DB_READ_HOSTS.
// Mesmo cluster = host do geo igual ao do sdc (ou ausente). Nao basta checar
// GEO_DB_HOST vazio: os composes de homolog/HA o definem apontando para o
// mesmo cluster, e o geo perderia as replicas.
$geoMesmoCluster = (string) env('GEO_DB_HOST', env('DB_HOST', '127.0.0.1')) === (string) env('DB_HOST', '127.0.0.1');
$geoReadHosts = array_values(array_filter(array_map('trim', explode(',', (string) env(
    'GEO_DB_READ_HOSTS',
    $geoMesmoCluster ? env('DB_READ_HOSTS', '') : '',
)))));
$geoReplicaRouting = filter_var(env('GEO_DB_READ_ENABLED', env('DB_READ_ENABLED', false)), FILTER_VALIDATE_BOOLEAN)
    && $geoReadHosts !== [];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'pgsql'),

    /*
    |--------------------------------------------------------------------------
    | Conexao de log
    |--------------------------------------------------------------------------
    |
    | Chave unica de troca para todos os tipos de log (ver App\Support\Database\
    | ConexaoDeLog). Sem DB_LOG_CONNECTION resolve para a conexao default: o log
    | segue na mesma sessao das operacoes, exatamente como antes. Com
    | DB_LOG_CONNECTION=pgsql_log o log ganha sessao e pool proprios. Rollback e
    | tirar a variavel e reiniciar.
    |
    */

    'log_connection' => env('DB_LOG_CONNECTION', env('DB_CONNECTION', 'pgsql')),

    // Mesmas regras da conexao de log (ver App\Support\Database\ConexaoSeparada):
    // sem a env, o dominio segue na conexao default; com ela, banco proprio.
    'geo_connection' => env('DB_GEO_CONNECTION', env('DB_CONNECTION', 'pgsql')),
    'treinamento_connection' => env('DB_TREINAMENTO_CONNECTION', env('DB_CONNECTION', 'pgsql')),

    // Separacao C1. Duas chaves DIFERENTES das acima: SEM fallback para a
    // conexao default. Sem a env o recurso fica DESLIGADO (entrada sincrona,
    // saida ao vivo, como hoje) e nada deste dominio pode cair no sdc.
    //  - powerbi_connection: banco sdc_powerbi (tabelas bi_* e controle), dado
    //    derivado e reconstruivel, em instancia Postgres PROPRIA fora do
    //    Patroni (ver App\Support\Database\ConexaoPowerBi).
    //  - entrada_connection: banco sdc_entrada (caixa de entrada assincrona),
    //    NO CLUSTER do sdc para ter durabilidade sincrona: a mensagem aceita
    //    nao pode se perder (ver App\Support\Database\ConexaoEntrada).
    'powerbi_connection' => env('DB_POWERBI_CONNECTION'),
    'entrada_connection' => env('DB_ENTRADA_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Pools por coroutine (Octane/Swoole com hooks)
    |--------------------------------------------------------------------------
    |
    | Um pool PROPRIO por conexao listada, por worker. Conexao fora desta lista
    | cai no DatabaseManager padrao (um PDO por processo). So vale com
    | OCTANE_HOOK_FLAGS_ENABLED; com hooks desligados nada aqui e instanciado.
    |
    | TETO DE CONEXOES: a soma de todos os pools x workers x instancias entra no
    | guardrail do entrypoint.sh contra PG_MAX_CONNECTIONS (mesmo servidor).
    |
    */

    'coroutine_pools' => [
        'pgsql' => [
            'size' => (int) env('SWOOLE_PG_POOL_SIZE', 16),
            'timeout' => (float) env('SWOOLE_PG_POOL_TIMEOUT', 3.0),
            'warm' => true,
            // Pools das replicas; so com replica_routing ligado na conexao.
            'read_size' => (int) env('SWOOLE_PG_READ_POOL_SIZE', 2),
        ],
        'pgsql_log' => [
            'size' => (int) env('SWOOLE_PG_LOG_POOL_SIZE', 4),
            // Curto de proposito: request esperando pool de log segura o PDO
            // critico durante a espera. Esgotou, o log vai para a fila.
            'timeout' => (float) env('SWOOLE_PG_LOG_POOL_TIMEOUT', 0.5),
            'warm' => filter_var(env('SWOOLE_PG_LOG_POOL_WARM', false), FILTER_VALIDATE_BOOLEAN),
        ],
        // Mapas e ingestao: leitura de GeoJSON pode esperar um pouco mais que log.
        'pgsql_geo' => [
            'size' => (int) env('SWOOLE_PG_GEO_POOL_SIZE', 4),
            'timeout' => (float) env('SWOOLE_PG_GEO_POOL_TIMEOUT', 1.0),
            'warm' => false,
        ],
        'pgsql_geo_read' => [
            'size' => (int) env('SWOOLE_PG_GEO_READ_POOL_SIZE', 4),
            'timeout' => (float) env('SWOOLE_PG_GEO_POOL_TIMEOUT', 1.0),
            'warm' => false,
            'read_size' => (int) env('SWOOLE_PG_GEO_REPLICA_POOL_SIZE', 1),
        ],
        // Login e catalogo do cidadao sao request critico: nao falhar rapido demais.
        'pgsql_treinamento' => [
            'size' => (int) env('SWOOLE_PG_TREINAMENTO_POOL_SIZE', 4),
            'timeout' => (float) env('SWOOLE_PG_TREINAMENTO_POOL_TIMEOUT', 2.0),
            'warm' => false,
        ],
        // Caixa de entrada (sdc_entrada, no cluster): o aceite da API passa
        // por aqui, mas e INSERT curto; esgotou, a API responde erro rapido.
        'pgsql_entrada' => [
            'size' => (int) env('SWOOLE_PG_ENTRADA_POOL_SIZE', 4),
            'timeout' => (float) env('SWOOLE_PG_ENTRADA_POOL_TIMEOUT', 2.0),
            'warm' => false,
        ],
        // Materializacao no sdc_powerbi (instancia propria): carga fora do site, falha rapida.
        'pgsql_powerbi' => [
            'size' => (int) env('SWOOLE_PG_POWERBI_POOL_SIZE', 4),
            'timeout' => (float) env('SWOOLE_PG_POWERBI_POOL_TIMEOUT', 1.0),
            'warm' => false,
        ],
        // Leitura da API de BI no mesmo servidor do powerbi: pode esperar um pouco mais.
        'pgsql_powerbi_read' => [
            'size' => (int) env('SWOOLE_PG_POWERBI_READ_POOL_SIZE', 4),
            'timeout' => (float) env('SWOOLE_PG_POWERBI_READ_POOL_TIMEOUT', 2.0),
            'warm' => false,
        ],
        // Origem do materializador (sdc no membro de BI): poucas sessoes, longas.
        'pgsql_fonte_bi' => [
            'size' => (int) env('SWOOLE_PG_FONTE_BI_POOL_SIZE', 2),
            'timeout' => (float) env('SWOOLE_PG_FONTE_BI_POOL_TIMEOUT', 5.0),
            'warm' => false,
        ],
        'ranking' => [
            'size' => (int) env('SWOOLE_PG_RANKING_POOL_SIZE', 4),
            'timeout' => (float) env('SWOOLE_PG_RANKING_POOL_TIMEOUT', 2.0),
            'warm' => false,
        ],
        'ranking_read' => [
            'size' => (int) env('SWOOLE_PG_RANKING_READ_POOL_SIZE', 3),
            'timeout' => (float) env('SWOOLE_PG_RANKING_POOL_TIMEOUT', 2.0),
            'warm' => false,
        ],
        'ranking_source_ro' => [
            'size' => (int) env('SWOOLE_PG_RANKING_SOURCE_POOL_SIZE', 2),
            'timeout' => (float) env('SWOOLE_PG_RANKING_POOL_TIMEOUT', 2.0),
            'warm' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bancos dedicados
    |--------------------------------------------------------------------------
    |
    | Bancos fora do sdc, provisionados por `db:provisionar` e migrados no boot
    | (entrypoint.sh) com a pasta propria de migrations. 'chave' e a chave de
    | troca acima: o banco so e usado quando ela aponta para a conexao; null =
    | sempre separado (ranking). Roles e senhas ficam com o DBA, fora do codigo.
    |
    */

    'bancos_dedicados' => [
        'pgsql_geo' => [
            'chave' => 'geo_connection',
            'extensoes' => ['postgis'],
            'migrations' => 'database/migrations/geoespacial',
        ],
        'pgsql_treinamento' => [
            'chave' => 'treinamento_connection',
            'extensoes' => [],
            'migrations' => 'database/migrations/treinamento',
        ],
        'pgsql_entrada' => [
            'chave' => 'entrada_connection',
            'extensoes' => [],
            'migrations' => 'database/migrations/entrada',
        ],
        'pgsql_powerbi' => [
            'chave' => 'powerbi_connection',
            'extensoes' => [],
            'migrations' => 'database/migrations/powerbi',
        ],
        'ranking' => [
            'chave' => null,
            'extensoes' => [],
            'migrations' => 'database/migrations/ranking',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // Conexão para integração/migração do banco legado (porta 3306)
        'legacy' => [
            'driver' => 'mysql',
            'host' => env('DB_LEGACY_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_LEGACY_PORT', '3306'),
            'database' => env('DB_LEGACY_DATABASE', ''),
            'username' => env('DB_LEGACY_USERNAME', ''),
            'password' => env('DB_LEGACY_PASSWORD', ''),
            'unix_socket' => env('DB_LEGACY_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'cedec_demanda_mysql' => [
            'driver' => 'mysql',
            'host' => env('CEDEC_DEMANDA_DB_HOST', '127.0.0.1'),
            'port' => env('CEDEC_DEMANDA_DB_PORT', '3306'),
            'database' => env('CEDEC_DEMANDA_DB_DATABASE', ''),
            'username' => env('CEDEC_DEMANDA_DB_USERNAME', ''),
            'password' => env('CEDEC_DEMANDA_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ],

        // Somente leitura, consumida por cisterna:extrair-legado. Nao ha
        // migration nem model apontando para ela.
        'legado_cisterna_mysql' => [
            'driver' => 'mysql',
            'host' => env('LEGADO_CISTERNA_DB_HOST', '127.0.0.1'),
            'port' => env('LEGADO_CISTERNA_DB_PORT', '3306'),
            'database' => env('LEGADO_CISTERNA_DB_DATABASE', 'dbsdc'),
            'username' => env('LEGADO_CISTERNA_DB_USERNAME', 'root'),
            'password' => env('LEGADO_CISTERNA_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('LEGADO_CISTERNA_DB_SSL_CA'),
            ]) : [],
        ],

        // Somente leitura, consumida pelo ETL de prefeituras do Cedec
        // (cedec:importar-prefeituras / PrefeituraService::migrarLegado). Dedicada
        // e SEPARADA da conexao 'legacy' acima: aquela aponta para dbsdc (legado
        // sdc/Laravel), esta aponta para gestaocedec_local (legado gestaocedec, PHP
        // puro). Os dois bancos tem tabelas de MESMO NOME (cedec_municipio,
        // cedec_prefeitura) com colunas incompativeis -- dbsdc.cedec_municipio nao
        // tem sequer as colunas email e prefeito. NAO reutilizar 'legacy' aqui: ela
        // e compartilhada por sete consumidores em Compdec, Pmda e AjudaHumanitaria.
        // Nao ha migration nem model apontando para ela.
        'legado_gestaocedec' => [
            'driver' => 'mysql',
            'host' => env('DB_LEGADO_GESTAOCEDEC_HOST', '127.0.0.1'),
            'port' => env('DB_LEGADO_GESTAOCEDEC_PORT', '3306'),
            'database' => env('DB_LEGADO_GESTAOCEDEC_DATABASE', 'gestaocedec_local'),
            'username' => env('DB_LEGADO_GESTAOCEDEC_USERNAME', 'root'),
            'password' => env('DB_LEGADO_GESTAOCEDEC_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('DB_LEGADO_GESTAOCEDEC_SSL_CA'),
            ]) : [],
        ],

        // Somente leitura, consumida por demandas:importar-legado. MySQL do
        // cedec-demanda (chamados, comentarios, anexos, historico). Nenhuma
        // migration nem model aponta para ela.
        'cedec_demanda_legacy' => [
            'driver' => 'mysql',
            'host' => env('DB_CEDEC_DEMANDA_HOST', '127.0.0.1'),
            'port' => env('DB_CEDEC_DEMANDA_PORT', '3306'),
            'database' => env('DB_CEDEC_DEMANDA_DATABASE', 'cedec_demanda'),
            'username' => env('DB_CEDEC_DEMANDA_USERNAME', 'root'),
            'password' => env('DB_CEDEC_DEMANDA_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('DB_CEDEC_DEMANDA_SSL_CA'),
            ]) : [],
        ],

        // Conexão para carga/queries otimizadas (leitura intensiva, ETL, BI)
        // Aponta para réplica de leitura ou banco dedicado a carga de dados
        'carga' => [
            'driver' => 'mysql',
            'host' => env('DB_CARGA_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_CARGA_PORT', '3306'),
            'database' => env('DB_CARGA_DATABASE', env('DB_DATABASE', 'forge')),
            'username' => env('DB_CARGA_USERNAME', env('DB_USERNAME', 'forge')),
            'password' => env('DB_CARGA_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('DB_CARGA_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false, // modo relaxado para queries analíticas/BI
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // Conexão de tenancy: database por tenant (configurada em runtime pelo SetTenant middleware)
        'tenancy' => [
            'driver' => 'mysql',
            'host' => env('DB_TENANCY_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_TENANCY_PORT', '3306'),
            'database' => env('DB_TENANCY_DATABASE', ''),
            'username' => env('DB_TENANCY_USERNAME', env('DB_USERNAME', 'forge')),
            'password' => env('DB_TENANCY_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('DB_TENANCY_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // Conexao principal Postgres - usada em producao (Azure Flexible Server PG17)
        // e em dev (db_ai container Docker com Citus + pgvector + PostGIS).
        'pgsql' => [
            'driver' => 'pgsql',
            ...$readWriteConfig,
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'sdc'),
            'username' => env('DB_USERNAME', 'sdc'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => $searchPathComLog,
            'sslmode' => env('DB_SSLMODE', 'prefer'),
            'sslrootcert' => env('DB_SSL_CA') ?: null,
            'application_name' => env('APP_NAME', 'sdc-laravel'),
            'timezone' => env('DB_TIMEZONE', 'America/Sao_Paulo'),
            // SwoolePdoPool (App\Support\Database\SwoolePdoPool) usa esta conexao.
            // TETO DE CONEXOES (restricao dura, sobretudo on-premise):
            //   SWOOLE_PG_POOL_SIZE x OCTANE_WORKERS x instancias <= max_connections - reserva
            // Azure: max_connections alto -> default 16 ok.
            // On-premise modesto: medir max_connections do servidor e reduzir
            //   SWOOLE_PG_POOL_SIZE (ex.: 8) para nao esgotar o Postgres.
            'options' => [
                // false por padrao; true so ao ligar o PgBouncer. Ver o bloco no
                // topo deste arquivo.
                PDO::ATTR_EMULATE_PREPARES => $emularPrepares,
                // Default false: PDO persistente sob Octane/Swoole reusa a MESMA
                // conexao entre requests do worker e vaza estado/transacao.
                // Ligar apenas via env em runtime nao-residente (ex.: FPM legado).
                PDO::ATTR_PERSISTENT => (bool) env('DB_PERSISTENT', false),
                PDO::ATTR_TIMEOUT => $pgConnectTimeout,
            ],
        ],

        // Ranking possui database propria. Credenciais nunca herdam o usuario
        // operacional; sem configuracao explicita a conexao deve falhar.
        'ranking' => [
            'driver' => 'pgsql',
            'host' => env('RANKING_DB_HOST', '127.0.0.1'),
            'port' => env('RANKING_DB_PORT', '5432'),
            'database' => env('RANKING_DB_DATABASE', 'sdc_ranking'),
            'username' => env('RANKING_DB_USERNAME', ''),
            'password' => env('RANKING_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'timezone' => 'UTC',
            'sslmode' => env('RANKING_DB_SSLMODE', 'prefer'),
            'application_name' => 'sdc-ranking-writer',
            'options' => [PDO::ATTR_PERSISTENT => false, PDO::ATTR_EMULATE_PREPARES => $emularPrepares, PDO::ATTR_TIMEOUT => $pgConnectTimeout],
        ],

        // Usuario leitor exclusivo das projecoes. Conceder SELECT no destino.
        'ranking_read' => [
            'driver' => 'pgsql',
            'read_only' => true,
            'host' => env('RANKING_READ_HOST', env('RANKING_DB_HOST', '127.0.0.1')),
            'port' => env('RANKING_READ_PORT', env('RANKING_DB_PORT', '5432')),
            'database' => env('RANKING_DB_DATABASE', 'sdc_ranking'),
            'username' => env('RANKING_READ_USERNAME', ''),
            'password' => env('RANKING_READ_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'timezone' => 'UTC',
            'sslmode' => env('RANKING_DB_SSLMODE', 'prefer'),
            'application_name' => 'sdc-ranking-reader',
            'options' => [PDO::ATTR_PERSISTENT => false, PDO::ATTR_EMULATE_PREPARES => $emularPrepares, PDO::ATTR_TIMEOUT => $pgConnectTimeout],
        ],

        // Origem somente leitura, preferencialmente replica. O DBA deve
        // conceder apenas SELECT; nome da conexao nao substitui privilegios.
        // Nao usar o OutboxDispatcher aqui: ele grava na origem.
        'ranking_source_ro' => [
            'driver' => 'pgsql',
            'read_only' => true,
            'host' => env('RANKING_SOURCE_HOST', '127.0.0.1'),
            'port' => env('RANKING_SOURCE_PORT', '5432'),
            'database' => env('RANKING_SOURCE_DATABASE', 'sdc'),
            'username' => env('RANKING_SOURCE_USERNAME', ''),
            'password' => env('RANKING_SOURCE_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            // Le audit_logs (logins), que vive no schema log.
            'search_path' => 'public,log',
            'timezone' => 'UTC',
            'sslmode' => env('RANKING_SOURCE_SSLMODE', 'prefer'),
            'application_name' => 'sdc-ranking-source-ro',
            'options' => [PDO::ATTR_PERSISTENT => false, PDO::ATTR_EMULATE_PREPARES => $emularPrepares, PDO::ATTR_TIMEOUT => $pgConnectTimeout],
        ],

        // Conexao de TODOS os tipos de log: notificacoes, logs tecnicos (failed_jobs,
        // webhooks, traces, IA, ETL), auditoria e historico dos modulos. Pool e
        // sessao proprios: pico de log nao consome conexao das operacoes dos
        // modulos. Substitui a antiga 'pgsql_webhook', que isolava so webhook.
        //
        // So entra em uso com DB_LOG_CONNECTION=pgsql_log (ver 'log_connection').
        // Por padrao herda servidor, database e credenciais de DB_*: as tabelas
        // continuam no `sdc`, muda apenas a conexao. LOG_DB_HOST/LOG_DB_DATABASE
        // sao o caminho para levar o log a outro servidor no futuro.
        //
        // Sem 'url' de proposito: DATABASE_URL aponta para o `sdc` e venceria os
        // overrides LOG_DB_*. Em producao, LOG_DB_USERNAME e uma role propria
        // (sdc_log_app) com CONNECTION LIMIT e escrita so nas tabelas de log.
        'pgsql_log' => [
            'driver' => 'pgsql',
            'host' => env('LOG_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('LOG_DB_PORT', env('DB_PORT', '5432')),
            'database' => env('LOG_DB_DATABASE', env('DB_DATABASE', 'sdc')),
            'username' => env('LOG_DB_USERNAME', env('DB_USERNAME', 'sdc')),
            'password' => env('LOG_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => $searchPathComLog,
            'sslmode' => env('LOG_DB_SSLMODE', env('DB_SSLMODE', 'prefer')),
            'sslrootcert' => env('LOG_DB_SSL_CA', env('DB_SSL_CA')) ?: null,
            'application_name' => env('APP_NAME', 'sdc-laravel').'-log',
            'timezone' => env('DB_TIMEZONE', 'America/Sao_Paulo'),
            'options' => [
                // false por padrao; true so ao ligar o PgBouncer. Ver o bloco no
                // topo deste arquivo.
                PDO::ATTR_EMULATE_PREPARES => $emularPrepares,
                // Mesmo racional da conexao pgsql: persistente e inseguro sob Octane.
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_TIMEOUT => $pgConnectTimeout,
            ],
        ],

        // Conexao usada pelas ferramentas de IA (core/IA) para consultar o banco
        // OPERACIONAL -- decretos, processos, municipios. Nao e banco de
        // embeddings, apesar do sufixo '-ai' no application_name; o sufixo existe
        // so para separar essas sessoes das do trafego web no pg_stat_activity.
        //
        // NAO E REPLICA DE LEITURA. O nome anterior era 'pgsql_read', que
        // convidava exatamente a esse engano: quem apontasse DB_PGSQL_HOST para um
        // standby faria as ferramentas de IA lerem com atraso de replicacao, e quem
        // seguisse o comentario antigo e setasse DB_PGSQL_DATABASE=sdc_ai as faria
        // consultar um banco onde decreto nenhum existe.
        //
        // Com DB_PGSQL_* nao definido -- que e o caso em dev, homologacao e
        // producao hoje -- ela resolve para o MESMO host e o MESMO banco da
        // conexao 'pgsql'. As variaveis existem para o dia em que a carga de IA
        // precisar de instancia propria.
        //
        // Split de leitura de verdade nao se faz aqui: seria um bloco
        // 'read'/'write' dentro da conexao 'pgsql', com 'sticky'.
        // Banco sdc_geoespacial (medalhao por dominio). Escrita: ingestao,
        // upsert silver, REFRESH das matviews. So usado com DB_GEO_CONNECTION.
        'pgsql_geo' => $bancoDedicado('GEO', 'sdc_geoespacial', 'geo'),

        // SELECT dos mapas. Role de leitura propria em producao
        // (GEO_DB_READ_USERNAME); nas replicas quando houver.
        'pgsql_geo_read' => $bancoDedicado('GEO', 'sdc_geoespacial', 'geo-read', array_filter([
            'read_only' => true,
            'username' => env('GEO_DB_READ_USERNAME', env('GEO_DB_USERNAME', env('DB_USERNAME', 'sdc'))),
            'password' => env('GEO_DB_READ_PASSWORD', env('GEO_DB_PASSWORD', env('DB_PASSWORD', ''))),
            'replica_routing' => $geoReplicaRouting ?: null,
            'sticky' => $geoReplicaRouting ?: null,
            'read' => $geoReplicaRouting ? [
                'host' => $geoReadHosts,
                'read_only' => true,
                'port' => env('GEO_DB_READ_PORT', env('DB_READ_PORT', env('DB_PORT', '5432'))),
                'application_name' => env('APP_NAME', 'sdc-laravel').'-geo-reader',
            ] : null,
            'write' => $geoReplicaRouting ? [
                'host' => env('GEO_DB_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => env('GEO_DB_PORT', env('DB_PORT', '5432')),
            ] : null,
        ], static fn ($valor): bool => $valor !== null)),

        // Banco sdc_treinamento (schemas cadastro e treinamento): dado pessoal
        // de cidadao (LGPD). Em producao role propria sdc_treinamento_app, sem
        // acesso ao sdc. So usado com DB_TREINAMENTO_CONNECTION.
        'pgsql_treinamento' => $bancoDedicado('TREINAMENTO', 'sdc_treinamento', 'treinamento'),

        // Banco sdc_entrada (schema entrada): caixa de entrada assincrona, no
        // cluster do sdc (mesmo host/porta do lider por padrao, durabilidade
        // sincrona). Usa uuidv7() nativo do PG18, sem extensao. So usado com
        // DB_ENTRADA_CONNECTION=pgsql_entrada.
        'pgsql_entrada' => $bancoDedicado('ENTRADA', 'sdc_entrada', 'entrada'),

        // Banco sdc_powerbi (bi_* e controle): dado derivado em instancia
        // Postgres PROPRIA (servico db_powerbi, fora do Patroni), apontada por
        // POWERBI_DB_HOST/PORT. So usado com DB_POWERBI_CONNECTION=pgsql_powerbi.
        'pgsql_powerbi' => $bancoDedicado('POWERBI', 'sdc_powerbi', 'powerbi'),

        // Leitura do sdc_powerbi. Escrita e leitura ficam no MESMO servidor
        // (instancia propria, sem replica): sem POWERBI_DB_READ_HOST herda host
        // e porta do powerbi. De proposito SEM replica_routing e sem read/write.
        'pgsql_powerbi_read' => $bancoDedicado('POWERBI', 'sdc_powerbi', 'powerbi-read', [
            'read_only' => true,
            'host' => env('POWERBI_DB_READ_HOST', env('POWERBI_DB_HOST', env('DB_HOST', '127.0.0.1'))),
            'port' => env('POWERBI_DB_READ_PORT', env('POWERBI_DB_PORT', env('DB_PORT', '5432'))),
            'username' => env('POWERBI_DB_READ_USERNAME', env('POWERBI_DB_USERNAME', env('DB_USERNAME', 'sdc'))),
            'password' => env('POWERBI_DB_READ_PASSWORD', env('POWERBI_DB_PASSWORD', env('DB_PASSWORD', ''))),
        ]),

        // Leitura do SDC (database = DB_DATABASE) no membro de BI, usada so pelo
        // materializador. Somente leitura por role; sem replica_routing pelo
        // mesmo motivo da conexao acima. Sem 'url': DATABASE_URL aponta para o
        // lider e venceria o host.
        'pgsql_fonte_bi' => [
            'driver' => 'pgsql',
            'read_only' => true,
            'host' => env('FONTE_BI_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('FONTE_BI_DB_PORT', env('FONTE_BI_DB_HOST') ? '6436' : env('DB_PORT', '5432')),
            'database' => env('DB_DATABASE', 'sdc'),
            'username' => env('FONTE_BI_DB_USERNAME', env('DB_USERNAME', 'sdc')),
            'password' => env('FONTE_BI_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => $searchPathComLog,
            'sslmode' => env('FONTE_BI_DB_SSLMODE', env('DB_SSLMODE', 'prefer')),
            'sslrootcert' => env('DB_SSL_CA') ?: null,
            'application_name' => env('APP_NAME', 'sdc-laravel').'-fonte-bi',
            'timezone' => env('DB_TIMEZONE', 'America/Sao_Paulo'),
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => $emularPrepares,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_TIMEOUT => $pgConnectTimeout,
            ],
        ],

        'pgsql_ai' => [
            'driver' => 'pgsql',
            'host' => env('DB_PGSQL_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_PGSQL_PORT', env('DB_PORT', '5432')),
            'database' => env('DB_PGSQL_DATABASE', env('DB_DATABASE', 'sdc_ai')),
            'username' => env('DB_PGSQL_USERNAME', env('DB_USERNAME', 'sdc')),
            'password' => env('DB_PGSQL_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => env('DB_PGSQL_SEARCH_PATH', 'public'),
            'sslmode' => env('DB_PGSQL_SSLMODE', env('DB_SSLMODE', 'prefer')),
            'sslrootcert' => env('DB_SSL_CA') ?: null,
            'application_name' => env('APP_NAME', 'sdc-laravel') . '-ai',
            'options' => [
                // false por padrao; true so ao ligar o PgBouncer. Ver o bloco no
                // topo deste arquivo.
                PDO::ATTR_EMULATE_PREPARES => $emularPrepares,
                // Mesmo racional da conexao pgsql: persistente e inseguro sob Octane.
                PDO::ATTR_PERSISTENT => (bool) env('DB_PERSISTENT', false),
                PDO::ATTR_TIMEOUT => $pgConnectTimeout,
            ],
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'scheme' => env('REDIS_SCHEME', 'tcp'),
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
