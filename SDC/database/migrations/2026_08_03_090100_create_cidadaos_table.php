<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conta de cidadao externo (sem vinculo com a Defesa Civil), usada apenas
 * pelo guard "cidadao" no Portal de Treinamentos. Isolada da tabela `users`
 * interna: nao participa do RBAC (Spatie) nem de nenhuma outra tela do SDC.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cidadaos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('cpf', 11)->unique();
            $table->string('telefone', 20)->nullable();
            $table->string('password');
            // Cadastro do portal e double opt-in: o cidadao so autentica depois
            // de confirmar o codigo enviado por e-mail (ver
            // Treinamento\Services\CidadaoVerificacaoService). Enquanto for null
            // a conta existe mas nao entra, e pode ser sobrescrita por um novo
            // cadastro no mesmo CPF - registro que nunca provou posse do e-mail
            // nao tem direito adquirido sobre o CPF.
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('aceite_lgpd_em')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cidadaos');
    }
};
