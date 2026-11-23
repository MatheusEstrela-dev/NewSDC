<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Services\CapaDoCursoService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Imagem de capa (do curso ou da pasta de categoria). Quem pode trocar ja foi
 * decidido pelo middleware da rota: leciona.curso (curso) ou
 * can:treinamento.cursos.edit (categoria).
 */
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
            'capa.max' => 'A imagem pode ter no máximo 10 MB.',
            'capa.uploaded' => 'A imagem pode ter no máximo 10 MB.',
        ];
    }
}
