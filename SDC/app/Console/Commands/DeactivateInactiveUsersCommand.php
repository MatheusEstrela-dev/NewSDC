<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\UserStatusHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeactivateInactiveUsersCommand extends Command
{
    protected $signature = 'users:deactivate-inactive
                            {--months=6 : Meses de inatividade para desativar}
                            {--chunk=100 : Tamanho do lote para processamento}
                            {--dry-run : Simula a execucao sem alterar dados}';

    protected $description = 'Desativa usuarios que nao fizeram login ha mais de X meses';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        $query = User::query()
            ->where('active', true)
            ->where('status', '!=', 'inactive')
            ->where(function ($q) use ($months) {
                $q->whereNull('last_login_at')
                    ->orWhere('last_login_at', '<', now()->subMonths($months));
            })
            ->where('created_at', '<', now()->subMonths($months));

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('Nenhum usuario inativo encontrado.');
            return Command::SUCCESS;
        }

        $this->info("Encontrados {$totalCount} usuarios inativos.");

        if ($dryRun) {
            $this->warn('Modo dry-run: nenhuma alteracao sera feita.');
            $this->table(
                ['ID', 'Nome', 'Email', 'Ultimo Login', 'Criado em'],
                $query->limit(50)->get()->map(fn ($u) => [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->last_login_at?->format('d/m/Y H:i') ?? 'Nunca',
                    $u->created_at->format('d/m/Y')
                ])
            );
            if ($totalCount > 50) {
                $this->warn("... e mais " . ($totalCount - 50) . " usuarios.");
            }
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $deactivatedCount = 0;
        $errorCount = 0;
        $reason = 'Desativacao automatica: inatividade superior a ' . $months . ' meses';

        $query->chunkById($chunkSize, function ($users) use (&$deactivatedCount, &$errorCount, $bar, $reason) {
            foreach ($users as $user) {
                try {
                    DB::transaction(function () use ($user, $reason, &$deactivatedCount) {
                        $oldStatus = $user->status;

                        $user->update([
                            'active' => false,
                            'status' => 'inactive',
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

                        $deactivatedCount++;
                    });
                } catch (\Throwable $e) {
                    $errorCount++;
                    $this->error("Erro ao desativar usuario {$user->id}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Desativados {$deactivatedCount} usuarios.");

        if ($errorCount > 0) {
            $this->warn("Erros encontrados: {$errorCount}");
        }

        return Command::SUCCESS;
    }
}
