<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TreinamentosMockSeeder extends Seeder
{
    /**
     * Seeder de dados fictícios para o módulo Treinamentos
     */
    public function run(): void
    {
        $now = Carbon::now();

        $treinamentos = [
            // Treinamentos Concluídos
            [
                'titulo' => 'Primeiros Socorros em Emergências',
                'descricao' => 'Curso completo de primeiros socorros voltado para situações de desastres naturais e emergências civis. Aborda técnicas de RCP, imobilização, controle de hemorragias e triagem de vítimas.',
                'carga_horaria' => 40,
                'tipo' => 'PRESENCIAL',
                'status' => 'CONCLUIDO',
                'instrutor' => 'Dr. Carlos Henrique Silva',
                'local' => 'Centro de Treinamento CEDEC - Belo Horizonte/MG',
                'data_inicio' => $now->copy()->subMonths(3)->format('Y-m-d'),
                'data_fim' => $now->copy()->subMonths(3)->addDays(5)->format('Y-m-d'),
                'numero_vagas' => 30,
                'percentual_frequencia_minimo' => 75.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subMonths(4),
                'updated_at' => $now->copy()->subMonths(2),
            ],
            [
                'titulo' => 'Gestão de Abrigos Temporários',
                'descricao' => 'Capacitação em gestão e organização de abrigos para populações afetadas por desastres. Inclui logística, controle de recursos, gestão de voluntários e atendimento psicossocial.',
                'carga_horaria' => 24,
                'tipo' => 'HIBRIDO',
                'status' => 'CONCLUIDO',
                'instrutor' => 'Ana Paula Rodrigues',
                'local' => 'Plataforma EAD + Encontro Presencial em Juiz de Fora/MG',
                'data_inicio' => $now->copy()->subMonths(2)->format('Y-m-d'),
                'data_fim' => $now->copy()->subMonths(2)->addDays(3)->format('Y-m-d'),
                'numero_vagas' => 50,
                'percentual_frequencia_minimo' => 80.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subMonths(3),
                'updated_at' => $now->copy()->subMonths(1),
            ],

            // Treinamentos Em Andamento
            [
                'titulo' => 'Sistema de Comando de Incidentes (SCI)',
                'descricao' => 'Treinamento no Sistema de Comando de Incidentes (ICS) para coordenação de operações de resposta a emergências. Baseado nas normas internacionais NIMS.',
                'carga_horaria' => 60,
                'tipo' => 'PRESENCIAL',
                'status' => 'EM_ANDAMENTO',
                'instrutor' => 'Cel. Roberto Andrade',
                'local' => 'Academia de Bombeiros Militar - BH/MG',
                'data_inicio' => $now->copy()->subDays(10)->format('Y-m-d'),
                'data_fim' => $now->copy()->addDays(5)->format('Y-m-d'),
                'numero_vagas' => 25,
                'percentual_frequencia_minimo' => 85.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subMonths(1),
                'updated_at' => $now->copy()->subDays(5),
            ],
            [
                'titulo' => 'Análise de Risco e Mapeamento de Áreas Vulneráveis',
                'descricao' => 'Curso EAD sobre metodologias de análise de risco, uso de SIG para mapeamento de áreas de risco e elaboração de planos de contingência municipais.',
                'carga_horaria' => 32,
                'tipo' => 'EAD',
                'status' => 'EM_ANDAMENTO',
                'instrutor' => 'Profª. Mariana Costa',
                'local' => 'https://ead.defesacivil.mg.gov.br/curso-analise-risco',
                'data_inicio' => $now->copy()->subDays(15)->format('Y-m-d'),
                'data_fim' => $now->copy()->addDays(15)->format('Y-m-d'),
                'numero_vagas' => null, // Ilimitado
                'percentual_frequencia_minimo' => 70.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subDays(3),
            ],

            // Treinamentos Planejados
            [
                'titulo' => 'Operação de Drones em Situações de Emergência',
                'descricao' => 'Treinamento prático para operação de VANTs (drones) em missões de busca e salvamento, mapeamento de áreas afetadas e avaliação de danos.',
                'carga_horaria' => 16,
                'tipo' => 'PRESENCIAL',
                'status' => 'PLANEJADO',
                'instrutor' => 'Maj. Pedro Oliveira',
                'local' => 'Campo de Treinamento - Lagoa Santa/MG',
                'data_inicio' => $now->copy()->addMonth()->format('Y-m-d'),
                'data_fim' => $now->copy()->addMonth()->addDays(2)->format('Y-m-d'),
                'numero_vagas' => 15,
                'percentual_frequencia_minimo' => 90.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subWeeks(2),
                'updated_at' => $now->copy()->subWeeks(2),
            ],
            [
                'titulo' => 'Coordenação de Voluntários em Emergências',
                'descricao' => 'Gestão eficiente de voluntários espontâneos durante desastres. Aborda recrutamento, capacitação rápida, coordenação de ações e gestão de expectativas.',
                'carga_horaria' => 20,
                'tipo' => 'HIBRIDO',
                'status' => 'PLANEJADO',
                'instrutor' => 'Luciana Mendes',
                'local' => 'Módulo online + Encontro presencial em Uberlândia/MG',
                'data_inicio' => $now->copy()->addMonths(2)->format('Y-m-d'),
                'data_fim' => $now->copy()->addMonths(2)->addDays(3)->format('Y-m-d'),
                'numero_vagas' => 40,
                'percentual_frequencia_minimo' => 75.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subWeek(),
                'updated_at' => $now->copy()->subWeek(),
            ],
            [
                'titulo' => 'Comunicação de Risco e Alertas à População',
                'descricao' => 'Técnicas de comunicação eficaz com a população em situações de risco iminente. Inclui uso de redes sociais, sirenes, mensagens SMS e interação com a imprensa.',
                'carga_horaria' => 12,
                'tipo' => 'EAD',
                'status' => 'PLANEJADO',
                'instrutor' => 'Jornalista Rafael Santos',
                'local' => 'https://ead.defesacivil.mg.gov.br/comunicacao-risco',
                'data_inicio' => $now->copy()->addMonth()->addWeeks(2)->format('Y-m-d'),
                'data_fim' => $now->copy()->addMonth()->addWeeks(3)->format('Y-m-d'),
                'numero_vagas' => null,
                'percentual_frequencia_minimo' => 70.00,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'titulo' => 'Elaboração de Planos de Contingência Municipal',
                'descricao' => 'Metodologia para elaboração de Planos de Contingência (PLANCON) municipais seguindo as diretrizes da Defesa Civil Nacional.',
                'carga_horaria' => 36,
                'tipo' => 'PRESENCIAL',
                'status' => 'PLANEJADO',
                'instrutor' => 'Equipe CEDEC/MG',
                'local' => 'Auditório CEDEC - Belo Horizonte/MG',
                'data_inicio' => $now->copy()->addMonths(3)->format('Y-m-d'),
                'data_fim' => $now->copy()->addMonths(3)->addDays(4)->format('Y-m-d'),
                'numero_vagas' => 35,
                'percentual_frequencia_minimo' => 80.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(3),
            ],

            // Mais treinamentos variados
            [
                'titulo' => 'Resgate em Áreas Alagadas',
                'descricao' => 'Treinamento especializado em técnicas de resgate aquático e em áreas inundadas. Inclui uso de botes, técnicas de natação de salvamento e segurança operacional.',
                'carga_horaria' => 28,
                'tipo' => 'PRESENCIAL',
                'status' => 'PLANEJADO',
                'instrutor' => 'Sgt. Marcos Vieira',
                'local' => 'Complexo Aquático - Vespasiano/MG',
                'data_inicio' => $now->copy()->addWeeks(3)->format('Y-m-d'),
                'data_fim' => $now->copy()->addWeeks(3)->addDays(4)->format('Y-m-d'),
                'numero_vagas' => 20,
                'percentual_frequencia_minimo' => 85.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
            ],
            [
                'titulo' => 'Introdução à Defesa Civil',
                'descricao' => 'Curso básico introdutório sobre o Sistema Nacional de Proteção e Defesa Civil (SINPDEC), legislação, atribuições e estrutura organizacional.',
                'carga_horaria' => 8,
                'tipo' => 'EAD',
                'status' => 'CONCLUIDO',
                'instrutor' => 'Equipe Técnica CEDEC',
                'local' => 'https://ead.defesacivil.mg.gov.br/introducao',
                'data_inicio' => $now->copy()->subMonths(4)->format('Y-m-d'),
                'data_fim' => $now->copy()->subMonths(4)->addWeek()->format('Y-m-d'),
                'numero_vagas' => null,
                'percentual_frequencia_minimo' => 60.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subMonths(5),
                'updated_at' => $now->copy()->subMonths(3),
            ],
        ];

        DB::table('treinamentos')->insert($treinamentos);

        $this->command->info('✓ ' . count($treinamentos) . ' treinamentos de exemplo criados com sucesso!');
    }
}
