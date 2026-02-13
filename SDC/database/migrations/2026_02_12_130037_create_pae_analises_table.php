<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pae_analises')) {
            Schema::create('pae_analises', function (Blueprint $table) {
                // id bigint unsigned NOT NULL AUTO_INCREMENT
                $table->id();

                // Chave Estrangeira para Protocolos
                $table->foreignId('pae_protocolo_id')
                      ->constrained('pae_protocolos')
                      ->onDelete('cascade');

                // Chave Estrangeira para Users (Pode ser nula conforme seu SQL)
                $table->foreignId('user_id')
                      ->nullable()
                      ->constrained('users')
                      ->onDelete('set null');

                $table->string('status', 50)->default('FINALIZADA');
                $table->text('parecer');
                $table->string('situacao', 50)->nullable();
                $table->text('obs')->nullable();
                $table->integer('tipo')->nullable();
                $table->string('anexo', 255)->nullable();

                // created_at, updated_at e deleted_at (SoftDeletes)
                $table->timestamps();
                $table->softDeletes();

                // Índices adicionais (O Laravel já cria para as FKs, mas para seguir seu SQL:)
                $table->index('pae_protocolo_id', 'idx_analises_protocolo');
                $table->index('user_id', 'idx_analises_user');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_analises');
    }
};