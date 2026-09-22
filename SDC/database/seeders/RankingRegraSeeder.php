<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Ranking\Models\Regra;
use Illuminate\Database\Seeder;

final class RankingRegraSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::catalogo() as $regra) {
            // Reexecucao preserva versoes e ativacoes aprovadas anteriormente.
            Regra::query()->firstOrCreate(
                ['rule_key' => $regra['rule_key'], 'versao' => $regra['versao']],
                $regra,
            );
        }
    }

    public static function catalogo(): array
    {
        return \App\Modules\Ranking\Support\CatalogoRegras::todos();
    }
}
