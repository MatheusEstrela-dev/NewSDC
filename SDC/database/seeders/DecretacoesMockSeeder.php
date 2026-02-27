<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Enums\StatusProcesso;
use App\Modules\Decretacoes\Enums\TipoProcesso;
use App\Modules\Decretacoes\Enums\TipoDecreto;
use App\Models\Municipio;
use App\Modules\Compdec\Models\Orgao;
use Carbon\Carbon;

class DecretacoesMockSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("\nCriando Decretacoes (Processos) mockados...\n");

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Processo::truncate();
        DB::table('processo_municipios')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $municipios = [
            'Belo Horizonte',
            'Contagem',
            'Uberlândia',
            'Juiz de Fora',
            'Governador Valadares',
        ];

        $orgao = Orgao::first();
        $orgaoId = $orgao ? $orgao->id : null;

        $muns = DB::table('municipios')->whereIn('nome', $municipios)->get()->keyBy('nome');

        foreach ($municipios as $index => $municipio) {
            $isVigente = $index % 2 === 0;
            
            $processo = Processo::create([
                'orgao_responsavel_id' => $orgaoId,
                'data_entrada' => Carbon::now()->subDays(10 + $index)->format('Y-m-d'),
                'data_ocorrencia_desastre' => Carbon::now()->subDays(15 + $index)->format('Y-m-d'),
                'processo' => $index % 2 === 0 ? TipoProcesso::MUNICIPAL->value : TipoProcesso::ESTADUAL->value,
                'tipo_decreto' => $index % 3 === 0 ? TipoDecreto::ECP->value : TipoDecreto::SE->value,
                'status' => $index === 0 ? StatusProcesso::REGISTRO->value : StatusProcesso::RECONHECIDO->value,
                'n_protocolo_fide' => 'FIDE-2026-00' . ($index + 1),
                'decreto_municipal' => 'DEC-2026-10' . $index,
                'prazo_vigencia' => 180,
                'data_decreto_municipal' => Carbon::now()->subDays(8 + $index)->format('Y-m-d'),
                'data_publicacao_mg' => Carbon::now()->subDays(5 + $index)->format('Y-m-d'),
                'tipo_desastre_id' => 1,
                'tipo_desastre_nome' => 'Inundação',
            ]);

            if (isset($muns[$municipio])) {
                $processo->municipios()->attach($muns[$municipio]->id, [
                    'n_protocolo_fide' => 'FIDE-2026-ATTACH-' . $index,
                ]);
            }
        }

        $this->command->info("Processos criados com sucesso: " . count($municipios));
    }
}
