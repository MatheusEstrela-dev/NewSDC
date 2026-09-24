<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorias antes de assuntos: a FK de assuntos exige a tabela. Antes as
        // duas viviam em migrations separadas, com a de categorias rodando DEPOIS,
        // e instalacao limpa falhava.
        if (! Schema::hasTable('demanda_categorias')) {
            Schema::create('demanda_categorias', function (Blueprint $table): void {
                $table->id();
                $table->string('nome');
                $table->text('descricao')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('demanda_categorias')->nullOnDelete();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('demanda_assuntos')) {
            Schema::create('demanda_assuntos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('categoria_id')->nullable()->constrained('demanda_categorias')->nullOnDelete();
                $table->string('nome', 150)->unique();
                $table->jsonb('campos_dinamicos')->nullable();
                $table->jsonb('form_automacao')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('tasks', 'assunto_id')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->foreignId('assunto_id')->nullable()->after('subcategoria')
                    ->constrained('demanda_assuntos')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'assunto_id')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('assunto_id');
            });
        }
        Schema::dropIfExists('demanda_assuntos');
        Schema::dropIfExists('demanda_categorias');
    }
};
