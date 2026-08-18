<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Um lote de TDAP atende VARIOS municipios (a ata subdivide o estado em lotes
 * e cada lote agrupa municipios vizinhos sob um prestador). O schema original
 * tinha um unico `tdap_lotes.municipio_id`, e por isso a base de producao
 * chegou com `municipio_id = 0` em todos os lotes (id inexistente em
 * `municipios`, FK nunca validada): a coluna "Municipio" da grade renderizava
 * vazia e os nomes reais so existiam soltos dentro do texto de `nome`.
 *
 * Esta migration cria o vinculo N:N, torna o `municipio_id` legado anulavel
 * (passa a ser apenas o municipio de referencia do lote) e faz o backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_lote_municipios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lote_id')
                ->constrained('tdap_lotes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(['lote_id', 'municipio_id']);
            $table->index('municipio_id');
        });

        // Deixa de ser obrigatorio: a lista de municipios do lote vive na
        // tabela pivo. Os `municipio_id = 0` (FK invalida) viram NULL.
        Schema::table('tdap_lotes', function (Blueprint $table) {
            $table->unsignedBigInteger('municipio_id')->nullable()->change();
        });

        DB::table('tdap_lotes')
            ->whereNotIn('municipio_id', fn ($q) => $q->select('id')->from('municipios'))
            ->update(['municipio_id' => null]);

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_lote_municipios');

        // Nao volta a NOT NULL: os registros legados tinham municipio_id = 0,
        // valor que a FK nunca aceitou de verdade.
    }

    /**
     * Backfill dos vinculos. Duas fontes, nesta ordem:
     *
     * 1. O texto de `tdap_lotes.nome` ("...destinado aos municipios de A, B e
     *    C."), que e a redacao da propria ata — fonte autoritativa.
     * 2. Os municipios dos cronogramas ja emitidos para o lote. Cobre o que o
     *    texto perdeu: `nome` e varchar(150) e truncou varias listas.
     *
     * O casamento dos nomes tolera acentuacao/caixa, truncamento (prefixo
     * unico) e erros de digitacao da redacao ("Captao Eneas", "Claro dos
     * Pocoes") via distancia de edicao <= 2 com melhor candidato unico.
     */
    private function backfill(): void
    {
        $municipios = DB::table('municipios')->orderBy('nome')->get(['id', 'nome']);

        /** @var array<string, int> $porNome */
        $porNome = [];
        /** @var array<string, int|false> $porChave chave sem preposicoes; false = ambigua */
        $porChave = [];
        /** @var array<int, array{id: int, norm: string}> $lista */
        $lista = [];

        foreach ($municipios as $m) {
            $norm = $this->normalizar($m->nome);
            $porNome[$norm] = (int) $m->id;
            $lista[] = ['id' => (int) $m->id, 'norm' => $norm];

            $chave = $this->chave($m->nome);
            $porChave[$chave] = array_key_exists($chave, $porChave) ? false : (int) $m->id;
        }

        $agora = now();
        $naoResolvidos = [];
        $linhas = [];

        $lotes = DB::table('tdap_lotes')->get(['id', 'nome']);

        foreach ($lotes as $lote) {
            $ids = [];

            foreach ($this->extrairNomes((string) ($lote->nome ?? '')) as $candidato) {
                $id = $this->resolver($candidato, $porNome, $porChave, $lista);

                if ($id === null) {
                    $naoResolvidos[] = "lote {$lote->id}: \"{$candidato}\"";

                    continue;
                }

                $ids[$id] = true;
            }

            // Cronogramas existentes: garante que todo municipio ja agendado
            // pertenca ao lote (do contrario a tela de cronograma ofereceria
            // uma lista que nao contem o valor gravado).
            $doCronograma = DB::table('tdap_cronogramas')
                ->where('lote_id', $lote->id)
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('municipio_id');

            // `tdap_cronogramas.municipio_id` e NOT NULL com FK valida, entao
            // vai direto para o vinculo.
            foreach ($doCronograma as $id) {
                $ids[(int) $id] = true;
            }

            foreach (array_keys($ids) as $municipioId) {
                $linhas[] = [
                    'lote_id'      => $lote->id,
                    'municipio_id' => $municipioId,
                    'created_at'   => $agora,
                    'updated_at'   => $agora,
                ];
            }
        }

        foreach (array_chunk($linhas, 500) as $chunk) {
            DB::table('tdap_lote_municipios')->insertOrIgnore($chunk);
        }

        // `municipio_id` legado = menor municipio vinculado, so para manter um
        // municipio de referencia coerente no registro do lote.
        DB::statement("
            UPDATE tdap_lotes l
               SET municipio_id = sub.municipio_id
              FROM (
                    SELECT lote_id, MIN(municipio_id) AS municipio_id
                      FROM tdap_lote_municipios
                     GROUP BY lote_id
                   ) sub
             WHERE sub.lote_id = l.id
               AND (l.municipio_id IS NULL OR l.municipio_id <> sub.municipio_id)
        ");

        if ($naoResolvidos !== []) {
            Log::warning('[tdap_lote_municipios] nomes nao resolvidos no backfill', $naoResolvidos);
        }
    }

    /**
     * Extrai os nomes de municipio do texto descritivo do lote.
     *
     * @return array<int, string>
     */
    private function extrairNomes(string $texto): array
    {
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? '');

        if ($texto === '') {
            return [];
        }

        // "...destinado aos municipios de A, B e C." / "...ao municipio de A."
        // Tolera "aosmunicipios" e "municipios do de" vistos na base.
        if (! preg_match('/munic[ií]pios?\b(.*)$/iu', $texto, $m)) {
            return [];
        }

        $lista = ltrim($m[1]);
        // Remove os artigos/preposicoes que antecedem o primeiro nome.
        $lista = preg_replace('/^(?:\s*(?:de|do|da|dos|das)\b)+\s*/iu', '', $lista) ?? $lista;
        $lista = rtrim($lista, " .;");

        if ($lista === '') {
            return [];
        }

        // Nenhum municipio de MG tem " e " no nome, entao ele e sempre separador.
        $partes = preg_split('/\s*,\s*|\s+e\s+/iu', $lista) ?: [];

        return array_values(array_filter(array_map(
            fn (string $p): string => trim($p, " .;"),
            $partes,
        ), fn (string $p): bool => $p !== ''));
    }

    /**
     * @param  array<string, int>  $porNome
     * @param  array<string, int|false>  $porChave
     * @param  array<int, array{id: int, norm: string}>  $lista
     */
    private function resolver(string $candidato, array $porNome, array $porChave, array $lista): ?int
    {
        $norm = $this->normalizar($candidato);

        if ($norm === '') {
            return null;
        }

        if (isset($porNome[$norm])) {
            return $porNome[$norm];
        }

        // A redacao costuma omitir preposicoes ("Rio Prado" para "Rio do
        // Prado"). Comparar sem elas evita cair na distancia de edicao, que
        // escolheria um vizinho parecido ("Rio Preto").
        $chave = $this->chave($candidato);

        if (isset($porChave[$chave]) && $porChave[$chave] !== false) {
            return $porChave[$chave];
        }

        // Truncamento do varchar(150): prefixo com um unico municipio possivel.
        if (mb_strlen($norm) >= 5) {
            $prefixo = array_values(array_filter(
                $lista,
                fn (array $m): bool => str_starts_with($m['norm'], $norm),
            ));

            if (count($prefixo) === 1) {
                return $prefixo[0]['id'];
            }
        }

        // Erro de digitacao na redacao da ata: melhor candidato unico a
        // distancia de edicao <= 2.
        if (mb_strlen($norm) >= 6) {
            $melhor = null;
            $melhorDist = PHP_INT_MAX;
            $empate = false;

            foreach ($lista as $m) {
                $dist = levenshtein($norm, $m['norm']);

                if ($dist < $melhorDist) {
                    $melhorDist = $dist;
                    $melhor = $m['id'];
                    $empate = false;
                } elseif ($dist === $melhorDist) {
                    $empate = true;
                }
            }

            if ($melhor !== null && $melhorDist <= 2 && ! $empate) {
                return $melhor;
            }
        }

        return null;
    }

    /** Nome normalizado e sem preposicoes, para comparacao tolerante. */
    private function chave(string $valor): string
    {
        $tokens = array_filter(
            explode(' ', $this->normalizar($valor)),
            fn (string $t): bool => ! in_array($t, ['de', 'do', 'da', 'dos', 'das', 'd'], true),
        );

        return implode(' ', $tokens);
    }

    private function normalizar(string $valor): string
    {
        $valor = Str::lower(Str::ascii($valor));
        $valor = preg_replace('/[^a-z0-9]+/', ' ', $valor) ?? $valor;

        return trim(preg_replace('/\s+/', ' ', $valor) ?? $valor);
    }
};
