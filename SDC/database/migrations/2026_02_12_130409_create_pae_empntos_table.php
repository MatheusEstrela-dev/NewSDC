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
        Schema::create('pae_empntos', function (Blueprint $table) {
            $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT

            $table->string('nome', 255);
            $table->string('status', 50)->default('OPERACAO');

            // Chaves Estrangeiras
            $table->unsignedBigInteger('municipio_id')->nullable();
            
            $table->foreignId('pae_empdor_id')
                  ->nullable()
                  ->constrained('pae_empdors')
                  ->onDelete('set null');

            $table->unsignedBigInteger('regiao_id')->nullable();

            $table->foreignId('pae_coordenador_id')
                  ->nullable()
                  ->constrained('pae_coordenador', 'id_coordenador') // Referencia id_coordenador
                  ->onDelete('set null');

            // Atributos Técnicos
            $table->string('m_construcao', 100)->nullable();
            $table->string('material', 100)->nullable();
            $table->string('finalidade', 100)->nullable();
            $table->decimal('volume', 15, 2)->nullable();
            $table->integer('pop_zas')->nullable();
            $table->string('orgao_fisc', 50)->nullable();

            // Dados de Contato (Coordenador Principal)
            $table->string('coordenador', 110)->nullable();
            $table->string('tel_coordenador', 20)->nullable();
            $table->string('email_coord', 120)->nullable();

            // Dados de Contato (Substituto)
            $table->string('mina', 100)->nullable();
            $table->string('coordenador_sub', 110)->nullable();
            $table->string('tel_coordenador_sub', 20)->nullable();
            $table->string('email_coord_sub', 120)->nullable();

            // Auditoria e Controle
            $table->string('user_update', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices Manuais
            $table->index('municipio_id', 'idx_empntos_municipio');
            $table->index('pae_empdor_id', 'idx_empntos_empdor');
            $table->index('status', 'idx_empntos_status');
            $table->index('pae_coordenador_id', 'idx_empntos_coordenador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_empntos');
    }
};