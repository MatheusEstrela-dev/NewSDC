<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_cronogramas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->string('empenho', 30)->nullable();

            $table->foreignId('ata_id')
                ->constrained('tdap_atas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('lote_id')
                ->constrained('tdap_lotes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('prestador_id')
                ->constrained('tdap_prestadores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('cnpj', 18)->nullable()->comment('Snapshot do CNPJ do prestador');

            $table->decimal('consumo_diario', 12, 2)->default(0)->comment('m3/dia');
            $table->unsignedSmallInteger('dias')->default(0);
            $table->decimal('fator', 5, 2)->default(1.0);

            $table->date('dt_inicio');
            $table->date('dt_final');
            $table->date('dt_inicio_prorrogacao')->nullable();
            $table->date('dt_final_prorrogacao')->nullable();

            $table->text('justificativa')->nullable();
            $table->string('nota_empenho', 50)->nullable();

            $table->unsignedBigInteger('ponto_captacao_id')->nullable()
                ->comment('Referencia opcional para ponto de captacao PMDA');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario que criou o cronograma');

            $table->boolean('ativo')->default(false)->index()
                ->comment('false = rascunho, true = ativo');
            $table->timestampTz('ativado_em')->nullable();
            $table->timestampTz('encerrado_em')->nullable();

            $table->text('observacao')->nullable();

            $table->jsonb('stored_caminhoes')->nullable()->comment('Snapshot dos caminhoes no momento da ativacao');
            $table->jsonb('stored_pmda_ponto')->nullable();
            $table->jsonb('stored_municipio')->nullable();
            $table->jsonb('stored_prestador')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['ativo', 'dt_final']);
            $table->index('prestador_id');
            $table->index('municipio_id');
            $table->index('lote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_cronogramas');
    }
};
