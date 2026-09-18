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
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'sdc'),
            'username' => env('DB_USERNAME', 'sdc'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => env('DB_SEARCH_PATH', 'public'),
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
            ],
        ],

        // Conexao isolada para jobs de webhook (ProcessWebhook, ProcessInboundWebhook).
        // Aponta para o mesmo host do pgsql, mas com application_name distinto e
        // sem ATTR_PERSISTENT — jobs nao reusam conexao entre execucoes.
        // Combinado com semaforo proprio (DB_MAX_CONCURRENT_WEBHOOK), isola pico
        // de webhooks externos do pool da web.
        'pgsql_webhook' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'sdc'),
            'username' => env('DB_USERNAME', 'sdc'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => env('DB_SEARCH_PATH', 'public'),
            'sslmode' => env('DB_SSLMODE', 'prefer'),
            'sslrootcert' => env('DB_SSL_CA') ?: null,
            'application_name' => env('APP_NAME', 'sdc-laravel').'-webhook',
            'timezone' => env('DB_TIMEZONE', 'America/Sao_Paulo'),
            'options' => [
                // false por padrao; true so ao ligar o PgBouncer. Ver o bloco no
                // topo deste arquivo.
                PDO::ATTR_EMULATE_PREPARES => $emularPrepares,
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
