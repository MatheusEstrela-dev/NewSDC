<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\CronoCaminhaoDTO;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Lote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CronoCaminhaoService
{
    public function __construct(
        private readonly CronogramaService $cronogramas,
    ) {}

    /**
     * Colunas QUALIFICADAS: `ultimaVistoriaAprovada` usa latestOfMany, que monta
     * um self-join sobre tdap_vistorias, e `placa_id` cru fica ambiguo entre as
     * duas pontas -- o Postgres recusa a consulta. Mesmo motivo documentado em
     * FrotaService::consultaDaFrota.
     *
     * @var array<int, string>
     */
    private const COLUNAS_DA_VISTORIA = [
        'tdap_vistorias.id', 'tdap_vistorias.placa_id',
        'tdap_vistorias.data', 'tdap_vistorias.parecer',
    ];

    /**
     * Vinculos da listagem.
     *
     * `cronograma` e `caminhao.ultimaVistoriaAprovada` entram porque
     * CronoCaminhaoResource sinaliza a vistoria que vence antes do fim do
     * cronograma; sem eles cada linha dispara duas consultas.
     *
     * @return array<string, mixed>
     */
    private function vinculosDaListagem(): array
    {
        return [
            'cronograma:id,numero,dt_inicio,dt_final,dt_inicio_prorrogacao,dt_final_prorrogacao',
            'caminhao:id,placa,marca,modelo,capacidade_m3,ativo',
            'caminhao.ultimaVistoriaAprovada' => fn ($q) => $q->select(self::COLUNAS_DA_VISTORIA),
        ];
    }

    public function listarDoCronograma(int $cronogramaId): Collection
    {
        return CronoCaminhao::query()
            ->doCronograma($cronogramaId)
            ->with($this->vinculosDaListagem())
            ->withCount(['viagensValidadas', 'viagensPendentes'])
            ->orderBy('ordem')
            ->get();
    }

    public function obter(int $id): CronoCaminhao
    {
        return CronoCaminhao::query()
            ->with($this->vinculosDaListagem())
            ->withCount(['viagensValidadas', 'viagensPendentes'])
            ->findOrFail($id);
    }

    /** Alocacao do cronograma em analise/aprovado e congelada (ver Cronograma::tramitacao_travada). */
    private function garantirEditavel(?Cronograma $cronograma): void
    {
        if ($cronograma?->tramitacao_travada) {
            throw new \DomainException('Cronograma em analise ou aprovado nao aceita alteracao de caminhoes. Devolva-o para a elaboracao no Acompanhamento.');
        }
    }

    /**
     * Trava a linha do cronograma ate o fim da transacao corrente.
     *
     * A tramitacao (TramitacaoCronogramaService::tramitar) faz lockForUpdate no
     * cronograma e confere a contagem de caminhoes. Sem a mesma trava aqui, a
     * checagem de fase de garantirEditavel() corre sobre leitura velha: o INSERT
     * ou DELETE entra depois do COMMIT do envio e a CEDEC analisa/aprova uma
     * frota diferente da que foi congelada (ou o cronograma entra em analise
     * com 0 caminhoes). Chamar SEMPRE dentro de DB::transaction.
     */
    private function travarCronograma(int $cronogramaId): Cronograma
    {
        return Cronograma::query()->lockForUpdate()->findOrFail($cronogramaId);
    }

    public function alocar(CronoCaminhaoDTO $dto): CronoCaminhao
    {
        return DB::transaction(function () use ($dto): CronoCaminhao {
            $cronograma = $this->travarCronograma($dto->cronograma_id);

            if ($cronograma->encerrado_em) {
                throw new \DomainException('Cronograma encerrado nao aceita novos caminhoes.');
            }
            $this->garantirEditavel($cronograma);

            $caminhao = Caminhao::findOrFail($dto->caminhao_id);
            if (! $caminhao->ativo) {
                throw new \DomainException("Caminhao {$caminhao->placa} esta inativo.");
            }

            /*
             * O caminhao tem que ser da empresa contratada no cronograma.
             *
             * Nao havia nada garantindo isso: `tdap_caminhoes.prestador_id` e
             * `tdap_cronogramas.prestador_id` sao independentes, e o
             * StoreCronoCaminhaoRequest so valida `exists`. Dava para alocar
             * caminhao de outra empresa e pagar a errada -- o valor da viagem sai
             * de `lote.valor_m3` do cronograma, nao do cadastro do veiculo.
             *
             * Verificado antes de ligar: 2 alocacoes em 1.276 violavam a regra, e
             * as duas eram o mesmo caso (um caminhao alocado em duplicidade num
             * cronograma de outro prestador). A regra entra sem exigir saneamento
             * em massa -- alocacoes ja gravadas nao sao revalidadas.
             */
            if ($cronograma->prestador_id !== null && $caminhao->prestador_id !== $cronograma->prestador_id) {
                $cronograma->loadMissing('prestador:id,nome');
                $caminhao->loadMissing('prestador:id,nome');

                throw new \DomainException(sprintf(
                    'Caminhao %s pertence a %s, mas o cronograma e de %s. Aloque um caminhao da empresa contratada.',
                    $caminhao->placa,
                    $caminhao->prestador?->nome ?? "prestador id={$caminhao->prestador_id}",
                    $cronograma->prestador?->nome ?? "prestador id={$cronograma->prestador_id}",
                ));
            }

            /*
             * Cronograma ATIVO (locacao de frota): o caminhao entra direto em
             * execucao, sem passar de novo pela ativacao. Sem esta checagem a
             * guarda de vistoria de podeAtivar/prorrogar era contornada
             * alocando depois de ativar. Mesma regra: a vistoria tem de cobrir
             * ate o fim efetivo do cronograma.
             */
            if ($cronograma->ativo) {
                $caminhao->loadMissing('ultimaVistoriaAprovada');
                $erro = $this->cronogramas->motivoVistoriaNaoCobre($caminhao, (string) $caminhao->placa, $cronograma->dt_final_efetiva);
                if ($erro !== null) {
                    throw new \DomainException($erro);
                }
            }

            $jaAlocado = CronoCaminhao::query()
                ->where('cronograma_id', $dto->cronograma_id)
                ->where('caminhao_id', $dto->caminhao_id)
                ->whereNull('deleted_at')
                ->exists();

            if ($jaAlocado) {
                throw new \DomainException("Caminhao {$caminhao->placa} ja alocado neste cronograma.");
            }

            return CronoCaminhao::create([
                ...$dto->toArray(),
                'num_viagens' => $dto->viagensPara((float) $caminhao->capacidade_m3),
            ]);
        });
    }

    public function atualizar(int $id, CronoCaminhaoDTO $dto): CronoCaminhao
    {
        return DB::transaction(function () use ($id, $dto): CronoCaminhao {
            $cc = CronoCaminhao::query()
                ->with(['cronograma:id,ativo,encerrado_em,fase_tramite', 'caminhao:id,capacidade_m3'])
                ->findOrFail($id);

            // Fase/encerramento lidos APOS a trava, nao da leitura acima.
            $cc->setRelation('cronograma', $this->travarCronograma($cc->cronograma_id));

            // Mesmo guard de alocar(): cronograma encerrado e registro fechado.
            // Sem ele era possivel remexer em agua_prevista depois do
            // encerramento e desalinhar o volume contratado do que foi pago.
            if ($cc->cronograma?->encerrado_em) {
                throw new \DomainException('Cronograma encerrado nao aceita alteracao de alocacao.');
            }
            $this->garantirEditavel($cc->cronograma);

            // somente campos editaveis (caminhao nao pode trocar via update; remova e aloque outro)
            $cc->update([
                'comunidade_id' => $dto->comunidade_id,
                'agua_prevista' => $dto->agua_prevista,
                'num_viagens'   => $dto->viagensPara((float) $cc->caminhao?->capacidade_m3),
                'ordem'         => $dto->ordem,
            ]);

            return $cc->fresh();
        });
    }

    public function remover(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $cc = CronoCaminhao::query()->findOrFail($id);

            $cc->setRelation('cronograma', $this->travarCronograma($cc->cronograma_id));
            $this->garantirEditavel($cc->cronograma);

            $cc->loadCount('viagens');
            if ($cc->viagens_count > 0) {
                throw new \DomainException(
                    "Este caminhao tem {$cc->viagens_count} viagem(ns) registrada(s). Remova-as antes."
                );
            }

            return (bool) $cc->delete();
        });
    }

    /**
     * Relacoes que entregaCalculada le. withTrashed: caminhao excluido depois
     * nao zera a agua que ele ja levou (mesma regra do m3 do dashboard).
     *
     * @return array<int|string, mixed>
     */
    public static function relacoesDaEntrega(): array
    {
        return [
            'caminhao' => fn ($q) => $q->withTrashed()->select('id', 'capacidade_m3'),
            'cronograma.lote:id,valor_m3',
        ];
    }

    /**
     * Formula unica da entrega: soma, viagem a viagem APROVADA, da capacidade
     * e do preco do m3 GRAVADOS na aprovacao (CronoViagemService::validar).
     * Usada pelo recalculo a cada validacao e pela correcao em massa
     * (tdap:recalcular-entregas).
     *
     * Viagem aprovada sem valor gravado (anterior a coluna e fora do backfill)
     * cai no cadastro atual do caminhao/lote, que e o que valia para ela.
     *
     * @return array{agua_entregue: float, vr_total: float}
     */
    public function entregaCalculada(CronoCaminhao $cc): array
    {
        $capacidadeAtual = (float) ($cc->caminhao?->capacidade_m3 ?? 0);
        $valorAtual = (float) ($cc->cronograma?->lote?->valor_m3 ?? 0);

        $soma = CronoViagem::query()
            ->where('crono_caminhao_id', $cc->id)
            ->where('validado', CronoViagem::STATUS_APROVADA)
            ->selectRaw('COALESCE(SUM(COALESCE(capacidade_m3, ?)), 0) AS agua', [$capacidadeAtual])
            ->selectRaw('COALESCE(SUM(COALESCE(capacidade_m3, ?) * COALESCE(valor_m3, ?)), 0) AS valor', [$capacidadeAtual, $valorAtual])
            ->toBase()
            ->first();

        return [
            'agua_entregue' => round((float) ($soma->agua ?? 0), 2),
            'vr_total'      => round((float) ($soma->valor ?? 0), 2),
        ];
    }

    /**
     * Recalcula agua_entregue e vr_total com base nas viagens validadas.
     * Chamado pelo CronoViagemService apos validar/rejeitar.
     */
    public function recalcularEntregas(int $cronoCaminhaoId): void
    {
        DB::transaction(function () use ($cronoCaminhaoId): void {
            $cc = CronoCaminhao::query()
                ->with(self::relacoesDaEntrega())
                ->lockForUpdate()
                ->find($cronoCaminhaoId);

            // Sem crono_caminhao correspondente: nada a recalcular.
            if ($cc === null) {
                return;
            }

            // forceFill: agua_entregue e vr_total nao estao em $fillable
            // (sao derivados — somente este Service pode escreve-los).
            $cc->forceFill($this->entregaCalculada($cc))->save();
        });
    }
}
