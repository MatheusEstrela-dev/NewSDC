<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Contracts;

use Carbon\CarbonInterface;

/**
 * Escreve a camada Bronze vencida em arquivo colunar.
 *
 * A interface existe porque nenhuma lib PHP de Parquet e madura (flow-php esta
 * em 0.x). Isolando a escrita aqui, trocar de lib e alterar uma classe.
 */
interface ArquivadorBronze
{
    /**
     * Escreve as linhas e devolve o caminho relativo no disco.
     *
     * Deve lancar excecao se a escrita ou a verificacao falhar — quem chama
     * depende disso para nao podar o Bronze de um arquivo que nao existe.
     *
     * @param iterable<array<string, mixed>> $linhas
     */
    public function arquivar(string $fonte, CarbonInterface $dia, iterable $linhas): string;
}
