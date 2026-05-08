<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Requests;

use App\Modules\Compdec\Enums\TipoAnexo;
use App\Modules\Compdec\Models\CompdecAnexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAnexoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CompdecAnexo::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) (config('compdec.upload_limits.anexo_arquivo', 2 * 1024 * 1024) / 1024);

        return [
            'tipo' => ['required', new Enum(TipoAnexo::class)],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'numero' => ['nullable', 'string', 'max:60'],
            'data_emissao' => ['nullable', 'date'],
            'data_validade' => ['nullable', 'date', 'after_or_equal:data_emissao'],
            'arquivo' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,odt',
                'max:' . $maxKb,
            ],
        ];
    }
}
