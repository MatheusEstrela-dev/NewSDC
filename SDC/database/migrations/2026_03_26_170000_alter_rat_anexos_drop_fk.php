<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rat_anexos', function (Blueprint $table) {
            // $table->dropForeign(['rat_id']);
            // $table->string('rat_id', 36)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rat_anexos', function (Blueprint $table) {
            $table->uuid('rat_id')->change();
            $table->foreign('rat_id')->references('id')->on('rats')->cascadeOnDelete();
        });
    }
};
