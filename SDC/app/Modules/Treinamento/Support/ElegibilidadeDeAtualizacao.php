<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Support\Collection;

/**
 * Regra unica de quem pode se atualizar para a versao vigente, usada pelo
 * servico que executa a atualizacao e pelo card de Meus cursos. Funcao pura
 * sobre as inscricoes da pessoa no curso (com `treinamento` carregado).
 */
final class ElegibilidadeDeAtualizacao
{
    /**
     * A inscricao concluida mais recente em versao anterior a vigente (a "origem").
     *
     * @param  Collection<int, Inscricao>  $doCurso  inscricoes da pessoa em qualquer versao do curso
     */
    public static function origem(Collection $doCurso, Treinamento $vigente): ?Inscricao
    {
        return $doCurso
            ->filter(fn (Inscricao $i) => $i->concluido_em !== null && $i->treinamento->versao < $vigente->versao)
            ->sortBy([['concluido_em', 'desc'], ['id', 'desc']])
            ->first();
    }

    /**
     * Motivo pelo qual a pessoa nao pode se atualizar; null quando pode.
     *
     * @param  Collection<int, Inscricao>  $doCurso
     */
    public static function motivo(Treinamento $vigente, Collection $doCurso): ?string
    {
        if (self::origem($doCurso, $vigente) === null) {
            return 'Voce nao concluiu uma versao anterior deste curso.';
        }

        if ($doCurso->contains(fn (Inscricao $i) => $i->treinamento_id === $vigente->id)) {
            return 'Voce ja possui uma inscricao nesta versao do curso.';
        }

        $emAndamento = $doCurso->contains(fn (Inscricao $i) => $i->concluido_em === null
            && in_array($i->status, [StatusInscricao::PENDENTE, StatusInscricao::APROVADA], true));

        return $emAndamento ? 'Conclua ou cancele a inscricao em andamento neste curso antes de atualizar.' : null;
    }
}
