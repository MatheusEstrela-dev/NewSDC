<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\Inscricao;

class CertificadoService
{
    /**
     * Condicao tripla: inscricao aprovada + frequencia minima atingida +
     * treinamento concluido. Chamada apos cada registro de presenca e apos
     * a finalizacao do treinamento (a ordem em que as tres condicoes se
     * completam nao importa).
     */
    public function emitirSeElegivel(Inscricao $inscricao): ?Certificado
    {
        $inscricao->loadMissing('treinamento');

        $elegivel = $inscricao->status === StatusInscricao::APROVADA
            && $inscricao->treinamento->status === StatusTreinamento::CONCLUIDO
            && $inscricao->estaAprovadoPorFrequencia();

        if (!$elegivel) {
            return null;
        }

        $certificado = Certificado::firstOrCreate(
            ['inscricao_id' => $inscricao->id],
            ['treinamento_id' => $inscricao->treinamento_id]
        );

        if ($certificado->status->value !== 'GERADO') {
            $certificado->marcarComoGerado();
        }

        return $certificado;
    }

    public function reemitir(Certificado $certificado): Certificado
    {
        $certificado->marcarComoGerado();
        return $certificado;
    }
}
