<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Enums\NotaAvaliacaoCurso;
use App\Modules\Treinamento\Enums\NotaRelevanciaCurso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Feedback de conclusao do curso. Quem pode avaliar (o dono da inscricao, uma
 * vez) e decidido no controller pela InscricaoDoAluno::paraAvaliar e pelo
 * AvaliacaoCursoService, nao aqui.
 */
class AvaliarCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nota' => ['required', 'integer', Rule::enum(NotaAvaliacaoCurso::class)],
            'nota_relevancia' => ['required', 'integer', Rule::enum(NotaRelevanciaCurso::class)],
            'comentario' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nota.required' => 'Escolha uma nota de 1 a 5 na barra de satisfação.',
            'nota.integer' => 'A nota deve ser de 1 a 5.',
            'nota.enum' => 'A nota deve ser de 1 a 5.',
            'nota_relevancia.required' => 'Diga em que medida o conteúdo foi relevante, de 1 a 5.',
            'nota_relevancia.integer' => 'A relevância deve ser de 1 a 5.',
            'nota_relevancia.enum' => 'A relevância deve ser de 1 a 5.',
            'comentario.required' => 'Deixe uma sugestão, comentário ou tema para futuras capacitações.',
            'comentario.string' => 'O comentário deve ser um texto.',
            'comentario.max' => 'O comentário pode ter no máximo 2000 caracteres.',
        ];
    }

    public function nota(): NotaAvaliacaoCurso
    {
        return NotaAvaliacaoCurso::from((int) $this->validated('nota'));
    }

    public function notaRelevancia(): NotaRelevanciaCurso
    {
        return NotaRelevanciaCurso::from((int) $this->validated('nota_relevancia'));
    }

    public function comentario(): string
    {
        return (string) $this->validated('comentario');
    }
}
