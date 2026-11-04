<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use App\Services\Auth\SessionVersionService;
use App\Support\Database\ConexaoDeLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Conexoes envolvidas pela transacao de teste (DatabaseTransactions e
     * RefreshDatabase): a default e a de log, sem repetir. Com a chave
     * DB_LOG_CONNECTION desligada as duas sao a mesma e a suite se comporta
     * como antes. Preenchida em setUpTraits(), que roda com a config ja
     * carregada e antes de a transacao abrir.
     *
     * @var list<string>
     */
    protected array $connectionsToTransact = [];

    protected function setUpTraits()
    {
        $this->connectionsToTransact = array_values(array_unique([
            (string) config('database.default'),
            ConexaoDeLog::nome(),
        ]));

        return parent::setUpTraits();
    }

    /**
     * Autentica e carimba a versao de sessao vigente, como o login real faz
     * (SessionVersionService::stampLogin).
     *
     * Desde a revogacao de sessoes em mudanca de acesso, dar cargo ou
     * permissao incrementa users.session_version; sem o carimbo o
     * CheckUserActive tratava a sessao do teste como antiga e a derrubava
     * (401/redirect para o login).
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user instanceof User && $user->exists) {
            $this->withSession([
                SessionVersionService::SESSION_KEY => (int) User::query()->whereKey($user->getKey())->value('session_version'),
            ]);
        }

        return $this;
    }
}
