<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rastreia como cada presenca foi registrada (RF07 exige distinguir
 * sincronizacoes offline; tambem util para auditoria/relatorios).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frequencias', function (Blueprint $table) {
            $table->string('origem', 30)->default('manual')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('frequencias', function (Blueprint $table) {
            $table->dropColumn('origem');
        });
    }
};
