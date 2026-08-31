<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Grau de parentesco do membro da familia com o responsavel pelo cadastro.
 *
 * O MembroFamilia ja fazia cast desta classe e importava este namespace, mas o
 * arquivo nunca existiu: qualquer leitura ou escrita de membro estourava
 * InvalidCastException. Passou despercebido porque a tabela esta vazia.
 *
 * Os valores sao os documentados no comentario da migration que criou a
 * coluna (2025_12_28_120200_create_membros_familia_table), em minusculas como
 * nos demais enums do modulo.
 */
enum Parentesco: string
{
    case CONJUGE = 'conjuge';
    case FILHO = 'filho';
    case PAI = 'pai';
    case MAE = 'mae';
    case IRMAO = 'irmao';
    case AVO = 'avo';
    case NETO = 'neto';
    case OUTRO = 'outro';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function toSelectArray(): array
    {
        return [
            ['value' => self::CONJUGE->value, 'label' => 'Cônjuge'],
            ['value' => self::FILHO->value, 'label' => 'Filho(a)'],
            ['value' => self::PAI->value, 'label' => 'Pai'],
            ['value' => self::MAE->value, 'label' => 'Mãe'],
            ['value' => self::IRMAO->value, 'label' => 'Irmão(ã)'],
            ['value' => self::AVO->value, 'label' => 'Avô(ó)'],
            ['value' => self::NETO->value, 'label' => 'Neto(a)'],
            ['value' => self::OUTRO->value, 'label' => 'Outro'],
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::CONJUGE => 'Cônjuge',
            self::FILHO   => 'Filho(a)',
            self::PAI     => 'Pai',
            self::MAE     => 'Mãe',
            self::IRMAO   => 'Irmão(ã)',
            self::AVO     => 'Avô(ó)',
            self::NETO    => 'Neto(a)',
            self::OUTRO   => 'Outro',
        };
    }
}
