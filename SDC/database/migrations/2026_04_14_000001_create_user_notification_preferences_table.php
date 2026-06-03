<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('module', ['rat', 'pae', 'meteorologia', 'demandas', 'decretacoes']);
            $table->boolean('canal_sistema')->default(true);
            $table->boolean('canal_email')->default(false);
            $table->boolean('canal_push')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
