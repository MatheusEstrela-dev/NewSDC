<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pae_fichas_anexo_b', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->unsignedInteger('versao');
            $table->string('nome_barragem')->nullable();
            $table->string('nome_mina')->nullable();
            $table->string('metodo_construtivo', 100)->nullable();
            $table->decimal('volume_reservatorio', 15, 2)->nullable();
            $table->foreignId('municipio_sede_id')->nullable()->constrained('municipios');
            $table->string('municipio_sede_nome')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('tipo_rejeito')->nullable();
            $table->string('toxicidade')->nullable();
            $table->decimal('extensao_zas_km', 10, 3)->nullable();
            $table->unsignedBigInteger('populacao_zas')->nullable();
            $table->unsignedBigInteger('populacao_zas_mobilidade_reduzida')->nullable();
            $table->unsignedBigInteger('populacao_zss')->nullable();
            $table->jsonb('cursos_agua')->nullable();
            $table->unsignedInteger('edificacoes_hospitalares')->nullable();
            $table->unsignedInteger('edificacoes_escolares')->nullable();
            $table->unsignedInteger('edificacoes_prisionais')->nullable();
            $table->unsignedInteger('edificacoes_outras')->nullable();
            $table->jsonb('estruturas_associadas')->nullable();
            $table->jsonb('municipios_snapshot');
            $table->foreignId('criado_por')->constrained('users');
            $table->timestampTz('created_at');
            $table->unique(['protocolo_id', 'versao'], 'pae_ficha_anexo_b_protocolo_versao_unique');
            $table->index(['protocolo_id', 'created_at'], 'pae_ficha_anexo_b_historico_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_fichas_anexo_b');
    }
};
