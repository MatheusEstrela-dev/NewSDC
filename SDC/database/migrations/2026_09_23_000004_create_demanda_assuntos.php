<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demanda_assuntos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('categoria_id')->nullable()->constrained('demanda_categorias')->nullOnDelete();
            $table->string('nome', 150)->unique();
            $table->jsonb('campos_dinamicos')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('assunto_id')->nullable()->after('subcategoria')
                ->constrained('demanda_assuntos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assunto_id');
        });
        Schema::dropIfExists('demanda_assuntos');
    }
};
