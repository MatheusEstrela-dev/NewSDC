<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use App\Modules\Shared\DTOs\Traits\LeituraComPresenca;
use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * DTO para dados de envolvidos em uma ocorrência RAT.
 */
readonly class RatEnvolvidoDTO
{
    use LeituraComPresenca;
    use SafeCastingTrait;

    public function __construct(
        // UUID do registro (para updates); tabelas RAT usam uuid.
        public ?string $id = null,
        public ?int $seq = null,

        // Dados Gerais (g_)
        public ?string $gTipoPessoa = null,
        public ?string $gLesaoGrau = null,
        public ?string $gLesaoGrauSelecionado = null,
        public ?string $gAtendimentoVitimaRepassada = null,
        public ?bool   $gEnvolvidoPresenca = null,
        public ?string $gEnvolvidoTipo = null,
        public ?string $gEnvolvidoOrgao = null,
        public ?string $gEnvolvidoUf = null,
        public ?string $gEnvolvidoMatricula = null,
        public ?bool   $gEnvolvidoServico = null,

        // Dados Pessoais (p_)
        public ?string $pTipo = null,
        public ?string $pTipoSelecionado = null,
        public ?string $pNumero = null,
        public ?string $pOrgaoExpedidor = null,
        public ?string $pUf = null,
        public ?int    $pIdadeAparente = null,
        public ?string $pNomeCompleto = null,
        public ?string $pNomeFantasia = null,
        public ?string $pDataNascimento = null,
        public ?string $pCpf = null,
        public ?string $pNomeMae = null,
        public ?string $pNomePai = null,
        public ?string $pOcupacaoAtual = null,
        public ?string $pEscolaridade = null,
        public ?string $pCorRaca = null,
        public ?string $pCorRacaCod = null,
        public ?string $pSexo = null,
        public ?string $pSexoSelecionado = null,
        public ?string $pEstadoCivil = null,
        public ?string $pOrientacaoSexual = null,
        public ?string $pIdentidadeGenero = null,
        public ?string $pNomeSocial = null,
        public ?string $pNacionalidade = null,
        public ?string $pPaisOrigem = null,
        public ?string $pNaturalidadeUf = null,
        public ?bool   $pTurista = null,
        public ?bool   $pSituacaoRua = null,

        // Endereço (p_end_)
        public ?string $pEndCep = null,
        public ?string $pEndPais = null,
        public ?string $pEndEstadoUf = null,
        public ?string $pEndMunicipio = null,
        public ?string $pEndBairro = null,
        public ?string $pEndLogradouro = null,
        public ?string $pEndNumero = null,
        public ?string $pEndComplemento = null,
        public ?string $pEndKm = null,
        public ?string $pEndIbge = null,

        // Contato (p_)
        public ?string $pTelefoneResidencial = null,
        public ?string $pTelefoneComercial = null,
        public ?string $pEmail = null,
        public ?string $pMotivoAusenciaContato = null,

        /** @var array<int, string> colunas enviadas no payload */
        public array $presentes = [],
    ) {}

    public static function fromArray(array $data): self
    {
        // Colunas presentes no payload: so elas vao ao toArray() (update parcial pela API).
        $p = [];

        return new self(
            id:                            self::asStringOrNull($data['id'] ?? null),
            seq:                           self::asIntOrNull(self::ler($data, $p, 'seq')),
            gTipoPessoa:                   self::asStringOrNull(self::ler($data, $p, 'g_tipo_pessoa', 'tipo_envolvimento')),
            gLesaoGrau:                    self::asStringOrNull(self::ler($data, $p, 'g_lesao_grau')),
            gLesaoGrauSelecionado:         self::asStringOrNull(self::ler($data, $p, 'g_lesao_grau_selecionado')),
            gAtendimentoVitimaRepassada:   self::asStringOrNull(self::ler($data, $p, 'g_atendimento_vitima_repassada')),
            gEnvolvidoPresenca:            self::asBoolOrNull(self::ler($data, $p, 'g_envolvido_presenca')),
            gEnvolvidoTipo:                self::asStringOrNull(self::ler($data, $p, 'g_envolvido_tipo')),
            gEnvolvidoOrgao:               self::asStringOrNull(self::ler($data, $p, 'g_envolvido_orgao')),
            gEnvolvidoUf:                  self::asStringOrNull(self::ler($data, $p, 'g_envolvido_uf')),
            gEnvolvidoMatricula:           self::asStringOrNull(self::ler($data, $p, 'g_envolvido_matricula')),
            gEnvolvidoServico:             self::asBoolOrNull(self::ler($data, $p, 'g_envolvido_servico')),
            pTipo:                         self::asStringOrNull(self::ler($data, $p, 'p_tipo')),
            pTipoSelecionado:              self::asStringOrNull(self::ler($data, $p, 'p_tipo_selecionado')),
            pNumero:                       self::asStringOrNull(self::ler($data, $p, 'p_numero', 'rg')),
            pOrgaoExpedidor:               self::asStringOrNull(self::ler($data, $p, 'p_orgao_expedidor')),
            pUf:                           self::asStringOrNull(self::ler($data, $p, 'p_uf')),
            pIdadeAparente:                self::asIntOrNull(self::ler($data, $p, 'p_idade_aparente')),
            pNomeCompleto:                 self::asStringOrNull(self::ler($data, $p, 'p_nome_completo', 'nome')),
            pNomeFantasia:                 self::asStringOrNull(self::ler($data, $p, 'p_nome_fantasia')),
            pDataNascimento:               self::asStringOrNull(self::ler($data, $p, 'p_data_nascimento', 'data_nascimento')),
            pCpf:                          self::asStringOrNull(self::ler($data, $p, 'p_cpf', 'cpf')),
            pNomeMae:                      self::asStringOrNull(self::ler($data, $p, 'p_nome_mae')),
            pNomePai:                      self::asStringOrNull(self::ler($data, $p, 'p_nome_pai')),
            pOcupacaoAtual:                self::asStringOrNull(self::ler($data, $p, 'p_ocupacao_atual')),
            pEscolaridade:                 self::asStringOrNull(self::ler($data, $p, 'p_escolaridade')),
            pCorRaca:                      self::asStringOrNull(self::ler($data, $p, 'p_cor_raca')),
            pCorRacaCod:                   self::asStringOrNull(self::ler($data, $p, 'p_cor_raca_cod')),
            pSexo:                         self::asStringOrNull(self::ler($data, $p, 'p_sexo', 'sexo')),
            pSexoSelecionado:              self::asStringOrNull(self::ler($data, $p, 'p_sexo_selecionado')),
            pEstadoCivil:                  self::asStringOrNull(self::ler($data, $p, 'p_estado_civil')),
            pOrientacaoSexual:             self::asStringOrNull(self::ler($data, $p, 'p_orientacao_sexual')),
            pIdentidadeGenero:             self::asStringOrNull(self::ler($data, $p, 'p_identidade_genero')),
            pNomeSocial:                   self::asStringOrNull(self::ler($data, $p, 'p_nome_social')),
            pNacionalidade:                self::asStringOrNull(self::ler($data, $p, 'p_nacionalidade')),
            pPaisOrigem:                   self::asStringOrNull(self::ler($data, $p, 'p_pais_origem')),
            pNaturalidadeUf:               self::asStringOrNull(self::ler($data, $p, 'p_naturalidade_uf')),
            pTurista:                      self::asBoolOrNull(self::ler($data, $p, 'p_turista')),
            pSituacaoRua:                  self::asBoolOrNull(self::ler($data, $p, 'p_situacao_rua')),
            pEndCep:                       self::asStringOrNull(self::ler($data, $p, 'p_end_cep', 'cep')),
            pEndPais:                      self::asStringOrNull(self::ler($data, $p, 'p_end_pais')),
            pEndEstadoUf:                  self::asStringOrNull(self::ler($data, $p, 'p_end_estado_uf', 'uf')),
            pEndMunicipio:                 self::asStringOrNull(self::ler($data, $p, 'p_end_municipio', 'municipio')),
            pEndBairro:                    self::asStringOrNull(self::ler($data, $p, 'p_end_bairro', 'bairro')),
            pEndLogradouro:                self::asStringOrNull(self::ler($data, $p, 'p_end_logradouro', 'endereco')),
            pEndNumero:                    self::asStringOrNull(self::ler($data, $p, 'p_end_numero')),
            pEndComplemento:               self::asStringOrNull(self::ler($data, $p, 'p_end_complemento')),
            pEndKm:                        self::asStringOrNull(self::ler($data, $p, 'p_end_km')),
            pEndIbge:                      self::asStringOrNull(self::ler($data, $p, 'p_end_ibge')),
            pTelefoneResidencial:         self::asStringOrNull(self::ler($data, $p, 'p_telefone_residencial', 'telefone')),
            pTelefoneComercial:           self::asStringOrNull(self::ler($data, $p, 'p_telefone_comercial')),
            pEmail:                        self::asStringOrNull(self::ler($data, $p, 'p_email')),
            pMotivoAusenciaContato:       self::asStringOrNull(self::ler($data, $p, 'p_motivo_ausencia_contato')),
            presentes:                     $p,
        );
    }

    public function toArray(): array
    {
        return self::somentePresentes([
            'seq'                            => $this->seq,
            'g_tipo_pessoa'                  => $this->gTipoPessoa,
            'g_lesao_grau'                   => $this->gLesaoGrau,
            'g_lesao_grau_selecionado'       => $this->gLesaoGrauSelecionado,
            'g_atendimento_vitima_repassada' => $this->gAtendimentoVitimaRepassada,
            'g_envolvido_presenca'          => $this->gEnvolvidoPresenca,
            'g_envolvido_tipo'              => $this->gEnvolvidoTipo,
            'g_envolvido_orgao'             => $this->gEnvolvidoOrgao,
            'g_envolvido_uf'                => $this->gEnvolvidoUf    ? mb_substr($this->gEnvolvidoUf, 0, 2)    : null,
            'g_envolvido_matricula'         => $this->gEnvolvidoMatricula,
            'g_envolvido_servico'           => $this->gEnvolvidoServico,
            'p_tipo'                        => $this->pTipo,
            'p_tipo_selecionado'            => $this->pTipoSelecionado,
            'p_numero'                      => $this->pNumero,
            'p_orgao_expedidor'             => $this->pOrgaoExpedidor,
            'p_uf'                          => $this->pUf,
            'p_idade_aparente'              => $this->pIdadeAparente,
            'p_nome_completo'               => $this->pNomeCompleto,
            'p_nome_fantasia'               => $this->pNomeFantasia,
            'p_data_nascimento'             => $this->pDataNascimento,
            'p_cpf'                         => $this->pCpf,
            'p_nome_mae'                    => $this->pNomeMae,
            'p_nome_pai'                    => $this->pNomePai,
            'p_ocupacao_atual'              => $this->pOcupacaoAtual,
            'p_escolaridade'                => $this->pEscolaridade,
            'p_cor_raca'                    => $this->pCorRaca,
            'p_cor_raca_cod'                => $this->pCorRacaCod,
            'p_sexo'                        => $this->pSexo,
            'p_sexo_selecionado'            => $this->pSexoSelecionado,
            'p_estado_civil'                => $this->pEstadoCivil,
            'p_orientacao_sexual'           => $this->pOrientacaoSexual,
            'p_identidade_genero'           => $this->pIdentidadeGenero,
            'p_nome_social'                 => $this->pNomeSocial,
            'p_nacionalidade'               => $this->pNacionalidade,
            'p_pais_origem'                 => $this->pPaisOrigem,
            'p_naturalidade_uf'             => $this->pNaturalidadeUf ? mb_substr($this->pNaturalidadeUf, 0, 2) : null,
            'p_turista'                     => $this->pTurista,
            'p_situacao_rua'                => $this->pSituacaoRua,
            'p_end_cep'                     => $this->pEndCep,
            'p_end_pais'                    => $this->pEndPais,
            'p_end_estado_uf'               => $this->pEndEstadoUf    ? mb_substr($this->pEndEstadoUf, 0, 2)    : null,
            'p_end_municipio'               => $this->pEndMunicipio,
            'p_end_bairro'                  => $this->pEndBairro,
            'p_end_logradouro'              => $this->pEndLogradouro,
            'p_end_numero'                  => $this->pEndNumero,
            'p_end_complemento'             => $this->pEndComplemento,
            'p_end_km'                      => $this->pEndKm,
            'p_end_ibge'                    => $this->pEndIbge,
            'p_telefone_residencial'        => $this->pTelefoneResidencial,
            'p_telefone_comercial'          => $this->pTelefoneComercial,
            'p_email'                       => $this->pEmail,
            'p_motivo_ausencia_contato'     => $this->pMotivoAusenciaContato,
        ], $this->presentes);
    }
}
