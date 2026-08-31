<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Publica as 19 REDECs de Minas Gerais em compdec_orgaos.
 *
 * O catalogo autoritativo das regionais e `dec_redecs` (carregado pela migration
 * 2026_08_25_100000_create_dec_redecs_table e ressincronizado por RedecSeeder),
 * mas quem alimenta o seletor "Orgao Principal" do Permissionamento -- e o escopo
 * territorial de PMDA, COMPDEC e Ajuda Humanitaria -- e `compdec_orgaos`. As duas
 * tabelas nunca foram ligadas: em compdec_orgaos so existiam 3 regionais de SANTA
 * CATARINA, vindas do OrgaosSeeder (seeder de demonstracao: CEDEC-SC,
 * REDEC-01-FLORIANOPOLIS...), mais 2 placeholders "(Teste)".
 *
 * Este seeder DERIVA de dec_redecs em vez de repetir a lista: duas copias da
 * mesma tabela de dominio saem de sincronia na primeira vez que uma regional e
 * renomeada, e a fonte de verdade tem de continuar sendo uma.
 *
 * Idempotente pelo `codigo`, como OrgaosSeeder. NAO remove os orgaos de Santa
 * Catarina: eles tem usuarios lotados (15 no total), e apagar deixaria esses
 * usuarios sem orgao. A limpeza e decisao de operacao, nao de seed.
 */
class RedecOrgaoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('dec_redecs')) {
            $this->command?->warn('Tabela "dec_redecs" nao encontrada - RedecOrgaoSeeder pulado.');

            return;
        }

        $regionais = DB::table('dec_redecs')->orderBy('id')->get();

        if ($regionais->isEmpty()) {
            $this->command?->warn('dec_redecs vazia - rode RedecSeeder antes.');

            return;
        }

        $cedecId = $this->cedecDeMinas();

        foreach ($regionais as $redec) {
            DB::table('compdec_orgaos')->updateOrInsert(
                ['codigo' => $this->codigo((int) $redec->id)],
                [
                    'nome'              => $redec->nome,
                    'tipo'              => 'redec',
                    // Regional cobre varios municipios: nao ha municipio_id unico.
                    'municipio_id'      => null,
                    'orgao_superior_id' => $cedecId,
                    'status'            => $redec->ativo ? 'ativo' : 'inativo',
                    'responsavel_nome'  => 'Coordenador '.$redec->sigla,
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );
        }

        $this->command?->info(sprintf(
            '%d REDECs de MG publicadas em compdec_orgaos (superior: %s).',
            $regionais->count(),
            $cedecId === null ? 'nenhuma CEDEC encontrada' : 'orgao #'.$cedecId
        ));
    }

    /**
     * Prefixo MG proprio para nao colidir com o padrao do OrgaosSeeder
     * (REDEC-01-FLORIANOPOLIS), que e de Santa Catarina.
     */
    private function codigo(int $id): string
    {
        return sprintf('REDEC-MG-%02d', $id);
    }

    /**
     * CEDEC de Minas para pendurar a hierarquia.
     *
     * Hoje a unica CEDEC de MG em compdec_orgaos e a "TEST-CEDEC-MG", criada pelo
     * TestOrgaosSeeder -- nao existe uma estadual definitiva. Em vez de inventar
     * uma aqui (o que criaria DUAS estaduais de MG e ambiguidade de lotacao),
     * o seeder usa a que houver e deixa null quando nao houver: a hierarquia
     * pode ser ajustada depois pela tela de Orgaos sem mexer nas 19 linhas.
     */
    private function cedecDeMinas(): ?int
    {
        foreach (['CEDEC-MG', 'TEST-CEDEC-MG'] as $codigo) {
            $id = DB::table('compdec_orgaos')->where('codigo', $codigo)->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        return null;
    }
}
