<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Simplifica TipoTreinamento para so PRESENCIAL/ONLINE (remove HIBRIDO,
 * renomeia EAD -> ONLINE). O check constraint do enum() do Postgres tem nome
 * auto-gerado pelo Laravel/Doctrine - buscamos dinamicamente em vez de
 * assumir o nome, para nao quebrar se o nome gerado for diferente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropTipoCheckConstraint();

        DB::table('treinamentos')->where('tipo', 'HIBRIDO')->update(['tipo' => 'PRESENCIAL']);
        DB::table('treinamentos')->where('tipo', 'EAD')->update(['tipo' => 'ONLINE']);

        DB::statement("ALTER TABLE treinamentos ADD CONSTRAINT treinamentos_tipo_check CHECK (tipo IN ('PRESENCIAL', 'ONLINE'))");
    }

    public function down(): void
    {
        $this->dropTipoCheckConstraint();

        DB::table('treinamentos')->where('tipo', 'ONLINE')->update(['tipo' => 'EAD']);

        DB::statement("ALTER TABLE treinamentos ADD CONSTRAINT treinamentos_tipo_check CHECK (tipo IN ('PRESENCIAL', 'EAD', 'HIBRIDO'))");
    }

    private function dropTipoCheckConstraint(): void
    {
        DB::statement(<<<'SQL'
            DO $$
            DECLARE
                nome_constraint text;
            BEGIN
                SELECT con.conname INTO nome_constraint
                FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                WHERE rel.relname = 'treinamentos'
                  AND con.contype = 'c'
                  AND pg_get_constraintdef(con.oid) ILIKE '%tipo%'
                LIMIT 1;

                IF nome_constraint IS NOT NULL THEN
                    EXECUTE 'ALTER TABLE treinamentos DROP CONSTRAINT ' || quote_ident(nome_constraint);
                END IF;
            END $$;
        SQL);
    }
};
