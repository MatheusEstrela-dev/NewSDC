<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Enums\Sexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Inscricao (servidor e cidadao). Quem pode se inscrever e decidido pelo
 * middleware da rota e pelas regras do InscricaoService; aqui so se valida o
 * sexo e a data de nascimento, ambos opcionais ("Prefiro nao informar" chega
 * como vazio e vira null).
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
            'data_nascimento' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
        ];
    }

    public function sexo(): ?Sexo
    {
        return Sexo::tryFrom((string) $this->validated('sexo'));
    }

    public function dataNascimento(): ?string
    {
        return $this->validated('data_nascimento') ?: null;
    }
}
