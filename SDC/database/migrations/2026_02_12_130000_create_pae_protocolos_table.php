<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pae_protocolos')) {
            Schema::create('pae_protocolos', function (Blueprint $table) {
                $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT

                $table->string('sigibar', 100)->nullable()->unique();
                $table->string('num_protocolo', 50)->unique();
                $table->string('status', 50)->default('NOVO');
                $table->text('situacao')->nullable();

                // Relacionamento Polimórfico (Conteúdo)
                $table->string('conteudo_type', 50)->nullable();
                $table->unsignedBigInteger('conteudo_id')->nullable();

                // Chaves Estrangeiras para Usuários (Analista e Auditoria)
                $table->foreignId('analista_atual_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');

                // Relacionamento com Empreendimentos
                $table->foreignId('pae_empnto_id')->nullable()->constrained('pae_empntos')->onDelete('set null');

                // Protocolo de origem (versionamento: 001 -> 002 -> ...)
                $table->foreignId('protocolo_origem_id')->nullable()->constrained('pae_protocolos')->onDelete('set null');

                $table->boolean('arquivado')->default(false);
                $table->string('sei_numero', 100)->nullable();
                
                // Datas
                $table->date('dt_entrada')->default(DB::raw('(CURRENT_DATE)'));
                $table->date('limite_analise')->nullable();
                $table->date('ccpae_venc')->nullable();
                
                // Outros Campos
                $table->string('ccpae', 100)->nullable();
                $table->string('empnto_search', 255)->nullable();
                $table->text('obs')->nullable();
                $table->string('sit_mancha', 255)->nullable();
                $table->string('user_update', 255)->nullable();

                // Timestamps e SoftDeletes
                $table->timestamps();
                $table->softDeletes();

                // Índices Manuais Adicionais
                $table->index(['conteudo_type', 'conteudo_id'], 'idx_protocolos_conteudo');
                $table->index('status', 'idx_protocolos_status');
                $table->index('dt_entrada', 'idx_protocolos_dt_entrada');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_protocolos');
    }
};