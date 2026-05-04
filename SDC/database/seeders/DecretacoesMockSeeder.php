<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Enums\StatusProcesso;
use Carbon\Carbon;

class DecretacoesMockSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("\nCriando Decretacoes (Processos) mockados...\n");

        DB::table('dec_decreto_municipios')->truncate();
        Processo::truncate();

        // Busca municipios reais da tabela correta
        $municipioIds = DB::table('cedec_municipio')
            ->whereIn('p_nome', ['Belo Horizonte', 'Contagem', 'Uberlandia', 'Juiz de Fora', 'Governador Valadares'])
            ->pluck('id', 'p_nome');

        // Fallback: pega os 5 primeiros se nao encontrar pelos nomes
        if ($municipioIds->isEmpty()) {
            $municipioIds = DB::table('cedec_municipio')->limit(5)->pluck('id', 'p_nome');
        }

        $municipioList = $municipioIds->keys()->toArray();
        if (empty($municipioList)) {
            $municipioList = ['Mock A', 'Mock B', 'Mock C', 'Mock D', 'Mock E'];
        }

        $reconhecimentos = [
            StatusProcesso::REGISTRO->value,
            StatusProcesso::AGUARDANDO_ANALISE_ESTADO->value,
            StatusProcesso::EM_ANALISE_ESTADO->value,
            StatusProcesso::RECONHECIDO_ESTADO_AG_UNIAO->value,
            StatusProcesso::RECONHECIDO_ESTADO_E_UNIAO->value,
        ];

        $processos = [
            'processo' => ['MUNICIPAL', 'ESTADUAL'],
            'tipo_desastre' => ['SE', 'N1'],
        ];

        foreach ($municipioList as $index => $municipioNome) {
            $processo = Processo::create([
                'data_entrada'             => Carbon::now()->subDays(10 + $index)->format('Y-m-d'),
                'data_ocorrencia_desastre' => Carbon::now()->subDays(15 + $index)->format('Y-m-d'),
                'processo'                 => $processos['processo'][$index % 2],
                'tipo_desastre'            => $processos['tipo_desastre'][$index % 2],
                'tipo_desastre_id'         => $index + 1,
                'reconhecimento'           => $reconhecimentos[$index % count($reconhecimentos)],
                'n_protocolo_fide'         => 'MG-F-' . str_pad($index + 1, 7, '0', STR_PAD_LEFT) . '-14120-20260101',
                'decreto_municipal'        => 'DEC-2026-10' . ($index + 1),
                'prazo_vigencia'           => 180,
                'data_decreto_municipal'   => Carbon::now()->subDays(8 + $index)->format('Y-m-d'),
                'data_publicacao_mg'       => Carbon::now()->subDays(5 + $index)->format('Y-m-d'),
                'analista'                 => 'Analista ' . chr(65 + $index),
                'observacoes'              => 'Processo mock gerado pelo seeder para testes.',
                'created_by'              => 1,
            ]);

            // Vincula municipio usando DecretoMunicipio (relacao correta)
            $municipioId = $municipioIds->get($municipioNome);
            if ($municipioId) {
                DecretoMunicipio::create([
                    'entrada_processos_id' => $processo->id,
                    'municipio_id'         => $municipioId,
                    'n_protocolo_fide'     => $processo->n_protocolo_fide,
                ]);
            }

            $this->command->info("  Processo #{$processo->id} criado — {$municipioNome}");
        }

        $this->command->info("\nProcessos mock criados com sucesso: " . count($municipioList) . "\n");
    }
}
