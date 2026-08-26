<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Models\ViaturaMovimentacao;
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

            $condutor = User::findOrFail((int) $dados['condutor_id']);

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
}
