<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Observers;

use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Support\ContextoImportacao;
use App\Modules\Shared\Events\RecursoAtualizado;

/**
 * Avisa as listagens abertas que algo mudou. O evento nao carrega dado: cada
 * tela recarrega pela propria rota, que ja aplica a visibilidade do usuario.
 */
class DemandaTempoRealObserver
{
    public function __construct(private readonly ContextoImportacao $contexto) {}

    public function saved(Demanda $demanda): void
    {
        $this->avisar();
    }

    public function deleted(Demanda $demanda): void
    {
        $this->avisar();
    }

    private function avisar(): void
    {
        if ($this->contexto->ativo()) {
            return;
        }
        RecursoAtualizado::dispatch('demandas');
    }
}
