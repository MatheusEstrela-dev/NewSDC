<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * Quem atende o beneficiario com caminhao pipa. No legado eram cinco
 * colunas varchar(50): respAtDefesaCivil, respAtExercito, respAtParticular,
 * respAtPrefeitura, respAtOutros.
 */
enum ResponsavelPipa: string
{
    case DEFESA_CIVIL = 'defesa_civil';
    case EXERCITO = 'exercito';
    case PARTICULAR = 'particular';
    case PREFEITURA = 'prefeitura';
    case OUTROS = 'outros';

    public function label(): string
    {
        return match ($this) {
            self::DEFESA_CIVIL => 'Defesa Civil',
            self::EXERCITO => 'Exercito',
            self::PARTICULAR => 'Particular',
            self::PREFEITURA => 'Prefeitura',
            self::OUTROS => 'Outros',
        };
    }

    /**
     * Nome da coluna correspondente em sinc_cisterna, para o refino.
     */
    public function colunaLegado(): string
    {
        return match ($this) {
            self::DEFESA_CIVIL => 'respAtDefesaCivil',
            self::EXERCITO => 'respAtExercito',
            self::PARTICULAR => 'respAtParticular',
            self::PREFEITURA => 'respAtPrefeitura',
            self::OUTROS => 'respAtOutros',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
