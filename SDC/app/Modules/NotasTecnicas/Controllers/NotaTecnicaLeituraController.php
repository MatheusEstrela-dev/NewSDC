<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;
use App\Modules\NotasTecnicas\Services\NotaTecnicaLeituraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Ciencia de leitura ("Li e estou ciente") e o relatorio de quem leu. */
final class NotaTecnicaLeituraController extends Controller
{
    private const ABAS = ['lidas', 'pendentes'];

    public function __construct(private readonly NotaTecnicaLeituraService $leituras) {}

    public function ciencia(Request $request, NotaTecnicaVersao $versao): RedirectResponse
    {
        $this->authorize('view', $versao->nota);

        $leitura = $this->leituras->darCiencia($versao, $request->user());

        return back()->with('success', $leitura->wasRecentlyCreated
            ? 'Ciência registrada. Obrigado!'
            : 'Você já tinha dado ciência nesta versão.');
    }

    public function relatorio(Request $request, NotaTecnica $nota): Response
    {
        $versao = $this->versao($request, $nota);
        $aba = in_array($request->query('aba'), self::ABAS, true) ? $request->query('aba') : 'pendentes';
        $busca = $request->query('busca');
        $busca = is_string($busca) ? $busca : null;

        return Inertia::render('NotasTecnicas/Leituras', [
            'nota' => ['id' => $nota->id, 'codigo' => $nota->codigo, 'titulo' => $nota->titulo],
            'versoes' => $nota->versoes()->get(['id', 'numero'])
                ->map(fn (NotaTecnicaVersao $v): array => ['value' => $v->id, 'label' => "v{$v->numero}"])
                ->values()
                ->all(),
            'versaoId' => $versao?->id,
            'filtros' => ['aba' => $aba, 'busca' => $busca],
            'resumo' => $versao === null ? null : $this->leituras->resumo($versao),
            'pessoas' => $versao === null ? null : ($aba === 'lidas'
                ? $this->leituras->lidas($versao, $busca)
                : $this->leituras->pendentes($versao, $busca)),
        ]);
    }

    public function exportar(Request $request, NotaTecnica $nota): StreamedResponse
    {
        $versao = $this->versao($request, $nota);
        abort_if($versao === null, 404);

        $arquivo = sprintf('leituras_%s_v%s_%s.csv', $nota->codigo, $versao->numero, now()->format('Y-m-d_H-i'));

        return response()->streamDownload(function () use ($versao): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8, para o Excel

            foreach ($this->leituras->linhasCsv($versao) as $linha) {
                fputcsv($handle, $linha, ';');
            }

            fclose($handle);
        }, $arquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Versao pedida em ?versao=, ou a vigente. */
    private function versao(Request $request, NotaTecnica $nota): ?NotaTecnicaVersao
    {
        $id = filter_var($request->query('versao'), FILTER_VALIDATE_INT);

        return $id === false
            ? $nota->versaoAtual()->first()
            : $nota->versoes()->whereKey($id)->first() ?? $nota->versaoAtual()->first();
    }
}
