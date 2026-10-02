<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Queries;

use App\Models\User;
use App\Modules\Shared\Support\PadraoBusca;

/**
 * Busca sob demanda de usuarios por nome para os campos de escolha de pessoa.
 * Substitui o envio da lista inteira (900+ usuarios) junto com a pagina.
 * So contas ativas.
 */
final class BuscaUsuarioQuery
{
    public const MINIMO_CARACTERES = 2;

    public const LIMITE = 20;

    /** @return list<array{id:int,name:string}> */
    public function porNome(?string $termo): array
    {
        $termo = trim((string) $termo);
        if (mb_strlen($termo) < self::MINIMO_CARACTERES) {
            return [];
        }

        return User::query()
            ->where('name', 'ilike', PadraoBusca::contem($termo))
            // Conta desativada nao ocupa estacao nem recebe equipamento.
            ->where('active', true)
            ->orderBy('name')->orderBy('id')
            ->limit(self::LIMITE)
            ->get(['id', 'name'])
            ->map(static fn (User $u): array => ['id' => (int) $u->id, 'name' => (string) $u->name])
            ->all();
    }
}
