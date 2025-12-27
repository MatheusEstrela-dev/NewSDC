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
        Schema::create('processo_danos_humanos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->onDelete('cascade');
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->onDelete('set null');
            $table->integer('obitos')->default(0);
            $table->integer('feridos')->default(0);
            $table->integer('enfermos')->default(0);
            $table->integer('desabrigados')->default(0);
            $table->integer('desalojados')->default(0);
            $table->integer('desaparecidos')->default(0);
            $table->integer('outros_afetados')->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('processo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processo_danos_humanos');
    }
};
