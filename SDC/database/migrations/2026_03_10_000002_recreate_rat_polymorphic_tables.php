<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recria as tabelas da arquitetura polimórfica do RAT que foram
 * incorretamente removidas pela migration 2026_03_09_200001_drop_rat_legacy_tables.
 *
 * Tabelas recriadas (9 ao total):
 *  1. rat_redec
 *  2. rat_ocorrencias
 *  3. rat_ocorrencia_relatos
 *  4. rat_relato_recursos
 *  5. rat_recursos_empregados
 *  6. rat_recursos_componentes_guarnicao
 *  7. rat_relato_envolvidos
 *  8. rat_relato_vistoria
 *  9. rat_veiculos
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------ //
        // 1. rat_redec — Tabela de referência de regiões
        // ------------------------------------------------------------------ //
        if (! Schema::hasTable('rat_redec')) {
            Schema::create('rat_redec', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 100);
                $table->string('sigla', 10);
                $table->timestamps();

                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $table->engine    = 'InnoDB';
                    $table->charset   = 'utf8mb4';
                    $table->collation = 'utf8mb4_unicode_ci';
                }
            });
        }

        // ------------------------------------------------------------------ //
        // 2. rat_ocorrencias — Entidade principal do BOS polimórfico
        // ------------------------------------------------------------------ //
        if (! Schema::hasTable('rat_ocorrencias')) {
            Schema::create('rat_ocorrencias', function (Blueprint $table) {
                $table->id();

                $table->string('numero_bos', 50)->nullable()
                      ->unique('rat_ocorrencias_numero_bos_unique')
                      ->comment('Número do BOS no formato YYYY-SEQUENCIAL-001');

                $table->unsignedBigInteger('sequencial_ano')->nullable()
                      ->comment('Sequencial do BOS no ano corrente');

                $table->string('created_by', 191)->nullable();

                $table->tinyInteger('status')->default(0)
                      ->comment('0=Rascunho, 1=Finalizado');

                $table->timestamp('prazo_edicao')->nullable()
                      ->comment('Prazo limite para edição do rascunho');

                $table->string('updated_by', 191)->nullable();

                $table->unsignedBigInteger('ocorrencia_origem_id')->nullable()
                      ->comment('ID da ocorrência de origem (BOS pai)');

                $table->text('historico')->nullable()
                      ->comment('Histórico da ocorrência');

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('ocorrencia_origem_id', 'rat_ocorrencias_ocorrencia_origem_id_foreign')
                      ->references('id')
                      ->on('rat_ocorrencias')
                      ->onDelete('set null');

                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $table->engine    = 'InnoDB';
                    $table->charset   = 'utf8mb4';
                    $table->collation = 'utf8mb4_unicode_ci';
                }
            });
        }

        // ------------------------------------------------------------------ //
        // 3. rat_ocorrencia_relatos — Pivot polimórfico (ocorrência → relato)
        // ------------------------------------------------------------------ //
        if (! Schema::hasTable('rat_ocorrencia_relatos')) {
            Schema::create('rat_ocorrencia_relatos', function (Blueprint $table) {
                $table->id();

                $table->string('created_by', 191)->nullable();

                $table->unsignedBigInteger('ocorrencia_id')->nullable();

                $table->unsignedBigInteger('conteudo_id')->nullable();
                $table->string('conteudo_type', 191)->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('ocorrencia_id', 'rat_ocorrencia_relatos_ocorrencia_id_foreign')
                      ->references('id')
                      ->on('rat_ocorrencias')
                      ->onDelete('set null');

                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $table->engine    = 'InnoDB';
                    $table->charset   = 'utf8mb4';
                    $table->collation = 'utf8mb4_unicode_ci';
                }
            });
        }

        // ------------------------------------------------------------------ //
        // 4. rat_relato_recursos — Recursos empregados no atendimento
        // ------------------------------------------------------------------ //
        if (! Schema::hasTable('rat_relato_recursos')) {
            Schema::create('rat_relato_recursos', function (Blueprint $table) {
                $table->comment('Tabela de Recursos do Relato - Armazena informações principais dos recursos utilizados no atendimento');

                $table->id();

                $table->integer('seq')->comment('Sequencial do Recurso');
                $table->enum('recurso_tipo', ['viatura', 'pe', 'aereo', 'aquatico', 'outro'])
                      ->nullable()
                      ->comment('Tipo de Recurso');

                $table->boolean('recurso_problemas')->default(false)->comment('Problemas durante o atendimento');
                $table->text('recurso_descricao')->nullable()->comment('Descrição do problema');

                $table->string('viatura_tipo', 100)->nullable()->comment('Tipo da Viatura');
                $table->string('viatura_placa', 20)->nullable()->comment('Placa da viatura');
                $table->string('viatura_prefixo', 50)->nullable()->comment('Prefixo Numérico');
                $table->string('viatura_padrao', 50)->nullable()->comment('Prefixo Padrão');
                $table->string('viatura_orgao', 255)->nullable()->comment('Órgão responsável');
                $table->text('viatura_descricao')->nullable()->comment('Descrição/Observação');

                $table->dateTime('viatura_saida')->nullable()->comment('Data/Hora de Saída');
                $table->dateTime('viatura_chegada')->nullable()->comment('Data/Hora de Chegada');
                $table->decimal('viatura_km', 10, 2)->nullable()->comment('KM Percorrido');

                $table->string('viatura_local_origem', 255)->nullable()->comment('Local de Origem');
                $table->string('viatura_local_destino', 255)->nullable()->comment('Local de Destino');
                $table->integer('viatura_quantidade')->default(1)->comment('Quantidade de Recursos');
                $table->string('viatura_capacidade', 100)->nullable()->comment('Capacidade/Potência');

                $table->enum('viatura_condicao', ['otima', 'boa', 'regular', 'ruim', 'inoperante'])
                      ->nullable()
                      ->comment('Condição do Recurso');

                $table->string('viatura_operador', 255)->nullable()->comment('Operador/Responsável');
                $table->string('operador_masp', 20)->nullable()->comment('MASP do Operador');
                $table->boolean('operador_is_condutor')->default(false)->comment('Indica se é o condutor');
                $table->string('viatura_contato', 50)->nullable()->comment('Contato de Emergência');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index('seq', 'idx_rat_relato_recursos_seq');
                $table->index('recurso_tipo', 'idx_rat_relato_recursos_tipo');
                $table->index('viatura_placa', 'idx_rat_relato_recursos_placa');
                $table->index('created_by', 'idx_rat_relato_recursos_created_by');
                $table->index('created_at', 'idx_rat_relato_recursos_created_at');

                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $table->engine    = 'InnoDB';
                    $table->charset   = 'utf8mb4';
                    $table->collation = 'utf8mb4_unicode_ci';
                }
            });
        }

        // ------------------------------------------------------------------ //
        // 5. rat_recursos_empregados — Viaturas/pés empregados no relato
        // ------------------------------------------------------------------ //
        if (! Schema::hasTable('rat_recursos_empregados')) {
            Schema::create('rat_recursos_empregados', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('relato_recurso_id')->nullable();

                $table->enum('recurso_tipo', ['viatura', 'pe'])
                      ->nullable()
                      ->comment('Tipo de Recurso');

                $table->string('recurso_problemas', 191)->nullable()
                      ->comment('Problemas durante o atendimento');

                $table->text('recurso_descricao')->nullable()
                      ->comment('Descrição do problema');

                $table->enum('viatura_tipo', ['Viatura Principal', 'Viatura de Apoio', 'Órgãos externos ao SISP'])
                      ->nullable()
                      ->comment('Tipo da Viatura');

                $table->string('viatura_placa', 20)->nullable()->comment('Placa');
                $table->string('viatura_prefixo', 50)->nullable()->comment('Prefixo Numérico');
                $table->string('viatura_padrao', 50)->nullable()->comment('Prefixo Padrão');
                $table->string('viatura_orgao', 100)->nullable()->comment('Órgão');
                $table->text('viatura_descricao')->nullable()->comment('Descrição/Observação');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index('relato_recurso_id', 'rat_recursos_empregados_relato_recurso_id_index');
                $table->index('recurso_tipo', 'rat_recursos_empregados_recurso_tipo_index');
                $table->index('viatura_placa', 'rat_recursos_empregados_viatura_placa_index');
                $table->index('created_by', 'rat_recursos_empregados_created_by_index');
                $table->index('created_at', 'rat_recursos_empregados_created_at_index');

                $table->foreign('relato_recurso_id', 'rat_recursos_empregados_relato_recurso_id_foreign')
                      ->references('id')
                      ->on('rat_relato_recursos')
                      ->onDelete('cascade');

                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $table->engine    = 'InnoDB';
                    $table->charset   = 'utf8mb4';
                    $table->collation = 'utf8mb4_unicode_ci';
                }
            });
        }

        // ------------------------------------------------------------------ //
        // 6. rat_recursos_componentes_guarnicao — Agentes da guarnição
        // ------------------------------------------------------------------ //
        if (! Schema::hasTable('rat_recursos_componentes_guarnicao')) {
            Schema::create('rat_recursos_componentes_guarnicao', function (Blueprint $table) {
                $table->comment('Tabela de Componentes da Guarnição - Armazena informações dos agentes/servidores que participaram do atendimento');

                $table->id();

                $table->unsignedBigInteger('recurso_empregado_id')->nullable()
                      ->comment('ID do Recurso Empregado');
                $table->unsignedBigInteger('relato_recurso_id')->nullable()
                      ->comment('ID do Relato Recurso');

                $table->string('corporacao', 100)->nullable()->comment('Corporação');
                $table->string('matricula', 50)->nullable()->comment('Matrícula/NR/CPF');
                $table->string('masp', 20)->nullable()->comment('MASP - Matrícula de Serviço Público');
                $table->string('nome_completo', 255)->nullable()->comment('Nome Completo');
                $table->string('pg_cargo', 100)->nullable()->comment('PG/Cargo');
                $table->string('orgao', 255)->nullable()->comment('Órgão');
                $table->string('unidade', 255)->nullable()->comment('Unidade');
                $table->string('funcao', 255)->nullable()->comment('Função no Atendimento');

                $table->boolean('is_condutor')->default(false)
                      ->comment('É o Condutor do veículo');

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index('recurso_empregado_id', 'idx_rat_recursos_componentes_guarnicao_recurso_empregado_id');
                $table->index('relato_recurso_id', 'idx_rat_recursos_componentes_guarnicao_relato_recurso_id');
                $table->index('masp', 'idx_rat_recursos_componentes_guarnicao_masp');
                $table->index('matricula', 'idx_rat_recursos_componentes_guarnicao_matricula');
                $table->index('created_by', 'idx_rat_recursos_componentes_guarnicao_created_by');
                $table->index('created_at', 'idx_rat_recursos_componentes_guarnicao_created_at');

                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $table->engine    = 'InnoDB';
                    $table->charset   = 'utf8mb4';
                    $table->collation = 'utf8mb4_unicode_ci';
                }
            });
        }

        // ------------------------------------------------------------------ //
        // 7. rat_relato_envolvidos — Pessoas envolvidas na ocorrência
        // ------------------------------------------------------------------ //
        if (! Schema::hasTable('rat_relato_envolvidos')) {
            Schema::create('rat_relato_envolvidos', function (Blueprint $table) {
                $table->id();

                // Dados Gerais
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

                // Documentos e Dados Pessoais
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

                // Endereço
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

                // Contato
                $table->string('p_telefone_residencial')->nullable()->comment('Telefone Residencial/Celular');
                $table->string('p_telefone_comercial')->nullable()->comment('Telefone Comercial/Celular');
                $table->string('p_email')->nullable()->comment('E-mail');
                $table->text('p_motivo_ausencia_contato')->nullable()->comment('Motivo ausência de Telefone/E-mail');

                $table->timestamps();
                $table->softDeletes();
                $table->string('created_by')->nullable()->comment('Quem criou o registro');

                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $table->engine    = 'InnoDB';
                    $table->charset   = 'utf8mb4';
                    $table->collation = 'utf8mb4_unicode_ci';
                }
            });
        }

        // ------------------------------------------------------------------ //
        // 8. rat_relato_vistoria — Vistoria imobiliária da ocorrência
        // ------------------------------------------------------------------ //
        if (! Schema::hasTable('rat_relato_vistoria')) {
            Schema::create('rat_relato_vistoria', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                // Solicitante
                $table->string('v_solicitante_nome', 255)->nullable();
                $table->string('v_solicitante_cpf', 14)->nullable();
                $table->string('v_solicitante_telefone', 20)->nullable();
                $table->string('v_solicitante_endereco', 255)->nullable();
                $table->string('v_solicitante_bairro', 100)->nullable();
                $table->string('v_solicitante_cep', 9)->nullable();

                // Dados do Local
                $table->string('v_local_endereco', 255)->nullable();
                $table->string('v_local_complemento', 255)->nullable();
                $table->string('v_tipo_area', 50)->nullable();
                $table->string('v_tipo_area_descricao', 255)->nullable();
                $table->string('v_tipo_imovel', 50)->nullable();
                $table->string('v_tipo_imovel_outro_descricao', 255)->nullable();
                $table->string('v_tipo_construcao', 50)->nullable();
                $table->string('v_tipo_construcao_outro_descricao', 255)->nullable();
                $table->string('v_tipo_edificacao', 50)->nullable();
                $table->string('v_tipo_terreno_relevo', 50)->nullable();
                $table->string('v_sistema_estrutural', 50)->nullable();
                $table->unsignedSmallInteger('v_numero_pavimentos')->nullable();
                $table->string('v_estado_conservacao', 20)->nullable();
                $table->string('v_regime_ocupacao', 20)->nullable();

                // Moradores
                $table->string('v_proprietario_morador', 255)->nullable();
                $table->string('v_contato_telefone', 20)->nullable();
                $table->unsignedSmallInteger('v_numero_moradores')->nullable();
                $table->string('v_ha_idosos', 10)->nullable();
                $table->string('v_ha_criancas', 10)->nullable();
                $table->string('v_ha_dificuldade_locomocao', 10)->nullable();

                // Localização
                $table->string('v_endereco_imovel', 255)->nullable();
                $table->string('v_bairro', 100)->nullable();
                $table->unsignedBigInteger('v_municipio')->nullable();
                $table->string('v_cep', 10)->nullable();
                $table->decimal('v_latitude', 10, 8)->nullable();
                $table->decimal('v_longitude', 11, 8)->nullable();

                // Infraestrutura
                $table->string('v_abastecimento_agua', 10)->nullable();
                $table->string('v_esgotamento_sanitario', 10)->nullable();
                $table->string('v_drenagem_superficial', 10)->nullable();
                $table->string('v_sistema_viario_acesso', 255)->nullable();
                $table->string('v_tipo_revestimento', 50)->nullable();
                $table->string('v_condicoes_acesso', 500)->nullable();
                $table->unsignedSmallInteger('v_numero_moradias_terreno')->nullable();
                $table->string('v_distancia_encosta', 50)->nullable();

                // Aspectos Técnicos
                $table->string('v_material_construtivo', 255)->nullable();
                $table->string('v_conservacao_estrutural', 1000)->nullable();
                $table->string('v_elementos_estruturais', 1000)->nullable();
                $table->string('v_elementos_construtivos', 1000)->nullable();
                $table->string('v_agentes_potencializadores', 1000)->nullable();
                $table->string('v_processos_geodinamicos', 1000)->nullable();
                $table->string('v_danos_estruturais', 20)->nullable();
                $table->string('v_manutencao_uso_ocupacao', 20)->nullable();
                $table->text('v_historico')->nullable();
                $table->string('v_tipo_destinacao', 20)->nullable();
                $table->string('v_tipo_localizacao', 20)->nullable();

                // Encaminhamentos
                $table->boolean('v_enc_interdicao_parcial')->default(false);
                $table->boolean('v_enc_interdicao_total')->default(false);
                $table->boolean('v_enc_remocao_temporaria')->default(false);
                $table->boolean('v_enc_remocao_definitiva')->default(false);
                $table->boolean('v_enc_isolamento_area')->default(false);
                $table->boolean('v_enc_desocupacao_abrigo')->default(false);
                $table->boolean('v_enc_notificacao_responsavel')->default(false);
                $table->boolean('v_enc_contratacao_responsavel')->default(false);
                $table->boolean('v_enc_comunicacao_orgaos')->default(false);
                $table->boolean('v_enc_apoio_social')->default(false);
                $table->boolean('v_enc_outros')->default(false);
                $table->text('v_enc_outros_descricao')->nullable();

                // Patologias
                $table->boolean('v_patologia_rachaduras')->default(false);
                $table->boolean('v_patologia_trincas')->default(false);
                $table->boolean('v_patologia_fissuras_estruturais')->default(false);
                $table->boolean('v_patologia_deformacoes_estruturais')->default(false);
                $table->boolean('v_patologia_infiltracoes')->default(false);
                $table->boolean('v_patologia_corrosao_armaduras')->default(false);
                $table->boolean('v_patologia_desagregacao')->default(false);
                $table->boolean('v_patologia_eflorescencia')->default(false);
                $table->boolean('v_patologia_desplacamento')->default(false);
                $table->boolean('v_patologia_fundacoes')->default(false);
                $table->boolean('v_patologia_instabilidade_talude')->default(false);
                $table->boolean('v_patologia_movimentacao_solo')->default(false);
                $table->boolean('v_patologia_tombamento_muralhas')->default(false);
                $table->boolean('v_patologia_inundacoes')->default(false);
                $table->boolean('v_patologia_alagamentos')->default(false);
                $table->boolean('v_patologia_enxurradas')->default(false);
                $table->boolean('v_patologia_madeira')->default(false);
                $table->boolean('v_patologia_elementos_nao_estruturais')->default(false);
                $table->boolean('v_patologia_falha_drenagem')->default(false);
                $table->boolean('v_patologia_queda_arvores')->default(false);
                $table->boolean('v_patologia_outros')->default(false);
                $table->text('v_patologia_outros_descricao')->nullable();

                // Bens Afetados
                $table->boolean('v_bens_residencia')->default(false);
                $table->boolean('v_bens_muros')->default(false);
                $table->boolean('v_bens_vias_publicas')->default(false);
                $table->boolean('v_bens_pontes')->default(false);
                $table->boolean('v_bens_viadutos')->default(false);
                $table->boolean('v_bens_comercios')->default(false);
                $table->boolean('v_bens_galpoes')->default(false);
                $table->boolean('v_bens_predios_publicos')->default(false);
                $table->boolean('v_bens_edificios_publicos')->default(false);
                $table->boolean('v_bens_outros')->default(false);
                $table->text('v_bens_outros_descricao')->nullable();

                // Órgãos Acionados
                $table->boolean('v_orgao_copasa')->default(false);
                $table->boolean('v_orgao_cemig')->default(false);
                $table->boolean('v_orgao_secretaria_municipal')->default(false);
                $table->boolean('v_orgao_defesa_civil_estadual')->default(false);
                $table->boolean('v_orgao_dnit')->default(false);
                $table->boolean('v_orgao_outros')->default(false);
                $table->text('v_orgao_outros_descricao')->nullable();
                $table->text('v_bens_afetados')->nullable();
                $table->text('v_orgaos_acionados')->nullable();
                $table->text('v_orgaos_acionados_outros')->nullable();
                $table->string('v_orgao_pm', 255)->nullable();
                $table->string('v_orgao_bm', 255)->nullable();
                $table->boolean('v_orgao_crea')->default(false);
                $table->boolean('v_orgao_emater')->default(false);
                $table->boolean('v_orgao_seapa')->default(false);

                $table->timestamps();
                $table->softDeletes();

                $table->index('v_tipo_imovel', 'idx_v_tipo_imovel');
                $table->index('v_municipio', 'idx_v_municipio');

                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $table->engine    = 'InnoDB';
                    $table->charset   = 'utf8mb4';
                    $table->collation = 'utf8mb4_unicode_ci';
                }
            });
        }

        // ------------------------------------------------------------------ //
        // 9. rat_veiculos — Cadastro de veículos
        // ------------------------------------------------------------------ //
        if (! Schema::hasTable('rat_veiculos')) {
            Schema::create('rat_veiculos', function (Blueprint $table) {
                $table->id();

                $table->string('placa', 10)
                      ->unique('rat_veiculos_placa_unique')
                      ->comment('Placa do veículo');

                $table->string('modelo', 100)->comment('Modelo do veículo');
                $table->string('marca', 50)->comment('Marca do veículo');

                $table->boolean('ativo')->default(true)->comment('Veículo ativo');

                $table->timestamps();
                $table->softDeletes();

                $table->index('placa', 'idx_placa');
                $table->index('ativo', 'idx_ativo');
                $table->index('deleted_at', 'idx_deleted_at');

                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $table->engine    = 'InnoDB';
                    $table->charset   = 'utf8mb4';
                    $table->collation = 'utf8mb4_unicode_ci';
                }
            });
        }
    }

    public function down(): void
    {
        // Remover na ordem inversa das dependências de FK
        Schema::dropIfExists('rat_recursos_componentes_guarnicao');
        Schema::dropIfExists('rat_recursos_empregados');
        Schema::dropIfExists('rat_ocorrencia_relatos');
        Schema::dropIfExists('rat_ocorrencias');
        Schema::dropIfExists('rat_relato_recursos');
        Schema::dropIfExists('rat_relato_envolvidos');
        Schema::dropIfExists('rat_relato_vistoria');
        Schema::dropIfExists('rat_veiculos');
        Schema::dropIfExists('rat_redec');
    }
};
