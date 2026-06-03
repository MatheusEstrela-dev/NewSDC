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
        if (Schema::hasTable('rat_relato_vistoria')) return;
        Schema::create('rat_relato_vistoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // --- Solicitante ---
            $table->string('v_solicitante_nome', 255)->nullable()->comment('Nome do Solicitante');
            $table->string('v_solicitante_cpf', 14)->nullable()->comment('CPF do Solicitante');
            $table->string('v_solicitante_telefone', 20)->nullable()->comment('Telefone do Solicitante');
            $table->string('v_solicitante_endereco', 255)->nullable()->comment('Endereco do Solicitante');
            $table->string('v_solicitante_bairro', 100)->nullable()->comment('Bairro do Solicitante');
            $table->string('v_solicitante_cep', 9)->nullable()->comment('CEP do Solicitante');

            // --- Dados do Local e Imóvel ---
            $table->string('v_local_endereco', 255)->nullable()->comment('Endereco do Local da Ocorrencia');
            $table->string('v_local_complemento', 255)->nullable()->comment('Complemento do Local');
            $table->string('v_tipo_area', 50)->nullable()->comment('Tipo de Area');
            $table->string('v_tipo_area_descricao', 255)->nullable()->comment('Descricao do Tipo de Area');
            $table->string('v_tipo_imovel', 50)->nullable()->comment('Tipo do Imovel');
            $table->string('v_tipo_imovel_outro_descricao', 255)->nullable()->comment('Descricao Outro Tipo de Imovel');
            $table->string('v_tipo_construcao', 50)->nullable()->comment('Tipo de Construcao');
            $table->string('v_tipo_construcao_outro_descricao', 255)->nullable()->comment('Descricao Outro Tipo de Construcao');
            $table->string('v_tipo_edificacao', 50)->nullable()->comment('Tipo de Edificacao');
            $table->string('v_tipo_terreno_relevo', 50)->nullable()->comment('Tipo de Terreno/Relevo');
            $table->string('v_sistema_estrutural', 50)->nullable()->comment('Sistema Estrutural');
            $table->unsignedSmallInteger('v_numero_pavimentos')->nullable()->comment('Numero de Pavimentos');
            $table->string('v_estado_conservacao', 20)->nullable()->comment('Estado de Conservacao');
            $table->string('v_regime_ocupacao', 20)->nullable()->comment('Regime de Ocupacao');

            // --- Moradores e Situação Social ---
            $table->string('v_proprietario_morador', 255)->nullable()->comment('Nome do Proprietario/Morador');
            $table->string('v_contato_telefone', 20)->nullable()->comment('Telefone de Contato');
            $table->unsignedSmallInteger('v_numero_moradores')->nullable()->comment('Numero de Moradores');
            $table->string('v_ha_idosos', 10)->nullable()->comment('Ha Idosos');
            $table->string('v_ha_criancas', 10)->nullable()->comment('Ha Criancas');
            $table->string('v_ha_dificuldade_locomocao', 10)->nullable()->comment('Ha Pessoas com Dificuldade de Locomocao');

            // --- Localização Geográfica ---
            $table->string('v_endereco_imovel', 255)->nullable()->comment('Endereco do Imovel');
            $table->string('v_bairro', 100)->nullable()->comment('Bairro');
            $table->unsignedBigInteger('v_municipio')->nullable()->comment('ID do Municipio');
            $table->string('v_cep', 10)->nullable()->comment('CEP');
            $table->decimal('v_latitude', 10, 8)->nullable()->comment('Latitude');
            $table->decimal('v_longitude', 11, 8)->nullable()->comment('Longitude');

            // --- Infraestrutura e Acesso ---
            $table->string('v_abastecimento_agua', 10)->nullable()->comment('Abastecimento de Agua');
            $table->string('v_esgotamento_sanitario', 10)->nullable()->comment('Esgotamento Sanitario');
            $table->string('v_drenagem_superficial', 10)->nullable()->comment('Drenagem Superficial');
            $table->string('v_sistema_viario_acesso', 255)->nullable()->comment('Sistema Viario de Acesso');
            $table->string('v_tipo_revestimento', 50)->nullable()->comment('Tipo de Revestimento');
            $table->string('v_condicoes_acesso', 500)->nullable()->comment('Condicoes de Acesso');
            $table->unsignedSmallInteger('v_numero_moradias_terreno')->nullable()->comment('Numero de Moradias no Terreno');
            $table->string('v_distancia_encosta', 50)->nullable()->comment('Distancia da Encosta');

            // --- Aspectos Técnicos Estruturais (Campos Longos) ---
            $table->string('v_material_construtivo', 255)->nullable()->comment('Material Construtivo');
            $table->string('v_conservacao_estrutural', 1000)->nullable()->comment('Conservacao Estrutural');
            $table->string('v_elementos_estruturais', 1000)->nullable()->comment('Elementos Estruturais');
            $table->string('v_elementos_construtivos', 1000)->nullable()->comment('Elementos Construtivos');
            $table->string('v_agentes_potencializadores', 1000)->nullable()->comment('Agentes Potencializadores');
            $table->string('v_processos_geodinamicos', 1000)->nullable()->comment('Processos Geodinamicos');
            $table->string('v_danos_estruturais', 20)->nullable()->comment('Nivel de Danos Estruturais');
            $table->string('v_manutencao_uso_ocupacao', 20)->nullable()->comment('Nivel de Manutencao');
            $table->text('v_historico')->nullable()->comment('Historico/Observacoes');
            $table->string('v_tipo_destinacao', 20)->nullable()->comment('Tipo de Destinacao');
            $table->string('v_tipo_localizacao', 20)->nullable()->comment('Tipo de Localizacao');

            // --- Encaminhamentos (Booleanos) ---
            $table->boolean('v_enc_interdicao_parcial')->default(false)->comment('Interdicao Parcial');
            $table->boolean('v_enc_interdicao_total')->default(false)->comment('Interdicao Total');
            $table->boolean('v_enc_remocao_temporaria')->default(false)->comment('Remocao Temporaria');
            $table->boolean('v_enc_remocao_definitiva')->default(false)->comment('Remocao Definitiva');
            $table->boolean('v_enc_isolamento_area')->default(false)->comment('Isolamento da Area');
            $table->boolean('v_enc_desocupacao_abrigo')->default(false)->comment('Desocupacao Abrigo');
            $table->boolean('v_enc_notificacao_responsavel')->default(false)->comment('Notificacao Responsavel');
            $table->boolean('v_enc_contratacao_responsavel')->default(false)->comment('Contratacao Responsavel');
            $table->boolean('v_enc_comunicacao_orgaos')->default(false)->comment('Comunicacao Orgaos');
            $table->boolean('v_enc_apoio_social')->default(false)->comment('Apoio Social');
            $table->boolean('v_enc_outros')->default(false)->comment('Outros Encaminhamentos');
            $table->text('v_enc_outros_descricao')->nullable()->comment('Descricao Outros Encaminhamentos');

            // --- Patologias (Booleanos) ---
            $table->boolean('v_patologia_rachaduras')->default(false)->comment('Rachaduras');
            $table->boolean('v_patologia_trincas')->default(false)->comment('Trincas');
            $table->boolean('v_patologia_fissuras_estruturais')->default(false)->comment('Fissuras Estruturais');
            $table->boolean('v_patologia_deformacoes_estruturais')->default(false)->comment('Deformacoes Estruturais');
            $table->boolean('v_patologia_infiltracoes')->default(false)->comment('Infiltracoes');
            $table->boolean('v_patologia_corrosao_armaduras')->default(false)->comment('Corrosao de Armaduras');
            $table->boolean('v_patologia_desagregacao')->default(false)->comment('Desagregacao');
            $table->boolean('v_patologia_eflorescencia')->default(false)->comment('Eflorescencia');
            $table->boolean('v_patologia_desplacamento')->default(false)->comment('Desplacamento');
            $table->boolean('v_patologia_fundacoes')->default(false)->comment('Comprometimento Fundacoes');
            $table->boolean('v_patologia_instabilidade_talude')->default(false)->comment('Instabilidade Talude');
            $table->boolean('v_patologia_movimentacao_solo')->default(false)->comment('Movimentacao Solo');
            $table->boolean('v_patologia_tombamento_muralhas')->default(false)->comment('Tombamento Muralhas');
            $table->boolean('v_patologia_inundacoes')->default(false)->comment('Inundacoes');
            $table->boolean('v_patologia_alagamentos')->default(false)->comment('Alagamentos');
            $table->boolean('v_patologia_enxurradas')->default(false)->comment('Enxurradas');
            $table->boolean('v_patologia_madeira')->default(false)->comment('Patologia Madeira');
            $table->boolean('v_patologia_elementos_nao_estruturais')->default(false)->comment('Elementos Nao Estruturais');
            $table->boolean('v_patologia_falha_drenagem')->default(false)->comment('Falha Drenagem');
            $table->boolean('v_patologia_queda_arvores')->default(false)->comment('Queda Arvores');
            $table->boolean('v_patologia_outros')->default(false)->comment('Outras Patologias');
            $table->text('v_patologia_outros_descricao')->nullable()->comment('Descricao Outras Patologias');

            // --- Bens Afetados (Booleanos) ---
            $table->boolean('v_bens_residencia')->default(false)->comment('Residencia');
            $table->boolean('v_bens_muros')->default(false)->comment('Muros');
            $table->boolean('v_bens_vias_publicas')->default(false)->comment('Vias Publicas');
            $table->boolean('v_bens_pontes')->default(false)->comment('Pontes');
            $table->boolean('v_bens_viadutos')->default(false)->comment('Viadutos');
            $table->boolean('v_bens_comercios')->default(false)->comment('Comercios');
            $table->boolean('v_bens_galpoes')->default(false)->comment('Galpoes');
            $table->boolean('v_bens_predios_publicos')->default(false)->comment('Predios Publicos');
            $table->boolean('v_bens_edificios_publicos')->default(false)->comment('Edificios Publicos');
            $table->boolean('v_bens_outros')->default(false)->comment('Outros Bens');
            $table->text('v_bens_outros_descricao')->nullable()->comment('Descricao Outros Bens');

            // --- Órgãos Acionados (Booleanos e Legado) ---
            $table->boolean('v_orgao_copasa')->default(false)->comment('COPASA');
            $table->boolean('v_orgao_cemig')->default(false)->comment('CEMIG');
            $table->boolean('v_orgao_secretaria_municipal')->default(false)->comment('Secretaria Municipal');
            $table->boolean('v_orgao_defesa_civil_estadual')->default(false)->comment('Defesa Civil Estadual');
            $table->boolean('v_orgao_dnit')->default(false)->comment('DNIT');
            $table->boolean('v_orgao_outros')->default(false)->comment('Outros Orgaos');
            $table->text('v_orgao_outros_descricao')->nullable()->comment('Descricao Outros Orgaos');
            $table->text('v_bens_afetados')->nullable()->comment('Bens Afetados (legado)');
            $table->text('v_orgaos_acionados')->nullable()->comment('Orgaos Acionados (legado)');
            $table->text('v_orgaos_acionados_outros')->nullable()->comment('Outros Orgaos (legado)');

            $table->string('v_orgao_pm', 255)->nullable();
            $table->string('v_orgao_bm', 255)->nullable();
            $table->boolean('v_orgao_crea')->default(false)->comment('CREA');
            $table->boolean('v_orgao_emater')->default(false)->comment('EMATER');
            $table->boolean('v_orgao_seapa')->default(false)->comment('SEAPA');

            // --- Timestamps e SoftDeletes ---
            $table->timestamps();
            $table->softDeletes();

            // --- Índices ---
            $table->index('v_tipo_imovel', 'idx_v_tipo_imovel');
            $table->index('v_municipio', 'idx_v_municipio');

            if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                $table->engine    = 'InnoDB';
                $table->charset   = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_relato_vistoria');
    }
};
