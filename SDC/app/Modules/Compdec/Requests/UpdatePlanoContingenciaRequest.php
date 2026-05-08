<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanoContingenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plano = $this->route('plano');

        return $this->user()?->can('update', $plano) ?? false;
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
            'arquivo' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,odt',
                'max:' . $maxKb,
            ],
        ];
    }
}
