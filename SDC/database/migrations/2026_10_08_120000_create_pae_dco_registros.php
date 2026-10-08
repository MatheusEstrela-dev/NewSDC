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
        Schema::create('pae_dco_avaliacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->string('resultado', 20);
            $table->text('fundamentacao');
            $table->string('num_sei', 100);
            $table->uuid('chave_idempotencia');
            $table->foreignId('decidido_por')->constrained('users');
            $table->timestampTz('decidido_em');
            $table->unique(['protocolo_id', 'chave_idempotencia'], 'pae_dco_avaliacao_idempotencia_unica');
            $table->index(['protocolo_id', 'id'], 'pae_dco_avaliacao_protocolo_idx');
        });
        DB::statement("ALTER TABLE pae_dco_avaliacoes ADD CONSTRAINT pae_dco_avaliacao_resultado_check CHECK (resultado IN ('aplicavel', 'nao_aplicavel'))");

        Schema::create('pae_dco_documentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->unsignedSmallInteger('competencia');
            $table->unsignedInteger('versao');
            $table->string('resultado', 20);
            $table->date('dt_documento');
            $table->date('dt_apresentacao');
            $table->string('num_sei', 100);
            $table->text('observacao')->nullable();
            $table->string('arquivo_path');
            $table->string('arquivo_nome_original');
            $table->string('arquivo_mime', 100);
            $table->unsignedBigInteger('arquivo_tamanho_bytes');
            $table->uuid('chave_idempotencia');
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestampTz('registrado_em');
            $table->unique(['protocolo_id', 'competencia', 'versao'], 'pae_dco_documento_versao_unica');
            $table->unique(['protocolo_id', 'chave_idempotencia'], 'pae_dco_documento_idempotencia_unica');
            $table->index(['protocolo_id', 'id'], 'pae_dco_documento_protocolo_idx');
        });
        DB::statement("ALTER TABLE pae_dco_documentos ADD CONSTRAINT pae_dco_documento_resultado_check CHECK (resultado IN ('positiva', 'nao_conforme'))");

        Schema::table('pae_ccpae', function (Blueprint $table): void {
            $table->foreignId('dco_avaliacao_id')->nullable()->constrained('pae_dco_avaliacoes')->restrictOnDelete();
            $table->foreignId('dco_documento_id')->nullable()->constrained('pae_dco_documentos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pae_ccpae', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dco_documento_id');
            $table->dropConstrainedForeignId('dco_avaliacao_id');
        });
        Schema::dropIfExists('pae_dco_documentos');
        Schema::dropIfExists('pae_dco_avaliacoes');
    }
};
