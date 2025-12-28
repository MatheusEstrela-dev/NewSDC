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
        Schema::create('itens_auxilio', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->unsignedBigInteger('auxilio_id');
            $table->unsignedBigInteger('produto_id')->nullable();

            // Dados do Item
            $table->string('descricao');
            $table->decimal('quantidade', 10, 2);
            $table->string('unidade_medida', 20);

            $table->timestamps();

            // Foreign Keys
            $table->foreign('auxilio_id')
                  ->references('id')
                  ->on('auxilios')
                  ->onDelete('cascade');

            // Índices
            $table->index('auxilio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itens_auxilio');
    }
};
