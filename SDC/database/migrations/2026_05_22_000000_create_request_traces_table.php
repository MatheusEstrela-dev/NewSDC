<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de rastreamento de requests assincronos (AsynchronousResponse).
     * Cliente recebe 202 com trace_id e consulta status via GET /api/v1/traces/{id}.
     */
    public function up(): void
    {
        Schema::create('request_traces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 100);
            $table->string('status', 30)->default('pending');
            $table->string('result_disk', 50)->nullable();
            $table->string('result_path', 500)->nullable();
            $table->jsonb('meta')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'request_traces_user_status');
            $table->index(['type', 'created_at'], 'request_traces_type_date');
            $table->index('status', 'request_traces_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_traces');
    }
};
