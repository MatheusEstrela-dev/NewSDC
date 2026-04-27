<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('protocolo')->nullable()->index();
            $table->string('status')->default('rascunho');
            $table->boolean('tem_vistoria')->default(false);
            $table->jsonb('dados_gerais')->nullable();
            $table->index('dados_gerais', 'idx_rats_dados_gerais', 'gin');
            $table->jsonb('local')->nullable();
            $table->index('local', 'idx_rats_local', 'gin');
            $table->jsonb('endereco')->nullable();
            $table->index('endereco', 'idx_rats_endereco', 'gin');
            $table->jsonb('comunicacao')->nullable();
            $table->index('comunicacao', 'idx_rats_comunicacao', 'gin');
            $table->foreignId('orgao_emissor_id')->nullable()->constrained('orgaos')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rats');
    }
};
