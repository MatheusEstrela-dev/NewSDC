<?php

namespace App\Models\Log;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    use HasFactory;

    protected $table = 'logs';
    protected $primaryKey = 'id';

    protected $fillable = [
        'level',
        'message',
        'channel',
        'user_id',
        'remote_addr',
        'user_agent',
        'instance',
        'context',
        'formatted',
        'class',
        'method',
        'line',
        'file',
        'url',
        'http_method',
        'request_data',
        'stack_trace',
        'layer',
        'request_id',
        'parent_log_id',
        'duration_ms',
        'memory_usage',
        'tags',
        'event',
        'model_id',
        'model_table',
    ];

    protected $casts = [
        'tags' => 'array',
        'duration_ms' => 'decimal:2',
        'memory_usage' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['error', 'critical', 'emergency', 'alert']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByLayer($query, string $layer)
    {
        return $query->where('layer', $layer);
    }

    public function scopeByClass($query, string $class)
    {
        return $query->where('class', 'like', '%' . $class . '%');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeByRequestId($query, string $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeByModel($query, string $modelTable, ?string $modelId = null)
    {
        $query->where('model_table', $modelTable);

        if ($modelId !== null) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    public function scopeSlow($query, int $thresholdMs = 1000)
    {
        return $query->where('duration_ms', '>', $thresholdMs);
    }

    public function parentLog()
    {
        return $this->belongsTo(self::class, 'parent_log_id');
    }

    public function childLogs()
    {
        return $this->hasMany(self::class, 'parent_log_id');
    }

    public function getRequestChain(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->request_id) {
            return collect([$this]);
        }

        return self::byRequestId($this->request_id)
            ->orderBy('created_at')
            ->get();
    }
}
