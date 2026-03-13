<?php

namespace Database\Seeders;

use App\Modules\Rat\Models\Rat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RatMockSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Criando RATs mockados...');

        $rats = [
            // ── Em Andamento (ativos) ───────────────────────────────────
            [
                'protocolo'  => 'RAT-2024-001',
                'status'     => 'em_andamento',
                'tem_vistoria' => true,
                'local'      => ['municipio' => 'Belo Horizonte', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Av. Afonso Pena, 1212', 'bairro' => 'Centro', 'cep' => '30130-003'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Inundação',
                    'cobrade'         => '1.2.1.0.0',
                    'data_fato'       => now()->subDays(15)->toDateString(),
                    'descricao'       => 'Inundação decorrente de chuvas intensas na região central. Nível do Rio das Velhas subiu 2,5m acima do normal.',
                    'danos_humanos'   => ['desalojados' => 45, 'desabrigados' => 12, 'feridos_leves' => 3],
                    'danos_materiais' => ['residencias_danificadas' => 28, 'residencias_destruidas' => 2],
                ],
                'comunicacao' => ['telefone' => '(31) 3277-4500', 'email' => 'defesacivil@pbh.gov.br'],
                'created_by' => 1,
                'days_ago'   => 15,
            ],
            [
                'protocolo'  => 'RAT-2024-002',
                'status'     => 'em_andamento',
                'tem_vistoria' => true,
                'local'      => ['municipio' => 'Contagem', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Rua Rio de Janeiro, 450', 'bairro' => 'Eldorado', 'cep' => '32310-000'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Deslizamento',
                    'cobrade'         => '1.1.3.2.1',
                    'data_fato'       => now()->subDays(10)->toDateString(),
                    'descricao'       => 'Deslizamento de encosta em área de ocupação irregular. Solo saturado após 72h de chuvas.',
                    'danos_humanos'   => ['desalojados' => 22, 'desabrigados' => 8, 'obitos' => 0],
                    'danos_materiais' => ['residencias_danificadas' => 15, 'residencias_destruidas' => 5],
                ],
                'comunicacao' => ['telefone' => '(31) 3352-5500', 'email' => 'defesacivil@contagem.mg.gov.br'],
                'created_by' => 2,
                'days_ago'   => 10,
            ],
            [
                'protocolo'  => 'RAT-2024-003',
                'status'     => 'em_andamento',
                'tem_vistoria' => false,
                'local'      => ['municipio' => 'Uberlândia', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Av. Rondon Pacheco, 3800', 'bairro' => 'Tibery', 'cep' => '38405-142'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Vendaval',
                    'cobrade'         => '1.3.2.1.3',
                    'data_fato'       => now()->subDays(5)->toDateString(),
                    'descricao'       => 'Vendaval com rajadas acima de 90km/h causando destelhamento e queda de árvores em diversos bairros.',
                    'danos_humanos'   => ['feridos_leves' => 7, 'desalojados' => 18],
                    'danos_materiais' => ['residencias_danificadas' => 42, 'infraestrutura_publica' => 6],
                ],
                'comunicacao' => ['telefone' => '(34) 3239-2800', 'email' => 'defesacivil@uberlandia.mg.gov.br'],
                'created_by' => 3,
                'days_ago'   => 5,
            ],
            [
                'protocolo'  => 'RAT-2024-004',
                'status'     => 'em_andamento',
                'tem_vistoria' => true,
                'local'      => ['municipio' => 'Juiz de Fora', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Rua Halfeld, 900', 'bairro' => 'Centro', 'cep' => '36016-000'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Enxurrada',
                    'cobrade'         => '1.2.2.0.0',
                    'data_fato'       => now()->subDays(3)->toDateString(),
                    'descricao'       => 'Enxurrada no córrego São Pedro atingindo residências e estabelecimentos comerciais do centro.',
                    'danos_humanos'   => ['desalojados' => 35, 'desabrigados' => 15, 'feridos_graves' => 1],
                    'danos_materiais' => ['residencias_danificadas' => 20, 'comercios_danificados' => 12],
                ],
                'comunicacao' => ['telefone' => '(32) 3690-7000', 'email' => 'defesacivil@pjf.mg.gov.br'],
                'created_by' => 4,
                'days_ago'   => 3,
            ],

            // ── Rascunho (em elaboração) ────────────────────────────────
            [
                'protocolo'  => 'RAT-2024-005',
                'status'     => 'rascunho',
                'tem_vistoria' => false,
                'local'      => ['municipio' => 'Governador Valadares', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Av. Brasil, 2500', 'bairro' => 'Vila Bretas', 'cep' => '35020-000'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Alagamento',
                    'cobrade'         => '1.2.3.0.0',
                    'data_fato'       => now()->subDays(2)->toDateString(),
                    'descricao'       => 'Alagamento em diversos bairros da cidade após elevação do nível do Rio Doce.',
                ],
                'comunicacao' => ['telefone' => '(33) 3271-6100'],
                'created_by' => 5,
                'days_ago'   => 2,
            ],
            [
                'protocolo'  => 'RAT-2024-006',
                'status'     => 'rascunho',
                'tem_vistoria' => false,
                'local'      => ['municipio' => 'Montes Claros', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Rua Coronel Spyer, 100', 'bairro' => 'Centro', 'cep' => '39400-074'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Seca',
                    'cobrade'         => '1.4.1.0.0',
                    'data_fato'       => now()->subDays(30)->toDateString(),
                    'descricao'       => 'Estiagem prolongada afetando abastecimento hídrico de comunidades rurais no norte de Minas.',
                    'danos_humanos'   => ['afetados' => 1500],
                ],
                'comunicacao' => ['telefone' => '(38) 3224-5800'],
                'created_by' => 6,
                'days_ago'   => 1,
            ],
            [
                'protocolo'  => 'RAT-2024-007',
                'status'     => 'rascunho',
                'tem_vistoria' => false,
                'local'      => ['municipio' => 'Ipatinga', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Av. Pedro Linhares Gomes, 5431', 'bairro' => 'Iguaçu', 'cep' => '35160-158'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Incêndio Florestal',
                    'cobrade'         => '1.4.1.3.0',
                    'data_fato'       => now()->subDay()->toDateString(),
                    'descricao'       => 'Incêndio florestal atingindo área de proteção ambiental próxima a bairros residenciais.',
                ],
                'comunicacao' => ['telefone' => '(31) 3829-8000'],
                'created_by' => 3,
                'days_ago'   => 0,
            ],

            // ── Finalizados (encerrados) ────────────────────────────────
            [
                'protocolo'  => 'RAT-2024-008',
                'status'     => 'finalizado',
                'tem_vistoria' => true,
                'local'      => ['municipio' => 'Betim', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Rua do Rosário, 200', 'bairro' => 'Angola', 'cep' => '32604-115'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Inundação',
                    'cobrade'         => '1.2.1.0.0',
                    'data_fato'       => now()->subMonths(2)->toDateString(),
                    'descricao'       => 'Inundação no bairro Angola após rompimento de rede pluvial. Situação normalizada.',
                    'danos_humanos'   => ['desalojados' => 60, 'desabrigados' => 20, 'feridos_leves' => 5],
                    'danos_materiais' => ['residencias_danificadas' => 35, 'residencias_destruidas' => 3],
                ],
                'comunicacao' => ['telefone' => '(31) 3532-4000', 'email' => 'defesacivil@betim.mg.gov.br'],
                'created_by' => 1,
                'days_ago'   => 60,
            ],
            [
                'protocolo'  => 'RAT-2024-009',
                'status'     => 'finalizado',
                'tem_vistoria' => true,
                'local'      => ['municipio' => 'Ribeirão das Neves', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Rua Doutor Campolina, 150', 'bairro' => 'Centro', 'cep' => '33805-050'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Deslizamento',
                    'cobrade'         => '1.1.3.2.1',
                    'data_fato'       => now()->subMonths(3)->toDateString(),
                    'descricao'       => 'Deslizamento em encosta urbanizada. Obras de contenção realizadas. Famílias realocadas.',
                    'danos_humanos'   => ['desalojados' => 30, 'desabrigados' => 10],
                    'danos_materiais' => ['residencias_destruidas' => 8],
                ],
                'comunicacao' => ['telefone' => '(31) 3621-7500'],
                'created_by' => 2,
                'days_ago'   => 90,
            ],
            [
                'protocolo'  => 'RAT-2024-010',
                'status'     => 'finalizado',
                'tem_vistoria' => true,
                'local'      => ['municipio' => 'Divinópolis', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Av. Primeiro de Junho, 800', 'bairro' => 'Centro', 'cep' => '35500-001'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Granizo',
                    'cobrade'         => '1.3.2.1.4',
                    'data_fato'       => now()->subMonths(1)->toDateString(),
                    'descricao'       => 'Tempestade de granizo com pedras de até 5cm de diâmetro causando danos extensos.',
                    'danos_humanos'   => ['feridos_leves' => 12],
                    'danos_materiais' => ['residencias_danificadas' => 85, 'veiculos_danificados' => 120],
                ],
                'comunicacao' => ['telefone' => '(37) 3229-6500', 'email' => 'defesacivil@divinopolis.mg.gov.br'],
                'created_by' => 4,
                'days_ago'   => 30,
            ],
            [
                'protocolo'  => 'RAT-2025-011',
                'status'     => 'finalizado',
                'tem_vistoria' => true,
                'local'      => ['municipio' => 'Poços de Caldas', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Praça Pedro Sanches, s/n', 'bairro' => 'Centro', 'cep' => '37701-000'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Erosão',
                    'cobrade'         => '1.1.4.1.0',
                    'data_fato'       => now()->subMonths(4)->toDateString(),
                    'descricao'       => 'Erosão de margem em área urbana próxima ao Parque Municipal. Risco à infraestrutura viária.',
                    'danos_materiais' => ['infraestrutura_publica' => 2, 'vias_interditadas' => 1],
                ],
                'comunicacao' => ['telefone' => '(35) 3697-2000'],
                'created_by' => 5,
                'days_ago'   => 120,
            ],

            // ── Hoje (criados recentemente) ─────────────────────────────
            [
                'protocolo'  => 'RAT-2026-012',
                'status'     => 'rascunho',
                'tem_vistoria' => false,
                'local'      => ['municipio' => 'Ouro Preto', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Praça Tiradentes, s/n', 'bairro' => 'Centro Histórico', 'cep' => '35400-000'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Deslizamento',
                    'cobrade'         => '1.1.3.2.1',
                    'data_fato'       => now()->toDateString(),
                    'descricao'       => 'Risco iminente de deslizamento em encosta próxima ao patrimônio histórico. Equipe técnica mobilizada.',
                ],
                'comunicacao' => ['telefone' => '(31) 3559-3232'],
                'created_by' => 6,
                'days_ago'   => 0,
            ],
            [
                'protocolo'  => 'RAT-2026-013',
                'status'     => 'em_andamento',
                'tem_vistoria' => false,
                'local'      => ['municipio' => 'Uberaba', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Av. Guilherme Ferreira, 500', 'bairro' => 'Centro', 'cep' => '38010-200'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Vendaval',
                    'cobrade'         => '1.3.2.1.3',
                    'data_fato'       => now()->toDateString(),
                    'descricao'       => 'Vendaval com queda generalizada de energia elétrica. CEMIG acionada para restabelecimento.',
                    'danos_humanos'   => ['afetados' => 5000],
                    'danos_materiais' => ['postes_derrubados' => 15, 'arvores_caidas' => 30],
                ],
                'comunicacao' => ['telefone' => '(34) 3318-0500', 'email' => 'defesacivil@uberaba.mg.gov.br'],
                'created_by' => 1,
                'days_ago'   => 0,
            ],
            [
                'protocolo'  => 'RAT-2026-014',
                'status'     => 'em_andamento',
                'tem_vistoria' => true,
                'local'      => ['municipio' => 'Sete Lagoas', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Rua Deputado Augusto Clementino, 200', 'bairro' => 'Centro', 'cep' => '35700-039'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Inundação',
                    'cobrade'         => '1.2.1.0.0',
                    'data_fato'       => now()->subDay()->toDateString(),
                    'descricao'       => 'Inundação no Ribeirão Jequitibá afetando bairros baixos da cidade. Nível 1,8m acima do normal.',
                    'danos_humanos'   => ['desalojados' => 80, 'desabrigados' => 25, 'feridos_leves' => 4],
                    'danos_materiais' => ['residencias_danificadas' => 55, 'residencias_destruidas' => 7],
                ],
                'comunicacao' => ['telefone' => '(31) 3773-2700'],
                'created_by' => 2,
                'days_ago'   => 1,
            ],
            [
                'protocolo'  => 'RAT-2026-015',
                'status'     => 'rascunho',
                'tem_vistoria' => false,
                'local'      => ['municipio' => 'Teófilo Otoni', 'uf' => 'MG'],
                'endereco'   => ['logradouro' => 'Rua Doutor José Otávio, 320', 'bairro' => 'Centro', 'cep' => '39800-015'],
                'dados_gerais' => [
                    'tipo_desastre'   => 'Enxurrada',
                    'cobrade'         => '1.2.2.0.0',
                    'data_fato'       => now()->subDays(2)->toDateString(),
                    'descricao'       => 'Enxurrada no Córrego Todos os Santos carregando veículos e danificando vias públicas.',
                    'danos_humanos'   => ['desalojados' => 40, 'desabrigados' => 12],
                    'danos_materiais' => ['veiculos_danificados' => 8, 'pontes_danificadas' => 1],
                ],
                'comunicacao' => ['telefone' => '(33) 3529-6000'],
                'created_by' => 3,
                'days_ago'   => 2,
            ],
        ];

        foreach ($rats as $ratData) {
            $daysAgo = $ratData['days_ago'];
            unset($ratData['days_ago']);

            Rat::updateOrCreate(
                ['protocolo' => $ratData['protocolo']],
                array_merge($ratData, [
                    'id'         => Str::uuid()->toString(),
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays(max(0, $daysAgo - 1)),
                ])
            );
        }

        $total = count($rats);
        $emAndamento = collect($rats)->where('status', 'em_andamento')->count();
        $rascunho    = collect($rats)->where('status', 'rascunho')->count();
        $finalizado  = collect($rats)->where('status', 'finalizado')->count();

        $this->command->newLine();
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('  RATs MOCK CRIADOS COM SUCESSO');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info("  Total: {$total}");
        $this->command->info("  Em Andamento: {$emAndamento}");
        $this->command->info("  Rascunho: {$rascunho}");
        $this->command->info("  Finalizado: {$finalizado}");
        $this->command->newLine();

        $this->command->table(
            ['Protocolo', 'Status', 'Município', 'Tipo Desastre'],
            collect($rats)->map(fn ($r) => [
                $r['protocolo'],
                $r['status'],
                $r['local']['municipio'] . '/' . $r['local']['uf'],
                $r['dados_gerais']['tipo_desastre'] ?? '-',
            ])->toArray()
        );
    }
}
