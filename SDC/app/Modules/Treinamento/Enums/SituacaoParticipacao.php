<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Situacao de uma participacao (inscricao aprovada) no painel de indicadores.
 * Nao ha coluna: e calculada em SQL a cada consulta. A regra (prazo, conclusao
 * no prazo, desistencia) mora num lugar so: Support\RegrasDaParticipacao.
 */
enum SituacaoParticipacao: string
{
    case CONCLUIDO = 'CONCLUIDO';
    case EM_ANDAMENTO = 'EM_ANDAMENTO';
    case DESISTENTE = 'DESISTENTE';

    public function getLabel(): string
    {
        return match ($this) {
            self::CONCLUIDO => 'Concluído',
            self::EM_ANDAMENTO => 'Em andamento',
            self::DESISTENTE => 'Desistente',
        };
    }

    /** Chave do bloco no payload do painel (concluidos, em_andamento, desistentes). */
    public function chave(): string
    {
        return match ($this) {
            self::CONCLUIDO => 'concluidos',
            self::EM_ANDAMENTO => 'em_andamento',
            self::DESISTENTE => 'desistentes',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn (self $situacao) => [
                'value' => $situacao->value,
                'label' => $situacao->getLabel(),
            ],
            self::cases()
        );
    }
}
