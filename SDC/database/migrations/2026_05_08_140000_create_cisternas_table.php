<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo Cisternas - cadastro e acompanhamento das cisternas vinculadas
 * aos municipios atendidos.
 *
 * Substitui o scaffold inicial (controller + page placeholder) por um
 * modelo de dados real com FK para municipios, enums textuais com CHECK
 * constraint, soft delete e indices uteis para listagens filtradas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cisternas')) {
            return;
        }

        Schema::create('cisternas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnDelete();

            $table->string('codigo', 60)->unique();
            $table->string('nome', 255);

            $table->text('endereco')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedInteger('capacidade_litros')->nullable()
                ->comment('Capacidade total em litros');

            $table->string('tipo', 20)
                ->comment('comunitaria|individual|escolar');

            $table->string('status', 20)->default('pendente')
                ->comment('ativa|pendente|inativa|em_obras');

            $table->date('data_instalacao')->nullable();

            $table->string('responsavel_nome', 255)->nullable();
            $table->string('responsavel_telefone', 20)->nullable();

            $table->text('observacoes')->nullable();

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('ID original do legado para idempotencia ETL');

            // Dono do registro, para a trilha de acoes do sino (Rastreavel).
            // responsavel_nome/telefone acima sao texto livre do beneficiario: nao
            // identificam usuario do sistema e nao servem para notificar ninguem.
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario que cadastrou; recebe a trilha de acoes do registro');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['municipio_id', 'status']);
            $table->index('legacy_id');
        });

        // CHECK constraints para enums textuais — apenas PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE cisternas ADD CONSTRAINT cisternas_tipo_check "
                ."CHECK (tipo IN ('comunitaria', 'individual', 'escolar'))"
            );
            DB::statement(
                "ALTER TABLE cisternas ADD CONSTRAINT cisternas_status_check "
                ."CHECK (status IN ('ativa', 'pendente', 'inativa', 'em_obras'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cisternas');
    }
};
