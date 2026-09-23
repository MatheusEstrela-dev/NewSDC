<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cedec_demanda_import_maps', function (Blueprint $table): void {
            $table->id();
            $table->string('source_table', 80);
            $table->string('source_id', 100);
            $table->string('target_table', 80);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            $table->unique(['source_table', 'source_id']);
            $table->index(['target_table', 'target_id']);
            $table->index(['status', 'source_table']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cedec_demanda_import_maps');
    }
};
