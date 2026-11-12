<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Database\UsaConexaoDeTreinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Configuracao da prova de um item PROVA (1:1 com modulo_itens). O item e
 * quem e excluido logicamente; a prova acompanha.
 */
class Prova extends Model
{
    use UsaConexaoDeTreinamento;

    public const NOTA_MINIMA_PADRAO = 70;

    public const MAX_TENTATIVAS_PADRAO = 3;

    /** Toda nota (nota_minima, nota_final, nota da tentativa, aproveitamento) e um percentual de 0 a ESCALA_NOTA. */
    public const ESCALA_NOTA = 100;

    protected $table = 'treinamento.provas';

    protected $fillable = ['modulo_item_id', 'nota_minima', 'max_tentativas'];

    protected $attributes = [
        'nota_minima' => self::NOTA_MINIMA_PADRAO,
        'max_tentativas' => self::MAX_TENTATIVAS_PADRAO,
    ];

    protected $casts = [
        'modulo_item_id' => 'integer',
        'nota_minima' => 'float',
        'max_tentativas' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ModuloItem::class, 'modulo_item_id')->withTrashed();
    }

    /** Questoes ativas, na ordem definida pelo professor. */
    public function questoes(): HasMany
    {
        return $this->hasMany(ProvaQuestao::class)->orderBy('ordem')->orderBy('id');
    }

    public function tentativas(): HasMany
    {
        return $this->hasMany(ProvaTentativa::class);
    }

    public function liberacoes(): HasMany
    {
        return $this->hasMany(ProvaLiberacao::class);
    }
}
