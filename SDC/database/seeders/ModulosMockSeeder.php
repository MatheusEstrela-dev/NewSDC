<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ModulosMockSeeder extends Seeder
{
    /**
     * Seeder de módulos (aulas) para os treinamentos
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Módulos para "Primeiros Socorros em Emergências" (treinamento_id = 1)
        $modulos = [
            // Treinamento 1: Primeiros Socorros (40h)
            [
                'treinamento_id' => 1,
                'titulo' => 'Introdução aos Primeiros Socorros',
                'descricao' => 'Conceitos básicos, cadeia de sobrevivência, avaliação de cena e biossegurança.',
                'ordem' => 1,
                'carga_horaria' => 4,
                'data_prevista' => $now->copy()->subMonths(3)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonths(4),
                'updated_at' => $now->copy()->subMonths(4),
            ],
            [
                'treinamento_id' => 1,
                'titulo' => 'Reanimação Cardiopulmonar (RCP)',
                'descricao' => 'Técnicas de RCP para adultos, crianças e bebês. Uso de DEA.',
                'ordem' => 2,
                'carga_horaria' => 8,
                'data_prevista' => $now->copy()->subMonths(3)->addDay()->format('Y-m-d'),
                'created_at' => $now->copy()->subMonths(4),
                'updated_at' => $now->copy()->subMonths(4),
            ],
            [
                'treinamento_id' => 1,
                'titulo' => 'Controle de Hemorragias e Ferimentos',
                'descricao' => 'Técnicas de controle de sangramentos, curativos e torniquetes.',
                'ordem' => 3,
                'carga_horaria' => 6,
                'data_prevista' => $now->copy()->subMonths(3)->addDays(2)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonths(4),
                'updated_at' => $now->copy()->subMonths(4),
            ],
            [
                'treinamento_id' => 1,
                'titulo' => 'Imobilização e Transporte de Vítimas',
                'descricao' => 'Técnicas de imobilização de fraturas, lesões de coluna e transporte seguro.',
                'ordem' => 4,
                'carga_horaria' => 8,
                'data_prevista' => $now->copy()->subMonths(3)->addDays(3)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonths(4),
                'updated_at' => $now->copy()->subMonths(4),
            ],
            [
                'treinamento_id' => 1,
                'titulo' => 'Triagem de Vítimas em Massa (START)',
                'descricao' => 'Protocolo START para triagem rápida em situações com múltiplas vítimas.',
                'ordem' => 5,
                'carga_horaria' => 6,
                'data_prevista' => $now->copy()->subMonths(3)->addDays(4)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonths(4),
                'updated_at' => $now->copy()->subMonths(4),
            ],
            [
                'treinamento_id' => 1,
                'titulo' => 'Prática e Simulação',
                'descricao' => 'Simulações práticas de cenários de desastres com múltiplas vítimas.',
                'ordem' => 6,
                'carga_horaria' => 8,
                'data_prevista' => $now->copy()->subMonths(3)->addDays(5)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonths(4),
                'updated_at' => $now->copy()->subMonths(4),
            ],

            // Treinamento 3: Sistema de Comando de Incidentes (60h)
            [
                'treinamento_id' => 3,
                'titulo' => 'Fundamentos do SCI',
                'descricao' => 'História, conceitos, terminologia e organização do Sistema de Comando de Incidentes.',
                'ordem' => 1,
                'carga_horaria' => 8,
                'data_prevista' => $now->copy()->subDays(10)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 3,
                'titulo' => 'Estrutura e Funções do SCI',
                'descricao' => 'Comando, operações, planejamento, logística e finanças/administração.',
                'ordem' => 2,
                'carga_horaria' => 12,
                'data_prevista' => $now->copy()->subDays(8)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 3,
                'titulo' => 'Plano de Ação do Incidente (PAI)',
                'descricao' => 'Elaboração e gerenciamento do Plano de Ação do Incidente.',
                'ordem' => 3,
                'carga_horaria' => 10,
                'data_prevista' => $now->copy()->subDays(5)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 3,
                'titulo' => 'Comunicação e Documentação',
                'descricao' => 'Protocolos de comunicação, registro de informações e documentação de incidentes.',
                'ordem' => 4,
                'carga_horaria' => 8,
                'data_prevista' => $now->copy()->subDays(3)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 3,
                'titulo' => 'Exercícios Práticos de Mesa',
                'descricao' => 'Simulações de gabinete com cenários variados de incidentes.',
                'ordem' => 5,
                'carga_horaria' => 12,
                'data_prevista' => $now->copy()->subDay()->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 3,
                'titulo' => 'Exercício Final de Campo',
                'descricao' => 'Simulação prática em campo com implementação completa do SCI.',
                'ordem' => 6,
                'carga_horaria' => 10,
                'data_prevista' => $now->copy()->addDays(2)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],

            // Treinamento 4: Análise de Risco (32h - EAD)
            [
                'treinamento_id' => 4,
                'titulo' => 'Conceitos de Risco e Desastre',
                'descricao' => 'Definições, classificação de desastres, ameaças e vulnerabilidades.',
                'ordem' => 1,
                'carga_horaria' => 4,
                'data_prevista' => $now->copy()->subDays(15)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 4,
                'titulo' => 'Metodologias de Análise de Risco',
                'descricao' => 'Diferentes metodologias para identificação e análise de riscos.',
                'ordem' => 2,
                'carga_horaria' => 8,
                'data_prevista' => $now->copy()->subDays(12)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 4,
                'titulo' => 'Uso de SIG para Mapeamento',
                'descricao' => 'Introdução a Sistemas de Informação Geográfica aplicados à análise de risco.',
                'ordem' => 3,
                'carga_horaria' => 10,
                'data_prevista' => $now->copy()->subDays(8)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 4,
                'titulo' => 'Elaboração de Mapas de Risco',
                'descricao' => 'Técnicas práticas para elaboração de mapas de risco municipais.',
                'ordem' => 4,
                'carga_horaria' => 6,
                'data_prevista' => $now->copy()->subDays(5)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 4,
                'titulo' => 'Projeto Final',
                'descricao' => 'Desenvolvimento de análise de risco para um município fictício.',
                'ordem' => 5,
                'carga_horaria' => 4,
                'data_prevista' => $now->copy()->addDays(10)->format('Y-m-d'),
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subMonth(),
            ],

            // Treinamento 5: Operação de Drones (16h)
            [
                'treinamento_id' => 5,
                'titulo' => 'Introdução aos VANTs',
                'descricao' => 'Tipos de drones, componentes, legislação e segurança operacional.',
                'ordem' => 1,
                'carga_horaria' => 4,
                'data_prevista' => $now->copy()->addMonth()->format('Y-m-d'),
                'created_at' => $now->copy()->subWeeks(2),
                'updated_at' => $now->copy()->subWeeks(2),
            ],
            [
                'treinamento_id' => 5,
                'titulo' => 'Pilotagem Básica',
                'descricao' => 'Técnicas de decolagem, voo estacionário, navegação e pouso.',
                'ordem' => 2,
                'carga_horaria' => 6,
                'data_prevista' => $now->copy()->addMonth()->addDay()->format('Y-m-d'),
                'created_at' => $now->copy()->subWeeks(2),
                'updated_at' => $now->copy()->subWeeks(2),
            ],
            [
                'treinamento_id' => 5,
                'titulo' => 'Missões de Emergência',
                'descricao' => 'Planejamento e execução de missões de busca, mapeamento e avaliação de danos.',
                'ordem' => 3,
                'carga_horaria' => 4,
                'data_prevista' => $now->copy()->addMonth()->addDays(2)->format('Y-m-d'),
                'created_at' => $now->copy()->subWeeks(2),
                'updated_at' => $now->copy()->subWeeks(2),
            ],
            [
                'treinamento_id' => 5,
                'titulo' => 'Processamento de Imagens',
                'descricao' => 'Análise e processamento de imagens aéreas para geração de relatórios.',
                'ordem' => 4,
                'carga_horaria' => 2,
                'data_prevista' => $now->copy()->addMonth()->addDays(2)->format('Y-m-d'),
                'created_at' => $now->copy()->subWeeks(2),
                'updated_at' => $now->copy()->subWeeks(2),
            ],
        ];

        DB::table('modulos')->insert($modulos);

        $this->command->info('✓ ' . count($modulos) . ' módulos criados para os treinamentos!');
    }
}
