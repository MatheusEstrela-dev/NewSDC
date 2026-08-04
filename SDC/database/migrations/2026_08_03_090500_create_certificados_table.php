<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificado nao guarda arquivo: e renderizado sob demanda (mesmo padrao de
 * impressao do modulo Rat - view Blade + window.print() do navegador), entao
 * so precisamos do status/elegibilidade, nao de um binario ou hash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscricao_id')->unique()->constrained('inscricoes')->restrictOnDelete();
            $table->foreignId('treinamento_id')->constrained('treinamentos')->restrictOnDelete();

            $table->enum('status', ['PENDENTE', 'GERADO'])->default('PENDENTE')->index();
            $table->timestamp('emitido_em')->nullable();

            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};
