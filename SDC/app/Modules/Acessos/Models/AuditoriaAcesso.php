<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Models;

use App\Support\Database\UsaConexaoDeLog;
use Illuminate\Database\Eloquent\Model;

class AuditoriaAcesso extends Model
{
    use UsaConexaoDeLog;

    public $timestamps = false;

    protected $table = 'acessos_auditoria';

    protected $fillable = ['cadastro_id', 'actor_id', 'acao', 'dados', 'created_at'];

    protected $casts = ['dados' => 'array', 'created_at' => 'datetime'];
}
