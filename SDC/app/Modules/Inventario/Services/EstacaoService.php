<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\DTOs\EstacaoData;
use App\Modules\Inventario\Models\Estacao;

/** Escritas das estacoes de trabalho (antes direto no EstacaoController). */
class EstacaoService
{
    public function criar(EstacaoData $dados): Estacao
    {
        return Estacao::create($dados->toArray());
    }

    public function atualizar(Estacao $estacao, EstacaoData $dados): Estacao
    {
        $estacao->update($dados->toArray());

        return $estacao;
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\HttpException 409 com equipamentos vinculados */
    public function remover(Estacao $estacao): void
    {
        abort_if($estacao->equipamentos()->exists(), 409, 'A estação possui equipamentos vinculados.');

        $estacao->delete();
    }
}
