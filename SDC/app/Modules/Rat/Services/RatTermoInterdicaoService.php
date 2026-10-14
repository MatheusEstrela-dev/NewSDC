<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Models\User;
use App\Modules\Rat\DTOs\RatTermoInterdicaoDTO;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\RatTermoInterdicao;
use App\Modules\Rat\Models\Relatos\RatRelatoVistoria;
use Illuminate\Support\Facades\DB;

/**
 * Termo de interdicao do RAT (#57).
 *
 * - RAT original (-001): o COMPDEC registra o termo (permissao
 *   `rat.vistoria.interditar`); para ele o bloco ja abre expandido.
 * - RAT relacionado (-002, -003...): o agente com registro no CREA
 *   (`rat.vistoria.crea`) recebe o termo da origem como sugestao e emite o
 *   seu, com o numero do registro.
 * - Vistoria com interdicao parcial/total marcada so finaliza com termo
 *   emitido.
 */
class RatTermoInterdicaoService
{
    public const PERMISSAO_ORIGINAL = 'rat.vistoria.interditar';
    public const PERMISSAO_CREA     = 'rat.vistoria.crea';

    /** RAT relacionado: tem origem (numero -002 em diante). */
    public function ehRelacionado(RatOcorrencia $ocorrencia): bool
    {
        return $ocorrencia->ocorrencia_origem_id !== null;
    }

    public function permissaoExigida(RatOcorrencia $ocorrencia): string
    {
        return $this->ehRelacionado($ocorrencia) ? self::PERMISSAO_CREA : self::PERMISSAO_ORIGINAL;
    }

    public function termoDa(RatOcorrencia $ocorrencia): ?RatTermoInterdicao
    {
        return RatTermoInterdicao::where('ocorrencia_id', $ocorrencia->id)->first();
    }

    public function interdicaoMarcada(string $ocorrenciaId): bool
    {
        $vistoria = RatRelatoVistoria::where('ocorrencia_id', $ocorrenciaId)
            ->first(['v_enc_interdicao_parcial', 'v_enc_interdicao_total']);

        return (bool) ($vistoria?->v_enc_interdicao_parcial || $vistoria?->v_enc_interdicao_total);
    }

    /** Props do bloco na tela de edicao. */
    public function contexto(RatOcorrencia $ocorrencia, ?User $user): array
    {
        $termo = $this->termoDa($ocorrencia);
        $relacionado = $this->ehRelacionado($ocorrencia);
        $sugestao = null;

        if ($termo === null && $relacionado) {
            $origem = RatTermoInterdicao::where('ocorrencia_id', $ocorrencia->ocorrencia_origem_id)->first();
            $sugestao = $origem ? $this->paraArray($origem) : null;
        }

        $podeEditar = $user !== null
            && $user->can($this->permissaoExigida($ocorrencia))
            && (int) $ocorrencia->status !== 1
            && ! $termo?->emitido();

        return [
            'termo'               => $termo ? $this->paraArray($termo) : null,
            'sugestao'            => $sugestao,
            'relacionado'         => $relacionado,
            'exige_crea'          => $relacionado,
            'interdicao_marcada'  => $this->interdicaoMarcada((string) $ocorrencia->id),
            'pode_editar'         => $podeEditar,
            'abrir_por_padrao'    => $termo !== null
                || (! $relacionado && $user?->hasRole('compdec'))
                || ($relacionado && $sugestao !== null && $podeEditar),
        ];
    }

