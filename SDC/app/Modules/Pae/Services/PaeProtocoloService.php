<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeEmpnto;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTramitacao;
use App\Modules\Pae\Models\PaeTimeline;
use App\Modules\Shared\BaseService;
use App\Support\Database\PgCompat;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaeProtocoloService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PaeProtocolo::query()
            ->with(['analistaAtual:id,name', 'empreendimento:id,pae_empdor_id,nome', 'empreendimento.empdor:id,nome'])
            ->ativo();

        if (!empty($filters['restringir_ao_analista'])) {
            $query->where('analista_atual_id', $filters['restringir_ao_analista']);
        }

        $query = $this->applySearch($query, $filters['search'] ?? null, [
            'num_protocolo', 'sigibar', 'sei_numero', 'empnto_search',
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['analista_id'])) {
            $query->where('analista_atual_id', $filters['analista_id']);
        }

        if (!empty($filters['data_inicio'])) {
            $query->where('dt_entrada', '>=', $filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $query->where('dt_entrada', '<=', $filters['data_fim']);
        }

        return $query->orderBy('dt_entrada', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?PaeProtocolo
    {
        return PaeProtocolo::with(['analistaAtual', 'tramitacoes.usuario', 'timeline.usuario'])->find($id);
    }

    public function gerarNumProtocolo(): string
    {
        $ano = now()->format('Y');
        $protocolos = PaeProtocolo::whereYear('created_at', $ano)
            ->pluck('num_protocolo');
            
        $maxSeq = 0;
        foreach ($protocolos as $num) {
            $parts = explode('.', $num);
            if (count($parts) === 4) {
                $seq = (int) $parts[3];
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }
        
        $seqStr = str_pad((string) ($maxSeq + 1), 3, '0', STR_PAD_LEFT);
        return now()->format('d.m.Y') . '.' . $seqStr;
    }

    public function create(array $data, User $user): PaeProtocolo
    {
        $data['num_protocolo'] ??= $this->gerarNumProtocolo();

        $protocolo = PaeProtocolo::create([
            ...$data,
            'status' => PaeProtocoloStatus::NOVO->value,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'dt_entrada' => now()->toDateString(),
        ]);

        if (!empty($data['pae_empnto_id'])) {
            $empnto = PaeEmpnto::with('empdor:id,nome')->find($data['pae_empnto_id']);
            if ($empnto) {
                $protocolo->update([
                    'empnto_search' => trim(($empnto->empdor?->nome ? $empnto->empdor->nome . ' - ' : '') . $empnto->nome),
                ]);
            }
        }

        $this->registrarTimeline($protocolo, 'criacao', 'Protocolo criado no sistema SDC.', $user);

        return $protocolo;
    }

    public function changeStatus(
        PaeProtocolo $protocolo,
        PaeProtocoloStatus $novo,
        User $user,
        string $obs = ''
    ): PaeProtocolo {
        if ($protocolo->status === $novo) {
            return $protocolo->fresh();
        }

        $podeConcluirParaCcpae = $novo === PaeProtocoloStatus::CCPAE
            && in_array($protocolo->status, [
                PaeProtocoloStatus::NOVO,
                PaeProtocoloStatus::ENTRADA_PROCESSO,
                PaeProtocoloStatus::CRIACAO_SDC,
                PaeProtocoloStatus::GERENCIAMENTO,
                PaeProtocoloStatus::NOTIFICACAO,
                PaeProtocoloStatus::ANALISE,
                PaeProtocoloStatus::APROVADO,
                PaeProtocoloStatus::ESPERAR_TRATATIVA,
                PaeProtocoloStatus::DILACAO,
            ], true);

        if (!$protocolo->validarTransicaoStatus($novo) && !$podeConcluirParaCcpae) {
            throw ValidationException::withMessages([
                'status' => "Transição inválida: {$protocolo->status->getLabel()} → {$novo->getLabel()}.",
            ]);
        }

        $statusAnterior = $protocolo->status;

        $protocolo->update([
            'status' => $novo->value,
            'updated_by' => $user->id,
        ]);

        PaeTramitacao::create([
            'protocolo_id' => $protocolo->id,
            'user_id' => $user->id,
            'status' => $novo->value,
            'obs' => $obs ?: null,
        ]);

        $this->registrarTimeline(
            $protocolo,
            'status_alterado',
            "Status alterado de '{$statusAnterior->getLabel()}' para '{$novo->getLabel()}'. {$obs}",
            $user
        );

        return $protocolo->fresh();
    }

    public function atribuir(PaeProtocolo $protocolo, User $analista, User $user): PaeProtocolo
    {
        $analistaJaAtribuido = $protocolo->analista_atual_id === $analista->id;
        $statusAnterior = $protocolo->status;
        $statusJaNotificacao = $statusAnterior === PaeProtocoloStatus::NOTIFICACAO;

        if ($analistaJaAtribuido) {
            return $protocolo;
        }

        $protocolo->update([
            'analista_atual_id' => $analista->id,
            'status' => PaeProtocoloStatus::NOTIFICACAO->value,
            'updated_by' => $user->id,
        ]);

        if (!$statusJaNotificacao) {
            PaeTramitacao::create([
                'protocolo_id' => $protocolo->id,
                'user_id' => $user->id,
                'status' => PaeProtocoloStatus::NOTIFICACAO->value,
                'obs' => "Analista {$analista->name} atribuído.",
            ]);
        }

        $descricao = $analistaJaAtribuido
            ? "Analista reatribuído: {$analista->name}."
            : "Protocolo atribuído ao analista {$analista->name}. Status: {$statusAnterior->getLabel()} → Notificação.";

        $this->registrarTimeline($protocolo, 'atribuicao', $descricao, $user);

        return $protocolo->fresh();
    }

    public function delete(PaeProtocolo $paeProtocolo): void
    {
        $paeProtocolo->delete();
    }

    public function getStatistics(?int $analistaId = null): array
    {
        $base = fn() => $analistaId
            ? PaeProtocolo::ativo()->where('analista_atual_id', $analistaId)
            : PaeProtocolo::ativo();

        return [
            'total' => $base()->count(),
            'novo' => $base()->where('status', PaeProtocoloStatus::NOVO->value)->count(),
            'analise' => $base()->where('status', PaeProtocoloStatus::ANALISE->value)->count(),
            'aprovado' => $base()->where('status', PaeProtocoloStatus::APROVADO->value)->count(),
            'ccpae' => $base()->where('status', PaeProtocoloStatus::CCPAE->value)->count(),
            'ativo_3_anos' => $base()->where('status', PaeProtocoloStatus::ATIVO_3_ANOS->value)->count(),
            'vencidos' => $base()
                ->whereNotNull('limite_analise')
                ->where('limite_analise', '<', now()->toDateString())
                ->whereNotIn('status', [
                    PaeProtocoloStatus::APROVADO->value,
                    PaeProtocoloStatus::CCPAE->value,
                    PaeProtocoloStatus::ATIVO_3_ANOS->value,
                    PaeProtocoloStatus::REPROVADO->value,
                    PaeProtocoloStatus::REVOGADO->value,
                ])
                ->count(),
        ];
    }

    private function registrarTimeline(PaeProtocolo $protocolo, string $evento, string $descricao, User $user): void
    {
        PaeTimeline::create([
            'protocolo_id' => $protocolo->id,
            'evento' => $evento,
            'descricao' => $descricao,
            'user_id' => $user->id,
        ]);
    }
}
