<?php

namespace Database\Seeders;

use App\Modules\Compdec\Domain\Entities\Orgao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrgaosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotente: usa updateOrCreate para não gerar duplicate key ao reexecutar.
     */
    public function run(): void
    {
        // CEDEC - Coordenadoria Estadual de Defesa Civil
        DB::table('orgaos')->updateOrInsert(
            ['codigo' => 'CEDEC-SC'],
            [
                'nome' => 'Coordenadoria Estadual de Defesa Civil de Santa Catarina',
                'tipo' => 'cedec',
                'municipio_id' => null, // Estadual
                'orgao_superior_id' => null,
                'status' => 'ativo',
                'email' => 'cedec@sc.gov.br',
                'telefone' => '(48) 3665-2200',
                'endereco' => 'Av. Governador Ivo Silveira, 2320 - Capoeiras, Florianópolis - SC',
                'responsavel_nome' => 'Coordenador CEDEC',
                'responsavel_email' => 'coordenador@cedec.sc.gov.br',
                'latitude' => -27.5969,
                'longitude' => -48.5495,
                'updated_at' => now(),
            ]
        );

        $cedecId = DB::table('orgaos')->where('codigo', 'CEDEC-SC')->value('id');

        // REDEC - Coordenadorias Regionais de Defesa Civil
        $redecs = [
            [
                'codigo' => 'REDEC-01-FLORIANOPOLIS',
                'nome' => 'REDEC 01 - Grande Florianópolis',
                'municipio_id' => null,
                'responsavel_nome' => 'Coordenador Regional 01',
                'abrangencia' => json_encode([1, 2, 3, 4, 5]), // IDs de municípios
            ],
            [
                'codigo' => 'REDEC-02-ITAJAI',
                'nome' => 'REDEC 02 - Vale do Itajaí',
                'municipio_id' => null,
                'responsavel_nome' => 'Coordenador Regional 02',
                'abrangencia' => json_encode([10, 11, 12, 13, 14]),
            ],
            [
                'codigo' => 'REDEC-03-JOINVILLE',
                'nome' => 'REDEC 03 - Norte',
                'municipio_id' => null,
                'responsavel_nome' => 'Coordenador Regional 03',
                'abrangencia' => json_encode([20, 21, 22, 23, 24]),
            ],
        ];

        foreach ($redecs as $redec) {
            DB::table('orgaos')->updateOrInsert(
                ['codigo' => $redec['codigo']],
                [
                    'nome' => $redec['nome'],
                    'tipo' => 'redec',
                    'municipio_id' => $redec['municipio_id'],
                    'orgao_superior_id' => $cedecId,
                    'status' => 'ativo',
                    'email' => strtolower(str_replace(' ', '', $redec['codigo'])) . '@sc.gov.br',
                    'telefone' => '(48) 3665-' . rand(2000, 2999),
                    'responsavel_nome' => $redec['responsavel_nome'],
                    'responsavel_email' => strtolower(str_replace(' ', '', $redec['codigo'])) . '@cedec.sc.gov.br',
                    'abrangencia' => $redec['abrangencia'],
                    'updated_at' => now(),
                ]
            );
        }

        $redecIds = DB::table('orgaos')->whereIn('codigo', array_column($redecs, 'codigo'))->pluck('id')->toArray();

        // COMPDEC - Coordenadorias Municipais de Defesa Civil
        $compdecs = [
            [
                'codigo' => 'COMPDEC-FLORIANOPOLIS',
                'nome' => 'COMPDEC Florianópolis',
                'municipio_id' => null,
                'redec_id' => $redecIds[0],
                'responsavel_nome' => 'João da Silva',
                'responsavel_cpf' => '123.456.789-00',
                'latitude' => -27.5954,
                'longitude' => -48.5480,
            ],
            [
                'codigo' => 'COMPDEC-SAO-JOSE',
                'nome' => 'COMPDEC São José',
                'municipio_id' => null,
                'redec_id' => $redecIds[0],
                'responsavel_nome' => 'Maria Santos',
                'responsavel_cpf' => '987.654.321-00',
                'latitude' => -27.6103,
                'longitude' => -48.6331,
            ],
            [
                'codigo' => 'COMPDEC-BLUMENAU',
                'nome' => 'COMPDEC Blumenau',
                'municipio_id' => null,
                'redec_id' => $redecIds[1],
                'responsavel_nome' => 'Carlos Oliveira',
                'responsavel_cpf' => '456.789.123-00',
                'latitude' => -26.9191,
                'longitude' => -49.0661,
            ],
            [
                'codigo' => 'COMPDEC-JOINVILLE',
                'nome' => 'COMPDEC Joinville',
                'municipio_id' => null,
                'redec_id' => $redecIds[2],
                'responsavel_nome' => 'Ana Paula',
                'responsavel_cpf' => '321.654.987-00',
                'latitude' => -26.3045,
                'longitude' => -48.8487,
            ],
        ];

        foreach ($compdecs as $compdec) {
            DB::table('orgaos')->updateOrInsert(
                ['codigo' => $compdec['codigo']],
                [
                    'nome' => $compdec['nome'],
                    'tipo' => 'compdec',
                    'municipio_id' => $compdec['municipio_id'],
                    'orgao_superior_id' => $compdec['redec_id'],
                    'status' => 'ativo',
                    'email' => strtolower(str_replace('-', '', $compdec['codigo'])) . '@defesacivil.sc.gov.br',
                    'telefone' => '(' . rand(47, 49) . ') ' . rand(3000, 3999) . '-' . rand(1000, 9999),
                    'responsavel_nome' => $compdec['responsavel_nome'],
                    'responsavel_cpf' => $compdec['responsavel_cpf'],
                    'responsavel_email' => strtolower(explode(' ', $compdec['responsavel_nome'])[0]) . '@' . strtolower(str_replace('-', '', $compdec['codigo'])) . '.sc.gov.br',
                    'latitude' => $compdec['latitude'] ?? null,
                    'longitude' => $compdec['longitude'] ?? null,
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ Órgãos criados/atualizados com sucesso!');
        $this->command->info('   - 1 CEDEC (Estadual)');
        $this->command->info('   - 3 REDECs (Regionais)');
        $this->command->info('   - 4 COMPDECs (Municipais)');
    }
}
