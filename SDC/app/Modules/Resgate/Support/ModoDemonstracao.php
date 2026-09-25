<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Support;

/**
 * Modo demonstracao do resgate: pedir item de demonstracao com pontos de
 * demonstracao, para homologar o fluxo antes do normativo.
 *
 * TRAVA DE PRODUCAO: com APP_ENV=production o modo e SEMPRE desligado, mesmo
 * que RESGATE_PERMITIR_DEMONSTRACAO venha ligado por engano. A premissa P5 do
 * plano (ponto de demonstracao nunca e resgatavel) continua valendo onde ha
 * consequencia real.
 */
final class ModoDemonstracao
{
    public function ativo(): bool
    {
        return ! app()->environment('production') && (bool) config('resgate.permitir_demonstracao');
    }
}
