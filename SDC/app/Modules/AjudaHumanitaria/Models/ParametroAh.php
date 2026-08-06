<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RN-16: parametros do modulo em linha unica, editaveis pelo CEDEC sem
 * necessidade de deploy.
 */
class ParametroAh extends Model
{
    protected $table = 'parametros_ah';

    protected $fillable = [
        'prazo_prestacao_contas_dias',
    ];

    protected $casts = [
        'prazo_prestacao_contas_dias' => 'integer',
    ];

    /**
     * A linha unica de parametros. Cria com os valores padrao se ainda nao
     * existir, para que o modulo nunca opere sem parametro.
     */
    public static function atual(): self
    {
        return static::query()->firstOrCreate([], ['prazo_prestacao_contas_dias' => 30]);
    }
}
