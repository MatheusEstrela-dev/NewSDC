<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->string('status_ad', 30)->default('desconhecido');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('acessos_auditoria');
        Schema::dropIfExists('acessos_cadastros');
    }
};
