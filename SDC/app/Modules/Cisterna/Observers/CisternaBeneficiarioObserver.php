<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Observers;

use App\Models\AuditLog;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Registra em `audit_logs` a entrada e a saida de beneficiario numa ordem de
 * servico. E o que alimenta o OrdemServicoService::timeline().
 *
 * So grava quando `ordem_servico_id` de fato mudou: alterar nome ou telefone do
 * beneficiario nao e movimentacao de lote e nao deve poluir o historico.
 *
 * ATENCAO: observer nao dispara em `->update()` de query builder. As acoes em
 * massa do BeneficiarioService gravam o log por conta propria, justamente
 * porque alocar em lote e o caminho principal de uso e passaria batido aqui.
 */
class CisternaBeneficiarioObserver
{
    public function updated(CisternaBeneficiario $beneficiario): void
    {
        if (! $beneficiario->wasChanged('ordem_servico_id')) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            // 'update', nao 'updated': audit_logs tem CHECK que aceita apenas
            // insert|update|delete|login|logout. O particípio do evento do
            // Eloquent nao passa.
            'event' => 'update',
            'table_name' => $beneficiario->getTable(),
            'row_id' => $beneficiario->getKey(),
            'old_values' => ['ordem_servico_id' => $beneficiario->getOriginal('ordem_servico_id')],
            'new_values' => ['ordem_servico_id' => $beneficiario->ordem_servico_id],
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
