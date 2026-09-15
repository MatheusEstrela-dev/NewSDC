<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fotos de uma vistoria TDAP.
 *
 * Mesma forma de tdap_cronograma_comprovantes: o binario vai para o disco
 * 'tdap' e so os metadados ficam na tabela. O disco e privado, entao a imagem
 * nunca tem URL publica -- quem serve e VistoriaFotoController@show, atras do
 * gate tdap.vistorias.view.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_vistoria_fotos', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('vistoria_id')
                ->constrained('tdap_vistorias')
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
                ->nullOnDelete();

            $table->timestamps();

            $table->index('vistoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_vistoria_fotos');
    }
};
