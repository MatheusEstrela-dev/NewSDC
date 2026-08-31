<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Models;

use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Enums\UnidadeItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Checklist polimorfico. Colapsa ~87 colunas repetidas nas tres tabelas de
 * relatorio do legado, com nomes divergentes entre elas.
 */
class CisternaItemConferido extends Model
{
    protected $table = 'cisterna_itens_conferidos';

    protected $fillable = [
        'conferivel_type', 'conferivel_id',
        'item', 'conferido', 'quantidade', 'unidade', 'detalhes', 'observacao',
    ];

    protected $casts = [
        'item' => ItemInstalacao::class,
        'unidade' => UnidadeItem::class,
        'conferido' => 'boolean',
        'quantidade' => 'decimal:2',
        // Somente `fixacao` usa hoje: abracadeira, bucha, parafuso.
        'detalhes' => 'array',
    ];

    public function conferivel(): MorphTo
    {
        return $this->morphTo();
    }
}
