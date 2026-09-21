<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Support;

use LogicException;

final class RankingDatabaseGuard
{
    /** @param array<string, mixed> $target @param array<string, mixed> $operational @param array<string, mixed> $source */
    public static function assertDedicated(array $target, array $operational, array $source): void
    {
        $database = trim((string) ($target['database'] ?? ''));

        if (($target['driver'] ?? null) !== 'pgsql' || $database === '' || ! empty($target['url'])) {
            throw new LogicException('Ranking exige conexao PostgreSQL explicita para uma database propria.');
        }

        foreach (['sdc', $operational['database'] ?? null, $source['database'] ?? null] as $origin) {
            if (is_string($origin) && strcasecmp($database, trim($origin)) === 0) {
                throw new LogicException('A database de Ranking deve ser diferente da origem e da database operacional.');
            }
        }
    }
}
