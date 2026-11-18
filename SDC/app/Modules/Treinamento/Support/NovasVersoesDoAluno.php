<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Support\Collection;

/**
 * Para os cards de Meus cursos: de quais inscricoes concluidas a pessoa pode se
 * atualizar para a versao vigente, e o que a vigente traz de novo. Uma consulta
 * para as vigentes e outra para as pastas novas, qualquer que seja o numero de
 * cards; as demais regras (espelho de AtualizacaoDeVersaoService::verificar)
 * usam as inscricoes ja carregadas.
 */
class NovasVersoesDoAluno
{
    /**
     * @param  Collection<int, Inscricao>  $inscricoes  inscricoes EAD da pessoa, com `treinamento`
     * @return array<int, array{id: int, rotulo: string, notas: ?string, itens_novos: int}> inscricao_id => nova versao
     */
    public static function para(Collection $inscricoes): array
    {
        $origens = self::origensConcluidas($inscricoes);
        if ($origens === []) {
            return [];
        }

        $vigentes = self::vigentes(array_keys($origens));
        $novasPorVersao = self::pastasNovas($vigentes->pluck('id')->all());

        $resultado = [];
        foreach ($origens as $cursoId => $origem) {
            $vigente = $vigentes->get($cursoId);
            if ($vigente === null || self::semDireito($inscricoes, $cursoId, $vigente, $origem)) {
                continue;
            }

            $resultado[$origem->id] = [
                'id' => $vigente->id,
                'rotulo' => $vigente->rotuloDeVersao(),
                'notas' => $vigente->versao_notas,
                'itens_novos' => $novasPorVersao[$vigente->id] ?? 0,
            ];
        }

        return $resultado;
    }

    /**
     * A inscricao concluida mais recente de cada curso do catalogo (a "origem" da atualizacao).
     *
     * @param  Collection<int, Inscricao>  $inscricoes
     * @return array<int, Inscricao> curso_id => inscricao
     */
    private static function origensConcluidas(Collection $inscricoes): array
    {
        return $inscricoes
            ->filter(fn (Inscricao $i) => $i->concluido_em !== null && $i->treinamento->curso_id !== null)
            ->sortBy([['concluido_em', 'desc'], ['id', 'desc']])
            ->groupBy(fn (Inscricao $i) => $i->treinamento->curso_id)
            ->map(fn (Collection $grupo) => $grupo->first())
            ->all();
    }

    /**
     * @param  list<int>  $cursoIds
     * @return Collection<int, Treinamento> curso_id => versao vigente publicada
     */
    private static function vigentes(array $cursoIds): Collection
    {
        return Treinamento::query()
            ->whereIn('curso_id', $cursoIds)
            ->where('tipo', TipoTreinamento::EAD->value)
            ->publicado()
            ->vigente()
            ->get()
            ->sortByDesc('versao')
            ->keyBy('curso_id');
    }

    /**
     * Pastas sem nenhum item copiado de versao anterior, por versao.
     *
     * @param  list<int>  $treinamentoIds
     * @return array<int, int> treinamento_id => quantidade
     */
    private static function pastasNovas(array $treinamentoIds): array
    {
        return Modulo::query()
            ->whereIn('treinamento_id', $treinamentoIds)
            ->whereDoesntHave('itens', fn ($q) => $q->whereNotNull('origem_item_id'))
            ->selectRaw('treinamento_id, count(*) as total')
            ->groupBy('treinamento_id')
            ->pluck('total', 'treinamento_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * A vigente nao e mais nova que a origem, a pessoa ja esta nela, ou ha outra
     * inscricao em andamento no curso.
     *
     * @param  Collection<int, Inscricao>  $inscricoes
     */
    private static function semDireito(Collection $inscricoes, int $cursoId, Treinamento $vigente, Inscricao $origem): bool
    {
        if ($origem->treinamento->versao >= $vigente->versao) {
            return true;
        }

        return $inscricoes->contains(fn (Inscricao $i) => $i->treinamento->curso_id === $cursoId && (
            $i->treinamento_id === $vigente->id
            || ($i->concluido_em === null && in_array($i->status, [StatusInscricao::PENDENTE, StatusInscricao::APROVADA], true))
        ));
    }
}
