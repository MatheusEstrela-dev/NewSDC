<?php

declare(strict_types=1);

namespace Tests\Feature\Decretacoes;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * #83: migracao:converter-decretacoes so converte depois do saneamento.
 *
 * O ALTER TABLE roda dentro da transacao do teste (DDL e transacional no
 * Postgres) e e desfeito no fim, como o resto dos dados.
 */
class ConverterColunasDecretacoesTest extends TestCase
{
    use DatabaseTransactions;

    private function tipo(string $coluna): string
    {
        return (string) DB::table('information_schema.columns')
            ->where('table_name', 'dec_entrada_processos')->where('column_name', $coluna)->value('data_type');
    }

    private function processo(string $autor, ?string $dataDiario): int
    {
        return DB::table('dec_entrada_processos')->insertGetId([
            'created_by' => $autor, 'data_publicacao_diario' => $dataDiario,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_recusa_sem_alterar_quando_ha_valor_que_nao_converte(): void
    {
        if ($this->tipo('data_publicacao_diario') === 'date') {
            $this->markTestSkipped('Colunas ja convertidas neste banco.');
        }

        $user = User::factory()->create();
        $sujo = $this->processo((string) $user->id, 'ontem');
        $inexistente = $this->processo((string) $user->id, '31/02/2024');
        $semAutor = $this->processo('importacao', '2024-05-01');

        $this->artisan('migracao:converter-decretacoes')
            ->expectsOutputToContain('Nada foi alterado')
            ->assertExitCode(1);

        $this->assertSame('character varying', $this->tipo('data_publicacao_diario'));
        $this->assertSame('ontem', DB::table('dec_entrada_processos')->where('id', $sujo)->value('data_publicacao_diario'));
        $this->assertNotNull($inexistente);
        $this->assertNotNull($semAutor);
    }

    public function test_converte_formatos_aceitos_e_e_idempotente(): void
    {
        if ($this->tipo('data_publicacao_diario') === 'date') {
            $this->markTestSkipped('Colunas ja convertidas neste banco.');
        }

        // Linhas que ja existirem no banco de dev tambem entram no ALTER.
        $this->artisan('migracao:converter-decretacoes', ['--dry-run' => true]);

        $user = User::factory()->create();
        $iso = $this->processo((string) $user->id, '2024-05-01');
        $br = $this->processo((string) $user->id, '15/03/2023');
        $vazio = $this->processo((string) $user->id, '');

        $this->artisan('migracao:converter-decretacoes', ['--dry-run' => true])->assertExitCode(0);
        $this->assertSame('character varying', $this->tipo('created_by'), 'dry-run nao altera');

        $this->artisan('migracao:converter-decretacoes')->assertExitCode(0);

        $this->assertSame('date', $this->tipo('data_publicacao_diario'));
        $this->assertSame('bigint', $this->tipo('created_by'));
        $this->assertSame('2024-05-01', (string) DB::table('dec_entrada_processos')->where('id', $iso)->value('data_publicacao_diario'));
        $this->assertSame('2023-03-15', (string) DB::table('dec_entrada_processos')->where('id', $br)->value('data_publicacao_diario'));
        $this->assertNull(DB::table('dec_entrada_processos')->where('id', $vazio)->value('data_publicacao_diario'));
        $this->assertSame($user->id, (int) DB::table('dec_entrada_processos')->where('id', $iso)->value('created_by'));

        $this->artisan('migracao:converter-decretacoes')->expectsOutputToContain('ja estao convertidas')->assertExitCode(0);
    }
}
