<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters;

use App\Core\Events\DomainEvent;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\DTOs\FatoNormalizado;

/**
 * A origem oferece conclusao de etapa, mas nao aceite/validacao independente.
 * CRUD de OS, conclusao e leitura de QR code nao comprovam os marcos do catalogo.
 */
final class CisternaAdapter implements ModuleAdapter
{
    public function modulo(): string
    {
        return 'Cisterna';
    }

    public function suporta(DomainEvent $evento): bool
    {
        return $evento->eventName() === 'cisterna.vistoria.concluida';
    }

    public function normalizar(DomainEvent $evento): ?FatoNormalizado
    {
        // Captura duravel para auditoria; nenhum credito ate existir o aceite.
        return null;
    }
}
