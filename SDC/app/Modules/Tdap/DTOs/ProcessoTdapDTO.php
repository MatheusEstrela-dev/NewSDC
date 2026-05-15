<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

final readonly class ProcessoTdapDTO
{
    public function __construct(
        public string $numero,
        public int $municipio_id,
        public ?array $contexto,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $contexto = $data['contexto'] ?? null;
        if (is_string($contexto)) {
            $contexto = json_decode($contexto, true) ?: null;
        }

        return new self(
            numero:       mb_strtoupper(trim((string) ($data['numero'] ?? ''))),
            municipio_id: (int) ($data['municipio_id'] ?? 0),
            contexto:     is_array($contexto) ? $contexto : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'numero'       => $this->numero,
            'municipio_id' => $this->municipio_id,
            'contexto'     => $this->contexto,
        ];
    }
}
