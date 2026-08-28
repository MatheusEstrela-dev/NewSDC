<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Services\PlantaoService;
use Illuminate\Http\RedirectResponse;

/**
 * Exclusao do turno.
 *
 * Fechava o ultimo TODO decorativo da coluna ACOES: o icone de lixeira existia
 * desde antes desta release e emitia um evento que NENHUM listener escutava --
 * o clique simplesmente sumia no caminho, sem erro e sem efeito.
 *
 * EXCLUSAO SUAVE, no padrao do projeto (mesma tratativa do PAE): o registro sai
 * das listagens mas continua no banco. Um turno carrega passagem de servico e
 * aceite formal de duas partes; apagar de verdade destruiria a prova de um
 * acordo entre duas pessoas.
 *
 * Efeito colateral desejado: o indice unico parcial plantoes_turno_ativo_unico
 * e `(data, periodo) WHERE status = 'ATIVO' AND deleted_at IS NULL`, entao
 * excluir um turno aberto por engano LIBERA a vaga para reabrir no mesmo
 * periodo -- que e justamente o motivo de alguem excluir.
 *
 * A vaga da escala, se houver, volta a ficar assumivel: o `escala_item_id`
 * aponta para um item que continua ESCALADO, e o proprio plantonista pode
 * assumir de novo.
 */
class PlantaoDestroyController extends Controller
{
    public function __construct(
        private readonly PlantaoService $plantaoService
    ) {
    }

    public function __invoke(Plantao $plantao): RedirectResponse
    {
        $rotulo = $plantao->data?->format('d/m/Y') ?? '';

        $this->plantaoService->delete($plantao->id);

        return back()->with(
            'success',
            trim("Turno de {$rotulo} excluido. Os dados foram preservados para auditoria.")
        );
    }
}
