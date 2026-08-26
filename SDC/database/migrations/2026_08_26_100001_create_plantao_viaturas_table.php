<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_viaturas', function (Blueprint $table) {
            $table->id();

            $table->string('prefixo', 20);
            $table->string('placa', 10)->unique();
            $table->string('marca', 50)->nullable();
            $table->string('modelo', 100);
            $table->string('localizacao', 40)->default('PREDIO_ALTEROSAS');
            $table->boolean('exclusiva_sobreaviso')->default(false);
            $table->string('status', 30)->default('DISPONIVEL');

            // Estado corrente. Derivado da ultima movimentacao e materializado
            // aqui porque a tela de indice lista a frota inteira com esses
            // valores. Escrito exclusivamente por MovimentacaoViaturaService.
            $table->unsignedInteger('hodometro_atual')->nullable();
            $table->string('nivel_combustivel', 20)->nullable();
            $table->foreignId('ultimo_condutor_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('ultimo_condutor_nome')->nullable();

            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('ativo');
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_viaturas');
    }
};
