<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Evacuacao;

/**
 * Tempos do Anexo E em segundos, exibidos em mm:ss. A fracao de minuto vira
 * segundos (x 60) e arredonda ao segundo, como no exemplo do item 4.2.
 */
final class TempoAnexoE
{
    private const SUFIXO = '_segundos';

    public static function formatar(int|float|null $segundos): ?string
    {
        if ($segundos === null) {
            return null;
        }
        $total = (int) round($segundos);

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }

    public static function paraSegundos(string $mmss): int
    {
        [$minutos, $segundos] = array_map('intval', explode(':', $mmss));

        return $minutos * 60 + $segundos;
    }

    public static function formatarResultado(array $dados): array
    {
        foreach ($dados as $chave => $valor) {
            if (is_array($valor)) {
                $dados[$chave] = self::formatarResultado($valor);
            } elseif (is_string($chave) && str_ends_with($chave, self::SUFIXO)) {
                $dados[substr($chave, 0, -strlen(self::SUFIXO)).'_fmt'] = self::formatar($valor);
            }
        }

        return $dados;
    }
}
