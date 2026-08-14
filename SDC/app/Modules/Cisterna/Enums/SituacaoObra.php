<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * Ciclo de execucao fisica da obra. Substitui a coluna `estado` (0..2) do
 * legado. Ortogonal a SituacaoAnalise.
 */
enum SituacaoObra: string
{
    case PROCESSAMENTO = 'processamento';
    case ENVIO_INSTALACAO = 'envio_instalacao';
    case INSTALADO = 'instalado';

    public function label(): string
    {
        return match ($this) {
            self::PROCESSAMENTO => 'Processamento',
            self::ENVIO_INSTALACAO => 'Envio Instalacao',
            self::INSTALADO => 'Instalado',
        };
    }

    public static function doLegado(int|string|null $codigo): self
    {
        return match ((string) $codigo) {
            '1' => self::ENVIO_INSTALACAO,
            '2' => self::INSTALADO,
            default => self::PROCESSAMENTO,
        };
    }

    /**
     * Situacoes que o fornecedor externo pode ver, conforme
     * CisternaController.php:75 do legado.
     *
     * @return array<int, string>
     */
    public static function visiveisAoFornecedor(): array
    {
        return [self::ENVIO_INSTALACAO->value, self::INSTALADO->value];
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
