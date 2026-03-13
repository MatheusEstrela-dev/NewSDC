<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO de atualização de RAT.
 * Todos os campos são opcionais — permite atualizações parciais.
 *
 * Factory method: UpdateRatDTO::from($request->validated())
 */
readonly class UpdateRatDTO
{
    public function __construct(
        public ?array  $dadosGerais = null,
        public ?array  $local       = null,
        public ?array  $endereco    = null,
        public ?array  $comunicacao = null,
        public ?array  $recursos    = null,
        public ?array  $envolvidos  = null,
        public ?array  $vistoria    = null,
        public ?array  $historico   = null,
        public ?string $status      = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            dadosGerais: $data['dadosGerais'] ?? null,
            local:       $data['local']       ?? null,
            endereco:    $data['endereco']     ?? null,
            comunicacao: $data['comunicacao']  ?? null,
            recursos:    $data['recursos']     ?? null,
            envolvidos:  $data['envolvidos']   ?? null,
            vistoria:    $data['vistoria']     ?? null,
            historico:   $data['historico']    ?? null,
            status:      $data['status']       ?? null,
        );
    }

    /** Retorna somente os campos com valor (ignora nulls). */
    public function toArray(): array
    {
        return array_filter([
            'dadosGerais' => $this->dadosGerais,
            'local'       => $this->local,
            'endereco'    => $this->endereco,
            'comunicacao' => $this->comunicacao,
            'recursos'    => $this->recursos,
            'envolvidos'  => $this->envolvidos,
            'vistoria'    => $this->vistoria,
            'historico'   => $this->historico,
            'status'      => $this->status,
        ], fn($v) => !is_null($v));
    }
}
