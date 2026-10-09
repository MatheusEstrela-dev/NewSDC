<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carimbo de seguranca da sessao ("session version").
 *
 * users.session_version sobe a cada mudanca que exige novo login. A sessao
 * guarda a versao vista no login e o middleware CheckUserActive compara a cada
 * requisicao: divergiu, a sessao cai. Unico ponto que escreve a coluna.
 *
 * Sem estado proprio (Octane-safe): a sessao e o request sao resolvidos a cada
 * chamada.
 */
class SessionVersionService
{
    public const SESSION_KEY = 'session_version';

    /**
     * Sobe a versao e rotaciona o remember_token (cookie "lembrar-me" antigo
     * deixa de valer). Usa query crua para nao disparar observers.
     *
     * @param  int|array<int, int>  $userIds
     */
    public function bump(int|array $userIds): void
    {
        $ids = array_values(array_unique((array) $userIds));
        if ($ids === []) {
            return;
        }

        foreach ($ids as $id) {
            User::withTrashed()->toBase()->where('id', $id)->update([
                'session_version' => DB::raw('session_version + 1'),
                'remember_token' => Str::random(60),
            ]);
        }

        $this->restampCurrentSession($ids);
    }

    public function bumpUser(User $user): void
    {
        $this->bump($user->getKey());

        $user->setAttribute('session_version', $this->currentVersion($user->getKey()));
        $user->syncOriginalAttribute('session_version');
    }

    /**
     * Grava no login a versao vigente no banco (nao a do model em memoria, que
     * pode ter sido carregado antes de uma troca de senha no mesmo request).
     */
    public function stampLogin(Session $session, User $user): void
    {
        $session->put(self::SESSION_KEY, $this->currentVersion($user->getKey()));
    }

    /**
     * Sessao sem carimbo (anterior ao deploy) adota a versao vigente.
     */
    public function matches(Session $session, User $user): bool
    {
        if (!$session->has(self::SESSION_KEY)) {
            $session->put(self::SESSION_KEY, (int) $user->session_version);

            return true;
        }

        return (int) $session->get(self::SESSION_KEY) === (int) $user->session_version;
    }

    /**
     * Quem muda os proprios dados (ou o cargo que ele mesmo tem) continua
     * logado nesta sessao; as demais caem.
     *
     * @param  array<int, int>  $ids
     */
    private function restampCurrentSession(array $ids): void
    {
        $request = request();
        if (!$request->hasSession()) {
            return;
        }

        $currentId = auth('web')->id();
        if ($currentId === null || !in_array((int) $currentId, array_map('intval', $ids), true)) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, $this->currentVersion((int) $currentId));
    }

    private function currentVersion(int $userId): int
    {
        return (int) User::withTrashed()->toBase()->where('id', $userId)->value('session_version');
    }
}
