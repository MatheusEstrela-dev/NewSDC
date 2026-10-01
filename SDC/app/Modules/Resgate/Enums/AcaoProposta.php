<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Enums;

/** O que uma proposta faz com o catalogo quando aprovada. */
enum AcaoProposta: string
{
    case Criar = 'criar';
    case NovaVersao = 'nova_versao';
    case Encerrar = 'encerrar';

    public function label(): string
    {
        return match ($this) {
            self::Criar      => 'Novo item',
            self::NovaVersao => 'Nova versão',
            self::Encerrar   => 'Encerrar item',
        };
    }

    /** Criar e nova versao publicam versao; encerrar so fecha a vigente. */
    public function publicaVersao(): bool
    {
        return $this !== self::Encerrar;
    }
}
