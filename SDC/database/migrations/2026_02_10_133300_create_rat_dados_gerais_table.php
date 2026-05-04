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
        if (Schema::hasTable('rat_relato_dados_gerais')) return;
        Schema::create('rat_relato_dados_gerais', function (Blueprint $table) {
            $table->id();
            
            $table->string('created_by', 191)->nullable();

            // Datas e Horários
            $table->dateTime('data_fato')->nullable()->comment('Data/Hora do Fato');
            $table->dateTime('data_inicio_atividade')->nullable()->comment('Data/Hora Início da Atividade');
            $table->dateTime('data_termino_atividade')->nullable()->comment('Data/Hora Término da Atividade');

            // Natureza e Códigos
            $table->string('nat_codigo', 191)->nullable()->comment('Código da Ocorrência');
            $table->unsignedInteger('nat_cobrade_id')->nullable()->comment('ID do COBRADE');
            $table->string('nat_ocorrencia', 191)->nullable()->comment('Código da Ocorrência');
            $table->string('nat_nome_operacao', 191)->nullable()->comment('Nome da Operação');

            // Unidade Responsável e BO
            $table->string('uni_responsavel_municipio', 191)->nullable()->comment('Município');
            $table->string('uni_responsavel_codigo', 191)->nullable()->comment('Código');
            $table->string('uni_responsavel_unidade', 191)->nullable()->comment('Nome da Unidade');
            $table->string('uni_bo_cod_unidade', 191)->nullable()->comment('BO - Cod. Unidade');
            $table->unsignedSmallInteger('uni_bo_ano')->nullable()->comment('BO - Ano');
            $table->string('uni_bo_sequencial', 191)->nullable()->comment('BO - Sequencial');

            // Comunicação
            $table->dateTime('com_ocorrencia_data')->nullable()->comment('Comunicação - Data/Hora');
            $table->text('com_ocorrencia_atendimento')->nullable()->comment('Como foi solicitado o atendimento');

            // Localização e Endereço
            $table->string('local_pais', 191)->nullable()->comment('País');
            $table->string('local_cruzamento', 191)->nullable()->comment('Cruzamento');
            $table->string('local_estadouf', 2)->nullable()->comment('Estado/UF');
            $table->string('local_municipio', 191)->nullable()->comment('Município');
            $table->string('local_cep', 9)->nullable()->comment('CEP do Logradouro 1');
            $table->string('local_logradoura_1', 191)->nullable()->comment('Logradouro 1');
            $table->string('local_bairro', 191)->nullable()->comment('Bairro');
            $table->string('local_complemento', 191)->nullable()->comment('Complemento');
            $table->string('local_numero', 191)->nullable()->comment('Número');
            $table->string('local_km', 191)->nullable()->comment('KM');
            $table->text('local_ponto_referencia')->nullable()->comment('Ponto de Referência');

            // Coordenadas Geográficas (Precisão Decimal)
            $table->decimal('local_latitude', 10, 8)->nullable()->comment('Latitude do endereço');
            $table->decimal('local_longitude', 11, 8)->nullable()->comment('Longitude do endereço');

            // Detalhes do Local
            $table->string('local_ocorrencia_tipo', 191)->nullable()->comment('Tipo de Localização da Ocorrência');
            $table->string('local_ocorrencia_estradas_rodovias', 191)->nullable()->comment('Estradas/Rodovias');
            $table->string('local_unidade_militar', 191)->nullable()->comment('Unidade Militar');

            // Check de Vistoria
            $table->boolean('tem_vistoria')->default(0)->comment('Indica se possui vistoria imobiliária');

            // Timestamps e SoftDeletes
            $table->timestamps();
            $table->softDeletes();

            if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_relato_dados_gerais');
    }
};