    /** Cria ou atualiza o rascunho. */
    public function salvar(RatOcorrencia $ocorrencia, RatTermoInterdicaoDTO $dto, User $user): RatTermoInterdicao
    {
        $this->autorizar($ocorrencia, $user);

        return DB::transaction(function () use ($ocorrencia, $dto, $user): RatTermoInterdicao {
            $termo = RatTermoInterdicao::where('ocorrencia_id', $ocorrencia->id)->lockForUpdate()->first();
            abort_if($termo?->emitido(), 422, 'O termo de interdição já foi emitido e não pode ser alterado.');

            $dados = $dto->toArray();

            if ($termo === null) {
                $origem = $this->ehRelacionado($ocorrencia)
                    ? RatTermoInterdicao::where('ocorrencia_id', $ocorrencia->ocorrencia_origem_id)->first()
                    : null;

                // O relacionado parte do termo da origem: o que nao veio na
                // requisicao e herdado; o registro CREA e sempre do novo agente.
                $herdado = $origem
                    ? array_intersect_key($origem->only($origem->getFillable()), array_flip([
                        'tipo', 'area_interditada', 'fundamentacao', 'medidas', 'prazo_dias',
                    ]))
                    : [];

                $termo = new RatTermoInterdicao(array_merge($herdado, $dados, [
                    'ocorrencia_id'   => $ocorrencia->id,
                    'origem_termo_id' => $origem?->id,
                    'status'          => RatTermoInterdicao::RASCUNHO,
                    'created_by'      => $user->id,
                ]));
                abort_if(blank($termo->tipo), 422, 'Informe o tipo de interdição (parcial ou total).');
            } else {
                $termo->fill($dados);
            }

            $termo->updated_by = $user->id;
            $termo->save();

            return $termo->fresh();
        });
    }

    public function emitir(RatOcorrencia $ocorrencia, User $user): RatTermoInterdicao
    {
        $this->autorizar($ocorrencia, $user);

        return DB::transaction(function () use ($ocorrencia, $user): RatTermoInterdicao {
            $termo = RatTermoInterdicao::where('ocorrencia_id', $ocorrencia->id)->lockForUpdate()->first();
            abort_if($termo === null, 422, 'Salve o termo de interdição antes de emitir.');
            abort_if($termo->emitido(), 422, 'O termo de interdição já foi emitido.');
            abort_if(blank($termo->area_interditada) || blank($termo->fundamentacao), 422,
                'Informe a área interditada e a fundamentação antes de emitir.');
            abort_if($this->ehRelacionado($ocorrencia) && blank($termo->registro_crea), 422,
                'Informe o número do registro no CREA para emitir o termo.');

            $termo->update([
                'status'      => RatTermoInterdicao::EMITIDO,
                'emitido_por' => $user->id,
                'emitido_em'  => now(),
                'updated_by'  => $user->id,
            ]);

            return $termo->fresh();
        });
    }

    /** Barra a finalizacao de RAT com interdicao marcada e sem termo emitido. */
    public function exigirParaFinalizar(string $ocorrenciaId): void
    {
        if (! $this->interdicaoMarcada($ocorrenciaId)) {
            return;
        }

        $emitido = RatTermoInterdicao::where('ocorrencia_id', $ocorrenciaId)
            ->where('status', RatTermoInterdicao::EMITIDO)
            ->exists();

        abort_unless($emitido, 422, 'A vistoria indica interdição: emita o termo de interdição antes de finalizar o RAT.');
    }

    public function paraArray(RatTermoInterdicao $termo): array
    {
        return [
            'id'               => $termo->id,
            'origem_termo_id'  => $termo->origem_termo_id,
            'tipo'             => $termo->tipo,
            'area_interditada' => $termo->area_interditada,
            'fundamentacao'    => $termo->fundamentacao,
            'medidas'          => $termo->medidas,
            'prazo_dias'       => $termo->prazo_dias,
            'responsavel_nome' => $termo->responsavel_nome,
            'registro_crea'    => $termo->registro_crea,
            'status'           => $termo->status,
            'emitido_por'      => $termo->emissor?->name,
            'emitido_em'       => $termo->emitido_em?->toIso8601String(),
        ];
    }

    private function autorizar(RatOcorrencia $ocorrencia, User $user): void
    {
        abort_if((int) $ocorrencia->status === 1, 422, 'RAT finalizado: o termo de interdição não pode mais ser alterado.');
        abort_unless($user->can($this->permissaoExigida($ocorrencia)), 403, $this->ehRelacionado($ocorrencia)
            ? 'Somente agente com registro no CREA pode emitir o termo do RAT relacionado.'
            : 'Você não tem permissão para registrar termo de interdição.');
    }
}
