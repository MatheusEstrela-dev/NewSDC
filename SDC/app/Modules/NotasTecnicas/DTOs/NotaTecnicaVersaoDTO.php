<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\DTOs;

use Illuminate\Http\UploadedFile;

/** Um PDF a gravar como versao de uma nota. */
final readonly class NotaTecnicaVersaoDTO
{
    public function __construct(
        public UploadedFile $arquivo,
        public string $numero,
        public ?string $changelog,
    ) {}

    /** @param  array<string, mixed>  $dados  validated() do Request */
    public static function fromRequest(array $dados): self
    {
        $changelog = trim((string) ($dados['changelog'] ?? ''));

        return new self(
            arquivo: $dados['arquivo'],
            numero: trim((string) $dados['numero']),
            changelog: $changelog === '' ? null : $changelog,
        );
    }
}
