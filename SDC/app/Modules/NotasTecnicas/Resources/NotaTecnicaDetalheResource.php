<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Resources;

use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Nota aberta no leitor. O `resource` e o array devolvido por
 * NotaTecnicaConsultaService::detalhe(): nota, versao escolhida, leitura do
 * usuario e telas relacionadas.
 */
final class NotaTecnicaDetalheResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var NotaTecnica $nota */
        $nota = $this->resource['nota'];
        /** @var NotaTecnicaVersao|null $versao */
        $versao = $this->resource['versao'];
        /** @var Carbon|null $lidaEm */
        $lidaEm = $this->resource['lida_em'];
        $atual = $nota->versoes->first();

        return [
            'id' => $nota->id,
            'codigo' => $nota->codigo,
            'titulo' => $nota->titulo,
            'resumo' => $nota->resumo,
            'favorita' => (bool) $this->resource['favorita'],
            'status' => $nota->status->value,
            'status_label' => $nota->status->label(),
            'status_variante' => $nota->status->variante(),
            'categoria' => $nota->categoria === null ? null : implode(' / ', array_filter([
                $nota->categoria->pai?->nome,
                $nota->categoria->nome,
            ])),
            'vigencia_inicio' => $nota->vigencia_inicio?->toDateString(),
            'elaborado_por' => $nota->elaboradoPor?->name,
            'aprovado_por' => $nota->aprovadoPor?->name,
            'rotas_relacionadas' => $this->resource['rotas'],
            'versao' => $versao === null ? null : [
                ...$this->versao($versao),
                'atual' => $versao->id === $atual?->id,
                'pdf_url' => route('notas-tecnicas.versoes.pdf', $versao->id),
                'download_url' => route('notas-tecnicas.versoes.pdf', ['versao' => $versao->id, 'download' => 1]),
                'lida_em' => $lidaEm?->toIso8601String(),
            ],
            'versoes' => $nota->versoes
                ->map(fn (NotaTecnicaVersao $v): array => [
                    ...$this->versao($v),
                    'atual' => $v->id === $atual?->id,
                ])
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function versao(NotaTecnicaVersao $versao): array
    {
        return [
            'id' => $versao->id,
            'numero' => $versao->numero,
            'arquivo_nome' => $versao->arquivo_nome,
            'arquivo_tamanho' => $versao->arquivo_tamanho,
            'changelog' => $versao->changelog,
            'publicada_em' => ($versao->publicada_em ?? $versao->created_at)?->toIso8601String(),
            'publicada_por' => $versao->publicadaPor?->name,
        ];
    }
}
