<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Pos-processamento de login fora do caminho critico da resposta.
 *
 * recordLogin (UPDATE + possivel UserStatusHistory), AuditLog de login e
 * invalidacao do cache do usuario rodam aqui em vez de bloquear o redirect.
 * Como nao ha request no worker, ip e userAgent sao passados explicitamente
 * (recordLogin/AuditLog cairiam em request()->ip() == null na fila).
 *
 * Usa Job (e nao defer() do Swoole) de proposito: o queue worker ja roda no
 * container e o Job funciona em qualquer servidor Octane; defer() sumiria fora
 * do Swoole. Ver plano de migracao Swoole.
 */
class RecordUserLogin implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly ?string $ip,
        private readonly ?string $userAgent,
    ) {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if ($user === null) {
            return;
        }

        $user->recordLogin($this->ip, $this->userAgent);

        AuditLog::create([
            'user_id' => $this->userId,
            'event' => AuditLog::EVENT_LOGIN,
            'table_name' => 'users',
            'row_id' => $this->userId,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $this->ip,
            'user_agent' => $this->userAgent,
            'created_at' => now(),
        ]);

        Cache::forget("inertia_user_data_{$this->userId}");
    }
}
