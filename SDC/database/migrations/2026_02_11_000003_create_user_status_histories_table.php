<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('old_status', 20);
            $table->string('new_status', 20);
            $table->string('reason', 255)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_user_status_history_user');
            $table->index('created_at', 'idx_user_status_history_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_status_histories');
    }
};
