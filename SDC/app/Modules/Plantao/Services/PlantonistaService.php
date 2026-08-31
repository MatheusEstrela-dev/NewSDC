<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Plantao\Models\Plantonista;

/**
 * Unico lugar que sabe o que significa "ser plantonista".
 *
 * Existe porque a marcacao passou a ter DOIS pontos de entrada: a tela do
 * proprio modulo (/plantao/plantonistas) e a tela de governanca de usuarios
 * (/admin/permissions/users/{id}/edit). Sem este servico, a regra viveria
 * duplicada, e o modulo de administracao passaria a conhecer a tabela
 * plantao_plantonistas -- acoplamento que nao se desfaz depois.
 *
 * A governanca chama metodos daqui; nunca toca no model.
 */
class PlantonistaService
{
    public function estaMarcado(User $usuario): bool
    {
        return Plantonista::where('user_id', $usuario->id)->exists();
    }

    public function para(User $usuario): ?Plantonista
    {
        return Plantonista::where('user_id', $usuario->id)->first();
    }

    /**
     * Torna o usuario escalavel. Idempotente.
     *
     * Nao sobrescreve posto ja gravado com null: quem edita pela tela de
     * governanca pode nao saber a patente, e apagar a que o modulo ja tinha
     * seria perda silenciosa.
     */
    public function marcar(User $usuario, ?string $posto = null): Plantonista
    {
        $plantonista = Plantonista::firstOrNew(['user_id' => $usuario->id]);

        $posto = $posto === null ? null : trim($posto);

        if ($posto !== null && $posto !== '') {
            $plantonista->posto = $posto;
        }

        // Remarcar alguem inativado volta a deixa-lo escalavel: e o que a acao
        // quer dizer.
        $plantonista->ativo = true;
        $plantonista->save();

        return $plantonista;
    }

    public function atualizar(Plantonista $plantonista, array $dados): Plantonista
    {
        $plantonista->update([
            'posto' => $dados['posto'] ?? null,
            'ativo' => (bool) ($dados['ativo'] ?? $plantonista->ativo),
            'observacao' => $dados['observacao'] ?? null,
        ]);

        return $plantonista;
    }

    /**
     * Tira da lista de escalaveis.
     *
     * NAO apaga historico: as vagas ja escaladas guardam plantonista_id (FK
     * para users) e plantonista_nome espelhado, entao continuam legiveis depois
     * que o cadastro sai daqui.
     */
    public function remover(User $usuario): void
    {
        Plantonista::where('user_id', $usuario->id)->delete();
    }
}
