<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_lotes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ata_id')
                ->constrained('tdap_atas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('prestador_id')
                ->constrained('tdap_prestadores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('numero', 20);
            $table->string('nome', 150)->nullable();
            $table->decimal('qtd_agua_m3', 12, 2)->comment('Quantidade contratada (m3)');
            $table->decimal('valor_m3', 10, 2)->comment('Valor unitario por m3 (R$)');
            $table->boolean('ativo')->default(true)->index();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ata_id', 'municipio_id', 'deleted_at'], 'tdap_lotes_ata_mun_unique');
            $table->index(['ata_id', 'ativo']);
            $table->index(['prestador_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_lotes');
    }
};
