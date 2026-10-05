<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Services\Acoes;

use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\DTOs\ResultadoOperacao;
use App\Modules\Acessos\Models\OperacaoAd;

/** Leitura: a conta que o job acabou de resolver ja e o resultado. */
final class ConsultarConta implements HandlerAcaoDiretorio
{
    public function executar(OperacaoAd $operacao, ContaDiretorio $conta): ResultadoOperacao
    {
        return new ResultadoOperacao($conta, efetivada: false);
    }
}
