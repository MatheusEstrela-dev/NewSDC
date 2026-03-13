<?php

declare(strict_types=1);

namespace App\Models\Rat\Relatos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\Rat\RatOcorrenciaRelato;

/**
 * Classe base para todos os tipos de relato polimórfico.
 * Fornece o link de volta à tabela pivô RatOcorrenciaRelato.
 */
abstract class RatRelato extends Model
{
    use SoftDeletes;

    /** Entrada na tabela pivô que aponta para este relato. */
    public function ocorrenciaRelato(): MorphOne
    {
        return $this->morphOne(RatOcorrenciaRelato::class, 'conteudo');
    }
}
