<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Console;

use App\Modules\AjudaHumanitaria\Domain\Etl\MapaTabelasLegado;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pousa as tabelas aju_* de um dump SQL do MySQL em ajuda_h_legado_raw.
 *
 * Mesmo destino e mesma chave de idempotencia de legado:aju:extrair, que le de
 * uma conexao MySQL viva. A diferenca e so a origem: aqui o dado vem de arquivo.
 * Existe porque o corte com a Prodemge nem sempre da acesso de rede a base
 * procedural, e o que chega e um dump do HeidiSQL. Sem este caminho, carregar o
 * legado exigiria subir um MySQL so para servir de intermediario.
 *
 * O refino subsequente (legado:aju:refinar) e o mesmo nos dois casos: ele le a
 * area de pouso, nunca a origem. Este comando existe abaixo dele, nao ao lado.
 *
 * Le apenas as tabelas declaradas em MapaTabelasLegado. Tabela do dump fora do
 * mapa nao tem destino no refino e e reportada, nao carregada.
 */
final class ImportarDumpLegadoAjuCommand extends Command
{
    protected $signature = 'legado:aju:importar-dump
        {arquivo : Caminho do dump .sql (relativo a base_path ou absoluto)}
        {--tabela=* : Limita a carga a estas tabelas}
        {--chunk=500 : Linhas por lote no upsert}';

    protected $description = 'Pousa as tabelas aju_* de um dump MySQL em ajuda_h_legado_raw';

    public function handle(): int
    {
        $caminho = (string) $this->argument('arquivo');

        if (! is_file($caminho)) {
            $caminho = base_path($caminho);
        }

        if (! is_file($caminho) || ! is_readable($caminho)) {
            $this->error("Dump inacessivel: {$this->argument('arquivo')}");

            return self::FAILURE;
        }

        $sql = file_get_contents($caminho);

        if ($sql === false) {
            $this->error('Falha ao ler o dump.');

            return self::FAILURE;
        }

        $escolhidas = (array) $this->option('tabela');
        $tabelas    = $escolhidas === []
            ? array_keys(MapaTabelasLegado::tabelas())
            : array_values(array_intersect(array_keys(MapaTabelasLegado::tabelas()), $escolhidas));

        if ($tabelas === []) {
            $this->error('Nenhuma tabela conhecida entre as informadas.');

            return self::FAILURE;
        }

        $this->avisarTabelasForaDoMapa($sql);

        $total    = 0;
        $ausentes = 0;

        foreach ($tabelas as $tabela) {
            $carregadas = $this->carregarTabela($sql, $tabela);

            if ($carregadas === null) {
                $this->line(sprintf('%-20s ausente no dump, pulada', $tabela));
                $ausentes++;

                continue;
            }

            $total += $carregadas;
        }

        $this->info(sprintf(
            'Carga concluida: %d linhas em %d tabelas (%d ausentes no dump).',
            $total,
            count($tabelas) - $ausentes,
            $ausentes
        ));

        return self::SUCCESS;
    }

    /**
     * Carrega uma tabela do dump. Retorna null quando a tabela nao aparece nele.
     */
    private function carregarTabela(string $sql, string $tabela): ?int
    {
        $lote       = [];
        $carregadas = 0;
        $chunk      = max(1, (int) $this->option('chunk'));
        $encontrada = false;
        $agora      = now();

        foreach ($this->lerInserts($sql, $tabela) as [$colunas, $linhas]) {
            $encontrada = true;
            $chave      = MapaTabelasLegado::resolverChave($tabela, $colunas);

            // Sem chave resolvida, pk_legado sairia vazio e o upsert colapsaria a
            // tabela inteira em uma linha. Falha alto em vez de corromper.
            if ($chave === null) {
                throw new RuntimeException(sprintf(
                    'Nenhuma coluna de chave (%s) existe em %s. Corrija MapaTabelasLegado.',
                    implode(', ', MapaTabelasLegado::candidatasChave($tabela)),
                    $tabela
                ));
            }

            foreach ($linhas as $valores) {
                // Linha com aridade diferente da lista de colunas nao e recuperavel
                // por adivinhacao: o dump esta inconsistente e precisa de olho humano.
                if (count($valores) !== count($colunas)) {
                    throw new RuntimeException(sprintf(
                        'Linha de %s com %d valores para %d colunas.',
                        $tabela,
                        count($valores),
                        count($colunas)
                    ));
                }

                $doc = array_combine($colunas, $valores);

                if (($doc[$chave] ?? null) === null) {
                    continue;
                }

                $lote[] = [
                    'tabela'      => $tabela,
                    'pk_legado'   => (string) $doc[$chave],
                    'doc'         => json_encode($doc, JSON_UNESCAPED_UNICODE),
                    'extraido_em' => $agora,
                ];

                if (count($lote) >= $chunk) {
                    $carregadas += $this->gravar($lote);
                    $lote = [];
                }
            }
        }

        if ($lote !== []) {
            $carregadas += $this->gravar($lote);
        }

        if (! $encontrada) {
            return null;
        }

        $this->line(sprintf('%-20s %6d linhas', $tabela, $carregadas));

        return $carregadas;
    }

    /**
     * Grava o lote na area de pouso. Idempotente por (tabela, pk_legado):
     * reexecutar atualiza o documento em vez de duplicar.
     *
     * @param  list<array<string, mixed>>  $lote
     */
    private function gravar(array $lote): int
    {
        DB::table('ajuda_h_legado_raw')->upsert($lote, ['tabela', 'pk_legado'], ['doc', 'extraido_em']);

        return count($lote);
    }

