<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Observers;

use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Support\Facades\DB;

/**
 * Mantem compdec_orgaos.tem_plano_contingencia sincronizado com a
 * existencia de pelo menos 1 plano ativo para o orgao.
 *
 * Tambem garante que ativar um plano desative os demais — o partial
 * unique index do banco e a salvaguarda final, este observer e o
 * fallback para casos de mass-update direto que poderiam violar.
 */
class CompdecPlanoContingenciaObserver
{
    public function saved(CompdecPlanoContingencia $plano): void
    {
        $this->recalcularTemPlanoContingencia($plano->orgao_id);
    }

    public function deleted(CompdecPlanoContingencia $plano): void
    {
        $this->recalcularTemPlanoContingencia($plano->orgao_id);
    }

    public function restored(CompdecPlanoContingencia $plano): void
    {
        $this->recalcularTemPlanoContingencia($plano->orgao_id);
    }

    private function recalcularTemPlanoContingencia(int $orgaoId): void
    {
        $temAtivo = CompdecPlanoContingencia::query()
            ->doOrgao($orgaoId)
            ->ativo()
            ->exists();

        Orgao::query()
            ->where('id', $orgaoId)
            ->update(['tem_plano_contingencia' => $temAtivo]);
    }
}
