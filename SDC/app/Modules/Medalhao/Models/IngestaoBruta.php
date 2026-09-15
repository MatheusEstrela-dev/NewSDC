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
        'verificado_em',
    ];

    protected $casts = [
        'meta' => 'array',
        'coletado_em' => 'datetime',
        'processado_em' => 'datetime',
        'verificado_em' => 'datetime',
    ];

    /**
     * Instante da verificacao mais recente entre as fontes informadas.
     *
     * Fica no kernel, e nao nos repositorios de dominio, porque a pergunta
     * "quando checamos por ultimo" e identica para sismos, INMET e CEMADEN --
     * so muda a lista de fontes, que o chamador obtem do IngestorRegistry.
     *
     * @param list<string> $fontes
     */
    public static function verificadoEm(array $fontes): ?string
    {
        if ($fontes === []) {
            return null;
        }

        return static::query()
            ->whereIn('fonte', $fontes)
            ->max('verificado_em');
    }
}
