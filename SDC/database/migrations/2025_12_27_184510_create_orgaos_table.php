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
        Schema::create('orgaos', function (Blueprint $table) {
            $table->id();

            // Identificação
            $table->string('codigo', 50)->unique()->index()->comment('Código oficial do órgão');
            $table->string('nome', 255);
            $table->enum('tipo', ['compdec', 'redec', 'cedec'])->index();

            // Localização e Jurisdição
            $table->foreignId('municipio_id')
                ->nullable()
                ->constrained('municipios')
                ->nullOnDelete()
                ->comment('Município sede (nullable para CEDEC/REDEC estaduais)');

            // Hierarquia (self-referential)
            $table->foreignId('orgao_superior_id')
                ->nullable()
                ->constrained('orgaos')
                ->nullOnDelete()
                ->comment('COMPDEC → REDEC → CEDEC');

            // Status
            $table->enum('status', ['ativo', 'inativo', 'em_implantacao', 'suspenso'])
                ->default('ativo')
                ->index();

            // Contatos
            $table->string('email', 255)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->text('endereco')->nullable();

            // Responsável
            $table->string('responsavel_nome', 255)->nullable();
            $table->string('responsavel_cpf', 14)->nullable();
            $table->string('responsavel_telefone', 20)->nullable();
            $table->string('responsavel_email', 255)->nullable();

            // Geolocalização
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Abrangência (JSON: array de município IDs cobertos)
            $table->json('abrangencia')->nullable()
                ->comment('IDs dos municípios cobertos por este órgão');

            // Metadados adicionais
            $table->json('metadata')->nullable()
                ->comment('Campos extras: site, horário, estrutura, etc');

            $table->timestamps();
            $table->softDeletes();

            // Índices compostos para performance
            $table->index(['tipo', 'status']);
            $table->index(['municipio_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orgaos');
    }
};
