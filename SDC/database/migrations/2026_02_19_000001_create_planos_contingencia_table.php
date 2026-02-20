<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos_contingencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipio_id');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->enum('situacao', ['regular', 'irregular'])->default('regular');
            $table->date('data_aprovacao')->nullable();
            $table->date('data_validade')->nullable();
            $table->string('arquivo_url')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('municipio_id');
            $table->index('situacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos_contingencia');
    }
};
