<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rats', function (Blueprint $table) {
            if (!Schema::hasColumn('rats', 'recursos')) {
                $table->jsonb('recursos')->nullable()->after('comunicacao');
                $table->index('recursos', 'idx_rats_recursos', 'gin');
            }
            if (!Schema::hasColumn('rats', 'envolvidos')) {
                $table->jsonb('envolvidos')->nullable()->after('recursos');
                $table->index('envolvidos', 'idx_rats_envolvidos', 'gin');
            }
            if (!Schema::hasColumn('rats', 'vistoria')) {
                $table->jsonb('vistoria')->nullable()->after('envolvidos');
                $table->index('vistoria', 'idx_rats_vistoria', 'gin');
            }
            if (!Schema::hasColumn('rats', 'historico')) {
                $table->jsonb('historico')->nullable()->after('vistoria');
                $table->index('historico', 'idx_rats_historico', 'gin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rats', function (Blueprint $table) {
            $table->dropColumn(['recursos', 'envolvidos', 'vistoria', 'historico']);
        });
    }
};
