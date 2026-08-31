<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quem entra na escala, e com que posto.
 *
 * Existe por dois motivos. Primeiro, `users` tem milhares de contas COMPDEC
 * municipais que nunca pisam no Predio Alterosas: sem esta lista, o select da
 * escala seria inutilizavel. Segundo, o relatorio de passagem nomeia as pessoas
 * por posto ("Sgt Leandro", "Ten Menon") e `users` nao tem essa coluna -- e nao
 * deve ganhar uma, por ser tabela transversal a todo o SDC.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_plantonistas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique()
                ->constrained('users')->cascadeOnDelete();

            // Livre de proposito: a corporacao usa abreviacoes que mudam com o
            // tempo (Sgt, 2Sgt, Ten, Cap) e um enum viraria divida na primeira
            // promocao fora da lista.
            $table->string('posto', 20)->nullable();

            $table->boolean('ativo')->default(true);
            $table->text('observacao')->nullable();

            $table->timestamps();

            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_plantonistas');
    }
};
