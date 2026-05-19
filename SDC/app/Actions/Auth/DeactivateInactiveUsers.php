<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class DeactivateInactiveUsers
{
    /**
     * Executa a regra de desativação: 6 meses sem update.
     *
     * @return int O número de usuários desativados.
     */
    public function execute(): int
    {
        // Busca usuários ativos que não atualizaram dados há 6 meses
        // O scopeOutdated já contém a lógica de tempo
        $query = User::where('active', true)->outdated();
        
        $count = $query->count();
        
        if ($count > 0) {
            // Realiza o update em massa para performance
            $updated = $query->update([
                'active' => false,
                'status' => 'inactive',
            ]);
            
            Log::info("SDC Auto-Deactivation: $updated usuários foram desativados por inatividade cadastral superior a 6 meses.");
            
            return $updated;
        }

        return 0;
    }
}
