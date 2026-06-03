<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserStatusHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Desativa usuarios com status='pending' cuja janela de 24h para troca da senha
 * provisoria ja expirou. Rodando hourly garante que qualquer conta criada via
 * onboarding e abandonada seja desativada no maximo ~1h apos o vencimento.
 */
class DeactivateExpiredPendingUsers extends Command
{
    protected $signature = 'users:deactivate-expired-pending
                            {--chunk=100 : Tamanho do lote para processamento}
                            {--dry-run : Simula a execucao sem alterar dados}';

    protected $description = 'Desativa contas pending cuja senha provisoria nao foi trocada na janela de 24h';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');
        $reason = 'Senha provisoria nao trocada dentro da janela de 24h apos cadastro';

        $query = User::query()
            ->where('status', 'pending')
            ->whereNotNull('pending_expires_at')
            ->where('pending_expires_at', '<', now());

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('Nenhuma conta pending expirada encontrada.');
            return Command::SUCCESS;
        }

        $this->info("Encontradas {$totalCount} conta(s) pending expirada(s).");

        if ($dryRun) {
            $this->warn('Modo dry-run: nenhuma alteracao sera feita.');
            $this->table(
                ['ID', 'Nome', 'Email', 'Expirado em'],
                $query->limit(50)->get()->map(fn ($u) => [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->pending_expires_at?->format('d/m/Y H:i') ?? '-',
                ])
            );
            return Command::SUCCESS;
        }

        $deactivated = 0;
        $errors = 0;

        $query->chunkById($chunkSize, function ($users) use (&$deactivated, &$errors, $reason) {
            foreach ($users as $user) {
                try {
                    DB::transaction(function () use ($user, $reason, &$deactivated) {
                        $oldStatus = $user->status;

                        $user->update([
                            'status' => 'inactive',
                            'active' => false,
                        ]);

                        UserStatusHistory::create([
                            'user_id' => $user->id,
                            'old_status' => $oldStatus,
                            'new_status' => 'inactive',
                            'reason' => $reason,
                            'changed_by' => null,
                            'ip_address' => null,
                        ]);

                        AuditLog::log(
                            event: AuditLog::EVENT_UPDATE,
                            tableName: 'users',
                            rowId: $user->id,
                            oldValues: ['active' => true, 'status' => $oldStatus],
                            newValues: ['active' => false, 'status' => 'inactive'],
                            userId: null
                        );

                        $deactivated++;
                    });
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("Erro ao desativar usuario {$user->id}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Desativadas {$deactivated} conta(s).");

        if ($errors > 0) {
            $this->warn("Erros: {$errors}");
        }

        return Command::SUCCESS;
    }
}
