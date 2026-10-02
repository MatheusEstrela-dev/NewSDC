<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mesma permissao de quem salva a estacao: so busca pessoa quem pode cadastrar
 * ou editar. O middleware `can:` aceita uma habilidade so, dai o canAny aqui.
 */
class BuscaUsuarioRequest extends FormRequest
{
    private const PERMISSOES = ['inventario.equipamentos.create', 'inventario.equipamentos.edit'];

    public function authorize(): bool
    {
        return (bool) $this->user()?->canAny(self::PERMISSOES);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function termo(): ?string
    {
        return $this->validated('q');
    }
}
