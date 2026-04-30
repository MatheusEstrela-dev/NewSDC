<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('event', ['insert', 'update', 'delete', 'login', 'logout']);
            $table->string('table_name', 50);
            $table->unsignedBigInteger('row_id')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->index('old_values', 'idx_audit_logs_old_values', 'gin');
            $table->jsonb('new_values')->nullable();
            $table->index('new_values', 'idx_audit_logs_new_values', 'gin');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id', 'idx_audit_user');
            $table->index(['table_name', 'row_id'], 'idx_audit_entity');
            $table->index('event', 'idx_audit_event');
            $table->index('created_at', 'idx_audit_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
