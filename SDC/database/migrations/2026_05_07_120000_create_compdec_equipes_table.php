<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F2 - Equipe COMPDEC (spec § 4.5).
 *
 * Cada orgao COMPDEC mantem uma equipe propria (coordenador, agentes, etc).
 * Independe da pivot compdec_orgao_user (que vincula apenas usuarios reais
 * com login no sistema).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compdec_equipes')) {
            return;
        }

        Schema::create('compdec_equipes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orgao_id')
                ->constrained('compdec_orgaos')
                ->cascadeOnDelete();

            $table->string('nome', 255);

            $table->string('funcao', 20)
                ->comment('coordenador|agente|tecnico|apoio|outro');

            $table->string('telefone', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('cpf', 14)->nullable()
                ->comment('GREEN: nao vem do legado, opcional');

            $table->boolean('ativo')->default(true);
            $table->smallInteger('ordem')->default(0);
            $table->text('observacoes')->nullable();

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('ID original em com_eq_comdec para idempotencia ETL');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['orgao_id', 'funcao', 'ativo']);
            $table->index('legacy_id');
        });

        // CHECK constraint para enum textual (PG)
        DB::statement(
            "ALTER TABLE compdec_equipes ADD CONSTRAINT compdec_equipes_funcao_check "
            ."CHECK (funcao IN ('coordenador', 'agente', 'tecnico', 'apoio', 'outro'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('compdec_equipes');
    }
};
