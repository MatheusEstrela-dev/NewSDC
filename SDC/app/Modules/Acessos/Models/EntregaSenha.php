<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Senha de reset cifrada aguardando a leitura unica do operador. A gravacao e a
 * retirada sao do CofreEntregaSenha; senha_cifrada fica fora da atribuicao em
 * massa e da serializacao.
 */
class EntregaSenha extends Model
{
    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $table = 'acessos_entregas_senha';

    protected $primaryKey = 'operacao_id';

    protected $keyType = 'string';

    protected $fillable = ['operacao_id', 'destinatario_id', 'expira_em'];

    protected $hidden = ['senha_cifrada'];

    protected function casts(): array
    {
        return ['expira_em' => 'datetime'];
    }

    public function operacao(): BelongsTo
    {
        return $this->belongsTo(OperacaoAd::class, 'operacao_id');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }
}
