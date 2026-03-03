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
        Schema::create('cedec_municipio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('redec_id')->nullable();
            $table->string('nome', 70)->nullable();
            $table->string('rpm', 191)->nullable();
            $table->string('p_nome', 191)->nullable();
            $table->string('macroregiao', 255)->nullable();
            $table->string('latitude', 13)->nullable();
            $table->string('longitude', 13)->nullable();
            $table->double('latitude_dec')->nullable();
            $table->double('longitude_dec')->nullable();
            $table->double('distancia_bh')->nullable();
            $table->double('populacao')->nullable();
            $table->string('territorio_desenv', 110)->nullable();
            $table->string('tel_prefeitura', 20)->nullable();
            $table->string('fax_prefeitura', 20)->nullable();
            $table->string('email_prefeitura', 110)->nullable();
            $table->string('endereco', 110)->nullable();
            $table->string('bairro', 45)->nullable();
            $table->string('cep', 45)->nullable();
            $table->string('nome_prefeito', 70)->nullable();
            $table->string('tel_prefeito', 20)->nullable();
            $table->string('cel_prefeito', 20)->nullable();
            $table->string('email_prefeito', 110)->nullable();
            $table->string('foto_prefeiro', 110)->nullable();
            $table->integer('pop_rural')->nullable();
            $table->integer('qtd_pipa')->nullable();
            $table->string('area', 45)->nullable();
            $table->decimal('aliquota_iss', 16, 2)->nullable();
            $table->string('resp_cob_iss', 15)->nullable();
            $table->string('num_lei_iss', 30)->nullable();
            $table->string('cobra_iss', 10)->nullable();
            $table->string('CodUf', 2)->nullable();
            $table->string('Codmundv', 10)->nullable();
            $table->string('Codmun', 10)->nullable();
            $table->integer('id_meso')->nullable();
            $table->integer('id_micro')->nullable();
            $table->timestamps();
            $table->tinyInteger('at_cisterna')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cedec_municipio');
    }
};
