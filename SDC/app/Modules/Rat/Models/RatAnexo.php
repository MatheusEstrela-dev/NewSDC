<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use App\Modules\Rat\Enums\CategoriaAnexo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Modules\Rat\Models\RatOcorrencia;

/**
 * Anexo vinculado a um RAT (tabela relacional).
 *
 * Armazena metadados do arquivo em tabela dedicada,
 * enquanto o binario fica no disco configurado (default: public).
 *
 * @property int         $id
 * @property string      $rat_id          UUID do RAT pai
 * @property string      $categoria
 * @property string      $nome_original
 * @property string      $nome_arquivo
 * @property string      $mime_type
 * @property int         $tamanho_bytes
 * @property string      $path
 * @property string      $disk
 * @property string|null $descricao
 * @property int|null    $uploaded_by
 */
class RatAnexo extends Model
{
    protected $table = 'rat_anexos';

    protected $fillable = [
        'rat_id',
        'categoria',
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
        'categoria'     => CategoriaAnexo::class,
        'tamanho_bytes' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    public function rat(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'rat_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho_bytes;

        if ($bytes >= 1_073_741_824) {
            return number_format($bytes / 1_073_741_824, 2) . ' GB';
        }
        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
