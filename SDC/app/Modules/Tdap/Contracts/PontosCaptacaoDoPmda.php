<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Contracts;

use App\Modules\Tdap\DTOs\VinculoPmdaDTO;

/**
 * Pontos de captacao que o PMDA do municipio autoriza.
 *
 * Unico ponto em que o TDAP depende do PMDA, e so para leitura. A
 * implementacao mora no modulo PMDA (quem sabe o que e um plano "aprovado")
 * e e ligada no PmdaServiceProvider.
 */
interface PontosCaptacaoDoPmda
{
    /**
     * Pontos ativos vinculados, com situacao ATIVO, ao PMDA vigente de cada
     * municipio -- o plano APROVADO/ATENDIDO mais recente.
     *
     * @param  list<int>  $municipioIds
     * @return array<int, VinculoPmdaDTO> indexado pelo id do ponto
     */
    public function vinculosVigentes(array $municipioIds): array;

    /**
     * Protocolo de cada plano, vigente ou nao -- e o que identifica o PMDA
     * para quem le o cronograma.
     *
     * @param  list<int>  $pmdaPlanoIds
     * @return array<int, ?string> id do plano => protocolo
     */
    public function protocolos(array $pmdaPlanoIds): array;
}
