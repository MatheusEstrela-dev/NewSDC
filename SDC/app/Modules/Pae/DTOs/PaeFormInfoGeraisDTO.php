<?php

declare(strict_types=1);

namespace App\Modules\Pae\DTOs;

readonly class PaeFormInfoGeraisDTO
{
    public function __construct(
        public int $userId,
        public ?string $barragem = null,
        public ?string $empreendedorRes = null,
        public ?string $coordenadorPae = null,
        public ?string $email = null,
        public ?string $coordenadorMunDefCiv = null,
        public ?string $coordenadorMunCompdec = null,
        public ?string $metodoConstrutivo = null,
        public ?int $numeroZas = null,
        public ?int $nivelEmergencia = null,
        public ?int $municipioId = null,
        public ?int $paeEmpntoId = null,
        public ?int $paeProtocoloId = null,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            barragem: $data['barragem'] ?? null,
            empreendedorRes: $data['empreendedor_res'] ?? null,
            coordenadorPae: $data['coordenador_pae'] ?? null,
            email: $data['email'] ?? null,
            coordenadorMunDefCiv: $data['coordenador_mun_def_civ'] ?? null,
            coordenadorMunCompdec: $data['coordenador_mun_compdec'] ?? null,
            metodoConstrutivo: $data['metodo_construtivo'] ?? null,
            numeroZas: isset($data['numero_zas']) && is_numeric($data['numero_zas']) ? (int) $data['numero_zas'] : null,
            nivelEmergencia: isset($data['nivel_emergencia']) && is_numeric($data['nivel_emergencia']) ? (int) $data['nivel_emergencia'] : null,
            municipioId: isset($data['municipio_id']) ? (int) $data['municipio_id'] : null,
            paeEmpntoId: isset($data['pae_empnto_id']) ? (int) $data['pae_empnto_id'] : null,
            paeProtocoloId: isset($data['pae_protocolo_id']) ? (int) $data['pae_protocolo_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'barragem_nome'        => $this->barragem,
            'emp_responsavel_nome' => $this->empreendedorRes,
            'coord_pae_nome'       => $this->coordenadorPae,
            'coord_pae_email'      => $this->email,
            'coord_mun_def_civ'    => $this->coordenadorMunDefCiv,
            'coord_mun_compdec'    => $this->coordenadorMunCompdec,
            'metodo_construtivo'   => $this->metodoConstrutivo,
            'num_zas'              => $this->numeroZas,
            'nivel_emergencia'     => $this->nivelEmergencia,
            'municipio_id'         => $this->municipioId,
            'pae_empnto_id'        => $this->paeEmpntoId,
            'pae_protocolo_id'     => $this->paeProtocoloId,
            'updated_by'           => $this->userId,
        ];
    }
}
