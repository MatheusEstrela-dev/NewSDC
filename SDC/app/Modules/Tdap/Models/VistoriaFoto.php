<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Foto de uma vistoria TDAP.
 *
 * @property int     $id
 * @property int     $vistoria_id
 * @property string  $nome_original
 * @property string  $nome_arquivo
 * @property ?string $mime_type
 * @property int     $tamanho_bytes
 * @property string  $path
 * @property string  $disk
 * @property ?string $descricao
 * @property ?int    $uploaded_by
 */
class VistoriaFoto extends Model
{
    protected $table = 'tdap_vistoria_fotos';

    protected $fillable = [
        'vistoria_id',
        'nome_original',
        'nome_arquivo',
        'mime_type',
        'tamanho_bytes',
        'path',
        'disk',
        'descricao',
        'uploaded_by',
    ];

    protected $casts = [
        'vistoria_id'   => 'integer',
        'tamanho_bytes' => 'integer',
        'uploaded_by'   => 'integer',
    ];

    protected $appends = ['tamanho_formatado'];

    public function vistoria(): BelongsTo
    {
        return $this->belongsTo(Vistoria::class, 'vistoria_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = (int) $this->tamanho_bytes;

        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 2, ',', '.').' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.').' KB';
        }

        return $bytes.' bytes';
    }
}
