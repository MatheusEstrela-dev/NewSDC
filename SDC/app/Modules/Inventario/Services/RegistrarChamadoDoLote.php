<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\DTOs\ResolucaoDemandaData;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Services\DemandaStatusService;
use App\Modules\Demandas\Services\DemandaWriteService;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Models\RemanejamentoPessoa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Registra o chamado do lote em Demandas, ja resolvido, pelos casos de uso
 * publicos do modulo (nunca criando Demanda direto). Um chamado por lote.
 */
final class RegistrarChamadoDoLote
{
    public function __construct(
        private readonly DemandaWriteService $escrita,
        private readonly DemandaStatusService $status,
    ) {}

    public function executar(Remanejamento $remanejamento, int $userId): Demanda
    {
        return DB::transaction(function () use ($remanejamento, $userId): Demanda {
            // Lock no lote: dois cliques simultaneos nao abrem duas demandas.
            $lote = Remanejamento::query()->lockForUpdate()->findOrFail($remanejamento->getKey());

            $existente = $lote->demanda_id !== null ? Demanda::query()->find($lote->demanda_id) : null;
            if ($existente !== null) {
                return $existente;
            }
            if (! $lote->estaAtivo()) {
                throw RemanejamentoProibido::loteDesfeito();
            }

            $assunto = $this->assunto();
            $lote->load([
                'pessoas.usuario:id,name',
                'pessoas.estacaoOrigem:id,nome',
                'pessoas.estacaoDestino:id,nome',
                'pessoas.movimentacoes.equipamento' => static fn ($q) => $q->withTrashed(),
            ]);
            $itens = $lote->itensRemanejados()->count();
            $prioridade = PrioridadeSimples::MEDIA;

            $demanda = $this->escrita->abrir(new CriarDemandaData(
                tipo: TipoDemanda::SOLICITACAO,
                titulo: sprintf('Remanejamento de %s — %d %s', $lote->created_at->format('d/m/Y H:i'), $itens, $itens === 1 ? 'item' : 'itens'),
                descricao: $this->descricao($lote),
                categoria: null,
                subcategoria: null,
                assuntoId: $assunto->id,
                urgencia: $prioridade->urgencia(),
                impacto: $prioridade->impacto(),
                solicitanteId: (int) ($lote->registrado_por_id ?? $userId),
                criadoPorId: $userId,
            ));

            // Abertura e fechamento na data do lote: o chamado documenta algo que
            // ja aconteceu, nao um atendimento que comeca agora.
            $data = CarbonImmutable::instance($lote->created_at);
            $this->status->resolver($demanda, new ResolucaoDemandaData($data, $data), $userId);
            $lote->update(['demanda_id' => $demanda->id]);

            return $demanda->refresh();
        });
    }

    private function assunto(): DemandaAssunto
    {
        $nome = trim((string) config('inventario.remanejamento.assunto_chamado'));
        $assunto = $nome !== '' ? DemandaAssunto::query()->where('nome', $nome)->first() : null;

        if ($assunto === null) {
            throw RemanejamentoProibido::configuracao(
                'O assunto do chamado de remanejamento não está configurado ou não existe em Demandas (INVENTARIO_ASSUNTO_CHAMADO_LOTE).'
            );
        }

        return $assunto;
    }

    private function descricao(Remanejamento $lote): string
    {
        $linhas = ['Remanejamento registrado no Inventário em '.$lote->created_at->format('d/m/Y H:i').'.', ''];

        foreach ($lote->pessoas->sortBy('id') as $pessoa) {
            /** @var RemanejamentoPessoa $pessoa */
            $patrimonios = $pessoa->movimentacoes
                ->where('tipo', TipoMovimentacao::REMANEJAMENTO->value)
                ->map(static fn (Movimentacao $m): string => $m->equipamento?->patrimonio ?? '#'.$m->equipamento_id)
                ->implode(', ');
            $linhas[] = sprintf(
                '- %s: %s -> %s (%s)',
                $pessoa->usuario?->name ?? 'Pessoa removida',
                $pessoa->estacaoOrigem?->nome ?? 'sem estação',
                $pessoa->estacaoDestino?->nome ?? 'sem estação',
                $patrimonios,
            );
        }

        if ($lote->observacao !== null) {
            $linhas[] = '';
            $linhas[] = 'Observação: '.$lote->observacao;
        }

        return implode("\n", $linhas);
    }
}
