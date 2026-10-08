<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pae_evacuacao_conferencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->unsignedInteger('versao');
            $table->jsonb('setores');
            $table->jsonb('rotas');
            $table->jsonb('acessos');
            $table->jsonb('pontos_encontro');
            $table->unsignedInteger('tte_declarado_segundos')->nullable();
            $table->string('num_sei', 100);
            $table->text('observacao')->nullable();
            $table->decimal('tmd_segundos', 12, 3)->nullable();
            $table->decimal('te_segundos', 12, 3)->nullable();
            $table->decimal('tte_segundos', 12, 3)->nullable();
            $table->boolean('criterio1_conforme');
            $table->boolean('criterio2_conforme');
            $table->boolean('possui_rota_invalida');
            $table->boolean('possui_setor_inviavel');
            $table->boolean('excede_declarado');
            $table->jsonb('resultado');
            $table->uuid('chave_idempotencia');
            $table->foreignId('criado_por')->constrained('users');
            $table->timestampTz('created_at');
            $table->unique(['protocolo_id', 'versao'], 'pae_evacuacao_versao_unica');
            $table->unique(['protocolo_id', 'chave_idempotencia'], 'pae_evacuacao_idempotencia_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_evacuacao_conferencias');
    }
};
