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
        Schema::create('treinamentos', function (Blueprint $table) {
            $table->id();

            // Informações Básicas
            $table->string('titulo', 255);
            $table->text('descricao')->nullable();
            $table->integer('carga_horaria')->comment('Carga horária total em horas');

            // Tipo e Status
            $table->enum('tipo', ['PRESENCIAL', 'EAD', 'HIBRIDO'])->default('PRESENCIAL')->index();
            $table->enum('status', ['PLANEJADO', 'EM_ANDAMENTO', 'CONCLUIDO', 'CANCELADO'])
                ->default('PLANEJADO')
                ->index();

            // Instrutor e Local
            $table->string('instrutor', 255)->nullable();
            $table->string('local', 255)->nullable()->comment('Local físico ou link para EAD');

            // Datas
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();

            // Controle de Vagas
            $table->integer('numero_vagas')->nullable()->comment('NULL = ilimitado');

            // Frequência mínima para aprovação (padrão 75%)
            $table->decimal('percentual_frequencia_minimo', 5, 2)->default(75.00);

            // Metadados
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices compostos
            $table->index(['status', 'data_inicio']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treinamentos');
    }
};
