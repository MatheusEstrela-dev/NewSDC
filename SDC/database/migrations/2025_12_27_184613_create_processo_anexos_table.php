<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('processo_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->onDelete('cascade');
            $table->string('tipo');
            $table->string('nome_arquivo');
            $table->string('caminho');
            $table->bigInteger('tamanho')->default(0);
            $table->string('mime_type')->nullable();
            $table->text('descricao')->nullable();
            $table->timestamps();

            $table->index('processo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processo_anexos');
    }
};
