<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Migration 2026_11_04_100100: copia atribuicoes DIRETAS (model_has_permissions)
 * dos slugs de origem para os slugs do Acompanhamento.
 *
 * Executa up()/down() da propria migration dentro da transacao do teste
 * (DatabaseTransactions; DDL no Postgres e transacional, a tabela de registro
 * some no rollback). Nao depende de a migration ja ter rodado no banco.
 * Depende de os slugs existirem (criados por 2026_10_29_100200/120000);
 * se faltarem, o teste e skipped.
 */
class TdapCopiaPermissoesDiretasMigrationTest extends TestCase
{
    use DatabaseTransactions;

    private const ARQUIVO = 'database/migrations/2026_11_04_100100_copia_permissoes_diretas_do_acompanhamento_tdap.php';

    private function migration(): Migration
    {
        return require base_path(self::ARQUIVO);
    }

    private function idDe(string $slug): int
    {
        $id = DB::table('permissions')->where('name', $slug)->where('guard_name', 'web')->value('id');
        if ($id === null) {
            $this->markTestSkipped("Permissao {$slug} nao existe neste banco.");
        }

        return (int) $id;
    }

    private function atribuir(int $permissao, User $user, ?string $expira = null): void
    {
        DB::table('model_has_permissions')->insert([
            'permission_id' => $permissao,
            'model_type'    => $user->getMorphClass(),
            'model_id'      => $user->id,
            'expires_at'    => $expira,
        ]);
    }

    private function tem(int $permissao, User $user): bool
    {
        return DB::table('model_has_permissions')
            ->where('permission_id', $permissao)
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->id)
            ->exists();
    }

    public function test_copia_direta_preserva_validade_e_e_idempotente(): void
    {
        $view = $this->idDe('tdap.cronogramas.view');
        $acompanhamento = $this->idDe('tdap.acompanhamento.view');
        $validar = $this->idDe('tdap.viagens.validar');
        $analisar = $this->idDe('tdap.cronogramas.analisar');

        $permanente = User::factory()->create();
        $temporario = User::factory()->create();
        $vencido = User::factory()->create();
        $expira = now()->addDays(10)->startOfSecond()->toDateTimeString();

        $this->atribuir($view, $permanente);
        $this->atribuir($validar, $temporario, $expira);
        $this->atribuir($validar, $vencido, now()->subDay()->toDateTimeString());

        $m = $this->migration();
        $m->up();
        $m->up(); // segunda vez: sem erro e sem duplicar

        $this->assertTrue($this->tem($acompanhamento, $permanente));
        $this->assertSame(
            $expira,
            DB::table('model_has_permissions')->where('permission_id', $analisar)
                ->where('model_id', $temporario->id)->value('expires_at'),
            'validade da atribuicao temporaria deve ser copiada'
        );
        $this->assertFalse($this->tem($analisar, $vencido), 'atribuicao vencida nao e copiada');
    }

    public function test_nao_reativa_slug_desativado(): void
    {
        $create = $this->idDe('tdap.cronogramas.create');
        $enviar = $this->idDe('tdap.cronogramas.enviar');
        DB::table('permissions')->where('id', $enviar)->update(['is_active' => false]);

        $user = User::factory()->create();
        $this->atribuir($create, $user);

        $this->migration()->up();

        $this->assertFalse($this->tem($enviar, $user));
        $this->assertFalse((bool) DB::table('permissions')->where('id', $enviar)->value('is_active'));
    }

    public function test_down_remove_so_o_que_o_up_inseriu(): void
    {
        $ativar = $this->idDe('tdap.cronogramas.ativar');
        $encerrar = $this->idDe('tdap.cronogramas.encerrar');

        $copiado = User::factory()->create();
        $jaTinha = User::factory()->create();
        $this->atribuir($ativar, $copiado);
        $this->atribuir($ativar, $jaTinha);
        $this->atribuir($encerrar, $jaTinha); // atribuicao manual previa

        $m = $this->migration();
        $m->up();
        $this->assertTrue($this->tem($encerrar, $copiado));

        $m->down();

        $this->assertFalse($this->tem($encerrar, $copiado), 'copia do up sai no down');
        $this->assertTrue($this->tem($encerrar, $jaTinha), 'atribuicao previa fica');
        $this->assertTrue($this->tem($ativar, $copiado), 'origem intacta');
    }
}
