<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amarra o turno executado a vaga da escala que o previu.
 *
 * Migration propria, e nao consolidacao na 2026_08_26_100004, porque aquela ja
 * rodou no banco compartilhado: editar migration aplicada nao produz efeito.
 *
 * nullOnDelete e nao cascade: apagar a escala nao pode apagar o turno
 * trabalhado, que tem passagem de servico e aceite formal. O turno apenas
 * perde a referencia e passa a contar como aberto fora de escala.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantoes', function (Blueprint $table) {
            $table->foreignId('escala_item_id')->nullable()->after('periodo')
                ->constrained('plantao_escala_itens')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plantoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('escala_item_id');
        });
    }
};
