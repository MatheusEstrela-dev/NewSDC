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
            // Antes disso, autoconfirmacao de presenca (sem QR/staff) so existia
            // hardcoded pra treinamentos ONLINE. Alguns cursos presenciais tambem
            // podem confiar no proprio inscrito; outros exigem check-in do staff -
            // essa decisao passa a ser por curso, nao mais fixa pelo tipo.
            $table->boolean('presenca_autoconfirmavel')->default(false)->after('presenca_liberada');
        });
    }

    public function down(): void
    {
        Schema::table('treinamentos', function (Blueprint $table) {
            $table->dropColumn('presenca_autoconfirmavel');
        });
    }
};
