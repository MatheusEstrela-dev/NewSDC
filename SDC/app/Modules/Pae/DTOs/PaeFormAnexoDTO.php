<?php

declare(strict_types=1);

namespace App\Modules\Pae\DTOs;

use Illuminate\Http\UploadedFile;

class PaeFormAnexoDTO
{
    public function __construct(
        public UploadedFile $arquivo,
        public ?string $descricao = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            arquivo: $data['arquivo'],
            descricao: $data['descricao'] ?? null,
        );
    }
}
