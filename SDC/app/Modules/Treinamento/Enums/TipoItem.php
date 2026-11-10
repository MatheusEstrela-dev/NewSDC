<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum TipoItem: string
{
    case PDF = 'PDF';
    case PROJETO = 'PROJETO';
    case VIDEO = 'VIDEO';

    public function getLabel(): string
    {
        return match ($this) {
            self::PDF => 'Material em PDF',
            self::PROJETO => 'Projeto',
            self::VIDEO => 'Vídeo',
        };
    }

    /** Itens cujo conteudo e um arquivo enviado junto com o cadastro. */
    public function exigeArquivo(): bool
    {
        return $this === self::PDF;
    }
}
