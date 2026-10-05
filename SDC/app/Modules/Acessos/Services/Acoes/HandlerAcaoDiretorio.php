<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Services\Acoes;

use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\DTOs\ResultadoOperacao;
use App\Modules\Acessos\Models\OperacaoAd;

/**
 * Efeito de uma AcaoDiretorio sobre a conta ja resolvida (e, nas escritas, ja
 * liberada pela guarda). Resolvido pelo job a partir de AcaoDiretorio::handler().
 */
interface HandlerAcaoDiretorio
{
    public function executar(OperacaoAd $operacao, ContaDiretorio $conta): ResultadoOperacao;
}
