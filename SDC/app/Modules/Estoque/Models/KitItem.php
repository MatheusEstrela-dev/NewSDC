<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitItem extends Model
{
    public $timestamps = false;

    protected $table = 'estoque_kit_itens';

    protected $fillable = ['kit_id', 'produto_id', 'quantidade'];

    protected $casts = ['quantidade' => 'integer'];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id')->withTrashed();
    }
}
