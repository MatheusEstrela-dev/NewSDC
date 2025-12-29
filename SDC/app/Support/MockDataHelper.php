<?php

namespace App\Support;

class MockDataHelper
{
    public static function getBeneficiarios(): array
    {
        $data = [];
        $municipios = ['Belo Horizonte', 'Contagem', 'Betim', 'Nova Lima', 'Sabará'];
        $situacoes = ['desabrigado', 'desalojado', 'vulneravel'];
        $status = ['ativo', 'inativo', 'pendente'];

        for ($i = 1; $i <= 15; $i++) {
            $data[] = [
                'id' => $i,
                'nome' => "Beneficiário " . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'cpf' => "000.000.000-" . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'municipio' => ['id' => ($i % 5) + 1, 'nome' => $municipios[$i % 5]],
                'situacao_vulnerabilidade' => $situacoes[$i % 3],
                'status' => $status[$i % 3],
                'data_cadastro' => now()->subDays($i)->format('Y-m-d'),
            ];
        }

        return [
            'data' => $data,
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 15,
            'total' => 15,
        ];
    }

    public static function getBeneficiarioStatistics(): array
    {
        return [
            'total' => 150,
            'ativos' => 135,
            'em_abrigo' => 45,
            'fora_de_abrigo' => 90,
        ];
    }

    public static function getTreinamentos(): array
    {
        $data = [];
        $tipos = ['presencial', 'ead', 'hibrido'];
        $status = ['aberto', 'em_andamento', 'concluido'];

        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'id' => $i,
                'titulo' => "Treinamento de Defesa Civil Nível " . $i,
                'tipo' => $tipos[$i % 3],
                'status' => $status[$i % 3],
                'data_inicio' => now()->addDays($i)->format('d/m/Y'),
                'instrutor' => "Instrutor " . chr(64 + $i),
                'vagas_totais' => 50,
                'vagas_preenchidas' => rand(10, 45),
            ];
        }

        return $data;
    }

    public static function getTreinamentoStatistics(): array
    {
        return [
            'total' => 10,
            'planejados' => 4,
            'em_andamento' => 3,
            'concluidos' => 3,
            'inscritos' => 342,
        ];
    }

    public static function getOrgaos(): array
    {
        $data = [];
        $tipos = ['compdec', 'redec', 'cedec'];
        $status = ['ativo', 'inativo'];

        for ($i = 1; $i <= 12; $i++) {
            $data[] = [
                'id' => $i,
                'codigo' => "ORG-" . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'nome' => "Órgão Competente " . $i,
                'tipo' => $tipos[$i % 3],
                'tipoLabel' => strtoupper($tipos[$i % 3]),
                'municipio' => ['nome' => 'Município ' . $i],
                'status' => $status[$i % 2],
                'statusLabel' => $status[$i % 2] === 'ativo' ? 'Ativo' : 'Inativo',
                'statusBadgeColor' => $status[$i % 2] === 'ativo' ? 'success' : 'danger',
                'usuarios_count' => rand(1, 10),
            ];
        }

        return [
            'data' => $data,
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 15,
            'total' => 12,
        ];
    }

    public static function getOrgaoStatistics(): array
    {
        return [
            'total' => 12,
            'ativos' => 10,
            'inativos' => 2,
            'com_compdec' => 8,
            'por_tipo' => [
                'compdec' => 8,
                'redec' => 3,
                'cedec' => 1,
            ],
        ];
    }

    public static function getProcessos(): array
    {
        $data = [];
        $status = ['analise', 'aprovado', 'rejeitado', 'pendente'];
        $tipos = ['decretacao', 'reconhecimento'];

        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'id' => $i,
                'n_protocolo_fide' => "FIDE-2024-" . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'processo' => $tipos[$i % 2],
                'tipo_desastre_nome' => 'Chuvas Intensas',
                'analista' => 'Analista ' . chr(65 + $i),
                'status' => $status[$i % 4],
                'data_entrada' => now()->subDays($i * 2)->format('Y-m-d'),
                'data_vencimento' => now()->addDays(30 - $i)->format('Y-m-d'),
                'dias_restantes' => 30 - $i,
            ];
        }

        return [
            'data' => $data,
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 15,
            'total' => 10,
        ];
    }

    public static function getProcessoStatistics(): array
    {
        return [
            'total' => 10,
            'vigentes' => 7,
            'vencidos' => 2,
            'proximos_vencer' => 1,
        ];
    }
}
