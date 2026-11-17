<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Respostas do checklist de analise da Segunda Secao do PAE (GMG 83/2024).
 * Uma linha por item do catalogo por protocolo; o catalogo vive em
 * App\Modules\Pae\Support\Checklist\ChecklistGmg83.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pae_checklist_respostas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
            $table->string('codigo', 10);
            $table->string('resposta', 20)->nullable();
            $table->text('observacao')->nullable();
            $table->unsignedSmallInteger('catalogo_versao');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['protocolo_id', 'codigo'], 'pae_checklist_resposta_unique');
        });

        DB::statement("ALTER TABLE pae_checklist_respostas ADD CONSTRAINT pae_checklist_resposta_check CHECK (resposta IS NULL OR resposta IN ('sim', 'nao', 'nao_aplicavel'))");
        DB::statement('ALTER TABLE pae_checklist_respostas ADD CONSTRAINT pae_checklist_preenchida_check CHECK (resposta IS NOT NULL OR observacao IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_checklist_respostas');
    }
};
