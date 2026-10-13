<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use App\Modules\Shared\DTOs\Traits\LeituraComPresenca;
use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * DTO de entrada para criar/atualizar os dados gerais de uma ocorrência RAT
 * (tabela rat_relato_dados_gerais).
 *
 * Mapeia os campos validados por RatDadosGeraisRequest, garantindo tipagem
 * estrita entre a camada HTTP e RatRelatoService / RatOcorrenciaService.
 */
readonly class RatDadosGeraisDTO
{
    use LeituraComPresenca;
    use SafeCastingTrait;

    public function __construct(
        // Datas e Horários
        public ?string $dataFato               = null,
        public ?string $dataInicioAtividade    = null,
        public ?string $dataTerminoAtividade   = null,

        // Natureza e Códigos
        public ?string $natCodigo              = null,
        public ?int    $natCobradeId           = null,
        public ?string $natOcorrencia          = null,
        public ?string $natNomeOperacao        = null,

        // Unidade Responsável e BO
        public ?string $uniResponsavelMunicipio = null,
        public ?string $uniResponsavelCodigo    = null,
        public ?string $uniResponsavelUnidade   = null,
        public ?string $uniBoCodUnidade         = null,
        public ?int    $uniBoAno                = null,
        public ?string $uniBoSequencial         = null,

        // Comunicação
        public ?string $comOcorrenciaData       = null,
        public ?string $comOcorrenciaAtendimento = null,

        // Localização e Endereço
        public ?string $localPais               = null,
        public ?string $localEstadoUf           = null,
        public ?string $localMunicipio          = null,
        public ?string $localCep                = null,
        public ?string $localLogradouro         = null,
        public ?string $localBairro             = null,
        public ?string $localComplemento        = null,
        public ?string $localNumero             = null,
        public ?string $localKm                 = null,
        public ?string $localCruzamento         = null,
        public ?string $localPontoReferencia    = null,
        public ?float  $localLatitude           = null,
        public ?float  $localLongitude          = null,
        public ?string $localOcorrenciaTipo     = null,
        public ?string $localOcorrenciaEstradas = null,
        public ?string $localUnidadeMilitar     = null,
        public ?bool   $temVistoria             = null,
        public ?string $descricao               = null,
        public ?string $comTelefoneContato      = null,
        public ?string $comNomeSolicitante      = null,
        public ?string $localMunicipioNome      = null,
        /** @var array<int, string> colunas enviadas no payload */
        public array   $presentes               = [],
    ) {}

    /**
     * Cria o DTO a partir do payload do formulario (secoes dadosGerais,
     * comunicacao, local, endereco) ou de FormRequest::validated() (raiz).
     *
     * Cada coluna registra se veio no payload: so as presentes vao ao
     * toArray(). Antes, tem_vistoria ausente virava false e era gravado, e
     * nenhum campo de texto podia ser limpo.
     */
    public static function fromArray(array $data): self
    {
        // Se os dados vierem aninhados (comunicacao, local, endereco), faz o merge para o nível raiz
        // para facilitar o mapeamento no DTO.
        $merged = array_merge(
            $data['dadosGerais'] ?? [],
            $data['comunicacao'] ?? [],
            $data['local'] ?? [],
            $data['endereco'] ?? [],
            $data // Mantém os campos que já estão na raiz
        );

        $p = [];
        // Closure (nao arrow fn): precisa acumular em $p por referencia.
        $txt = function (string $coluna, string ...$aliases) use ($merged, &$p): ?string {
            return self::asStringOrNull(self::ler($merged, $p, $coluna, ...$aliases));
        };

        return new self(
            dataFato:                 $txt('data_fato', 'data_ocorrencia', 'data'),
            dataInicioAtividade:      $txt('data_inicio_atividade'),
            dataTerminoAtividade:     $txt('data_termino_atividade'),
            natCodigo:                $txt('nat_codigo', 'natureza'),
            natCobradeId:             self::asIntOrNull(self::ler($merged, $p, 'nat_cobrade_id')),
            natOcorrencia:            $txt('nat_ocorrencia'),
            natNomeOperacao:          $txt('nat_nome_operacao'),
            uniResponsavelMunicipio:  $txt('uni_responsavel_municipio'),
            uniResponsavelCodigo:     $txt('uni_responsavel_codigo'),
            uniResponsavelUnidade:    $txt('uni_responsavel_unidade'),
            uniBoCodUnidade:          $txt('uni_bo_cod_unidade'),
            uniBoAno:                 self::asIntOrNull(self::ler($merged, $p, 'uni_bo_ano')),
            uniBoSequencial:          $txt('uni_bo_sequencial'),
            comOcorrenciaData:        $txt('com_ocorrencia_data', 'data_comunicacao', 'data'),
            comOcorrenciaAtendimento: $txt('com_ocorrencia_atendimento', 'tipo_solicitacao', 'atendimento'),
            localPais:                $txt('local_pais', 'pais_id', 'pais'),
            localEstadoUf:            $txt('local_estadouf', 'local_estado_uf', 'uf'),
            localMunicipio:           $txt('local_municipio', 'municipio_id', 'cidade', 'municipio'),
            localCep:                 $txt('local_cep', 'cep'),
            localLogradouro:          $txt('local_logradoura_1', 'local_logradouro', 'logradouro'),
            localBairro:              $txt('local_bairro', 'bairro'),
            localComplemento:         $txt('local_complemento', 'complemento'),
            localNumero:              $txt('local_numero', 'numero'),
            localKm:                  $txt('local_km', 'km'),
            localCruzamento:          $txt('local_cruzamento', 'cruzamento'),
            localPontoReferencia:     $txt('local_ponto_referencia', 'ponto_referencia'),
            localLatitude:            self::asFloatOrNull(self::ler($merged, $p, 'local_latitude', 'latitude')),
            localLongitude:           self::asFloatOrNull(self::ler($merged, $p, 'local_longitude', 'longitude')),
            localOcorrenciaTipo:      $txt('local_ocorrencia_tipo', 'tipo_localizacao', 'tipo'),
            localOcorrenciaEstradas:  $txt('local_ocorrencia_estradas_rodovias', 'estradas_rodovias'),
            localUnidadeMilitar:      $txt('local_unidade_militar', 'unidade_militar'),
            temVistoria:              self::asBoolOrNull(self::ler($merged, $p, 'tem_vistoria')),
            descricao:                $txt('descricao'),
            comTelefoneContato:       $txt('com_telefone_contato', 'telefone_contato'),
            comNomeSolicitante:       $txt('com_nome_solicitante', 'nome_solicitante'),
            localMunicipioNome:       $txt('local_municipio_nome', 'municipio_nome'),
            presentes:                $p,
        );
    }

    /**
     * Converte para array com as chaves esperadas pelo Eloquent/BD, somente
     * com as colunas enviadas: ausente preserva, null/'' limpa, false grava.
     */
    public function toArray(): array
    {
        return self::somentePresentes([
            'data_fato'                   => $this->dataFato,
            'data_inicio_atividade'       => $this->dataInicioAtividade,
            'data_termino_atividade'      => $this->dataTerminoAtividade,
            'nat_codigo'                  => $this->natCodigo,
            'nat_cobrade_id'              => $this->natCobradeId,
            'nat_ocorrencia'              => $this->natOcorrencia,
            'nat_nome_operacao'           => $this->natNomeOperacao,
            'uni_responsavel_municipio'   => $this->uniResponsavelMunicipio,
            'uni_responsavel_codigo'      => $this->uniResponsavelCodigo,
            'uni_responsavel_unidade'     => $this->uniResponsavelUnidade,
            'uni_bo_cod_unidade'          => $this->uniBoCodUnidade,
            'uni_bo_ano'                  => $this->uniBoAno,
            'uni_bo_sequencial'           => $this->uniBoSequencial,
            'com_ocorrencia_data'         => $this->comOcorrenciaData,
            'com_ocorrencia_atendimento'  => $this->comOcorrenciaAtendimento,
            'local_pais'                  => $this->localPais,
            'local_estadouf'              => $this->localEstadoUf,
            'local_municipio'             => $this->localMunicipio,
            'local_cep'                   => $this->localCep,
            'local_logradoura_1'          => $this->localLogradouro,
            'local_bairro'                => $this->localBairro,
            'local_complemento'           => $this->localComplemento,
            'local_numero'                => $this->localNumero,
            'local_km'                    => $this->localKm,
            'local_cruzamento'            => $this->localCruzamento,
            'local_ponto_referencia'      => $this->localPontoReferencia,
            'local_latitude'              => $this->localLatitude,
            'local_longitude'             => $this->localLongitude,
            'local_ocorrencia_tipo'       => $this->localOcorrenciaTipo,
            'local_ocorrencia_estradas_rodovias' => $this->localOcorrenciaEstradas,
            'local_unidade_militar'       => $this->localUnidadeMilitar,
            'tem_vistoria'                => $this->temVistoria,
            'descricao'                   => $this->descricao,
            'com_telefone_contato'        => $this->comTelefoneContato,
            'com_nome_solicitante'        => $this->comNomeSolicitante,
            'local_municipio_nome'        => $this->localMunicipioNome,
        ], $this->presentes);
    }
}
