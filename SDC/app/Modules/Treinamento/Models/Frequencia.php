<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusFrequencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Frequencia extends Model
{
    protected $table = 'frequencias';

    protected $fillable = [
        'modulo_id',
        'inscricao_id',
        'status',
        'origem',
        'data_aula',
        'observacoes',
        'registrado_por_id',
    ];

    protected $casts = [
        'status' => StatusFrequencia::class,
        'data_aula' => 'date',
    ];

    // Relationships

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    /**
     * Acesso ao inscrito (User ou Cidadao) via $frequencia->inscricao->inscrito.
     */
    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    // Scopes

    public function scopePresente($query)
    {
        return $query->where('status', StatusFrequencia::PRESENTE->value);
    }

    public function scopeAusente($query)
    {
        return $query->where('status', StatusFrequencia::AUSENTE->value);
    }

    public function scopeJustificada($query)
    {
        return $query->where('status', StatusFrequencia::JUSTIFICADA->value);
    }

    public function scopePorModulo($query, int $moduloId)
    {
        return $query->where('modulo_id', $moduloId);
    }
}
