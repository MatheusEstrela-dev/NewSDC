<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_execution_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('plugin_name')->index();
            $table->json('input_params')->nullable();
            $table->json('output_result')->nullable();
            $table->string('status')->default('success'); // success, error
            $table->text('error_message')->nullable();
            $table->decimal('execution_time_ms', 10, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_execution_logs');
    }
};
