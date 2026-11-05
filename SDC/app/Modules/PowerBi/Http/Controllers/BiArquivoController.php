<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\AsynchronousResponse;
use App\Modules\PowerBi\Arquivos\RegistroDeArquivos;
use App\Modules\PowerBi\Jobs\GerarArquivoBiJob;
use App\Modules\PowerBi\Support\CatalogoBi;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Arquivos do BI: pedido assincrono (POST), listagem dos Parquet publicados
 * (GET) e download autenticado quando o disco nao gera URL temporaria.
 */
final class BiArquivoController extends Controller
{
    use AsynchronousResponse;

    public function gerar(Request $request, CatalogoBi $catalogo, string $modulo, string $tabela): JsonResponse
    {
        if (! ConexaoPowerBi::ativa()) {
            return response()->json(['message' => 'Saida de BI desligada neste ambiente.'], 503);
        }
        if ($catalogo->definicao($modulo, $tabela) === null) {
            return response()->json(['message' => 'Tabela de BI inexistente.'], 404);
        }

        $dados = $request->validate(['formato' => ['required', 'string', 'in:'.implode(',', GerarArquivoBiJob::FORMATOS)]]);

        return $this->dispatchAsyncJob(
            jobClass: GerarArquivoBiJob::class,
            type: 'bi_arquivo',
            args: [$modulo, $tabela, $dados['formato']],
            meta: ['modulo' => $modulo, 'tabela' => $tabela, 'formato' => $dados['formato']],
            queue: (string) config('powerbi.arquivos.fila', 'powerbi'),
            estimatedSeconds: 120,
        );
    }

    public function index(): JsonResponse
    {
        if (! ConexaoPowerBi::ativa()) {
            return response()->json(['message' => 'Saida de BI desligada neste ambiente.'], 503);
        }

        $linhas = ConexaoPowerBi::leitura()->table('controle.arquivos')
            ->orderByDesc('gerado_em')->orderByDesc('id')
            ->limit((int) config('powerbi.arquivos.limite_listagem', 100))
            ->get();

        $dados = $linhas->map(fn (object $l): array => [
            'id' => (int) $l->id,
            'modulo' => $l->modulo,
            'tabela' => $l->tabela,
            'formato' => $l->formato,
            'linhas' => (int) $l->linhas,
            'dados_ate' => $l->dados_ate !== null ? Carbon::parse($l->dados_ate)->toIso8601String() : null,
            'gerado_em' => Carbon::parse($l->gerado_em)->toIso8601String(),
            'url' => $this->urlDe($l),
        ])->values()->all();

        return response()->json(['dados' => $dados]);
    }

    public function baixar(int $id): JsonResponse|StreamedResponse
    {
        if (! ConexaoPowerBi::ativa()) {
            return response()->json(['message' => 'Saida de BI desligada neste ambiente.'], 503);
        }

        $arquivo = ConexaoPowerBi::leitura()->table('controle.arquivos')->where('id', $id)->first();
        $disco = Storage::disk(RegistroDeArquivos::disco());
        if ($arquivo === null || ! $disco->exists($arquivo->caminho)) {
            return response()->json(['message' => 'Arquivo inexistente.'], 404);
        }

        // Content-Type explicito: o detector de mime do disco local nao conhece .parquet.
        return $disco->download($arquivo->caminho, null, ['Content-Type' => 'application/octet-stream']);
    }

    /** URL temporaria se o disco suporta; senao a rota autenticada de download. */
    private function urlDe(object $arquivo): string
    {
        try {
            return Storage::disk(RegistroDeArquivos::disco())->temporaryUrl(
                $arquivo->caminho,
                now()->addMinutes((int) config('powerbi.arquivos.url_temporaria_minutos', 15))
            );
        } catch (Throwable) {
            return route('api.v1.bi.arquivos.baixar', ['id' => $arquivo->id]);
        }
    }
}
