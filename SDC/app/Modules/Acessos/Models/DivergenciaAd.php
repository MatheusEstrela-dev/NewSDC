<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Models;

use App\Modules\Acessos\Enums\TipoDivergenciaAd;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Diferenca entre cadastro e AD apontada por uma rodada de sincronizacao. */
class DivergenciaAd extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'acessos_divergencias_ad';

    protected $fillable = ['sincronizacao_id', 'cadastro_id', 'object_guid', 'login_ad', 'tipo', 'detalhe'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoDivergenciaAd::class,
            'detalhe' => 'array',
        ];
    }

    public function sincronizacao(): BelongsTo
    {
        return $this->belongsTo(SincronizacaoAd::class, 'sincronizacao_id');
    }

    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(CadastroAcesso::class, 'cadastro_id');
    }
}
