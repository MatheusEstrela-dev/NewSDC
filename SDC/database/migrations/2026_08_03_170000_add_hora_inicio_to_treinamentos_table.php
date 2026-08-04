<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF02 - a imagem de divulgacao mostra "Data e Hora"; faltava o horario
 * (so tinhamos a data). Nullable: eventos antigos/sem horario definido ainda
 * mostram so a data na peca de divulgacao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treinamentos', function (Blueprint $table) {
            $table->time('hora_inicio')->nullable()->after('data_fim');
        });
    }

    public function down(): void
    {
        Schema::table('treinamentos', function (Blueprint $table) {
            $table->dropColumn('hora_inicio');
        });
    }
};
