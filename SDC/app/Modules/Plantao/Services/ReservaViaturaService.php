<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use App\Modules\Plantao\Enums\StatusReserva;
use App\Modules\Plantao\Exceptions\ReservaInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Models\ViaturaMovimentacao;
use App\Modules\Plantao\Models\ViaturaReserva;
use App\Modules\Shared\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agenda da frota: quem vai ficar com qual viatura, e quando.
 *
 * A divisao de responsabilidade com MovimentacaoViaturaService e deliberada e
 * nao deve ser desfeita:
 *
 *   reserva      = a intencao  ("vou sair as 14h")
 *   movimentacao = o fato      ("saiu as 14h07 com 123.456 km")
 *
 * O check-in e o check-out daqui NAO escrevem estado de viatura. Eles chamam
 * registrarSaida()/registrarRetorno() e guardam os carimbos da reserva. O
 * MovimentacaoViaturaService continua sendo o unico ponto do sistema que toca
 * hodometro_atual, nivel_combustivel, ultimo_condutor_* e status da viatura --
 * inclusive as guardas de hodometro regressivo e de saida ja aberta, que valem
 * igual para quem chegou pela agenda e para quem chegou pelo formulario.
 */
class ReservaViaturaService extends BaseService
{
    public function __construct(
        private readonly MovimentacaoViaturaService $movimentacaoService
    ) {
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ViaturaReserva::query()
            ->with(['viatura:id,prefixo,placa,modelo', 'agente:id,name'])
            ->orderByDesc('inicio_previsto');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['viatura_id'])) {
            $query->where('viatura_id', (int) $filters['viatura_id']);
        }

        if (!empty($filters['agente_id'])) {
            $query->where('agente_id', (int) $filters['agente_id']);
        }

        if (!empty($filters['de'])) {
            $query->where('fim_previsto', '>=', Carbon::parse($filters['de'])->startOfDay());
        }

        if (!empty($filters['ate'])) {
            $query->where('inicio_previsto', '<=', Carbon::parse($filters['ate'])->endOfDay());
        }

        return $this->paginate($query, $perPage);
    }

    /**
     * Cria a reserva. A trava de conflito roda dentro da transacao com
     * lockForUpdate na viatura -- mesmo motivo de registrarSaida(): sem o lock,
     * dois agentes reservando o mesmo carro no mesmo segundo passam os dois
     * pela consulta de sobreposicao antes de qualquer um gravar.
     */
    public function agendar(array $dados, User $agente): ViaturaReserva
    {
        $inicio = Carbon::parse($dados['inicio_previsto']);
        $fim = Carbon::parse($dados['fim_previsto']);

        $this->validarJanela($inicio, $fim);

        return DB::transaction(function () use ($dados, $agente, $inicio, $fim): ViaturaReserva {
            $viatura = Viatura::query()->lockForUpdate()->findOrFail((int) $dados['viatura_id']);

            if (!$viatura->ativo) {
                throw new ReservaInvalidaException(
                    "Viatura {$viatura->placa} esta inativa e nao aceita reserva."
                );
            }

            // O status atual NAO bloqueia a reserva de proposito: uma viatura em
            // manutencao hoje pode estar liberada na quinta-feira reservada. O
            // que vale no momento da retirada e a guarda podeSair(), aplicada
            // pelo MovimentacaoViaturaService no check-in.

            $this->garantirSemConflito($viatura->id, $inicio, $fim);

            return ViaturaReserva::create([
                'viatura_id' => $viatura->id,
                'agente_id' => $agente->id,
                'agente_nome' => $agente->name,
                'inicio_previsto' => $inicio,
                'fim_previsto' => $fim,
                'status' => StatusReserva::AGENDADA,
                'destino' => $dados['destino'] ?? null,
                'motivo' => $dados['motivo'] ?? null,
            ]);
        });
    }

    /**
     * Cancela a reserva e, quando quem cancelou NAO e o dono, avisa o dono.
     *
     * O aviso e a razao de o cancelamento alheio existir sem virar armadilha:
     * com reserva obrigatoria para retirar a chave, um agente cuja reserva foi
     * derrubada em silencio so descobriria na frente da viatura, com a camera
     * apontada e a chave negada. Cancelamento proprio nao notifica -- avisar
     * alguem do que ele mesmo acabou de fazer ensina a ignorar o sino.
     */
    public function cancelar(int $reservaId, User $ator, ?string $motivo = null): ViaturaReserva
    {
        $reserva = DB::transaction(function () use ($reservaId, $ator, $motivo): ViaturaReserva {
            $reserva = ViaturaReserva::query()->lockForUpdate()->findOrFail($reservaId);

            if (!$reserva->status->podeCancelar()) {
                throw new ReservaInvalidaException(
                    $reserva->status === StatusReserva::EM_USO
                        ? 'A chave ja foi retirada. Registre a devolucao em vez de cancelar.'
                        : "Reserva {$reserva->status->label()} nao pode ser cancelada."
                );
            }

            $reserva->update([
                'status' => StatusReserva::CANCELADA,
                'cancelada_em' => now(),
                'cancelamento_motivo' => $motivo,
                'cancelada_por_id' => $ator->id,
                'cancelada_por_nome' => $ator->name,
            ]);

            return $reserva->fresh(['viatura']);
        });

        // Fora da transacao de proposito: notificacao despachada dentro dela
        // seria enfileirada mesmo se o commit falhasse depois.
        if ($ator->id !== $reserva->agente_id) {
            $this->avisarCancelamento($reserva, $ator, $motivo);
        }

        return $reserva;
    }

    /**
     * Retirada da chave. Abre a movimentacao e marca a reserva como EM_USO.
     *
     * Os dados de saida vem do formulario (hodometro e combustivel sao do
     * momento, nao da reserva); destino e motivo caem para o que foi agendado
     * quando o formulario nao os sobrescreve.
     */
    public function checkin(int $reservaId, array $dadosSaida, User $agente): ViaturaReserva
    {
        return DB::transaction(function () use ($reservaId, $dadosSaida, $agente): ViaturaReserva {
            $reserva = ViaturaReserva::query()->lockForUpdate()->findOrFail($reservaId);

            $this->garantirCheckinPermitido($reserva, $agente);

            // O condutor e sempre o dono da reserva: a chave e nominal. Aceitar
            // condutor_id do request abriria a porta para retirar em nome de
            // outra pessoa, que e exatamente o que a reserva obrigatoria evita.
            // `reserva_id` diz ao servico de movimentacao QUAL reserva esta
            // sendo consumida. A transicao para EM_USO acontece la, e nao aqui,
            // porque o modal da tela de Viaturas tambem abre saida e precisa da
            // mesma amarracao -- um dono por invariante.
            $this->movimentacaoService->registrarSaida($reserva->viatura_id, [
                'condutor_id' => $reserva->agente_id,
                'reserva_id' => $reserva->id,
                'plantao_id' => $dadosSaida['plantao_id'] ?? null,
                'saida_hodometro' => $dadosSaida['saida_hodometro'],
                'saida_combustivel' => $dadosSaida['saida_combustivel'],
                'destino' => $dadosSaida['destino'] ?? $reserva->destino,
                'motivo' => $dadosSaida['motivo'] ?? $reserva->motivo,
            ]);

            return $reserva->fresh(['viatura', 'movimentacao']);
        });
    }

    /**
     * Devolucao da chave. Fecha a movimentacao e conclui a reserva.
     */
    public function checkout(int $reservaId, array $dadosRetorno, User $agente): ViaturaReserva
    {
        return DB::transaction(function () use ($reservaId, $dadosRetorno, $agente): ViaturaReserva {
            $reserva = ViaturaReserva::query()->lockForUpdate()->findOrFail($reservaId);

            if ($reserva->status !== StatusReserva::EM_USO) {
                throw new ReservaInvalidaException(
                    'Esta reserva nao esta com a chave retirada.'
                );
            }

            if ($reserva->movimentacao_id === null) {
                throw new ReservaInvalidaException(
                    'Reserva em uso sem movimentacao associada. Registre a devolucao pela tela de viaturas.'
                );
            }

            $this->garantirMesmoAgente($reserva, $agente, 'devolver');

            // Concluir a reserva NAO acontece aqui: registrarRetorno() faz isso
            // para qualquer caminho que feche a movimentacao, inclusive o modal
            // da tela de Viaturas. Duplicar a escrita daria dois donos para a
            // mesma invariante, e foi por ela morar so aqui que um retorno
            // registrado por fora deixava a reserva EM_USO para sempre.
            $this->movimentacaoService->registrarRetorno($reserva->movimentacao_id, [
                'retorno_hodometro' => $dadosRetorno['retorno_hodometro'],
                'retorno_combustivel' => $dadosRetorno['retorno_combustivel'],
                'alteracoes' => $dadosRetorno['alteracoes'] ?? null,
            ]);

            return $reserva->fresh(['viatura', 'movimentacao']);
        });
    }

    /**
     * A reserva que autoriza este agente a pegar a chave desta viatura agora.
     * Null quando nao existe -- e o caso que o scan traduz em recusa.
     */
    public function reservaVigente(int $viaturaId, int $agenteId, ?Carbon $instante = null): ?ViaturaReserva
    {
        $instante ??= Carbon::now();
        $tolerancia = (int) config('plantao.reservas.tolerancia_checkin_minutos', 30);

        return ViaturaReserva::query()
            ->where('viatura_id', $viaturaId)
            ->where('agente_id', $agenteId)
            ->where('status', StatusReserva::AGENDADA->value)
            ->where('inicio_previsto', '<=', $instante->copy()->addMinutes($tolerancia))
            ->where('fim_previsto', '>=', $instante)
            ->orderBy('inicio_previsto')
            ->first();
    }

    /**
     * Reserva EM_USO ligada a uma movimentacao aberta. E como o scan sabe que o
     * proximo ato daquela viatura e a devolucao.
     */
    public function reservaEmUso(int $viaturaId): ?ViaturaReserva
    {
        return ViaturaReserva::query()
            ->with('agente:id,name')
            ->where('viatura_id', $viaturaId)
            ->where('status', StatusReserva::EM_USO->value)
            ->first();
    }

    private function avisarCancelamento(ViaturaReserva $reserva, User $ator, ?string $motivo): void
    {
        $viatura = $reserva->viatura?->placa ?? 'viatura';

        // Janela inteira, nao so o inicio: "de 12/09 13:15" sozinho parece um
        // intervalo truncado, e quem le precisa saber qual periodo perdeu. Fim
        // no mesmo dia mostra so a hora -- repetir a data ocupa a linha do card
        // sem informar nada.
        $mesmoDia = $reserva->inicio_previsto->isSameDay($reserva->fim_previsto);
        $janela = sprintf(
            '%s a %s',
            $reserva->inicio_previsto->format('d/m H:i'),
            $reserva->fim_previsto->format($mesmoDia ? 'H:i' : 'd/m H:i'),
        );

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'plantao',
                titulo: 'Sua reserva de viatura foi cancelada',
                mensagem: sprintf(
                    '%s cancelou a sua reserva da %s de %s.%s',
                    $ator->name,
                    $viatura,
                    $janela,
                    $motivo === null || $motivo === '' ? '' : ' Motivo: '.$motivo,
                ),
                // urgent, e nao info: sem a reserva a chave nao sai, entao isto
                // muda o que a pessoa vai fazer nas proximas horas.
                tipo: 'urgent',
                // Sem agrupamento: cada cancelamento e de uma reserva especifica
                // e nao deve ser fundido com outro aviso do modulo.
                groupKey: null,
                acaoUrl: '/plantao/reservas',
                acaoTexto: 'Ver reservas',
            ),
            [(int) $reserva->agente_id],
        );
    }

    private function validarJanela(Carbon $inicio, Carbon $fim): void
    {
        if ($fim->lessThanOrEqualTo($inicio)) {
            throw new ReservaInvalidaException('O fim da reserva precisa ser depois do inicio.');
        }

        $maximoHoras = (int) config('plantao.reservas.duracao_maxima_horas', 72);

        if ($inicio->diffInHours($fim) > $maximoHoras) {
            throw new ReservaInvalidaException(
                "Reserva nao pode passar de {$maximoHoras} horas. Para uso prolongado, registre a viatura como cedida."
            );
        }
    }

    /**
     * @throws ReservaInvalidaException quando ja existe reserva ocupando a janela
     */
    private function garantirSemConflito(int $viaturaId, Carbon $inicio, Carbon $fim, ?int $ignorarId = null): void
    {
        $conflito = ViaturaReserva::query()
            ->where('viatura_id', $viaturaId)
            ->ocupandoAgenda()
            ->conflitandoCom($inicio, $fim)
            ->when($ignorarId !== null, fn($q) => $q->whereKeyNot($ignorarId))
            ->orderBy('inicio_previsto')
            ->first();

        if ($conflito === null) {
            return;
        }

        throw new ReservaInvalidaException(sprintf(
            'Ja reservada por %s de %s a %s.',
            $conflito->agente_nome,
            $conflito->inicio_previsto->format('d/m H:i'),
            $conflito->fim_previsto->format('d/m H:i'),
        ));
    }

    private function garantirCheckinPermitido(ViaturaReserva $reserva, User $agente): void
    {
        if ($reserva->status !== StatusReserva::AGENDADA) {
            throw new ReservaInvalidaException(
                $reserva->status === StatusReserva::EM_USO
                    ? 'A chave desta reserva ja foi retirada.'
                    : "Reserva {$reserva->status->label()} nao permite retirada."
            );
        }

        $this->garantirMesmoAgente($reserva, $agente, 'retirar');

        $tolerancia = (int) config('plantao.reservas.tolerancia_checkin_minutos', 30);

        if (!$reserva->vigenteEm(Carbon::now(), $tolerancia)) {
            throw new ReservaInvalidaException(sprintf(
                'Fora da janela reservada (%s a %s). A retirada e liberada a partir de %d minutos antes.',
                $reserva->inicio_previsto->format('d/m H:i'),
                $reserva->fim_previsto->format('d/m H:i'),
                $tolerancia,
            ));
        }
    }

    private function garantirMesmoAgente(ViaturaReserva $reserva, User $agente, string $acao): void
    {
        if ($reserva->agente_id !== $agente->id) {
            throw new ReservaInvalidaException(
                "Esta reserva e de {$reserva->agente_nome}. Somente essa pessoa pode {$acao} a chave."
            );
        }
    }

    /**
     * Movimentacao aberta da viatura, com ou sem reserva associada. Usada pelo
     * scan para distinguir "esta com voce" de "esta com outra pessoa".
     */
    public function movimentacaoAberta(int $viaturaId): ?ViaturaMovimentacao
    {
        return ViaturaMovimentacao::query()
            ->where('viatura_id', $viaturaId)
            ->abertas()
            ->first();
    }
}
