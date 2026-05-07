<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Observers;

use App\Modules\Compdec\Enums\FuncaoEquipe;
use App\Modules\Compdec\Models\CompdecEquipe;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Support\Facades\DB;

/**
 * Mantem compdec_orgaos.qtd_efetivo sincronizado com a contagem de
 * membros ativos (coordenador/agente/tecnico) da equipe.
 *
 * Tambem refresca responsavel_* quando o coordenador ativo muda
 * (cache derivado, leitura rapida no Show sem JOIN).
 */
class CompdecEquipeObserver
{
    public function saved(CompdecEquipe $equipe): void
    {
        $this->recalcularQtdEfetivo($equipe->orgao_id);
        $this->refrescarResponsavelSeMudouCoordenador($equipe);
    }

    public function deleted(CompdecEquipe $equipe): void
    {
        $this->recalcularQtdEfetivo($equipe->orgao_id);
    }

    public function restored(CompdecEquipe $equipe): void
    {
        $this->recalcularQtdEfetivo($equipe->orgao_id);
    }

    private function recalcularQtdEfetivo(int $orgaoId): void
    {
        $qtd = CompdecEquipe::query()
            ->doOrgao($orgaoId)
            ->contaEfetivo()
            ->count();

        DB::table('compdec_orgaos')
            ->where('id', $orgaoId)
            ->update(['qtd_efetivo' => $qtd]);
    }

    private function refrescarResponsavelSeMudouCoordenador(CompdecEquipe $equipe): void
    {
        if ($equipe->funcao !== FuncaoEquipe::COORDENADOR) {
            return;
        }

        $coordenador = CompdecEquipe::query()
            ->doOrgao($equipe->orgao_id)
            ->coordenador()
            ->ativo()
            ->orderBy('ordem')
            ->first();

        if (! $coordenador) {
            return;
        }

        Orgao::query()
            ->where('id', $equipe->orgao_id)
            ->update([
                'responsavel_nome' => $coordenador->nome,
                'responsavel_cpf' => $coordenador->cpf,
                'responsavel_telefone' => $coordenador->celular ?? $coordenador->telefone,
                'responsavel_email' => $coordenador->email,
            ]);
    }
}
