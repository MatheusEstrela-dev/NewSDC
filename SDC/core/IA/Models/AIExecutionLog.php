<?php

namespace App\Core\IA\Models;

use App\Support\Database\UsaConexaoDeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Log tecnico de execucao de plugin de IA: vive na conexao de log e o
 * log:podar apaga o que passou de 90 dias.
 */
class AIExecutionLog extends Model
{
    use HasUuids;
    use UsaConexaoDeLog;

    protected $table = 'ai_execution_logs';

    protected $fillable = [
        'plugin_name',
        'input_params',
        'output_result',
        'status',
        'error_message',
        'execution_time_ms',
        'user_id',
        'ip_address',
    ];

    protected $casts = [
        'input_params' => 'array',
        'output_result' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
