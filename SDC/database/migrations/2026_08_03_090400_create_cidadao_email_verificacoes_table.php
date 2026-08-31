<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verificacao de e-mail do cadastro publico do Portal de Treinamentos.
 *
 * Espelha email_change_requests de proposito: o mesmo contrato de colunas deixa
 * os dois modelos compartilharem App\Support\Auth\MagicCodeVerifiable (TTL,
 * teto de tentativas, cooldown de reenvio e isPending()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cidadao_email_verificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cidadao_id')->constrained('cidadaos')->cascadeOnDelete();

            // E-mail alvo gravado junto: se o cadastro pendente for sobrescrito
            // por outro CPF/e-mail, o pedido antigo continua auditavel.
            $table->string('email', 191);

            // Codigo nunca em claro. Hash + Hash::check constant-time.
            $table->string('code_hash');
            $table->unsignedTinyInteger('code_attempts')->default(0);

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Forense
            $table->string('requested_ip', 45)->nullable();
            $table->string('requested_user_agent')->nullable();

            // Reenvio
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('last_resend_at')->nullable();

            $table->timestamps();

            $table->index(
                ['cidadao_id', 'used_at', 'cancelled_at'],
                'idx_cev_cidadao_pending'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cidadao_email_verificacoes');
    }
};
