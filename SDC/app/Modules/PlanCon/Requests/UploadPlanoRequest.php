<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plancon.upload') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Selecione ao menos um arquivo PDF.',
            'files.*.file' => 'O plano precisa ser um arquivo valido.',
            'files.*.mimes' => 'O plano de contingencia deve ser um PDF.',
            'files.*.max' => 'Cada arquivo deve ter no maximo 50 MB.',
        ];
    }
}
