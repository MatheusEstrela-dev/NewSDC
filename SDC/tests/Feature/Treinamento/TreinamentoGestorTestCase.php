<?php

declare(strict_types=1);

namespace Tests\Feature\Treinamento;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Enums\PublicoAlvo;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\TreinamentoProfessor;
use App\Modules\Treinamento\Models\Turma;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Base dos testes da Area do Gestor (issue #86).
 *
 * O modulo Treinamento vive numa conexao propria (database.treinamento_connection,
 * resolvida por App\Modules\Treinamento\Support\ConexaoTreinamento). Quando ela e
 * DIFERENTE da conexao default (DB_TREINAMENTO_CONNECTION setada para a rodada de
 * teste, apontando para um banco descartavel proprio), Tests\TestCase so envolve em
 * transacao a conexao default + a de log (ver connectionsToTransact la): sem o
 * reforco abaixo, qualquer INSERT/UPDATE feito atraves da conexao do Treinamento
 * ficaria gravado de verdade, mesmo com DatabaseTransactions.
 *
 * Por isso aqui a conexao do Treinamento tambem entra numa transacao, aberta no
 * setUp() e desfeita no tearDown() -- so quando ela e de fato outra conexao, para
 * nao abrir uma segunda transacao na MESMA conexao que o trait ja cobre.
 */
abstract class TreinamentoGestorTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        if (ConexaoTreinamento::nome() !== (string) config('database.default')) {
            DB::connection(ConexaoTreinamento::nome())->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        $nome = ConexaoTreinamento::nome();

        if ($nome !== (string) config('database.default') && DB::connection($nome)->transactionLevel() > 0) {
            DB::connection($nome)->rollBack();
        }

        parent::tearDown();
    }

    /**
     * Usuario com um cargo (role) ja seedado (super-admin, admin, manager, ...).
     */
    protected function usuarioComCargo(string $slug): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::where('slug', $slug)->firstOrFail());

        return $user->fresh();
    }

    /**
     * Usuario sem cargo, com permissoes avulsas (direct permission, sem role).
     *
     * @param  list<string>  $permissoes
     */
    protected function usuarioComPermissoes(array $permissoes): User
    {
        $user = User::factory()->create();

        foreach ($permissoes as $permissao) {
            $user->givePermissionTo($permissao);
        }

        return $user->fresh();
    }

    /** Autentica e ja carimba a sessao (ver Tests\TestCase::actingAs). */
    protected function logarComo(User $user): User
    {
        $this->actingAs($user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function criarCurso(array $overrides = []): Treinamento
    {
        return Treinamento::create(array_merge([
            'titulo' => 'Curso de teste '.Str::random(8),
            'descricao' => 'Descricao do curso de teste.',
            'carga_horaria' => 20,
            'categoria' => CategoriaTreinamento::CURSO->value,
            'tipo' => TipoTreinamento::PRESENCIAL->value,
            'publico_alvo' => PublicoAlvo::SERVIDORES->value,
            'status' => StatusTreinamento::PLANEJADO->value,
            'inscricao_aprovacao_automatica' => false,
        ], $overrides));
    }

    protected function criarCursoEad(array $overrides = []): Treinamento
    {
        return $this->criarCurso(array_merge(['tipo' => TipoTreinamento::EAD->value], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function criarTurma(Treinamento $curso, array $overrides = []): Turma
    {
        return Turma::create(array_merge([
            'treinamento_id' => $curso->id,
            'nome' => 'Turma '.Str::random(6),
            'data_inicio' => now()->toDateString(),
            'data_fim' => now()->addMonth()->toDateString(),
            'numero_vagas' => 10,
        ], $overrides));
    }

    protected function designarProfessor(Treinamento $curso, User $professor): TreinamentoProfessor
    {
        return TreinamentoProfessor::create([
            'treinamento_id' => $curso->id,
            'user_id' => $professor->id,
        ]);
    }

    protected function municipioMg(): Municipio
    {
        return Municipio::factory()->create(['uf' => 'MG']);
    }
}
