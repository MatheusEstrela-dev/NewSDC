<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rats', function (Blueprint $table) {
            $table->json('anexos')->nullable()->after('historico');
        });
    }

    public function down(): void
    {
        Schema::table('rats', function (Blueprint $table) {
            $table->dropColumn('anexos');
        });
    }
};
