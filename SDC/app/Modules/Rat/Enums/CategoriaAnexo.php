<?php

declare(strict_types=1);

namespace App\Modules\Rat\Enums;

enum CategoriaAnexo: string
{
    case FOTO_VISTORIA = 'foto_vistoria';
    case DOCUMENTO     = 'documento';
    case LAUDO         = 'laudo';
    case IMAGEM        = 'imagem';
    case OUTRO         = 'outro';

    public function getLabel(): string
    {
        return match ($this) {
            self::FOTO_VISTORIA => 'Foto de Vistoria',
            self::DOCUMENTO     => 'Documento',
            self::LAUDO         => 'Laudo',
            self::IMAGEM        => 'Imagem',
            self::OUTRO         => 'Outro',
        };
    }

    public function getStorageFolder(): string
    {
        return match ($this) {
            self::FOTO_VISTORIA, self::IMAGEM => 'fotos',
            self::DOCUMENTO, self::LAUDO      => 'documentos',
            self::OUTRO                       => 'outros',
        };
    }

    public static function toSelectArray(): array
    {
        $array = [];
        foreach (self::cases() as $case) {
            $array[$case->value] = $case->getLabel();
        }
        return $array;
    }
}
