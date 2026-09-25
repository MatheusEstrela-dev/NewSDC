<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Queries\DemandaDashboardQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DemandaDashboardController extends Controller
{
    public function __construct(
        private readonly DemandaRepository $repository,
        private readonly DemandaDashboardQuery $query,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $gerir = $user->can('demandas.chamados.manage');
        $id = (int) $user->id;

        return Inertia::render('Demandas/DemandasDashboard', [
            'estatisticas' => $this->repository->getStatistics($id, $gerir),
            'serie' => $this->query->serieMensal($id, $gerir),
            'recentes' => array_map(fn (Demanda $d): array => [
                'id' => $d->id, 'protocolo' => $d->protocolo, 'titulo' => $d->titulo,
                'etapa' => $d->status->etapa()->value, 'etapa_label' => $d->status->etapa()->label(),
                'prioridade_simples' => PrioridadeSimples::dePrioridade($d->prioridade)->value,
                'prioridade_label' => PrioridadeSimples::dePrioridade($d->prioridade)->label(),
                'solicitante' => $d->solicitante?->name, 'created_at' => $d->created_at?->toIso8601String(),
            ], $this->query->recentes($id, $gerir)),
            'pode' => ['exportar' => $user->can('demandas.chamados.export')],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate(['de' => ['nullable', 'date'], 'ate' => ['nullable', 'date', 'after_or_equal:de']]);
        $linhas = $this->query->quantitativoPorAssunto(
            (int) $request->user()->id, $request->user()->can('demandas.chamados.manage'), $data['de'] ?? null, $data['ate'] ?? null,
        );

        return response()->streamDownload(function () use ($linhas): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            $this->escreverLinhaCsv($out, ['assunto', 'total', 'concluidas']);
            foreach ($linhas as $l) {
                // Formula injection: nome de assunto vem de cadastro livre.
                $assunto = preg_match('/^[=+\-@]/', $l['assunto']) ? "'".$l['assunto'] : $l['assunto'];
                $this->escreverLinhaCsv($out, [$assunto, $l['total'], $l['concluidas']]);
            }
            fclose($out);
        }, 'demandas-quantitativo-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Escreve uma linha CSV com quoting RFC4180 minimo (so quando necessario).
     *
     * O fputcsv nativo do PHP 8.4 passou a envolver em aspas qualquer campo
     * com espaco, o que quebraria a saida esperada (ex.: "nome;1;0"). Aqui o
     * campo so e citado se contiver o delimitador, aspas ou quebra de linha.
     *
     * @param resource $out
     * @param array<int, string|int> $campos
     */
    private function escreverLinhaCsv($out, array $campos): void
    {
        $linha = implode(';', array_map(function (string|int $valor): string {
            $texto = (string) $valor;
            if (str_contains($texto, ';') || str_contains($texto, '"') || str_contains($texto, "\n") || str_contains($texto, "\r")) {
                return '"'.str_replace('"', '""', $texto).'"';
            }

            return $texto;
        }, $campos));

        fwrite($out, $linha."\r\n");
    }
}
