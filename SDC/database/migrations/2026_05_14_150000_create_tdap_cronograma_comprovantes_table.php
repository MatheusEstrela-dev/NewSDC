<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comprovantes (anexos) de um cronograma TDAP - ex.: comprovantes de
 * prorrogacao. Binario no disco 'tdap'; metadados nesta tabela. Espelha o
 * padrao de rat_anexos / pae_form_anexos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_cronograma_comprovantes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cronograma_id')
                ->constrained('tdap_cronogramas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('nome_original');
            $table->string('nome_arquivo');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('tamanho_bytes')->default(0);
            $table->string('path');
            $table->string('disk', 30)->default('tdap');
            $table->string('descricao')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();

            $table->index('cronograma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_cronograma_comprovantes');
    }
};
