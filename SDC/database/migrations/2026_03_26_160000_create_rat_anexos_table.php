<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rat_anexos', function (Blueprint $table) {
            $table->id();
            $table->string('rat_id', 36)->index();
            $table->string('categoria', 30);
            $table->string('nome_original', 255);
            $table->string('nome_arquivo', 255)->comment('UUID.ext no storage');
            $table->string('mime_type', 100);
            $table->bigInteger('tamanho_bytes');
            $table->string('path', 500);
            $table->string('disk', 50)->default('public');
            $table->text('descricao')->nullable();
            $table->foreignId('uploaded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index(['rat_id', 'categoria']);
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rat_anexos');
    }
};
