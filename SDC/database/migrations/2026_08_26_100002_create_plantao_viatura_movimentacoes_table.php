<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_viatura_movimentacoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('viatura_id')
                ->constrained('plantao_viaturas')->cascadeOnDelete();
            $table->foreignId('plantao_id')->nullable()
                ->constrained('plantoes')->nullOnDelete();

            $table->foreignId('condutor_id')
                ->constrained('users')->restrictOnDelete();
            $table->string('condutor_nome');

            $table->dateTime('saida_em');
            $table->unsignedInteger('saida_hodometro');
            $table->string('saida_combustivel', 20);
            $table->string('destino', 160)->nullable();
            $table->string('motivo', 160)->nullable();

            $table->dateTime('retorno_em')->nullable();
            $table->unsignedInteger('retorno_hodometro')->nullable();
            $table->string('retorno_combustivel', 20)->nullable();
            $table->text('alteracoes')->nullable();

            $table->string('status', 20)->default('EM_TRANSITO');

            $table->timestamps();
            $table->softDeletes();

            $table->index('plantao_id');
            $table->index('condutor_id');
            // Suporta a guarda "uma viatura nao pode ter duas saidas abertas".
            $table->index(['viatura_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_viatura_movimentacoes');
    }
};
