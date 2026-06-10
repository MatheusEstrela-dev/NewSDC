<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Municipio;
use App\Modules\Tdap\Models\Ata;
use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\Prestador;
use Illuminate\Database\Seeder;

/**
 * Seeder de DEV para o modulo TDAP.
 *
 * Cria 1 Ata vigente, 1 Prestador e 2 Lotes em municipios reais, permitindo
 * testar o fluxo completo do cronograma (Ata -> Lote -> Municipio/Prestador ->
 * Ponto de Captacao). Idempotente. Rode em seguida o PontosCaptacaoMockSeeder.
 */
class TdapTestSeeder extends Seeder
{
    public function run(): void
    {
        $municipios = Municipio::query()->orderBy('nome')->limit(2)->get();

        if ($municipios->count() < 1) {
            $this->command?->warn('Nenhum municipio encontrado. Rode MunicipiosMGSeeder antes.');

            return;
        }

        $prestador = Prestador::updateOrCreate(
            ['cnpj' => '12.345.678/0001-90'],
            [
                'nome'          => 'PRESTADOR TESTE TDAP LTDA',
                'representante' => 'Fulano de Tal',
                'email'         => 'prestador.teste@example.com',
                'tel1'          => '(31) 99999-0000',
                'uf'            => 'MG',
                'ativo'         => true,
            ],
        );

        $ata = Ata::updateOrCreate(
            ['numero' => '0001/2026'],
            [
                'dt_inicio'   => now()->startOfYear()->toDateString(),
                'dt_final'    => now()->endOfYear()->toDateString(),
                'historico'   => 'Ata de teste para validacao do modulo TDAP.',
                'ativo'       => true,
            ],
        );

        foreach ($municipios as $i => $municipio) {
            Lote::updateOrCreate(
                ['ata_id' => $ata->id, 'municipio_id' => $municipio->id],
                [
                    'prestador_id' => $prestador->id,
                    'numero'       => str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'nome'         => 'Lote '.($i + 1).' - '.$municipio->nome,
                    'qtd_agua_m3'  => 1000,
                    'valor_m3'     => 45.50,
                    'ativo'        => true,
                ],
            );
        }

        $this->command?->info('TDAP de teste: 1 ata, 1 prestador, '.$municipios->count().' lote(s) criados.');
    }
}
