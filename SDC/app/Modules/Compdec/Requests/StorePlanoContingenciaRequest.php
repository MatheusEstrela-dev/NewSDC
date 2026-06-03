<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Requests;

use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanoContingenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CompdecPlanoContingencia::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) (config('compdec.upload_limits.plano_arquivo', 20 * 1024 * 1024) / 1024);

        return [
            'versao' => ['required', 'string', 'max:40'],
            'observacoes' => ['nullable', 'string'],
            'ativo' => ['nullable', 'boolean'],
            'arquivo' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,odt',
                'max:' . $maxKb,
            ],
        ];
    }
}
