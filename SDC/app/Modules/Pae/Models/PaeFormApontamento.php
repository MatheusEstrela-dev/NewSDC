<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use Illuminate\Database\Eloquent\Model;

class PaeFormApontamento extends Model
{
    public $timestamps = false;

    protected $table = 'pae_form_apontamentos';

    protected $fillable = [
        'pae_form_id',
        'parent_id',
        'status',
        'ordem',
        'conteudo',
    ];

    public function form()
    {
        return $this->belongsTo(PaeForm::class, 'pae_form_id');
    }
}
