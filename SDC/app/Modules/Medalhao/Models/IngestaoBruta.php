<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Models;

use Illuminate\Database\Eloquent\Model;

class IngestaoBruta extends Model
{
    protected $table = 'bronze.ingestao_bruta';

    // A tabela usa coletado_em/processado_em, nao created_at/updated_at: o
    // instante da coleta e dado da ingestao, nao metadado do ORM.
    public $timestamps = false;

    protected $fillable = [
        'fonte',
        'conteudo_bruto',
        'formato',
        'hash_conteudo',
        'meta',
        'coletado_em',
        'processado_em',
    ];

    protected $casts = [
        'meta' => 'array',
        'coletado_em' => 'datetime',
        'processado_em' => 'datetime',
    ];
}
