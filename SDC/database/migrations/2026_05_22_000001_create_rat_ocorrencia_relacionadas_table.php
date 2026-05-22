<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rat_ocorrencia_relacionadas', function (Blueprint $table) {
            $table->id();
            $table->uuid('rat_origem_id');
            $table->uuid('rat_destino_id');
            $table->timestamps();

            $table->foreign('rat_origem_id')
                ->references('id')->on('rat_ocorrencias')
                ->onDelete('cascade');

            $table->foreign('rat_destino_id')
                ->references('id')->on('rat_ocorrencias')
                ->onDelete('cascade');

            $table->unique(['rat_origem_id', 'rat_destino_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rat_ocorrencia_relacionadas');
    }
};
