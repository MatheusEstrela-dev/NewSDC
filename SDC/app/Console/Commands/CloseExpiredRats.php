<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseExpiredRats extends Command
{
    protected $signature   = 'rat:close-expired';
    protected $description = 'Finaliza automaticamente RATs cujo prazo de edição (48h) expirou';

    public function handle(): int
    {
        $expired = RatOcorrencia::where('status', 0)
            ->whereNotNull('prazo_edicao')
            ->where('prazo_edicao', '<=', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Nenhum RAT expirado encontrado.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($expired as $rat) {
            $rat->update(['status' => 1]);
            $count++;
            Log::info('[RAT] Protocolo fechado automaticamente por prazo', [
                'id'          => $rat->id,
                'numero_bos'  => $rat->numero_bos,
                'prazo_edicao' => $rat->prazo_edicao?->toISOString(),
            ]);
        }

        $this->info("$count RAT(s) fechado(s) automaticamente por prazo expirado.");
        return self::SUCCESS;
    }
}
