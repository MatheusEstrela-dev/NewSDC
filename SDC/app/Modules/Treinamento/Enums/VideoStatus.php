<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum VideoStatus: string
{
    case SEM_VIDEO = 'SEM_VIDEO';
    case UPLOAD_ABERTO = 'UPLOAD_ABERTO';
    case ENVIADO = 'ENVIADO';
    case PROCESSANDO = 'PROCESSANDO';
    case PRONTO = 'PRONTO';
    case FALHOU = 'FALHOU';

    public function getLabel(): string
    {
        return match ($this) {
            self::SEM_VIDEO => 'Sem vídeo',
            self::UPLOAD_ABERTO => 'Enviando',
            self::ENVIADO => 'Enviado, na fila',
            self::PROCESSANDO => 'Processando',
            self::PRONTO => 'Pronto',
            self::FALHOU => 'Falhou',
        };
    }

    public function estaPronto(): bool
    {
        return $this === self::PRONTO;
    }

    public function podeReprocessar(): bool
    {
        return $this === self::FALHOU;
    }

    public function emProcessamento(): bool
    {
        return $this === self::ENVIADO || $this === self::PROCESSANDO;
    }
}
