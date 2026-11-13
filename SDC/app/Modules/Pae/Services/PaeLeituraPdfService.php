<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\Municipio;
use App\Modules\Pae\Models\PaeFormAnexo;
use App\Modules\Pae\Support\LeitorDadosBasicosPae;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Le um anexo PDF ja enviado e SUGERE valores para os campos do PAE. Nao grava
 * nada: devolve o que encontrou para o analista conferir e salvar.
 *
 * O texto vem do `pdftotext` (poppler), presente na imagem do app. PDF
 * escaneado (so imagem) nao tem texto e volta como "sem_texto".
 */
final class PaeLeituraPdfService
{
    /** A ficha (Anexo B, item 2) fica nas primeiras paginas do documento. */
    private const PAGINAS = 25;

    private const TEMPO_LIMITE_SEGUNDOS = 30;

    private const TEXTO_MINIMO = 400;

    /**
     * @return array{lido: bool, motivo: ?string, campos: array<string, string>, municipio_id: ?int}
     */
    public function lerAnexo(PaeFormAnexo $anexo): array
    {
        if ($anexo->mime_type !== 'application/pdf') {
            return $this->semLeitura('nao_pdf');
        }

        $texto = $this->texto($anexo);
        if ($texto === null) {
            return $this->semLeitura('indisponivel');
        }
        if (trim($texto) === '') {
            return $this->semLeitura('sem_texto');
        }

        $campos = LeitorDadosBasicosPae::ler($texto);

        // Escaneado costuma trazer so um rodape digitado: sem campos e com
        // pouco texto, o motivo real e a falta de texto, nao o formato.
        $motivo = mb_strlen(trim($texto)) < self::TEXTO_MINIMO ? 'sem_texto' : 'sem_campos';

        return [
            'lido' => $campos !== [],
            'motivo' => $campos === [] ? $motivo : null,
            'campos' => $campos,
            'municipio_id' => $this->municipioId($campos['municipio'] ?? null),
        ];
    }

    private function texto(PaeFormAnexo $anexo): ?string
    {
        $binario = (new ExecutableFinder())->find('pdftotext');
        $disco = Storage::disk($anexo->disk);
        if ($binario === null || ! $disco->exists($anexo->path)) {
            return null;
        }

        // Copia para um temporario local: o disco pode ser remoto (Azure).
        $temporario = tempnam(sys_get_temp_dir(), 'pae-pdf-');
        try {
            $origem = $disco->readStream($anexo->path);
            $destino = fopen($temporario, 'wb');
            stream_copy_to_stream($origem, $destino);
            fclose($destino);
            if (is_resource($origem)) {
                fclose($origem);
            }

            $processo = new Process([$binario, '-enc', 'UTF-8', '-l', (string) self::PAGINAS, $temporario, '-']);
            $processo->setTimeout(self::TEMPO_LIMITE_SEGUNDOS);
            $processo->run();

            return $processo->isSuccessful() ? $processo->getOutput() : null;
        } catch (Throwable) {
            return null;
        } finally {
            @unlink($temporario);
        }
    }

    /** Casa o nome lido com o catalogo, ignorando acento e caixa. */
    private function municipioId(?string $nome): ?int
    {
        if ($nome === null) {
            return null;
        }

        // O catalogo pode vir como arrays ou modelos; data_get cobre os dois.
        $alvo = Str::lower(Str::ascii($nome));
        $municipio = collect(Municipio::catalogo())->first(
            static fn ($m): bool => Str::lower(Str::ascii((string) data_get($m, 'nome'))) === $alvo
        );
        $id = data_get($municipio, 'id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @return array{lido: bool, motivo: string, campos: array<string, string>, municipio_id: null}
     */
    private function semLeitura(string $motivo): array
    {
        return ['lido' => false, 'motivo' => $motivo, 'campos' => [], 'municipio_id' => null];
    }
}
