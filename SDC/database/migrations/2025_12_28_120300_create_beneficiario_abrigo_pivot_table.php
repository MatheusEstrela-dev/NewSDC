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
        Schema::create('beneficiario_abrigo', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->unsignedBigInteger('beneficiario_id');
            $table->unsignedBigInteger('abrigo_id');

            // Datas
            $table->date('data_entrada');
            $table->date('data_saida')->nullable();
            $table->string('motivo_saida')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('beneficiario_id')
                  ->references('id')
                  ->on('beneficiarios')
                  ->onDelete('cascade');

            $table->foreign('abrigo_id')
                  ->references('id')
                  ->on('abrigos')
                  ->onDelete('cascade');

            // Índices
            $table->index(['beneficiario_id', 'abrigo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiario_abrigo');
    }
};
