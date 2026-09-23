<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\SlaDefinicao;
use App\Modules\Demandas\Models\SlaInstancia;
use Carbon\Carbon;

class SlaEngine
{
    public function iniciarSlaParaDemanda(Demanda $demanda): void
    {
        $definicao = $this->encontrarDefinicao($demanda);
        
        if (!$definicao) {
            return;
        }

        $instancia = new SlaInstancia([
            'task_id' => $demanda->id,
            'sla_definition_id' => $definicao->id,
            'primeira_resposta_inicio' => now(),
            'resolucao_inicio' => now(),
        ]);
        
        // Calcula prazos (simplificado para V1, deve integrar calendário de dias úteis)
        $instancia->primeira_resposta_prazo = $this->calcularPrazo($definicao, now(), $definicao->tempo_primeira_resposta ?? 24);
        $instancia->resolucao_prazo = $this->calcularPrazo($definicao, now(), $definicao->tempo_resolucao ?? 72);
        
        $instancia->save();
    }

    public function verificarViolacoes(): void
    {
        // Marca SLAs de Resolução vencidos
        SlaInstancia::query()
            ->whereNull('resolucao_atingido')
            ->where('resolucao_prazo', '<', now())
            ->where('resolucao_violado', false)
            ->update(['resolucao_violado' => true]);
            
        // Marca SLAs de Primeira Resposta vencidos
        SlaInstancia::query()
            ->whereNull('primeira_resposta_atingido')
            ->where('primeira_resposta_prazo', '<', now())
            ->where('primeira_resposta_violado', false)
            ->update(['primeira_resposta_violado' => true]);
    }
    
    private function encontrarDefinicao(Demanda $demanda): ?SlaDefinicao
    {
        return SlaDefinicao::where('ativo', true)
            ->where(function($q) use ($demanda) {
                $q->where('prioridade', $demanda->prioridade?->value)
                  ->orWhereNull('prioridade');
            })
            ->first();
    }
    
    private function calcularPrazo(SlaDefinicao $definicao, Carbon $inicio, int $horasSla): Carbon
    {
        // TODO: Substituir por biblioteca de Business Days (ex: carbon-business-days)
        return $inicio->copy()->addHours($horasSla);
    }
}
