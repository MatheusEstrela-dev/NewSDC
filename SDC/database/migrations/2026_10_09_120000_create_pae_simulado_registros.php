<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pae_simulado_avaliacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->string('resultado', 20);
            $table->string('motivo_dispensa', 30)->nullable();
            $table->text('fundamentacao');
            $table->string('num_sei', 100);
            $table->uuid('chave_idempotencia');
            $table->foreignId('decidido_por')->constrained('users');
            $table->timestampTz('decidido_em');
            $table->unique(['protocolo_id', 'chave_idempotencia'], 'pae_simulado_avaliacao_idempotencia_unica');
            $table->index(['protocolo_id', 'id'], 'pae_simulado_avaliacao_protocolo_idx');
        });
        DB::statement("ALTER TABLE pae_simulado_avaliacoes ADD CONSTRAINT pae_simulado_avaliacao_resultado_check CHECK ((resultado = 'exigivel' AND motivo_dispensa IS NULL) OR (resultado = 'dispensado' AND motivo_dispensa IS NOT NULL AND motivo_dispensa IN ('licenca_instalacao', 'metodo_alternativo')))");

        Schema::create('pae_simulado_relatorios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->date('dt_realizacao');
            $table->unsignedInteger('versao');
            $table->unsignedSmallInteger('nivel_emergencia');
            $table->date('dt_apresentacao');
            $table->string('num_sei', 100);
            $table->text('observacao')->nullable();
            $table->string('arquivo_path');
            $table->string('arquivo_nome_original');
            $table->string('arquivo_mime', 100);
            $table->unsignedBigInteger('arquivo_tamanho_bytes');
            $table->boolean('integrado')->default(false);
            $table->text('barragens_integradas')->nullable();
            $table->date('aviso_cedec_em')->nullable();
            $table->jsonb('criterios');
            $table->jsonb('tempos');
            $table->jsonb('alarme');
            $table->jsonb('informativos');
            $table->boolean('validado');
            $table->jsonb('indicios');
            $table->uuid('chave_idempotencia');
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestampTz('registrado_em');
            $table->unique(['protocolo_id', 'dt_realizacao', 'versao'], 'pae_simulado_relatorio_versao_unica');
            $table->unique(['protocolo_id', 'chave_idempotencia'], 'pae_simulado_relatorio_idempotencia_unica');
            $table->index(['protocolo_id', 'id'], 'pae_simulado_relatorio_protocolo_idx');
        });
        DB::statement('ALTER TABLE pae_simulado_relatorios ADD CONSTRAINT pae_simulado_relatorio_nivel_check CHECK (nivel_emergencia IN (2, 3))');

        Schema::table('pae_ccpae', function (Blueprint $table): void {
            $table->foreignId('simulado_avaliacao_id')->nullable()->constrained('pae_simulado_avaliacoes')->restrictOnDelete();
            $table->foreignId('simulado_relatorio_id')->nullable()->constrained('pae_simulado_relatorios')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pae_ccpae', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('simulado_relatorio_id');
            $table->dropConstrainedForeignId('simulado_avaliacao_id');
        });
        Schema::dropIfExists('pae_simulado_relatorios');
        Schema::dropIfExists('pae_simulado_avaliacoes');
    }
};
