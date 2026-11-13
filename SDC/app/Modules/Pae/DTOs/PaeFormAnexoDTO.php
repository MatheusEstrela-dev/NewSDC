<?php

declare(strict_types=1);

namespace App\Modules\Pae\DTOs;

use App\Modules\Pae\Enums\CategoriaAnexo;
use Illuminate\Http\UploadedFile;

final readonly class PaeFormAnexoDTO
{
    public function __construct(
        public UploadedFile $arquivo,
        public ?string $descricao = null,
        public CategoriaAnexo $categoria = CategoriaAnexo::Outros,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            arquivo: $data['arquivo'],
            descricao: $data['descricao'] ?? null,
            categoria: CategoriaAnexo::tryFrom((string) ($data['categoria'] ?? '')) ?? CategoriaAnexo::Outros,
        );
    }
}
