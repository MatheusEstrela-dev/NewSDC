<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Services\CapaDoCursoService;
use Illuminate\Foundation\Http\FormRequest;

/** Quem pode trocar a capa ja foi decidido pelo middleware leciona.curso. */
class CapaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'capa' => [
                'required',
                'image',
                'mimes:'.implode(',', CapaDoCursoService::MIMES),
                'max:'.CapaDoCursoService::TAMANHO_MAXIMO_KB,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'capa.required' => 'Escolha uma imagem.',
            'capa.image' => 'O arquivo precisa ser uma imagem.',
            'capa.mimes' => 'Use uma imagem JPG, PNG ou WebP.',
            'capa.max' => 'A imagem pode ter no máximo 2 MB.',
            'capa.uploaded' => 'A imagem pode ter no máximo 2 MB.',
        ];
    }
}
