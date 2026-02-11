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
        if (Schema::hasTable('rat_relato_envolvidos')) return;
        Schema::create('rat_relato_envolvidos', function (Blueprint $table) {
            $table->id();

            // --- Dados Gerais (Prefixo g_) ---
            $table->string('g_tipo_pessoa')->nullable()->comment('Tipo de Pessoa');
            $table->string('g_lesao_grau')->nullable()->comment('Grau da lesão');
            $table->string('g_lesao_grau_selecionado')->nullable()->comment('Grau da lesão (texto selecionado)');
            $table->string('g_atendimento_vitima_repassada')->nullable()->comment('Vítima repassada para');
            $table->boolean('g_envolvido_presenca')->nullable()->comment('É Militar/Policial/Agente de Segurança Pública?');
            $table->string('g_envolvido_tipo')->nullable()->comment('Militar, Policial/Agente de Segurança');
            $table->string('g_envolvido_orgao')->nullable()->comment('Órgão');
            $table->string('g_envolvido_uf', 2)->nullable()->comment('UF do Órgão');
            $table->string('g_envolvido_matricula')->nullable()->comment('Matrícula/NR');
            $table->boolean('g_envolvido_servico')->nullable()->comment('Em Serviço?');

            // --- Dados Pessoais e Documentos (Prefixo p_) ---
            $table->string('p_tipo')->nullable()->comment('Tipo de Documento');
            $table->string('p_tipo_selecionado')->nullable()->comment('Tipo de documento (texto selecionado)');
            $table->string('p_numero')->nullable()->comment('Número do Documento');
            $table->string('p_orgao_expedidor')->nullable()->comment('Órgão Expedidor');
            $table->string('p_nome_completo')->nullable()->comment('Nome Completo/Razão Social');
            $table->string('p_nome_fantasia')->nullable()->comment('Apelido/Nome Fantasia');
            $table->date('p_data_nascimento')->nullable()->comment('Data de Nascimento');
            $table->string('p_cpf')->nullable()->comment('CPF');
            $table->string('p_nome_mae')->nullable()->comment('Nome da mãe');
            $table->string('p_nome_pai')->nullable()->comment('Nome do pai');
            $table->string('p_ocupacao_atual')->nullable()->comment('Ocupação atual');
            $table->string('p_escolaridade')->nullable()->comment('Escolaridade');
            $table->string('p_cor_raca')->nullable()->comment('Cor/Raça');
            $table->string('p_cor_raca_cod')->nullable()->comment('Código Cor/Raça');
            $table->string('p_sexo')->nullable()->comment('Sexo');
            $table->string('p_sexo_selecionado')->nullable()->comment('Sexo (texto selecionado)');
            $table->string('p_estado_civil')->nullable()->comment('Estado Civil');
            $table->string('p_orientacao_sexual')->nullable()->comment('Orientação sexual');
            $table->string('p_identidade_genero')->nullable()->comment('Identidade de gênero');
            $table->string('p_nome_social')->nullable()->comment('Nome Social');
            $table->string('p_nacionalidade')->nullable()->comment('Nacionalidade');
            $table->string('p_pais_origem')->nullable()->comment('País de Origem');
            $table->string('p_naturalidade_uf', 2)->nullable()->comment('Naturalidade/UF');
            $table->boolean('p_turista')->nullable()->comment('Indivíduo é turista?');
            $table->boolean('p_situacao_rua')->nullable()->comment('É pessoa em situação de rua?');

            // --- Endereço ---
            $table->string('p_end_cep', 9)->nullable()->comment('CEP');
            $table->string('p_end_pais')->nullable()->comment('País');
            $table->string('p_end_estado_uf', 2)->nullable()->comment('Estado/UF');
            $table->string('p_end_municipio')->nullable()->comment('Município');
            $table->string('p_end_bairro')->nullable()->comment('Bairro');
            $table->string('p_end_logradouro')->nullable()->comment('Logradouro');
            $table->string('p_end_numero')->nullable()->comment('Número');
            $table->string('p_end_complemento')->nullable()->comment('Complemento');
            $table->string('p_end_km')->nullable()->comment('KM - Rodovia');
            $table->string('p_end_ibge')->nullable()->comment('Código IBGE');

            // --- Contato ---
            $table->string('p_telefone_residencial')->nullable()->comment('Telefone Residencial/Celular');
            $table->string('p_telefone_comercial')->nullable()->comment('Telefone Comercial/Celular');
            $table->string('p_email')->nullable()->comment('E-mail');
            $table->text('p_motivo_ausencia_contato')->nullable()->comment('Motivo ausência de Telefone/E-mail');

            // --- Auditoria e Timestamps ---
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->comment('Quem criou o registro');

            // Configurações do Banco
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_relato_envolvidos');
    }
};
