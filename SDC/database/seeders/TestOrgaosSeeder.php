<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestOrgaosSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Criando orgaos de teste...');

        $now = now();

        $cedecExistente = DB::table('orgaos')->where('codigo', 'TEST-CEDEC-MG')->first();
        if ($cedecExistente) {
            $this->command->warn('Orgaos de teste ja existem. Pulando...');
            return;
        }

        $cedecId = DB::table('orgaos')->insertGetId([
            'codigo' => 'TEST-CEDEC-MG',
            'nome' => 'Coordenadoria Estadual de Defesa Civil - MG (Teste)',
            'tipo' => 'cedec',
            'municipio_id' => null,
            'orgao_superior_id' => null,
            'status' => 'ativo',
            'email' => 'cedec.teste@defesa.mg.gov.br',
            'telefone' => '(31) 3333-0001',
            'endereco' => 'Cidade Administrativa, Belo Horizonte - MG',
            'responsavel_nome' => 'Coordenador CEDEC Teste',
            'responsavel_cpf' => '00000000001',
            'responsavel_telefone' => '(31) 99999-0001',
            'responsavel_email' => 'coord.cedec.teste@defesa.mg.gov.br',
            'latitude' => -19.8157,
            'longitude' => -43.9542,
            'abrangencia' => json_encode([]),
            'metadata' => json_encode(['ambiente' => 'teste']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $redecMetroId = DB::table('orgaos')->insertGetId([
            'codigo' => 'TEST-REDEC-METRO',
            'nome' => 'Regional Metropolitana de Defesa Civil (Teste)',
            'tipo' => 'redec',
            'municipio_id' => null,
            'orgao_superior_id' => $cedecId,
            'status' => 'ativo',
            'email' => 'redec.metro.teste@defesa.mg.gov.br',
            'telefone' => '(31) 3333-0002',
            'endereco' => 'Av. Afonso Pena, 1000 - Centro, Belo Horizonte - MG',
            'responsavel_nome' => 'Coordenador REDEC Metro Teste',
            'responsavel_cpf' => '00000000002',
            'responsavel_telefone' => '(31) 99999-0002',
            'responsavel_email' => 'coord.redec.metro.teste@defesa.mg.gov.br',
            'latitude' => -19.9191,
            'longitude' => -43.9386,
            'abrangencia' => json_encode([]),
            'metadata' => json_encode(['ambiente' => 'teste']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $redecSulId = DB::table('orgaos')->insertGetId([
            'codigo' => 'TEST-REDEC-SUL',
            'nome' => 'Regional Sul de Defesa Civil (Teste)',
            'tipo' => 'redec',
            'municipio_id' => null,
            'orgao_superior_id' => $cedecId,
            'status' => 'ativo',
            'email' => 'redec.sul.teste@defesa.mg.gov.br',
            'telefone' => '(35) 3333-0003',
            'endereco' => 'Rua Principal, 500 - Centro, Pouso Alegre - MG',
            'responsavel_nome' => 'Coordenador REDEC Sul Teste',
            'responsavel_cpf' => '00000000003',
            'responsavel_telefone' => '(35) 99999-0003',
            'responsavel_email' => 'coord.redec.sul.teste@defesa.mg.gov.br',
            'latitude' => -22.2299,
            'longitude' => -45.9369,
            'abrangencia' => json_encode([]),
            'metadata' => json_encode(['ambiente' => 'teste']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $compdecs = [
            [
                'codigo' => 'TEST-COMPDEC-BH',
                'nome' => 'COMPDEC Belo Horizonte (Teste)',
                'orgao_superior_id' => $redecMetroId,
                'email' => 'compdec.bh.teste@defesa.mg.gov.br',
                'telefone' => '(31) 3333-0004',
                'endereco' => 'Av. Amazonas, 500 - Centro, Belo Horizonte - MG',
                'responsavel_nome' => 'Coordenador COMPDEC BH Teste',
                'responsavel_cpf' => '00000000004',
                'latitude' => -19.9167,
                'longitude' => -43.9345,
            ],
            [
                'codigo' => 'TEST-COMPDEC-CONTAGEM',
                'nome' => 'COMPDEC Contagem (Teste)',
                'orgao_superior_id' => $redecMetroId,
                'email' => 'compdec.contagem.teste@defesa.mg.gov.br',
                'telefone' => '(31) 3333-0005',
                'endereco' => 'Av. Joao Cesar, 200 - Centro, Contagem - MG',
                'responsavel_nome' => 'Coordenador COMPDEC Contagem Teste',
                'responsavel_cpf' => '00000000005',
                'latitude' => -19.9318,
                'longitude' => -44.0539,
            ],
            [
                'codigo' => 'TEST-COMPDEC-POUSO',
                'nome' => 'COMPDEC Pouso Alegre (Teste)',
                'orgao_superior_id' => $redecSulId,
                'email' => 'compdec.pouso.teste@defesa.mg.gov.br',
                'telefone' => '(35) 3333-0006',
                'endereco' => 'Praca da Matriz, 100 - Centro, Pouso Alegre - MG',
                'responsavel_nome' => 'Coordenador COMPDEC Pouso Alegre Teste',
                'responsavel_cpf' => '00000000006',
                'latitude' => -22.2300,
                'longitude' => -45.9370,
            ],
            [
                'codigo' => 'TEST-COMPDEC-VARGINHA',
                'nome' => 'COMPDEC Varginha (Teste)',
                'orgao_superior_id' => $redecSulId,
                'email' => 'compdec.varginha.teste@defesa.mg.gov.br',
                'telefone' => '(35) 3333-0007',
                'endereco' => 'Rua Wenceslau Braz, 300 - Centro, Varginha - MG',
                'responsavel_nome' => 'Coordenador COMPDEC Varginha Teste',
                'responsavel_cpf' => '00000000007',
                'latitude' => -21.5514,
                'longitude' => -45.4303,
            ],
        ];

        foreach ($compdecs as $compdec) {
            DB::table('orgaos')->insert(array_merge($compdec, [
                'tipo' => 'compdec',
                'municipio_id' => null,
                'status' => 'ativo',
                'responsavel_telefone' => '(00) 99999-0000',
                'responsavel_email' => str_replace('@defesa', '.coord@defesa', $compdec['email']),
                'abrangencia' => json_encode([]),
                'metadata' => json_encode(['ambiente' => 'teste']),
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command->info('Orgaos de teste criados:');
        $this->command->table(
            ['Codigo', 'Tipo', 'Superior'],
            [
                ['TEST-CEDEC-MG', 'cedec', '-'],
                ['TEST-REDEC-METRO', 'redec', 'TEST-CEDEC-MG'],
                ['TEST-REDEC-SUL', 'redec', 'TEST-CEDEC-MG'],
                ['TEST-COMPDEC-BH', 'compdec', 'TEST-REDEC-METRO'],
                ['TEST-COMPDEC-CONTAGEM', 'compdec', 'TEST-REDEC-METRO'],
                ['TEST-COMPDEC-POUSO', 'compdec', 'TEST-REDEC-SUL'],
                ['TEST-COMPDEC-VARGINHA', 'compdec', 'TEST-REDEC-SUL'],
            ]
        );
    }
}
