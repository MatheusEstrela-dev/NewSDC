<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Contracts;

use App\Modules\Medalhao\DTOs\PayloadBruto;

/**
 * Converte o payload bruto da camada Bronze em DTOs de dominio (camada Silver).
 *
 * Implementacoes devem iterar sem materializar o conteudo inteiro em memoria —
 * daí o retorno ser iterable, tipicamente um Generator. Isso mantem o consumo do
 * worker estavel independente do tamanho do payload, e permite trocar o consumo
 * por Bus::batch adiante sem alterar as fontes.
 */
interface NormalizadorSilver
{
    /** @return iterable<object> */
    public function normalizar(PayloadBruto $bruto): iterable;
}
