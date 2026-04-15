<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO para dados de Vistoria em uma ocorrência RAT.
 */
readonly class RatVistoriaDTO
{
    public function __construct(
        public ?int    $id = null,
        // Solicitante
        public ?string $vSolicitanteNome = null,
        public ?string $vSolicitanteCpf = null,
        public ?string $vSolicitanteTelefone = null,
        public ?string $vSolicitanteEndereco = null,
        public ?string $vSolicitanteBairro = null,
        public ?string $vSolicitanteCep = null,
        // Local e imóvel
        public ?string $vLocalEndereco = null,
        public ?string $vLocalComplemento = null,
        public ?string $vTipoArea = null,
        public ?string $vTipoAreaDescricao = null,
        public ?string $vTipoImovel = null,
        public ?string $vTipoImovelOutroDescricao = null,
        public ?string $vTipoConstrucao = null,
        public ?string $vTipoEdificacao = null,
        public ?string $vTipoTerrenoRelevo = null,
        public ?string $vSistemaEstrutural = null,
        public ?int    $vNumeroPavimentos = null,
        public ?string $vEstadoConservacao = null,
        public ?string $vRegimeOcupacao = null,
        // Moradores
        public ?string $vProprietarioMorador = null,
        public ?string $vContatoTelefone = null,
        public ?int    $vNumeroMoradores = null,
        public ?bool   $vHaIdosos = null,
        public ?bool   $vHaCriancas = null,
        public ?string $vHaDificuldadeLocomocao = null,
        // Localização
        public ?string $vEnderecoImovel = null,
        public ?string $vBairro = null,
        public ?string $vMunicipio = null,
        public ?string $vCep = null,
        public ?float  $vLatitude = null,
        public ?float  $vLongitude = null,
        // Technical & Other fields (omitted for brevity in constructor if too many, but we should include important ones)
        public ?array  $extraFields = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:                            $data['id']                             ?? null,
            vSolicitanteNome:              $data['v_solicitante_nome']             ?? null,
            vSolicitanteCpf:               $data['v_solicitante_cpf']              ?? null,
            vSolicitanteTelefone:          $data['v_solicitante_telefone']         ?? null,
            vSolicitanteEndereco:          $data['v_solicitante_endereco']         ?? null,
            vSolicitanteBairro:            $data['v_solicitante_bairro']           ?? null,
            vSolicitanteCep:               $data['v_solicitante_cep']              ?? null,
            vLocalEndereco:                $data['v_local_endereco']               ?? null,
            vLocalComplemento:             $data['v_local_complemento']            ?? null,
            vTipoArea:                     $data['v_tipo_area']                    ?? null,
            vTipoAreaDescricao:            $data['v_tipo_area_descricao']          ?? null,
            vTipoImovel:                   $data['v_tipo_imovel']                  ?? null,
            vTipoImovelOutroDescricao:     $data['v_tipo_imovel_outro_descricao']  ?? null,
            vTipoConstrucao:               $data['v_tipo_construcao']              ?? null,
            vTipoEdificacao:               $data['v_tipo_edificacao']              ?? null,
            vTipoTerrenoRelevo:            $data['v_tipo_terreno_relevo']          ?? null,
            vSistemaEstrutural:            $data['v_sistema_estrutural']           ?? null,
            vNumeroPavimentos:             isset($data['v_numero_pavimentos'])     ? (int) $data['v_numero_pavimentos'] : null,
            vEstadoConservacao:            $data['v_estado_conservacao']           ?? null,
            vRegimeOcupacao:               $data['v_regime_ocupacao']              ?? null,
            vProprietarioMorador:          $data['v_proprietario_morador']         ?? null,
            vContatoTelefone:              $data['v_contato_telefone']             ?? null,
            vNumeroMoradores:              isset($data['v_numero_moradores'])      ? (int) $data['v_numero_moradores'] : null,
            vHaIdosos:                     isset($data['v_ha_idosos'])             ? (bool) $data['v_ha_idosos'] : null,
            vHaCriancas:                   isset($data['v_ha_criancas'])           ? (bool) $data['v_ha_criancas'] : null,
            vHaDificuldadeLocomocao:       $data['v_ha_dificuldade_locomocao']     ?? null,
            vEnderecoImovel:               $data['v_endereco_imovel']              ?? null,
            vBairro:                       $data['v_bairro']                       ?? null,
            vMunicipio:                    $data['v_municipio']                    ?? null,
            vCep:                          $data['v_cep']                          ?? null,
            vLatitude:                    isset($data['v_latitude'])              ? (float) $data['v_latitude'] : null,
            vLongitude:                   isset($data['v_longitude'])             ? (float) $data['v_longitude'] : null,
            extraFields:                  $data, // Keep everything else for now
        );
    }

    public function toArray(): array
    {
        // Mercing specific fields with everything else in extraFields
        $base = [
            'v_solicitante_nome'        => $this->vSolicitanteNome,
            'v_solicitante_cpf'         => $this->vSolicitanteCpf,
            'v_solicitante_telefone'    => $this->vSolicitanteTelefone,
            'v_solicitante_endereco'    => $this->vSolicitanteEndereco,
            'v_solicitante_bairro'      => $this->vSolicitanteBairro,
            'v_solicitante_cep'         => $this->vSolicitanteCep,
            'v_local_endereco'          => $this->vLocalEndereco,
            'v_local_complemento'       => $this->vLocalComplemento,
            'v_tipo_area'               => $this->vTipoArea,
            'v_tipo_area_descricao'     => $this->vTipoAreaDescricao,
            'v_tipo_imovel'             => $this->vTipoImovel,
            'v_tipo_imovel_outro_descricao' => $this->vTipoImovelOutroDescricao,
            'v_tipo_construcao'         => $this->vTipoConstrucao,
            'v_tipo_edificacao'         => $this->vTipoEdificacao,
            'v_tipo_terreno_relevo'     => $this->vTipoTerrenoRelevo,
            'v_sistema_estrutural'      => $this->vSistemaEstrutural,
            'v_numero_pavimentos'       => $this->vNumeroPavimentos,
            'v_estado_conservacao'      => $this->vEstadoConservacao,
            'v_regime_ocupacao'         => $this->vRegimeOcupacao,
            'v_proprietario_morador'    => $this->vProprietarioMorador,
            'v_contato_telefone'        => $this->vContatoTelefone,
            'v_numero_moradores'        => $this->vNumeroMoradores,
            'v_ha_idosos'               => $this->vHaIdosos,
            'v_ha_criancas'             => $this->vHaCriancas,
            'v_ha_dificuldade_locomocao' => $this->vHaDificuldadeLocomocao,
            'v_endereco_imovel'         => $this->vEnderecoImovel,
            'v_bairro'                  => $this->vBairro,
            'v_municipio'               => $this->vMunicipio,
            'v_cep'                     => $this->vCep,
            'v_latitude'                => $this->vLatitude,
            'v_longitude'               => $this->vLongitude,
        ];

        return array_filter(array_merge($this->extraFields, $base), fn ($v) => $v !== null);
    }
}
