<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InscricoesMockSeeder extends Seeder
{
    /**
     * Seeder de inscrições para os treinamentos
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Garantir que temos o user_id 1 (usuário admin)
        $userId = 1;

        $inscricoes = [
            // Inscrições APROVADAS para treinamentos concluídos
            [
                'treinamento_id' => 1, // Primeiros Socorros
                'user_id' => $userId,
                'status' => 'APROVADA',
                'data_inscricao' => $now->copy()->subMonths(4),
                'data_aprovacao' => $now->copy()->subMonths(4)->addDay(),
                'aprovado_por_id' => $userId,
                'observacoes' => 'Aprovado automaticamente - participação confirmada',
                'created_at' => $now->copy()->subMonths(4),
                'updated_at' => $now->copy()->subMonths(3),
            ],
            [
                'treinamento_id' => 2, // Gestão de Abrigos
                'user_id' => $userId,
                'status' => 'APROVADA',
                'data_inscricao' => $now->copy()->subMonths(3),
                'data_aprovacao' => $now->copy()->subMonths(3)->addHours(2),
                'aprovado_por_id' => $userId,
                'observacoes' => 'Experiência prévia em gestão de emergências',
                'created_at' => $now->copy()->subMonths(3),
                'updated_at' => $now->copy()->subMonths(2),
            ],
            [
                'treinamento_id' => 10, // Introdução à Defesa Civil (EAD)
                'user_id' => $userId,
                'status' => 'APROVADA',
                'data_inscricao' => $now->copy()->subMonths(5),
                'data_aprovacao' => $now->copy()->subMonths(5)->addMinutes(30),
                'aprovado_por_id' => $userId,
                'observacoes' => 'Curso básico - aprovação automática',
                'created_at' => $now->copy()->subMonths(5),
                'updated_at' => $now->copy()->subMonths(4),
            ],

            // Inscrições APROVADAS para treinamentos em andamento
            [
                'treinamento_id' => 3, // Sistema de Comando de Incidentes
                'user_id' => $userId,
                'status' => 'APROVADA',
                'data_inscricao' => $now->copy()->subMonths(2),
                'data_aprovacao' => $now->copy()->subMonths(2)->addDays(3),
                'aprovado_por_id' => $userId,
                'observacoes' => 'Pré-requisitos atendidos - curso de Primeiros Socorros concluído',
                'created_at' => $now->copy()->subMonths(2),
                'updated_at' => $now->copy()->subMonth(),
            ],
            [
                'treinamento_id' => 4, // Análise de Risco (EAD)
                'user_id' => $userId,
                'status' => 'APROVADA',
                'data_inscricao' => $now->copy()->subMonth(),
                'data_aprovacao' => $now->copy()->subMonth()->addHours(6),
                'aprovado_por_id' => $userId,
                'observacoes' => 'Curso EAD - vagas ilimitadas',
                'created_at' => $now->copy()->subMonth(),
                'updated_at' => $now->copy()->subWeeks(3),
            ],

            // Inscrições PENDENTES (aguardando aprovação)
            [
                'treinamento_id' => 5, // Operação de Drones
                'user_id' => $userId,
                'status' => 'PENDENTE',
                'data_inscricao' => $now->copy()->subWeek(),
                'data_aprovacao' => null,
                'aprovado_por_id' => null,
                'observacoes' => null,
                'created_at' => $now->copy()->subWeek(),
                'updated_at' => $now->copy()->subWeek(),
            ],
            [
                'treinamento_id' => 6, // Coordenação de Voluntários
                'user_id' => $userId,
                'status' => 'PENDENTE',
                'data_inscricao' => $now->copy()->subDays(4),
                'data_aprovacao' => null,
                'aprovado_por_id' => null,
                'observacoes' => null,
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(4),
            ],

            // Inscrição CANCELADA
            [
                'treinamento_id' => 9, // Resgate em Áreas Alagadas
                'user_id' => $userId,
                'status' => 'CANCELADA',
                'data_inscricao' => $now->copy()->subWeeks(2),
                'data_aprovacao' => null,
                'aprovado_por_id' => null,
                'observacoes' => 'Cancelado pelo participante - conflito de agenda',
                'created_at' => $now->copy()->subWeeks(2),
                'updated_at' => $now->copy()->subWeek(),
            ],

            // Mais inscrições aprovadas para outros treinamentos
            [
                'treinamento_id' => 7, // Comunicação de Risco (EAD)
                'user_id' => $userId,
                'status' => 'APROVADA',
                'data_inscricao' => $now->copy()->subDays(5),
                'data_aprovacao' => $now->copy()->subDays(5)->addHours(3),
                'aprovado_por_id' => $userId,
                'observacoes' => 'Curso EAD - inscrições abertas',
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(4),
            ],
            [
                'treinamento_id' => 8, // Elaboração de PLANCON
                'user_id' => $userId,
                'status' => 'PENDENTE',
                'data_inscricao' => $now->copy()->subDays(2),
                'data_aprovacao' => null,
                'aprovado_por_id' => null,
                'observacoes' => null,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
        ];

        DB::table('inscricoes')->insert($inscricoes);

        $this->command->info('✓ ' . count($inscricoes) . ' inscrições criadas para o usuário!');
    }
}
