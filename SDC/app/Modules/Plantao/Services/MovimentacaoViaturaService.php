<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use App\Modules\Plantao\Enums\StatusReserva;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Models\ViaturaMovimentacao;
use App\Modules\Plantao\Models\ViaturaReserva;
use App\Modules\Shared\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * Unico ponto do sistema autorizado a escrever o estado corrente da viatura
 * (hodometro_atual, nivel_combustivel, ultimo_condutor_id, ultimo_condutor_nome
 * e status). Nenhum controller, request ou outro service toca esses campos.
 */
class MovimentacaoViaturaService extends BaseService
{
    public function registrarSaida(int $viaturaId, array $dados): ViaturaMovimentacao
    {
        return DB::transaction(function () use ($viaturaId, $dados): ViaturaMovimentacao {
            // lockForUpdate evita duas saidas simultaneas passando pela guarda.
            $viatura = Viatura::query()->lockForUpdate()->findOrFail($viaturaId);

            if (!$viatura->status->podeSair()) {
                throw new MovimentacaoInvalidaException(
                    "Viatura {$viatura->placa} esta em {$viatura->status->label()} e nao pode sair."
                );
            }

            if ($viatura->movimentacoes()->abertas()->exists()) {
                throw new MovimentacaoInvalidaException(
                    "Viatura {$viatura->placa} ja possui uma saida em aberto."
                );
            }

            $hodometroSaida = (int) $dados['saida_hodometro'];

            if ($viatura->hodometro_atual !== null && $hodometroSaida < $viatura->hodometro_atual) {
                throw new MovimentacaoInvalidaException(
                    "Hodometro de saida ({$hodometroSaida}) e menor que o registrado na viatura ({$viatura->hodometro_atual})."
                );
            }

            $condutor = User::find((int) $dados['condutor_id']);

            if ($condutor === null) {
                throw new MovimentacaoInvalidaException(
                    "Condutor {$dados['condutor_id']} nao foi encontrado."
                );
            }

            $this->garantirAgendaLivre($viatura, $condutor);

            $movimentacao = ViaturaMovimentacao::create([
                'viatura_id' => $viatura->id,
                'plantao_id' => $dados['plantao_id'] ?? null,
                'condutor_id' => $condutor->id,
                'condutor_nome' => $condutor->name,
                'saida_em' => $dados['saida_em'] ?? now(),
                'saida_hodometro' => $hodometroSaida,
                'saida_combustivel' => $dados['saida_combustivel'],
                'destino' => $dados['destino'] ?? null,
                'motivo' => $dados['motivo'] ?? null,
                'status' => StatusMovimentacao::EM_TRANSITO,
            ]);

            $this->sincronizarEstado($viatura, [
                'status' => StatusViatura::EM_TRANSITO,
                'hodometro_atual' => $hodometroSaida,
                'nivel_combustivel' => NivelCombustivel::from($dados['saida_combustivel']),
                'ultimo_condutor_id' => $condutor->id,
                'ultimo_condutor_nome' => $condutor->name,
            ]);

            $this->consumirReserva($movimentacao, $dados['reserva_id'] ?? null);

            return $movimentacao;
        });
    }

    public function registrarRetorno(int $movimentacaoId, array $dados): ViaturaMovimentacao
    {
        return DB::transaction(function () use ($movimentacaoId, $dados): ViaturaMovimentacao {
            $movimentacao = ViaturaMovimentacao::query()
                ->lockForUpdate()
                ->findOrFail($movimentacaoId);

            if ($movimentacao->status !== StatusMovimentacao::EM_TRANSITO) {
                throw new MovimentacaoInvalidaException(
                    'Esta movimentacao ja foi encerrada.'
                );
            }

            $hodometroRetorno = (int) $dados['retorno_hodometro'];

            if ($hodometroRetorno < $movimentacao->saida_hodometro) {
                throw new MovimentacaoInvalidaException(
                    "Hodometro de retorno ({$hodometroRetorno}) e menor que o de saida ({$movimentacao->saida_hodometro})."
                );
            }

            $movimentacao->update([
                'retorno_em' => $dados['retorno_em'] ?? now(),
                'retorno_hodometro' => $hodometroRetorno,
                'retorno_combustivel' => $dados['retorno_combustivel'],
                'alteracoes' => $dados['alteracoes'] ?? null,
                'status' => StatusMovimentacao::RETORNADA,
            ]);

            $this->concluirReservaAssociada($movimentacao);

            $viatura = Viatura::query()->lockForUpdate()->findOrFail($movimentacao->viatura_id);

            $this->sincronizarEstado($viatura, [
                'status' => StatusViatura::DISPONIVEL,
                'hodometro_atual' => $hodometroRetorno,
                'nivel_combustivel' => NivelCombustivel::from($dados['retorno_combustivel']),
                'ultimo_condutor_id' => $movimentacao->condutor_id,
                'ultimo_condutor_nome' => $movimentacao->condutor_nome,
            ]);

            return $movimentacao->fresh();
        });
    }

    /**
     * Escreve o cache de estado da viatura. Metodo privado de proposito: e a
     * fronteira que garante uma unica fonte de verdade.
     */
    private function sincronizarEstado(Viatura $viatura, array $estado): void
    {
        $viatura->update($estado);
    }

