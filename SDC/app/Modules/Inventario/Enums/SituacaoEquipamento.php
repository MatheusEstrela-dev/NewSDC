<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

enum SituacaoEquipamento: string
{
    case DISPONIVEL = 'disponivel';
    case EM_USO = 'em_uso';
    case MANUTENCAO = 'manutencao';
    case BAIXADO = 'baixado';

    public function label(): string
    {
        return match ($this) {
            self::DISPONIVEL => 'Disponível',
            self::EM_USO => 'Em uso',
            self::MANUTENCAO => 'Manutenção',
            self::BAIXADO => 'Baixado',
        };
    }

    public static function options(): array
    {
        return array_map(static fn (self $situacao) => [
            'value' => $situacao->value,
            'label' => $situacao->label(),
        ], self::cases());
    }
}
