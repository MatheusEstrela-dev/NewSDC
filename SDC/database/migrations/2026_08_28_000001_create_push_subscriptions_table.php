<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inscricoes de Web Push.
 *
 * Preferencia e entrega vivem em lugares diferentes de proposito:
 * user_notification_preferences.canal_push diz "eu QUERO push", esta tabela diz
 * "entregue nestes dispositivos". Um usuario pode querer push e nao ter nenhum
 * navegador autorizado -- nesse caso nada e enviado, e a tela avisa.
 *
 * O endpoint e a identidade da inscricao para o push service, entao e a chave
 * natural do unique. Ele passa de 255 caracteres com frequencia (FCM), por isso
 * string(500). O indice unico usa hash do endpoint: Postgres nao indexa b-tree
 * acima de ~2704 bytes e MySQL limita a 3072 bytes na chave, e um unique sobre
 * 500 chars utf8mb4 estoura os dois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('endpoint', 500);
            // sha256 hex do endpoint: garante uma linha por endpoint sem depender
            // do tamanho maximo de indice do banco.
            $table->char('endpoint_hash', 64)->unique();

            $table->string('public_key', 255)->nullable();
            $table->string('auth_token', 255)->nullable();
            $table->string('content_encoding', 20)->default('aesgcm');

            // So para o usuario reconhecer o dispositivo na lista ("Chrome no
            // Windows"). Nao participa de nenhuma regra.
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Todo envio comeca por "as inscricoes deste usuario".
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
