<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compdec_prefeituras')) {
            return;
        }

        Schema::create('compdec_prefeituras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipio_id')
                ->unique()
                ->constrained('municipios')
                ->cascadeOnDelete()
                ->comment('Cada municipio tem uma unica prefeitura');

            // Dados do prefeito
            $table->string('prefeito_nome', 255)->nullable();
            $table->string('prefeito_telefone', 20)->nullable();
            $table->string('prefeito_celular', 20)->nullable();
            $table->string('prefeito_email', 255)->nullable();

            // Endereco
            $table->text('endereco')->nullable();
            $table->string('bairro', 120)->nullable();
            $table->string('cep', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // INSS
            $table->boolean('inss_tem_cobranca')->default(false);
            $table->decimal('inss_aliquota', 5, 2)->nullable()
                ->comment('% da aliquota INSS, ex: 2.50');
            $table->string('inss_lei_cobranca', 120)->nullable();
            $table->string('inss_responsavel', 255)->nullable();

            // Rastreabilidade ETL
            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('ID original do cedec_prefeitura.id');

            $table->timestamps();
            $table->softDeletes();

            $table->index('legacy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compdec_prefeituras');
    }
};
