<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Enums\Sexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Inscricao (servidor e cidadao). Quem pode se inscrever e decidido pelo
 * middleware da rota e pelas regras do InscricaoService; aqui so se valida o
 * sexo, opcional ("Prefiro nao informar" chega como vazio e vira null), e a
 * turma, quando o curso tiver turma (o InscricaoService e que decide se ela
 * e obrigatoria - aqui so se valida o formato).
 */
class InscreverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sexo' => ['nullable', Rule::enum(Sexo::class)],
            'turma_id' => ['nullable', 'integer'],
        ];
    }

    public function sexo(): ?Sexo
    {
        return Sexo::tryFrom((string) $this->validated('sexo'));
    }

    public function turmaId(): ?int
    {
        $turmaId = $this->validated('turma_id');

        return $turmaId === null ? null : (int) $turmaId;
    }
}
