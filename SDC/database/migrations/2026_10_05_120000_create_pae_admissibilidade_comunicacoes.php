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
        Schema::table('pae_protocolos', function (Blueprint $table): void {
            $table->boolean('admissibilidade_legada_sem_triagem')->default(false);
        });

        DB::table('pae_protocolos')->update(['admissibilidade_legada_sem_triagem' => true]);

        Schema::create('pae_protocolo_municipios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->foreignId('municipio_id')->constrained('municipios');
            $table->boolean('na_zas');
            $table->boolean('na_zss');
            $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('confirmado_em')->nullable();
            $table->timestamps();
            $table->unique(['protocolo_id', 'municipio_id'], 'pae_protocolo_municipio_unique');
        });
        DB::statement('ALTER TABLE pae_protocolo_municipios ADD CONSTRAINT pae_municipio_zona_check CHECK (na_zas OR na_zss)');

        Schema::create('pae_admissibilidade_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->string('chave', 50);
            $table->string('resultado', 20);
            $table->text('justificativa')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['protocolo_id', 'chave'], 'pae_admissibilidade_item_unique');
        });
        DB::statement("ALTER TABLE pae_admissibilidade_itens ADD CONSTRAINT pae_item_resultado_check CHECK (resultado IN ('sim', 'nao', 'nao_aplicavel'))");

        Schema::create('pae_admissibilidade_decisoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->text('fundamentacao');
            $table->jsonb('fundamentos')->nullable();
            $table->jsonb('checklist_snapshot');
            $table->date('submetido_em')->nullable();
            $table->boolean('transitorio_confirmado')->default(false);
            $table->date('notificado_em')->nullable();
            $table->date('prazo_correcao_em')->nullable();
            $table->string('num_sei', 100)->nullable();
            $table->foreignId('decidido_por')->constrained('users');
            $table->timestampTz('decidido_em');
            $table->timestamps();
            $table->index(['protocolo_id', 'decidido_em'], 'pae_admissibilidade_decisao_protocolo_idx');
        });
        DB::statement("ALTER TABLE pae_admissibilidade_decisoes ADD CONSTRAINT pae_decisao_tipo_check CHECK (tipo IN ('admitido', 'correcao_solicitada', 'reprovado_sumariamente'))");

        Schema::create('pae_comunicacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->string('origem_tipo', 40);
            $table->unsignedBigInteger('origem_id');
            $table->string('destinatario_tipo', 20);
            $table->foreignId('municipio_id')->nullable()->constrained('municipios');
            $table->text('motivos')->nullable();
            $table->string('status', 20)->default('pendente');
            $table->date('dt_envio')->nullable();
            $table->string('num_sei', 100)->nullable();
            $table->string('comprovante_path')->nullable();
            $table->string('comprovante_nome_original')->nullable();
            $table->string('comprovante_mime', 100)->nullable();
            $table->unsignedBigInteger('comprovante_tamanho_bytes')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('registrado_em')->nullable();
            $table->timestamps();
            $table->index(['protocolo_id', 'status'], 'pae_comunicacoes_protocolo_status_idx');
        });
        DB::statement("ALTER TABLE pae_comunicacoes ADD CONSTRAINT pae_comunicacao_destino_check CHECK ((destinatario_tipo = 'feam' AND municipio_id IS NULL) OR (destinatario_tipo = 'compdec' AND municipio_id IS NOT NULL))");
        DB::statement("ALTER TABLE pae_comunicacoes ADD CONSTRAINT pae_comunicacao_status_check CHECK (status IN ('pendente', 'registrada'))");
        DB::statement('CREATE UNIQUE INDEX pae_comunicacoes_origem_destino_unique ON pae_comunicacoes (protocolo_id, origem_tipo, origem_id, destinatario_tipo, COALESCE(municipio_id, 0))');
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_comunicacoes');
        Schema::dropIfExists('pae_admissibilidade_decisoes');
        Schema::dropIfExists('pae_admissibilidade_itens');
        Schema::dropIfExists('pae_protocolo_municipios');

        Schema::table('pae_protocolos', function (Blueprint $table): void {
            $table->dropColumn('admissibilidade_legada_sem_triagem');
        });
    }
};
