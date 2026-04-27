<?php

namespace Database\Seeders;

use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\RatOcorrenciaHistorico;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Models\Relatos\RatRelatoVistoria;
use Illuminate\Database\Seeder;

/**
 * Seeder de dados de teste para o módulo RAT
 *
 * Cria RATs com todos os relatos polimórficos para testes de integração
 *
 * Execução:
 *   php artisan db:seed --class=RatSeeder --database=pgsql
 */
class RatSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n🌱 Iniciando seeding do módulo RAT...\n";

        // ════════════════════════════════════════════════════════════════════════════
        // 1. Criar RATs base
        // ════════════════════════════════════════════════════════════════════════════

        $rats = [
            [
                'numero_bos' => 'BOS-2025-001',
                'numero_protocolo' => 'RAT-2025-001',
                'status' => 1, // Finalizado
                'prazo_edicao' => now()->addDays(30),
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'numero_bos' => 'BOS-2025-002',
                'numero_protocolo' => 'RAT-2025-002',
                'status' => 0, // Rascunho
                'prazo_edicao' => now()->addDays(15),
                'created_by' => 1,
                'updated_by' => 1,
            ],
            [
                'numero_bos' => 'BOS-2025-003',
                'numero_protocolo' => 'RAT-2025-003',
                'status' => 1, // Finalizado
                'prazo_edicao' => now()->addDays(45),
                'created_by' => 1,
                'updated_by' => 1,
            ],
        ];

        $ratModels = [];
        foreach ($rats as $ratData) {
            $rat = RatOcorrencia::create($ratData);
            $ratModels[] = $rat;
            echo "  ✅ RAT {$rat->numero_protocolo} criado\n";
        }

        // ════════════════════════════════════════════════════════════════════════════
        // 2. Criar Dados Gerais para cada RAT
        // ════════════════════════════════════════════════════════════════════════════

        $dadosGerais = [
            [
                'ocorrencia_id' => $ratModels[0]->id,
                'data_fato' => now()->subDays(10),
                'data_inicio_atividade' => now()->subDays(10),
                'data_termino_atividade' => now()->subDays(8),
                'nat_codigo' => '1701',
                'nat_cobrade_id' => 1,
                'nat_nome_operacao' => 'Deslizamento de terra',

                // Comunicação
                'com_ocorrencia_data' => now()->subDays(10),
                'com_ocorrencia_atendimento' => 'telefone',
                'com_telefonista' => 'João Silva',
                'com_numero_telefone' => '(31) 3333-3333',

                // Localização
                'local_pais' => 'Brasil',
                'local_uf' => 'MG',
                'local_municipio' => 'Itabirito',
                'local_cep' => '35280-000',
                'local_logradouro' => 'Rua das Minas',
                'local_numero' => '100',
                'local_bairro' => 'Centro',
                'local_complemento' => 'Próximo ao banco',
                'local_latitude' => -20.2547,
                'local_longitude' => -43.8011,
            ],
            [
                'ocorrencia_id' => $ratModels[1]->id,
                'data_fato' => now()->subDays(5),
                'data_inicio_atividade' => now()->subDays(5),
                'data_termino_atividade' => now()->subDays(4),
                'nat_codigo' => '1702',
                'nat_cobrade_id' => 2,
                'nat_nome_operacao' => 'Enchente',

                'com_ocorrencia_data' => now()->subDays(5),
                'com_ocorrencia_atendimento' => 'radio',
                'com_telefonista' => 'Maria Santos',
                'com_numero_telefone' => '(31) 9999-8888',

                'local_pais' => 'Brasil',
                'local_uf' => 'MG',
                'local_municipio' => 'Ouro Preto',
                'local_cep' => '35400-000',
                'local_logradouro' => 'Avenida Getúlio Vargas',
                'local_numero' => '500',
                'local_bairro' => 'Pilar',
                'local_latitude' => -20.3885,
                'local_longitude' => -43.5038,
            ],
            [
                'ocorrencia_id' => $ratModels[2]->id,
                'data_fato' => now()->subDays(2),
                'data_inicio_atividade' => now()->subDays(2),
                'data_termino_atividade' => now()->subDays(1),
                'nat_codigo' => '1710',
                'nat_cobrade_id' => 3,
                'nat_nome_operacao' => 'Incêndio florestal',

                'com_ocorrencia_data' => now()->subDays(2),
                'com_ocorrencia_atendimento' => 'pessoal',
                'com_telefonista' => 'Carlos Oliveira',
                'com_numero_telefone' => '(31) 7777-6666',

                'local_pais' => 'Brasil',
                'local_uf' => 'MG',
                'local_municipio' => 'Mariana',
                'local_cep' => '35420-000',
                'local_logradouro' => 'Estrada da Serra',
                'local_numero' => '1200',
                'local_bairro' => 'Zona Rural',
                'local_latitude' => -20.3732,
                'local_longitude' => -43.4067,
            ],
        ];

        foreach ($dadosGerais as $dados) {
            RatRelatoDadosGerais::create($dados);
            echo "  ✅ Dados gerais criados para RAT\n";
        }

        // ════════════════════════════════════════════════════════════════════════════
        // 3. Criar Envolvidos
        // ════════════════════════════════════════════════════════════════════════════

        $envolvidos = [
            ['ocorrencia_id' => $ratModels[0]->id, 'nome' => 'João da Silva', 'cpf_cnpj' => '123.456.789-10', 'funcao' => 'Chefe de Operações', 'orgao' => 'Defesa Civil', 'telefone' => '(31) 3333-3333', 'email' => 'joao@defesacivil.mg.gov.br'],
            ['ocorrencia_id' => $ratModels[0]->id, 'nome' => 'Maria Santos', 'cpf_cnpj' => '987.654.321-00', 'funcao' => 'Coordenadora', 'orgao' => 'CBMG', 'telefone' => '(31) 9999-8888', 'email' => 'maria@bombeiros.mg.gov.br'],
            ['ocorrencia_id' => $ratModels[0]->id, 'nome' => 'Dr. Carlos Pereira', 'cpf_cnpj' => '111.222.333-44', 'funcao' => 'Médico', 'orgao' => 'Saúde Municipal', 'telefone' => '(31) 5555-5555', 'email' => 'carlos@saude.itabirito.mg.gov.br'],

            ['ocorrencia_id' => $ratModels[1]->id, 'nome' => 'Ana Paula Silva', 'cpf_cnpj' => '555.666.777-88', 'funcao' => 'Técnica', 'orgao' => 'IGAM', 'telefone' => '(31) 4444-4444', 'email' => 'ana@igam.mg.gov.br'],
            ['ocorrencia_id' => $ratModels[1]->id, 'nome' => 'Roberto Costa', 'cpf_cnpj' => '999.888.777-66', 'funcao' => 'Engenheiro', 'orgao' => 'CREA', 'telefone' => '(31) 2222-2222', 'email' => 'roberto@crea.mg.gov.br'],

            ['ocorrencia_id' => $ratModels[2]->id, 'nome' => 'Fernanda Gomes', 'cpf_cnpj' => '333.444.555-66', 'funcao' => 'Brigadista', 'orgao' => 'IBAMA', 'telefone' => '(31) 6666-6666', 'email' => 'fernanda@ibama.gov.br'],
        ];

        foreach ($envolvidos as $envolvido) {
            RatRelatoEnvolvidos::create($envolvido);
        }
        echo "  ✅ " . count($envolvidos) . " envolvidos criados\n";

        // ════════════════════════════════════════════════════════════════════════════
        // 4. Criar Recursos
        // ════════════════════════════════════════════════════════════════════════════

        $recursos = [
            ['ocorrencia_id' => $ratModels[0]->id, 'tipo' => 'Viaturas', 'quantidade' => 5, 'descricao' => 'Caminhões de resgate', 'valor' => 250000.00],
            ['ocorrencia_id' => $ratModels[0]->id, 'tipo' => 'Pessoal', 'quantidade' => 25, 'descricao' => 'Bombeiros e técnicos', 'valor' => 50000.00],
            ['ocorrencia_id' => $ratModels[0]->id, 'tipo' => 'Equipamentos', 'quantidade' => 10, 'descricao' => 'Cilindros de oxigênio', 'valor' => 15000.00],

            ['ocorrencia_id' => $ratModels[1]->id, 'tipo' => 'Viaturas', 'quantidade' => 3, 'descricao' => 'Lanchas de resgate', 'valor' => 150000.00],
            ['ocorrencia_id' => $ratModels[1]->id, 'tipo' => 'Pessoal', 'quantidade' => 15, 'descricao' => 'Mergulhadores', 'valor' => 40000.00],

            ['ocorrencia_id' => $ratModels[2]->id, 'tipo' => 'Viaturas', 'quantidade' => 8, 'descricao' => 'Caminhões pipa', 'valor' => 300000.00],
            ['ocorrencia_id' => $ratModels[2]->id, 'tipo' => 'Pessoal', 'quantidade' => 30, 'descricao' => 'Brigadistas florestais', 'valor' => 60000.00],
            ['ocorrencia_id' => $ratModels[2]->id, 'tipo' => 'Equipamentos', 'quantidade' => 20, 'descricao' => 'Motoserras', 'valor' => 25000.00],
        ];

        foreach ($recursos as $recurso) {
            RatRelatoRecurso::create($recurso);
        }
        echo "  ✅ " . count($recursos) . " recursos criados\n";

        // ════════════════════════════════════════════════════════════════════════════
        // 5. Criar Vistorias
        // ════════════════════════════════════════════════════════════════════════════

        $vistorias = [
            [
                'ocorrencia_id' => $ratModels[0]->id,
                'data_vistoria' => now()->subDays(8),
                'responsavel' => 'Dr. Carlos Pereira',
                'constatacoes' => 'Local totalmente destruído. Estruturas danificadas. Risco de novos deslizamentos.',
                'recomendacoes' => 'Reforçar contenção. Acompanhamento técnico quinzenal.',
            ],
            [
                'ocorrencia_id' => $ratModels[1]->id,
                'data_vistoria' => now()->subDays(4),
                'responsavel' => 'Eng. Roberto Costa',
                'constatacoes' => 'Nível de água normalizado. Estruturas afetadas mas recuperáveis.',
                'recomendacoes' => 'Monitoramento de drenagem. Limpeza de área afetada.',
            ],
            [
                'ocorrencia_id' => $ratModels[2]->id,
                'data_vistoria' => now()->subDays(1),
                'responsavel' => 'Fernanda Gomes',
                'constatacoes' => 'Incêndio controlado. Fauna e flora afetadas. Solo exposto.',
                'recomendacoes' => 'Replantio imediato. Programa de recuperação ambiental.',
            ],
        ];

        foreach ($vistorias as $vistoria) {
            RatRelatoVistoria::create($vistoria);
        }
        echo "  ✅ " . count($vistorias) . " vistorias criadas\n";

        // ════════════════════════════════════════════════════════════════════════════
        // 6. Criar Histórico de Eventos
        // ════════════════════════════════════════════════════════════════════════════

        foreach ($ratModels as $rat) {
            RatOcorrenciaHistorico::create([
                'ocorrencia_id' => $rat->id,
                'tipo' => 'criacao',
                'titulo' => 'RAT criado',
                'descricao' => 'RAT ' . $rat->numero_protocolo . ' criado no sistema.',
                'usuario_id' => 1,
            ]);

            RatOcorrenciaHistorico::create([
                'ocorrencia_id' => $rat->id,
                'tipo' => 'atualizacao',
                'titulo' => 'Dados gerais atualizados',
                'descricao' => 'Dados gerais da ocorrência foram preenchidos.',
                'usuario_id' => 1,
            ]);

            if ($rat->status === 1) {
                RatOcorrenciaHistorico::create([
                    'ocorrencia_id' => $rat->id,
                    'tipo' => 'finalizacao',
                    'titulo' => 'RAT finalizado',
                    'descricao' => 'RAT foi finalizado e enviado para análise.',
                    'usuario_id' => 1,
                ]);
            }
        }
        echo "  ✅ Histórico de eventos criado\n";

        echo "\n✨ Seeding do módulo RAT concluído com sucesso!\n";
        echo "   📊 Total de RATs: " . count($ratModels) . "\n";
        echo "   👥 Total de Envolvidos: " . count($envolvidos) . "\n";
        echo "   🔧 Total de Recursos: " . count($recursos) . "\n";
        echo "   🔍 Total de Vistorias: " . count($vistorias) . "\n\n";
    }
}
