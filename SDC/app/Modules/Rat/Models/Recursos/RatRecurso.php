<?php

declare(strict_types=1);

namespace App\Models\Rat\Recursos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Classe base para modelos de recurso empregado em ocorrências RAT.
 */
abstract class RatRecurso extends Model
{
    use SoftDeletes;
}
