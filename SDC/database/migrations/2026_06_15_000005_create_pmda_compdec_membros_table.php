<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Equipe tecnica / outros membros da COMPDEC (Etapa 3 do wizard PMDA).
 * O coordenador permanece em colunas de pmda_planos; aqui ficam os demais membros.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmda_compdec_membros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pmda_plano_id')->constrained('pmda_planos')->cascadeOnDelete();
            $table->string('nome', 110);
            $table->string('cargo', 80)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmda_compdec_membros');
    }
};
