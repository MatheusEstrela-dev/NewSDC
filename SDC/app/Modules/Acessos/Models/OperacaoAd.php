<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Models;

use App\Models\User;
use App\Modules\Acessos\Enums\AcaoDiretorio;
use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Enums\EstadoOperacaoAd;
use App\Modules\Acessos\Enums\OrigemOperacaoAd;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Razao unica de toda acao no AD, venha da tela de Acessos ou da automacao de
 * Demandas. O erro persistido e so o codigo da lista fechada; nunca texto do LDAP.
 */
class OperacaoAd extends Model
{
    use HasUuids;

    protected $table = 'acessos_operacoes_ad';

    protected $fillable = [
        'chave_idempotencia', 'cadastro_id', 'login_ad', 'object_guid', 'acao', 'origem', 'origem_id',
        'solicitado_por_id', 'motivo', 'estado', 'codigo_erro', 'tentativas', 'resultado',
        'enviado_em', 'concluido_em',
    ];

    protected $attributes = [
        'estado' => 'solicitado',
        'tentativas' => 0,
    ];

    protected function casts(): array
    {
        return [
            'acao' => AcaoDiretorio::class,
            'estado' => EstadoOperacaoAd::class,
            'origem' => OrigemOperacaoAd::class,
            'codigo_erro' => CodigoErroDiretorio::class,
            'origem_id' => 'integer',
            'tentativas' => 'integer',
            'resultado' => 'array',
            'enviado_em' => 'datetime',
            'concluido_em' => 'datetime',
        ];
    }

    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(CadastroAcesso::class, 'cadastro_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por_id');
    }

    public function entrega(): HasOne
    {
        return $this->hasOne(EntregaSenha::class, 'operacao_id');
    }

    public function estaEmVoo(): bool
    {
        return ! $this->estaFinalizada();
    }

    public function estaFinalizada(): bool
    {
        return $this->estado->final();
    }
}
