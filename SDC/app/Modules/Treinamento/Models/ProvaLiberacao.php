<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Models\User;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tentativas extras concedidas pelo professor a uma inscricao travada:
 * quanto, quem (users.id do sdc, sem FK) e quando (created_at). Registro
 * imutavel, por isso sem updated_at.
 */
class ProvaLiberacao extends Model
{
    use UsaConexaoDeTreinamento;

    public const UPDATED_AT = null;

    protected $table = 'treinamento.prova_liberacoes';

    protected $fillable = ['prova_id', 'inscricao_id', 'quantidade', 'liberado_por'];

    protected $casts = [
        'prova_id' => 'integer',
        'inscricao_id' => 'integer',
        'quantidade' => 'integer',
        'liberado_por' => 'integer',
    ];

    public function prova(): BelongsTo
    {
        return $this->belongsTo(Prova::class);
    }

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function liberadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'liberado_por');
    }
}
