<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'session_version')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            // Carimbo de seguranca: sobe a cada mudanca que exige novo login
            // (status, cargo, permissao, senha, orgao principal, e-mail).
            $table->unsignedInteger('session_version')->default(1);
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'session_version')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('session_version');
        });
    }
};
