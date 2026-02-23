<?php

namespace App\Core\IA\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AIExecutionLog extends Model
{
    use HasUuids;

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
