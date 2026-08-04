<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `frequencias` passa a referenciar `inscricao_id` em vez de `user_id` direto,
 * para acompanhar a mudanca polimorfica de `inscricoes` (o inscrito pode ser
 * um User interno ou um Cidadao externo - o acesso continua disponivel via
 * $frequencia->inscricao->inscrito).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frequencias', function (Blueprint $table) {
            $table->foreignId('inscricao_id')->nullable()->after('modulo_id')
                ->constrained('inscricoes')->cascadeOnDelete();
        });

        DB::statement(<<<'SQL'
            UPDATE frequencias AS f
            SET inscricao_id = i.id
            FROM inscricoes AS i, modulos AS m
            WHERE m.id = f.modulo_id
              AND i.treinamento_id = m.treinamento_id
              AND i.inscrito_type = 'App\Models\User'
              AND i.inscrito_id = f.user_id
        SQL);

        Schema::table('frequencias', function (Blueprint $table) {
            $table->dropUnique('idx_frequencia_unique');
            $table->dropConstrainedForeignId('user_id');
            $table->foreignId('inscricao_id')->nullable(false)->change();

            $table->unique(['modulo_id', 'inscricao_id', 'data_aula'], 'idx_frequencia_unique');
        });
    }

    public function down(): void
    {
        Schema::table('frequencias', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('modulo_id')->constrained('users')->cascadeOnDelete();
        });

        DB::statement(<<<'SQL'
            UPDATE frequencias AS f
            SET user_id = i.inscrito_id
            FROM inscricoes AS i
            WHERE i.id = f.inscricao_id
        SQL);

        Schema::table('frequencias', function (Blueprint $table) {
            $table->dropUnique('idx_frequencia_unique');
            $table->dropConstrainedForeignId('inscricao_id');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            $table->unique(['modulo_id', 'user_id', 'data_aula'], 'idx_frequencia_unique');
        });
    }
};
