<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indice composto para os filtros quentes do modulo PMDA: fila de analise
     * (status + municipio_id), listagem filtrada do indice e counts dos cards
     * (o prefixo status atende os counts por status sozinhos). O indice simples
     * de status da migration de criacao permanece para nao invalidar planos de
     * consulta existentes.
     */
    public function up(): void
    {
        Schema::table('pmda_planos', function (Blueprint $table) {
            $table->index(['status', 'municipio_id'], 'pmda_planos_status_municipio_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pmda_planos', function (Blueprint $table) {
            $table->dropIndex('pmda_planos_status_municipio_idx');
        });
    }
};
