<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Contracts;

use App\Modules\Treinamento\DTOs\ResultadoConclusao;
use App\Modules\Treinamento\Models\Inscricao;

/**
 * Regra que decide se uma inscricao concluiu o treinamento (spec 8.4),
 * escolhida pelo tipo do treinamento: por frequencia (eventos presenciais e
 * online ao vivo) ou por trilha (EAD). O CertificadoService delega a ela.
 */
interface CriterioConclusao
{
    /**
     * Resultado da conclusao, ou null enquanto a inscricao nao cumpre o
     * criterio. Nao grava nada: congelar o resultado na inscricao e emitir o
     * certificado e de quem chama.
     */
    public function avaliar(Inscricao $inscricao): ?ResultadoConclusao;
}
