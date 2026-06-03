<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Traits\LogsActivity;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

abstract class BaseFormRequest extends FormRequest
{
    use LogsActivity;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->logInfo('Validation starting', [
            'rules_count' => count($this->rules()),
            'input_keys' => array_keys($this->all()),
        ]);
    }

    protected function passedValidation(): void
    {
        $this->logInfo('Validation passed', [
            'validated_keys' => array_keys($this->validated()),
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();

        Log::channel('db')->warning('Validation failed', [
            'request_id' => $this->getRequestId(),
            'layer' => 'Request',
            'class' => static::class,
            'method' => 'failedValidation',
            'url' => $this->fullUrl(),
            'http_method' => $this->method(),
            'user_id' => $this->user()?->id,
            'errors' => $errors,
            'error_count' => count($errors),
            'failed_fields' => array_keys($errors),
            'input_data' => $this->sanitizeParams($this->except([
                'password', 'password_confirmation', 'current_password'
            ])),
        ]);

        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors,
                ], 422)
            );
        }

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    protected function failedAuthorization(): void
    {
        Log::channel('db')->warning('Authorization failed', [
            'request_id' => $this->getRequestId(),
            'layer' => 'Request',
            'class' => static::class,
            'method' => 'failedAuthorization',
            'url' => $this->fullUrl(),
            'http_method' => $this->method(),
            'user_id' => $this->user()?->id,
            'ip' => $this->ip(),
        ]);

        parent::failedAuthorization();
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
