<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona canais Telegram e WhatsApp nas preferencias de notificacao
     * por modulo. Default false (opt-in) — usuario precisa ativar
     * explicitamente apos conectar sua conta em Configuracoes > Integracoes.
     */
    public function up(): void
    {
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            $table->boolean('canal_telegram')->default(false)->after('canal_push');
            $table->boolean('canal_whatsapp')->default(false)->after('canal_telegram');
        });
    }

    public function down(): void
    {
        Schema::table('user_notification_preferences', function (Blueprint $table) {
            $table->dropColumn(['canal_telegram', 'canal_whatsapp']);
        });
    }
};
