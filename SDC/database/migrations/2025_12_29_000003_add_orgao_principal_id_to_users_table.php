<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('orgao_principal_id')
                ->nullable()
                ->after('email')
                ->constrained('orgaos')
                ->nullOnDelete()
                ->comment('Órgão principal do usuário (cache para performance)');

            $table->index('orgao_principal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['orgao_principal_id']);
            $table->dropColumn('orgao_principal_id');
        });
    }
};
