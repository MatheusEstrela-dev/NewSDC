<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treinamentos', function (Blueprint $table) {
            $table->string('categoria', 20)->default('CURSO')->after('tipo');
            $table->string('link_publico_slug')->nullable()->unique()->after('local');
            $table->timestamp('publicado_em')->nullable()->after('link_publico_slug');
            $table->boolean('presenca_liberada')->default(false)->after('numero_vagas');
            $table->timestamp('presenca_liberada_em')->nullable()->after('presenca_liberada');
            $table->foreignId('presenca_liberada_por')->nullable()->after('presenca_liberada_em')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('finalizado_em')->nullable()->after('presenca_liberada_por');

            $table->index('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('treinamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('presenca_liberada_por');
            $table->dropColumn([
                'categoria',
                'link_publico_slug',
                'publicado_em',
                'presenca_liberada',
                'presenca_liberada_em',
                'finalizado_em',
            ]);
        });
    }
};
