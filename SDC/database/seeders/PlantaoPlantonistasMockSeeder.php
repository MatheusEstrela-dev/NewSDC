<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Plantao\Models\Plantonista;
use Illuminate\Database\Seeder;

/**
 * Marca servidores JA CADASTRADOS como plantonistas, para homologar a escala
 * com a base real em vez de contas inventadas.
 *
 * Nao cria usuario nenhum. Seleciona entre os internos ativos -- os que tem
 * cargo operacional e e-mail institucional -- porque a base tem milhares de
 * contas COMPDEC municipais que nunca fazem plantao no Predio Alterosas, e
 * escala-las nao homologaria nada.
 *
 * NAO DESTRUTIVO, ao contrario do PlantoesMockSeeder, que faz `truncate()` na
 * tabela de turnos. Aqui e tudo firstOrNew: rodar de novo nao duplica ninguem,
 * e o posto ajustado a mao pela tela nunca e sobrescrito.
 *
 * O posto vem do cargo do usuario porque `users` nao tem patente -- e nem deve
 * ter, sendo tabela transversal a todo o SDC. E um ponto de partida plausivel
 * para o teste; a patente real se corrige na tela de Plantonistas.
 */
class PlantaoPlantonistasMockSeeder extends Seeder
{
    /**
     * Cargos que de fato operam o plantao, e a patente sugerida para cada um.
     * A ordem importa: e a prioridade de selecao quando ha mais candidatos que
     * vagas de teste.
     */
    private const CARGOS = [
        'operator' => 'Cb',
        'analyst' => '2Sgt',
        'manager' => 'Ten',
    ];

    /**
     * Time de teste enxuto de proposito: escala de mes com trinta nomes nao se
     * confere de olho.
     */
    private const LIMITE_POR_CARGO = 3;

    public function run(): void
    {
        $marcados = 0;
        $jaExistiam = 0;

        foreach (self::CARGOS as $cargo => $posto) {
            $candidatos = User::query()
                ->whereHas('roles', fn ($q) => $q->where('slug', $cargo))
                ->where('active', true)
                // Institucional: filtra as contas de municipio e as de dominio
                // de teste de outros modulos.
                ->where('email', 'like', '%@defesa.mg.gov.br')
                ->orderBy('id')
                ->limit(self::LIMITE_POR_CARGO)
                ->get();

            foreach ($candidatos as $usuario) {
                $plantonista = Plantonista::firstOrNew(['user_id' => $usuario->id]);

                if ($plantonista->exists) {
                    $jaExistiam++;

                    continue;
                }

                $plantonista->posto = $posto;
                $plantonista->ativo = true;
                $plantonista->save();

                $marcados++;
                $this->command?->line("  + {$posto} {$usuario->name}");
            }
        }

        $this->promoverPrimeiroUsuarioReal();

        $this->command?->info(sprintf(
            'Plantonistas: %d no total (%d marcados agora, %d ja estavam).',
            Plantonista::count(),
            $marcados,
            $jaExistiam,
        ));
    }

    /**
     * O dono do sistema precisa estar na lista para conseguir assumir a propria
     * vaga: a vaga e pessoal, e so o escalado a assume. Sem isso nao ha como
     * exercitar o fluxo de ponta a ponta, porque nao se loga nas contas alheias.
     */
    private function promoverPrimeiroUsuarioReal(): void
    {
        $admin = User::query()->orderBy('id')->first();

        if ($admin === null) {
            return;
        }

        $plantonista = Plantonista::firstOrNew(['user_id' => $admin->id]);

        if ($plantonista->exists) {
            return;
        }

        $plantonista->posto = 'Cel';
        $plantonista->ativo = true;
        $plantonista->save();

        $this->command?->line("  + Cel {$admin->name} (dono do sistema)");
    }
}