    /**
     * Viatura reservada nao sai com outra pessoa.
     *
     * A guarda vive AQUI pelo mesmo motivo de concluirReservaAssociada(): este
     * e o unico ponto que abre movimentacao, e chegam nele o scanner da chave,
     * o modal da tela de Viaturas e o console. Enquanto a reserva era exigida
     * apenas no scanner, o modal continuava sendo um segundo caminho sem
     * tranca -- dava para retirar uma viatura reservada e furar a reserva de
     * quem chegasse no horario, que e exatamente o que a agenda existe para
     * impedir.
     *
     * O dono da reserva passa: a reserva e dele, e barra-lo seria a agenda
     * bloqueando o proprio beneficiario. Para liberar a viatura a terceiros o
     * caminho e cancelar a reserva -- ato explicito, com autor registrado.
     *
     * @throws MovimentacaoInvalidaException quando ha reserva de outra pessoa
     */
    private function garantirAgendaLivre(Viatura $viatura, User $condutor): void
    {
        $reserva = ViaturaReserva::query()
            ->where('viatura_id', $viatura->id)
            ->where('status', StatusReserva::AGENDADA->value)
            ->where('agente_id', '!=', $condutor->id)
            ->orderBy('inicio_previsto')
            ->first();

        if ($reserva === null) {
            return;
        }

        throw new MovimentacaoInvalidaException(sprintf(
            'Viatura %s esta reservada por %s de %s a %s. Cancele a reserva para liberar a viatura.',
            $viatura->placa,
            $reserva->agente_nome,
            $reserva->inicio_previsto->format('d/m H:i'),
            $reserva->fim_previsto->format('d/m H:i'),
        ));
    }

    /**
     * Amarra a saida a reserva que a autorizava, quando existe.
     *
     * Sem isto, o dono que retira a propria viatura pelo modal da tela de
     * Viaturas deixaria a reserva AGENDADA -- que depois EXPIRARIA com a chave
     * na mao dele, e o scanner passaria a ver duas verdades diferentes sobre a
     * mesma viatura. `reserva_id` vem preenchido quando quem chama e o check-in
     * do scanner, que ja sabe qual reserva validou; nos outros caminhos a
     * reserva vigente do condutor e descoberta aqui.
     */
    private function consumirReserva(ViaturaMovimentacao $movimentacao, ?int $reservaId): void
    {
        $query = ViaturaReserva::query()
            ->where('status', StatusReserva::AGENDADA->value);

        if ($reservaId !== null) {
            $query->whereKey($reservaId);
        } else {
            $tolerancia = (int) config('plantao.reservas.tolerancia_checkin_minutos', 30);

            $query
                ->where('viatura_id', $movimentacao->viatura_id)
                ->where('agente_id', $movimentacao->condutor_id)
                ->where('inicio_previsto', '<=', now()->addMinutes($tolerancia))
                ->where('fim_previsto', '>=', now())
                ->orderBy('inicio_previsto')
                ->limit(1);
        }

        // Duas reservas do mesmo agente para a mesma viatura nao se sobrepoem
        // (garantia de garantirSemConflito), entao o recorte acima acha no
        // maximo uma. UPDATE condicional: idempotente e sem eventos Eloquent.
        $alvo = $query->value('id');

        if ($alvo === null) {
            return;
        }

        ViaturaReserva::query()
            ->whereKey($alvo)
            ->where('status', StatusReserva::AGENDADA->value)
            ->update([
                'status' => StatusReserva::EM_USO->value,
                'movimentacao_id' => $movimentacao->id,
                'checkin_em' => $movimentacao->saida_em ?? now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Fechar a movimentacao conclui a reserva que a originou, se houver.
     *
     * A invariante vive AQUI, e nao no ReservaViaturaService, porque este e o
     * unico ponto que encerra movimentacao -- e sao varios os caminhos que
     * chegam nele: o scanner da chave, o modal da tela de Viaturas e o console.
     * Enquanto a regra morava so no check-out do scanner, um retorno registrado
     * pela tela de Viaturas deixava a reserva EM_USO para sempre, e o estrago
     * era duplo: a agenda da viatura ficava travada (EM_USO ocupa horario e nao
     * pode ser cancelada) e o scanner passava a recusar a chave dizendo que nao
     * havia reserva vigente -- porque nao ha movimentacao aberta, e a busca de
     * reserva vigente so considera AGENDADA.
     *
     * UPDATE condicional e pelo query builder: idempotente quando o check-out
     * do scanner ja concluiu a reserva, e sem disparar eventos do Eloquent,
     * porque concluir a reserva e consequencia do retorno e nao um ato proprio
     * a ser registrado na trilha.
     */
    private function concluirReservaAssociada(ViaturaMovimentacao $movimentacao): void
    {
        ViaturaReserva::query()
            ->where('movimentacao_id', $movimentacao->id)
            ->where('status', StatusReserva::EM_USO->value)
            ->update([
                'status' => StatusReserva::CONCLUIDA->value,
                'checkout_em' => $movimentacao->retorno_em ?? now(),
                'updated_at' => now(),
            ]);
    }
}
