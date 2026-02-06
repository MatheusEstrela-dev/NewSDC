<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system', 'tool']);
            $table->text('content');
            $table->json('tool_calls')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('role');
            $table->index('created_at');
        });

        Schema::create('ai_execution_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('plugin_name')->index();
            $table->json('input_params')->nullable();
            $table->json('output_result')->nullable();
            $table->string('status')->default('success');
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
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
