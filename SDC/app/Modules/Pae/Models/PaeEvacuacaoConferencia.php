<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use App\Modules\Pae\Support\Evacuacao\CalculoEvacuacaoAnexoE;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Versao imutavel da conferencia de evacuacao (Anexo E e item 8 do Anexo B). */
final class PaeEvacuacaoConferencia extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'pae_evacuacao_conferencias';

    protected $fillable = [
        'protocolo_id', 'versao', 'setores', 'rotas', 'acessos', 'pontos_encontro',
        'tte_declarado_segundos', 'num_sei', 'observacao', 'tmd_segundos', 'te_segundos',
        'tte_segundos', 'criterio1_conforme', 'criterio2_conforme', 'possui_rota_invalida',
        'possui_setor_inviavel', 'excede_declarado', 'resultado', 'chave_idempotencia', 'criado_por',
    ];

    protected $casts = [
        'versao' => 'integer',
        'setores' => 'array',
        'rotas' => 'array',
        'acessos' => 'array',
        'pontos_encontro' => 'array',
        'resultado' => 'array',
        'tte_declarado_segundos' => 'integer',
        'tmd_segundos' => 'float',
        'te_segundos' => 'float',
        'tte_segundos' => 'float',
        'criterio1_conforme' => 'boolean',
        'criterio2_conforme' => 'boolean',
        'possui_rota_invalida' => 'boolean',
        'possui_setor_inviavel' => 'boolean',
        'excede_declarado' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function entrada(): array
    {
        return [
            'setores' => $this->setores,
            'rotas' => $this->rotas,
            'acessos' => $this->acessos,
            'pontos_encontro' => $this->pontos_encontro,
            'tte_declarado_segundos' => $this->tte_declarado_segundos,
        ];
    }

    public function conforme(): bool
    {
        return CalculoEvacuacaoAnexoE::conforme($this->only([
            'criterio1_conforme', 'criterio2_conforme', 'possui_rota_invalida', 'possui_setor_inviavel', 'excede_declarado',
        ]));
    }
}
