<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pae_form_apontamentos')) {
            Schema::create('pae_form_apontamentos', function (Blueprint $table) {
                $table->id();

                $table->foreignId('pae_form_id')
                      ->constrained('pae_forms')
                      ->onDelete('cascade');

                $table->foreignId('parent_id')
                      ->nullable()
                      ->constrained('pae_form_apontamentos')
                      ->onDelete('cascade');

                $table->string('status', 50)->default('CONFORME');
                $table->integer('ordem')->default(0);
                $table->text('conteudo')->nullable();

                $table->timestamp('updated_at')
                      ->useCurrent()
                      ->useCurrentOnUpdate();

                $table->index('pae_form_id', 'idx_apontamentos_form');
                $table->index('parent_id', 'idx_apontamentos_parent');
            });
        }

        if (!Schema::hasTable('pae_form_conclusao')) {
            Schema::create('pae_form_conclusao', function (Blueprint $table) {
                $table->id();

                $table->foreignId('pae_form_id')
                      ->constrained('pae_forms')
                      ->onDelete('cascade');

                $table->foreignId('parent_id')
                      ->nullable()
                      ->constrained('pae_form_conclusao')
                      ->onDelete('cascade');

                $table->string('status', 50)->default('CONFORME');
                $table->integer('ordem')->default(0);
                $table->text('conteudo')->nullable();

                $table->timestamp('updated_at')
                      ->useCurrent()
                      ->useCurrentOnUpdate();

                $table->index('pae_form_id', 'idx_conclusao_form');
                $table->index('parent_id', 'idx_conclusao_parent');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_form_conclusao');
        Schema::dropIfExists('pae_form_apontamentos');
    }
};
