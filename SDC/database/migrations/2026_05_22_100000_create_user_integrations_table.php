<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Integracoes pessoais por usuario (Telegram, WhatsApp, N8N webhook).
     * Cada user pode vincular sua propria conta para receber notificacoes
     * personalizadas via TelegramChannel (App\Notifications\Channels).
     */
    public function up(): void
    {
        Schema::create('user_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 30); // telegram | whatsapp | n8n_webhook
            $table->string('external_id', 255); // chat_id / phone / webhook url
            $table->string('display_name', 100)->nullable();
            $table->text('secret')->nullable(); // encrypted at rest via model cast
            $table->jsonb('meta')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'external_id'], 'user_integrations_unique');
            $table->index(['user_id', 'is_active'], 'user_integrations_user_active');
            $table->index(['provider', 'is_active'], 'user_integrations_provider_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_integrations');
    }
};
