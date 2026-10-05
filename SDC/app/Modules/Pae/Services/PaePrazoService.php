<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Enums\SituacaoPrazo;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\Datas;
use App\Modules\Pae\Support\PrazoAnalise;
use App\Modules\Pae\Support\PrazoProtocolo;
use App\Modules\Pae\Support\TimelinePae;
use App\Support\Calendario\CalendarioDiasUteis;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Grava e expoe os prazos legais do protocolo (Resolucao GMG 83/2024, Arts. 7 e 9).
 *
 * O calculo e das classes puras em Support/; aqui so se busca o que elas
 * precisam e se grava limite_analise. Chamado quando algo muda o prazo: data
 * da FEAM, notificacao emitida, devolutiva, dilacao e o comando diario.
 */
final class PaePrazoService
{
    public function __construct(
        private readonly CalendarioDiasUteis $calendario,
    ) {}

    public function recalcular(PaeProtocolo $protocolo): PaeProtocolo
    {
        $limite = PrazoAnalise::limite($protocolo->dt_notificacao_feam, $this->intervalos($protocolo, false));
        $valor = $limite?->toDateString();

        // Query direta: coluna calculada, nao edicao do usuario (sem trilha nem observers).
        PaeProtocolo::query()->whereKey($protocolo->getKey())->update(['limite_analise' => $valor]);
        $protocolo->setAttribute('limite_analise', $valor);
        $protocolo->syncOriginalAttribute('limite_analise');

        return $protocolo;
    }

    public function definirNotificacaoFeam(PaeProtocolo $protocolo, string $data, User $user): PaeProtocolo
    {
        return DB::transaction(function () use ($protocolo, $data, $user): PaeProtocolo {
            $protocolo->update([
                'dt_notificacao_feam' => $data,
                'dt_notificacao_feam_estimada' => false,
                'updated_by' => $user->id,
            ]);

            TimelinePae::registrar(
                $protocolo,
                'prazo',
                'Data da notificacao da FEAM informada: '.Datas::dia($data)->format('d/m/Y').'.',
                $user
            );

            return $this->recalcular($protocolo);
        });
    }

    /** Diligencia aberta empurra o limite um dia por dia: o comando diario chama isto. */
    public function recalcularDiligenciasAbertas(): int
    {
        $total = 0;

        PaeProtocolo::query()
            ->ativo()
            ->whereNotNull('dt_notificacao_feam')
            ->whereHas('analise.notificacoes', fn ($q) => $q->whereNull('dt_devolutiva'))
            ->chunkById(200, function ($lote) use (&$total): void {
                foreach ($lote as $protocolo) {
                    $this->recalcular($protocolo);
                    $total++;
                }
            });

        return $total;
    }

    public function situacao(PaeProtocolo $protocolo): SituacaoPrazo
    {
        return PrazoAnalise::situacao(
            $protocolo->status,
            $protocolo->limite_analise,
            PrazoAnalise::estaPausado($this->intervalos($protocolo, true)),
        );
    }

    /** Art. 7: so faz sentido com a data real da FEAM, nunca com a estimada. */
    public function foraDoPrazoDeProtocolo(PaeProtocolo $protocolo): bool
    {
        if ($protocolo->dt_notificacao_feam_estimada) {
            return false;
        }

        return PrazoProtocolo::foraDoPrazo($protocolo->dt_notificacao_feam, $protocolo->dt_entrada, $this->calendario);
    }

    /**
     * Bloco "Prazos" do historico do protocolo.
     *
     * @return array<string, mixed>
     */
    public function resumo(PaeProtocolo $protocolo): array
    {
        $protocolo->loadMissing(['analise.notificacoes', 'ccpaeVigente']);
        $intervalos = $this->intervalos($protocolo, true);
        $ccpae = $protocolo->ccpaeVigente;

        return [
            'dt_notificacao_feam' => $protocolo->dt_notificacao_feam?->toDateString(),
            'dt_notificacao_feam_estimada' => (bool) $protocolo->dt_notificacao_feam_estimada,
            'dt_entrada' => $protocolo->dt_entrada?->toDateString(),
            'limite_protocolo' => PrazoProtocolo::limite($protocolo->dt_notificacao_feam, $this->calendario)?->toDateString(),
            'fora_do_prazo' => $this->foraDoPrazoDeProtocolo($protocolo),
            'limite_analise' => $protocolo->limite_analise?->toDateString(),
            'situacao' => $this->situacao($protocolo)->value,
            'dias_pausados' => PrazoAnalise::diasPausados($intervalos),
            'ciclos_esgotados_em' => $protocolo->ciclos_esgotados_em?->toDateString(),
            'ccpae' => $ccpae === null ? null : [
                'codigo' => $ccpae->codigo,
                'dt_emissao' => $ccpae->dt_emissao?->toDateString(),
                'dt_vencimento' => $ccpae->dt_vencimento?->toDateString(),
                'dt_licenca_operacao' => $ccpae->dt_licenca_operacao?->toDateString(),
            ],
        ];
    }

    /** Acrescenta a situacao do prazo a cada linha da listagem (relacao ja carregada no list()). */
    public function anotarListagem(LengthAwarePaginator $pagina): LengthAwarePaginator
    {
        return $pagina->through(fn (PaeProtocolo $p): array => [
            ...$p->makeHidden('analise')->toArray(),
            'prazo_situacao' => $this->situacao($p)->value,
            'fora_do_prazo' => $this->foraDoPrazoDeProtocolo($p),
        ]);
    }

    /**
     * @return list<array{inicio: \Carbon\CarbonImmutable, fim: ?\Carbon\CarbonImmutable}>
     */
    private function intervalos(PaeProtocolo $protocolo, bool $usarCarregados): array
    {
        /** @var Collection<int, PaeNotificacao> $notificacoes */
        $notificacoes = $usarCarregados && $protocolo->relationLoaded('analise')
            ? ($protocolo->analise?->notificacoes ?? collect())
            : (PaeNotificacao::query()
                ->whereHas('analise', fn ($q) => $q->where('pae_protocolo_id', $protocolo->getKey()))
                ->get(['dt_notificacao', 'dt_devolutiva']));

        return PrazoAnalise::intervalosDeNotificacoes($notificacoes->map(fn (PaeNotificacao $n): array => [
            'dt_notificacao' => $n->dt_notificacao,
            'dt_devolutiva' => $n->dt_devolutiva,
        ])->all());
    }
}
