<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RAT legado (arquivo morto). Tabela `legado_rat`, importada do sistema antigo
 * (com_rat). Somente leitura: nao ha create/update/delete neste modulo.
 *
 * Resolucao de municipio via ponte:
 *   legado_rat.municipio_id -> cedec_municipio.id -> cedec_municipio.Codmundv
 *   -> municipios.codigo_ibge -> municipios.nome
 * A resolucao e feita por join no LegadoRatService (evita N+1); os nomes chegam
 * como atributos extra (municipio_nome, cobrade_nome, tipo_descricao).
 *
 * @property int $id
 * @property string $num_ocorrencia
 * @property ?string $dt_ocorrencia
 * @property ?int $municipio_id
 * @property ?string $operador_nome
 * @property ?int $ocorrencia_id
 * @property ?int $alvo_id
 * @property ?int $cobrade_id
 * @property ?string $acoes
 */
class LegadoRat extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'legado_rat';

    protected $keyType = 'int';

    protected $casts = [
        'municipio_id' => 'integer',
        'operador_id' => 'integer',
        'ocorrencia_id' => 'integer',
        'alvo_id' => 'integer',
        'cobrade_id' => 'integer',
        'dt_ocorrencia' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Somente leitura: bloqueia qualquer tentativa de persistencia no arquivo morto.
     */
    public static function boot(): void
    {
        parent::boot();

        foreach (['creating', 'updating', 'deleting'] as $evento) {
            static::$evento(static fn (): bool => false);
        }
    }
}
