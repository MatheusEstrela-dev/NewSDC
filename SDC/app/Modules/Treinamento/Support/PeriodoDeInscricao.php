<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Turma;
use DomainException;

/**
 * Periodo de atuacao da inscricao: com turma, vale o periodo da turma; sem
 * turma, valem as datas do curso, quando houver (EAD e o legado podem nao ter
 * data e seguem abertos).
 *
 * SO O LIMITE FINAL RECUSA. Inscricao antes da data de inicio e inscricao
 * antecipada, que o modulo sempre permitiu - publicar um curso PLANEJADO
 * existe justamente para abrir inscricao antes de o curso comecar
 * (TreinamentoService::publicar). Ligar o limite inicial fecharia a inscricao
 * antecipada de todo curso ja cadastrado.
 */
final class PeriodoDeInscricao
{
    public static function exigirAberto(Treinamento $curso, ?Turma $turma): void
    {
        if ($turma !== null) {
            if ($turma->encerrouInscricoes()) {
                throw new DomainException(
                    'As inscricoes da turma "'.$turma->nome.'" encerraram em '.$turma->data_fim->format('d/m/Y').'.',
                );
            }

            return;
        }

        if ($curso->encerrouInscricoes()) {
            throw new DomainException(
                'As inscricoes deste treinamento encerraram em '.$curso->data_fim->format('d/m/Y').'.',
            );
        }
    }
}
