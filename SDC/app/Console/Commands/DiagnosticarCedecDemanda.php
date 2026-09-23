<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnosticarCedecDemanda extends Command
{
    protected $signature = 'cedec-demanda:diagnosticar {--connection=cedec_demanda_mysql}';

    protected $description = 'Lê contagens e colunas do banco legado sem alterar registros';

    private const TABLES = [
        'users', 'chamados', 'chamados_categorias', 'assuntos', 'comentarios',
        'anexos', 'historico_chamados', 'inventario_categorias', 'estacoes',
        'inventario_equipamentos', 'inventario_movimentacoes',
        'movimentacao_equipamentos', 'cadastro_acessos',
    ];

    public function handle(): int
    {
        $connection = (string) $this->option('connection');
        if ($connection !== 'cedec_demanda_mysql') {
            $this->error('Use a conexão dedicada cedec_demanda_mysql.');
            return self::FAILURE;
        }

        $database = (string) config('database.connections.cedec_demanda_mysql.database');
        if ($database === '') {
            $this->error('Configure CEDEC_DEMANDA_DB_DATABASE e credenciais de leitura.');
            return self::FAILURE;
        }

        $rows = [];
        foreach (self::TABLES as $table) {
            if (! Schema::connection($connection)->hasTable($table)) {
                $rows[] = [$table, 'ausente', '—'];
                continue;
            }
            $rows[] = [
                $table,
                (string) DB::connection($connection)->table($table)->count(),
                implode(', ', Schema::connection($connection)->getColumnListing($table)),
            ];
        }
        $this->table(['Tabela', 'Registros', 'Colunas'], $rows);

        return self::SUCCESS;
    }
}
