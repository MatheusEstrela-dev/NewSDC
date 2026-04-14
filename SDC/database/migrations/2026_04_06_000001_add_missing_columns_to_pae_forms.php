<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pae_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('pae_forms', 'municipio_id')) {
                $table->unsignedBigInteger('municipio_id')->nullable()->after('contexto');
                $table->foreign('municipio_id', 'fk_forms_municipio')
                    ->references('id')->on('municipios')->onDelete('set null');
                $table->index('municipio_id', 'idx_forms_municipio');
            }

            if (!Schema::hasColumn('pae_forms', 'pae_empnto_id')) {
                $table->unsignedBigInteger('pae_empnto_id')->nullable()->after('municipio_id');
                $table->foreign('pae_empnto_id', 'fk_forms_empnto')
                    ->references('id')->on('pae_empntos')->onDelete('set null');
                $table->index('pae_empnto_id', 'idx_forms_empnto');
            }

            if (!Schema::hasColumn('pae_forms', 'pae_protocolo_id')) {
                $table->unsignedBigInteger('pae_protocolo_id')->nullable()->after('id');
                $table->foreign('pae_protocolo_id', 'fk_forms_protocolo_new')
                    ->references('id')->on('pae_protocolos')->onDelete('set null');
                $table->index('pae_protocolo_id', 'idx_forms_protocolo_new');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pae_forms', function (Blueprint $table) {
            $table->dropForeignIfExists('fk_forms_municipio');
            $table->dropForeignIfExists('fk_forms_empnto');
            $table->dropForeignIfExists('fk_forms_protocolo_new');
            $table->dropColumnIfExists(['municipio_id', 'pae_empnto_id', 'pae_protocolo_id']);
        });
    }
};
