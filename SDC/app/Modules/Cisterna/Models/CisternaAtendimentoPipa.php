<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Modules\Cisterna\Enums\ResponsavelPipa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CisternaAtendimentoPipa extends Model
{
    public $timestamps = false;

    protected $table = 'cisterna_atendimentos_pipa';

    protected $fillable = ['beneficiario_id', 'responsavel', 'descricao'];

    protected $casts = ['responsavel' => ResponsavelPipa::class];

    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(CisternaBeneficiario::class, 'beneficiario_id');
    }
}
