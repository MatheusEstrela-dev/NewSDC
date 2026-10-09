<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;

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

        foreach (array_chunk($ids, 1000) as $lote) {
            // remember_token nulo invalida o cookie "lembrar-me" (o guard so
            // aceita token nao vazio) e dispensa um token aleatorio por linha.
            User::withTrashed()->toBase()->whereIn('id', $lote)->update([
                'session_version' => DB::raw('session_version + 1'),
                'remember_token' => null,
            ]);

            // Tokens de API tem abilities fixas: qualquer mudanca de acesso os revoga.
            DB::table('personal_access_tokens')
                ->where('tokenable_type', (new User())->getMorphClass())
                ->whereIn('tokenable_id', $lote)
                ->delete();
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
     * Sessao sem carimbo (anterior ao deploy) vale como versao 1 (default da
     * migration): cai se o usuario ja teve qualquer mudanca de acesso.
     */
    public function matches(Session $session, User $user): bool
    {
        return (int) $session->get(self::SESSION_KEY, 1) === (int) $user->session_version;
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
