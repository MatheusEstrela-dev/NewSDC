<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Download de CSV no formato que o Excel pt-BR abre sem perguntar nada.
 *
 * DUAS DECISOES QUE PARECEM DETALHE E NAO SAO:
 *
 * 1) BOM UTF-8. Sem ele o Excel assume a codepage do sistema e todo acento vira
 *    caractere quebrado -- "PRESTAÇÃO" sai "PRESTAÃÃO". O CSV abre, a pessoa
 *    ve lixo e conclui que o sistema exportou errado.
 *
 * 2) Separador ';'. O Excel em pt-BR usa a virgula como separador DECIMAL, e um
 *    CSV separado por virgula entra todo numa coluna so. Como os numeros daqui
 *    saem formatados em pt-BR (number_format com ',' decimal), virgula como
 *    separador tambem quebraria a propria celula.
 *
 * MOTIVO DE EXISTIR: este bloco estava copiado identico em CaminhaoController e
 * VistoriaController -- as mesmas ~25 linhas, incluindo os dois comentarios
 * acima. Duas copias do formato de saida significam que corrigir codificacao num
 * lugar deixa o outro quebrado.
 *
 * O cabecalho sai das CHAVES da primeira linha, entao a ordem das colunas e a
 * ordem do array que o Service monta -- e o Service ja e a fonte unica de
 * filtros da listagem e do CSV.
 */
final class ExportadorCsv
{
    /** Separador que o Excel pt-BR entende. */
    private const SEPARADOR = ';';

    /**
     * @param  array<int, array<string, mixed>>  $linhas
     * @param  string  $prefixo  Nome do arquivo sem data nem extensao (ex.: 'frota')
     */
    public static function baixar(array $linhas, string $prefixo): StreamedResponse
    {
        $arquivo = $prefixo.'_'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($linhas): void {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($linhas !== []) {
                fputcsv($handle, array_keys($linhas[0]), self::SEPARADOR);
            }

            foreach ($linhas as $linha) {
                fputcsv($handle, array_values($linha), self::SEPARADOR);
            }

            fclose($handle);
        }, $arquivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
