<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoUpload extends Model
{
    use HasUuids, UsaConexaoDeTreinamento;

    public const ABERTO = 'ABERTO';

    public const COMPLETO = 'COMPLETO';

    public const CANCELADO = 'CANCELADO';

    protected $table = 'treinamento.video_uploads';

    protected $fillable = [
        'video_id',
        'user_id',
        'nome_original',
        'mime',
        'tamanho_total',
        'tamanho_parte',
        'total_partes',
        'partes_recebidas',
        'tamanho_recebido',
        'status',
        'expira_em',
    ];

    protected $casts = [
        'video_id' => 'integer',
        'user_id' => 'integer',
        'tamanho_total' => 'integer',
        'tamanho_parte' => 'integer',
        'total_partes' => 'integer',
        'partes_recebidas' => 'integer',
        'tamanho_recebido' => 'integer',
        'expira_em' => 'datetime',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function estaAberto(): bool
    {
        return $this->status === self::ABERTO && $this->expira_em->isFuture();
    }

    public function completo(): bool
    {
        return $this->partes_recebidas === $this->total_partes
            && $this->tamanho_recebido === $this->tamanho_total;
    }
}
