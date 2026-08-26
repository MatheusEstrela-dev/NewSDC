<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_viatura_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plantao_id')
                ->constrained('plantoes')->cascadeOnDelete();
            $table->foreignId('viatura_id')
                ->constrained('plantao_viaturas')->restrictOnDelete();

            // Espelhos: o snapshot e registro historico. Se a placa mudar, o
            // relatorio de um turno passado continua fiel ao que foi declarado.
            $table->string('prefixo', 20);
            $table->string('placa', 10);

            $table->unsignedInteger('hodometro');
            $table->string('nivel_combustivel', 20);
            $table->text('alteracoes')->nullable();

            $table->foreignId('ultimo_condutor_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('ultimo_condutor_nome')->nullable();

            $table->string('anotacao', 160)->nullable();
            $table->boolean('em_condicoes')->default(true);

            $table->timestamps();

            $table->unique(['plantao_id', 'viatura_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_viatura_snapshots');
    }
};
