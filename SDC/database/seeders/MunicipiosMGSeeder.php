<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Popula a tabela municipios com os 853 municipios de Minas Gerais.
 *
 * Fonte: database/data/municipios_mg.json (gerado a partir de
 * kelvins/municipios-brasileiros + API de localidades do IBGE).
 *
 * Idempotente: usa updateOrInsert por codigo_ibge, seguro para reexecutar
 * e para rodar em producao sem gerar duplicidade.
 */
class MunicipiosMGSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/municipios_mg.json');

        if (! is_file($path)) {
            $this->command->error("Arquivo de dados nao encontrado: {$path}");

            return;
        }

        $registros = json_decode((string) file_get_contents($path), true);

        if (! is_array($registros) || $registros === []) {
            $this->command->error('Dataset de municipios vazio ou invalido.');

            return;
        }

        $now = now();

        $rows = array_map(static fn (array $r): array => [
            'codigo_ibge' => $r['codigo_ibge'],
            'nome' => $r['nome'],
            'uf' => $r['uf'],
            'regiao' => $r['regiao'],
            'mesorregiao' => $r['mesorregiao'],
            'microrregiao' => $r['microrregiao'],
            'latitude' => $r['latitude'],
            'longitude' => $r['longitude'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $registros);

        // upsert por codigo_ibge: atualiza dados mas preserva created_at em re-execucoes.
        $updateCols = ['nome', 'uf', 'regiao', 'mesorregiao', 'microrregiao', 'latitude', 'longitude', 'updated_at'];

        DB::transaction(function () use ($rows, $updateCols): void {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('municipios')->upsert($chunk, ['codigo_ibge'], $updateCols);
            }
        });

        $total = DB::table('municipios')->where('uf', 'MG')->count();
        $this->command->info("Municipios MG semeados/atualizados: {$total} (esperado 853).");
    }
}
