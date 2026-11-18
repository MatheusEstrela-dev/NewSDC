<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Support\Collection;

/**
 * Para os cards de Meus cursos: de quais inscricoes concluidas a pessoa pode se
 * atualizar para a versao vigente, e o que a vigente traz de novo. Uma consulta
 * para as vigentes e outra para as pastas novas, qualquer que seja o numero de
 * cards; a elegibilidade (ElegibilidadeDeAtualizacao, a mesma do servico de
 * atualizacao) usa as inscricoes ja carregadas.
 */
class NovasVersoesDoAluno
{
    /**
     * @param  Collection<int, Inscricao>  $inscricoes  inscricoes EAD da pessoa, com `treinamento`
     * @return array<int, array{id: int, rotulo: string, notas: ?string, itens_novos: int}> inscricao_id => nova versao
     */
    public static function para(Collection $inscricoes): array
    {
        $cursoIds = $inscricoes
            ->filter(fn (Inscricao $i) => $i->concluido_em !== null && $i->treinamento->curso_id !== null)
            ->map(fn (Inscricao $i) => $i->treinamento->curso_id)
            ->unique()
            ->values()
            ->all();
        if ($cursoIds === []) {
            return [];
        }

        $vigentes = self::vigentes($cursoIds);
        $novasPorVersao = self::pastasNovas($vigentes->pluck('id')->all());

        $resultado = [];
        foreach ($vigentes as $cursoId => $vigente) {
            $doCurso = $inscricoes->filter(fn (Inscricao $i) => $i->treinamento->curso_id === $cursoId);
            $origem = ElegibilidadeDeAtualizacao::origem($doCurso, $vigente);
            if ($origem === null || ElegibilidadeDeAtualizacao::motivo($vigente, $doCurso) !== null) {
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
}
