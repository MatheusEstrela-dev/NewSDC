<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Models;

/**
 * Protege instancias Eloquent, inclusive saveQuietly/deleteQuietly.
 * Operacoes diretas no query builder exigem protecao adicional no banco.
 */
abstract class RegistroImutavel extends RankingModel
{
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \LogicException('Registro imutavel: publique uma nova versao ou lancamento corretivo.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new \LogicException('Registros imutaveis nao podem ser excluidos.');
    }
}