    /**
     * Percorre os INSERT de uma tabela no dump.
     *
     * O HeidiSQL emite a lista de colunas em cada INSERT, entao a ordem vem do
     * proprio comando e nao depende de interpretar o CREATE TABLE.
     *
     * @return iterable<array{0: list<string>, 1: list<list<string|null>>}>
     */
    private function lerInserts(string $sql, string $tabela): iterable
    {
        $padrao = '/INSERT\s+(?:IGNORE\s+)?INTO\s+`'.preg_quote($tabela, '/').'`\s*\(([^)]*)\)\s*VALUES/i';
        $offset = 0;

        while (preg_match($padrao, $sql, $achado, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $colunas = array_map(
                static fn (string $coluna): string => trim(trim($coluna), '`'),
                explode(',', $achado[1][0])
            );

            $inicio = $achado[0][1] + strlen($achado[0][0]);
            $fim    = $inicio;

            yield [$colunas, $this->lerTuplas($sql, $inicio, $fim)];

            $offset = $fim;
        }
    }

    /**
     * Le a lista de tuplas de um VALUES, do offset informado ate o ponto e virgula.
     *
     * Feito caractere a caractere de proposito. Expressao regular nao distingue
     * um parentese ou virgula literal dentro de string ('BR 367 KM 112, S-N') do
     * separador de valores, e o dump esta cheio de endereco e observacao com os
     * dois. O $fim volta por referencia para a varredura seguir do fim deste
     * INSERT, sem reprocessar o que ja foi lido.
     *
     * @return list<list<string|null>>
     */
    private function lerTuplas(string $sql, int $inicio, int &$fim): array
    {
        $tuplas       = [];
        $valores      = [];
        $bruto        = '';
        $entreAspas   = false;
        $foiCitado    = false;
        $fechouString = false;
        $emTupla      = false;
        $tamanho      = strlen($sql);
        $i            = $inicio;

        while ($i < $tamanho) {
            $caractere = $sql[$i];

            if ($entreAspas) {
                if ($caractere === '\\' && $i + 1 < $tamanho) {
                    $bruto .= $this->desescapar($sql[$i + 1]);
                    $i += 2;

                    continue;
                }

                // Aspas dobrada e aspas literal; sozinha, fecha a string.
                if ($caractere === "'") {
                    if (($sql[$i + 1] ?? '') === "'") {
                        $bruto .= "'";
                        $i += 2;

                        continue;
                    }

                    $entreAspas   = false;
                    $fechouString = true;
                    $i++;

                    continue;
                }

                $bruto .= $caractere;
                $i++;

                continue;
            }

            if ($caractere === "'") {
                // O espaco entre a virgula e a abertura da aspa e formatacao do
                // dump, nao conteudo. Sem descartar, todo valor de texto entra
                // com um espaco a mais na frente: os campos que o refino le com
                // trim() sobrevivem, mas os guardados por ancora de regex
                // ('^[0-9]{4}-') falham calados e a data vira NULL.
                if (trim($bruto) === '') {
                    $bruto = '';
                }

                $entreAspas = true;
                $foiCitado  = true;
                $i++;

                continue;
            }

            if (! $emTupla) {
                if ($caractere === '(') {
                    $emTupla      = true;
                    $valores      = [];
                    $bruto        = '';
                    $foiCitado    = false;
                    $fechouString = false;
                } elseif ($caractere === ';') {
                    $fim = $i + 1;

                    return $tuplas;
                }

                $i++;

                continue;
            }

            if ($caractere === ',') {
                $valores[]    = $this->normalizar($bruto, $foiCitado);
                $bruto        = '';
                $foiCitado    = false;
                $fechouString = false;
                $i++;

                continue;
            }

            if ($caractere === ')') {
                $valores[]    = $this->normalizar($bruto, $foiCitado);
                $tuplas[]     = $valores;
                $emTupla      = false;
                $fechouString = false;
                $i++;

                continue;
            }

            // Espaco depois da aspa de fechamento tambem e formatacao. Dentro da
            // string ele ja foi preservado pelo ramo de cima.
            if ($fechouString && trim($caractere) === '') {
                $i++;

                continue;
            }

            $bruto .= $caractere;
            $i++;
        }

        $fim = $tamanho;

        return $tuplas;
    }

    /**
     * Valor nao citado NULL e ausencia; citado, e o texto literal "NULL".
     *
     * Numero fica como string porque e o que o PDO do MySQL devolve em
     * legado:aju:extrair, e o refino le tudo com doc->>'campo'. Manter os dois
     * caminhos produzindo o mesmo documento e o que permite um refino unico.
     */
    private function normalizar(string $bruto, bool $foiCitado): ?string
    {
        if ($foiCitado) {
            return $bruto;
        }

        $limpo = trim($bruto);

        return strcasecmp($limpo, 'NULL') === 0 ? null : $limpo;
    }

    private function desescapar(string $caractere): string
    {
        return match ($caractere) {
            'n'     => "\n",
            'r'     => "\r",
            't'     => "\t",
            '0'     => "\0",
            'Z'     => "\x1a",
            'b'     => "\x08",
            default => $caractere,
        };
    }

    /**
     * Reporta o que o dump traz e o refino ainda nao consome. Nao e erro: o
     * escopo modelado hoje e o nucleo de estoque, e o resto do procedural
     * (pedidos, permissoes, cadastros auxiliares) entra em fase propria.
     */
    private function avisarTabelasForaDoMapa(string $sql): void
    {
        preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)? `(aju_[^`]+)`/i', $sql, $achados);

        $fora = array_values(array_diff(
            array_unique($achados[1]),
            array_keys(MapaTabelasLegado::tabelas())
        ));

        if ($fora === []) {
            return;
        }

        $this->warn(sprintf(
            '%d tabelas do dump estao fora do mapa de carga e nao serao lidas: %s',
            count($fora),
            implode(', ', $fora)
        ));
    }
}
