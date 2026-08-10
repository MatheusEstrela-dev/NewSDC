<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Treinamento\Enums\StatusFrequencia;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Frequencia;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;

class PresencaService
{
    public function __construct(private readonly CertificadoService $certificadoService)
    {
    }

    /**
     * Check-in via leitura do QR Code do ingresso (camera) ou digitacao manual
     * do token. $moduloId identifica a aula/modulo do dia (o SDC registra
     * frequencia por aula, nao 1 unica presenca por evento).
     */
    public function registrarPorQr(string $qrCodeToken, int $moduloId, ?User $registradoPor): Frequencia
    {
        $inscricao = $this->buscarPorToken($qrCodeToken);

        return $this->registrar($inscricao, $moduloId, $registradoPor, 'qr');
    }

    public function registrarManual(Inscricao $inscricao, int $moduloId, ?User $registradoPor): Frequencia
    {
        return $this->registrar($inscricao, $moduloId, $registradoPor, 'manual');
    }

    /**
     * RF07 - registro feito enquanto offline e sincronizado depois. Mesma
     * regra de negocio do check-in por QR normal; so muda a origem gravada
     * (para auditoria/relatorios) e o timestamp, que reflete o momento real
     * da confirmacao em campo, nao o momento em que o servidor recebeu o lote.
     */
    public function registrarOffline(string $qrCodeToken, int $moduloId, ?User $registradoPor, ?string $confirmadoEm = null): Frequencia
    {
        $inscricao = $this->buscarPorToken($qrCodeToken);

        return $this->registrar($inscricao, $moduloId, $registradoPor, 'offline_sync', $confirmadoEm);
    }

    /**
     * Processa um lote de check-ins offline (RF07). Tolerante a falhas
     * individuais - um item invalido nao aborta o restante do lote. A chave
     * natural (modulo_id + inscricao_id + data_aula) ja garante idempotencia:
     * reenviar o mesmo item nunca duplica.
     *
     * @param array<int, array{qr_code_token: string, modulo_id: int, confirmado_em?: string|null}> $itens
     * @return array<int, array{qr_code_token: string, sucesso: bool, mensagem: string}>
     */
    public function sincronizarLote(array $itens, ?User $registradoPor): array
    {
        $resultados = [];

        foreach ($itens as $item) {
            try {
                $this->registrarOffline(
                    (string) ($item['qr_code_token'] ?? ''),
                    (int) ($item['modulo_id'] ?? 0),
                    $registradoPor,
                    $item['confirmado_em'] ?? null
                );

                $resultados[] = [
                    'qr_code_token' => $item['qr_code_token'] ?? null,
                    'sucesso' => true,
                    'mensagem' => 'Sincronizado.',
                ];
            } catch (\DomainException $e) {
                $resultados[] = [
                    'qr_code_token' => $item['qr_code_token'] ?? null,
                    'sucesso' => false,
                    'mensagem' => $e->getMessage(),
                ];
            }
        }

        return $resultados;
    }

    /**
     * Autoconfirmacao (sem QR) - o proprio inscrito confirma a presenca dele
     * mesmo, direto na tela. Sempre permitida para treinamentos ONLINE (nao ha
     * check-in fisico possivel); para PRESENCIAL so quando o curso foi
     * marcado como `presenca_autoconfirmavel` (alguns cursos confiam no
     * proprio inscrito, outros exigem check-in do staff via QR/manual - ver
     * TreinamentoLiberarPresencaController). Marca a aula com data_prevista =
     * hoje, ou a primeira aula cadastrada se nenhuma bater com a data de hoje.
     */
    public function autoconfirmar(Inscricao $inscricao): Frequencia
    {
        $inscricao->loadMissing('treinamento.modulos');

        $treinamento = $inscricao->treinamento;
        $podeAutoconfirmar = $treinamento->tipo === TipoTreinamento::ONLINE || $treinamento->presenca_autoconfirmavel;

        if (!$podeAutoconfirmar) {
            throw new \DomainException('Este treinamento exige confirmacao de presenca pelo staff (QR Code ou manual).');
        }

        $modulo = $inscricao->treinamento->modulos
            ->firstWhere(fn (Modulo $m) => $m->data_prevista?->isToday())
            ?? $inscricao->treinamento->modulos->sortBy('ordem')->first();

        if (!$modulo) {
            throw new \DomainException('Este treinamento ainda nao tem nenhuma aula/modulo cadastrado.');
        }

        return $this->registrar($inscricao, $modulo->id, null, 'autoconfirmacao');
    }

    private function buscarPorToken(string $qrCodeToken): Inscricao
    {
        $inscricao = Inscricao::where('qr_code_token', $qrCodeToken)->first();

        if (!$inscricao) {
            throw new \DomainException('QR Code invalido ou inscricao nao encontrada.');
        }

        return $inscricao;
    }

    private function registrar(
        Inscricao $inscricao,
        int $moduloId,
        ?User $registradoPor,
        string $origem,
        ?string $confirmadoEm = null
    ): Frequencia {
        $inscricao->loadMissing('treinamento');
        $modulo = Modulo::findOrFail($moduloId);

        if ($modulo->treinamento_id !== $inscricao->treinamento_id) {
            throw new \DomainException('Este modulo nao pertence ao treinamento da inscricao.');
        }

        if (!$inscricao->treinamento->presenca_liberada) {
            throw new \DomainException('A presenca ainda nao foi liberada para este treinamento.');
        }

        if (!$inscricao->status->podeRegistrarFrequencia()) {
            throw new \DomainException('Somente inscricoes aprovadas podem ter presenca registrada.');
        }

        $dataAula = $confirmadoEm ? date('Y-m-d', strtotime($confirmadoEm)) : now()->toDateString();

        $frequencia = Frequencia::updateOrCreate(
            ['modulo_id' => $modulo->id, 'inscricao_id' => $inscricao->id, 'data_aula' => $dataAula],
            [
                'status' => StatusFrequencia::PRESENTE->value,
                'origem' => $origem,
                'registrado_por_id' => $registradoPor?->id,
            ]
        );

        $this->certificadoService->emitirSeElegivel($inscricao);

        // Ponto unico da trilha para presenca: qr, manual, offline e lote passam
        // todos por aqui. Fica depois do updateOrCreate porque reenviar o mesmo
        // check-in e idempotente e nao deve render card novo -- e a janela de
        // agrupamento do modulo cobre a chamada de uma turma inteira.
        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $inscricao->treinamento_id,
            AcaoTrilha::Relacionado,
            'presenca registrada',
        );

        return $frequencia;
    }
}
