<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Reordenacao por lista de ids (arrastar e soltar do professor).
 */
final class Ordenacao
{
    /**
     * Grava ordem 1..n na sequencia recebida; a lista tem de ser exatamente o
     * conjunto de ids ativos.
     *
     * @param  class-string<Model>  $model
     * @param  list<int|string>  $ativos
     * @param  list<int|string>  $ids
     */
    public static function aplicar(string $model, array $ativos, array $ids): void
    {
        $ativos = array_map('intval', $ativos);
        $ids = array_map('intval', $ids);

        $igual = count($ids) === count($ativos)
            && count(array_unique($ids)) === count($ids)
            && array_diff($ids, $ativos) === [];

        if (! $igual) {
            throw new \DomainException('A lista de ordenacao nao corresponde aos itens atuais.');
        }

        ConexaoTreinamento::transacao(function () use ($model, $ids) {
            foreach ($ids as $posicao => $id) {
                $model::query()->whereKey($id)->update(['ordem' => $posicao + 1]);
            }
        });
    }
}
