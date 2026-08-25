<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Cobrade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carrega em `dec_cobrade` os 65 codigos oficiais do COBRADE.
 *
 * Idempotente e casado por `codigo`: os `id` existentes nunca sao renumerados,
 * porque `legado_rat.cobrade_id` e `pedidos_ah.cobrade_id` apontam para eles.
 * Codigos ausentes sao inseridos; os presentes tem `nome` e `descricao`
 * reescritos com o texto oficial (o banco herdou descricoes cortadas em 45
 * caracteres e `nome` vazio em 65 das 66 linhas).
 *
 * O codigo 3.0.0.0.0 ("Outros") nao faz parte do padrao nacional: e um
 * acrescimo do SDC, preservado aqui para nao invalidar registros antigos.
 */
class CobradeSeeder extends Seeder
{
    public function run(): void
    {
        $oficiais = Cobrade::oficiais();

        if ($oficiais === []) {
            $this->command?->warn('CobradeSeeder ignorado: app/Enums/classificacao_desastres.php nao encontrado.');

            return;
        }

        $atualizados = 0;
        $inseridos   = 0;

        foreach ($oficiais as $codigo => $texto) {
            $afetadas = DB::table('dec_cobrade')
                ->where('codigo', $codigo)
                ->update([
                    'nome'      => $texto['nome'],
                    'descricao' => $texto['descricao'],
                    'grupo'     => $texto['grupo'],
                ]);

            if ($afetadas > 0) {
                $atualizados++;

                continue;
            }

            DB::table('dec_cobrade')->insert([
                'codigo'    => $codigo,
                'nome'      => $texto['nome'],
                'descricao' => $texto['descricao'],
                'grupo'     => $texto['grupo'],
            ]);

            $inseridos++;
        }

        $this->garanteOutros();

        $this->command?->info(
            "COBRADE sincronizado: {$atualizados} atualizados, {$inseridos} inseridos, "
            . count($oficiais) . ' codigos oficiais.'
        );
    }

    /**
     * Mantem a opcao "Outros" utilizavel na tela, com nome preenchido.
     */
    private function garanteOutros(): void
    {
        $existe = DB::table('dec_cobrade')->where('codigo', Cobrade::CODIGO_OUTROS)->exists();

        $dados = [
            'nome'      => 'Outros (discriminar no historico)',
            'descricao' => 'Ocorrencia sem enquadramento na tabela oficial do COBRADE. Detalhar no historico do registro.',
            // Grupo proprio: sem ele a opcao nao apareceria na cascata da tela,
            // que lista eventos a partir do grupo escolhido.
            'grupo'     => 'Outros',
        ];

        if ($existe) {
            DB::table('dec_cobrade')->where('codigo', Cobrade::CODIGO_OUTROS)->update($dados);

            return;
        }

        DB::table('dec_cobrade')->insert($dados + ['codigo' => Cobrade::CODIGO_OUTROS]);
    }
}
