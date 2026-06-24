<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Models;

use App\Models\Municipio;
use App\Modules\Pmda\Enums\PmdaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmdaPlano extends Model
{
    use SoftDeletes;

    protected $table = 'pmda_planos';

    protected $fillable = [
        'protocolo', 'municipio_id', 'status', 'data', 'acoes', 'qtd_caminhao',
        'pop_at_municipio', 'pedido_altera', 'alterar_com', 'resp_homolog', 'dt_analise',
        'dt_ultima_alteracao', 'data_aprov', 'resp_estado', 'dt_estado',
        'cobra_iss', 'num_lei_iss', 'aliquota_iss', 'resp_cob_iss', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'status'              => PmdaStatus::class,
        'data'                => 'datetime',
        'dt_analise'          => 'datetime',
        'data_aprov'          => 'datetime',
        'dt_estado'           => 'datetime',
        'dt_ultima_alteracao' => 'datetime',
        'pedido_altera'       => 'boolean',
        'alterar_com'         => 'boolean',
        'cobra_iss'           => 'boolean',
        'aliquota_iss'        => 'decimal:2',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}
