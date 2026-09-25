<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Models\User;

/** So mapeia; nunca cria conta. CPF, depois e-mail. Ambiguidade rejeita. */
final class ImportarUsuarios extends EtapaBase
{
    public function nome(): string { return 'usuarios'; }
    protected function tabelaOrigem(): string { return 'users'; }
    protected function tabelaDestino(): string { return 'users'; }
    protected function temUpdatedAt(): bool { return false; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        $cpf = preg_replace('/\D/', '', (string) ($linha->cpf ?? ''));
        if ($cpf !== '' && strlen($cpf) === 11) {
            $ids = User::query()->whereRaw("regexp_replace(coalesce(cpf, ''), '\\D', '', 'g') = ?", [$cpf])->limit(2)->pluck('id');
            if ($ids->count() === 1) {
                return (int) $ids->first();
            }
            if ($ids->count() > 1) {
                return 'cpf_ambiguo';
            }
        }

        $email = mb_strtolower(trim((string) ($linha->email ?? '')));
        if ($email !== '') {
            $ids = User::query()->whereRaw('lower(email) = ?', [$email])->limit(2)->pluck('id');
            if ($ids->count() === 1) {
                return (int) $ids->first();
            }
            if ($ids->count() > 1) {
                return 'email_ambiguo';
            }
        }

        return 'usuario_sem_correspondencia';
    }
}
