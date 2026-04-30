<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use App\Modules\Rat\Enums\CategoriaAnexo;
use App\Modules\Rat\Services\RatAnexoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreRatAnexoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $service = app(RatAnexoService::class);

        return [
            'file' => [
                'required',
                'file',
                'max:' . $service->getMaxKb(),
                'mimetypes:' . implode(',', $service->getAllowedMimes()),
            ],
            'categoria' => ['nullable', new Enum(CategoriaAnexo::class)],
            'descricao' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max'       => 'O arquivo excede o tamanho maximo de 10MB.',
            'file.mimetypes' => 'Tipo de arquivo nao permitido. Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, TXT, JPG, PNG, GIF.',
            'categoria.required' => 'A categoria do anexo e obrigatoria.',
        ];
    }
}
