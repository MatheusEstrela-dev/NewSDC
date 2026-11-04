<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fase 1 do banco sdc_treinamento: organiza, dentro do proprio sdc, as tabelas
 * do Treinamento nos schemas que o banco dedicado vai ter, para o codigo usar
 * nomes qualificados (cadastro.*, treinamento.*) com a chave ligada ou desligada.
 *
 * - cadastro: login e dado pessoal do cidadao (LGPD).
 * - treinamento: treinamentos, modulos, inscricoes, frequencias, certificados.
 *
 * So metadado: SET SCHEMA e instantaneo e leva indices e sequencias junto. As 5
 * FKs para users saem porque users fica no sdc e a tabela vai para outro banco:
 * a coluna segue bigint indexado (os indices *_fkidx ja existem) e o nome do
 * usuario e buscado numa segunda consulta. Reversivel e idempotente.
 *
 * A DDL do banco novo vive em database/migrations/treinamento/.
 */
return new class extends Migration
{
    private const TABELAS = [
        'cadastro' => [
            'cidadaos',
            'cidadao_email_verificacoes',
            'cidadao_password_reset_tokens',
        ],
        'treinamento' => [
            'treinamentos',
            'modulos',
            'inscricoes',
            'frequencias',
            'certificados',
        ],
    ];

    /**
     * Tabela => [constraint, coluna]; todas ON DELETE SET NULL para users(id).
     */
    private const FKS_PARA_USERS = [
        'treinamentos' => [
            ['treinamentos_created_by_foreign', 'created_by'],
            ['treinamentos_presenca_liberada_por_foreign', 'presenca_liberada_por'],
        ],
        'inscricoes' => [
            ['inscricoes_aprovado_por_id_foreign', 'aprovado_por_id'],
        ],
        'frequencias' => [
            ['frequencias_registrado_por_id_foreign', 'registrado_por_id'],
        ],
        'certificados' => [
            ['certificados_criado_por_foreign', 'criado_por'],
        ],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys(self::TABELAS) as $schema) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
        }

        foreach (self::TABELAS as $schema => $tabelas) {
            $this->mover($tabelas, 'public', $schema);
        }

        foreach (self::FKS_PARA_USERS as $tabela => $fks) {
            foreach ($fks as [$constraint, $coluna]) {
                DB::statement("ALTER TABLE IF EXISTS {$this->schemaDe($tabela)}.{$tabela} DROP CONSTRAINT IF EXISTS {$constraint}");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::FKS_PARA_USERS as $tabela => $fks) {
            $schema = $this->schemaDe($tabela);

            if (DB::selectOne('SELECT to_regclass(?) AS oid', ["{$schema}.{$tabela}"])->oid === null) {
                continue;
            }

            foreach ($fks as [$constraint, $coluna]) {
                $existe = DB::selectOne(
                    'SELECT 1 FROM pg_constraint WHERE conname = ? AND conrelid = to_regclass(?)',
                    [$constraint, "{$schema}.{$tabela}"],
                );

                if ($existe === null) {
                    // NOT VALID: linhas gravadas depois da separacao podem apontar
                    // para usuario que nao existe mais; vale so para as novas.
                    DB::statement(
                        "ALTER TABLE {$schema}.{$tabela} ADD CONSTRAINT {$constraint} "
                        ."FOREIGN KEY ({$coluna}) REFERENCES public.users (id) ON DELETE SET NULL NOT VALID"
                    );
                }
            }
        }

        foreach (self::TABELAS as $schema => $tabelas) {
            $this->mover($tabelas, $schema, 'public');
        }
    }

    private function schemaDe(string $tabela): string
    {
        foreach (self::TABELAS as $schema => $tabelas) {
            if (in_array($tabela, $tabelas, true)) {
                return $schema;
            }
        }

        throw new LogicException("Tabela fora do mapa: {$tabela}");
    }

    /**
     * Move cada tabela que exista na origem e ainda nao exista no destino.
     *
     * @param  list<string>  $tabelas
     */
    private function mover(array $tabelas, string $origem, string $destino): void
    {
        $existentes = DB::table('pg_tables')
            ->whereIn('schemaname', [$origem, $destino])
            ->whereIn('tablename', $tabelas)
            ->get(['schemaname', 'tablename']);

        $noDestino = $existentes->where('schemaname', $destino)->pluck('tablename')->all();

        foreach ($existentes->where('schemaname', $origem) as $tabela) {
            if (in_array($tabela->tablename, $noDestino, true)) {
                continue;
            }

            DB::statement("ALTER TABLE {$origem}.{$tabela->tablename} SET SCHEMA {$destino}");
        }
    }
};
