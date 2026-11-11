<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

final readonly class SondagemVideo
{
    public function __construct(
        public int $duracaoSegundos,
        public int $largura,
        public int $altura,
        public bool $temAudio = true,
    ) {}
}
