<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Services;

use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Extrai o texto do PDF de uma versao (pdftotext, do poppler-utils) e grava o
 * vetor de busca. Sem dependencia PHP nova: o binario e chamado pelo Process do
 * Laravel.
 *
 * PDF escaneado (so imagem) volta sem texto: a versao fica indexada vazia e a
 * busca simplesmente nao a encontra pelo conteudo -- OCR esta fora do escopo.
 */
final class NotaTecnicaIndexacaoService
{
    /**
     * O tsvector do Postgres tem limite de 1 MB. Texto acima disso nao cabe; os
     * primeiros 500 mil caracteres cobrem com folga um POP de centenas de paginas.
     * Publica: a consulta limita o ts_headline ao mesmo trecho que foi indexado.
     */
    public const LIMITE_VETOR = 500_000;

    /**
     * Segunda tentativa: texto com palavras muito distintas pode estourar o
     * tsvector mesmo abaixo do limite acima (o limite e em bytes do vetor, nao
     * em caracteres do texto).
     */
    private const LIMITE_VETOR_REDUZIDO = 200_000;

    public function indexar(NotaTecnicaVersao $versao): void
    {
        $texto = $this->extrairTexto($versao);

        try {
            $this->gravar($versao, $texto, self::LIMITE_VETOR);
        } catch (QueryException $e) {
            if (! str_contains($e->getMessage(), 'string is too long for tsvector')) {
                throw $e;
            }

            // O conteudo_texto fica integral; so o vetor de busca cobre menos texto.
            $this->gravar($versao, $texto, self::LIMITE_VETOR_REDUZIDO);
        }
    }

    /**
     * Em transacao propria (savepoint se ja houver uma): no Postgres, um erro
     * aborta a transacao inteira e impediria a nova tentativa.
     */
    private function gravar(NotaTecnicaVersao $versao, string $texto, int $limite): void
    {
        DB::transaction(fn () => DB::update(
            "update nota_tecnica_versoes
                set conteudo_texto = ?,
                    conteudo_busca = to_tsvector('pt_sem_acento', left(?, ?)),
                    updated_at = now()
              where id = ?",
            [$texto, $texto, $limite, $versao->id],
        ));
    }

    private function extrairTexto(NotaTecnicaVersao $versao): string
    {
        $disk = Storage::disk(NotaTecnicaVersao::DISK);

        if (! $disk->exists($versao->arquivo_path)) {
            throw new RuntimeException("PDF da versao {$versao->id} nao encontrado no disk.");
        }

        // O disk pode ser Azure Blob: o pdftotext precisa de arquivo local.
        $temporario = tempnam(sys_get_temp_dir(), 'nt-pdf-');

        try {
            file_put_contents($temporario, $disk->get($versao->arquivo_path));

            $resultado = Process::timeout((int) config('notas_tecnicas.pdftotext.timeout', 90))
                ->run([config('notas_tecnicas.pdftotext.binario', 'pdftotext'), '-enc', 'UTF-8', '-q', $temporario, '-']);

            if ($resultado->failed()) {
                throw new RuntimeException(sprintf(
                    'pdftotext falhou na versao %d (exit %d): %s',
                    $versao->id,
                    $resultado->exitCode(),
                    trim($resultado->errorOutput()) ?: 'sem mensagem',
                ));
            }

            // mb_scrub antes das regex /u: com UTF-8 invalido elas devolvem null e o
            // Postgres recusaria o texto cru no update.
            return $this->limpar(mb_scrub($resultado->output(), 'UTF-8'));
        } finally {
            @unlink($temporario);
        }
    }

    /** Tira byte nulo (o Postgres recusa) e colapsa espacos e quebras de pagina. */
    private function limpar(string $texto): string
    {
        $texto = str_replace(["\0", "\f"], ['', "\n"], $texto);
        $texto = preg_replace('/[ \t]+/u', ' ', $texto) ?? $texto;

        return trim(preg_replace("/\n{3,}/u", "\n\n", $texto) ?? $texto);
    }
}
