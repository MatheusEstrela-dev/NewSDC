<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alinha o banco ao backend:
 *  1. Adiciona colunas JSON ao rats (recursos, envolvidos, vistoria, historico)
 *  2. Cria rat_imagens — tabela dedicada para arquivos/imagens do RAT
 *     (substitui o campo JSON rats.anexos que nunca foi criado no BD)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. Colunas JSON ausentes na tabela rats
        // ------------------------------------------------------------------
        Schema::table('rats', function (Blueprint $table) {
            if (! Schema::hasColumn('rats', 'recursos')) {
                $table->json('recursos')->nullable()->after('comunicacao');
            }
            if (! Schema::hasColumn('rats', 'envolvidos')) {
                $table->json('envolvidos')->nullable()->after('recursos');
            }
            if (! Schema::hasColumn('rats', 'vistoria')) {
                $table->json('vistoria')->nullable()->after('envolvidos');
            }
            if (! Schema::hasColumn('rats', 'historico')) {
                $table->json('historico')->nullable()->after('vistoria');
            }
        });

        // ------------------------------------------------------------------
        // 2. Tabela dedicada para imagens/arquivos do RAT
        // ------------------------------------------------------------------
        if (! Schema::hasTable('rat_imagens')) {
            Schema::create('rat_imagens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('rat_id', 36)->index();
                $table->string('nome');
                $table->string('path');
                $table->string('tipo', 100)->nullable();
                $table->unsignedBigInteger('tamanho')->default(0);
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->foreign('rat_id')->references('id')->on('rats')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rat_imagens');

        Schema::table('rats', function (Blueprint $table) {
            foreach (['recursos', 'envolvidos', 'vistoria', 'historico'] as $col) {
                if (Schema::hasColumn('rats', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
