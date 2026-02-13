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
        Schema::create('pae_forms', function (Blueprint $table) {
            $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT

            // Relacionamento com Protocolo (via num_protocolo)
            $table->string('protocolo_id', 50)->nullable();
            
            // UUID para acesso externo/público
            $table->uuid('uuid_publico')->unique();
            
            $table->string('status', 50)->default('RASCUNHO');
            
            // Dados da Barragem e Responsáveis
            $table->string('barragem_nome', 255)->nullable();
            $table->string('emp_responsavel_nome', 255)->nullable();
            $table->string('coord_pae_nome', 255)->nullable();
            $table->string('coord_pae_email', 255)->nullable();
            $table->string('coord_mun_def_civ', 255)->nullable();
            $table->string('coord_mun_compdec', 255)->nullable();
            
            // Características Técnicas
            $table->string('metodo_construtivo', 100)->nullable();
            $table->integer('num_zas')->nullable();
            $table->smallInteger('nivel_emergencia')->nullable();
            
            $table->text('objetivo')->nullable();
            $table->text('contexto')->nullable();

            // Auditoria de Usuários (Foreign Keys)
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            // Timestamps e SoftDeletes
            $table->timestamps();
            $table->softDeletes();

            // Índices Manuais
            $table->index('protocolo_id', 'idx_forms_protocolo');
            
            // Constraint específica para o protocolo (vinculada ao num_protocolo)
            $table->foreign('protocolo_id', 'fk_forms_protocolo')
                  ->references('num_protocolo')
                  ->on('pae_protocolos')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_forms');
    }
};