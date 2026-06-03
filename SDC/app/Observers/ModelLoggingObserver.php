<?php

declare(strict_types=1);

namespace App\Observers;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ModelLoggingObserver
{
    use LogsActivity;

    protected array $excludedModels = [
        'App\Models\Log\Logs',
        'App\Models\AuditLog',
        'App\Models\PermissionAuditLog',
    ];

    protected array $sensitiveFields = [
        'password', 'remember_token', 'api_token', 'secret', 'token',
    ];

    public function creating(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->logModelEvent($model, 'creating', [
            'attributes' => $this->sanitizeAttributes($model->getAttributes()),
        ]);
    }

    public function created(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->logModelEvent($model, 'created', [
            'id' => $model->getKey(),
            'attributes' => $this->sanitizeAttributes($model->getAttributes()),
        ]);
    }

    public function updating(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $dirty = $model->getDirty();
        $original = collect($model->getOriginal())->only(array_keys($dirty))->toArray();

        $this->logModelEvent($model, 'updating', [
            'id' => $model->getKey(),
            'changes' => $this->sanitizeAttributes($dirty),
            'original' => $this->sanitizeAttributes($original),
            'changed_fields' => array_keys($dirty),
        ]);
    }

    public function updated(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $changes = $model->getChanges();

        $this->logModelEvent($model, 'updated', [
            'id' => $model->getKey(),
            'changes' => $this->sanitizeAttributes($changes),
            'changed_fields' => array_keys($changes),
        ]);
    }

    public function deleting(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->logModelEvent($model, 'deleting', [
            'id' => $model->getKey(),
            'attributes' => $this->sanitizeAttributes($model->getAttributes()),
        ]);
    }

    public function deleted(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->logModelEvent($model, 'deleted', [
            'id' => $model->getKey(),
        ]);
    }

    public function restoring(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->logModelEvent($model, 'restoring', [
            'id' => $model->getKey(),
        ]);
    }

    public function restored(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->logModelEvent($model, 'restored', [
            'id' => $model->getKey(),
        ]);
    }

    public function forceDeleting(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->logModelEvent($model, 'force_deleting', [
            'id' => $model->getKey(),
            'attributes' => $this->sanitizeAttributes($model->getAttributes()),
        ]);
    }

    public function forceDeleted(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->logModelEvent($model, 'force_deleted', [
            'id' => $model->getKey(),
        ]);
    }

    protected function shouldSkip(Model $model): bool
    {
        return in_array(get_class($model), $this->excludedModels);
    }

    protected function logModelEvent(Model $model, string $event, array $additionalContext = []): void
    {
        $req = request();

        $context = array_merge([
            'request_id' => $req?->header('X-Request-ID'),
            'layer' => 'Model',
            'class' => get_class($model),
            'method' => $event,
            'table' => $model->getTable(),
            'model_id' => $model->getKey(),
            'user_id' => Auth::id(),
            'url' => $req?->fullUrl() ?? 'cli',
            'http_method' => $req?->method() ?? 'cli',
        ], $additionalContext);

        $level = $this->getLogLevel($event);
        $message = sprintf(
            'Model %s: %s (ID: %s)',
            $event,
            class_basename($model),
            $model->getKey() ?? 'new'
        );

        Log::channel('db')->{$level}($message, $context);
    }

    protected function getLogLevel(string $event): string
    {
        return match ($event) {
            'deleting', 'deleted', 'force_deleting', 'force_deleted' => 'warning',
            default => 'info',
        };
    }

    protected function sanitizeAttributes(array $attributes): array
    {
        $sanitized = [];

        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_string($value) && strlen($value) > 500) {
                $sanitized[$key] = substr($value, 0, 500) . '...[truncated]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
