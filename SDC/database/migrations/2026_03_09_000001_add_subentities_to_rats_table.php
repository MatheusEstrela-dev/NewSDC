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
                $table->json('recursos')->nullable()->after('comunicacao');
            }
            if (!Schema::hasColumn('rats', 'envolvidos')) {
                $table->json('envolvidos')->nullable()->after('recursos');
            }
            if (!Schema::hasColumn('rats', 'vistoria')) {
                $table->json('vistoria')->nullable()->after('envolvidos');
            }
            if (!Schema::hasColumn('rats', 'historico')) {
                $table->json('historico')->nullable()->after('vistoria');
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
