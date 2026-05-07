<?php

declare(strict_types=1);

namespace App\Modules\Compdec\DTOs;

class EquipeDTO
{
    public function __construct(
        public string $nome,
        public string $funcao,
        public ?string $telefone = null,
        public ?string $celular = null,
        public ?string $email = null,
        public ?string $cpf = null,
        public bool $ativo = true,
        public int $ordem = 0,
        public ?string $observacoes = null,
        public ?int $legacyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            nome: (string) ($data['nome'] ?? ''),
            funcao: (string) ($data['funcao'] ?? 'agente'),
            telefone: $data['telefone'] ?? null,
            celular: $data['celular'] ?? null,
            email: $data['email'] ?? null,
            cpf: $data['cpf'] ?? null,
            ativo: array_key_exists('ativo', $data) ? (bool) $data['ativo'] : true,
            ordem: (int) ($data['ordem'] ?? 0),
            observacoes: $data['observacoes'] ?? null,
            legacyId: isset($data['legacy_id']) ? (int) $data['legacy_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'funcao' => $this->funcao,
            'telefone' => $this->telefone,
            'celular' => $this->celular,
            'email' => $this->email,
            'cpf' => $this->cpf,
            'ativo' => $this->ativo,
            'ordem' => $this->ordem,
            'observacoes' => $this->observacoes,
            'legacy_id' => $this->legacyId,
        ];
    }
}
