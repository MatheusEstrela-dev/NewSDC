<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

use App\Models\User;
use App\Modules\Treinamento\Models\Cidadao;
use Illuminate\Database\Eloquent\Model;

enum PublicoAlvo: string
{
    case SERVIDORES = 'SERVIDORES';
    case CIDADAOS = 'CIDADAOS';

    public function getLabel(): string
    {
        return match ($this) {
            self::SERVIDORES => 'Só servidores',
            self::CIDADAOS => 'Só cidadãos',
        };
    }

    public function aceita(Model $inscrito): bool
    {
        return match ($this) {
            self::SERVIDORES => $inscrito instanceof User,
            self::CIDADAOS => $inscrito instanceof Cidadao,
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn (self $publico) => [
                'value' => $publico->value,
                'label' => $publico->getLabel(),
            ],
            self::cases()
        );
    }
}
