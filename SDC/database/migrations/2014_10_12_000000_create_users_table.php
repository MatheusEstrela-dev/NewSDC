<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('email', 191)->unique();

            // 🔗 Órgão principal (FK adicionada em migration posterior)
            $table->unsignedBigInteger('orgao_principal_id')
                ->nullable()
                ->comment('Órgão principal do usuário (cache para performance)');

            $table->char('cpf', 11)->unique();
            $table->string('password');

            $table->boolean('active')->default(true)->index();

            $table->enum('status', [
                'active',
                'inactive',
                'suspended',
                'pending',
                'blocked'
            ])->default('pending');

            // Onboarding: senha provisoria criada pelo admin (OnboardingService).
            // Apos pending_expires_at, scheduler DeactivateExpiredPendingUsers
            // inativa a conta que nao trocou a senha em 24h.
            $table->timestamp('pending_expires_at')->nullable()
                ->comment('Prazo para troca da senha provisoria no primeiro acesso (24h apos criacao). Apos expirar, scheduler desativa a conta.');
            $table->boolean('must_change_password')->default(false)
                ->comment('Forca troca de senha no proximo login. Setado para true ao criar usuario via onboarding.');
            $table->timestamp('password_changed_at')->nullable()
                ->comment('Timestamp da ultima troca de senha pelo proprio usuario.');
            $table->timestamp('welcome_tour_completed_at')->nullable()
                ->comment('Timestamp em que o usuario concluiu (ou pulou) o tour de boas-vindas.');

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('last_login_browser', 100)->nullable()
                ->comment('Resumo legivel do user-agent do ultimo login (ex.: "Chrome 148 / Windows"). Parseado em User::recordLogin.');

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // 👇 Auditoria (auto-relacionamento)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // 📊 Índices
            $table->index('orgao_principal_id');
            $table->index(['status', 'last_login_at'], 'idx_users_inactivity');
            $table->index(['active', 'name'], 'idx_active_users_search');
            $table->index(['status', 'pending_expires_at'], 'idx_users_pending_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
