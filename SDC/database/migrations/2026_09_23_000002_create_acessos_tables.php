<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acessos_cadastros', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('solicitado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprovado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nome', 255);
            $table->string('tipo_documento', 20);
            $table->string('documento', 30);
            $table->string('cpf', 14)->unique();
            $table->string('login_ad', 100)->nullable()->unique();
            $table->string('email_corporativo', 255)->nullable();
            $table->string('email_pessoal', 255)->nullable();
            $table->string('telefone_mesa', 30)->nullable();
            $table->string('telefone_whatsapp', 30)->nullable();
            $table->string('setor', 150)->nullable();
            $table->string('posto', 150)->nullable();
            $table->string('cargo', 150)->nullable();
            $table->text('observacoes_ti')->nullable();
            $table->string('status', 30)->default('pendente')->index();
            $table->string('status_ad', 30)->default('desconhecido')->index();
            // Espelho do AD: so leitura do diretorio; dn_ad nunca e usado para escrever.
            $table->uuid('object_guid')->nullable()->unique();
            $table->string('dn_ad', 500)->nullable();
            $table->boolean('conta_ativa_ad')->nullable();
            $table->boolean('bloqueada_ad')->nullable();
            $table->boolean('troca_senha_pendente_ad')->nullable();
            $table->timestamp('ad_sincronizado_em')->nullable();
            $table->timestamp('data_solicitacao')->nullable();
            $table->timestamp('data_aprovacao')->nullable();
            $table->timestamps();
            $table->index(['setor', 'status']);
        });

        Schema::create('acessos_auditoria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cadastro_id')->constrained('acessos_cadastros')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 50);
            $table->jsonb('dados')->nullable();
            $table->timestamp('created_at');
            $table->index(['cadastro_id', 'created_at']);
        });

        Schema::create('acessos_operacoes_ad', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('chave_idempotencia', 120)->unique();
            $table->foreignId('cadastro_id')->nullable()->constrained('acessos_cadastros')->nullOnDelete();
            $table->string('login_ad', 100);
            $table->uuid('object_guid')->nullable();
            $table->string('acao', 30);
            $table->string('origem', 20);
            $table->unsignedBigInteger('origem_id')->nullable();
            $table->foreignId('solicitado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo', 500)->nullable();
            $table->string('estado', 20)->default('solicitado');
            $table->string('codigo_erro', 40)->nullable();
            $table->unsignedSmallInteger('tentativas')->default(0);
            $table->jsonb('resultado')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
            $table->index(['cadastro_id', 'created_at']);
            $table->index(['origem', 'origem_id']);
        });
        // Duas solicitacoes iguais em voo para a mesma conta viram uma.
        DB::statement(
            "CREATE UNIQUE INDEX acessos_operacoes_ad_em_voo_unique ON acessos_operacoes_ad (lower(login_ad), acao)
             WHERE estado IN ('solicitado', 'enviado')"
        );

        Schema::create('acessos_entregas_senha', function (Blueprint $table): void {
            $table->foreignUuid('operacao_id')->primary()->constrained('acessos_operacoes_ad')->cascadeOnDelete();
            $table->foreignId('destinatario_id')->constrained('users')->cascadeOnDelete();
            $table->text('senha_cifrada');
            $table->timestamp('expira_em')->index();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('acessos_sincronizacoes_ad', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('disparada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('simulacao')->default(false);
            $table->string('estado', 20)->default('solicitada');
            $table->string('codigo_erro', 40)->nullable();
            $table->jsonb('totais')->nullable();
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
        });

        Schema::create('acessos_divergencias_ad', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('sincronizacao_id')->constrained('acessos_sincronizacoes_ad')->cascadeOnDelete();
            $table->foreignId('cadastro_id')->nullable()->constrained('acessos_cadastros')->cascadeOnDelete();
            $table->uuid('object_guid')->nullable();
            $table->string('login_ad', 100)->nullable();
            $table->string('tipo', 40);
            $table->jsonb('detalhe');
            $table->timestamp('created_at')->nullable();
            $table->index(['sincronizacao_id', 'tipo']);
        });

        // Fila database da conexao diretorio (esquema da tabela jobs do Laravel).
        Schema::create('acessos_fila_diretorio', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acessos_fila_diretorio');
        Schema::dropIfExists('acessos_divergencias_ad');
        Schema::dropIfExists('acessos_sincronizacoes_ad');
        Schema::dropIfExists('acessos_entregas_senha');
        Schema::dropIfExists('acessos_operacoes_ad');
        Schema::dropIfExists('acessos_auditoria');
        Schema::dropIfExists('acessos_cadastros');
    }
};
