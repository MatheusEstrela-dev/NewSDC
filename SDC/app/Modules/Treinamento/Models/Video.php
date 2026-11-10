<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Modules\Treinamento\Enums\VideoStatus;
use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Registro da biblioteca de videos: um mesmo video pode ser usado por itens
 * de varios cursos.
 */
class Video extends Model
{
    use SoftDeletes, UsaConexaoDeTreinamento;

    protected $table = 'treinamento.videos';

    protected $fillable = [
        'titulo',
        'user_id',
        'status',
        'original_path',
        'tamanho_bytes',
        'hls_versao',
        'duracao_segundos',
        'largura',
        'altura',
        'processado_em',
        'erro',
        'tentativas',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'status' => VideoStatus::class,
        'tamanho_bytes' => 'integer',
        'duracao_segundos' => 'integer',
        'largura' => 'integer',
        'altura' => 'integer',
        'processado_em' => 'datetime',
        'tentativas' => 'integer',
    ];

    public function uploads(): HasMany
    {
        return $this->hasMany(VideoUpload::class);
    }

    public function uploadAberto(): HasOne
    {
        return $this->hasOne(VideoUpload::class)->where('status', VideoUpload::ABERTO);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ModuloItem::class);
    }

    public function estaPronto(): bool
    {
        return $this->status->estaPronto();
    }

    /** Existe item nao excluido apontando para este video. */
    public function emUso(): bool
    {
        return $this->itens()->exists();
    }
}
