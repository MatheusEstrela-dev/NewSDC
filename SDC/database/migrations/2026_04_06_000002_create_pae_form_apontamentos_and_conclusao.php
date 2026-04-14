<?php

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
                $table->unsignedBigInteger('pae_form_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('status', 50)->nullable();
                $table->integer('ordem')->default(0);
                $table->text('conteudo')->nullable();

                $table->foreign('pae_form_id', 'fk_apontamentos_form')
                    ->references('id')->on('pae_forms')->onDelete('cascade');

                $table->index('pae_form_id', 'idx_apontamentos_form');
            });
        }

        if (!Schema::hasTable('pae_form_conclusao')) {
            Schema::create('pae_form_conclusao', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pae_form_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('status', 50)->nullable();
                $table->integer('ordem')->default(0);
                $table->text('conteudo')->nullable();

                $table->foreign('pae_form_id', 'fk_conclusao_form')
                    ->references('id')->on('pae_forms')->onDelete('cascade');

                $table->index('pae_form_id', 'idx_conclusao_form');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_form_conclusao');
        Schema::dropIfExists('pae_form_apontamentos');
    }
};
