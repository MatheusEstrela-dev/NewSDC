<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Models\User;
use Illuminate\Support\Str;
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
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
                'inscrito_type' => User::class,
                'inscrito_id' => $userId,
                'qr_code_token' => (string) Str::uuid(),
                'status' => 'PENDENTE',
                'data_inscricao' => $now->copy()->subDays(2),
                'data_aprovacao' => null,
                'aprovado_por_id' => null,
                'observacoes' => null,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
        ];

        // O mock de treinamentos cria menos treinamentos que os ids usados acima;
        // sem filtrar, a FK interna do banco recusa a carga inteira.
        $existentes = ConexaoTreinamento::conexao()->table('treinamento.treinamentos')->pluck('id')->all();
        $inscricoes = array_values(array_filter(
            $inscricoes,
            static fn (array $i): bool => in_array($i['treinamento_id'], $existentes, true),
        ));

        ConexaoTreinamento::conexao()->table('treinamento.inscricoes')->insert($inscricoes);

        $this->command->info('✓ ' . count($inscricoes) . ' inscrições criadas para o usuário!');
    }
}
