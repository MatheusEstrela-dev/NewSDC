<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Observers;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaVistoria;

/**
 * Ao gravar a vistoria do fornecedor, a obra passa a Instalado.
 *
 * No legado isso era efeito colateral dentro do controller
 * (CisternaController.php:1681), junto com a criacao da linha vazia do
 * COMPDEC. Aqui fica no observer, e vale tambem para o refino do ETL.
 */
class CisternaVistoriaObserver
{
    public function created(CisternaVistoria $vistoria): void
    {
        if ($vistoria->etapa !== EtapaVistoria::FORNECEDOR) {
            return;
        }

        $beneficiario = $vistoria->beneficiario;

        if ($beneficiario === null || $beneficiario->situacao_obra === SituacaoObra::INSTALADO) {
            return;
        }

        $beneficiario->update(['situacao_obra' => SituacaoObra::INSTALADO->value]);
    }
}
