<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('pending_expires_at')
                ->nullable()
                ->after('status')
                ->comment('Prazo para troca da senha provisoria no primeiro acesso (24h apos criacao). Apos expirar, scheduler desativa a conta.');

            $table->boolean('must_change_password')
                ->default(false)
                ->after('pending_expires_at')
                ->comment('Forca troca de senha no proximo login. Setado para true ao criar usuario via onboarding.');

            $table->timestamp('password_changed_at')
                ->nullable()
                ->after('must_change_password')
                ->comment('Timestamp da ultima troca de senha pelo proprio usuario.');

            $table->timestamp('welcome_tour_completed_at')
                ->nullable()
                ->after('password_changed_at')
                ->comment('Timestamp em que o usuario concluiu (ou pulou) o tour de boas-vindas.');

            $table->index(['status', 'pending_expires_at'], 'idx_users_pending_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_pending_expiry');
            $table->dropColumn([
                'pending_expires_at',
                'must_change_password',
                'password_changed_at',
                'welcome_tour_completed_at',
            ]);
        });
    }
};
