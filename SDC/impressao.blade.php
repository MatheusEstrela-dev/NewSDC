@extends('layouts.pagina_master')

@section('title', 'Visualizar Boletim de Ocorrência')

@section('content')
@php
    $dadosGerais = $ocorrencia->relatos->first(function($relato) {
        return $relato->conteudo_type === 'App\\Models\\Rat\\Relatos\\RatRelatoDadosGerais';
    });
    $dadosGeraisConteudo = $dadosGerais ? $dadosGerais->conteudo : null;

    $envolvidos = $ocorrencia->relatos->filter(function($relato) {
        return $relato->conteudo_type === 'App\\Models\\Rat\\Relatos\\RatRelatoEnvolvidos';
    });

    $recursos = $ocorrencia->relatos->filter(function($relato) {
        return $relato->conteudo_type === 'App\\Models\\Rat\\Relatos\\RatRelatoRecurso';
    });

    $vistoria = $ocorrencia->relatos->first(function($relato) {
        return $relato->conteudo_type === 'App\\Models\\Rat\\Relatos\\RatRelatoVistoria';
    });
    $vistoriaConteudo = $vistoria ? $vistoria->conteudo : null;

    $agentes = collect();
    foreach($recursos as $recurso) {
        if($recurso->conteudo && $recurso->conteudo->componentesGuarnicao) {
            $agentes = $agentes->merge($recurso->conteudo->componentesGuarnicao);
        }
    }
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- ========================================================================== --}}
                {{-- CABEÇALHO DO BOLETIM --}}
                {{-- ========================================================================== --}}
                <div class="card-header bg-primary text-white p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div style="flex: 0 0 80px;">
                            <img src="{{ asset('imagem/DEFESACIVILMG_400.png') }}" alt="Defesa Civil MG" class="brasao-logo">
                        </div>
                        <div class="flex-grow-1 text-center px-3">
                            <h5 class="mb-2 fw-bold" style="font-size: 1.1rem;">SISTEMA INTEGRADO DE DEFESA CIVIL</h5>
                            <h4 class="mb-0 fw-bold" style="font-size: 1.3rem;">BOLETIM DE OCORRÊNCIA SIMPLIFICADO</h4>
                        </div>
                        <div class="text-end" style="flex: 0 0 180px;">
                            <div class="badge bg-light text-dark px-2 py-1" style="font-size: 0.75rem; display: inline-block;">
                                <strong>BOS:</strong> {{ $ocorrencia->numero_bos ?? 'N/A' }}
                        </div>
                    </div>
                    </div>
                </div>

                <div class="card-body p-0">

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: DADOS GERAIS --}}
                    {{-- ========================================================================== --}}
                    <div class="section-title-bar">DADOS GERAIS</div>

                            <table class="table-bordered w-100 bos-table">
                                <tr>
                            <td class="field-label" width="18%">DATA/HORA DO FATO</td>
                            <td class="field-value" width="32%">
                                @isset($dadosGeraisConteudo->data_fato)
                                    {{ \Carbon\Carbon::parse($dadosGeraisConteudo->data_fato)->format('d/m/Y H:i') }}
                                @endisset
                            </td>
                            <td class="field-label" width="20%">DATA/HORA TÉRMINO ATIVIDADE</td>
                            <td class="field-value" width="30%">
                                @isset($dadosGeraisConteudo->data_termino_atividade)
                                    {{ \Carbon\Carbon::parse($dadosGeraisConteudo->data_termino_atividade)->format('d/m/Y H:i') }}
                                @endisset
                            </td>
                        </tr>
                        <tr>
                            <td class="field-label">CÓDIGO DA OCORRÊNCIA</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->nat_codigo ?? '' }}</td>
                            <td class="field-label">COBRADE</td>
                            <td class="field-value">
                                @isset($dadosGeraisConteudo->cobrade)
                                    {{ $dadosGeraisConteudo->cobrade->codigo }} - {{ $dadosGeraisConteudo->cobrade->descricao }}
                                @else
                                    {{ $dadosGeraisConteudo->nat_cobrade_id ?? '' }}
                                @endisset
                            </td>
                        </tr>
                        <tr>
                            <td class="field-label">DATA/HORA DA COMUNICAÇÃO</td>
                            <td class="field-value">
                                @isset($dadosGeraisConteudo->data_comunicacao)
                                    {{ \Carbon\Carbon::parse($dadosGeraisConteudo->data_comunicacao)->format('d/m/Y H:i') }}
                                @endisset
                            </td>
                            <td class="field-label">COMO FOI SOLICITADO</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->com_ocorrencia_atendimento ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">MUNICÍPIO</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->municipio->nome ?? $dadosGeraisConteudo->local_municipio ?? '' }}</td>
                            <td class="field-label">PAÍS</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_pais ?? 'Brasil' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">ESTADO/UF</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_estadouf ?? 'MG' }}</td>
                            <td class="field-label">CEP</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_cep ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">LOGRADOURO 1</td>
                            <td class="field-value" colspan="3">{{ $dadosGeraisConteudo->local_logradoura_1 ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">BAIRRO</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_bairro ?? '' }}</td>
                            <td class="field-label">COMPLEMENTO</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_complemento ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">NÚMERO</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_numero ?? '' }}</td>
                            <td class="field-label">KM</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_km ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">CRUZAMENTO</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_cruzamento ?? '' }}</td>
                            <td class="field-label">PONTO DE REFERÊNCIA</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_ponto_referencia ?? '' }}</td>
                                </tr>
                                <tr>
                            <td class="field-label">TIPO DE LOCALIZAÇÃO</td>
                            <td class="field-value">{{ $dadosGeraisConteudo->local_ocorrencia_tipo ?? '' }}</td>
                                </tr>
                    </table>

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: HISTÓRICO DA OCORRÊNCIA / ATIVIDADE --}}
                    {{-- ========================================================================== --}}
                    <div class="section-title-bar">HISTÓRICO DA OCORRÊNCIA / ATIVIDADE</div>

                    <table class="table-bordered w-100 bos-table">
                        <tr>
                            <td class="field-value historico-text" style="min-height: 150px; padding: 20px; vertical-align: top;">
                                {{ $ocorrencia->historico ?? 'NÃO DESCRITO' }}
                            </td>
                        </tr>
                    </table>

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: RECURSOS EMPREGADOS --}}
                    {{-- ========================================================================== --}}
                    <div class="section-title-bar">RECURSOS EMPREGADOS</div>

                    @if($recursos->count() > 0)
                        @foreach($recursos as $index => $recursoRelato)
                            @php
                                $recurso = $recursoRelato->conteudo;
                            @endphp

                            @if($recursos->count() > 1)
                                <div class="subsection-title">RECURSO Nº {{ $loop->iteration }}</div>
                            @endif

                            <table class="table-bordered w-100 bos-table">
                                <tr>
                                    <td class="field-label" width="20%">TIPO DE RECURSO</td>
                                    <td class="field-value" width="30%">{{ $recurso->r_tipo_recurso ?? $recurso->recurso_tipo ?? '' }}</td>
                                    <td class="field-label" width="20%">CATEGORIA</td>
                                    <td class="field-value" width="30%">{{ $recurso->r_categoria ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">ÓRGÃO RESPONSÁVEL</td>
                                    <td class="field-value">{{ $recurso->r_orgao_responsavel ?? $recurso->viatura_orgao ?? '' }}</td>
                                    <td class="field-label">IDENTIFICAÇÃO</td>
                                    <td class="field-value">{{ $recurso->r_identificacao ?? $recurso->viatura_placa ?? $recurso->viatura_prefixo ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">DESCRIÇÃO DO RECURSO</td>
                                    <td class="field-value" colspan="3">{{ $recurso->viatura_descricao ?? $recurso->r_descricao ?? $recurso->recurso_descricao ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">DATA/HORA SAÍDA</td>
                                    <td class="field-value">
                                        @if($recurso->r_data_saida)
                                            {{ \Carbon\Carbon::parse($recurso->r_data_saida)->format('d/m/Y') }}
                                            {{ $recurso->r_hora_saida ? \Carbon\Carbon::parse($recurso->r_hora_saida)->format('H:i') : '' }}
                                        @endif
                                    </td>
                                    <td class="field-label">DATA/HORA CHEGADA</td>
                                    <td class="field-value">
                                        @if($recurso->r_data_chegada)
                                            {{ \Carbon\Carbon::parse($recurso->r_data_chegada)->format('d/m/Y') }}
                                            {{ $recurso->r_hora_chegada ? \Carbon\Carbon::parse($recurso->r_hora_chegada)->format('H:i') : '' }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="field-label">KM PERCORRIDO</td>
                                    <td class="field-value">{{ $recurso->r_km_percorrido ?? '' }}</td>
                                    <td class="field-label">QUANTIDADE</td>
                                    <td class="field-value">{{ $recurso->r_quantidade ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">LOCAL DE ORIGEM</td>
                                    <td class="field-value">{{ $recurso->r_origem ?? '' }}</td>
                                    <td class="field-label">LOCAL DE DESTINO</td>
                                    <td class="field-value">{{ $recurso->r_destino ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">CAPACIDADE/POTÊNCIA</td>
                                    <td class="field-value">{{ $recurso->r_capacidade ?? '' }}</td>
                                    <td class="field-label">CONDIÇÃO DO RECURSO</td>
                                    <td class="field-value">{{ $recurso->r_condicao ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">OPERADOR/RESPONSÁVEL</td>
                                    <td class="field-value">{{ $recurso->r_operador_responsavel ?? '' }}</td>
                                    <td class="field-label">CONTATO DE EMERGÊNCIA</td>
                                    <td class="field-value">{{ $recurso->r_contato ?? '' }}</td>
                                </tr>
                                @if($recurso->viatura_tipo)
                                <tr>
                                    <td class="field-label">TIPO VIATURA</td>
                                    <td class="field-value">{{ $recurso->viatura_tipo ?? '' }}</td>
                                    <td class="field-label">PLACA</td>
                                    <td class="field-value">{{ $recurso->viatura_placa ?? '' }}</td>
                                </tr>
                                @endif
                            </table>
                        @endforeach
                    @else
                        <table class="table-bordered w-100 bos-table">
                            <tr>
                                <td class="field-value text-center" style="padding: 20px;">Nenhum recurso empregado cadastrado</td>
                            </tr>
                        </table>
                    @endif

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: SERVIDORES INTEGRANTES --}}
                    {{-- ========================================================================== --}}
                    <div class="section-title-bar">SERVIDORES INTEGRANTES</div>

                    @if($agentes->count() > 0)
                        @foreach($agentes as $index => $agente)
                            @if($index % 2 == 0)
                                <table class="table-bordered w-100 bos-table">
                            @endif

                            @if($index % 2 == 0)
                                <tr>
                                    <td class="field-label" width="20%">MATRÍCULA/MASP</td>
                                    <td class="field-value" width="30%">{{ $agente->masp ?? $agente->matricula ?? '' }}</td>
                            @else
                                    <td class="field-label" width="20%">MATRÍCULA/MASP</td>
                                    <td class="field-value" width="30%">{{ $agente->masp ?? $agente->matricula ?? '' }}</td>
                                </tr>
                            @endif

                            @if($index % 2 == 0)
                                <tr>
                                    <td class="field-label">NOME COMPLETO</td>
                                    <td class="field-value">{{ $agente->nome_completo ?? '' }}</td>
                            @else
                                    <td class="field-label">NOME COMPLETO</td>
                                    <td class="field-value">{{ $agente->nome_completo ?? '' }}</td>
                                </tr>
                            @endif

                            @if($index % 2 == 0)
                                <tr>
                                    <td class="field-label">PG/CARGO</td>
                                    <td class="field-value">{{ $agente->pg_cargo ?? '' }}</td>
                            @else
                                    <td class="field-label">PG/CARGO</td>
                                    <td class="field-value">{{ $agente->pg_cargo ?? '' }}</td>
                                </tr>
                            @endif

                            @if($index % 2 == 0)
                                <tr>
                                    <td class="field-label">CORPORAÇÃO</td>
                                    <td class="field-value">{{ $agente->corporacao ?? '' }}</td>
                            @else
                                    <td class="field-label">CORPORAÇÃO</td>
                                    <td class="field-value">{{ $agente->corporacao ?? '' }}</td>
                                </tr>
                            @endif

                            @if($index % 2 == 0)
                                <tr>
                                    <td class="field-label">ÓRGÃO</td>
                                    <td class="field-value">{{ $agente->orgao ?? '' }}</td>
                            @else
                                    <td class="field-label">ÓRGÃO</td>
                                    <td class="field-value">{{ $agente->orgao ?? '' }}</td>
                                </tr>
                            @endif

                            @if($index % 2 == 0)
                                <tr>
                                    <td class="field-label">UNIDADE</td>
                                    <td class="field-value">{{ $agente->unidade ?? '' }}</td>
                            @else
                                    <td class="field-label">UNIDADE</td>
                                    <td class="field-value">{{ $agente->unidade ?? '' }}</td>
                                </tr>
                            @endif

                            @if($index % 2 == 0)
                                <tr>
                                    <td class="field-label">FUNÇÃO</td>
                                    <td class="field-value">{{ $agente->funcao ?? '' }}</td>
                            @else
                                    <td class="field-label">FUNÇÃO</td>
                                    <td class="field-value">{{ $agente->funcao ?? '' }}</td>
                                </tr>
                            @endif

                            @if($index % 2 == 0)
                                <tr>
                                    <td class="field-label">CONDUTOR</td>
                                    <td class="field-value">{{ $agente->is_condutor ? 'SIM' : 'NÃO' }}</td>
                            @else
                                    <td class="field-label">CONDUTOR</td>
                                    <td class="field-value">{{ $agente->is_condutor ? 'SIM' : 'NÃO' }}</td>
                                </tr>
                                </table>
                            @endif

                            @if($index % 2 == 0 && $index == $agentes->count() - 1)
                                    <td class="field-label" width="20%"></td>
                                    <td class="field-value" width="30%"></td>
                                </tr>
                                </table>
                            @endif
                        @endforeach
                    @else
                        <table class="table-bordered w-100 bos-table">
                            <tr>
                                <td class="field-value text-center" style="padding: 20px;">Nenhum servidor integrante cadastrado</td>
                            </tr>
                        </table>
                    @endif

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: ENVOLVIDOS NA OCORRÊNCIA --}}
                    {{-- ========================================================================== --}}
                    <div class="section-title-bar">ENVOLVIDOS NA OCORRÊNCIA</div>

                    @if($envolvidos->count() > 0)
                        @foreach($envolvidos as $index => $envolvidoRelato)
                            @php
                                $envolvido = $envolvidoRelato->conteudo;
                            @endphp

                            @if($envolvidos->count() > 1)
                                <div class="subsection-title">ENVOLVIDO Nº {{ $loop->iteration }}</div>
                            @endif

                    <table class="table-bordered w-100 bos-table">
                                {{-- Dados Gerais --}}
                                <tr>
                                    <td class="field-label" width="20%">TIPO DE PESSOA</td>
                                    <td class="field-value" width="30%">{{ $envolvido->g_tipo_pessoa ?? '' }}</td>
                                    <td class="field-label" width="20%"></td>
                                    <td class="field-value" width="30%"></td>
                                </tr>

                                {{-- Dados Pessoais --}}
                                <tr>
                                    <td class="field-label" width="20%">NOME COMPLETO / RAZÃO SOCIAL</td>
                                    <td class="field-value" colspan="3">{{ $envolvido->p_nome_completo ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">APELIDO / NOME FANTASIA</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_nome_fantasia ?? '' }}</td>
                                    <td class="field-label" width="20%">NOME SOCIAL</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_nome_social ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">DATA DE NASCIMENTO</td>
                                    <td class="field-value" width="30%">
                                        @if($envolvido->p_data_nascimento)
                                            {{ \Carbon\Carbon::parse($envolvido->p_data_nascimento)->format('d/m/Y') }}
                                        @endif
                                    </td>
                                    <td class="field-label" width="20%">IDADE APARENTE</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_idade_aparente ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">CPF</td>
                                    <td class="field-value" width="30%">
                                        @if(!empty($envolvido->p_cpf))
                                            {{ $envolvido->p_cpf }}
                                        @elseif(!empty($envolvido->cpf))
                                            {{ $envolvido->cpf }}
                                        @endif
                                    </td>
                                    <td class="field-label" width="20%">SEXO</td>
                                    <td class="field-value" width="30%">
                                        @if(!empty($envolvido->p_sexo))
                                            {{ strtoupper($envolvido->p_sexo) }}
                                        @elseif(!empty($envolvido->sexo))
                                            {{ strtoupper($envolvido->sexo) }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">NOME DA MÃE</td>
                                    <td class="field-value" colspan="3">{{ $envolvido->p_nome_mae ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">NOME DO PAI</td>
                                    <td class="field-value" colspan="3">{{ $envolvido->p_nome_pai ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">OCUPAÇÃO ATUAL</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_ocupacao_atual ?? '' }}</td>
                                    <td class="field-label" width="20%">ESCOLARIDADE</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_escolaridade ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">ESTADO CIVIL</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_estado_civil ?? '' }}</td>
                                    <td class="field-label" width="20%">COR/RAÇA</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_cor_raca ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">NACIONALIDADE</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_nacionalidade ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">NATURALIDADE/UF</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_naturalidade_uf ?? '' }}</td>
                                    <td class="field-label" width="20%">PAÍS DE ORIGEM</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_pais_origem ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">ORIENTAÇÃO SEXUAL</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_orientacao_sexual ?? '' }}</td>
                                    <td class="field-label" width="20%">IDENTIDADE DE GÊNERO</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_identidade_genero ?? '' }}</td>
                                </tr>

                                {{-- Situação --}}
                                <tr>
                                    <td class="field-label" width="20%">É TURISTA?</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_turista ? 'SIM' : 'NÃO' }}</td>
                                    <td class="field-label" width="20%">PESSOA EM SITUAÇÃO DE RUA?</td>
                                    <td class="field-value" width="30%">
                                        @if($envolvido->p_situacao_rua === null)
                                            NÃO INFORMADO
                                        @else
                                            {{ $envolvido->p_situacao_rua ? 'SIM' : 'NÃO' }}
                                        @endif
                                    </td>
                                </tr>

                                {{-- Documentação --}}
                                <tr>
                                    <td class="field-label" width="20%">TIPO DE DOCUMENTO</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_tipo ?? '' }}</td>
                                    <td class="field-label" width="20%">NÚMERO DO DOCUMENTO</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_numero ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">ÓRGÃO EXPEDIDOR</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_orgao_expedidor ?? '' }}</td>
                                    <td class="field-label" width="20%">UF</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_uf ?? '' }}</td>
                                </tr>

                                {{-- Contato --}}
                                <tr>
                                    <td class="field-label" width="20%">TELEFONE RESIDENCIAL/CELULAR</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_telefone_residencial ?? '' }}</td>
                                    <td class="field-label" width="20%">TELEFONE COMERCIAL/CELULAR</td>
                                    <td class="field-value" width="30%">{{ $envolvido->p_telefone_comercial ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label" width="20%">E-MAIL</td>
                                    <td class="field-value" colspan="3">{{ $envolvido->p_email ?? '' }}</td>
                        </tr>
                        <tr>
                                    <td class="field-label" width="20%">MOTIVO AUSÊNCIA TELEFONE/E-MAIL</td>
                                    <td class="field-value" colspan="3">{{ $envolvido->p_motivo_ausencia_contato ?? '' }}</td>
                                </tr>

                                {{-- Militar/Policial --}}
                                <tr>
                                    <td class="field-label" width="20%">É MILITAR/POLICIAL/AGENTE?</td>
                                    <td class="field-value" colspan="3">{{ $envolvido->g_envolvido_presenca ? 'SIM' : 'NÃO' }}</td>
                                </tr>
                                @if($envolvido->g_envolvido_presenca)
                                <tr>
                                    <td class="field-label" width="20%">TIPO</td>
                                    <td class="field-value" width="30%">{{ $envolvido->g_envolvido_tipo ?? '' }}</td>
                                    <td class="field-label" width="20%">ÓRGÃO</td>
                                    <td class="field-value" width="30%">{{ $envolvido->g_envolvido_orgao ?? '' }}</td>
                        </tr>
                        <tr>
                                    <td class="field-label" width="20%">UF</td>
                                    <td class="field-value" width="30%">{{ $envolvido->g_envolvido_uf ?? '' }}</td>
                                    <td class="field-label" width="20%">MATRÍCULA/NR</td>
                                    <td class="field-value" width="30%">{{ $envolvido->g_envolvido_matricula ?? '' }}</td>
                        </tr>
                        <tr>
                                    <td class="field-label" width="20%">EM SERVIÇO?</td>
                                    <td class="field-value" colspan="3">{{ $envolvido->g_envolvido_servico ?? '' }}</td>
                                </tr>
                                @endif
                            </table>
                        @endforeach
                    @else
                        <table class="table-bordered w-100 bos-table">
                            <tr>
                                <td class="field-value text-center" style="padding: 20px;">Nenhum envolvido cadastrado</td>
                        </tr>
                    </table>
                    @endif

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: VISTORIA --}}
                    {{-- ========================================================================== --}}
                    @if($vistoriaConteudo)
                    <div class="section-title-bar">VISTORIA TÉCNICA</div>

                    <table class="table-bordered w-100 bos-table">
                        {{-- Dados do Imóvel --}}
                        <tr>
                            <td class="field-label" width="20%">TIPO DE IMÓVEL</td>
                            <td class="field-value" width="30%">{{ strtoupper($vistoriaConteudo->v_tipo_imovel ?? '') }}</td>
                            <td class="field-label" width="20%">PROPRIETÁRIO/MORADOR</td>
                            <td class="field-value" width="30%">{{ $vistoriaConteudo->v_proprietario_morador ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">TELEFONE DE CONTATO</td>
                            <td class="field-value">{{ $vistoriaConteudo->v_contato_telefone ?? '' }}</td>
                            <td class="field-label">Nº DE MORADORES</td>
                            <td class="field-value">{{ $vistoriaConteudo->v_numero_moradores ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">HÁ IDOSOS?</td>
                            <td class="field-value">{{ strtoupper($vistoriaConteudo->v_ha_idosos ?? '') }}</td>
                            <td class="field-label">HÁ CRIANÇAS?</td>
                            <td class="field-value">{{ strtoupper($vistoriaConteudo->v_ha_criancas ?? '') }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">HÁ PESSOAS COM DIFICULDADE DE LOCOMOÇÃO?</td>
                            <td class="field-value" colspan="3">{{ strtoupper($vistoriaConteudo->v_ha_dificuldade_locomocao ?? '') }}</td>
                        </tr>

                        {{-- Localização --}}
                        <tr>
                            <td class="field-label">ENDEREÇO DO IMÓVEL</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_endereco_imovel ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">BAIRRO</td>
                            <td class="field-value">{{ $vistoriaConteudo->v_bairro ?? '' }}</td>
                            <td class="field-label">CEP</td>
                            <td class="field-value">{{ $vistoriaConteudo->v_cep ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">LATITUDE</td>
                            <td class="field-value">{{ $vistoriaConteudo->v_latitude ?? '' }}</td>
                            <td class="field-label">LONGITUDE</td>
                            <td class="field-value">{{ $vistoriaConteudo->v_longitude ?? '' }}</td>
                        </tr>

                        {{-- Infraestrutura --}}
                        <tr>
                            <td class="field-label">ABASTECIMENTO DE ÁGUA</td>
                            <td class="field-value">{{ strtoupper($vistoriaConteudo->v_abastecimento_agua ?? '') }}</td>
                            <td class="field-label">ESGOTAMENTO SANITÁRIO</td>
                            <td class="field-value">{{ strtoupper($vistoriaConteudo->v_esgotamento_sanitario ?? '') }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">DRENAGEM SUPERFICIAL</td>
                            <td class="field-value">{{ strtoupper($vistoriaConteudo->v_drenagem_superficial ?? '') }}</td>
                            <td class="field-label">TIPO DE REVESTIMENTO</td>
                            <td class="field-value">{{ strtoupper($vistoriaConteudo->v_tipo_revestimento ?? '') }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">SISTEMA VIÁRIO DE ACESSO</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_sistema_viario_acesso ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">CONDIÇÕES DE ACESSO</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_condicoes_acesso ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">Nº DE MORADIAS NO TERRENO</td>
                            <td class="field-value">{{ $vistoriaConteudo->v_numero_moradias_terreno ?? '' }}</td>
                            <td class="field-label">DISTÂNCIA DA ENCOSTA</td>
                            <td class="field-value">{{ $vistoriaConteudo->v_distancia_encosta ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">MATERIAL CONSTRUTIVO</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_material_construtivo ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">CONSERVAÇÃO ESTRUTURAL</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_conservacao_estrutural ?? '' }}</td>
                        </tr>

                        {{-- Análise Técnica --}}
                        <tr>
                            <td class="field-label">ELEMENTOS ESTRUTURAIS</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_elementos_estruturais ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">ELEMENTOS CONSTRUTIVOS</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_elementos_construtivos ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">AGENTES POTENCIALIZADORES</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_agentes_potencializadores ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">PROCESSOS GEODINÂMICOS</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_processos_geodinamicos ?? '' }}</td>
                        </tr>

                        {{-- Bens Afetados, Encaminhamentos e Órgãos --}}
                        <tr>
                            <td class="field-label">BENS E INFRAESTRUTURAS AFETADOS</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_bens_afetados ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="field-label">ENCAMINHAMENTOS NECESSÁRIOS</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_encaminhamentos ?? '' }}</td>
                        </tr>
                        @if($vistoriaConteudo->v_encaminhamentos_outros)
                        <tr>
                            <td class="field-label">OUTROS ENCAMINHAMENTOS</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_encaminhamentos_outros }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="field-label">ÓRGÃOS/ENTIDADES ACIONADOS</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_orgaos_acionados ?? '' }}</td>
                        </tr>
                        @if($vistoriaConteudo->v_orgaos_acionados_outros)
                        <tr>
                            <td class="field-label">OUTROS ÓRGÃOS/ENTIDADES</td>
                            <td class="field-value" colspan="3">{{ $vistoriaConteudo->v_orgaos_acionados_outros }}</td>
                        </tr>
                        @endif

                        {{-- Histórico/Observações --}}
                        @if($vistoriaConteudo->v_historico)
                        <tr>
                            <td class="field-label">HISTÓRICO / OBSERVAÇÕES</td>
                            <td class="field-value" colspan="3" style="white-space: pre-wrap;">{{ $vistoriaConteudo->v_historico }}</td>
                        </tr>
                        @endif
                    </table>

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: PATOLOGIAS IDENTIFICADAS --}}
                    {{-- ========================================================================== --}}
                    @php
                        $patologias = [];
                        if($vistoriaConteudo->v_patologia_rachaduras) $patologias[] = 'Rachaduras';
                        if($vistoriaConteudo->v_patologia_trincas) $patologias[] = 'Trincas';
                        if($vistoriaConteudo->v_patologia_fissuras_estruturais) $patologias[] = 'Fissuras em Elementos Estruturais (pilar, viga, laje, etc.)';
                        if($vistoriaConteudo->v_patologia_deformacoes_estruturais) $patologias[] = 'Deformações ou Deslocamentos Estruturais';
                        if($vistoriaConteudo->v_patologia_infiltracoes) $patologias[] = 'Infiltrações / Umidade Ascendente';
                        if($vistoriaConteudo->v_patologia_corrosao_armaduras) $patologias[] = 'Corrosão de Armaduras';
                        if($vistoriaConteudo->v_patologia_desagregacao) $patologias[] = 'Desagregação de Reboco / Argamassa';
                        if($vistoriaConteudo->v_patologia_eflorescencia) $patologias[] = 'Eflorescência / Bolor / Mofo';
                        if($vistoriaConteudo->v_patologia_desplacamento) $patologias[] = 'Desplacamento de Revestimento';
                        if($vistoriaConteudo->v_patologia_fundacoes) $patologias[] = 'Comprometimento das Fundações';
                        if($vistoriaConteudo->v_patologia_instabilidade_talude) $patologias[] = 'Instabilidade de Talude / Contenções';
                        if($vistoriaConteudo->v_patologia_movimentacao_solo) $patologias[] = 'Indícios de Movimentação de Solo';
                        if($vistoriaConteudo->v_patologia_tombamento_muralhas) $patologias[] = 'Risco de Tombamento de Muralhas de Vedação';
                        if($vistoriaConteudo->v_patologia_inundacoes) $patologias[] = 'Inundações';
                        if($vistoriaConteudo->v_patologia_alagamentos) $patologias[] = 'Alagamentos';
                        if($vistoriaConteudo->v_patologia_enxurradas) $patologias[] = 'Enxurradas';
                        if($vistoriaConteudo->v_patologia_madeira) $patologias[] = 'Patologia em Elementos de Madeira (cupins, apodrecimento, etc.)';
                        if($vistoriaConteudo->v_patologia_elementos_nao_estruturais) $patologias[] = 'Comprometimento de Elementos Não Estruturais (janelas, portas, etc.)';
                        if($vistoriaConteudo->v_patologia_falha_drenagem) $patologias[] = 'Falha em Drenagem Pluvial ou Esgoto';
                        if($vistoriaConteudo->v_patologia_queda_arvores) $patologias[] = 'Risco de Queda de Árvores e/ou Troncos e Galhos';
                        if($vistoriaConteudo->v_patologia_outros) {
                            $outrosTexto = 'Outros';
                            if($vistoriaConteudo->v_patologia_outros_descricao) {
                                $outrosTexto .= ' (' . $vistoriaConteudo->v_patologia_outros_descricao . ')';
                            }
                            $patologias[] = $outrosTexto;
                        }
                    @endphp

                    @if(count($patologias) > 0)
                    <div class="subsection-title">TIPIFICAÇÃO DA OCORRÊNCIA / PATOLOGIAS IDENTIFICADAS</div>
                    <table class="table-bordered w-100 bos-table">
                        <tr>
                            <td class="field-value" style="padding: 10px;">
                                <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
                                    @foreach($patologias as $patologia)
                                        <li style="margin-bottom: 5px;">{{ $patologia }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    </table>
                    @endif

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: BENS E INFRAESTRUTURAS AFETADOS --}}
                    {{-- ========================================================================== --}}
                    @php
                        $bensAfetados = [];
                        if($vistoriaConteudo->v_bens_residencia) $bensAfetados[] = 'Residência';
                        if($vistoriaConteudo->v_bens_muros) $bensAfetados[] = 'Muros';
                        if($vistoriaConteudo->v_bens_vias_publicas) $bensAfetados[] = 'Vias Públicas';
                        if($vistoriaConteudo->v_bens_pontes) $bensAfetados[] = 'Pontes';
                        if($vistoriaConteudo->v_bens_viadutos) $bensAfetados[] = 'Viadutos';
                        if($vistoriaConteudo->v_bens_comercios) $bensAfetados[] = 'Comércios';
                        if($vistoriaConteudo->v_bens_galpoes) $bensAfetados[] = 'Galpões';
                        if($vistoriaConteudo->v_bens_predios_publicos) $bensAfetados[] = 'Prédios Públicos';
                        if($vistoriaConteudo->v_bens_edificios_publicos) $bensAfetados[] = 'Edifícios Públicos';
                        if($vistoriaConteudo->v_bens_outros) {
                            $outrosTexto = 'Outros';
                            if($vistoriaConteudo->v_bens_outros_descricao) {
                                $outrosTexto .= ' (' . $vistoriaConteudo->v_bens_outros_descricao . ')';
                            }
                            $bensAfetados[] = $outrosTexto;
                        }
                    @endphp

                    @if(count($bensAfetados) > 0)
                    <div class="subsection-title">BENS E INFRAESTRUTURAS AFETADOS</div>
                    <table class="table-bordered w-100 bos-table">
                        <tr>
                            <td class="field-value" style="padding: 10px;">
                                <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
                                    @foreach($bensAfetados as $bem)
                                        <li style="margin-bottom: 5px;">{{ $bem }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    </table>
                    @endif

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: ANÁLISE DE VULNERABILIDADE / ENCAMINHAMENTOS --}}
                    {{-- ========================================================================== --}}
                    @php
                        $encaminhamentos = [];
                        if($vistoriaConteudo->v_enc_interdicao_parcial) $encaminhamentos[] = 'Interdição Parcial';
                        if($vistoriaConteudo->v_enc_interdicao_total) $encaminhamentos[] = 'Interdição Total';
                        if($vistoriaConteudo->v_enc_remocao_temporaria) $encaminhamentos[] = 'Remoção Temporária';
                        if($vistoriaConteudo->v_enc_remocao_definitiva) $encaminhamentos[] = 'Remoção Definitiva';
                        if($vistoriaConteudo->v_enc_isolamento_area) $encaminhamentos[] = 'Isolamento da Área';
                        if($vistoriaConteudo->v_enc_desocupacao_abrigo) $encaminhamentos[] = 'Desocupação / Abrigo';
                        if($vistoriaConteudo->v_enc_notificacao_responsavel) $encaminhamentos[] = 'Notificação ao Responsável';
                        if($vistoriaConteudo->v_enc_contratacao_responsavel) $encaminhamentos[] = 'Contratação de Responsável Técnico';
                        if($vistoriaConteudo->v_enc_comunicacao_orgaos) $encaminhamentos[] = 'Comunicação a Órgãos Competentes';
                        if($vistoriaConteudo->v_enc_apoio_social) $encaminhamentos[] = 'Apoio Social';
                        if($vistoriaConteudo->v_enc_outros) {
                            $outrosTexto = 'Outros';
                            if($vistoriaConteudo->v_enc_outros_descricao) {
                                $outrosTexto .= ' (' . $vistoriaConteudo->v_enc_outros_descricao . ')';
                            }
                            $encaminhamentos[] = $outrosTexto;
                        }
                    @endphp

                    @if(count($encaminhamentos) > 0)
                    <div class="subsection-title">ANÁLISE DE VULNERABILIDADE / ENCAMINHAMENTOS</div>
                    <table class="table-bordered w-100 bos-table">
                        <tr>
                            <td class="field-value" style="padding: 10px;">
                                <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
                                    @foreach($encaminhamentos as $encaminhamento)
                                        <li style="margin-bottom: 5px;">{{ $encaminhamento }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    </table>
                    @endif

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: ÓRGÃOS / ENTIDADES ACIONADAS --}}
                    {{-- ========================================================================== --}}
                    @php
                        $orgaosAcionados = [];
                        if($vistoriaConteudo->v_orgao_copasa) $orgaosAcionados[] = 'COPASA';
                        if($vistoriaConteudo->v_orgao_cemig) $orgaosAcionados[] = 'CEMIG';
                        if($vistoriaConteudo->v_orgao_secretaria_municipal) $orgaosAcionados[] = 'Secretaria Municipal';
                        if($vistoriaConteudo->v_orgao_defesa_civil_estadual) $orgaosAcionados[] = 'Defesa Civil Estadual';
                        if($vistoriaConteudo->v_orgao_dnit) $orgaosAcionados[] = 'DNIT';
                        if($vistoriaConteudo->v_orgao_pm) $orgaosAcionados[] = 'Polícia Militar';
                        if($vistoriaConteudo->v_orgao_bm) $orgaosAcionados[] = 'Bombeiros Militares';
                        if($vistoriaConteudo->v_orgao_crea) $orgaosAcionados[] = 'CREA';
                        if($vistoriaConteudo->v_orgao_emater) $orgaosAcionados[] = 'EMATER';
                        if($vistoriaConteudo->v_orgao_seapa) $orgaosAcionados[] = 'SEAPA';
                        if($vistoriaConteudo->v_orgao_outros) {
                            $outrosTexto = 'Outros';
                            if($vistoriaConteudo->v_orgao_outros_descricao) {
                                $outrosTexto .= ' (' . $vistoriaConteudo->v_orgao_outros_descricao . ')';
                            }
                            $orgaosAcionados[] = $outrosTexto;
                        }
                    @endphp

                    @if(count($orgaosAcionados) > 0)
                    <div class="subsection-title">ÓRGÃOS / ENTIDADES ACIONADAS</div>
                    <table class="table-bordered w-100 bos-table">
                        <tr>
                            <td class="field-value" style="padding: 10px;">
                                <ul style="margin: 0; padding-left: 20px; list-style-type: disc;">
                                    @foreach($orgaosAcionados as $orgao)
                                        <li style="margin-bottom: 5px;">{{ $orgao }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    </table>
                    @endif
                    @endif

                    {{-- ========================================================================== --}}
                    {{-- SEÇÃO: RECIBO DA AUTORIDADE --}}
                    {{-- ========================================================================== --}}
                    <div class="section-title-bar">RECIBO DA AUTORIDADE A QUE SE DESTINA OU SEU AGENTE / AUXILIAR POLICIAL OU RECIBO DO RESPONSÁVEL CIVIL</div>

                    <div class="subsection-title">DESTINATÁRIO / RECIBO 1</div>

                    <table class="table-bordered w-100 bos-table">
                        <tr>
                            <td class="field-value recibo-text">
                                Recebi o "Boletim de Ocorrência" de Número BO <strong>{{ $ocorrencia->numero_bos ?? 'XXXX' }}</strong> para conhecimento e providências, bem como as pessoas, materiais, objetos, animais, substâncias e/ ou documentos que, existindo, estejam descritos ou assinalados neste documento.
                            </td>
                        </tr>
                    </table>

                    <table class="table-bordered w-100 bos-table">
                        <tr>
                            <td class="field-label" width="15%">DIGITADOR:</td>
                            <td class="field-value" width="35%"></td>
                            <td class="field-label" width="15%">CRIADO POR:</td>
                            <td class="field-value">{{ $ocorrencia->creator->name ?? 'Sistema' }}</td>
                        </tr>
                    </table>

                    <table class="table-bordered w-100 bos-table mb-3">
                        <tr>
                            <td class="field-value text-muted small">
                                Registro sujeito a alterações até o dia {{ $ocorrencia->created_at ? $ocorrencia->created_at->addDays(30)->format('d/m/Y') : now()->addDays(30)->format('d/m/Y') }}
                            </td>
                        </tr>
                    </table>

                    {{-- Campo de Assinatura --}}
                    <table class="table-bordered w-100 bos-table">
                        <tr>
                            <td class="field-label" width="20%">ASSINATURA:</td>
                            <td class="field-value signature-line">___________________________________________________</td>
                        </tr>
                    </table>

                    {{-- ========================================================================== --}}
                    {{-- BOTÕES DE AÇÃO --}}
                    {{-- ========================================================================== --}}
                    <div class="d-flex justify-content-between p-3 no-print">
                        <a href="{{ route('rat.bo.historico') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>
                        <div>
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="bi bi-printer me-2"></i>Imprimir
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    /* Esconder cabeçalho e rodapé da aplicação */
    body { background-color: #fff !important; }
    .container-fluid.gx-0 { padding: 0 !important; margin: 0 !important; }
    #barra, #breadcrumb, #footer, nav, .navbar {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
    }
    #corpo { margin: 0 !important; padding: 0 !important; }
     
     /* Remover qualquer arredondamento global */
     * {
         border-radius: 0 !important;
         -webkit-border-radius: 0 !important;
         -moz-border-radius: 0 !important;
     }

    /* Estilos do Boletim */
    .bos-table {
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 10px;
    }
    .bos-table td, .bos-table th {
        border: 1px solid #000;
        padding: 3px 5px;
        vertical-align: top;
    }
    .field-label {
        background-color: #e8e8e8;
        font-weight: bold;
        font-size: 8px;
        text-transform: uppercase;
        padding: 2px 4px;
        color: #000;
    }
    .field-value {
        background-color: #fff;
        padding: 3px 5px;
        min-height: 18px;
        font-size: 10px;
        color: #000;
    }
    .section-title-bar {
        background-color: #2c3e50;
        color: #fff;
        padding: 6px 10px;
        font-weight: bold;
        font-size: 10px;
        text-align: left;
        margin: 0;
        border: 1px solid #000;
        text-transform: uppercase;
    }
    
    /* Remover espaços entre títulos e tabelas */
    .section-title-bar + table {
        margin-top: 0 !important;
    }
    
    .section-title-bar + * {
        margin-top: 0 !important;
    }
    .subsection-title {
        background-color: #d5d5d5;
        color: #000;
        padding: 4px 8px;
        font-weight: bold;
        font-size: 8px;
        text-align: left;
        margin: 0;
        border: 1px solid #000;
        border-bottom: none;
        text-transform: uppercase;
    }
    .historico-text {
        min-height: 100px;
        padding: 8px;
        text-align: justify;
        white-space: pre-wrap;
        font-size: 9px;
        line-height: 1.3;
    }
    .recibo-text {
        padding: 8px;
        text-align: justify;
        line-height: 1.3;
        font-size: 9px;
    }
    .signature-line {
        text-align: center;
        padding-top: 15px;
    }
    .card-body {
        padding: 0 !important;
    }
     
     /* Container para tabelas lado a lado na seção SERVIDORES INTEGRANTES */
     .servidores-container {
         display: flex;
         gap: 0;
         width: 100%;
     }
     
     .servidores-table-left {
         width: 50%;
         border-right: none;
     }
     
     .servidores-table-right {
         width: 50%;
    }
    .card {
        border: 2px solid #000;
         width: 100%;
         max-width: 100%;
         margin: 0;
         border-radius: 0 !important;
         box-shadow: none !important;
         -webkit-border-radius: 0 !important;
         -moz-border-radius: 0 !important;
    }
    .card-header {
        border-bottom: 2px solid #000;
         background-color: #003d82 !important; /* Azul mais escuro */
         border-radius: 0 !important;
         -webkit-border-radius: 0 !important;
         -moz-border-radius: 0 !important;
     }
     
     .card-body {
         border-radius: 0 !important;
     }
     
     .card-footer {
         border-radius: 0 !important;
     }
     .bg-primary {
         background-color: #003d82 !important;
    }
    .brasao-logo {
         max-width: 70px;
        height: auto;
    }

     /* Container para tela */
     .container-fluid {
         width: 100%;
         max-width: 100%;
         padding: 10px 15px;
         margin: 0;
     }
     
     .col-12 {
         padding: 0;
     }
     
     /* Ajustes de proporção e grid */
     body {
         background-color: #f5f5f5;
     }
     
     .card {
         page-break-inside: avoid;
     }
     
     /* Melhorar proporções das tabelas */
     .bos-table td {
         word-wrap: break-word;
         overflow-wrap: break-word;
     }
     
     /* Ajustar larguras proporcionais */
     .bos-table td[width="5%"] { width: 5% !important; }
     .bos-table td[width="10%"] { width: 10% !important; }
     .bos-table td[width="15%"] { width: 15% !important; }
     .bos-table td[width="20%"] { width: 20% !important; }
     .bos-table td[width="25%"] { width: 25% !important; }
     .bos-table td[width="30%"] { width: 30% !important; }
     .bos-table td[width="35%"] { width: 35% !important; }

    /* Print Styles - Formato A4 */
    @media print {
        .no-print, .btn, button, a.btn {
            display: none !important;
            visibility: hidden !important;
        }
        #barra, #breadcrumb, #footer, nav, .navbar {
            display: none !important;
            visibility: hidden !important;
        }
        
        /* Ajustar para A4 na impressão */
        .container-fluid {
            width: 210mm;
            max-width: 210mm;
            padding: 10mm;
        }
        
        .card {
            border: 1px solid #000;
            box-shadow: none;
            width: 100%;
            max-width: 100%;
        }
        .bos-table {
            page-break-inside: avoid;
        }
        body {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
            background-color: white !important;
        }
        .section-title-bar, .subsection-title, .field-label {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        @page {
            size: A4;
            margin: 10mm;
        }
    }
</style>
@endsection

