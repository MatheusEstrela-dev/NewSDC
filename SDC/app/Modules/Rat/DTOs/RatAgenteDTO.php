<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO para dados de agentes/servidores na guarnição de um recurso RAT.
 */
readonly class RatAgenteDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $nomeCompleto = null,
        public ?string $matricula = null,
        public ?string $masp = null,
        public ?string $pgCargo = null,
        public ?string $orgao = null,
        public ?string $unidade = null,
        public ?string $funcao = null,
        public ?bool   $isCondutor = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:           $data['id']            ?? null,
            nomeCompleto: $data['nome_completo'] ?? $data['nome']  ?? null,
            matricula:    $data['matricula']     ?? null,
            masp:         $data['masp']          ?? null,
            pgCargo:      $data['pg_cargo']      ?? $data['cargo'] ?? null,
            orgao:        $data['orgao']         ?? null,
            unidade:      $data['unidade']       ?? null,
            funcao:       $data['funcao']        ?? null,
            isCondutor:   isset($data['is_condutor']) ? (bool) $data['is_condutor'] : (isset($data['condutor']) ? (bool) $data['condutor'] : null),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'nome_completo' => $this->nomeCompleto,
            'matricula'     => $this->matricula,
            'masp'          => $this->masp,
            'pg_cargo'      => $this->pgCargo,
            'orgao'         => $this->orgao,
            'unidade'       => $this->unidade,
            'funcao'        => $this->funcao,
            'is_condutor'   => $this->isCondutor,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
