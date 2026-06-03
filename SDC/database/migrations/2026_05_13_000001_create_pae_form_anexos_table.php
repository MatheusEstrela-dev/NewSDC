<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pae_form_anexos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pae_form_id');
            $table->string('nome_original', 255);
            $table->string('nome_arquivo', 255)->comment('UUID.ext no storage');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamanho_bytes');
            $table->string('path', 500);
            $table->string('disk', 50)->default('pae');
            $table->text('descricao')->nullable();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pae_form_id', 'fk_pae_form_anexos_form')
                ->references('id')
                ->on('pae_forms')
                ->cascadeOnDelete();

            $table->index('pae_form_id', 'idx_pae_form_anexos_form');
            $table->index('uploaded_by', 'idx_pae_form_anexos_uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_form_anexos');
    }
};
