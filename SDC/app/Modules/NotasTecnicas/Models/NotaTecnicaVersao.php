<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Um PDF publicado de uma nota. O arquivo vive no disk `notas_tecnicas`. */
class NotaTecnicaVersao extends Model
{
    public const DISK = 'notas_tecnicas';

    protected $table = 'nota_tecnica_versoes';

    protected $fillable = [
        'nota_tecnica_id',
        'numero',
        'arquivo_path',
        'arquivo_nome',
        'arquivo_tamanho',
        'changelog',
        'publicada_em',
        'publicada_por',
        'conteudo_texto',
    ];

    /** Texto extraido do PDF: pesado e so serve a busca, nunca vai para a tela. */
    protected $hidden = [
        'conteudo_texto',
    ];

    protected $casts = [
        'arquivo_tamanho' => 'integer',
        'publicada_em' => 'datetime',
    ];

    public function nota(): BelongsTo
    {
        return $this->belongsTo(NotaTecnica::class, 'nota_tecnica_id');
    }

    public function publicadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publicada_por');
    }

    public function leituras(): HasMany
    {
        return $this->hasMany(NotaTecnicaLeitura::class, 'nota_tecnica_versao_id');
    }
}
