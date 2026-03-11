<?php

declare(strict_types=1);

namespace App\Core\DTO;

use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

abstract class BaseDTO
{
    use LogsActivity;

    protected array $originalData = [];

    public function __construct(array $data = [])
    {
        $this->originalData = $data;
        $this->logCreation($data);
    }

    public static function fromRequest(Request $request): static
    {
        $data = $request->validated() ?: $request->all();

        Log::channel('db')->debug('DTO created from request', [
            'request_id' => $request->header('X-Request-ID'),
            'layer' => 'DTO',
            'class' => static::class,
            'method' => 'fromRequest',
            'url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'user_id' => $request->user()?->id,
            'data_keys' => array_keys($data),
        ]);

        return new static($data);
    }

    public static function fromArray(array $data): static
    {
        return new static($data);
    }

    protected function logCreation(array $data): void
    {
        Log::channel('db')->debug('DTO instantiated', [
            'layer' => 'DTO',
            'class' => static::class,
            'method' => '__construct',
            'data_keys' => array_keys($data),
            'data_count' => count($data),
        ]);
    }

    protected function validate(array $data, array $rules): void
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            if ($rule === 'required' && !isset($data[$field])) {
                $errors[$field] = "Field '{$field}' is required";
            }
        }

        if (!empty($errors)) {
            Log::channel('db')->error('DTO validation failed', [
                'layer' => 'DTO',
                'class' => static::class,
                'method' => 'validate',
                'errors' => $errors,
            ]);

            throw new InvalidArgumentException(
                'DTO validation failed: ' . json_encode($errors)
            );
        }
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->originalData[$key] ?? $default;
    }

    protected function has(string $key): bool
    {
        return isset($this->originalData[$key]);
    }

    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $data = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            if ($property->isInitialized($this)) {
                $data[$name] = $this->{$name};
            }
        }

        return $data;
    }

    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    public function with(string $key, mixed $value): static
    {
        $data = $this->toArray();
        $data[$key] = $value;

        return new static($data);
    }

    public function __debugInfo(): array
    {
        return [
            'class' => static::class,
            'data' => $this->toArray(),
        ];
    }
}
