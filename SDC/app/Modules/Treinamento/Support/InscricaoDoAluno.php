<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Database\Eloquent\Model;

/**
 * Porta de entrada do aluno na sala de aula, igual para os dois escopos
 * (servidor no SDC, cidadao no portal): decide quem pode ver o curso.
 */
final class InscricaoDoAluno
{
    /**
     * 404 se o curso nao e EAD, nao esta publicado ou o publico nao aceita o
     * inscrito (nao revela a existencia do curso do outro escopo); 403 se nao
     * ha inscricao APROVADA.
     */
    public static function aprovada(Treinamento $treinamento, ?Model $inscrito): Inscricao
    {
        abort_if($inscrito === null || ! $treinamento->isEad() || ! $treinamento->estaPublicado() || ! $treinamento->publico_alvo->aceita($inscrito), 404);

        $inscricao = Inscricao::query()
            ->porTreinamento($treinamento->id)
            ->where('inscrito_type', $inscrito->getMorphClass())
            ->where('inscrito_id', $inscrito->getKey())
            ->where('status', StatusInscricao::APROVADA->value)
            ->first();

        abort_if($inscricao === null, 403, 'Inscricao nao aprovada neste curso.');
        abort_unless($treinamento->situacaoEfetiva()->permiteAcessoDoAluno(), 403, $treinamento->situacaoEfetiva()->mensagemDeBloqueio());

        return $inscricao;
    }

    /**
     * Curso publicado pelo slug do link publico (escopo do portal).
     */
    public static function cursoPublicado(string $slug): Treinamento
    {
        return Treinamento::query()->publicado()->where('link_publico_slug', $slug)->firstOrFail();
    }

    /**
     * Item que pertence ao curso (e a uma pasta ativa); 404 caso contrario.
     */
    public static function item(Treinamento $treinamento, int $itemId): ModuloItem
    {
        return ModuloItem::query()
            ->whereKey($itemId)
            ->whereIn('modulo_id', Modulo::query()->where('treinamento_id', $treinamento->id)->select('id'))
            ->firstOrFail();
    }
}
