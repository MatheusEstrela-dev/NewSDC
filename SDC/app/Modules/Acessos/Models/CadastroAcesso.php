<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CadastroAcesso extends Model
{
    protected $table = 'acessos_cadastros';

    protected $fillable = [
        'user_id', 'solicitado_por_id', 'aprovado_por_id', 'nome', 'tipo_documento',
        'documento', 'cpf', 'login_ad', 'email_corporativo', 'email_pessoal',
        'telefone_mesa', 'telefone_whatsapp', 'setor', 'posto', 'cargo',
        'observacoes_ti', 'status', 'status_ad', 'data_solicitacao', 'data_aprovacao',
    ];

    protected $casts = ['data_solicitacao' => 'datetime', 'data_aprovacao' => 'datetime'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auditoria(): HasMany
    {
        return $this->hasMany(AuditoriaAcesso::class, 'cadastro_id');
    }
}
