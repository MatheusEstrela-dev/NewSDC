<?php

declare(strict_types=1);

use App\Modules\Notificacoes\Retencao\InvalidaInboxAposPoda;

/*
|--------------------------------------------------------------------------
| Retencao dos logs (log:podar)
|--------------------------------------------------------------------------
|
| Poda unica, diaria, das notificacoes e dos logs tecnicos que vivem na conexao
| de log. Passados os dias, a linha e APAGADA -- nao ha arquivo. A excecao e a
| notificacao nao lida: nunca e apagada, por mais antiga que seja.
|
| Registro declarativo, sem closures, para sobreviver ao config:cache. Por
| tabela:
|  - coluna: data que conta a idade;
|  - dias: sobrescreve o padrao; null = tabela sem poda;
|  - nao_nulos / nulos: colunas que precisam estar preenchidas / vazias;
|  - em: [coluna => valores permitidos];
|  - chave: chave primaria (padrao id);
|  - retornar + apos: colunas devolvidas pelo DELETE ... RETURNING e a classe
|    (App\Support\Database\Log\AposPoda) que recebe essas linhas a cada lote.
|
| Auditoria e historico dos modulos tambem vivem na conexao de log, mas nao
| estao aqui: nao sao podados.
|
*/

return [

    // ?: e nao ??: LOG_RETENCAO_DIAS vazia no .env viraria 0 e apagaria tudo.
    'dias' => (int) (env('LOG_RETENCAO_DIAS') ?: 90),

    // Linhas por DELETE. Cada lote e um autocommit curto: segura pouco lock e
    // gera WAL em degraus, sem atrasar replica.
    'lote' => 1000,

    // Respiro entre lotes, para a poda nao disputar disco com o trafego.
    'pausa_ms' => 50,

    // Teto de cada DELETE. Acima do statement_timeout da role de log (3s),
    // porque tabela sem indice na coluna de idade faz varredura por lote.
    'statement_timeout_ms' => 30000,

    'tabelas' => [

        // Nao lida nunca sai. O sino e o badge mudam: o apos invalida a versao
        // do inbox e o contador de quem perdeu linhas.
        'notifications' => [
            'coluna' => 'created_at',
            'nao_nulos' => ['read_at'],
            'retornar' => ['notifiable_type', 'notifiable_id'],
            'apos' => InvalidaInboxAposPoda::class,
        ],

        // O que o botao Limpar do sino arquivou.
        'notifications_archive' => [
            'coluna' => 'archived_at',
        ],

        'failed_jobs' => [
            'coluna' => 'failed_at',
        ],

        'webhook_logs' => [
            'coluna' => 'created_at',
        ],

        // So evento encerrado: pendente, em processamento e falho ainda podem
        // ser retentados.
        'webhook_events' => [
            'coluna' => 'created_at',
            'em' => ['status' => ['completed', 'dead_letter']],
        ],

        'webhook_events_archive' => [
            'coluna' => 'archived_at',
        ],

        // Trace com export ainda no disco fica: apaga-lo orfanaria o arquivo.
        // O exports:cleanup limpa result_path depois de 7 dias.
        'request_traces' => [
            'coluna' => 'created_at',
            'nulos' => ['result_path'],
        ],

        'ai_execution_logs' => [
            'coluna' => 'created_at',
        ],

        // A "ultima carga" aparece na tela da prefeitura (CedecPrefeituraService):
        // o log de ETL e a unica fonte dela.
        'compdec_etl_log' => [
            'coluna' => 'created_at',
            'dias' => null,
        ],

        'cisterna_etl_log' => [
            'coluna' => 'created_at',
            'dias' => null,
        ],

    ],

];
