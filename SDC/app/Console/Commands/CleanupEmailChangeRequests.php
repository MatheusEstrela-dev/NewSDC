<?php

namespace App\Console\Commands;

use App\Models\EmailChangeRequest;
use Illuminate\Console\Command;

class CleanupEmailChangeRequests extends Command
{
    protected $signature = 'email-change:cleanup-expired
        {--days=30 : Idade minima (em dias) para purgar registros}';

    protected $description = 'Remove pedidos de troca de e-mail concluidos, cancelados ou expirados ha mais de N dias.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = EmailChangeRequest::query()
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($q) use ($cutoff) {
                    $q->whereNotNull('used_at')->where('used_at', '<', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    $q->whereNotNull('cancelled_at')->where('cancelled_at', '<', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    $q->whereNull('used_at')
                      ->whereNull('cancelled_at')
                      ->where('expires_at', '<', $cutoff);
                });
            })
            ->delete();

        $this->info("Removidos {$deleted} pedidos com mais de {$days} dias.");

        return self::SUCCESS;
    }
}
