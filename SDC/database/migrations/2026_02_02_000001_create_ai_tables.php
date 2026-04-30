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
            $table->jsonb('metadata')->nullable();
            $table->index('metadata', 'idx_ai_conversations_metadata', 'gin');
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system', 'tool']);
            $table->text('content');
            $table->jsonb('tool_calls')->nullable();
            $table->index('tool_calls', 'idx_ai_messages_tool_calls', 'gin');
            $table->jsonb('metadata')->nullable();
            $table->index('metadata', 'idx_ai_messages_metadata', 'gin');
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('role');
            $table->index('created_at');
        });

        Schema::create('ai_execution_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('plugin_name')->index();
            $table->jsonb('input_params')->nullable();
            $table->index('input_params', 'idx_ai_execution_logs_input_params', 'gin');
            $table->jsonb('output_result')->nullable();
            $table->index('output_result', 'idx_ai_execution_logs_output_result', 'gin');
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
