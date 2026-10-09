<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Pae\DTOs\EmitirCcpaeDTO;
use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Events\CcpaeEmitidoV1;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Domain\Workflows\PaeProtocoloWorkflow;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeCcpae;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\TimelinePae;
use App\Modules\Pae\Support\VigenciaCcpae;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Emissao do CCPAE (Resolucao GMG 83/2024, Arts. 4, 5 e 132-139): registra o
 * certificado, a vigencia de 3 anos e leva o protocolo a CCPAE pelo workflow.
 * Unico caminho para o status CCPAE (guard ExigeEmissaoCcpae).
 */
final class PaeCcpaeService
{
    public function __construct(
        private readonly PaeProtocoloWorkflow $workflow,
        private readonly PaeComunicacaoService $comunicacoes,
        private readonly OutboxDispatcher $outbox,
        private readonly PaeDcoService $dco,
        private readonly PaeSimuladoService $simulado,
    ) {}

    public function emitir(PaeProtocolo $protocolo, EmitirCcpaeDTO $dados, User $user): PaeCcpae
    {
        try {
            $vencimento = VigenciaCcpae::vencimento($dados->dtEmissao, $dados->empreendimentoNovo, $dados->dtLicencaOperacao);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['dt_licenca_operacao' => $e->getMessage()]);
        }

        try {
            return DB::transaction(function () use ($protocolo, $dados, $user, $vencimento): PaeCcpae {
                $protocolo = PaeProtocolo::query()->lockForUpdate()->findOrFail($protocolo->getKey());
                if (in_array($protocolo->status, [PaeProtocoloStatus::CCPAE, PaeProtocoloStatus::ATIVO_3_ANOS], true)
                    || PaeCcpae::query()->where('protocolo_id', $protocolo->getKey())->exists()) {
                    throw ValidationException::withMessages(['ccpae' => 'Este protocolo ja tem CCPAE emitido.']);
                }

                $evidencia = $this->dco->evidenciaParaEmissao($protocolo, $dados->dtEmissao);
                $evidenciaSimulado = $this->simulado->evidenciaParaEmissao($protocolo, $dados->dtEmissao);

                $this->workflow->transitar(
                    $protocolo,
                    PaeProtocoloStatus::CCPAE,
                    $user,
                    "CCPAE {$dados->codigo} emitido.",
                    ContextoTransicao::emissaoCcpae(),
                );

                $ccpae = PaeCcpae::create([
                    'protocolo_id' => $protocolo->id,
                    'codigo' => $dados->codigo,
                    'dt_emissao' => $dados->dtEmissao->toDateString(),
                    'dt_vencimento' => $vencimento->toDateString(),
                    'status' => PaeCcpae::STATUS_ATIVO,
                    'dt_licenca_operacao' => $dados->dtLicencaOperacao?->toDateString(),
                    'emitido_por' => $user->id,
                    'dco_avaliacao_id' => $evidencia['avaliacao']->id,
                    'dco_documento_id' => $evidencia['documento']?->id,
                    'simulado_avaliacao_id' => $evidenciaSimulado['avaliacao']->id,
                    'simulado_relatorio_id' => $evidenciaSimulado['relatorio']?->id,
                ]);

                PaeProtocolo::query()->whereKey($protocolo->getKey())->update([
                    'ccpae' => $dados->codigo,
                    'ccpae_venc' => $vencimento->toDateString(),
                ]);

                $base = $dados->empreendimentoNovo
                    ? "3 anos da Licenca de Operacao de {$dados->dtLicencaOperacao->format('d/m/Y')} (Art. 4)"
                    : '3 anos da emissao (Art. 5)';
                TimelinePae::registrar(
                    $protocolo,
                    'ccpae',
                    "CCPAE {$dados->codigo} emitido em {$dados->dtEmissao->format('d/m/Y')}, vigente ate {$vencimento->format('d/m/Y')}: {$base}.",
                    $user
                );

                $this->comunicacoes->abrir($protocolo, 'ccpae', $ccpae->id);
                $this->outbox->persist(new CcpaeEmitidoV1(
                    eventId: DomainEvent::newId(),
                    aggregateType: 'pae_protocolo',
                    aggregateId: (string) $protocolo->id,
                    occurredAt: new \DateTimeImmutable(),
                    metadata: [
                        'protocolo_id' => $protocolo->id,
                        'ccpae_id' => $ccpae->id,
                        'codigo' => $ccpae->codigo,
                        'actor_user_id' => $user->id,
                    ],
                ));

                return $ccpae;
            });
        } catch (TransicaoProibidaException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
    }
}
