<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Support\Collection;

/**
 * Leituras sobre as versoes de um curso do catalogo. O rotulo da versao so
 * aparece quando o curso tem mais de uma versao publicada (spec, decisao 8).
 */
class VersoesDoCurso
{
    /**
     * @param  iterable<Treinamento>  $treinamentos
     * @return array<int, ?string> treinamento_id => "v2.0" ou null
     */
    public static function rotulos(iterable $treinamentos): array
    {
        $lista = collect($treinamentos);
        $publicadas = self::publicadasPorCurso($lista->pluck('curso_id')->filter()->unique()->values()->all());

        return $lista->mapWithKeys(fn (Treinamento $t) => [
            $t->id => ($publicadas[$t->curso_id] ?? 0) > 1 ? $t->rotuloDeVersao() : null,
        ])->all();
    }

    /** Rotulo de um unico treinamento (ver rotulos). */
    public static function rotuloDe(Treinamento $treinamento): ?string
    {
        return self::rotulos([$treinamento])[$treinamento->id] ?? null;
    }

    /**
     * Todas as versoes do curso, da mais nova para a mais antiga, com
     * `inscritos` e `formados` (inscricao concluida) numa unica consulta.
     *
     * @return Collection<int, Treinamento>
     */
    public static function listar(int $cursoId): Collection
    {
        return Treinamento::query()
            ->where('curso_id', $cursoId)
            ->where('tipo', TipoTreinamento::EAD->value)
            ->withCount([
                'inscricoes as inscritos',
                'inscricoes as formados' => fn ($q) => $q->whereNotNull('concluido_em'),
            ])
            ->orderByDesc('versao')
            ->get();
    }

    /** Versao vigente (publicada de maior numero) do curso, se houver. */
    public static function vigenteDe(int $cursoId): ?Treinamento
    {
        return Treinamento::query()
            ->where('curso_id', $cursoId)
            ->where('tipo', TipoTreinamento::EAD->value)
            ->whereNotNull('publicado_em')
            ->vigente()
            ->first();
    }

    /**
     * @param  list<int>  $cursoIds
     * @return array<int, int> curso_id => quantidade de versoes EAD publicadas
     */
    private static function publicadasPorCurso(array $cursoIds): array
    {
        if ($cursoIds === []) {
            return [];
        }

        return Treinamento::query()
            ->whereIn('curso_id', $cursoIds)
            ->where('tipo', TipoTreinamento::EAD->value)
            ->whereNotNull('publicado_em')
            ->selectRaw('curso_id, count(*) as total')
            ->groupBy('curso_id')
            ->pluck('total', 'curso_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
