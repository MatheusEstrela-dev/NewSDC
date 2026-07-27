<?php

declare(strict_types=1);

namespace App\Support\Legacy;

use Closure;

/**
 * Leitor de dumps MySQL (mysqldump/HeidiSQL) em streaming.
 *
 * Le o arquivo linha a linha (sem carregar tudo em memoria), reconhece os
 * cabecalhos `INSERT INTO ... (colunas) VALUES` e entrega cada tupla como um
 * array associativo coluna => valor (string ou null), respeitando strings entre
 * aspas, escapes MySQL (\n \r \t \\ \' etc.) e HTML embutido.
 *
 * Uso generico para importar tabelas legadas (com_rat, cedec_municipio, ...)
 * quando nao ha um banco MySQL vivo, apenas o arquivo .sql.
 */
class MysqlDumpReader
{
    /**
     * Percorre o dump chamando $onRow para cada linha de dados.
     *
     * @param  Closure(array<string, string|null>): void  $onRow
     * @param  string|null  $somenteTabela  Se informado, ignora INSERTs de outras tabelas.
     */
    public function eachRow(string $file, Closure $onRow, ?string $somenteTabela = null): void
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Nao foi possivel abrir o dump: {$file}");
        }

        $colunas = null;
        $tabelaAtiva = true;

        try {
            while (($linha = fgets($handle)) !== false) {
                $trim = ltrim($linha);

                if (stripos($trim, 'INSERT INTO') === 0) {
                    [$tabela, $colunas, $payload] = $this->parseCabecalho($trim);
                    $tabelaAtiva = $somenteTabela === null || strcasecmp($tabela, $somenteTabela) === 0;

                    if ($tabelaAtiva && $payload !== '') {
                        foreach ($this->extrairTuplos($payload) as $tuplo) {
                            $onRow($this->combinar($colunas, $this->parseTuplo($tuplo)));
                        }
                    }

                    continue;
                }

                if ($tabelaAtiva && $colunas !== null && ($trim[0] ?? '') === '(') {
                    foreach ($this->extrairTuplos($trim) as $tuplo) {
                        $onRow($this->combinar($colunas, $this->parseTuplo($tuplo)));
                    }
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array{0: string, 1: array<int, string>, 2: string}
     */
    private function parseCabecalho(string $linha): array
    {
        $tabela = '';
        if (preg_match('/INSERT\s+INTO\s+[`"]?([a-zA-Z0-9_]+)[`"]?/i', $linha, $mt) === 1) {
            $tabela = $mt[1];
        }

        $colunas = [];
        if (preg_match('/\(([^)]*)\)\s*VALUES/i', $linha, $m) === 1) {
            foreach (explode(',', $m[1]) as $col) {
                $colunas[] = trim(trim($col), " `\t");
            }
        }

        $pos = stripos($linha, 'VALUES');
        $payload = $pos === false ? '' : trim(substr($linha, $pos + 6));

        return [$tabela, $colunas, $payload];
    }

    /**
     * Quebra o payload em tuplos de topo `(...)`, respeitando aspas/escapes.
     *
     * @return array<int, string>
     */
    private function extrairTuplos(string $payload): array
    {
        $tuplos = [];
        $len = strlen($payload);
        $i = 0;

        while ($i < $len) {
            if ($payload[$i] !== '(') {
                $i++;

                continue;
            }

            $depth = 0;
            $emString = false;
            $inicio = $i;

            for (; $i < $len; $i++) {
                $ch = $payload[$i];

                if ($emString) {
                    if ($ch === '\\') {
                        $i++;
                    } elseif ($ch === "'") {
                        if (($payload[$i + 1] ?? '') === "'") {
                            $i++;
                        } else {
                            $emString = false;
                        }
                    }

                    continue;
                }

                if ($ch === "'") {
                    $emString = true;
                } elseif ($ch === '(') {
                    $depth++;
                } elseif ($ch === ')') {
                    $depth--;
                    if ($depth === 0) {
                        $tuplos[] = substr($payload, $inicio, $i - $inicio + 1);
                        $i++;
                        break;
                    }
                }
            }
        }

        return $tuplos;
    }

    /**
     * Parseia um tuplo `(v1, v2, ...)` em valores escalares (string|null).
     *
     * @return array<int, string|null>
     */
    private function parseTuplo(string $tuplo): array
    {
        $conteudo = substr(trim($tuplo), 1, -1);
        $valores = [];
        $len = strlen($conteudo);
        $buffer = '';
        $emString = false;
        $ehString = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $conteudo[$i];

            if ($emString) {
                if ($ch === '\\') {
                    $buffer .= $this->desescapar($conteudo[$i + 1] ?? '');
                    $i++;
                } elseif ($ch === "'") {
                    if (($conteudo[$i + 1] ?? '') === "'") {
                        $buffer .= "'";
                        $i++;
                    } else {
                        $emString = false;
                    }
                } else {
                    $buffer .= $ch;
                }

                continue;
            }

            if ($ch === "'") {
                $emString = true;
                $ehString = true;
            } elseif ($ch === ',') {
                $valores[] = $this->normalizar($buffer, $ehString);
                $buffer = '';
                $ehString = false;
            } else {
                $buffer .= $ch;
            }
        }

        $valores[] = $this->normalizar($buffer, $ehString);

        return $valores;
    }

    private function desescapar(string $ch): string
    {
        return match ($ch) {
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            '0' => "\0",
            'b' => "\x08",
            'Z' => "\x1a",
            default => $ch,
        };
    }

    private function normalizar(string $bruto, bool $ehString): ?string
    {
        if (! $ehString) {
            $t = trim($bruto);

            return ($t === '' || strcasecmp($t, 'NULL') === 0) ? null : $t;
        }

        return $bruto;
    }

    /**
     * @param  array<int, string>  $colunas
     * @param  array<int, string|null>  $valores
     * @return array<string, string|null>
     */
    private function combinar(array $colunas, array $valores): array
    {
        if ($colunas === [] || count($colunas) !== count($valores)) {
            throw new \RuntimeException('Numero de valores diverge do numero de colunas no dump.');
        }

        return array_combine($colunas, $valores);
    }
}
