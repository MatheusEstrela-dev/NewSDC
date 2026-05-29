<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('current_email', 191);
            $table->string('new_email', 191);

            // Codigo nunca em claro. Hash bcrypt + Hash::check constant-time.
            $table->string('code_hash');
            $table->unsignedTinyInteger('code_attempts')->default(0);

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Forense
            $table->string('requested_ip', 45)->nullable();
            $table->string('requested_user_agent')->nullable();
            $table->foreignId('requested_by_admin_id')
                ->nullable()->constrained('users')->nullOnDelete();

            // Reenvio
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('last_resend_at')->nullable();

            $table->timestamps();

            $table->index(
                ['user_id', 'used_at', 'cancelled_at'],
                'idx_ecr_user_pending'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_change_requests');
    }
};
