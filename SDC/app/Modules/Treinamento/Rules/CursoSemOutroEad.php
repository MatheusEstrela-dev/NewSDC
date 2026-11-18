<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Rules;

use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Treinamento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Um curso do catalogo tem um unico EAD por vez; as demais edicoes sao
 * versoes (Nova versao na tela do curso), nunca outro EAD avulso.
 */
final class CursoSemOutroEad implements ValidationRule
{
    /**
     * @param  ?int  $ignorarTreinamentoId  o proprio treinamento, na edicao
     * @param  ?int  $cursoAtual  curso que o treinamento ja tem: mante-lo nunca e colisao (as versoes dividem o curso)
     */
    public function __construct(
        private readonly ?int $ignorarTreinamentoId = null,
        private readonly ?int $cursoAtual = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->cursoAtual !== null && (int) $value === $this->cursoAtual) {
            return;
        }

        $existe = Treinamento::query()
            ->where('curso_id', $value)
            ->where('tipo', TipoTreinamento::EAD->value)
            ->when($this->ignorarTreinamentoId !== null, fn ($q) => $q->where('id', '<>', $this->ignorarTreinamentoId))
            ->exists();

        if ($existe) {
            $fail('Este curso do catalogo ja tem um EAD. Para uma nova edicao, use "Nova versao" na tela do curso.');
        }
    }
}
