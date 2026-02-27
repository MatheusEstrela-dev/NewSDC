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
            $table->json('dados_gerais')->nullable();
            $table->json('local')->nullable();
            $table->json('endereco')->nullable();
            $table->json('comunicacao')->nullable();
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
