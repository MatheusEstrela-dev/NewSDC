<?php

declare(strict_types=1);

namespace App\Modules\Compdec\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

class EquipeDTO
{
    use SafeCastingTrait;

    public function __construct(
        public string $nome,
        public string $funcao,
        public ?string $telefone = null,
        public ?string $celular = null,
        public ?string $email = null,
        public ?string $cpf = null,
        public ?bool $ativo = null,
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
            // Ausente = null: editar membro inativo (inclusive pela ficha do PMDA,
            // que nao envia o campo) nao o reativa. false explicito grava.
            ativo: self::asBoolOrNull($data['ativo'] ?? null),
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
            ...($this->ativo !== null ? ['ativo' => $this->ativo] : []),
            'ordem' => $this->ordem,
            'observacoes' => $this->observacoes,
            ...($this->legacyId !== null ? ['legacy_id' => $this->legacyId] : []),
        ];
    }
}
