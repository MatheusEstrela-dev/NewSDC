<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Enums;

use App\Modules\Ranking\Enums\EscopoPlacar;

/**
 * Ente dono da carteira de resgate. Nunca o usuario: o beneficiario e sempre
 * o ente (premissa P1 do plano), mesmo quando quem opera e um usuario com
 * permissao especial.
 */
enum EscopoCarteira: string
{
    case Municipio = 'municipio';
    case Orgao = 'orgao';

    /** Coluna do lancamento que identifica o ente; fixa, segura para SQL. */
    public function colunaDoLancamento(): string
    {
        return match ($this) {
            self::Municipio => 'municipio_id',
            self::Orgao     => 'orgao_id',
        };
    }

    /** Mesma dimensao no placar, para ler a faixa da temporada fechada. */
    public function escopoDoPlacar(): EscopoPlacar
    {
        return match ($this) {
            self::Municipio => EscopoPlacar::Municipio,
            self::Orgao     => EscopoPlacar::Orgao,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Municipio => 'Município',
            self::Orgao     => 'Órgão',
        };
    }
}
