<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use Carbon\Carbon;

class TreinamentosMockSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("\nCriando Treinamentos mockados...\n");

        Treinamento::truncate();

        $treinamentos = [
            [
                'titulo' => 'Capacitação em Proteção e Defesa Civil 2026',
                'descricao' => 'Curso de nivelamento para novos agentes de Defesa Civil.',
                'tipo' => TipoTreinamento::EAD->value,
                'status' => StatusTreinamento::EM_ANDAMENTO->value,
                'instrutor' => 'Ten. Cel. Almeida',
                'local' => 'Plataforma EAD CEDEC',
            ],
            [
                'titulo' => 'Simulado de Abandono de Área de Risco',
                'descricao' => 'Simulado prático envolvendo comunidades ribeirinhas.',
                'tipo' => TipoTreinamento::PRESENCIAL->value,
                'status' => StatusTreinamento::PLANEJADO->value,
                'instrutor' => 'Maj. Carvalho',
                'local' => 'Governador Valadares, Bairro X',
            ],
            [
                'titulo' => 'Workshop: Operação Chuvas 2025/2026',
                'descricao' => 'Treinamento sobre protocolos de resposta rápida para a Defesa Civil Estadual.',
                'tipo' => TipoTreinamento::HIBRIDO->value,
                'status' => StatusTreinamento::CONCLUIDO->value,
                'instrutor' => 'Cap. Souza',
                'local' => 'Auditório CEDEC-MG',
            ],
            [
                'titulo' => 'Treinamento SDC Nível 2',
                'descricao' => 'Treinamento avançado no uso do Sistema Integrado de Defesa Civil.',
                'tipo' => TipoTreinamento::EAD->value,
                'status' => StatusTreinamento::PLANEJADO->value,
                'instrutor' => 'Sgt. Lima',
                'local' => 'Online',
            ],
            [
                'titulo' => 'Exercício Simulado de Mesa',
                'descricao' => 'Exercício para tomada de decisão em cenários de desastre químico.',
                'tipo' => TipoTreinamento::HIBRIDO->value,
                'status' => StatusTreinamento::CANCELADO->value,
                'instrutor' => 'Ten. Marques',
                'local' => 'Online via Teams',
            ],
        ];

        foreach ($treinamentos as $index => $data) {
            $dataInicio = Carbon::now()->addDays($index * 5 - 10);
            $dataFim = (clone $dataInicio)->addDays(rand(1, 5));

            Treinamento::create([
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
                'carga_horaria' => rand(10, 40),
                'tipo' => $data['tipo'],
                'status' => $data['status'],
                'instrutor' => $data['instrutor'],
                'local' => $data['local'],
                'data_inicio' => $dataInicio->format('Y-m-d'),
                'data_fim' => $dataFim->format('Y-m-d'),
                'numero_vagas' => rand(30, 200),
                'percentual_frequencia_minimo' => 75.00,
                'created_by' => 1,
            ]);
        }

        $this->command->info("Treinamentos criados com sucesso: " . count($treinamentos));
    }
}
