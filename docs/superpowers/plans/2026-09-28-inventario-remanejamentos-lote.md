# Inventario de TI — Remanejamentos em lote — Plano de implementacao

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Levar para o NewSDC o remanejamento em lote do `cedec-demanda` — registrar varias pessoas trocando de estacao com seus equipamentos num lote atomico, listar, expandir, desfazer (tudo ou nada), editar, exportar a planilha XLSX, avisar a SEPLAG por e-mail e registrar o chamado do lote.

**Architecture:** Novo agregado `Remanejamento` (cabecalho do lote, uuid) com `RemanejamentoPessoa` (unidade do lote) e as `Movimentacao` existentes ligadas por `lote_id`/`remanejamento_pessoa_id`. Toda regra vive em `RemanejamentoService` (DDD do modulo: FormRequest -> Controller fino -> DTO -> Service), com `lockForUpdate` em ordem crescente de id e a excecao de dominio `RemanejamentoProibido` (filha de `ValidationException`, entao vira 422/erros de sessao sem `try/catch`). Integracoes: `RegistrarChamadoDoLote` (Inventario -> Demandas pelos casos de uso publicos), `PlanilhaRemanejamento` (openspout) e `EnviarRemanejamentoSeplag` (Mailable em fila). Frontend em Atomic Design como o Catalogo de demandas.

**Tech Stack:** PHP 8.3/8.4, Laravel 12, PostgreSQL, Spatie Permission, Inertia + Vue 3, Tailwind, `openspout/openspout` ^4, PHPUnit com `DatabaseTransactions`.

**Spec:** `docs/superpowers/specs/2026-09-28-inventario-remanejamentos-lote-design.md`

## Global Constraints

- Branch/worktree: `feat/inventario-remanejamentos-lote` em `NewSDC/.worktrees/demandas-paridade-chamados`. Todo caminho abaixo e relativo a `SDC/` salvo indicacao; comandos `git`, `npx` e `bash /c/tmp/teste-demandas.sh` rodam a partir de `SDC/` da worktree.
- Todo arquivo PHP novo comeca com `declare(strict_types=1);`; dependencias injetadas como `private readonly` no construtor.
- Sem emojis no codigo. Comentarios em pt-BR sem acento, no estilo do modulo. Textos de tela e mensagens ao usuario em pt-BR com acento.
- Commits: gitmoji + `tipo(escopo): descricao` em pt-BR, SEM trailer `Co-Authored-By`. Um commit por task.
- Nunca `git add` em `SDC/tests/` (gitignored; testes ficam so no disco).
- Testes rodam SO contra o banco `sdc_test`, via `bash /c/tmp/teste-demandas.sh <comando>` (nunca `sdc` nem `sdc_medalhao`; nunca `migrate:fresh`). Dados de teste com prefixo `TST-`.
- NUNCA `git stash` (a pilha e compartilhada entre worktrees e sessoes). Para guardar trabalho, commit WIP temporario.
- Tasks paralelas commitam com pathspec: `git add <arquivos> && git commit -m "..." -- <arquivos>` (assim um agente nunca leva o arquivo staged de outro).
- Parametros de rota nao podem colidir com `Route::model()` global (`orgao`, `equipe`, `anexo`, `solicitacao`, `representante`, `prestador`, `caminhao`, `ata`, `cronograma`, `cronoCaminhao`, `viagem`, `historico`, `empreendimento`, `protocolo`). O parametro novo e `{remanejamento}`; conferir com `grep -rn "Route::model\|Route::bind" routes app/Providers` antes de criar rota.
- Builds de frontend de tasks paralelas: `npx vite build --outDir C:/tmp/vite-<task> --emptyOutDir` (nunca sobre `public/build` da worktree enquanto outra task builda).
- Migrations: schema consolidado em `2026_09_23_000001_create_inventario_ti_tables.php`; bancos ja migrados recebem `2026_09_28_100000_ajusta_inventario_remanejamentos.php` idempotente (`hasTable`/`hasColumn`/constraint).
- Sem IP fixo, `shell_exec`, API Python, Outlook COM ou e-mail fixo no codigo: destinatarios e assunto vem de `config/inventario.php` (env).
- Logs de depuracao criados durante o trabalho saem antes do commit.

## Review Focus

1. Pessoa remanejada que deixa para tras um equipamento EMPRESTADO ou EM MANUTENCAO: esse equipamento nao pode ser "liberado" (continua com a pessoa e com a situacao que tinha); so os equipamentos comuns viram `disponivel` (teste `test_nao_libera_emprestado_nem_em_manutencao` na Task 2).
2. Desfazer em ordem inversa: lote 2 remaneja de novo o equipamento do lote 1; desfaz o lote 2 e depois o lote 1 — o segundo desfazer deve funcionar (movimentacao `devolvido` nao conta como "posterior") e o equipamento volta ao dono original (teste `test_desfazer_em_ordem_inversa_funciona` na Task 4).
3. Equipamento liberado pelo lote que foi emprestado depois: desfazer o lote seria devolver ao dono antigo algo que esta com outra pessoa — tem de ser recusado com mensagem citando o equipamento, e nada muda (teste `test_liberado_movimentado_depois_bloqueia_desfazer` na Task 3).
4. URL de lote com id que nao e uuid (`/inventario/remanejamentos/123/editar`): 404, nunca 500 de cast do PostgreSQL (teste `test_id_que_nao_e_uuid_da_404` na Task 11).
5. Categoria, nome de estacao ou ponto de rede cadastrado comecando com `=`/`+`/`-`/`@`: a planilha enviada a SEPLAG sai com o texto neutralizado, nunca formula (teste `test_texto_com_formula_sai_neutralizado` na Task 7).

---

## Mapa de arquivos

**Criar**
- `database/migrations/2026_09_28_100000_ajusta_inventario_remanejamentos.php`
- `app/Modules/Inventario/Enums/{StatusRemanejamento,TipoMovimentacao,StatusMovimentacao}.php`
- `app/Modules/Inventario/Models/{Remanejamento,RemanejamentoPessoa}.php`
- `app/Modules/Inventario/DTOs/{RemanejamentoData,PessoaRemanejadaData}.php`
- `app/Modules/Inventario/Exceptions/RemanejamentoProibido.php`
- `app/Modules/Inventario/Services/{RemanejamentoService,PlanilhaRemanejamento,RegistrarChamadoDoLote,EnviarRemanejamentoSeplag}.php`
- `app/Modules/Inventario/Mail/RemanejamentoSeplagMail.php`
- `app/Modules/Inventario/Observers/RemanejamentoTempoRealObserver.php`
- `app/Modules/Inventario/Requests/SalvarRemanejamentoRequest.php`
- `app/Modules/Inventario/Controllers/RemanejamentoController.php`
- `app/Modules/Inventario/Queries/{RemanejamentoListagemQuery,OpcoesRemanejamentoQuery}.php`
- `app/Modules/Inventario/Support/RemanejamentoApresentacao.php`
- `app/Modules/Shared/Support/CsvSeguro.php` (movido de Demandas)
- `config/inventario.php`
- `resources/views/emails/inventario_remanejamento_seplag.blade.php`
- `resources/js/Components/Emails/Organisms/RemanejamentoSeplagBody.vue`
- Frontend: ver Tasks 13 e 14.
- Testes (nao versionados): `tests/Feature/Inventario/**`.

**Modificar**
- `database/migrations/2026_09_23_000001_create_inventario_ti_tables.php`
- `app/Modules/Inventario/Models/Movimentacao.php`
- `app/Modules/Inventario/Services/MovimentacaoService.php`
- `app/Modules/Inventario/Controllers/MovimentacaoController.php`
- `app/Modules/Inventario/InventarioServiceProvider.php`
- `app/Modules/Shared/Support/CanaisDeListagem.php`
- `app/Modules/Demandas/Controllers/DemandaDashboardController.php`, `app/Modules/Demandas/Services/DemandaCsvExporter.php`
- `app/Services/Mail/VueEmailRenderer.php`, `resources/js/email-ssr.ts`
- `config/permissions.php`, `routes/modules/inventario.php`, `composer.json`, `composer.lock`, `.env.example`
- `resources/js/Pages/Inventario/MovimentacoesIndex.vue`

**Remover**
- `app/Modules/Demandas/Support/CsvSeguro.php`

## Ordem e paralelismo

| Fase | Tasks | Paralelismo |
|---|---|---|
| 1 — Dados e dominio | 1, 2, 3, 4, 5 | Sequencial (2-4 editam o mesmo `RemanejamentoService`). |
| 2 — Integracoes e HTTP | 6, 7, 8, 9, 10, 11, 12 | Onda A: **6, 7, 8, 9 em paralelo** (arquivos disjuntos; o Step 1 da Task 7 — `composer require` — roda ANTES de despachar as outras tres). Onda B: 10 (depende de 7 e 8). Onda C: 11 (depende de todas). 12 fecha. |
| 3 — Frontend | 13, 14, 15 | **13 e 14 em paralelo** (arquivos disjuntos). 15 fecha. |
| 4 — Verificacao | 16, 17 | Sequencial. |

---

### Task 0: Conferir o ambiente da worktree

Sem commit. A worktree ja tem `.env`, `vendor` e `public/build` da entrega anterior; aqui so se confirma.

**Files:** nenhum versionado.

- [ ] **Step 1: Conferir vendor, script de teste e branch**

```bash
cd /c/Users/x24679188/Documents/Github/NewSDC/.worktrees/demandas-paridade-chamados/SDC
git branch --show-current
ls vendor/bin/phpunit .env public/build/manifest.json
grep -c "DB_DATABASE=sdc_test" /c/tmp/teste-demandas.sh
```
Expected: `feat/inventario-remanejamentos-lote`; os tres arquivos existem; `1`. Se `vendor/bin/phpunit` faltar, rodar o Step 2 da Task 0 do plano `2026-09-24-demandas-paridade-chamados.md` (composer install pelo container `newsdc-swoole-dev:latest`).

- [ ] **Step 2: `sdc_test` no schema atual e baseline**

```bash
bash /c/tmp/teste-demandas.sh php artisan migrate --force
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas
```
Expected: migrate sem erro; suite Demandas verde. Anotar o resultado como baseline.

---

## FASE 1 — Dados e dominio

Entrega: schema, models, DTOs e `RemanejamentoService` (registrar, desfazer, editar). A tela antiga de movimentacoes continua funcionando sem mudanca.

### Task 1: Schema, models e enums do lote

**Files:**
- Modify: `database/migrations/2026_09_23_000001_create_inventario_ti_tables.php`
- Create: `database/migrations/2026_09_28_100000_ajusta_inventario_remanejamentos.php`
- Create: `app/Modules/Inventario/Enums/StatusRemanejamento.php`, `app/Modules/Inventario/Enums/TipoMovimentacao.php`, `app/Modules/Inventario/Enums/StatusMovimentacao.php`
- Create: `app/Modules/Inventario/Models/Remanejamento.php`, `app/Modules/Inventario/Models/RemanejamentoPessoa.php`
- Modify: `app/Modules/Inventario/Models/Movimentacao.php`
- Test: `tests/Feature/Inventario/SchemaRemanejamentoTest.php`, `tests/Feature/Inventario/Concerns/CriaInventario.php`

**Interfaces:**
- Produces:
  - Tabelas `inventario_ti_remanejamentos` (uuid), `inventario_ti_remanejamento_pessoas`; colunas `inventario_ti_movimentacoes.remanejamento_pessoa_id`, `situacao_origem`; FK `inventario_ti_movimentacoes_lote_id_foreign`.
  - `StatusRemanejamento::{ATIVO,DESFEITO}` (`ativo|desfeito`), `label(): string`, `static options(): list<array{value:string,label:string}>`.
  - `TipoMovimentacao::{EMPRESTIMO,REMANEJAMENTO,LIBERACAO}` (`emprestimo|remanejamento|liberacao`), `label()`.
  - `StatusMovimentacao::{ATIVO,DEVOLVIDO,SUBSTITUIDA}` (`ativo|devolvido|substituida`), `label()` (`Ativo|Devolvido|Substituída`).
  - `Remanejamento` (HasUuids; casts `status` -> `StatusRemanejamento`, `seplag_enviado_em`/`desfeito_em` datetime): `registradoPor()`, `desfeitoPor()`, `seplagEnviadoPor()`, `demanda()`, `pessoas(): HasMany`, `movimentacoes(): HasMany` (por `lote_id`), `itensRemanejados(): HasMany` (so `tipo=remanejamento`), `estaAtivo(): bool`.
  - `RemanejamentoPessoa`: `remanejamento()`, `usuario()`, `estacaoOrigem()`, `estacaoDestino()`, `usuarioAnteriorDoDestino()`, `movimentacoes()`.
  - `Movimentacao` ganha `remanejamento()` (BelongsTo por `lote_id`), `pessoa()`, `usuarioOrigem()`, `usuarioDestino()`, `estacaoOrigem()`, `estacaoDestino()`; `tipo`/`status` continuam string (sem cast, para nao quebrar `MovimentacaoService`).
  - Trait de teste `Tests\Feature\Inventario\Concerns\CriaInventario` (usado por todas as tasks seguintes).

- [ ] **Step 1: Criar o trait de teste**

`tests/Feature/Inventario/Concerns/CriaInventario.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario\Concerns;

use App\Models\User;
use App\Modules\Inventario\DTOs\RemanejamentoData;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Estacao;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Services\RemanejamentoService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

trait CriaInventario
{
    protected const PERMISSOES_OPERADOR = [
        'inventario.emprestimos.view', 'inventario.emprestimos.create', 'inventario.emprestimos.return',
        'inventario.emprestimos.export', 'inventario.remanejamentos.create', 'inventario.remanejamentos.edit',
        'inventario.remanejamentos.seplag',
    ];

    protected function usuarioCom(array $permissoes): User
    {
        foreach ($permissoes as $nome) {
            Permission::firstOrCreate(['name' => $nome, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo($permissoes);

        return $user;
    }

    protected function operador(): User
    {
        return $this->usuarioCom(self::PERMISSOES_OPERADOR);
    }

    protected function pessoa(): User
    {
        return User::factory()->create();
    }

    protected function estacao(?User $ocupante = null, array $atributos = []): Estacao
    {
        return Estacao::create(array_merge([
            'nome' => 'TST-EST-'.uniqid(),
            'ponto_rede' => 'PR-'.random_int(1000, 9999),
            'user_id' => $ocupante?->id,
        ], $atributos));
    }

    protected function equipamento(?User $dono = null, ?Estacao $estacao = null, array $atributos = []): Equipamento
    {
        return Equipamento::create(array_merge([
            'nome' => 'TST- notebook',
            'patrimonio' => 'TST-'.uniqid(),
            'numero_serie' => 'SN-'.uniqid(),
            'ramal' => '3916-0000',
            'user_id' => $dono?->id,
            'estacao_id' => $estacao?->id,
            'situacao' => $dono !== null ? SituacaoEquipamento::EM_USO : SituacaoEquipamento::DISPONIVEL,
            'emprestavel' => true,
            'quantidade' => 1,
        ], $atributos));
    }

    /** @param list<Equipamento> $equipamentos */
    protected function pessoaNoLote(User $usuario, ?Estacao $origem, ?Estacao $destino, array $equipamentos, ?string $condicao = null): array
    {
        return [
            'usuario_id' => $usuario->id,
            'estacao_origem_id' => $origem?->id,
            'estacao_destino_id' => $destino?->id,
            'condicao_destino' => $condicao,
            'equipamento_ids' => array_map(static fn (Equipamento $e): int => $e->id, $equipamentos),
        ];
    }

    protected function dadosLote(User $autor, array $pessoas, ?string $observacao = null): RemanejamentoData
    {
        return RemanejamentoData::fromArray(['observacao' => $observacao, 'pessoas' => $pessoas], (int) $autor->id);
    }

    protected function registrarLote(User $autor, array $pessoas, ?string $observacao = null): Remanejamento
    {
        return app(RemanejamentoService::class)->registrar($this->dadosLote($autor, $pessoas, $observacao));
    }

    /** Emprestimo ativo direto no banco, como o fluxo avulso deixa. */
    protected function emprestar(Equipamento $equipamento, User $para, User $autor): Movimentacao
    {
        $movimentacao = Movimentacao::create([
            'equipamento_id' => $equipamento->id,
            'registrado_por_id' => $autor->id,
            'usuario_origem_id' => $equipamento->user_id,
            'usuario_destino_id' => $para->id,
            'estacao_origem_id' => $equipamento->estacao_id,
            'tipo' => 'emprestimo',
            'status' => 'ativo',
            'quantidade' => $equipamento->quantidade,
            'data_saida' => now(),
        ]);
        $equipamento->update(['user_id' => $para->id, 'situacao' => SituacaoEquipamento::EM_USO]);

        return $movimentacao;
    }
}
```

- [ ] **Step 2: Write the failing test**

`tests/Feature/Inventario/SchemaRemanejamentoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class SchemaRemanejamentoTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    public function test_tabelas_e_colunas_novas_existem(): void
    {
        $this->assertTrue(Schema::hasTable('inventario_ti_remanejamentos'));
        $this->assertTrue(Schema::hasTable('inventario_ti_remanejamento_pessoas'));
        $this->assertTrue(Schema::hasColumns('inventario_ti_movimentacoes', ['remanejamento_pessoa_id', 'situacao_origem']));
        $this->assertTrue(Schema::hasColumns('inventario_ti_remanejamentos', [
            'registrado_por_id', 'observacao', 'status', 'demanda_id', 'seplag_enviado_em',
            'seplag_enviado_por_id', 'seplag_envios', 'desfeito_em', 'desfeito_por_id',
        ]));
    }

    public function test_lote_id_virou_fk_para_remanejamentos(): void
    {
        $fk = DB::selectOne(
            "select count(*) as n from pg_constraint where conname = 'inventario_ti_movimentacoes_lote_id_foreign'"
        );
        $this->assertSame(1, (int) $fk->n);
    }

    public function test_relacoes_do_lote(): void
    {
        $autor = $this->operador();
        $pessoa = $this->pessoa();
        $destino = $this->estacao();
        $equipamento = $this->equipamento($pessoa);

        $lote = Remanejamento::create(['registrado_por_id' => $autor->id]);
        $registro = $lote->pessoas()->create([
            'usuario_id' => $pessoa->id,
            'estacao_destino_id' => $destino->id,
            'estacao_origem_ficou_vazia' => true,
        ]);
        Movimentacao::create([
            'equipamento_id' => $equipamento->id, 'lote_id' => $lote->id, 'remanejamento_pessoa_id' => $registro->id,
            'tipo' => 'remanejamento', 'status' => 'ativo', 'situacao_origem' => 'em_uso', 'data_saida' => now(),
        ]);
        Movimentacao::create([
            'equipamento_id' => $equipamento->id, 'lote_id' => $lote->id, 'remanejamento_pessoa_id' => $registro->id,
            'tipo' => 'liberacao', 'status' => 'ativo', 'data_saida' => now(),
        ]);

        $lote->refresh();
        $this->assertTrue(Str::isUuid($lote->id));
        $this->assertSame(StatusRemanejamento::ATIVO, $lote->status);
        $this->assertSame(0, $lote->seplag_envios);
        $this->assertTrue($lote->estaAtivo());
        $this->assertCount(1, $lote->pessoas);
        $this->assertCount(2, $lote->movimentacoes);
        $this->assertCount(1, $lote->itensRemanejados);
        $this->assertSame($lote->id, $lote->itensRemanejados->first()->remanejamento->id);
        $this->assertSame($destino->id, $registro->estacaoDestino->id);
        $this->assertTrue($registro->estacao_origem_ficou_vazia);
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/SchemaRemanejamentoTest.php`
Expected: FAIL (tabelas e classes inexistentes).

- [ ] **Step 4: Consolidar na migration principal**

Em `2026_09_23_000001_create_inventario_ti_tables.php`, entre o `Schema::create('inventario_ti_equipamentos', ...)` e o `Schema::create('inventario_ti_movimentacoes', ...)`, inserir:

```php
        // Cabecalho do lote de remanejamento. Vem antes de movimentacoes porque
        // movimentacoes.lote_id aponta para ele.
        Schema::create('inventario_ti_remanejamentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->string('status', 20)->default('ativo')->index();
            $table->foreignId('demanda_id')->nullable()->unique()->constrained('tasks')->nullOnDelete();
            $table->timestamp('seplag_enviado_em')->nullable();
            $table->foreignId('seplag_enviado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('seplag_envios')->default(0);
            $table->timestamp('desfeito_em')->nullable();
            $table->foreignId('desfeito_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // Unidade do lote: uma pessoa com origem, destino e o que leva junto.
        Schema::create('inventario_ti_remanejamento_pessoas', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('remanejamento_id')->constrained('inventario_ti_remanejamentos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('estacao_origem_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
            $table->foreignId('estacao_destino_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
            $table->foreignId('estacao_destino_usuario_anterior_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('estacao_origem_ficou_vazia')->default(false);
            $table->string('condicao_destino', 60)->nullable();
            $table->timestamps();
            $table->unique(['remanejamento_id', 'usuario_id']);
        });
```

No `Schema::create('inventario_ti_movimentacoes', ...)`, trocar a linha `$table->uuid('lote_id')->nullable()->index();` por:

```php
            $table->foreignUuid('lote_id')->nullable()->constrained('inventario_ti_remanejamentos')->nullOnDelete();
            $table->foreignId('remanejamento_pessoa_id')->nullable()->constrained('inventario_ti_remanejamento_pessoas')->nullOnDelete();
            $table->string('situacao_origem', 30)->nullable();
```
e, junto do `$table->index(['equipamento_id', 'status']);`, acrescentar `$table->index('lote_id');`.

Substituir o `down()` por:

```php
    public function down(): void
    {
        Schema::dropIfExists('inventario_ti_movimentacoes');
        Schema::dropIfExists('inventario_ti_remanejamento_pessoas');
        Schema::dropIfExists('inventario_ti_remanejamentos');
        Schema::dropIfExists('inventario_ti_equipamentos');
        Schema::dropIfExists('inventario_ti_estacoes');
        Schema::dropIfExists('inventario_ti_categorias');
    }
```

- [ ] **Step 5: Migration de ajuste para bancos ja migrados**

`database/migrations/2026_09_28_100000_ajusta_inventario_remanejamentos.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Leva bancos que ja rodaram 2026_09_23_000001 ao schema que a instalacao limpa
 * produz. Em instalacao limpa tudo aqui e no-op.
 */
return new class extends Migration
{
    private const FK_LOTE = 'inventario_ti_movimentacoes_lote_id_foreign';

    public function up(): void
    {
        if (! Schema::hasTable('inventario_ti_remanejamentos')) {
            Schema::create('inventario_ti_remanejamentos', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('observacao')->nullable();
                $table->string('status', 20)->default('ativo')->index();
                $table->foreignId('demanda_id')->nullable()->unique()->constrained('tasks')->nullOnDelete();
                $table->timestamp('seplag_enviado_em')->nullable();
                $table->foreignId('seplag_enviado_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('seplag_envios')->default(0);
                $table->timestamp('desfeito_em')->nullable();
                $table->foreignId('desfeito_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('inventario_ti_remanejamento_pessoas')) {
            Schema::create('inventario_ti_remanejamento_pessoas', function (Blueprint $table): void {
                $table->id();
                $table->foreignUuid('remanejamento_id')->constrained('inventario_ti_remanejamentos')->cascadeOnDelete();
                $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('estacao_origem_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
                $table->foreignId('estacao_destino_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
                $table->foreignId('estacao_destino_usuario_anterior_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('estacao_origem_ficou_vazia')->default(false);
                $table->string('condicao_destino', 60)->nullable();
                $table->timestamps();
                $table->unique(['remanejamento_id', 'usuario_id']);
            });
        }

        if (! Schema::hasColumn('inventario_ti_movimentacoes', 'remanejamento_pessoa_id')) {
            Schema::table('inventario_ti_movimentacoes', function (Blueprint $table): void {
                $table->foreignId('remanejamento_pessoa_id')->nullable()->after('lote_id')
                    ->constrained('inventario_ti_remanejamento_pessoas')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('inventario_ti_movimentacoes', 'situacao_origem')) {
            Schema::table('inventario_ti_movimentacoes', function (Blueprint $table): void {
                $table->string('situacao_origem', 30)->nullable()->after('status');
            });
        }

        if (! $this->temConstraint(self::FK_LOTE)) {
            // lote_id existia sem FK; qualquer valor sem cabecalho impediria a FK.
            DB::statement(
                'UPDATE inventario_ti_movimentacoes SET lote_id = NULL
                 WHERE lote_id IS NOT NULL AND lote_id NOT IN (SELECT id FROM inventario_ti_remanejamentos)'
            );
            Schema::table('inventario_ti_movimentacoes', function (Blueprint $table): void {
                $table->foreign('lote_id')->references('id')->on('inventario_ti_remanejamentos')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Sem volta: a principal ja descreve o estado final e o down dela derruba tudo.
    }

    private function temConstraint(string $nome): bool
    {
        return (int) DB::selectOne('select count(*) as n from pg_constraint where conname = ?', [$nome])->n > 0;
    }
};
```

- [ ] **Step 6: Enums**

`app/Modules/Inventario/Enums/StatusRemanejamento.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

enum StatusRemanejamento: string
{
    case ATIVO = 'ativo';
    case DESFEITO = 'desfeito';

    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::DESFEITO => 'Desfeito',
        };
    }

    /** @return list<array{value:string,label:string}> */
    public static function options(): array
    {
        return array_map(static fn (self $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }
}
```

`app/Modules/Inventario/Enums/TipoMovimentacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

/**
 * Valores de inventario_ti_movimentacoes.tipo. A coluna continua string sem
 * cast no model: MovimentacaoService compara strings e nao deve quebrar.
 */
enum TipoMovimentacao: string
{
    case EMPRESTIMO = 'emprestimo';
    case REMANEJAMENTO = 'remanejamento';
    // Equipamento que a pessoa remanejada deixou para tras.
    case LIBERACAO = 'liberacao';

    public function label(): string
    {
        return match ($this) {
            self::EMPRESTIMO => 'Empréstimo',
            self::REMANEJAMENTO => 'Remanejamento',
            self::LIBERACAO => 'Liberação',
        };
    }
}
```

`app/Modules/Inventario/Enums/StatusMovimentacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

enum StatusMovimentacao: string
{
    case ATIVO = 'ativo';
    case DEVOLVIDO = 'devolvido';
    // Remanejamento que perdeu efeito porque o equipamento foi remanejado de novo.
    case SUBSTITUIDA = 'substituida';

    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::DEVOLVIDO => 'Devolvido',
            self::SUBSTITUIDA => 'Substituída',
        };
    }

    public static function rotulo(string $valor): string
    {
        return self::tryFrom($valor)?->label() ?? $valor;
    }
}
```

- [ ] **Step 7: Models**

`app/Modules/Inventario/Models/Remanejamento.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cabecalho de um lote de remanejamento. As movimentacoes do lote apontam para
 * ele por lote_id (nome herdado da coluna que ja existia).
 */
class Remanejamento extends Model
{
    use HasUuids;

    protected $table = 'inventario_ti_remanejamentos';

    protected $fillable = [
        'registrado_por_id', 'observacao', 'status', 'demanda_id', 'seplag_enviado_em',
        'seplag_enviado_por_id', 'seplag_envios', 'desfeito_em', 'desfeito_por_id',
    ];

    protected $attributes = [
        'status' => 'ativo',
        'seplag_envios' => 0,
    ];

    protected $casts = [
        'status' => StatusRemanejamento::class,
        'seplag_enviado_em' => 'datetime',
        'desfeito_em' => 'datetime',
        'seplag_envios' => 'integer',
    ];

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function desfeitoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desfeito_por_id');
    }

    public function seplagEnviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seplag_enviado_por_id');
    }

    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class, 'demanda_id');
    }

    public function pessoas(): HasMany
    {
        return $this->hasMany(RemanejamentoPessoa::class, 'remanejamento_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class, 'lote_id');
    }

    /** So os equipamentos remanejados: e o que conta como "item movido" e vai na planilha. */
    public function itensRemanejados(): HasMany
    {
        return $this->movimentacoes()->where('tipo', TipoMovimentacao::REMANEJAMENTO->value);
    }

    public function estaAtivo(): bool
    {
        return $this->status === StatusRemanejamento::ATIVO;
    }
}
```

`app/Modules/Inventario/Models/RemanejamentoPessoa.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemanejamentoPessoa extends Model
{
    protected $table = 'inventario_ti_remanejamento_pessoas';

    protected $fillable = [
        'remanejamento_id', 'usuario_id', 'estacao_origem_id', 'estacao_destino_id',
        'estacao_destino_usuario_anterior_id', 'estacao_origem_ficou_vazia', 'condicao_destino',
    ];

    protected $casts = ['estacao_origem_ficou_vazia' => 'boolean'];

    public function remanejamento(): BelongsTo
    {
        return $this->belongsTo(Remanejamento::class, 'remanejamento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estacaoOrigem(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_origem_id');
    }

    public function estacaoDestino(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_destino_id');
    }

    public function usuarioAnteriorDoDestino(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estacao_destino_usuario_anterior_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(Movimentacao::class, 'remanejamento_pessoa_id');
    }
}
```

Substituir `app/Modules/Inventario/Models/Movimentacao.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimentacao extends Model
{
    protected $table = 'inventario_ti_movimentacoes';

    protected $fillable = [
        'equipamento_id', 'registrado_por_id', 'usuario_origem_id', 'usuario_destino_id',
        'estacao_origem_id', 'estacao_destino_id', 'lote_id', 'remanejamento_pessoa_id', 'tipo', 'status',
        'situacao_origem', 'quantidade', 'data_saida', 'data_prevista_devolucao', 'data_devolucao',
        'retirante_nome', 'retirante_cpf', 'retirante_contato', 'observacao',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'data_saida' => 'datetime',
        'data_prevista_devolucao' => 'datetime',
        'data_devolucao' => 'datetime',
    ];

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class, 'equipamento_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function remanejamento(): BelongsTo
    {
        return $this->belongsTo(Remanejamento::class, 'lote_id');
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(RemanejamentoPessoa::class, 'remanejamento_pessoa_id');
    }

    public function usuarioOrigem(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_origem_id');
    }

    public function usuarioDestino(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_destino_id');
    }

    public function estacaoOrigem(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_origem_id');
    }

    public function estacaoDestino(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_destino_id');
    }
}
```

- [ ] **Step 8: Migrar `sdc_test` e rodar o teste**

```bash
bash /c/tmp/teste-demandas.sh php artisan migrate --force
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/SchemaRemanejamentoTest.php
```
Expected: migrate aplica `2026_09_28_100000`; 3 testes PASS.

- [ ] **Step 9: Instalacao limpa em banco descartavel**

```bash
docker exec newsdc_dev_db psql -U sdc -d postgres -c "CREATE DATABASE sdc_inventario_fresh TEMPLATE template_postgis"
bash /c/tmp/teste-demandas.sh sh -c "DB_DATABASE=sdc_inventario_fresh php artisan migrate --force" 2>&1 | tail -5
docker exec newsdc_dev_db psql -U sdc -d postgres -c "DROP DATABASE sdc_inventario_fresh"
```
Expected: migrate completo; `2026_09_23_000001` cria as cinco tabelas e `2026_09_28_100000` e no-op. Falha de outro modulo nao relacionado: registrar e confirmar que as duas migrations de inventario passaram.

- [ ] **Step 10: Regressao da tela antiga**

Run: `bash /c/tmp/teste-demandas.sh php artisan route:list --path=inventario/movimentacoes`
Expected: `movimentacoes.index`, `movimentacoes.store`, `movimentacoes.devolver` intactas.

- [ ] **Step 11: Commit**

```bash
git add database/migrations/2026_09_23_000001_create_inventario_ti_tables.php database/migrations/2026_09_28_100000_ajusta_inventario_remanejamentos.php app/Modules/Inventario/Enums/StatusRemanejamento.php app/Modules/Inventario/Enums/TipoMovimentacao.php app/Modules/Inventario/Enums/StatusMovimentacao.php app/Modules/Inventario/Models/Remanejamento.php app/Modules/Inventario/Models/RemanejamentoPessoa.php app/Modules/Inventario/Models/Movimentacao.php
git commit -m "🗃️ db(inventario): tabelas do remanejamento em lote" -- database/migrations/2026_09_23_000001_create_inventario_ti_tables.php database/migrations/2026_09_28_100000_ajusta_inventario_remanejamentos.php app/Modules/Inventario/Enums/StatusRemanejamento.php app/Modules/Inventario/Enums/TipoMovimentacao.php app/Modules/Inventario/Enums/StatusMovimentacao.php app/Modules/Inventario/Models/Remanejamento.php app/Modules/Inventario/Models/RemanejamentoPessoa.php app/Modules/Inventario/Models/Movimentacao.php
```

---

### Task 2: DTOs, `RemanejamentoProibido` e registrar lote

**Files:**
- Create: `app/Modules/Inventario/DTOs/PessoaRemanejadaData.php`, `app/Modules/Inventario/DTOs/RemanejamentoData.php`
- Create: `app/Modules/Inventario/Exceptions/RemanejamentoProibido.php`
- Create: `app/Modules/Inventario/Services/RemanejamentoService.php`
- Test: `tests/Feature/Inventario/RemanejamentoRegistrarTest.php`

**Interfaces:**
- Consumes: models e enums da Task 1; trait `CriaInventario`.
- Produces:
  - `PessoaRemanejadaData(int $usuarioId, ?int $estacaoOrigemId, ?int $estacaoDestinoId, ?string $condicaoDestino, list<int> $equipamentoIds)`, `static fromArray(array): self` (chaves `usuario_id, estacao_origem_id, estacao_destino_id, condicao_destino, equipamento_ids`; NAO remove repetidos — o service precisa ve-los).
  - `RemanejamentoData(?string $observacao, list<PessoaRemanejadaData> $pessoas, int $autorId)`, `static fromArray(array $dados, int $autorId): self`, `usuarioIds(): list<int>`, `equipamentoIds(): list<int>`, `estacaoDestinoIds(): list<int>`, `estacaoIds(): list<int>`.
  - `RemanejamentoProibido extends ValidationException`: `static comErros(array<string, string|list<string>>): self` (junta as mensagens de cada chave numa so string, porque o Inertia repassa so a primeira), `static loteDesfeito(): self` (chave `remanejamento`, mensagem `Lote já desfeito.`), `static configuracao(string): self` (chave `configuracao`).
  - `RemanejamentoService::registrar(RemanejamentoData): Remanejamento`. Chaves de erro: `pessoas`, `pessoas.{i}.usuario_id`, `pessoas.{i}.estacao_origem_id`, `pessoas.{i}.estacao_destino_id`, `pessoas.{i}.equipamento_ids`.
- Regra adicional (resolve ambiguidade da spec): `estacao_origem_id`, quando informada, tem de ser uma estacao ocupada AGORA pela pessoa. Sem isso o desfazer nao saberia se deve devolver a estacao a ela.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Inventario/RemanejamentoRegistrarTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class RemanejamentoRegistrarTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    public function test_duas_pessoas_trocando_de_estacao(): void
    {
        $autor = $this->operador();
        [$ana, $bia] = [$this->pessoa(), $this->pessoa()];
        $mesaAna = $this->estacao($ana);
        $mesaBia = $this->estacao($bia);
        $pcAna = $this->equipamento($ana, $mesaAna);
        $pcBia = $this->equipamento($bia, $mesaBia);

        $lote = $this->registrarLote($autor, [
            $this->pessoaNoLote($ana, $mesaAna, $mesaBia, [$pcAna]),
            $this->pessoaNoLote($bia, $mesaBia, $mesaAna, [$pcBia]),
        ], 'TST- troca');

        $this->assertSame($bia->id, $mesaAna->fresh()->user_id);
        $this->assertSame($ana->id, $mesaBia->fresh()->user_id);
        $this->assertSame([$ana->id, $mesaBia->id, SituacaoEquipamento::EM_USO], [
            $pcAna->fresh()->user_id, $pcAna->fresh()->estacao_id, $pcAna->fresh()->situacao,
        ]);
        $this->assertSame($mesaAna->id, $pcBia->fresh()->estacao_id);

        $pessoas = $lote->pessoas()->orderBy('id')->get();
        $this->assertFalse($pessoas[0]->estacao_origem_ficou_vazia);
        $this->assertFalse($pessoas[1]->estacao_origem_ficou_vazia);
        $this->assertNull($pessoas[0]->estacao_destino_usuario_anterior_id);
        $this->assertNull($pessoas[1]->estacao_destino_usuario_anterior_id);

        $movs = $lote->itensRemanejados()->orderBy('id')->get();
        $this->assertCount(2, $movs);
        $this->assertSame(['ativo', 'em_uso', $ana->id, $mesaAna->id, $mesaBia->id], [
            $movs[0]->status, $movs[0]->situacao_origem, $movs[0]->usuario_origem_id,
            $movs[0]->estacao_origem_id, $movs[0]->estacao_destino_id,
        ]);
        $this->assertSame('TST- troca', $lote->observacao);
        $this->assertSame($autor->id, $lote->registrado_por_id);
    }

    public function test_destino_ocupado_guarda_ocupante_anterior_e_origem_fica_vazia(): void
    {
        $autor = $this->operador();
        [$ana, $caio] = [$this->pessoa(), $this->pessoa()];
        $mesaAna = $this->estacao($ana);
        $mesaCaio = $this->estacao($caio);
        $pc = $this->equipamento($ana, $mesaAna);

        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, $mesaAna, $mesaCaio, [$pc], 'OCUPADA')]);

        $pessoa = $lote->pessoas()->first();
        $this->assertSame($caio->id, $pessoa->estacao_destino_usuario_anterior_id);
        $this->assertTrue($pessoa->estacao_origem_ficou_vazia);
        $this->assertSame('OCUPADA', $pessoa->condicao_destino);
        $this->assertNull($mesaAna->fresh()->user_id);
        $this->assertSame($ana->id, $mesaCaio->fresh()->user_id);
    }

    public function test_libera_equipamentos_deixados_para_tras(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $mesa = $this->estacao($ana);
        $destino = $this->estacao();
        $leva = $this->equipamento($ana, $mesa);
        $fica = $this->equipamento($ana, $mesa);

        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, $mesa, $destino, [$leva])]);

        $fica->refresh();
        $this->assertNull($fica->user_id);
        $this->assertSame(SituacaoEquipamento::DISPONIVEL, $fica->situacao);
        $this->assertSame($mesa->id, $fica->estacao_id);

        $liberacao = Movimentacao::query()->where('lote_id', $lote->id)->where('tipo', 'liberacao')->sole();
        $this->assertSame([$fica->id, $ana->id, $mesa->id, 'em_uso', 'ativo'], [
            $liberacao->equipamento_id, $liberacao->usuario_origem_id, $liberacao->estacao_origem_id,
            $liberacao->situacao_origem, $liberacao->status,
        ]);
        $this->assertSame(1, $lote->itensRemanejados()->count());
    }

    public function test_nao_libera_emprestado_nem_em_manutencao(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $mesa = $this->estacao($ana);
        $leva = $this->equipamento($ana, $mesa);
        $manutencao = $this->equipamento($ana, $mesa, ['situacao' => SituacaoEquipamento::MANUTENCAO]);
        $emprestado = $this->equipamento(null, null);
        $this->emprestar($emprestado, $ana, $autor);

        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, $mesa, $this->estacao(), [$leva])]);

        $this->assertSame([$ana->id, SituacaoEquipamento::MANUTENCAO], [$manutencao->fresh()->user_id, $manutencao->fresh()->situacao]);
        $this->assertSame([$ana->id, SituacaoEquipamento::EM_USO], [$emprestado->fresh()->user_id, $emprestado->fresh()->situacao]);
        $this->assertSame(0, Movimentacao::query()->where('lote_id', $lote->id)->where('tipo', 'liberacao')->count());
    }

    public function test_equipamento_de_outra_pessoa_do_lote_e_transferido_e_nao_liberado(): void
    {
        $autor = $this->operador();
        [$ana, $bia] = [$this->pessoa(), $this->pessoa()];
        $pcAna = $this->equipamento($ana);
        $pcBia = $this->equipamento($bia);

        $lote = $this->registrarLote($autor, [
            $this->pessoaNoLote($ana, null, null, [$pcBia]),
            $this->pessoaNoLote($bia, null, null, [$pcAna]),
        ]);

        $this->assertSame($bia->id, $pcAna->fresh()->user_id);
        $this->assertSame($ana->id, $pcBia->fresh()->user_id);
        $this->assertSame(0, Movimentacao::query()->where('lote_id', $lote->id)->where('tipo', 'liberacao')->count());
    }

    public function test_substitui_remanejamento_ativo_anterior(): void
    {
        $autor = $this->operador();
        [$ana, $bia] = [$this->pessoa(), $this->pessoa()];
        $pc = $this->equipamento($ana);

        $primeiro = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, $this->estacao(), [$pc])]);
        $segundo = $this->registrarLote($autor, [$this->pessoaNoLote($bia, null, $this->estacao(), [$pc])]);

        $this->assertSame('substituida', $primeiro->itensRemanejados()->sole()->status);
        $this->assertSame('ativo', $segundo->itensRemanejados()->sole()->status);
        $this->assertSame($bia->id, $pc->fresh()->user_id);
    }

    public function test_bloqueia_baixado_manutencao_e_emprestado(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $baixado = $this->equipamento(null, null, ['situacao' => SituacaoEquipamento::BAIXADO]);
        $manutencao = $this->equipamento(null, null, ['situacao' => SituacaoEquipamento::MANUTENCAO]);
        $emprestado = $this->equipamento();
        $this->emprestar($emprestado, $this->pessoa(), $autor);

        $erros = $this->errosAoRegistrar($autor, [$this->pessoaNoLote($ana, null, null, [$baixado, $manutencao, $emprestado])]);

        $mensagens = implode(' ', $erros['pessoas.0.equipamento_ids']);
        $this->assertStringContainsString($baixado->patrimonio.' está baixado', $mensagens);
        $this->assertStringContainsString($manutencao->patrimonio.' está em manutenção', $mensagens);
        $this->assertStringContainsString($emprestado->patrimonio.' está emprestado', $mensagens);
    }

    public function test_bloqueia_repetidos_no_lote(): void
    {
        $autor = $this->operador();
        [$ana, $bia] = [$this->pessoa(), $this->pessoa()];
        $pc = $this->equipamento();
        $destino = $this->estacao();

        $erros = $this->errosAoRegistrar($autor, [
            $this->pessoaNoLote($ana, null, $destino, [$pc]),
            $this->pessoaNoLote($bia, null, $destino, [$pc]),
            $this->pessoaNoLote($ana, null, null, [$this->equipamento()]),
        ]);

        $this->assertArrayHasKey('pessoas.1.equipamento_ids', $erros);
        $this->assertArrayHasKey('pessoas.1.estacao_destino_id', $erros);
        $this->assertArrayHasKey('pessoas.2.usuario_id', $erros);
    }

    public function test_estacao_origem_que_nao_e_da_pessoa_e_recusada(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $mesaDeOutro = $this->estacao($this->pessoa());

        $erros = $this->errosAoRegistrar($autor, [$this->pessoaNoLote($ana, $mesaDeOutro, null, [$this->equipamento()])]);

        $this->assertArrayHasKey('pessoas.0.estacao_origem_id', $erros);
        $this->assertNotNull($mesaDeOutro->fresh()->user_id);
    }

    public function test_lote_sem_pessoas_e_recusado(): void
    {
        $erros = $this->errosAoRegistrar($this->operador(), []);

        $this->assertArrayHasKey('pessoas', $erros);
    }

    private function errosAoRegistrar($autor, array $pessoas): array
    {
        $antes = Remanejamento::query()->count();
        try {
            $this->registrarLote($autor, $pessoas);
            $this->fail('Esperava RemanejamentoProibido.');
        } catch (RemanejamentoProibido $e) {
            $this->assertSame($antes, Remanejamento::query()->count(), 'Nada pode ficar gravado.');

            return $e->errors();
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RemanejamentoRegistrarTest.php`
Expected: FAIL com `Class "App\Modules\Inventario\DTOs\RemanejamentoData" not found`.

- [ ] **Step 3: DTOs e excecao**

`app/Modules/Inventario/DTOs/PessoaRemanejadaData.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\DTOs;

final readonly class PessoaRemanejadaData
{
    /** @param list<int> $equipamentoIds */
    public function __construct(
        public int $usuarioId,
        public ?int $estacaoOrigemId,
        public ?int $estacaoDestinoId,
        public ?string $condicaoDestino,
        public array $equipamentoIds,
    ) {}

    public static function fromArray(array $dados): self
    {
        $inteiro = static fn (mixed $valor): ?int => $valor === null || $valor === '' ? null : (int) $valor;
        $condicao = trim((string) ($dados['condicao_destino'] ?? ''));

        return new self(
            usuarioId: (int) $dados['usuario_id'],
            estacaoOrigemId: $inteiro($dados['estacao_origem_id'] ?? null),
            estacaoDestinoId: $inteiro($dados['estacao_destino_id'] ?? null),
            condicaoDestino: $condicao === '' ? null : mb_substr($condicao, 0, 60),
            // Sem array_unique: repeticao e erro de dominio e o service precisa ve-la.
            equipamentoIds: array_values(array_map('intval', (array) ($dados['equipamento_ids'] ?? []))),
        );
    }
}
```

`app/Modules/Inventario/DTOs/RemanejamentoData.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\DTOs;

final readonly class RemanejamentoData
{
    /** @param list<PessoaRemanejadaData> $pessoas */
    public function __construct(
        public ?string $observacao,
        public array $pessoas,
        public int $autorId,
    ) {}

    public static function fromArray(array $dados, int $autorId): self
    {
        $observacao = trim((string) ($dados['observacao'] ?? ''));

        return new self(
            observacao: $observacao === '' ? null : $observacao,
            pessoas: array_values(array_map(
                static fn (array $pessoa): PessoaRemanejadaData => PessoaRemanejadaData::fromArray($pessoa),
                (array) ($dados['pessoas'] ?? []),
            )),
            autorId: $autorId,
        );
    }

    /** @return list<int> */
    public function usuarioIds(): array
    {
        return $this->unicos(array_map(static fn (PessoaRemanejadaData $p): int => $p->usuarioId, $this->pessoas));
    }

    /** @return list<int> */
    public function equipamentoIds(): array
    {
        return $this->unicos(array_merge([], ...array_map(
            static fn (PessoaRemanejadaData $p): array => $p->equipamentoIds,
            $this->pessoas,
        )));
    }

    /** @return list<int> */
    public function estacaoDestinoIds(): array
    {
        return $this->unicos(array_map(static fn (PessoaRemanejadaData $p): ?int => $p->estacaoDestinoId, $this->pessoas));
    }

    /** @return list<int> */
    public function estacaoIds(): array
    {
        return $this->unicos(array_merge(
            array_map(static fn (PessoaRemanejadaData $p): ?int => $p->estacaoOrigemId, $this->pessoas),
            $this->estacaoDestinoIds(),
        ));
    }

    /** @param list<int|null> $ids @return list<int> */
    private function unicos(array $ids): array
    {
        return array_values(array_unique(array_filter($ids, static fn (?int $id): bool => $id !== null)));
    }
}
```

`app/Modules/Inventario/Exceptions/RemanejamentoProibido.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Regra de dominio do lote violada. Filha de ValidationException de proposito:
 * o handler do Laravel ja devolve 422 (JSON) ou erros de sessao (Inertia) por
 * chave, entao cada item recusado chega ao campo certo do formulario sem
 * try/catch nos controllers.
 */
final class RemanejamentoProibido extends ValidationException
{
    /**
     * Uma mensagem por chave: o Inertia so repassa a primeira mensagem de cada
     * campo, entao os itens recusados do mesmo campo seguem juntos.
     *
     * @param array<string, string|list<string>> $erros
     */
    public static function comErros(array $erros): self
    {
        return self::withMessages(array_map(
            static fn (string|array $mensagens): string => is_array($mensagens) ? implode(' ', $mensagens) : $mensagens,
            $erros,
        ));
    }

    public static function loteDesfeito(): self
    {
        return self::withMessages(['remanejamento' => 'Lote já desfeito.']);
    }

    public static function configuracao(string $mensagem): self
    {
        return self::withMessages(['configuracao' => $mensagem]);
    }
}
```

- [ ] **Step 4: Service com `registrar`**

`app/Modules/Inventario/Services/RemanejamentoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\DTOs\PessoaRemanejadaData;
use App\Modules\Inventario\DTOs\RemanejamentoData;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Enums\StatusMovimentacao;
use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Estacao;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Models\RemanejamentoPessoa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Remanejamento em lote: registrar, desfazer e editar.
 *
 * Toda escrita numa transacao, com lockForUpdate em equipamentos e depois em
 * estacoes, cada grupo em ordem crescente de id. A ordem fixa e o que impede
 * dois lotes concorrentes de travarem um esperando o outro.
 */
final class RemanejamentoService
{
    private const SITUACOES_BLOQUEADAS = [SituacaoEquipamento::BAIXADO, SituacaoEquipamento::MANUTENCAO];

    public function registrar(RemanejamentoData $dados): Remanejamento
    {
        return DB::transaction(function () use ($dados): Remanejamento {
            $lote = Remanejamento::create([
                'registrado_por_id' => $dados->autorId,
                'observacao' => $dados->observacao,
                'status' => StatusRemanejamento::ATIVO,
            ]);
            $this->aplicar($lote, $dados);

            return $lote->refresh();
        });
    }

    private function aplicar(Remanejamento $lote, RemanejamentoData $dados): void
    {
        if ($dados->pessoas === []) {
            throw RemanejamentoProibido::comErros(['pessoas' => 'Informe ao menos uma pessoa no remanejamento.']);
        }

        $equipamentos = $this->travarEquipamentos(Equipamento::query()->where(
            static fn (Builder $q) => $q->whereIn('id', $dados->equipamentoIds())->orWhereIn('user_id', $dados->usuarioIds())
        ));
        $estacoes = $this->travarEstacoes($dados->estacaoIds());
        $emprestados = $this->comEmprestimoAtivo($equipamentos->keys()->all());

        $this->validar($dados, $equipamentos, $estacoes, $emprestados);

        // Passo 1: solta todas as origens antes de ocupar qualquer destino. Numa
        // troca mutua, ocupar primeiro gravaria o colega como "ocupante anterior"
        // da mesa que ele mesmo esta deixando.
        foreach ($dados->pessoas as $pessoa) {
            if ($pessoa->estacaoOrigemId !== null) {
                $estacoes[$pessoa->estacaoOrigemId]->update(['user_id' => null]);
            }
        }

        $deixados = $this->deixadosParaTras($dados, $equipamentos, $emprestados);
        $destinos = $dados->estacaoDestinoIds();

        foreach ($dados->pessoas as $pessoa) {
            $destino = $pessoa->estacaoDestinoId !== null ? $estacoes[$pessoa->estacaoDestinoId] : null;
            $registro = $lote->pessoas()->create([
                'usuario_id' => $pessoa->usuarioId,
                'estacao_origem_id' => $pessoa->estacaoOrigemId,
                'estacao_destino_id' => $pessoa->estacaoDestinoId,
                'estacao_destino_usuario_anterior_id' => $destino?->user_id,
                // A origem so nao fica vazia se alguem do lote (inclusive a propria
                // pessoa, quando fica na mesma mesa) a tiver como destino.
                'estacao_origem_ficou_vazia' => ! in_array($pessoa->estacaoOrigemId, $destinos, true),
                'condicao_destino' => $pessoa->condicaoDestino,
            ]);
            $destino?->update(['user_id' => $pessoa->usuarioId]);

            foreach ($deixados[$pessoa->usuarioId] ?? [] as $equipamento) {
                $this->liberar($lote, $registro, $equipamento, $dados);
            }
            foreach ($pessoa->equipamentoIds as $id) {
                $this->remanejar($lote, $registro, $equipamentos[$id], $pessoa, $dados);
            }
        }
    }

    /**
     * Junta TODOS os erros antes de lancar: a tela mostra cada item recusado de
     * uma vez, em vez de o usuario descobrir um por envio.
     *
     * @param Collection<int, Equipamento> $equipamentos
     * @param Collection<int, Estacao> $estacoes
     * @param list<int> $emprestados
     */
    private function validar(RemanejamentoData $dados, Collection $equipamentos, Collection $estacoes, array $emprestados): void
    {
        $erros = [];
        $usuariosVistos = [];
        $equipamentosVistos = [];
        $destinosVistos = [];

        foreach ($dados->pessoas as $i => $pessoa) {
            $chave = "pessoas.{$i}";

            if (in_array($pessoa->usuarioId, $usuariosVistos, true)) {
                $erros["{$chave}.usuario_id"][] = 'Esta pessoa já está em outro bloco do lote.';
            }
            $usuariosVistos[] = $pessoa->usuarioId;

            if ($pessoa->estacaoOrigemId !== null) {
                $origem = $estacoes->get($pessoa->estacaoOrigemId);
                if ($origem === null) {
                    $erros["{$chave}.estacao_origem_id"][] = 'Estação de origem não encontrada.';
                } elseif ((int) $origem->user_id !== $pessoa->usuarioId) {
                    $erros["{$chave}.estacao_origem_id"][] = "A estação {$origem->nome} não está ocupada por esta pessoa.";
                }
            }

            if ($pessoa->estacaoDestinoId !== null) {
                $destino = $estacoes->get($pessoa->estacaoDestinoId);
                if ($destino === null) {
                    $erros["{$chave}.estacao_destino_id"][] = 'Estação de destino não encontrada.';
                } elseif (in_array($pessoa->estacaoDestinoId, $destinosVistos, true)) {
                    $erros["{$chave}.estacao_destino_id"][] = "A estação {$destino->nome} já é destino de outra pessoa do lote.";
                }
                $destinosVistos[] = $pessoa->estacaoDestinoId;
            }

            foreach ($pessoa->equipamentoIds as $id) {
                $equipamento = $equipamentos->get($id);
                if ($equipamento === null) {
                    $erros["{$chave}.equipamento_ids"][] = "Equipamento #{$id} não encontrado.";
                    continue;
                }
                $rotulo = "O equipamento {$equipamento->patrimonio}";
                if (in_array($id, $equipamentosVistos, true)) {
                    $erros["{$chave}.equipamento_ids"][] = "{$rotulo} aparece mais de uma vez no lote.";
                }
                $equipamentosVistos[] = $id;

                if ($equipamento->situacao === SituacaoEquipamento::BAIXADO) {
                    $erros["{$chave}.equipamento_ids"][] = "{$rotulo} está baixado.";
                } elseif ($equipamento->situacao === SituacaoEquipamento::MANUTENCAO) {
                    $erros["{$chave}.equipamento_ids"][] = "{$rotulo} está em manutenção.";
                }
                if (in_array($id, $emprestados, true)) {
                    $erros["{$chave}.equipamento_ids"][] = "{$rotulo} está emprestado; registre a devolução antes.";
                }
            }
        }

        if ($erros !== []) {
            throw RemanejamentoProibido::comErros($erros);
        }
    }

    /**
     * Equipamentos das pessoas do lote que nao foram listados em bloco nenhum.
     * Emprestado e manutencao/baixado nao sao liberados: o emprestimo e a
     * manutencao tem fluxo proprio e nao acabam porque a pessoa mudou de mesa.
     *
     * @param Collection<int, Equipamento> $equipamentos
     * @param list<int> $emprestados
     * @return array<int, list<Equipamento>>
     */
    private function deixadosParaTras(RemanejamentoData $dados, Collection $equipamentos, array $emprestados): array
    {
        $listados = $dados->equipamentoIds();
        $usuarios = $dados->usuarioIds();
        $deixados = [];

        foreach ($equipamentos as $equipamento) {
            $dono = $equipamento->user_id !== null ? (int) $equipamento->user_id : null;
            if ($dono === null || ! in_array($dono, $usuarios, true) || in_array($equipamento->id, $listados, true)) {
                continue;
            }
            if (in_array($equipamento->id, $emprestados, true) || in_array($equipamento->situacao, self::SITUACOES_BLOQUEADAS, true)) {
                continue;
            }
            $deixados[$dono][] = $equipamento;
        }

        return $deixados;
    }

    private function liberar(Remanejamento $lote, RemanejamentoPessoa $registro, Equipamento $equipamento, RemanejamentoData $dados): void
    {
        Movimentacao::create([
            'equipamento_id' => $equipamento->id,
            'registrado_por_id' => $dados->autorId,
            'usuario_origem_id' => $equipamento->user_id,
            'usuario_destino_id' => null,
            'estacao_origem_id' => $equipamento->estacao_id,
            // O equipamento fica fisicamente onde estava; so perde o responsavel.
            'estacao_destino_id' => $equipamento->estacao_id,
            'lote_id' => $lote->id,
            'remanejamento_pessoa_id' => $registro->id,
            'tipo' => TipoMovimentacao::LIBERACAO->value,
            'status' => StatusMovimentacao::ATIVO->value,
            'situacao_origem' => $equipamento->situacao->value,
            'quantidade' => $equipamento->quantidade,
            'data_saida' => now(),
            'observacao' => $dados->observacao,
        ]);
        $equipamento->update(['user_id' => null, 'situacao' => SituacaoEquipamento::DISPONIVEL]);
    }

    private function remanejar(
        Remanejamento $lote,
        RemanejamentoPessoa $registro,
        Equipamento $equipamento,
        PessoaRemanejadaData $pessoa,
        RemanejamentoData $dados,
    ): void {
        // Remanejar de novo e normal: o anterior perde efeito, mas fica no
        // historico e volta a valer se este lote for desfeito.
        Movimentacao::query()
            ->where('equipamento_id', $equipamento->id)
            ->where('tipo', TipoMovimentacao::REMANEJAMENTO->value)
            ->where('status', StatusMovimentacao::ATIVO->value)
            ->update(['status' => StatusMovimentacao::SUBSTITUIDA->value]);

        Movimentacao::create([
            'equipamento_id' => $equipamento->id,
            'registrado_por_id' => $dados->autorId,
            'usuario_origem_id' => $equipamento->user_id,
            'usuario_destino_id' => $pessoa->usuarioId,
            'estacao_origem_id' => $equipamento->estacao_id,
            'estacao_destino_id' => $pessoa->estacaoDestinoId,
            'lote_id' => $lote->id,
            'remanejamento_pessoa_id' => $registro->id,
            'tipo' => TipoMovimentacao::REMANEJAMENTO->value,
            'status' => StatusMovimentacao::ATIVO->value,
            'situacao_origem' => $equipamento->situacao->value,
            'quantidade' => $equipamento->quantidade,
            'data_saida' => now(),
            'observacao' => $dados->observacao,
        ]);
        $equipamento->update([
            'user_id' => $pessoa->usuarioId,
            'estacao_id' => $pessoa->estacaoDestinoId,
            'situacao' => SituacaoEquipamento::EM_USO,
        ]);
    }

    /** @return Collection<int, Equipamento> */
    private function travarEquipamentos(Builder $query): Collection
    {
        return $query->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    /**
     * @param list<int> $ids
     * @return Collection<int, Estacao>
     */
    private function travarEstacoes(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection();
        }

        return Estacao::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function comEmprestimoAtivo(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Movimentacao::query()
            ->whereIn('equipamento_id', $ids)
            ->where('tipo', TipoMovimentacao::EMPRESTIMO->value)
            ->where('status', StatusMovimentacao::ATIVO->value)
            ->pluck('equipamento_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()->values()->all();
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RemanejamentoRegistrarTest.php`
Expected: 10 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Inventario/DTOs/PessoaRemanejadaData.php app/Modules/Inventario/DTOs/RemanejamentoData.php app/Modules/Inventario/Exceptions/RemanejamentoProibido.php app/Modules/Inventario/Services/RemanejamentoService.php
git commit -m "✨ feat(inventario): registrar remanejamento em lote" -- app/Modules/Inventario/DTOs/PessoaRemanejadaData.php app/Modules/Inventario/DTOs/RemanejamentoData.php app/Modules/Inventario/Exceptions/RemanejamentoProibido.php app/Modules/Inventario/Services/RemanejamentoService.php
```

---

### Task 3: Desfazer lote (tudo ou nada)

**Files:**
- Modify: `app/Modules/Inventario/Services/RemanejamentoService.php`
- Test: `tests/Feature/Inventario/RemanejamentoDesfazerTest.php`

**Interfaces:**
- Consumes: `RemanejamentoService::registrar`, `RemanejamentoProibido`.
- Produces: `RemanejamentoService::desfazer(Remanejamento $remanejamento, int $userId): Remanejamento`. Erros na chave `remanejamento`. Metodos privados `travarLoteAtivo(Remanejamento): Remanejamento` e `reverter(Remanejamento): void`, reusados pela Task 4.
- Regra "posterior": movimentacao do MESMO equipamento, de id maior, fora deste lote e com status diferente de `devolvido`. Um remanejamento do lote que esteja `substituida` tambem bloqueia.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Inventario/RemanejamentoDesfazerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Services\RemanejamentoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class RemanejamentoDesfazerTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    public function test_desfazer_restaura_equipamentos_estacoes_e_liberados(): void
    {
        $autor = $this->operador();
        [$ana, $bia, $caio] = [$this->pessoa(), $this->pessoa(), $this->pessoa()];
        $mesaAna = $this->estacao($ana);
        $mesaBia = $this->estacao($bia);
        $mesaCaio = $this->estacao($caio);
        $leva = $this->equipamento($ana, $mesaAna);
        $fica = $this->equipamento($ana, $mesaAna);
        $pcBia = $this->equipamento($bia, $mesaBia);

        $lote = $this->registrarLote($autor, [
            $this->pessoaNoLote($ana, $mesaAna, $mesaCaio, [$leva]),
            $this->pessoaNoLote($bia, $mesaBia, $mesaAna, [$pcBia]),
        ]);

        app(RemanejamentoService::class)->desfazer($lote, $autor->id);

        $this->assertSame([$ana->id, $bia->id, $caio->id], [
            $mesaAna->fresh()->user_id, $mesaBia->fresh()->user_id, $mesaCaio->fresh()->user_id,
        ]);
        foreach ([[$leva, $ana, $mesaAna], [$fica, $ana, $mesaAna], [$pcBia, $bia, $mesaBia]] as [$equipamento, $dono, $mesa]) {
            $equipamento->refresh();
            $this->assertSame([$dono->id, $mesa->id, SituacaoEquipamento::EM_USO], [
                $equipamento->user_id, $equipamento->estacao_id, $equipamento->situacao,
            ]);
        }

        $lote->refresh();
        $this->assertSame(StatusRemanejamento::DESFEITO, $lote->status);
        $this->assertSame($autor->id, $lote->desfeito_por_id);
        $this->assertNotNull($lote->desfeito_em);
        $this->assertSame(0, $lote->movimentacoes()->where('status', '!=', 'devolvido')->count());
        $this->assertSame(0, $lote->movimentacoes()->whereNull('data_devolucao')->count());
    }

    public function test_desfazer_reativa_a_substituida_anterior(): void
    {
        $autor = $this->operador();
        [$ana, $bia] = [$this->pessoa(), $this->pessoa()];
        $pc = $this->equipamento($ana);
        $primeiro = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, null, [$pc])]);
        $segundo = $this->registrarLote($autor, [$this->pessoaNoLote($bia, null, null, [$pc])]);

        app(RemanejamentoService::class)->desfazer($segundo, $autor->id);

        $this->assertSame('ativo', $primeiro->itensRemanejados()->sole()->status);
        $this->assertSame($ana->id, $pc->fresh()->user_id);
    }

    public function test_tudo_ou_nada_quando_um_item_tem_movimentacao_posterior(): void
    {
        $autor = $this->operador();
        [$ana, $bia] = [$this->pessoa(), $this->pessoa()];
        $destino = $this->estacao();
        $pc1 = $this->equipamento($ana);
        $pc2 = $this->equipamento($ana);
        $primeiro = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, $destino, [$pc1, $pc2])]);
        $this->registrarLote($autor, [$this->pessoaNoLote($bia, null, null, [$pc2])]);

        try {
            app(RemanejamentoService::class)->desfazer($primeiro, $autor->id);
            $this->fail('Esperava RemanejamentoProibido.');
        } catch (RemanejamentoProibido $e) {
            $this->assertStringContainsString($pc2->patrimonio, implode(' ', $e->errors()['remanejamento']));
        }

        $this->assertSame([$ana->id, $destino->id], [$pc1->fresh()->user_id, $pc1->fresh()->estacao_id]);
        $this->assertSame($ana->id, $destino->fresh()->user_id);
        $this->assertSame(StatusRemanejamento::ATIVO, $primeiro->fresh()->status);
    }

    public function test_liberado_movimentado_depois_bloqueia_desfazer(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $leva = $this->equipamento($ana);
        $fica = $this->equipamento($ana);
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, null, [$leva])]);
        $this->emprestar($fica->fresh(), $this->pessoa(), $autor);

        try {
            app(RemanejamentoService::class)->desfazer($lote, $autor->id);
            $this->fail('Esperava RemanejamentoProibido.');
        } catch (RemanejamentoProibido $e) {
            $this->assertStringContainsString($fica->patrimonio, implode(' ', $e->errors()['remanejamento']));
        }

        $this->assertNotSame($ana->id, $fica->fresh()->user_id);
        $this->assertSame(StatusRemanejamento::ATIVO, $lote->fresh()->status);
    }

    public function test_desfazer_lote_ja_desfeito_e_recusado(): void
    {
        $autor = $this->operador();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);
        app(RemanejamentoService::class)->desfazer($lote, $autor->id);

        $this->expectException(RemanejamentoProibido::class);
        $this->expectExceptionMessage('Lote já desfeito.');
        app(RemanejamentoService::class)->desfazer($lote, $autor->id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RemanejamentoDesfazerTest.php`
Expected: FAIL com `Call to undefined method ...RemanejamentoService::desfazer()`.

- [ ] **Step 3: Implementar `desfazer`**

Em `RemanejamentoService.php`, logo apos o metodo `registrar`, acrescentar:

```php
    public function desfazer(Remanejamento $remanejamento, int $userId): Remanejamento
    {
        return DB::transaction(function () use ($remanejamento, $userId): Remanejamento {
            $lote = $this->travarLoteAtivo($remanejamento);
            $this->reverter($lote);
            $lote->update([
                'status' => StatusRemanejamento::DESFEITO,
                'desfeito_em' => now(),
                'desfeito_por_id' => $userId,
            ]);

            return $lote;
        });
    }

    private function travarLoteAtivo(Remanejamento $remanejamento): Remanejamento
    {
        $lote = Remanejamento::query()->lockForUpdate()->findOrFail($remanejamento->getKey());
        if (! $lote->estaAtivo()) {
            throw RemanejamentoProibido::loteDesfeito();
        }

        return $lote;
    }

    /**
     * Devolve equipamentos e estacoes ao estado de antes do lote. Valida tudo
     * antes de escrever qualquer linha: tudo ou nada, mesmo fora da transacao.
     */
    private function reverter(Remanejamento $lote): void
    {
        $movimentacoes = $lote->movimentacoes()->with('equipamento:id,patrimonio')->orderByDesc('id')->get();
        $equipamentos = $this->travarEquipamentos(
            Equipamento::withTrashed()->whereIn('id', $movimentacoes->pluck('equipamento_id')->unique()->values()->all())
        );
        $pessoas = $lote->pessoas()->orderBy('id')->get();
        $estacoes = $this->travarEstacoes(array_values(array_unique(array_filter(array_merge(
            $pessoas->pluck('estacao_origem_id')->all(),
            $pessoas->pluck('estacao_destino_id')->all(),
        )))));

        $this->validarReversao($lote, $movimentacoes);

        // Ordem inversa do registro (id decrescente).
        foreach ($movimentacoes as $movimentacao) {
            $equipamentos->get($movimentacao->equipamento_id)?->update([
                'user_id' => $movimentacao->usuario_origem_id,
                'estacao_id' => $movimentacao->estacao_origem_id,
                'situacao' => $movimentacao->situacao_origem ?? SituacaoEquipamento::DISPONIVEL->value,
            ]);
            $movimentacao->update(['status' => StatusMovimentacao::DEVOLVIDO->value, 'data_devolucao' => now()]);
            if ($movimentacao->tipo === TipoMovimentacao::REMANEJAMENTO->value) {
                $this->reativarSubstituida($movimentacao);
            }
        }

        // Destinos antes das origens: numa troca mutua a mesa e destino de um e
        // origem do outro, e quem fica com ela no fim e o dono original.
        foreach ($pessoas as $pessoa) {
            if ($pessoa->estacao_destino_id !== null) {
                $estacoes->get($pessoa->estacao_destino_id)?->update(['user_id' => $pessoa->estacao_destino_usuario_anterior_id]);
            }
        }
        foreach ($pessoas as $pessoa) {
            if ($pessoa->estacao_origem_id !== null) {
                $estacoes->get($pessoa->estacao_origem_id)?->update(['user_id' => $pessoa->usuario_id]);
            }
        }
    }

    /** @param Collection<int, Movimentacao> $movimentacoes */
    private function validarReversao(Remanejamento $lote, Collection $movimentacoes): void
    {
        $erros = [];

        foreach ($movimentacoes as $movimentacao) {
            // Devolvido nao conta: desfazer em ordem inversa (o lote mais novo
            // primeiro) tem de liberar o lote anterior.
            $posterior = Movimentacao::query()
                ->with('remanejamento:id,created_at')
                ->where('equipamento_id', $movimentacao->equipamento_id)
                ->where('id', '>', $movimentacao->id)
                ->where(static fn (Builder $q) => $q->whereNull('lote_id')->orWhere('lote_id', '!=', $lote->id))
                ->where('status', '!=', StatusMovimentacao::DEVOLVIDO->value)
                ->orderBy('id')
                ->first();
            $substituida = $movimentacao->tipo === TipoMovimentacao::REMANEJAMENTO->value
                && $movimentacao->status !== StatusMovimentacao::ATIVO->value;

            if ($posterior === null && ! $substituida) {
                continue;
            }

            $rotulo = $movimentacao->equipamento?->patrimonio ?? '#'.$movimentacao->equipamento_id;
            $erros['remanejamento'][] = sprintf(
                'O equipamento %s foi movimentado depois deste lote (%s). Desfaça primeiro o que veio depois.',
                $rotulo,
                $posterior !== null ? $this->descrever($posterior) : 'remanejamento posterior',
            );
        }

        if ($erros !== []) {
            throw RemanejamentoProibido::comErros($erros);
        }
    }

    private function descrever(Movimentacao $movimentacao): string
    {
        if ($movimentacao->remanejamento !== null) {
            return 'lote de '.$movimentacao->remanejamento->created_at->format('d/m/Y H:i');
        }

        return TipoMovimentacao::tryFrom($movimentacao->tipo)?->label().' #'.$movimentacao->id;
    }

    /**
     * Uma so movimentacao de remanejamento fica ativa por equipamento; a
     * substituida mais recente antes desta e exatamente a que esta substituiu.
     */
    private function reativarSubstituida(Movimentacao $movimentacao): void
    {
        Movimentacao::query()
            ->where('equipamento_id', $movimentacao->equipamento_id)
            ->where('tipo', TipoMovimentacao::REMANEJAMENTO->value)
            ->where('status', StatusMovimentacao::SUBSTITUIDA->value)
            ->where('id', '<', $movimentacao->id)
            ->orderByDesc('id')
            ->first()
            ?->update(['status' => StatusMovimentacao::ATIVO->value]);
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RemanejamentoDesfazerTest.php tests/Feature/Inventario/RemanejamentoRegistrarTest.php`
Expected: 15 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Inventario/Services/RemanejamentoService.php
git commit -m "✨ feat(inventario): desfazer lote de remanejamento tudo ou nada" -- app/Modules/Inventario/Services/RemanejamentoService.php
```

---

### Task 4: Editar lote preservando o id + concorrencia

**Files:**
- Modify: `app/Modules/Inventario/Services/RemanejamentoService.php`
- Test: `tests/Feature/Inventario/RemanejamentoEditarTest.php`

**Interfaces:**
- Consumes: `travarLoteAtivo`, `reverter`, `aplicar` (Tasks 2-3).
- Produces: `RemanejamentoService::editar(Remanejamento $remanejamento, RemanejamentoData $dados): Remanejamento`. Mesmo `id`; `demanda_id`, `registrado_por_id` e campos `seplag_*` preservados; movimentacoes e pessoas antigas do lote sao apagadas depois de revertidas (o lote passa a conter so o conteudo novo, que e o que a planilha reenviada reflete).

- [ ] **Step 1: Write the failing test**

`tests/Feature/Inventario/RemanejamentoEditarTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Services\RemanejamentoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class RemanejamentoEditarTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    public function test_editar_mantem_id_e_chega_ao_estado_de_um_registro_novo(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $mesa = $this->estacao($ana);
        $destinoErrado = $this->estacao();
        $destinoCerto = $this->estacao();
        $pc1 = $this->equipamento($ana, $mesa);
        $pc2 = $this->equipamento($ana, $mesa);

        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, $mesa, $destinoErrado, [$pc1])], 'TST- v1');
        $lote->update(['seplag_envios' => 2, 'seplag_enviado_em' => now()->subHour()]);

        $editado = app(RemanejamentoService::class)->editar(
            $lote,
            $this->dadosLote($autor, [$this->pessoaNoLote($ana, $mesa, $destinoCerto, [$pc1, $pc2], 'VAZIA')], 'TST- v2'),
        );

        $this->assertSame($lote->id, $editado->id);
        $this->assertSame(StatusRemanejamento::ATIVO, $editado->status);
        $this->assertSame('TST- v2', $editado->observacao);
        $this->assertSame(2, $editado->seplag_envios);
        $this->assertNull($destinoErrado->fresh()->user_id);
        $this->assertSame($ana->id, $destinoCerto->fresh()->user_id);
        $this->assertNull($mesa->fresh()->user_id);
        $this->assertSame([$destinoCerto->id, $destinoCerto->id], [$pc1->fresh()->estacao_id, $pc2->fresh()->estacao_id]);
        $this->assertSame(1, $editado->pessoas()->count());
        $this->assertSame(2, $editado->itensRemanejados()->count());
        $this->assertSame(0, $editado->movimentacoes()->where('status', 'devolvido')->count());
        $this->assertSame('ativo', $editado->itensRemanejados()->where('equipamento_id', $pc1->id)->sole()->status);
    }

    public function test_editar_lote_desfeito_e_recusado(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $pc = $this->equipamento($ana);
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, null, [$pc])]);
        app(RemanejamentoService::class)->desfazer($lote, $autor->id);

        $this->expectException(RemanejamentoProibido::class);
        app(RemanejamentoService::class)->editar($lote, $this->dadosLote($autor, [$this->pessoaNoLote($ana, null, null, [$pc])]));
    }

    public function test_editar_com_item_movimentado_depois_e_recusado_sem_mudar_nada(): void
    {
        $autor = $this->operador();
        [$ana, $bia] = [$this->pessoa(), $this->pessoa()];
        $pc = $this->equipamento($ana);
        $destino = $this->estacao();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, $destino, [$pc])]);
        $this->registrarLote($autor, [$this->pessoaNoLote($bia, null, null, [$pc])]);

        try {
            app(RemanejamentoService::class)->editar($lote, $this->dadosLote($autor, [$this->pessoaNoLote($ana, null, null, [$this->equipamento()])]));
            $this->fail('Esperava RemanejamentoProibido.');
        } catch (RemanejamentoProibido) {
        }

        $this->assertSame($ana->id, $destino->fresh()->user_id);
        $this->assertSame(1, $lote->itensRemanejados()->count());
    }

    public function test_dois_lotes_com_o_mesmo_equipamento_o_segundo_substitui_e_o_primeiro_nao_desfaz(): void
    {
        $autor = $this->operador();
        [$ana, $bia] = [$this->pessoa(), $this->pessoa()];
        $pc = $this->equipamento($ana);
        $primeiro = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, null, [$pc])]);
        $segundo = $this->registrarLote($autor, [$this->pessoaNoLote($bia, null, null, [$pc])]);

        try {
            app(RemanejamentoService::class)->desfazer($primeiro, $autor->id);
            $this->fail('Esperava RemanejamentoProibido.');
        } catch (RemanejamentoProibido $e) {
            $this->assertStringContainsString(
                'lote de '.$segundo->created_at->format('d/m/Y H:i'),
                implode(' ', $e->errors()['remanejamento']),
            );
        }
        $this->assertSame($bia->id, $pc->fresh()->user_id);
    }

    public function test_desfazer_em_ordem_inversa_funciona(): void
    {
        $autor = $this->operador();
        [$ana, $bia, $caio] = [$this->pessoa(), $this->pessoa(), $this->pessoa()];
        $pc = $this->equipamento($caio);
        $primeiro = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, null, [$pc])]);
        $segundo = $this->registrarLote($autor, [$this->pessoaNoLote($bia, null, null, [$pc])]);

        app(RemanejamentoService::class)->desfazer($segundo, $autor->id);
        app(RemanejamentoService::class)->desfazer($primeiro, $autor->id);

        $this->assertSame($caio->id, $pc->fresh()->user_id);
        $this->assertSame(StatusRemanejamento::DESFEITO, $primeiro->fresh()->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RemanejamentoEditarTest.php`
Expected: FAIL com `Call to undefined method ...RemanejamentoService::editar()` (os dois ultimos testes ja passam: so dependem de registrar/desfazer).

- [ ] **Step 3: Implementar `editar`**

Em `RemanejamentoService.php`, logo apos `desfazer`, acrescentar:

```php
    public function editar(Remanejamento $remanejamento, RemanejamentoData $dados): Remanejamento
    {
        return DB::transaction(function () use ($remanejamento, $dados): Remanejamento {
            $lote = $this->travarLoteAtivo($remanejamento);
            $this->reverter($lote);

            // As linhas antigas ja cumpriram o papel de restaurar o estado; se
            // ficassem, a planilha e o "N itens movidos" somariam o lote velho.
            $lote->movimentacoes()->delete();
            $lote->pessoas()->delete();
            $lote->update(['observacao' => $dados->observacao]);

            $this->aplicar($lote, $dados);

            return $lote->refresh();
        });
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario`
Expected: 23 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Inventario/Services/RemanejamentoService.php
git commit -m "✨ feat(inventario): editar lote de remanejamento preservando o id" -- app/Modules/Inventario/Services/RemanejamentoService.php
```

---

### Task 5: Fechamento da Fase 1

Sem codigo novo, salvo correcoes (cada uma vira commit `🐛 fix(inventario): ...`).

- [ ] **Step 1: Suite da fase e regressao**

```bash
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario tests/Feature/Demandas
```
Expected: tudo PASS (Demandas igual a baseline da Task 0).

- [ ] **Step 2: Conferir o escopo da fase**

```bash
git status --short
git log --oneline dev..HEAD
```
Expected: nada fora do escopo modificado; nenhum arquivo de `tests/` staged; 4 commits da fase (Tasks 1-4) alem do commit da spec.

- [ ] **Step 3: Entrega**

O controlador mergeia a branch em `dev` por uma worktree temporaria (nunca no checkout principal) e refaz o deploy de homolog. A tela antiga de movimentacoes segue igual; o deploy aplica `2026_09_28_100000`.

---

## FASE 2 — Integracoes e HTTP

Entrega: permissoes, tempo real, planilha XLSX, chamado do lote, envio a SEPLAG, rotas e a listagem por lote no `movimentacoes.index` (props novas para a Fase 3, mantendo `movimentacoes`/`filters` que a tela antiga usa).

**Ondas:** A = Tasks 6, 7, 8, 9 em paralelo (rodar o Step 1 da Task 7 antes de despachar as demais, porque ele reescreve `vendor/`). B = Task 10. C = Task 11. Depois Task 12.

**Posse de arquivos na onda A** (nenhum arquivo aparece em duas tasks):
- Task 6: `config/permissions.php`, `app/Modules/Shared/Support/CanaisDeListagem.php`, `app/Modules/Inventario/Observers/RemanejamentoTempoRealObserver.php`, `app/Modules/Inventario/InventarioServiceProvider.php`.
- Task 7: `composer.json`, `composer.lock`, `app/Modules/Shared/Support/CsvSeguro.php`, `app/Modules/Demandas/Support/CsvSeguro.php` (remove), `app/Modules/Demandas/Controllers/DemandaDashboardController.php`, `app/Modules/Demandas/Services/DemandaCsvExporter.php`, `app/Modules/Inventario/Services/PlanilhaRemanejamento.php`, e o trait de teste `tests/Feature/Inventario/Concerns/CriaInventario.php` (so ela o edita nesta fase).
- Task 8: `config/inventario.php`, `.env.example`, `app/Modules/Inventario/Services/RegistrarChamadoDoLote.php`.
- Task 9: `app/Modules/Inventario/Services/MovimentacaoService.php`.

### Task 6: Permissoes do remanejamento e canal de tempo real

**Files:**
- Modify: `config/permissions.php` (bloco `INVENTARIO` ~linha 584; papeis manager ~902, analyst ~1094, operator ~1205)
- Modify: `app/Modules/Shared/Support/CanaisDeListagem.php`
- Create: `app/Modules/Inventario/Observers/RemanejamentoTempoRealObserver.php`
- Modify: `app/Modules/Inventario/InventarioServiceProvider.php`
- Test: `tests/Feature/Inventario/PermissoesETempoRealTest.php`

**Interfaces:**
- Produces: slugs `inventario.remanejamentos.create|edit|seplag` (`config('permissions.modules.INVENTARIO.Remanejamentos')`); recurso de tempo real `inventario-remanejamentos` (canal `listagem.inventario-remanejamentos`, evento `.RecursoAtualizado`), constante `RemanejamentoTempoRealObserver::RECURSO`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Inventario/PermissoesETempoRealTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Modules\Shared\Events\RecursoAtualizado;
use App\Modules\Shared\Support\CanaisDeListagem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class PermissoesETempoRealTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    private const SLUGS = [
        'inventario.remanejamentos.create',
        'inventario.remanejamentos.edit',
        'inventario.remanejamentos.seplag',
    ];

    public function test_modulo_tem_os_slugs_de_remanejamento(): void
    {
        $this->assertSame(
            ['create' => self::SLUGS[0], 'edit' => self::SLUGS[1], 'seplag' => self::SLUGS[2]],
            config('permissions.modules.INVENTARIO.Remanejamentos'),
        );
    }

    public function test_papeis_que_emprestam_tambem_remanejam(): void
    {
        $papeis = 0;
        foreach (config('permissions.role_permissions') as $papel => $slugs) {
            if (! in_array('inventario.emprestimos.create', $slugs, true)) {
                continue;
            }
            $papeis++;
            foreach (self::SLUGS as $slug) {
                $this->assertContains($slug, $slugs, "Papel {$papel} sem {$slug}.");
            }
        }
        $this->assertSame(3, $papeis, 'manager, analyst e operator.');
    }

    public function test_canal_usa_a_permissao_da_rota_da_listagem(): void
    {
        $this->assertSame('inventario.emprestimos.view', CanaisDeListagem::permissaoDe('inventario-remanejamentos'));
        $this->assertFalse(CanaisDeListagem::exigeEscopo('inventario-remanejamentos'));
    }

    public function test_registrar_lote_avisa_a_listagem(): void
    {
        Event::fake([RecursoAtualizado::class]);

        $this->registrarLote($this->operador(), [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);

        Event::assertDispatched(RecursoAtualizado::class, static fn (RecursoAtualizado $e): bool => $e->recurso === 'inventario-remanejamentos');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/PermissoesETempoRealTest.php`
Expected: FAIL nos 4 testes (slugs ausentes; canal null; evento nao despachado).

- [ ] **Step 3: Slugs e papeis**

Em `config/permissions.php`, no bloco `'INVENTARIO'`, depois de `'Emprestimos' => [...],`:

```php
            'Remanejamentos' => [
                'create' => 'inventario.remanejamentos.create',
                'edit'   => 'inventario.remanejamentos.edit',
                'seplag' => 'inventario.remanejamentos.seplag',
            ],
```

Nos tres papeis que ja listam `'inventario.emprestimos.create',` (manager ~902, analyst ~1094, operator ~1205; `admin` ja cobre com `inventario.*`), acrescentar logo depois dessa linha, em cada ocorrencia:

```php
            'inventario.remanejamentos.create',
            'inventario.remanejamentos.edit',
            'inventario.remanejamentos.seplag',
```

- [ ] **Step 4: Canal e observer**

Em `CanaisDeListagem::MAPA`, depois da entrada `'demandas'`:

```php
        // routes/modules/inventario.php -> can:inventario.emprestimos.view
        // (movimentacoes.index, que lista os lotes de remanejamento).
        'inventario-remanejamentos' => 'inventario.emprestimos.view',
```

`app/Modules/Inventario/Observers/RemanejamentoTempoRealObserver.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Observers;

use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Shared\Events\RecursoAtualizado;

/**
 * Avisa a listagem de movimentacoes que um lote mudou. Todo caminho de escrita
 * (registrar, desfazer, editar, chamado, SEPLAG) salva o cabecalho do lote, entao
 * observar so ele cobre todos. O evento e ShouldDispatchAfterCommit.
 */
final class RemanejamentoTempoRealObserver
{
    public const RECURSO = 'inventario-remanejamentos';

    public function saved(Remanejamento $remanejamento): void
    {
        RecursoAtualizado::dispatch(self::RECURSO);
    }

    public function deleted(Remanejamento $remanejamento): void
    {
        RecursoAtualizado::dispatch(self::RECURSO);
    }
}
```

Substituir `app/Modules/Inventario/InventarioServiceProvider.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario;

use App\Modules\Inventario\Contracts\EquipamentoRepository;
use App\Modules\Inventario\Infrastructure\EloquentEquipamentoRepository;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Observers\RemanejamentoTempoRealObserver;
use Illuminate\Support\ServiceProvider;

class InventarioServiceProvider extends ServiceProvider
{
    public array $bindings = [
        EquipamentoRepository::class => EloquentEquipamentoRepository::class,
    ];

    public function boot(): void
    {
        Remanejamento::observe(RemanejamentoTempoRealObserver::class);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/PermissoesETempoRealTest.php
bash /c/tmp/teste-demandas.sh php artisan permissions:audit 2>&1 | grep -i remanejamento
```
Expected: 4 PASS. O audit pode listar os 3 slugs como presentes no config e ausentes no banco (o banco recebe pelo `RolesAndPermissionsSeeder` no deploy) — aceitavel; nenhum outro aviso de inventario.

- [ ] **Step 6: Commit**

```bash
git add config/permissions.php app/Modules/Shared/Support/CanaisDeListagem.php app/Modules/Inventario/Observers/RemanejamentoTempoRealObserver.php app/Modules/Inventario/InventarioServiceProvider.php
git commit -m "🔒 security(inventario): permissoes de remanejamento e canal de tempo real" -- config/permissions.php app/Modules/Shared/Support/CanaisDeListagem.php app/Modules/Inventario/Observers/RemanejamentoTempoRealObserver.php app/Modules/Inventario/InventarioServiceProvider.php
```

---

### Task 7: openspout, `CsvSeguro` em Shared e `PlanilhaRemanejamento`

**Files:**
- Modify: `composer.json`, `composer.lock`
- Create (por `git mv`): `app/Modules/Shared/Support/CsvSeguro.php`
- Delete: `app/Modules/Demandas/Support/CsvSeguro.php`
- Modify: `app/Modules/Demandas/Controllers/DemandaDashboardController.php:12`, `app/Modules/Demandas/Services/DemandaCsvExporter.php:8`
- Create: `app/Modules/Inventario/Services/PlanilhaRemanejamento.php`
- Modify (teste, nao versionado): `tests/Feature/Inventario/Concerns/CriaInventario.php` (helper `lerPlanilha`)
- Test: `tests/Feature/Inventario/PlanilhaRemanejamentoTest.php`

**Interfaces:**
- Produces:
  - `App\Modules\Shared\Support\CsvSeguro::celula(mixed): string` (mesmo comportamento de antes).
  - `PlanilhaRemanejamento::MIME` (`application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`), `PlanilhaRemanejamento::CABECALHO` (10 colunas), `linhas(Remanejamento): list<list<string>>`, `gerar(Remanejamento): string` (bytes do XLSX), `nomeArquivo(Remanejamento): string` (`Remanejamento_YYYYmmdd_HHiiss.xlsx`).
  - Trait de teste: `lerPlanilha(string $bytes): list<list<string>>`.
- API openspout restrita ao que e igual em 4.x e 5.x: `Writer::openToFile/addRow/close`, `new Row(list<Cell>)`, `new Cell\StringCell(string, null)`, `Reader::open/getSheetIterator/getRowIterator/close`, `Row::toArray()`. Sempre `StringCell` explicito: `Cell::fromValue('=...')` viraria `FormulaCell`.

- [ ] **Step 1: Instalar openspout (rodar ANTES de despachar as Tasks 6, 8 e 9)**

```bash
WT=/c/Users/x24679188/Documents/Github/NewSDC/.worktrees/demandas-paridade-chamados/SDC
MSYS_NO_PATHCONV=1 docker run --rm -v "$WT:/app" -w /app -e COMPOSER_ALLOW_SUPERUSER=1 -e COMPOSER_PROCESS_TIMEOUT=0 \
  newsdc-swoole-dev:latest composer require "openspout/openspout:^4.24" --ignore-platform-reqs > /c/tmp/composer-inventario.log 2>&1
ls "$WT/vendor/openspout/openspout/src/Writer/XLSX/Writer.php"
grep -n "function __construct" "$WT/vendor/openspout/openspout/src/Common/Entity/Cell/StringCell.php"
git diff --stat composer.json composer.lock
```
Expected: o arquivo existe; o construtor de `StringCell` recebe `(string $value, ?Style $style)`; so `composer.json` (linha `openspout/openspout`) e `composer.lock` mudam. `composer.json` fixa `config.platform.php = 8.3.30`, por isso `^4` (a 5.x pode exigir PHP 8.4). Se falhar, ler `/c/tmp/composer-inventario.log`.

- [ ] **Step 2: Mover `CsvSeguro` para Shared**

```bash
git mv app/Modules/Demandas/Support/CsvSeguro.php app/Modules/Shared/Support/CsvSeguro.php
```
No arquivo movido, trocar `namespace App\Modules\Demandas\Support;` por `namespace App\Modules\Shared\Support;` e a primeira linha do docblock por ` * Guarda unica contra formula injection em exports (CSV de Demandas, XLSX do Inventario).`.
Em `DemandaDashboardController.php` e `DemandaCsvExporter.php`, trocar `use App\Modules\Demandas\Support\CsvSeguro;` por `use App\Modules\Shared\Support\CsvSeguro;`. Conferir:

```bash
grep -rn "Demandas\\\\Support\\\\CsvSeguro" app tests
```
Expected: nenhuma ocorrencia.

- [ ] **Step 3: Helper de leitura no trait de teste**

Em `tests/Feature/Inventario/Concerns/CriaInventario.php`, acrescentar `use OpenSpout\Reader\XLSX\Reader;` e o metodo:

```php
    /** @return list<list<string>> */
    protected function lerPlanilha(string $bytes): array
    {
        $caminho = tempnam(sys_get_temp_dir(), 'tst_xlsx_');
        file_put_contents($caminho, $bytes);
        $reader = new Reader();
        $reader->open($caminho);
        $linhas = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $linhas[] = array_map(static fn ($v): string => (string) $v, $row->toArray());
            }
            break;
        }
        $reader->close();
        unlink($caminho);

        return $linhas;
    }
```

- [ ] **Step 4: Write the failing test**

`tests/Feature/Inventario/PlanilhaRemanejamentoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Modules\Inventario\Models\CategoriaInventario;
use App\Modules\Inventario\Services\PlanilhaRemanejamento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class PlanilhaRemanejamentoTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    public function test_planilha_tem_cabecalho_do_legado_e_um_item_por_equipamento_remanejado(): void
    {
        $autor = $this->operador();
        [$ana, $bia] = [$this->pessoa(), $this->pessoa()];
        $categoria = CategoriaInventario::create(['nome' => 'TST- Notebook '.uniqid()]);
        $mesa = $this->estacao($ana, ['ponto_rede' => 'PR-1']);
        $destino = $this->estacao(null, ['ponto_rede' => 'PR-2']);
        $pc = $this->equipamento($ana, $mesa, ['categoria_id' => $categoria->id, 'ramal' => '3916-1234']);
        $this->equipamento($ana, $mesa); // deixado para tras: liberacao nao entra
        $semMesa = $this->equipamento($bia, null, ['nome' => 'TST- monitor']);

        $lote = $this->registrarLote($autor, [
            $this->pessoaNoLote($ana, $mesa, $destino, [$pc]),
            $this->pessoaNoLote($bia, null, null, [$semMesa], 'OCUPADA'),
        ]);
        $planilha = app(PlanilhaRemanejamento::class);

        $linhas = $this->lerPlanilha($planilha->gerar($lote));

        $this->assertSame(PlanilhaRemanejamento::CABECALHO, $linhas[0]);
        $this->assertCount(3, $linhas);
        $this->assertSame([
            $categoria->nome, '3916-1234', $pc->patrimonio, $pc->numero_serie,
            $mesa->nome, 'PR-1', 'SIM', $destino->nome, 'PR-2', 'VAZIA',
        ], $linhas[1]);
        $this->assertSame(['TST- monitor', 'ESTOQUE', 'SIM', 'ESTOQUE', 'OCUPADA'], [
            $linhas[2][0], $linhas[2][4], $linhas[2][6], $linhas[2][7], $linhas[2][9],
        ]);
        $this->assertSame('Remanejamento_'.$lote->created_at->format('Ymd_His').'.xlsx', $planilha->nomeArquivo($lote));
    }

    public function test_texto_com_formula_sai_neutralizado(): void
    {
        $ana = $this->pessoa();
        $categoria = CategoriaInventario::create(['nome' => '=1+1 TST'.uniqid()]);
        $destino = $this->estacao(null, ['nome' => '@SOMA TST'.uniqid(), 'ponto_rede' => '-2+3']);
        $pc = $this->equipamento($ana, null, ['categoria_id' => $categoria->id]);
        $lote = $this->registrarLote($this->operador(), [$this->pessoaNoLote($ana, null, $destino, [$pc])]);

        $linha = $this->lerPlanilha(app(PlanilhaRemanejamento::class)->gerar($lote))[1];

        $this->assertSame("'".$categoria->nome, $linha[0]);
        $this->assertSame("'".$destino->nome, $linha[7]);
        $this->assertSame("'-2+3", $linha[8]);
    }
}
```

- [ ] **Step 5: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/PlanilhaRemanejamentoTest.php`
Expected: FAIL com `Class "App\Modules\Inventario\Services\PlanilhaRemanejamento" not found`.

- [ ] **Step 6: Implementar a planilha**

`app/Modules/Inventario/Services/PlanilhaRemanejamento.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Shared\Support\CsvSeguro;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Planilha do lote no formato que a SEPLAG ja recebia do cedec-demanda: um item
 * por equipamento remanejado (liberacoes nao entram). Toda celula e texto e
 * passa por CsvSeguro: o arquivo sai do sistema e e aberto no Excel de terceiros.
 */
final class PlanilhaRemanejamento
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public const CABECALHO = [
        'Tipo de dispositivo', 'Ramal', 'Patrimônio', 'Número de série', 'Origem',
        'Ponto de rede origem', 'Origem ficará vazia', 'Destino', 'Ponto de rede destino', 'Condição do destino',
    ];

    private const SEM_ESTACAO = 'ESTOQUE';

    private const CONDICAO_PADRAO = 'VAZIA';

    /** @return list<list<string>> */
    public function linhas(Remanejamento $lote): array
    {
        $lote->load([
            'itensRemanejados.equipamento' => static fn ($q) => $q->withTrashed(),
            'itensRemanejados.equipamento.categoria:id,nome',
            'itensRemanejados.pessoa.estacaoOrigem:id,nome,ponto_rede',
            'itensRemanejados.pessoa.estacaoDestino:id,nome,ponto_rede',
        ]);

        return $lote->itensRemanejados->sortBy('id')->map(static function (Movimentacao $item): array {
            $equipamento = $item->equipamento;
            $pessoa = $item->pessoa;

            return [
                // Tipo = categoria (spec 4.5); sem categoria, o nome do equipamento.
                (string) ($equipamento?->categoria?->nome ?? $equipamento?->nome ?? '-'),
                (string) ($equipamento?->ramal ?? ''),
                (string) ($equipamento?->patrimonio ?? ''),
                (string) ($equipamento?->numero_serie ?? ''),
                (string) ($pessoa?->estacaoOrigem?->nome ?? self::SEM_ESTACAO),
                (string) ($pessoa?->estacaoOrigem?->ponto_rede ?? ''),
                $pessoa?->estacao_origem_ficou_vazia === false ? 'NÃO' : 'SIM',
                (string) ($pessoa?->estacaoDestino?->nome ?? self::SEM_ESTACAO),
                (string) ($pessoa?->estacaoDestino?->ponto_rede ?? ''),
                (string) ($pessoa?->condicao_destino ?? self::CONDICAO_PADRAO),
            ];
        })->values()->all();
    }

    public function gerar(Remanejamento $lote): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'remanejamento_');
        try {
            $writer = new Writer();
            $writer->openToFile($caminho);
            $writer->addRow($this->linha(self::CABECALHO));
            foreach ($this->linhas($lote) as $linha) {
                $writer->addRow($this->linha($linha));
            }
            $writer->close();

            return (string) file_get_contents($caminho);
        } finally {
            if (is_file($caminho)) {
                unlink($caminho);
            }
        }
    }

    public function nomeArquivo(Remanejamento $lote): string
    {
        return 'Remanejamento_'.$lote->created_at->format('Ymd_His').'.xlsx';
    }

    /** @param list<string> $valores */
    private function linha(array $valores): Row
    {
        return new Row(array_map(
            static fn (string $valor): StringCell => new StringCell(CsvSeguro::celula($valor), null),
            $valores,
        ));
    }
}
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/PlanilhaRemanejamentoTest.php tests/Feature/Demandas/DemandaDashboardTest.php
```
Expected: 2 PASS na planilha; Dashboard de Demandas (usa `CsvSeguro`) segue verde.

- [ ] **Step 8: Commit**

```bash
git add composer.json composer.lock app/Modules/Shared/Support/CsvSeguro.php app/Modules/Demandas/Support/CsvSeguro.php app/Modules/Demandas/Controllers/DemandaDashboardController.php app/Modules/Demandas/Services/DemandaCsvExporter.php app/Modules/Inventario/Services/PlanilhaRemanejamento.php
git commit -m "✨ feat(inventario): planilha XLSX do lote com openspout e CsvSeguro em Shared" -- composer.json composer.lock app/Modules/Shared/Support/CsvSeguro.php app/Modules/Demandas/Support/CsvSeguro.php app/Modules/Demandas/Controllers/DemandaDashboardController.php app/Modules/Demandas/Services/DemandaCsvExporter.php app/Modules/Inventario/Services/PlanilhaRemanejamento.php
```

---

### Task 8: Configuracao e chamado do lote

**Files:**
- Create: `config/inventario.php`
- Modify: `.env.example` (final do arquivo)
- Create: `app/Modules/Inventario/Services/RegistrarChamadoDoLote.php`
- Test: `tests/Feature/Inventario/RegistrarChamadoDoLoteTest.php`

**Interfaces:**
- Consumes: `DemandaWriteService::abrir(CriarDemandaData): Demanda`, `DemandaStatusService::resolver(Demanda, ResolucaoDemandaData, int): Demanda`, `RemanejamentoProibido::{loteDesfeito,configuracao}`.
- Produces:
  - `config('inventario.remanejamento.assunto_chamado')` (env `INVENTARIO_ASSUNTO_CHAMADO_LOTE`, padrao `null`), `config('inventario.seplag.destinatarios')` (`list<string>`, env `INVENTARIO_SEPLAG_DESTINATARIOS` separado por virgula), `config('inventario.seplag.assunto_email')` (env `INVENTARIO_SEPLAG_ASSUNTO`).
  - `RegistrarChamadoDoLote::executar(Remanejamento $remanejamento, int $userId): Demanda` — idempotente (devolve a existente); lote desfeito sem chamado -> `loteDesfeito`; assunto ausente/inexistente -> `configuracao`, nada gravado.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Inventario/RegistrarChamadoDoLoteTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Services\RegistrarChamadoDoLote;
use App\Modules\Inventario\Services\RemanejamentoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class RegistrarChamadoDoLoteTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    private DemandaAssunto $assunto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assunto = DemandaAssunto::create(['nome' => 'TST- REMANEJAMENTO '.uniqid(), 'campos_dinamicos' => [], 'ativo' => true]);
        config(['inventario.remanejamento.assunto_chamado' => $this->assunto->nome]);
    }

    public function test_cria_demanda_ja_resolvida_com_o_assunto_configurado(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $pc = $this->equipamento($ana);
        $destino = $this->estacao();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, $destino, [$pc])]);
        $executor = $this->operador();

        $demanda = app(RegistrarChamadoDoLote::class)->executar($lote, $executor->id);

        $this->assertSame(StatusDemanda::RESOLVIDA, $demanda->status);
        $this->assertSame($this->assunto->id, $demanda->assunto_id);
        $this->assertSame($autor->id, $demanda->solicitante_id);
        $this->assertSame($executor->id, $demanda->criado_por_id);
        $this->assertStringContainsString('Remanejamento de '.$lote->created_at->format('d/m/Y H:i'), $demanda->titulo);
        $this->assertStringContainsString('1 item', $demanda->titulo);
        $this->assertStringContainsString($ana->name, $demanda->descricao);
        $this->assertStringContainsString($pc->patrimonio, $demanda->descricao);
        $this->assertStringContainsString($destino->nome, $demanda->descricao);
        $this->assertSame($demanda->id, $lote->fresh()->demanda_id);
    }

    public function test_segunda_chamada_devolve_a_mesma_demanda(): void
    {
        $autor = $this->operador();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);
        $servico = app(RegistrarChamadoDoLote::class);

        $primeira = $servico->executar($lote, $autor->id);
        $antes = Demanda::query()->count();
        $segunda = $servico->executar($lote, $autor->id);

        $this->assertSame($primeira->id, $segunda->id);
        $this->assertSame($antes, Demanda::query()->count());
    }

    public function test_assunto_nao_configurado_e_erro_claro_e_nada_e_gravado(): void
    {
        $autor = $this->operador();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);
        $antes = Demanda::query()->count();

        foreach ([null, 'TST- assunto que nao existe'] as $nome) {
            config(['inventario.remanejamento.assunto_chamado' => $nome]);
            try {
                app(RegistrarChamadoDoLote::class)->executar($lote, $autor->id);
                $this->fail('Esperava RemanejamentoProibido.');
            } catch (RemanejamentoProibido $e) {
                $this->assertArrayHasKey('configuracao', $e->errors());
            }
        }

        $this->assertSame($antes, Demanda::query()->count());
        $this->assertNull($lote->fresh()->demanda_id);
    }

    public function test_lote_desfeito_sem_chamado_e_recusado(): void
    {
        $autor = $this->operador();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);
        app(RemanejamentoService::class)->desfazer($lote, $autor->id);

        $this->expectException(RemanejamentoProibido::class);
        $this->expectExceptionMessage('Lote já desfeito.');
        app(RegistrarChamadoDoLote::class)->executar($lote, $autor->id);
    }

    public function test_editar_lote_preserva_o_chamado(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $pc = $this->equipamento($ana);
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, null, [$pc])]);
        $demanda = app(RegistrarChamadoDoLote::class)->executar($lote, $autor->id);

        app(RemanejamentoService::class)->editar($lote, $this->dadosLote($autor, [$this->pessoaNoLote($ana, null, $this->estacao(), [$pc])]));

        $this->assertSame($demanda->id, $lote->fresh()->demanda_id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RegistrarChamadoDoLoteTest.php`
Expected: FAIL com `Class "App\Modules\Inventario\Services\RegistrarChamadoDoLote" not found`.

- [ ] **Step 3: Config**

`config/inventario.php`:

```php
<?php

declare(strict_types=1);

/*
| Integracoes do Inventario de TI. Nada de endereco, IP ou nome fixo no codigo:
| o assunto do chamado e os destinatarios da SEPLAG mudam por ambiente.
*/

$destinatarios = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('INVENTARIO_SEPLAG_DESTINATARIOS', '')),
)));

return [
    'remanejamento' => [
        // Nome EXATO de um assunto de Demandas. Sem ele, "Registrar chamado"
        // responde com erro de configuracao e nao grava nada.
        'assunto_chamado' => env('INVENTARIO_ASSUNTO_CHAMADO_LOTE'),
    ],

    'seplag' => [
        'destinatarios' => $destinatarios,
        'assunto_email' => env('INVENTARIO_SEPLAG_ASSUNTO', 'Desbloqueio de pontos de rede - remanejamento'),
    ],
];
```

Acrescentar ao final de `.env.example`:

```
# Inventario de TI - remanejamento em lote.
# Nome exato do assunto de Demandas usado no chamado do lote (ex.: REMANEJAMENTO).
INVENTARIO_ASSUNTO_CHAMADO_LOTE=
# Destinatarios do aviso a SEPLAG, separados por virgula.
INVENTARIO_SEPLAG_DESTINATARIOS=
INVENTARIO_SEPLAG_ASSUNTO="Desbloqueio de pontos de rede - remanejamento"
```

- [ ] **Step 4: Implementar o caso de uso**

`app/Modules/Inventario/Services/RegistrarChamadoDoLote.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\DTOs\ResolucaoDemandaData;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Services\DemandaStatusService;
use App\Modules\Demandas\Services\DemandaWriteService;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Models\RemanejamentoPessoa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Registra o chamado do lote em Demandas, ja resolvido, pelos casos de uso
 * publicos do modulo (nunca criando Demanda direto). Um chamado por lote.
 */
final class RegistrarChamadoDoLote
{
    public function __construct(
        private readonly DemandaWriteService $escrita,
        private readonly DemandaStatusService $status,
    ) {}

    public function executar(Remanejamento $remanejamento, int $userId): Demanda
    {
        return DB::transaction(function () use ($remanejamento, $userId): Demanda {
            // Lock no lote: dois cliques simultaneos nao abrem duas demandas.
            $lote = Remanejamento::query()->lockForUpdate()->findOrFail($remanejamento->getKey());

            $existente = $lote->demanda_id !== null ? Demanda::query()->find($lote->demanda_id) : null;
            if ($existente !== null) {
                return $existente;
            }
            if (! $lote->estaAtivo()) {
                throw RemanejamentoProibido::loteDesfeito();
            }

            $assunto = $this->assunto();
            $lote->load([
                'pessoas.usuario:id,name',
                'pessoas.estacaoOrigem:id,nome',
                'pessoas.estacaoDestino:id,nome',
                'pessoas.movimentacoes.equipamento' => static fn ($q) => $q->withTrashed(),
            ]);
            $itens = $lote->itensRemanejados()->count();
            $prioridade = PrioridadeSimples::MEDIA;

            $demanda = $this->escrita->abrir(new CriarDemandaData(
                tipo: TipoDemanda::SOLICITACAO,
                titulo: sprintf('Remanejamento de %s — %d %s', $lote->created_at->format('d/m/Y H:i'), $itens, $itens === 1 ? 'item' : 'itens'),
                descricao: $this->descricao($lote),
                categoria: null,
                subcategoria: null,
                assuntoId: $assunto->id,
                urgencia: $prioridade->urgencia(),
                impacto: $prioridade->impacto(),
                solicitanteId: (int) ($lote->registrado_por_id ?? $userId),
                criadoPorId: $userId,
            ));

            // Abertura e fechamento na data do lote: o chamado documenta algo que
            // ja aconteceu, nao um atendimento que comeca agora.
            $data = CarbonImmutable::instance($lote->created_at);
            $this->status->resolver($demanda, new ResolucaoDemandaData($data, $data), $userId);
            $lote->update(['demanda_id' => $demanda->id]);

            return $demanda->refresh();
        });
    }

    private function assunto(): DemandaAssunto
    {
        $nome = trim((string) config('inventario.remanejamento.assunto_chamado'));
        $assunto = $nome !== '' ? DemandaAssunto::query()->where('nome', $nome)->first() : null;

        if ($assunto === null) {
            throw RemanejamentoProibido::configuracao(
                'O assunto do chamado de remanejamento não está configurado ou não existe em Demandas (INVENTARIO_ASSUNTO_CHAMADO_LOTE).'
            );
        }

        return $assunto;
    }

    private function descricao(Remanejamento $lote): string
    {
        $linhas = ['Remanejamento registrado no Inventário em '.$lote->created_at->format('d/m/Y H:i').'.', ''];

        foreach ($lote->pessoas->sortBy('id') as $pessoa) {
            /** @var RemanejamentoPessoa $pessoa */
            $patrimonios = $pessoa->movimentacoes
                ->where('tipo', TipoMovimentacao::REMANEJAMENTO->value)
                ->map(static fn (Movimentacao $m): string => $m->equipamento?->patrimonio ?? '#'.$m->equipamento_id)
                ->implode(', ');
            $linhas[] = sprintf(
                '- %s: %s -> %s (%s)',
                $pessoa->usuario?->name ?? 'Pessoa removida',
                $pessoa->estacaoOrigem?->nome ?? 'sem estação',
                $pessoa->estacaoDestino?->nome ?? 'sem estação',
                $patrimonios,
            );
        }

        if ($lote->observacao !== null) {
            $linhas[] = '';
            $linhas[] = 'Observação: '.$lote->observacao;
        }

        return implode("\n", $linhas);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RegistrarChamadoDoLoteTest.php`
Expected: 5 PASS.

- [ ] **Step 6: Commit**

```bash
git add config/inventario.php .env.example app/Modules/Inventario/Services/RegistrarChamadoDoLote.php
git commit -m "✨ feat(inventario): chamado do lote resolvido em Demandas" -- config/inventario.php .env.example app/Modules/Inventario/Services/RegistrarChamadoDoLote.php
```

---

### Task 9: Emprestimo avulso convive com o lote

**Files:**
- Modify: `app/Modules/Inventario/Services/MovimentacaoService.php`
- Test: `tests/Feature/Inventario/MovimentacaoAvulsaTest.php`

**Interfaces:**
- Produces: `MovimentacaoService::devolver(int)` recusa movimentacao com `lote_id` (erro `movimentacao`, mandando usar "Desfazer" no lote). Liberacao (`tipo=liberacao`) deixa de contar como "movimentacao ativa" no `registrar` e no `devolver`: equipamento liberado pode ser emprestado e devolvido normalmente. A regra do emprestimo sobre remanejamento ativo nao muda (D4). `registrar` passa a gravar `situacao_origem`.

- [ ] **Step 1: Write the failing test**

`tests/Feature/Inventario/MovimentacaoAvulsaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Modules\Inventario\Services\MovimentacaoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class MovimentacaoAvulsaTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    public function test_devolver_recusa_movimentacao_de_lote(): void
    {
        $autor = $this->operador();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);

        try {
            app(MovimentacaoService::class)->devolver($lote->itensRemanejados()->sole()->id);
            $this->fail('Esperava ValidationException.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Desfazer', $e->errors()['movimentacao'][0]);
        }
        $this->assertSame('ativo', $lote->itensRemanejados()->sole()->status);
    }

    public function test_equipamento_liberado_pode_ser_emprestado_e_devolvido(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $leva = $this->equipamento($ana);
        $fica = $this->equipamento($ana);
        $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, null, [$leva])]);
        $caio = $this->pessoa();

        $emprestimo = app(MovimentacaoService::class)->registrar([
            'equipamento_id' => $fica->id, 'tipo' => 'emprestimo', 'quantidade' => 1,
            'usuario_destino_id' => $caio->id, 'estacao_destino_id' => null,
            'data_prevista_devolucao' => null, 'observacao' => null,
        ], $autor->id);
        $this->assertSame($caio->id, $fica->fresh()->user_id);

        app(MovimentacaoService::class)->devolver($emprestimo->id);

        $this->assertNull($fica->fresh()->user_id);
        $this->assertSame('devolvido', $emprestimo->fresh()->status);
    }

    public function test_equipamento_remanejado_continua_sem_emprestimo(): void
    {
        $autor = $this->operador();
        $pc = $this->equipamento();
        $this->registrarLote($autor, [$this->pessoaNoLote($this->pessoa(), null, null, [$pc])]);

        $this->expectException(ValidationException::class);
        app(MovimentacaoService::class)->registrar([
            'equipamento_id' => $pc->id, 'tipo' => 'emprestimo', 'quantidade' => 1,
            'usuario_destino_id' => $this->pessoa()->id, 'estacao_destino_id' => null,
            'data_prevista_devolucao' => null, 'observacao' => null,
        ], $autor->id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/MovimentacaoAvulsaTest.php`
Expected: FAIL nos dois primeiros (devolver aceita a do lote; emprestimo do liberado recusado por "movimentacao ativa").

- [ ] **Step 3: Ajustar o service**

Substituir `app/Modules/Inventario/Services/MovimentacaoService.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Movimentacao;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Movimentacao avulsa (emprestimo). O remanejamento em lote vive em
 * RemanejamentoService; aqui so se garante que um nao desfaz o outro.
 */
final class MovimentacaoService
{
    public function registrar(array $data, int $actorId): Movimentacao
    {
        return DB::transaction(function () use ($data, $actorId): Movimentacao {
            $equipamento = Equipamento::query()->lockForUpdate()->findOrFail($data['equipamento_id']);
            if (in_array($equipamento->situacao, [SituacaoEquipamento::MANUTENCAO, SituacaoEquipamento::BAIXADO], true)) {
                throw ValidationException::withMessages(['equipamento_id' => 'Equipamento indisponível para movimentação.']);
            }
            if ($data['tipo'] === 'emprestimo' && ! $equipamento->emprestavel) {
                throw ValidationException::withMessages(['equipamento_id' => 'Equipamento não permite empréstimo.']);
            }

            $ativos = (int) $this->ativasComEfeito($equipamento)->sum('quantidade');
            if ($ativos > 0 || $data['quantidade'] !== $equipamento->quantidade) {
                throw ValidationException::withMessages(['quantidade' => 'Movimente a quantidade integral do equipamento, sem movimentação ativa.']);
            }

            $movimentacao = $equipamento->movimentacoes()->create([
                ...$data,
                'registrado_por_id' => $actorId,
                'usuario_origem_id' => $equipamento->user_id,
                'estacao_origem_id' => $equipamento->estacao_id,
                'situacao_origem' => $equipamento->situacao->value,
                'data_saida' => now(),
                'status' => 'ativo',
            ]);
            $equipamento->update([
                'user_id' => $data['usuario_destino_id'] ?? null,
                'estacao_id' => $data['estacao_destino_id'] ?? null,
                'situacao' => SituacaoEquipamento::EM_USO,
            ]);

            return $movimentacao;
        });
    }

    public function devolver(int $id): Movimentacao
    {
        return DB::transaction(function () use ($id): Movimentacao {
            $referencia = Movimentacao::query()->findOrFail($id);
            if ($referencia->lote_id !== null) {
                throw ValidationException::withMessages([
                    'movimentacao' => 'Esta movimentação faz parte de um remanejamento em lote. Use "Desfazer" no lote.',
                ]);
            }
            $equipamento = Equipamento::query()->lockForUpdate()->findOrFail($referencia->equipamento_id);
            $movimentacao = Movimentacao::query()->lockForUpdate()->findOrFail($id);
            if ($movimentacao->status !== 'ativo') {
                throw ValidationException::withMessages(['movimentacao' => 'Movimentação já encerrada.']);
            }

            $movimentacao->update(['status' => 'devolvido', 'data_devolucao' => now()]);
            if (! $this->ativasComEfeito($equipamento)->exists()) {
                $equipamento->update([
                    'user_id' => $movimentacao->usuario_origem_id,
                    'estacao_id' => $movimentacao->estacao_origem_id,
                    'situacao' => $movimentacao->usuario_origem_id || $movimentacao->estacao_origem_id
                        ? SituacaoEquipamento::EM_USO : SituacaoEquipamento::DISPONIVEL,
                ]);
            }

            return $movimentacao;
        });
    }

    /**
     * Movimentacoes ativas que seguram o equipamento. A liberacao do lote fica
     * "ativa" so para poder ser desfeita: o equipamento liberado esta livre.
     */
    private function ativasComEfeito(Equipamento $equipamento): HasMany
    {
        return $equipamento->movimentacoes()
            ->where('status', 'ativo')
            ->where('tipo', '!=', TipoMovimentacao::LIBERACAO->value);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/MovimentacaoAvulsaTest.php tests/Feature/Inventario/RemanejamentoDesfazerTest.php`
Expected: 8 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Inventario/Services/MovimentacaoService.php
git commit -m "🐛 fix(inventario): devolucao avulsa recusa item de lote e ignora liberacao" -- app/Modules/Inventario/Services/MovimentacaoService.php
```

---

### Task 10: Envio a SEPLAG por e-mail (onda B, depois das Tasks 7 e 8)

**Files:**
- Create: `app/Modules/Inventario/Mail/RemanejamentoSeplagMail.php`
- Create: `app/Modules/Inventario/Services/EnviarRemanejamentoSeplag.php`
- Create: `resources/js/Components/Emails/Organisms/RemanejamentoSeplagBody.vue`
- Create: `resources/views/emails/inventario_remanejamento_seplag.blade.php`
- Modify: `resources/js/email-ssr.ts`, `app/Services/Mail/VueEmailRenderer.php` (`COMPONENT_TO_BLADE`)
- Test: `tests/Feature/Inventario/EnviarRemanejamentoSeplagTest.php`

**Interfaces:**
- Consumes: `PlanilhaRemanejamento::{gerar,nomeArquivo,MIME,CABECALHO}` (Task 7), `config('inventario.seplag.*')` (Task 8), `lerPlanilha` do trait (Task 7), `VueEmailRenderer::render(string, array): string`.
- Produces: `RemanejamentoSeplagMail(string $remanejamentoId)` (ShouldQueue; componente de e-mail `RemanejamentoSeplag` com props `dataLote: string`, `totalItens: int`, `pessoas: list<string>`); `EnviarRemanejamentoSeplag::executar(Remanejamento $remanejamento, int $userId): Remanejamento` (incrementa `seplag_envios` e grava `seplag_enviado_em/por` so depois do enfileiramento).

- [ ] **Step 1: Write the failing test**

`tests/Feature/Inventario/EnviarRemanejamentoSeplagTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Mail\RemanejamentoSeplagMail;
use App\Modules\Inventario\Services\EnviarRemanejamentoSeplag;
use App\Modules\Inventario\Services\PlanilhaRemanejamento;
use App\Modules\Inventario\Services\RemanejamentoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class EnviarRemanejamentoSeplagTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    private const DESTINATARIOS = ['seplag1@teste.gov.br', 'seplag2@teste.gov.br'];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['inventario.seplag.destinatarios' => self::DESTINATARIOS, 'inventario.seplag.assunto_email' => 'TST- desbloqueio']);
    }

    public function test_enfileira_email_com_planilha_para_os_destinatarios(): void
    {
        $autor = $this->operador();
        $ana = $this->pessoa();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($ana, null, $this->estacao(), [$this->equipamento($ana)])]);

        app(EnviarRemanejamentoSeplag::class)->executar($lote, $autor->id);

        Mail::assertQueued(
            RemanejamentoSeplagMail::class,
            static fn (RemanejamentoSeplagMail $m): bool => $m->hasTo(self::DESTINATARIOS[0]) && $m->hasTo(self::DESTINATARIOS[1]),
        );
        $mail = Mail::queued(RemanejamentoSeplagMail::class)->first();
        $anexo = $mail->attachments()[0];
        $this->assertSame(app(PlanilhaRemanejamento::class)->nomeArquivo($lote), $anexo->as);
        $this->assertSame(PlanilhaRemanejamento::MIME, $anexo->mime);
        $bytes = $anexo->attachWith(static fn () => null, static fn ($dados) => $dados());
        $this->assertSame(PlanilhaRemanejamento::CABECALHO, $this->lerPlanilha($bytes)[0]);
        $this->assertSame('TST- desbloqueio', $mail->envelope()->subject);
        $html = $mail->render();
        $this->assertStringContainsString('desbloqueio dos pontos de rede', $html);
        $this->assertStringContainsString(e($ana->name), $html);

        $lote->refresh();
        $this->assertSame(1, $lote->seplag_envios);
        $this->assertSame($autor->id, $lote->seplag_enviado_por_id);
        $this->assertNotNull($lote->seplag_enviado_em);
    }

    public function test_reenvio_e_permitido_e_contado(): void
    {
        $autor = $this->operador();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);

        app(EnviarRemanejamentoSeplag::class)->executar($lote, $autor->id);
        app(EnviarRemanejamentoSeplag::class)->executar($lote, $autor->id);

        Mail::assertQueued(RemanejamentoSeplagMail::class, 2);
        $this->assertSame(2, $lote->fresh()->seplag_envios);
    }

    public function test_lote_desfeito_nao_envia(): void
    {
        $autor = $this->operador();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);
        app(RemanejamentoService::class)->desfazer($lote, $autor->id);

        try {
            app(EnviarRemanejamentoSeplag::class)->executar($lote, $autor->id);
            $this->fail('Esperava RemanejamentoProibido.');
        } catch (RemanejamentoProibido $e) {
            $this->assertArrayHasKey('remanejamento', $e->errors());
        }
        Mail::assertNothingQueued();
        $this->assertSame(0, $lote->fresh()->seplag_envios);
    }

    public function test_sem_destinatarios_configurados_e_erro_de_configuracao(): void
    {
        config(['inventario.seplag.destinatarios' => []]);
        $autor = $this->operador();
        $lote = $this->registrarLote($autor, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);

        try {
            app(EnviarRemanejamentoSeplag::class)->executar($lote, $autor->id);
            $this->fail('Esperava RemanejamentoProibido.');
        } catch (RemanejamentoProibido $e) {
            $this->assertArrayHasKey('configuracao', $e->errors());
        }
        Mail::assertNothingQueued();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/EnviarRemanejamentoSeplagTest.php`
Expected: FAIL com `Class "App\Modules\Inventario\Services\EnviarRemanejamentoSeplag" not found`.

- [ ] **Step 3: Template de e-mail (Vue + fallback Blade, padrao de `EmailChangeNotice`)**

`resources/js/Components/Emails/Organisms/RemanejamentoSeplagBody.vue`:

```vue
<script setup lang="ts">
import EmailHeading from '../Atoms/EmailHeading.vue';
import EmailParagraph from '../Atoms/EmailParagraph.vue';
import EmailStrong from '../Atoms/EmailStrong.vue';

interface Props {
    dataLote: string;
    totalItens: number;
    pessoas: string[];
}

defineProps<Props>();
</script>

<template>
    <EmailHeading text="Remanejamento de estações de trabalho" />

    <EmailParagraph>Prezados Senhores,</EmailParagraph>

    <EmailParagraph>
        Gentileza fazer o desbloqueio dos pontos de rede relacionados na planilha de remanejamento em anexo.
    </EmailParagraph>

    <EmailParagraph>
        Lote de <EmailStrong>{{ dataLote }}</EmailStrong>: {{ totalItens }} equipamento(s) de
        {{ pessoas.join(', ') }}.
    </EmailParagraph>

    <EmailParagraph :size="12" color="#64748B" margin="0">
        Mensagem enviada pelo SDC - Defesa Civil de Minas Gerais.
    </EmailParagraph>
</template>
```

Em `resources/js/email-ssr.ts`: acrescentar `import RemanejamentoSeplagBody from './Components/Emails/Organisms/RemanejamentoSeplagBody.vue';` depois dos outros imports de organismo; `RemanejamentoSeplag: RemanejamentoSeplagBody,` em `COMPONENTS`; `RemanejamentoSeplag: 'Remanejamento de estacoes de trabalho',` em `TITLES`.

Em `app/Services/Mail/VueEmailRenderer.php`, em `COMPONENT_TO_BLADE`, acrescentar a linha `'RemanejamentoSeplag'     => 'emails.inventario_remanejamento_seplag',`.

`resources/views/emails/inventario_remanejamento_seplag.blade.php`:

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Remanejamento de estações de trabalho</title>
</head>
<body style="margin:0;padding:0;background:#0B1F3A;font-family:Arial,Helvetica,sans-serif;color:#0B1F3A;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0B1F3A;padding:40px 0;">
        <tr><td align="center">
            <table width="560" cellpadding="0" cellspacing="0" border="0" style="background:#FFFFFF;border-radius:8px;overflow:hidden;">
                <tr><td align="center" style="background:#0B1F3A;padding:24px;">
                    <img src="cid:logo-cedec" alt="CEDEC" width="64" style="display:block;border:0;">
                </td></tr>
                <tr><td style="padding:32px;">
                    <h1 style="margin:0 0 16px;font-size:20px;color:#0B1F3A;">Remanejamento de estações de trabalho</h1>
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">Prezados Senhores,</p>
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                        Gentileza fazer o desbloqueio dos pontos de rede relacionados na planilha de remanejamento em anexo.
                    </p>
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                        Lote de <strong>{{ $dataLote }}</strong>: {{ $totalItens }} equipamento(s) de {{ implode(', ', $pessoas) }}.
                    </p>
                    <p style="margin:0;font-size:12px;color:#64748B;line-height:1.5;">
                        Mensagem enviada pelo SDC - Defesa Civil de Minas Gerais.
                    </p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
```

- [ ] **Step 4: Mailable e caso de uso**

`app/Modules/Inventario/Mail/RemanejamentoSeplagMail.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Mail;

use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Services\PlanilhaRemanejamento;
use App\Services\Mail\VueEmailRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * Pedido de desbloqueio de pontos de rede a SEPLAG, com a planilha do lote.
 * Carrega so o id (primitivo, como os demais mailables em fila): a planilha e
 * gerada na execucao do job, entao reenviar um lote editado manda o atual.
 */
class RemanejamentoSeplagMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $remanejamentoId) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: (string) config('inventario.seplag.assunto_email'),
            using: [
                function (Email $message): void {
                    $logoPath = public_path('imgs/logo_dc.png');
                    if (is_file($logoPath)) {
                        $message->embedFromPath($logoPath, 'logo-cedec', 'image/png');
                    }
                },
            ],
        );
    }

    public function content(): Content
    {
        $lote = $this->lote();

        return new Content(htmlString: app(VueEmailRenderer::class)->render('RemanejamentoSeplag', [
            'dataLote' => $lote->created_at->format('d/m/Y H:i'),
            'totalItens' => (int) $lote->itens_remanejados_count,
            'pessoas' => $lote->pessoas->map(static fn ($p): ?string => $p->usuario?->name)->filter()->values()->all(),
        ]));
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $planilha = app(PlanilhaRemanejamento::class);
        $lote = $this->lote();

        return [
            Attachment::fromData(static fn (): string => $planilha->gerar($lote), $planilha->nomeArquivo($lote))
                ->withMime(PlanilhaRemanejamento::MIME),
        ];
    }

    private function lote(): Remanejamento
    {
        return Remanejamento::query()
            ->with('pessoas.usuario:id,name')
            ->withCount('itensRemanejados')
            ->findOrFail($this->remanejamentoId);
    }
}
```

`app/Modules/Inventario/Services/EnviarRemanejamentoSeplag.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Modules\Inventario\Exceptions\RemanejamentoProibido;
use App\Modules\Inventario\Mail\RemanejamentoSeplagMail;
use App\Modules\Inventario\Models\Remanejamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class EnviarRemanejamentoSeplag
{
    public function executar(Remanejamento $remanejamento, int $userId): Remanejamento
    {
        $destinatarios = array_values(array_filter((array) config('inventario.seplag.destinatarios', [])));
        if ($destinatarios === []) {
            throw RemanejamentoProibido::configuracao(
                'Nenhum destinatário da SEPLAG configurado (INVENTARIO_SEPLAG_DESTINATARIOS).'
            );
        }

        return DB::transaction(function () use ($remanejamento, $userId, $destinatarios): Remanejamento {
            $lote = Remanejamento::query()->lockForUpdate()->findOrFail($remanejamento->getKey());
            if (! $lote->estaAtivo()) {
                throw RemanejamentoProibido::loteDesfeito();
            }

            // Enfileira antes de registrar: se o enfileiramento falhar, a excecao
            // desfaz a transacao e o lote nao mostra um envio que nao saiu.
            Mail::to($destinatarios)->queue(new RemanejamentoSeplagMail($lote->id));
            $lote->update([
                'seplag_envios' => $lote->seplag_envios + 1,
                'seplag_enviado_em' => now(),
                'seplag_enviado_por_id' => $userId,
            ]);

            return $lote;
        });
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/EnviarRemanejamentoSeplagTest.php`
Expected: 4 PASS. (O render cai no Blade porque `bootstrap/ssr/email-ssr.js` nao existe no container; e o mesmo caminho do worker de fila.)

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Inventario/Mail/RemanejamentoSeplagMail.php app/Modules/Inventario/Services/EnviarRemanejamentoSeplag.php resources/js/Components/Emails/Organisms/RemanejamentoSeplagBody.vue resources/views/emails/inventario_remanejamento_seplag.blade.php resources/js/email-ssr.ts app/Services/Mail/VueEmailRenderer.php
git commit -m "✨ feat(inventario): envio da planilha do lote a SEPLAG por e-mail" -- app/Modules/Inventario/Mail/RemanejamentoSeplagMail.php app/Modules/Inventario/Services/EnviarRemanejamentoSeplag.php resources/js/Components/Emails/Organisms/RemanejamentoSeplagBody.vue resources/views/emails/inventario_remanejamento_seplag.blade.php resources/js/email-ssr.ts app/Services/Mail/VueEmailRenderer.php
```

---

### Task 11: HTTP — FormRequest, controllers, rotas e listagem por lote (onda C)

**Files:**
- Create: `app/Modules/Inventario/Requests/SalvarRemanejamentoRequest.php`
- Create: `app/Modules/Inventario/Queries/RemanejamentoListagemQuery.php`
- Create: `app/Modules/Inventario/Queries/OpcoesRemanejamentoQuery.php`
- Create: `app/Modules/Inventario/Support/RemanejamentoApresentacao.php`
- Create: `app/Modules/Inventario/Controllers/RemanejamentoController.php`
- Modify: `app/Modules/Inventario/Controllers/MovimentacaoController.php` (so `index` e o construtor)
- Modify: `routes/modules/inventario.php`
- Test: `tests/Feature/Inventario/RemanejamentoHttpTest.php`

**Interfaces:**
- Consumes: tudo das Tasks 2-10.
- Produces (contrato da Fase 3):
  - Rotas `inventario.remanejamentos.{create,store,edit,update,desfazer,planilha,chamado,seplag}` com `{remanejamento}` uuid (`whereUuid`).
  - `Inventario/MovimentacoesIndex` recebe: `remanejamentos` (paginator; `data[]` com `id, criado_em, status, status_label, observacao, registrado_por, pessoas: list<string>, itens_movidos: int, itens: list<{id, equipamento, patrimonio, usuario_destino, estacao_destino, status, status_label}>, demanda: {id, protocolo}|null, seplag: {envios: int, ultimo_envio_em: string|null}`), `movimentacoes` (paginator de avulsas, `pageName=pagina_avulsas`, mesmo formato de antes), `filters` (`search, status, data`), `opcoes.status`, `pode` (`criar, editar, seplag, planilha, emprestar, devolver`), `equipamentos`, `usuarios`, `estacoes` (para o modal de emprestimo, iguais aos de antes).
  - `Inventario/RemanejamentoForm` recebe: `remanejamento` (`null` ou `{id, observacao, pessoas: list<{usuario_id, estacao_origem_id, estacao_destino_id, condicao_destino, equipamento_ids}>}`) e `opcoes` (`usuarios: list<{value,label}>`, `estacoes: list<{value,label,ponto_rede,user_id,ocupante}>`, `equipamentos: list<{id,nome,patrimonio,categoria,user_id,estacao_id,bloqueio}>`, `bloqueio` = `'Em manutenção'|'Emprestado'|null`).
  - `RemanejamentoApresentacao::{paraListagem(Remanejamento): array, paraFormulario(Remanejamento): array, nomeCurto(?string): string}`; `RemanejamentoListagemQuery::{lotes(array): LengthAwarePaginator, avulsas(array): LengthAwarePaginator}`; `OpcoesRemanejamentoQuery::paraFormulario(): array`; `SalvarRemanejamentoRequest::dados(): RemanejamentoData`.

- [ ] **Step 1: Conferir colisao de parametro de rota**

```bash
grep -rn "Route::model\|Route::bind" routes app/Providers | grep -i "remanejamento"
```
Expected: nenhuma linha.

- [ ] **Step 2: Write the failing test**

`tests/Feature/Inventario/RemanejamentoHttpTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Inventario;

use App\Http\Middleware\VerifyCsrfToken;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Mail\RemanejamentoSeplagMail;
use App\Modules\Inventario\Services\PlanilhaRemanejamento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Inventario\Concerns\CriaInventario;
use Tests\TestCase;

class RemanejamentoHttpTest extends TestCase
{
    use CriaInventario;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutVite();
    }

    public function test_index_lista_lotes_e_mantem_as_avulsas(): void
    {
        $operador = $this->operador();
        $ana = $this->pessoa();
        $lote = $this->registrarLote($operador, [$this->pessoaNoLote($ana, null, $this->estacao(), [$this->equipamento($ana)])]);

        $this->actingAs($operador)->get('/inventario/movimentacoes?search='.urlencode($ana->name))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventario/MovimentacoesIndex')
                ->where('remanejamentos.data.0.id', $lote->id)
                ->where('remanejamentos.data.0.itens_movidos', 1)
                ->where('remanejamentos.data.0.status', 'ativo')
                ->has('remanejamentos.data.0.itens', 1)
                ->where('remanejamentos.data.0.demanda', null)
                ->where('remanejamentos.data.0.seplag.envios', 0)
                ->has('movimentacoes.data')
                ->has('opcoes.status', 2)
                ->where('pode.criar', true)
                ->where('pode.seplag', true));
    }

    public function test_filtro_de_status_do_lote(): void
    {
        $operador = $this->operador();
        $ana = $this->pessoa();
        $lote = $this->registrarLote($operador, [$this->pessoaNoLote($ana, null, null, [$this->equipamento($ana)])]);
        $this->actingAs($operador)->post("/inventario/remanejamentos/{$lote->id}/desfazer")->assertRedirect();
        $busca = urlencode($ana->name);

        $this->actingAs($operador)->get("/inventario/movimentacoes?status=ativo&search={$busca}")
            ->assertInertia(fn (Assert $page) => $page->has('remanejamentos.data', 0));
        $this->actingAs($operador)->get("/inventario/movimentacoes?status=desfeito&search={$busca}")
            ->assertInertia(fn (Assert $page) => $page->has('remanejamentos.data', 1));
    }

    public function test_create_entrega_as_opcoes_do_formulario(): void
    {
        $operador = $this->operador();
        $pc = $this->equipamento(null, null, ['situacao' => SituacaoEquipamento::MANUTENCAO]);

        $this->actingAs($operador)->get('/inventario/remanejamentos/novo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventario/RemanejamentoForm')
                ->where('remanejamento', null)
                ->has('opcoes.usuarios')
                ->has('opcoes.estacoes')
                ->where('opcoes.equipamentos', fn ($lista) => collect($lista)->firstWhere('id', $pc->id)['bloqueio'] === 'Em manutenção'));
    }

    public function test_store_registra_e_volta_para_a_listagem(): void
    {
        $operador = $this->operador();
        $ana = $this->pessoa();
        $pc = $this->equipamento($ana);

        $this->actingAs($operador)->post('/inventario/remanejamentos', [
            'observacao' => 'TST- http',
            'pessoas' => [$this->pessoaNoLote($ana, null, $this->estacao(), [$pc])],
        ])->assertRedirect(route('inventario.movimentacoes.index'));

        $this->assertDatabaseHas('inventario_ti_remanejamentos', ['observacao' => 'TST- http', 'registrado_por_id' => $operador->id]);
    }

    public function test_store_devolve_erro_por_item_e_por_campo(): void
    {
        $operador = $this->operador();
        $ana = $this->pessoa();
        $baixado = $this->equipamento(null, null, ['situacao' => SituacaoEquipamento::BAIXADO]);

        $this->actingAs($operador)->post('/inventario/remanejamentos', [
            'pessoas' => [$this->pessoaNoLote($ana, null, null, [$baixado])],
        ])->assertSessionHasErrors('pessoas.0.equipamento_ids');

        $this->actingAs($operador)->post('/inventario/remanejamentos', ['pessoas' => []])
            ->assertSessionHasErrors('pessoas');

        $this->actingAs($operador)->post('/inventario/remanejamentos', [
            'pessoas' => [['usuario_id' => $ana->id, 'equipamento_ids' => []]],
        ])->assertSessionHasErrors('pessoas.0.equipamento_ids');
    }

    public function test_edit_e_update_preservam_o_lote(): void
    {
        $operador = $this->operador();
        $ana = $this->pessoa();
        $pc = $this->equipamento($ana);
        $novoDestino = $this->estacao();
        $lote = $this->registrarLote($operador, [$this->pessoaNoLote($ana, null, $this->estacao(), [$pc])]);

        $this->actingAs($operador)->get("/inventario/remanejamentos/{$lote->id}/editar")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventario/RemanejamentoForm')
                ->where('remanejamento.id', $lote->id)
                ->where('remanejamento.pessoas.0.equipamento_ids', [$pc->id]));

        $this->actingAs($operador)->put("/inventario/remanejamentos/{$lote->id}", [
            'pessoas' => [$this->pessoaNoLote($ana, null, $novoDestino, [$pc])],
        ])->assertRedirect(route('inventario.movimentacoes.index'));

        $this->assertSame($novoDestino->id, $lote->pessoas()->sole()->estacao_destino_id);
        $this->assertSame($ana->id, $novoDestino->fresh()->user_id);
    }

    public function test_desfazer_e_desfazer_de_novo(): void
    {
        $operador = $this->operador();
        $lote = $this->registrarLote($operador, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);

        $this->actingAs($operador)->post("/inventario/remanejamentos/{$lote->id}/desfazer")->assertSessionHasNoErrors();
        $this->assertSame(StatusRemanejamento::DESFEITO, $lote->fresh()->status);

        $this->actingAs($operador)->post("/inventario/remanejamentos/{$lote->id}/desfazer")
            ->assertSessionHasErrors('remanejamento');
    }

    public function test_planilha_baixa_xlsx(): void
    {
        $operador = $this->operador();
        $lote = $this->registrarLote($operador, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);

        $resposta = $this->actingAs($operador)->get("/inventario/remanejamentos/{$lote->id}/planilha");

        $resposta->assertOk()->assertHeader('Content-Type', PlanilhaRemanejamento::MIME);
        $this->assertStringContainsString(app(PlanilhaRemanejamento::class)->nomeArquivo($lote), (string) $resposta->headers->get('Content-Disposition'));
        $this->assertSame(PlanilhaRemanejamento::CABECALHO, $this->lerPlanilha($resposta->getContent())[0]);
    }

    public function test_chamado_e_seplag_pelas_rotas(): void
    {
        Mail::fake();
        $assunto = DemandaAssunto::create(['nome' => 'TST- REMANEJAMENTO '.uniqid(), 'campos_dinamicos' => [], 'ativo' => true]);
        config([
            'inventario.remanejamento.assunto_chamado' => $assunto->nome,
            'inventario.seplag.destinatarios' => ['seplag@teste.gov.br'],
        ]);
        $operador = $this->operador();
        $lote = $this->registrarLote($operador, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);

        $this->actingAs($operador)->post("/inventario/remanejamentos/{$lote->id}/chamado")->assertSessionHasNoErrors();
        $this->actingAs($operador)->post("/inventario/remanejamentos/{$lote->id}/seplag")->assertSessionHasNoErrors();

        $this->assertNotNull($lote->fresh()->demanda_id);
        $this->assertSame(1, $lote->fresh()->seplag_envios);
        Mail::assertQueued(RemanejamentoSeplagMail::class);
    }

    public function test_config_ausente_volta_com_erro_de_configuracao(): void
    {
        config(['inventario.seplag.destinatarios' => [], 'inventario.remanejamento.assunto_chamado' => null]);
        $operador = $this->operador();
        $lote = $this->registrarLote($operador, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);

        $this->actingAs($operador)->post("/inventario/remanejamentos/{$lote->id}/seplag")->assertSessionHasErrors('configuracao');
        $this->actingAs($operador)->post("/inventario/remanejamentos/{$lote->id}/chamado")->assertSessionHasErrors('configuracao');
    }

    public function test_rotas_exigem_permissao(): void
    {
        $leitor = $this->usuarioCom(['inventario.emprestimos.view']);
        $lote = $this->registrarLote($this->operador(), [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);
        $base = "/inventario/remanejamentos/{$lote->id}";

        foreach ([
            ['get', '/inventario/remanejamentos/novo'], ['post', '/inventario/remanejamentos'],
            ['get', "{$base}/editar"], ['put', $base], ['post', "{$base}/desfazer"],
            ['get', "{$base}/planilha"], ['post', "{$base}/chamado"], ['post', "{$base}/seplag"],
        ] as [$metodo, $url]) {
            $this->actingAs($leitor)->{$metodo}($url)->assertForbidden();
        }

        $this->actingAs($leitor)->get('/inventario/movimentacoes')->assertOk();
    }

    public function test_id_que_nao_e_uuid_da_404(): void
    {
        $operador = $this->operador();

        $this->actingAs($operador)->get('/inventario/remanejamentos/123/editar')->assertNotFound();
        $this->actingAs($operador)->post("/inventario/remanejamentos/123/desfazer")->assertNotFound();
        $this->actingAs($operador)->get('/inventario/remanejamentos/'.Str::uuid().'/editar')->assertNotFound();
    }

    public function test_devolver_pela_rota_recusa_item_de_lote(): void
    {
        $operador = $this->operador();
        $lote = $this->registrarLote($operador, [$this->pessoaNoLote($this->pessoa(), null, null, [$this->equipamento()])]);

        $this->actingAs($operador)->post('/inventario/movimentacoes/'.$lote->itensRemanejados()->sole()->id.'/devolver')
            ->assertSessionHasErrors('movimentacao');
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RemanejamentoHttpTest.php`
Expected: FAIL (rotas inexistentes -> 404; props `remanejamentos` ausentes). `test_devolver_pela_rota_recusa_item_de_lote` ja passa (Task 9).

- [ ] **Step 4: FormRequest**

`app/Modules/Inventario/Requests/SalvarRemanejamentoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use App\Modules\Inventario\DTOs\RemanejamentoData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * So estrutura e existencia. Repeticao, situacao do equipamento, emprestimo e
 * estacao ocupada sao regras de dominio e ficam em RemanejamentoService, com
 * mensagem por item.
 */
class SalvarRemanejamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('post')
            ? 'inventario.remanejamentos.create'
            : 'inventario.remanejamentos.edit');
    }

    public function rules(): array
    {
        return [
            'observacao' => ['nullable', 'string', 'max:5000'],
            'pessoas' => ['required', 'array', 'min:1', 'max:100'],
            'pessoas.*.usuario_id' => ['required', 'integer', 'exists:users,id'],
            'pessoas.*.estacao_origem_id' => ['nullable', 'integer', 'exists:inventario_ti_estacoes,id'],
            'pessoas.*.estacao_destino_id' => ['nullable', 'integer', 'exists:inventario_ti_estacoes,id'],
            'pessoas.*.condicao_destino' => ['nullable', 'string', 'max:60'],
            'pessoas.*.equipamento_ids' => ['required', 'array', 'min:1'],
            'pessoas.*.equipamento_ids.*' => ['integer', Rule::exists('inventario_ti_equipamentos', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'pessoas.required' => 'Informe ao menos uma pessoa no remanejamento.',
            'pessoas.*.usuario_id.required' => 'Selecione a pessoa.',
            'pessoas.*.equipamento_ids.required' => 'Selecione ao menos um equipamento.',
            'pessoas.*.equipamento_ids.min' => 'Selecione ao menos um equipamento.',
            'pessoas.*.equipamento_ids.*.exists' => 'Equipamento não encontrado.',
        ];
    }

    public function dados(): RemanejamentoData
    {
        return RemanejamentoData::fromArray($this->validated(), (int) $this->user()->id);
    }
}
```

- [ ] **Step 5: Queries e apresentacao**

`app/Modules/Inventario/Queries/RemanejamentoListagemQuery.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Queries;

use App\Modules\Inventario\Enums\StatusMovimentacao;
use App\Modules\Inventario\Enums\StatusRemanejamento;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Listagem da tela de movimentacoes: lotes de remanejamento e, em paginacao
 * propria, as movimentacoes avulsas (emprestimos).
 *
 * O filtro `status` aceita os valores das duas listas (ativo/desfeito do lote,
 * ativo/devolvido da avulsa) e cada lista traduz para o seu vocabulario.
 */
final class RemanejamentoListagemQuery
{
    public const POR_PAGINA = 15;

    /** @param array{search?:string|null,status?:string|null,data?:string|null} $filtros */
    public function lotes(array $filtros): LengthAwarePaginator
    {
        $query = Remanejamento::query()->with([
            'registradoPor:id,name',
            'pessoas' => static fn ($q) => $q->orderBy('id'),
            'pessoas.usuario:id,name',
            'itensRemanejados' => static fn ($q) => $q->orderBy('id'),
            'itensRemanejados.equipamento' => static fn ($q) => $q->withTrashed()->select('id', 'nome', 'patrimonio'),
            'itensRemanejados.usuarioDestino:id,name',
            'itensRemanejados.estacaoDestino:id,nome',
            'demanda:id,protocolo',
        ]);

        $termo = trim((string) ($filtros['search'] ?? ''));
        if ($termo !== '') {
            $like = '%'.$termo.'%';
            $query->where(static fn (Builder $q) => $q
                ->whereHas('pessoas.usuario', static fn (Builder $u) => $u->where('name', 'ilike', $like))
                ->orWhereHas('itensRemanejados.equipamento', static fn (Builder $e) => $e->where(
                    static fn (Builder $x) => $x->where('nome', 'ilike', $like)->orWhere('patrimonio', 'ilike', $like)
                )));
        }

        $status = match ($filtros['status'] ?? null) {
            'ativo' => StatusRemanejamento::ATIVO,
            'desfeito', 'devolvido' => StatusRemanejamento::DESFEITO,
            default => null,
        };
        if ($status !== null) {
            $query->where('status', $status->value);
        }
        if (! empty($filtros['data'])) {
            $query->whereDate('created_at', $filtros['data']);
        }

        return $query->latest('created_at')->paginate(self::POR_PAGINA)->withQueryString();
    }

    /** @param array{search?:string|null,status?:string|null} $filtros */
    public function avulsas(array $filtros): LengthAwarePaginator
    {
        $query = Movimentacao::query()->whereNull('lote_id')
            ->with(['equipamento:id,nome,patrimonio', 'registradoPor:id,name', 'usuarioDestino:id,name']);

        $termo = trim((string) ($filtros['search'] ?? ''));
        if ($termo !== '') {
            $like = '%'.$termo.'%';
            $query->whereHas('equipamento', static fn (Builder $e) => $e->where(
                static fn (Builder $x) => $x->where('nome', 'ilike', $like)->orWhere('patrimonio', 'ilike', $like)
            ));
        }

        $status = match ($filtros['status'] ?? null) {
            'ativo' => StatusMovimentacao::ATIVO,
            'desfeito', 'devolvido' => StatusMovimentacao::DEVOLVIDO,
            default => null,
        };
        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query->latest('data_saida')->paginate(self::POR_PAGINA, ['*'], 'pagina_avulsas')->withQueryString();
    }
}
```

`app/Modules/Inventario/Queries/OpcoesRemanejamentoQuery.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Queries;

use App\Models\User;
use App\Modules\Inventario\Enums\SituacaoEquipamento;
use App\Modules\Inventario\Enums\StatusMovimentacao;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Estacao;
use App\Modules\Inventario\Models\Movimentacao;

/**
 * Opcoes do formulario de lote. Equipamento bloqueado vem marcado (e nao
 * omitido) para a tela mostrar por que ele fica com a pessoa.
 */
final class OpcoesRemanejamentoQuery
{
    public function paraFormulario(): array
    {
        $emprestados = Movimentacao::query()
            ->where('tipo', TipoMovimentacao::EMPRESTIMO->value)
            ->where('status', StatusMovimentacao::ATIVO->value)
            ->pluck('equipamento_id')->map(static fn ($id): int => (int) $id)->all();

        return [
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(static fn (User $u): array => ['value' => $u->id, 'label' => $u->name])->all(),
            'estacoes' => Estacao::query()->with('usuario:id,name')->orderBy('nome')->get(['id', 'nome', 'ponto_rede', 'user_id'])
                ->map(static fn (Estacao $e): array => [
                    'value' => $e->id,
                    'label' => $e->nome,
                    'ponto_rede' => $e->ponto_rede,
                    'user_id' => $e->user_id,
                    'ocupante' => $e->usuario?->name,
                ])->all(),
            'equipamentos' => Equipamento::query()->with('categoria:id,nome')
                ->where('situacao', '!=', SituacaoEquipamento::BAIXADO->value)
                ->orderBy('nome')->orderBy('id')
                ->get(['id', 'nome', 'patrimonio', 'categoria_id', 'user_id', 'estacao_id', 'situacao'])
                ->map(static fn (Equipamento $e): array => [
                    'id' => $e->id,
                    'nome' => $e->nome,
                    'patrimonio' => $e->patrimonio,
                    'categoria' => $e->categoria?->nome,
                    'user_id' => $e->user_id,
                    'estacao_id' => $e->estacao_id,
                    'bloqueio' => match (true) {
                        $e->situacao === SituacaoEquipamento::MANUTENCAO => 'Em manutenção',
                        in_array($e->id, $emprestados, true) => 'Emprestado',
                        default => null,
                    },
                ])->all(),
        ];
    }
}
```

`app/Modules/Inventario/Support/RemanejamentoApresentacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Support;

use App\Modules\Inventario\Enums\StatusMovimentacao;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Models\RemanejamentoPessoa;

/** Formato do lote para as telas. Sem consulta: espera as relacoes carregadas. */
final class RemanejamentoApresentacao
{
    public function paraListagem(Remanejamento $lote): array
    {
        return [
            'id' => $lote->id,
            'criado_em' => $lote->created_at?->toIso8601String(),
            'status' => $lote->status->value,
            'status_label' => $lote->status->label(),
            'observacao' => $lote->observacao,
            'registrado_por' => $lote->registradoPor?->name,
            'pessoas' => $lote->pessoas->map(fn (RemanejamentoPessoa $p): string => $this->nomeCurto($p->usuario?->name))->values()->all(),
            'itens_movidos' => $lote->itensRemanejados->count(),
            'itens' => $lote->itensRemanejados->map(static fn (Movimentacao $m): array => [
                'id' => $m->id,
                'equipamento' => $m->equipamento?->nome ?? '—',
                'patrimonio' => $m->equipamento?->patrimonio ?? '—',
                'usuario_destino' => $m->usuarioDestino?->name ?? '—',
                'estacao_destino' => $m->estacaoDestino?->nome ?? '—',
                'status' => $m->status,
                'status_label' => StatusMovimentacao::rotulo($m->status),
            ])->values()->all(),
            'demanda' => $lote->demanda !== null
                ? ['id' => $lote->demanda->id, 'protocolo' => $lote->demanda->protocolo]
                : null,
            'seplag' => [
                'envios' => $lote->seplag_envios,
                'ultimo_envio_em' => $lote->seplag_enviado_em?->toIso8601String(),
            ],
        ];
    }

    public function paraFormulario(Remanejamento $lote): array
    {
        $lote->loadMissing('pessoas.movimentacoes');

        return [
            'id' => $lote->id,
            'observacao' => $lote->observacao ?? '',
            'pessoas' => $lote->pessoas->sortBy('id')->map(static fn (RemanejamentoPessoa $p): array => [
                'usuario_id' => $p->usuario_id,
                'estacao_origem_id' => $p->estacao_origem_id,
                'estacao_destino_id' => $p->estacao_destino_id,
                'condicao_destino' => $p->condicao_destino ?? '',
                'equipamento_ids' => $p->movimentacoes
                    ->where('tipo', TipoMovimentacao::REMANEJAMENTO->value)
                    ->sortBy('id')->pluck('equipamento_id')
                    ->map(static fn ($id): int => (int) $id)->values()->all(),
            ])->values()->all(),
        ];
    }

    /** "Maria da Silva Souza" -> "Maria Souza". */
    public function nomeCurto(?string $nome): string
    {
        $partes = preg_split('/\s+/', trim((string) $nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($partes === []) {
            return '—';
        }

        return count($partes) === 1 ? $partes[0] : $partes[0].' '.$partes[count($partes) - 1];
    }
}
```

- [ ] **Step 6: Controllers**

`app/Modules/Inventario/Controllers/RemanejamentoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Queries\OpcoesRemanejamentoQuery;
use App\Modules\Inventario\Requests\SalvarRemanejamentoRequest;
use App\Modules\Inventario\Services\EnviarRemanejamentoSeplag;
use App\Modules\Inventario\Services\PlanilhaRemanejamento;
use App\Modules\Inventario\Services\RegistrarChamadoDoLote;
use App\Modules\Inventario\Services\RemanejamentoService;
use App\Modules\Inventario\Support\RemanejamentoApresentacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller fino: RemanejamentoProibido e ValidationException, entao erro de
 * dominio volta como erro de sessao por chave sem try/catch aqui.
 */
class RemanejamentoController extends Controller
{
    public function __construct(
        private readonly RemanejamentoService $service,
        private readonly RegistrarChamadoDoLote $chamado,
        private readonly EnviarRemanejamentoSeplag $seplag,
        private readonly PlanilhaRemanejamento $planilha,
        private readonly OpcoesRemanejamentoQuery $opcoes,
        private readonly RemanejamentoApresentacao $apresentacao,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Inventario/RemanejamentoForm', [
            'remanejamento' => null,
            'opcoes' => $this->opcoes->paraFormulario(),
        ]);
    }

    public function store(SalvarRemanejamentoRequest $request): RedirectResponse
    {
        $this->service->registrar($request->dados());

        return redirect()->route('inventario.movimentacoes.index')->with('success', 'Remanejamento registrado.');
    }

    public function edit(Remanejamento $remanejamento): Response|RedirectResponse
    {
        if (! $remanejamento->estaAtivo()) {
            return redirect()->route('inventario.movimentacoes.index')->withErrors(['remanejamento' => 'Lote já desfeito.']);
        }

        return Inertia::render('Inventario/RemanejamentoForm', [
            'remanejamento' => $this->apresentacao->paraFormulario($remanejamento),
            'opcoes' => $this->opcoes->paraFormulario(),
        ]);
    }

    public function update(SalvarRemanejamentoRequest $request, Remanejamento $remanejamento): RedirectResponse
    {
        $this->service->editar($remanejamento, $request->dados());

        return redirect()->route('inventario.movimentacoes.index')->with('success', 'Remanejamento atualizado.');
    }

    public function desfazer(Request $request, Remanejamento $remanejamento): RedirectResponse
    {
        $this->service->desfazer($remanejamento, (int) $request->user()->id);

        return redirect()->back()->with('success', 'Lote desfeito. Equipamentos e estações voltaram ao estado anterior.');
    }

    public function planilha(Remanejamento $remanejamento): HttpResponse
    {
        return response($this->planilha->gerar($remanejamento), 200, [
            'Content-Type' => PlanilhaRemanejamento::MIME,
            'Content-Disposition' => 'attachment; filename="'.$this->planilha->nomeArquivo($remanejamento).'"',
        ]);
    }

    public function chamado(Request $request, Remanejamento $remanejamento): RedirectResponse
    {
        $demanda = $this->chamado->executar($remanejamento, (int) $request->user()->id);

        return redirect()->back()->with('success', 'Chamado '.$demanda->protocolo.' registrado.');
    }

    public function seplag(Request $request, Remanejamento $remanejamento): RedirectResponse
    {
        $lote = $this->seplag->executar($remanejamento, (int) $request->user()->id);

        return redirect()->back()->with('success', "Planilha enviada à SEPLAG ({$lote->seplag_envios}º envio).");
    }
}
```

Em `app/Modules/Inventario/Controllers/MovimentacaoController.php`, trocar o construtor e o `index` (o `store` e o `devolver` ficam como estao):

```php
    public function __construct(
        private readonly MovimentacaoService $service,
        private readonly RemanejamentoListagemQuery $listagem,
        private readonly RemanejamentoApresentacao $apresentacao,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:ativo,desfeito,devolvido'],
            'data' => ['nullable', 'date'],
        ]);
        $user = $request->user();

        return Inertia::render('Inventario/MovimentacoesIndex', [
            'remanejamentos' => $this->listagem->lotes($filters)
                ->through(fn (Remanejamento $lote): array => $this->apresentacao->paraListagem($lote)),
            // Emprestimos avulsos, no formato de antes: a tela antiga le esta prop.
            'movimentacoes' => $this->listagem->avulsas($filters),
            'filters' => $filters,
            'opcoes' => ['status' => StatusRemanejamento::options()],
            'pode' => [
                'criar' => $user->can('inventario.remanejamentos.create'),
                'editar' => $user->can('inventario.remanejamentos.edit'),
                'seplag' => $user->can('inventario.remanejamentos.seplag'),
                'planilha' => $user->can('inventario.emprestimos.export'),
                'emprestar' => $user->can('inventario.emprestimos.create'),
                'devolver' => $user->can('inventario.emprestimos.return'),
            ],
            'equipamentos' => Equipamento::query()->whereNotIn('situacao', ['manutencao', 'baixado'])
                ->orderBy('nome')->get(['id', 'nome', 'patrimonio', 'quantidade']),
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name']),
            'estacoes' => Estacao::query()->orderBy('nome')->get(['id', 'nome']),
        ]);
    }
```
Imports novos: `App\Modules\Inventario\Enums\StatusRemanejamento`, `App\Modules\Inventario\Models\Remanejamento`, `App\Modules\Inventario\Queries\RemanejamentoListagemQuery`, `App\Modules\Inventario\Support\RemanejamentoApresentacao`; remover `use App\Modules\Inventario\Models\Movimentacao;` se ficar sem uso.

- [ ] **Step 7: Rotas**

Em `routes/modules/inventario.php`, acrescentar `use App\Modules\Inventario\Controllers\RemanejamentoController;` e, dentro do grupo, depois da rota `movimentacoes.devolver`:

```php
    // Lotes de remanejamento. {remanejamento} e uuid: whereUuid devolve 404 (e
    // nao 500 do cast do PostgreSQL) para id malformado. O nome nao tem
    // Route::model() global (conferido com grep em routes/ e app/Providers).
    Route::get('/remanejamentos/novo', [RemanejamentoController::class, 'create'])
        ->name('remanejamentos.create')->middleware('can:inventario.remanejamentos.create');
    Route::post('/remanejamentos', [RemanejamentoController::class, 'store'])
        ->name('remanejamentos.store')->middleware('can:inventario.remanejamentos.create');
    Route::get('/remanejamentos/{remanejamento}/editar', [RemanejamentoController::class, 'edit'])
        ->name('remanejamentos.edit')->middleware('can:inventario.remanejamentos.edit')->whereUuid('remanejamento');
    Route::put('/remanejamentos/{remanejamento}', [RemanejamentoController::class, 'update'])
        ->name('remanejamentos.update')->middleware('can:inventario.remanejamentos.edit')->whereUuid('remanejamento');
    Route::post('/remanejamentos/{remanejamento}/desfazer', [RemanejamentoController::class, 'desfazer'])
        ->name('remanejamentos.desfazer')->middleware('can:inventario.remanejamentos.edit')->whereUuid('remanejamento');
    Route::get('/remanejamentos/{remanejamento}/planilha', [RemanejamentoController::class, 'planilha'])
        ->name('remanejamentos.planilha')->middleware('can:inventario.emprestimos.export')->whereUuid('remanejamento');
    Route::post('/remanejamentos/{remanejamento}/chamado', [RemanejamentoController::class, 'chamado'])
        ->name('remanejamentos.chamado')->middleware('can:inventario.remanejamentos.edit')->whereUuid('remanejamento');
    Route::post('/remanejamentos/{remanejamento}/seplag', [RemanejamentoController::class, 'seplag'])
        ->name('remanejamentos.seplag')->middleware('can:inventario.remanejamentos.seplag')->whereUuid('remanejamento');
```

- [ ] **Step 8: Run tests to verify they pass**

```bash
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RemanejamentoHttpTest.php
bash /c/tmp/teste-demandas.sh php artisan route:list --path=inventario/remanejamentos
```
Expected: 13 PASS; 8 rotas `inventario.remanejamentos.*`, cada uma com `can:`. Se o 403 de POST vier como redirect, conferir o `Handler::render` (403 vira `Errors/Forbidden` com status 403).

- [ ] **Step 9: Tela antiga ainda abre**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario`
Expected: tudo PASS. A Page antiga le `movimentacoes.data`, `filters`, `equipamentos`, `usuarios`, `estacoes` — todas presentes.

- [ ] **Step 10: Commit**

```bash
git add app/Modules/Inventario/Requests/SalvarRemanejamentoRequest.php app/Modules/Inventario/Queries/RemanejamentoListagemQuery.php app/Modules/Inventario/Queries/OpcoesRemanejamentoQuery.php app/Modules/Inventario/Support/RemanejamentoApresentacao.php app/Modules/Inventario/Controllers/RemanejamentoController.php app/Modules/Inventario/Controllers/MovimentacaoController.php routes/modules/inventario.php
git commit -m "✨ feat(inventario): rotas e listagem dos lotes de remanejamento" -- app/Modules/Inventario/Requests/SalvarRemanejamentoRequest.php app/Modules/Inventario/Queries/RemanejamentoListagemQuery.php app/Modules/Inventario/Queries/OpcoesRemanejamentoQuery.php app/Modules/Inventario/Support/RemanejamentoApresentacao.php app/Modules/Inventario/Controllers/RemanejamentoController.php app/Modules/Inventario/Controllers/MovimentacaoController.php routes/modules/inventario.php
```

---

### Task 12: Fechamento da Fase 2

Sem codigo novo, salvo correcoes (`🐛 fix(inventario): ...`).

- [ ] **Step 1: Suite da fase e regressao**

```bash
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario tests/Feature/Demandas tests/Unit/Demandas
```
Expected: tudo PASS.

- [ ] **Step 2: Rotas, permissoes e escopo**

```bash
bash /c/tmp/teste-demandas.sh php artisan route:list --path=inventario
bash /c/tmp/teste-demandas.sh php artisan permissions:audit
git status --short
grep -rn "dd(\|dump(\|Log::debug\|ray(" app/Modules/Inventario
```
Expected: rotas com `can:`; audit sem divergencia nova alem dos 3 slugs ainda nao semeados; nenhum log de depuracao; nada de `tests/` staged.

- [ ] **Step 3: Entrega**

O controlador mergeia a branch em `dev` por uma worktree temporaria e refaz o deploy de homolog. Pontos do deploy desta fase (o controlador executa): dependencia nova exige `composer install` na imagem/container (o vendor vive na imagem); `php artisan db:seed --class=RolesAndPermissionsSeeder --force` para criar os 3 slugs e atribui-los; definir `INVENTARIO_ASSUNTO_CHAMADO_LOTE`, `INVENTARIO_SEPLAG_DESTINATARIOS` e `INVENTARIO_SEPLAG_ASSUNTO` no `.env` de homolog; `config:clear`. A tela antiga continua igual (sem acesso aos lotes ainda).

---

## FASE 3 — Frontend

Entrega: a tela de Movimentacoes passa a mostrar os lotes (com expandir, acoes, filtros, paginacao e tempo real) e o formulario de lote (novo/editar). Mesmo padrao do Catalogo de demandas: Page fina -> Template -> Organisms -> Molecules/Atoms, raiz `space-y-6 pb-8`, filhos com `:espaco-inferior="false"`, dark mode por classe, tabela a partir de `lg` e blocos abaixo (`useMobile().isDesktop`).

**Paralelismo:** Tasks 13 e 14 em paralelo. Posse de arquivos:
- Task 13: `resources/js/Pages/Inventario/MovimentacoesIndex.vue`, `resources/js/Templates/Inventario/RemanejamentosIndexTemplate.vue`, `resources/js/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue`, `resources/js/Components/Organisms/Inventario/Remanejamentos/{RemanejamentosFiltersSection,RemanejamentosLista,RemanejamentoAcoes,RemanejamentoItensTabela}.vue`, `resources/js/Components/Organisms/Inventario/Movimentacoes/{EmprestimosAvulsosLista,EmprestimoFormModal}.vue`, `resources/js/Composables/inventario/useRemanejamentoAcoes.js`.
- Task 14: `resources/js/Pages/Inventario/RemanejamentoForm.vue`, `resources/js/Templates/Inventario/RemanejamentoFormTemplate.vue`, `resources/js/Components/Organisms/Inventario/Remanejamentos/PessoaRemanejamentoBloco.vue`, `resources/js/Components/Molecules/Inventario/EquipamentoBusca.vue`, `resources/js/Composables/inventario/useRemanejamentoForm.js`.

Icones: `@heroicons/vue/24/outline` (o Inventario ja usa `ArchiveBoxIcon` no `InventarioIndexTemplate`). Datas: `formatarDataHora` de `@/Support/demandasFormat` (reuso, sem copia).

### Task 13: Listagem de lotes em Movimentacoes

**Files:**
- Create: `resources/js/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue`
- Create: `resources/js/Composables/inventario/useRemanejamentoAcoes.js`
- Create: `resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentosFiltersSection.vue`
- Create: `resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentoAcoes.vue`
- Create: `resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentoItensTabela.vue`
- Create: `resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentosLista.vue`
- Create: `resources/js/Components/Organisms/Inventario/Movimentacoes/EmprestimosAvulsosLista.vue`
- Create: `resources/js/Components/Organisms/Inventario/Movimentacoes/EmprestimoFormModal.vue`
- Create: `resources/js/Templates/Inventario/RemanejamentosIndexTemplate.vue`
- Modify (reescrever): `resources/js/Pages/Inventario/MovimentacoesIndex.vue`
- Test: `npx vite build` em diretorio proprio (o projeto nao tem teste unitario de JS); conferencia no navegador fica na Task 16.

**Interfaces:**
- Consumes: props de `Inventario/MovimentacoesIndex` (Task 11); rotas `inventario.remanejamentos.{create,edit,desfazer,planilha,chamado,seplag}`, `inventario.movimentacoes.{index,store,devolver}`, `demandas.show`; canal `listagem.inventario-remanejamentos` (Task 6).
- Produces: `<StatusRemanejamentoBadge :status :label />` (aceita status de lote e de item); `useRemanejamentoAcoes() -> { loteParaDesfazer, processando, pedirDesfazer, cancelarDesfazer, confirmarDesfazer, editar, baixarPlanilha, registrarChamado, abrirChamado, enviarSeplag }`.

- [ ] **Step 1: Atom e composable de acoes**

`resources/js/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue`:

```vue
<template>
  <Badge :variant="VARIANTE[status] ?? 'default'" size="sm">{{ label }}</Badge>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';

defineProps({
  status: { type: String, required: true },
  label: { type: String, required: true },
});

// Lote: ativo/desfeito. Item: ativo/devolvido/substituida.
const VARIANTE = { ativo: 'success', desfeito: 'neutral', devolvido: 'default', substituida: 'warning' };
</script>
```

`resources/js/Composables/inventario/useRemanejamentoAcoes.js`:

```js
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

// Acoes de um lote na listagem. Erro de dominio volta como erro de sessao
// (chave remanejamento/configuracao) e o template mostra no aviso do topo.
export function useRemanejamentoAcoes() {
  const loteParaDesfazer = ref(null);
  const processando = ref(false);

  const opcoes = {
    preserveScroll: true,
    onStart: () => { processando.value = true; },
    onFinish: () => { processando.value = false; },
  };

  function pedirDesfazer(lote) {
    loteParaDesfazer.value = lote;
  }

  function cancelarDesfazer() {
    if (!processando.value) loteParaDesfazer.value = null;
  }

  function confirmarDesfazer() {
    if (!loteParaDesfazer.value) return;
    router.post(route('inventario.remanejamentos.desfazer', loteParaDesfazer.value.id), {}, {
      ...opcoes,
      onSuccess: () => { loteParaDesfazer.value = null; },
    });
  }

  function editar(lote) {
    router.visit(route('inventario.remanejamentos.edit', lote.id));
  }

  function baixarPlanilha(lote) {
    window.location.href = route('inventario.remanejamentos.planilha', lote.id);
  }

  function registrarChamado(lote) {
    router.post(route('inventario.remanejamentos.chamado', lote.id), {}, opcoes);
  }

  function abrirChamado(lote) {
    router.visit(route('demandas.show', lote.demanda.id));
  }

  function enviarSeplag(lote) {
    router.post(route('inventario.remanejamentos.seplag', lote.id), {}, opcoes);
  }

  return {
    loteParaDesfazer, processando, pedirDesfazer, cancelarDesfazer, confirmarDesfazer,
    editar, baixarPlanilha, registrarChamado, abrirChamado, enviarSeplag,
  };
}
```

- [ ] **Step 2: Organisms do lote**

`resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentosFiltersSection.vue`:

```vue
<template>
  <FilterSection title="Filtros" :columns="4">
    <FilterField v-model="filtros.search" label="Busca" placeholder="Pessoa, equipamento ou patrimônio" @keyup.enter="$emit('aplicar')" />
    <FilterField v-model="filtros.status" label="Status" type="select" :options="[{ value: '', label: 'Todos' }, ...opcoesStatus]" />
    <FilterField v-model="filtros.data" label="Data" type="date" />
    <div class="flex items-end gap-2">
      <Button variant="primary" size="md" @click="$emit('aplicar')">Filtrar</Button>
      <Button variant="secondary" size="md" @click="$emit('limpar')">Limpar</Button>
    </div>
  </FilterSection>
</template>

<script setup>
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

defineProps({
  filtros: { type: Object, required: true },
  opcoesStatus: { type: Array, required: true },
});
defineEmits(['aplicar', 'limpar']);
</script>
```

`resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentoAcoes.vue`:

```vue
<template>
  <div class="flex flex-wrap items-center justify-end gap-1">
    <ButtonIcon
      v-if="pode.seplag && ativo"
      :icon="PaperAirplaneIcon"
      variant="info"
      size="sm"
      :title="tituloSeplag"
      @click="$emit('seplag', lote)"
    />
    <ButtonIcon
      :icon="ChevronDownIcon"
      variant="secondary"
      size="sm"
      class="transition-transform"
      :class="{ 'rotate-180': expandido }"
      :title="expandido ? 'Recolher itens' : 'Ver itens do lote'"
      @click="$emit('expandir', lote)"
    />
    <ButtonIcon v-if="pode.planilha" :icon="ArrowDownTrayIcon" variant="secondary" size="sm" title="Baixar planilha" @click="$emit('planilha', lote)" />
    <ButtonIcon v-if="pode.editar && ativo" :icon="PencilSquareIcon" variant="primary" size="sm" title="Editar lote" @click="$emit('editar', lote)" />
    <ButtonIcon v-if="pode.editar && ativo" :icon="ArrowUturnLeftIcon" variant="danger" size="sm" title="Desfazer lote" @click="$emit('desfazer', lote)" />
    <Button v-if="lote.demanda" variant="outline" size="sm" :title="`Protocolo ${lote.demanda.protocolo}`" @click="$emit('abrir-chamado', lote)">
      Chamado #{{ lote.demanda.id }}
    </Button>
    <ButtonIcon
      v-else-if="pode.editar && ativo"
      :icon="TicketIcon"
      variant="success"
      size="sm"
      title="Registrar chamado"
      @click="$emit('chamado', lote)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  ArrowDownTrayIcon, ArrowUturnLeftIcon, ChevronDownIcon, PaperAirplaneIcon, PencilSquareIcon, TicketIcon,
} from '@heroicons/vue/24/outline';
import Button from '@/Components/Atoms/Button/Button.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import { formatarDataHora } from '@/Support/demandasFormat';

const props = defineProps({
  lote: { type: Object, required: true },
  pode: { type: Object, required: true },
  expandido: { type: Boolean, default: false },
});
defineEmits(['seplag', 'expandir', 'planilha', 'editar', 'desfazer', 'chamado', 'abrir-chamado']);

const ativo = computed(() => props.lote.status === 'ativo');

const tituloSeplag = computed(() => {
  const { envios, ultimo_envio_em: ultimo } = props.lote.seplag;
  if (!envios) return 'Enviar à SEPLAG';
  return `Reenviar à SEPLAG — ${envios} envio(s), último em ${formatarDataHora(ultimo)}`;
});
</script>
```

`resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentoItensTabela.vue`:

```vue
<template>
  <div v-if="isDesktop" class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
      <thead>
        <tr>
          <th v-for="coluna in COLUNAS" :key="coluna" scope="col" :class="TH">{{ coluna }}</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
        <tr v-for="item in itens" :key="item.id">
          <td :class="TD">#{{ item.id }}</td>
          <td :class="TD"><span class="block max-w-[16rem] truncate" :title="item.equipamento">{{ item.equipamento }}</span></td>
          <td :class="TD">{{ item.patrimonio }}</td>
          <td :class="TD"><span class="block max-w-[12rem] truncate">{{ item.usuario_destino }}</span></td>
          <td :class="TD"><span class="block max-w-[12rem] truncate">{{ item.estacao_destino }}</span></td>
          <td :class="TD"><StatusRemanejamentoBadge :status="item.status" :label="item.status_label" /></td>
        </tr>
      </tbody>
    </table>
  </div>

  <ul v-else class="space-y-2">
    <li
      v-for="item in itens"
      :key="item.id"
      class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <div class="flex min-w-0 items-start justify-between gap-2">
        <p class="min-w-0 truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ item.equipamento }}</p>
        <StatusRemanejamentoBadge class="shrink-0" :status="item.status" :label="item.status_label" />
      </div>
      <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Patrimônio</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ item.patrimonio }}</dd></div>
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">ID</dt><dd class="text-slate-800 dark:text-slate-200">#{{ item.id }}</dd></div>
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Usuário destino</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ item.usuario_destino }}</dd></div>
        <div class="min-w-0"><dt class="text-slate-500 dark:text-slate-400">Estação destino</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ item.estacao_destino }}</dd></div>
      </dl>
    </li>
  </ul>
</template>

<script setup>
import StatusRemanejamentoBadge from '@/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue';
import { useMobile } from '@/Composables/useMobile';

defineProps({ itens: { type: Array, required: true } });

const { isDesktop } = useMobile();
const COLUNAS = ['ID', 'Equipamento', 'Patrimônio', 'Usuário destino', 'Estação destino', 'Status'];
const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
</script>
```

`resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentosLista.vue`:

```vue
<template>
  <!-- Mesmo corte do Catalogo: tabela a partir de lg, bloco abaixo disso. -->
  <div
    v-if="isDesktop"
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
  >
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-900/50">
          <tr>
            <th scope="col" :class="TH">Data / hora</th>
            <th scope="col" :class="TH">Pessoas</th>
            <th scope="col" :class="TH">Itens</th>
            <th scope="col" :class="TH">Status</th>
            <th scope="col" class="table-actions-head px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <template v-for="lote in lotes" :key="lote.id">
            <tr class="table-row-solid transition-colors">
              <td :class="TD_FORTE">{{ formatarDataHora(lote.criado_em) }}</td>
              <td :class="TD"><span class="block max-w-[22rem] truncate" :title="lote.pessoas.join(', ')">{{ lote.pessoas.join(', ') }}</span></td>
              <td :class="TD"><Badge variant="info" size="sm">{{ rotuloItens(lote) }}</Badge></td>
              <td :class="TD"><StatusRemanejamentoBadge :status="lote.status" :label="lote.status_label" /></td>
              <td class="table-actions-cell px-3 py-2">
                <RemanejamentoAcoes :lote="lote" :pode="pode" :expandido="estaExpandido(lote.id)" v-bind="repasse" @expandir="alternar(lote.id)" />
              </td>
            </tr>
            <tr v-if="estaExpandido(lote.id)">
              <td colspan="5" class="border-t border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-700/50 dark:bg-slate-900/40">
                <p v-if="lote.observacao" class="mb-3 text-sm text-slate-600 dark:text-slate-300">{{ lote.observacao }}</p>
                <RemanejamentoItensTabela :itens="lote.itens" />
              </td>
            </tr>
          </template>
          <tr v-if="lotes.length === 0">
            <td colspan="5" class="p-0">
              <ListEmptyState title="Nenhum remanejamento encontrado" helper='Ajuste os filtros ou use "Novo remanejamento".' />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div v-else class="space-y-3">
    <article
      v-for="lote in lotes"
      :key="lote.id"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatarDataHora(lote.criado_em) }}</p>
          <h3 class="break-words text-sm font-bold text-slate-900 dark:text-slate-100">{{ lote.pessoas.join(', ') }}</h3>
        </div>
        <StatusRemanejamentoBadge class="shrink-0" :status="lote.status" :label="lote.status_label" />
      </header>
      <div class="mt-2"><Badge variant="info" size="sm">{{ rotuloItens(lote) }}</Badge></div>
      <footer class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <RemanejamentoAcoes :lote="lote" :pode="pode" :expandido="estaExpandido(lote.id)" v-bind="repasse" @expandir="alternar(lote.id)" />
      </footer>
      <div v-if="estaExpandido(lote.id)" class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <p v-if="lote.observacao" class="mb-3 break-words text-sm text-slate-600 dark:text-slate-300">{{ lote.observacao }}</p>
        <RemanejamentoItensTabela :itens="lote.itens" />
      </div>
    </article>
    <div
      v-if="lotes.length === 0"
      class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <ListEmptyState title="Nenhum remanejamento encontrado" helper='Ajuste os filtros ou use "Novo remanejamento".' />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import StatusRemanejamentoBadge from '@/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue';
import RemanejamentoAcoes from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentoAcoes.vue';
import RemanejamentoItensTabela from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentoItensTabela.vue';
import { useMobile } from '@/Composables/useMobile';
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({
  lotes: { type: Array, required: true },
  pode: { type: Object, required: true },
});
const emit = defineEmits(['seplag', 'planilha', 'editar', 'desfazer', 'chamado', 'abrir-chamado']);

const { isDesktop } = useMobile();

// Repassa as acoes de RemanejamentoAcoes para o template sem reescrever cada uma.
const repasse = {
  onSeplag: (lote) => emit('seplag', lote),
  onPlanilha: (lote) => emit('planilha', lote),
  onEditar: (lote) => emit('editar', lote),
  onDesfazer: (lote) => emit('desfazer', lote),
  onChamado: (lote) => emit('chamado', lote),
  onAbrirChamado: (lote) => emit('abrir-chamado', lote),
};

// Guardado por id: o reload do tempo real reordena a lista sem fechar o que esta aberto.
const expandidos = ref([]);
const estaExpandido = (id) => expandidos.value.includes(id);
function alternar(id) {
  expandidos.value = estaExpandido(id) ? expandidos.value.filter((item) => item !== id) : [...expandidos.value, id];
}

const rotuloItens = (lote) => `${lote.itens_movidos} ${lote.itens_movidos === 1 ? 'item movido' : 'itens movidos'}`;

const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'whitespace-nowrap px-3 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100';
</script>
```

- [ ] **Step 3: Emprestimos avulsos (preserva o que a tela antiga fazia)**

`resources/js/Components/Organisms/Inventario/Movimentacoes/EmprestimosAvulsosLista.vue`:

```vue
<template>
  <div v-if="isDesktop" class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
      <thead class="bg-slate-50 dark:bg-slate-900/50">
        <tr>
          <th v-for="coluna in COLUNAS" :key="coluna" scope="col" :class="TH">{{ coluna }}</th>
          <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ação</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
        <tr v-for="item in movimentacoes" :key="item.id" class="table-row-solid">
          <td :class="TD">{{ formatarDataHora(item.data_saida) }}</td>
          <td :class="TD"><span class="block max-w-[16rem] truncate">{{ item.equipamento?.nome ?? '—' }}</span></td>
          <td :class="TD">{{ item.equipamento?.patrimonio ?? '—' }}</td>
          <td :class="TD"><span class="block max-w-[12rem] truncate">{{ item.usuario_destino?.name ?? '—' }}</span></td>
          <td :class="TD"><StatusRemanejamentoBadge :status="item.status" :label="item.status === 'ativo' ? 'Ativo' : 'Devolvido'" /></td>
          <td class="px-3 py-2 text-right">
            <Button v-if="podeDevolver && item.status === 'ativo'" variant="success" size="sm" @click="$emit('devolver', item)">Devolver</Button>
          </td>
        </tr>
        <tr v-if="movimentacoes.length === 0">
          <td :colspan="COLUNAS.length + 1" class="p-0"><ListEmptyState title="Nenhum empréstimo encontrado" helper="Empréstimos avulsos aparecem aqui." /></td>
        </tr>
      </tbody>
    </table>
  </div>

  <div v-else class="space-y-3">
    <article
      v-for="item in movimentacoes"
      :key="item.id"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <div class="flex min-w-0 items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ item.equipamento?.nome ?? '—' }}</p>
          <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ item.equipamento?.patrimonio ?? '—' }} · {{ formatarDataHora(item.data_saida) }}</p>
        </div>
        <StatusRemanejamentoBadge class="shrink-0" :status="item.status" :label="item.status === 'ativo' ? 'Ativo' : 'Devolvido'" />
      </div>
      <p class="mt-2 truncate text-xs text-slate-600 dark:text-slate-300">Com: {{ item.usuario_destino?.name ?? '—' }}</p>
      <div v-if="podeDevolver && item.status === 'ativo'" class="mt-3 flex justify-end">
        <Button variant="success" size="sm" @click="$emit('devolver', item)">Devolver</Button>
      </div>
    </article>
    <ListEmptyState v-if="movimentacoes.length === 0" title="Nenhum empréstimo encontrado" helper="Empréstimos avulsos aparecem aqui." />
  </div>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import StatusRemanejamentoBadge from '@/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue';
import { useMobile } from '@/Composables/useMobile';
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({
  movimentacoes: { type: Array, required: true },
  podeDevolver: { type: Boolean, default: false },
});
defineEmits(['devolver']);

const { isDesktop } = useMobile();
const COLUNAS = ['Saída', 'Equipamento', 'Patrimônio', 'Com', 'Status'];
const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
</script>
```

`resources/js/Components/Organisms/Inventario/Movimentacoes/EmprestimoFormModal.vue`:

```vue
<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <div class="p-6">
      <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Novo empréstimo</h3>
        <button type="button" class="text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-200" @click="fechar">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <form class="mt-4" @submit.prevent="salvar">
        <FormSection :cols="1">
          <FormSelect v-model="form.equipamento_id" label="Equipamento" required :options="opcoesEquipamento" :error="form.errors.equipamento_id" />
          <FormField v-model="form.quantidade" label="Quantidade" type="number" required :error="form.errors.quantidade" />
          <FormSelect v-model="form.usuario_destino_id" label="Usuário de destino" :options="opcoesUsuario" placeholder="Nenhum" :error="form.errors.usuario_destino_id" />
          <FormSelect v-model="form.estacao_destino_id" label="Estação de destino" :options="opcoesEstacao" placeholder="Nenhuma" :error="form.errors.estacao_destino_id" />
          <FormField v-model="form.data_prevista_devolucao" label="Devolução prevista" type="date" :error="form.errors.data_prevista_devolucao" />
          <FormTextarea v-model="form.observacao" label="Observação" :rows="3" :error="form.errors.observacao" />
        </FormSection>

        <div class="mt-2 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700/50">
          <Button type="button" variant="outline" @click="fechar">Cancelar</Button>
          <Button type="submit" variant="primary" :loading="form.processing">Registrar</Button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  equipamentos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  estacoes: { type: Array, default: () => [] },
});
const emit = defineEmits(['close']);

const opcoesEquipamento = computed(() => props.equipamentos.map((e) => ({ value: e.id, label: `${e.nome} — ${e.patrimonio} (${e.quantidade})` })));
const opcoesUsuario = computed(() => props.usuarios.map((u) => ({ value: u.id, label: u.name })));
const opcoesEstacao = computed(() => props.estacoes.map((e) => ({ value: e.id, label: e.nome })));

// tipo fixo: remanejamento agora e sempre em lote, pelo formulario proprio.
const form = useForm({
  equipamento_id: '', tipo: 'emprestimo', quantidade: 1, usuario_destino_id: '',
  estacao_destino_id: '', data_prevista_devolucao: '', observacao: '',
});

// Empréstimo e da quantidade integral (regra do MovimentacaoService).
watch(() => form.equipamento_id, (id) => {
  form.quantidade = props.equipamentos.find((e) => e.id === Number(id))?.quantidade ?? 1;
});

function fechar() {
  if (form.processing) return;
  emit('close');
}

function salvar() {
  form
    .transform((dados) => ({
      ...dados,
      quantidade: Number(dados.quantidade),
      usuario_destino_id: dados.usuario_destino_id || null,
      estacao_destino_id: dados.estacao_destino_id || null,
      data_prevista_devolucao: dados.data_prevista_devolucao || null,
    }))
    .post(route('inventario.movimentacoes.store'), {
      preserveScroll: true,
      onSuccess: () => { form.reset(); emit('close'); },
    });
}
</script>
```

- [ ] **Step 4: Template e Page**

`resources/js/Templates/Inventario/RemanejamentosIndexTemplate.vue`:

```vue
<template>
  <div class="space-y-6 pb-8">
    <PageHeader
      title="Movimentações"
      description="Remanejamentos em lote e empréstimos de equipamentos"
      :icon="ArchiveBoxIcon"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <Button v-if="pode.emprestar" variant="secondary" size="md" :icon="PlusIcon" icon-position="left" @click="emprestimoAberto = true">
            Novo empréstimo
          </Button>
          <Button v-if="pode.criar" variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="$emit('novo')">
            Novo remanejamento
          </Button>
        </div>
      </template>
    </PageHeader>

    <div
      v-if="errosDaPagina.length"
      role="alert"
      class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
    >
      <p v-for="erro in errosDaPagina" :key="erro" class="break-words">{{ erro }}</p>
    </div>

    <RemanejamentosFiltersSection :filtros="filtros" :opcoes-status="opcoes.status" @aplicar="$emit('aplicar')" @limpar="$emit('limpar')" />

    <ListContainer title="Remanejamentos" :icon="ArrowsRightLeftIcon" :count="paginacao.total">
      <RemanejamentosLista
        :lotes="lotes"
        :pode="pode"
        @seplag="acoes.enviarSeplag"
        @planilha="acoes.baixarPlanilha"
        @editar="acoes.editar"
        @desfazer="acoes.pedirDesfazer"
        @chamado="acoes.registrarChamado"
        @abrir-chamado="acoes.abrirChamado"
      />
      <Pagination class="mt-4" :pagination="paginacao" @page-change="(p) => $emit('pagina', p)" />
    </ListContainer>

    <ListContainer title="Empréstimos" :icon="ArchiveBoxIcon" :count="avulsas.total">
      <EmprestimosAvulsosLista :movimentacoes="avulsas.data" :pode-devolver="pode.devolver" @devolver="(item) => (paraDevolver = item)" />
      <Pagination class="mt-4" :pagination="avulsas" @page-change="(p) => $emit('pagina-avulsas', p)" />
    </ListContainer>

    <ConfirmDialog
      :is-open="Boolean(acoes.loteParaDesfazer.value)"
      title="Desfazer remanejamento"
      message="Desfazer este lote?"
      description="Equipamentos, estações e itens liberados voltam ao estado de antes do lote. Se algum equipamento foi movimentado depois, nada é alterado."
      variant="danger"
      confirm-text="Desfazer"
      :loading="acoes.processando.value"
      @confirm="acoes.confirmarDesfazer"
      @cancel="acoes.cancelarDesfazer"
    />

    <ConfirmDialog
      :is-open="Boolean(paraDevolver)"
      title="Registrar devolução"
      message="Confirmar a devolução deste empréstimo?"
      variant="warning"
      confirm-text="Devolver"
      @confirm="devolver"
      @cancel="paraDevolver = null"
    />

    <EmprestimoFormModal
      :show="emprestimoAberto"
      :equipamentos="equipamentos"
      :usuarios="usuarios"
      :estacoes="estacoes"
      @close="emprestimoAberto = false"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ArchiveBoxIcon, ArrowsRightLeftIcon, PlusIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import RemanejamentosFiltersSection from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentosFiltersSection.vue';
import RemanejamentosLista from '@/Components/Organisms/Inventario/Remanejamentos/RemanejamentosLista.vue';
import EmprestimosAvulsosLista from '@/Components/Organisms/Inventario/Movimentacoes/EmprestimosAvulsosLista.vue';
import EmprestimoFormModal from '@/Components/Organisms/Inventario/Movimentacoes/EmprestimoFormModal.vue';
import { useRemanejamentoAcoes } from '@/Composables/inventario/useRemanejamentoAcoes';

defineProps({
  lotes: { type: Array, required: true },
  paginacao: { type: Object, required: true },
  avulsas: { type: Object, required: true },
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
  pode: { type: Object, required: true },
  equipamentos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  estacoes: { type: Array, default: () => [] },
});
defineEmits(['novo', 'aplicar', 'limpar', 'pagina', 'pagina-avulsas']);

const acoes = useRemanejamentoAcoes();
const emprestimoAberto = ref(false);
const paraDevolver = ref(null);

// Erros de dominio das acoes do lote e da devolucao chegam como erro de sessao.
const page = usePage();
const errosDaPagina = computed(() => ['remanejamento', 'configuracao', 'movimentacao']
  .map((chave) => page.props.errors?.[chave])
  .filter(Boolean));

function devolver() {
  router.post(route('inventario.movimentacoes.devolver', paraDevolver.value.id), {}, {
    preserveScroll: true,
    onFinish: () => { paraDevolver.value = null; },
  });
}
</script>
```

Substituir `resources/js/Pages/Inventario/MovimentacoesIndex.vue` por:

```vue
<template>
  <Head title="Movimentações de equipamentos" />
  <RemanejamentosIndexTemplate
    :lotes="remanejamentos.data"
    :paginacao="remanejamentos"
    :avulsas="movimentacoes"
    :filtros="filtros"
    :opcoes="opcoes"
    :pode="pode"
    :equipamentos="equipamentos"
    :usuarios="usuarios"
    :estacoes="estacoes"
    @novo="router.visit(route('inventario.remanejamentos.create'))"
    @aplicar="aplicar()"
    @limpar="limpar"
    @pagina="(p) => aplicar({ page: p })"
    @pagina-avulsas="(p) => aplicar({ pagina_avulsas: p })"
  />
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RemanejamentosIndexTemplate from '@/Templates/Inventario/RemanejamentosIndexTemplate.vue';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  remanejamentos: { type: Object, required: true },
  movimentacoes: { type: Object, required: true },
  filters: { type: [Object, Array], default: () => ({}) },
  opcoes: { type: Object, required: true },
  pode: { type: Object, required: true },
  equipamentos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  estacoes: { type: Array, default: () => [] },
});

const VAZIO = { search: '', status: '', data: '' };
// filters chega como [] (array PHP vazio) quando nao ha filtro.
const filtros = reactive({ ...VAZIO, ...(Array.isArray(props.filters) ? {} : props.filters) });

function aplicar(extra = {}) {
  const params = Object.fromEntries(
    Object.entries({ ...filtros, ...extra }).filter(([, v]) => v !== '' && v !== null && v !== undefined),
  );
  router.get(route('inventario.movimentacoes.index'), params, { preserveState: true, preserveScroll: true, replace: true });
}

function limpar() {
  Object.assign(filtros, VAZIO);
  aplicar();
}

useAtualizacaoAoVivo({
  canal: 'listagem.inventario-remanejamentos',
  evento: '.RecursoAtualizado',
  props: ['remanejamentos', 'movimentacoes'],
});
</script>
```

- [ ] **Step 5: Build em diretorio proprio**

```bash
npx vite build --outDir C:/tmp/vite-task13 --emptyOutDir 2>&1 | tail -15
```
Expected: build sem erro. Erro de import (nome de icone ou componente): corrigir o caminho conferindo com `ls resources/js/Components/...`; nao criar componente novo fora da lista de arquivos.

- [ ] **Step 6: Props batem com o servidor**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario/RemanejamentoHttpTest.php`
Expected: PASS (nenhuma mudanca de backend nesta task; confirma o contrato usado acima).

- [ ] **Step 7: Commit**

```bash
git add resources/js/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue resources/js/Composables/inventario/useRemanejamentoAcoes.js resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentosFiltersSection.vue resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentoAcoes.vue resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentoItensTabela.vue resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentosLista.vue resources/js/Components/Organisms/Inventario/Movimentacoes/EmprestimosAvulsosLista.vue resources/js/Components/Organisms/Inventario/Movimentacoes/EmprestimoFormModal.vue resources/js/Templates/Inventario/RemanejamentosIndexTemplate.vue resources/js/Pages/Inventario/MovimentacoesIndex.vue
git commit -m "🎨 style(inventario): movimentacoes listam os lotes de remanejamento" -- resources/js/Components/Atoms/Inventario/StatusRemanejamentoBadge.vue resources/js/Composables/inventario/useRemanejamentoAcoes.js resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentosFiltersSection.vue resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentoAcoes.vue resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentoItensTabela.vue resources/js/Components/Organisms/Inventario/Remanejamentos/RemanejamentosLista.vue resources/js/Components/Organisms/Inventario/Movimentacoes/EmprestimosAvulsosLista.vue resources/js/Components/Organisms/Inventario/Movimentacoes/EmprestimoFormModal.vue resources/js/Templates/Inventario/RemanejamentosIndexTemplate.vue resources/js/Pages/Inventario/MovimentacoesIndex.vue
```

---

### Task 14: Formulario de lote (novo e editar)

**Files:**
- Create: `resources/js/Composables/inventario/useRemanejamentoForm.js`
- Create: `resources/js/Components/Molecules/Inventario/EquipamentoBusca.vue`
- Create: `resources/js/Components/Organisms/Inventario/Remanejamentos/PessoaRemanejamentoBloco.vue`
- Create: `resources/js/Templates/Inventario/RemanejamentoFormTemplate.vue`
- Create: `resources/js/Pages/Inventario/RemanejamentoForm.vue`
- Test: `npx vite build` em diretorio proprio.

**Interfaces:**
- Consumes: props de `Inventario/RemanejamentoForm` (Task 11: `remanejamento`, `opcoes.{usuarios,estacoes,equipamentos}`); rotas `inventario.remanejamentos.{store,update}` e `inventario.movimentacoes.index`; erros por chave `pessoas.N.<campo>`, `pessoas`, `remanejamento`, `configuracao`, `observacao`.
- Produces: `useRemanejamentoForm(remanejamento, opcoes) -> { form, editando, equipamentosPorId, equipamentosDoUsuario(usuarioId), selecionarPessoa(bloco, usuarioId), atualizarCampo(bloco, campo, valor), adicionarPessoa(), removerPessoa(chave), alternarEquipamento(bloco, id), liberados(bloco), enviar() }`.

- [ ] **Step 1: Composable do formulario**

`resources/js/Composables/inventario/useRemanejamentoForm.js`:

```js
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

// Estado do formulario de lote. A "chave" de cada bloco so existe no cliente
// (v-for estavel ao remover); o transform tira antes de enviar.
export function useRemanejamentoForm(remanejamento, opcoes) {
  let sequencia = 0;
  const novoBloco = (dados = {}) => ({
    chave: ++sequencia,
    usuario_id: '',
    estacao_origem_id: '',
    estacao_destino_id: '',
    condicao_destino: '',
    equipamento_ids: [],
    ...dados,
  });

  const form = useForm({
    observacao: remanejamento?.observacao ?? '',
    pessoas: remanejamento
      ? remanejamento.pessoas.map((p) => novoBloco({
        ...p,
        estacao_origem_id: p.estacao_origem_id ?? '',
        estacao_destino_id: p.estacao_destino_id ?? '',
        condicao_destino: p.condicao_destino ?? '',
      }))
      : [novoBloco()],
  });

  const editando = computed(() => Boolean(remanejamento?.id));
  const equipamentosPorId = computed(() => new Map(opcoes.equipamentos.map((e) => [e.id, e])));

  const equipamentosDoUsuario = (usuarioId) => (usuarioId
    ? opcoes.equipamentos.filter((e) => e.user_id === Number(usuarioId))
    : []);
  const estacaoAtual = (usuarioId) => opcoes.estacoes.find((e) => e.user_id === Number(usuarioId)) ?? null;

  // Trocar a pessoa recarrega o bloco com o que ela tem hoje: equipamentos
  // pre-marcados (menos os bloqueados, que ficam com ela) e a estacao atual
  // como origem -- o servidor recusa origem que nao seja dela.
  function selecionarPessoa(bloco, usuarioId) {
    bloco.usuario_id = usuarioId;
    bloco.equipamento_ids = equipamentosDoUsuario(usuarioId).filter((e) => !e.bloqueio).map((e) => e.id);
    bloco.estacao_origem_id = estacaoAtual(usuarioId)?.value ?? '';
  }

  function atualizarCampo(bloco, campo, valor) {
    bloco[campo] = valor;
  }

  function adicionarPessoa() {
    form.pessoas.push(novoBloco());
  }

  function removerPessoa(chave) {
    form.pessoas = form.pessoas.filter((bloco) => bloco.chave !== chave);
  }

  function alternarEquipamento(bloco, id) {
    bloco.equipamento_ids = bloco.equipamento_ids.includes(id)
      ? bloco.equipamento_ids.filter((item) => item !== id)
      : [...bloco.equipamento_ids, id];
  }

  // Equipamentos da pessoa que ninguem do lote levou: o servidor libera esses.
  function liberados(bloco) {
    const levados = new Set(form.pessoas.flatMap((b) => b.equipamento_ids));
    return equipamentosDoUsuario(bloco.usuario_id).filter((e) => !e.bloqueio && !levados.has(e.id));
  }

  function enviar() {
    form.transform((dados) => ({
      observacao: dados.observacao,
      pessoas: dados.pessoas.map(({ chave, ...p }) => ({
        ...p,
        estacao_origem_id: p.estacao_origem_id || null,
        estacao_destino_id: p.estacao_destino_id || null,
        condicao_destino: p.condicao_destino || null,
      })),
    }));

    const opcoesEnvio = { preserveScroll: true };
    if (editando.value) {
      form.put(route('inventario.remanejamentos.update', remanejamento.id), opcoesEnvio);
    } else {
      form.post(route('inventario.remanejamentos.store'), opcoesEnvio);
    }
  }

  return {
    form, editando, equipamentosPorId, equipamentosDoUsuario, selecionarPessoa, atualizarCampo,
    adicionarPessoa, removerPessoa, alternarEquipamento, liberados, enviar,
  };
}
```

- [ ] **Step 2: Molecule de busca e organism do bloco**

`resources/js/Components/Molecules/Inventario/EquipamentoBusca.vue`:

```vue
<template>
  <div class="min-w-0">
    <label :for="campoId" class="text-xs font-medium text-slate-500 dark:text-slate-400">Adicionar outro equipamento</label>
    <input
      :id="campoId"
      v-model="termo"
      type="search"
      autocomplete="off"
      placeholder="Patrimônio ou nome (mínimo 2 letras)"
      class="mt-1 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-orange-500 focus:ring-orange-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
    />
    <ul
      v-if="resultados.length"
      class="mt-1 max-h-56 divide-y divide-slate-200 overflow-y-auto rounded-lg border border-slate-200 bg-white dark:divide-slate-700/50 dark:border-slate-700/50 dark:bg-slate-900"
    >
      <li v-for="equipamento in resultados" :key="equipamento.id">
        <button
          type="button"
          class="flex w-full min-w-0 items-center justify-between gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:text-slate-200 dark:hover:bg-slate-800"
          :disabled="Boolean(equipamento.bloqueio)"
          @click="adicionar(equipamento)"
        >
          <span class="min-w-0 truncate">{{ equipamento.patrimonio }} — {{ equipamento.nome }}</span>
          <span v-if="equipamento.bloqueio" class="shrink-0 text-xs text-amber-600 dark:text-amber-400">{{ equipamento.bloqueio }}</span>
        </button>
      </li>
    </ul>
    <p v-else-if="termo.trim().length >= 2" class="mt-1 text-xs text-slate-500 dark:text-slate-400">Nenhum equipamento encontrado.</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  equipamentos: { type: Array, required: true },
  jaListados: { type: Array, default: () => [] },
});
const emit = defineEmits(['adicionar']);

const campoId = `busca-equipamento-${Math.random().toString(36).slice(2, 8)}`;
const termo = ref('');

const resultados = computed(() => {
  const busca = termo.value.trim().toLowerCase();
  if (busca.length < 2) return [];
  return props.equipamentos
    .filter((e) => !props.jaListados.includes(e.id) && `${e.patrimonio} ${e.nome}`.toLowerCase().includes(busca))
    .slice(0, 20);
});

function adicionar(equipamento) {
  emit('adicionar', equipamento.id);
  termo.value = '';
}
</script>
```

`resources/js/Components/Organisms/Inventario/Remanejamentos/PessoaRemanejamentoBloco.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:border-slate-700/50 dark:bg-slate-900/60">
    <header class="flex min-w-0 items-center justify-between gap-3">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Pessoa {{ indice + 1 }}</h3>
      <ButtonIcon v-if="podeRemover" :icon="TrashIcon" variant="danger" size="sm" title="Remover pessoa do lote" @click="$emit('remover', bloco.chave)" />
    </header>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
      <FormSelect
        :model-value="bloco.usuario_id"
        label="Pessoa"
        required
        :options="opcoes.usuarios"
        :error="erro('usuario_id')"
        @update:model-value="(valor) => $emit('selecionar-pessoa', bloco, valor)"
      />
      <FormField
        :model-value="bloco.condicao_destino"
        label="Condição do destino"
        placeholder="VAZIA"
        maxlength="60"
        :error="erro('condicao_destino')"
        @update:model-value="(valor) => $emit('atualizar', bloco, 'condicao_destino', valor)"
      />
      <FormSelect
        :model-value="bloco.estacao_origem_id"
        label="Estação de origem"
        :options="opcoesEstacao"
        placeholder="Sem estação (estoque)"
        :error="erro('estacao_origem_id')"
        @update:model-value="(valor) => $emit('atualizar', bloco, 'estacao_origem_id', valor)"
      />
      <FormSelect
        :model-value="bloco.estacao_destino_id"
        label="Estação de destino"
        :options="opcoesEstacao"
        placeholder="Sem estação (estoque)"
        :error="erro('estacao_destino_id')"
        @update:model-value="(valor) => $emit('atualizar', bloco, 'estacao_destino_id', valor)"
      />
    </div>

    <div class="mt-5">
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Equipamentos que vão junto</p>
      <ul class="mt-2 space-y-2">
        <li
          v-for="equipamento in listaVisivel"
          :key="equipamento.id"
          class="flex min-w-0 items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700/50"
        >
          <input
            :id="`eq-${bloco.chave}-${equipamento.id}`"
            type="checkbox"
            class="h-4 w-4 shrink-0 rounded border-slate-300 text-orange-600 focus:ring-orange-500 dark:border-slate-600 dark:bg-slate-800"
            :checked="bloco.equipamento_ids.includes(equipamento.id)"
            :disabled="Boolean(equipamento.bloqueio)"
            @change="$emit('alternar-equipamento', bloco, equipamento.id)"
          />
          <label :for="`eq-${bloco.chave}-${equipamento.id}`" class="min-w-0 flex-1 text-sm text-slate-700 dark:text-slate-200">
            <span class="block truncate font-medium">{{ equipamento.nome }}</span>
            <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ equipamento.patrimonio }} · {{ equipamento.categoria ?? 'Sem categoria' }}</span>
          </label>
          <Badge v-if="equipamento.bloqueio" variant="warning" size="sm" class="shrink-0">{{ equipamento.bloqueio }}</Badge>
        </li>
        <li v-if="!listaVisivel.length" class="text-sm text-slate-500 dark:text-slate-400">
          Selecione a pessoa para ver os equipamentos dela.
        </li>
      </ul>
      <p v-if="erro('equipamento_ids')" class="mt-2 break-words text-sm text-red-600 dark:text-red-400">{{ erro('equipamento_ids') }}</p>
      <EquipamentoBusca
        class="mt-3"
        :equipamentos="opcoes.equipamentos"
        :ja-listados="idsVisiveis"
        @adicionar="(id) => $emit('alternar-equipamento', bloco, id)"
      />
    </div>

    <div
      v-if="liberados.length"
      role="status"
      class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
    >
      <p class="font-semibold">Serão liberados (ficam disponíveis, sem responsável):</p>
      <p class="mt-1 break-words">{{ liberados.map((e) => `${e.patrimonio} — ${e.nome}`).join(', ') }}</p>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { TrashIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import ButtonIcon from '@/Components/Atoms/Button/ButtonIcon.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import EquipamentoBusca from '@/Components/Molecules/Inventario/EquipamentoBusca.vue';

const props = defineProps({
  bloco: { type: Object, required: true },
  indice: { type: Number, required: true },
  opcoes: { type: Object, required: true },
  erros: { type: Object, default: () => ({}) },
  podeRemover: { type: Boolean, default: false },
  equipamentosDoUsuario: { type: Array, default: () => [] },
  equipamentosPorId: { type: Map, required: true },
  liberados: { type: Array, default: () => [] },
});
defineEmits(['selecionar-pessoa', 'atualizar', 'alternar-equipamento', 'remover']);

const erro = (campo) => props.erros[`pessoas.${props.indice}.${campo}`] ?? '';

const opcoesEstacao = computed(() => props.opcoes.estacoes.map((e) => ({
  value: e.value,
  label: e.ocupante ? `${e.label} (${e.ocupante})` : e.label,
})));

// Os da pessoa + os que vieram de outra pessoa pela busca.
const listaVisivel = computed(() => {
  const proprios = props.equipamentosDoUsuario;
  const extras = props.bloco.equipamento_ids
    .filter((id) => !proprios.some((e) => e.id === id))
    .map((id) => props.equipamentosPorId.get(id))
    .filter(Boolean);
  return [...proprios, ...extras];
});
const idsVisiveis = computed(() => listaVisivel.value.map((e) => e.id));
</script>
```

- [ ] **Step 3: Template e Page**

`resources/js/Templates/Inventario/RemanejamentoFormTemplate.vue`:

```vue
<template>
  <div class="space-y-6 pb-8">
    <PageHeader
      :title="editando ? 'Editar remanejamento' : 'Novo remanejamento'"
      description="Pessoas que mudam de estação levando seus equipamentos"
      :icon="ArrowsRightLeftIcon"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <Button variant="secondary" size="md" :icon="ArrowLeftIcon" icon-position="left" @click="$emit('voltar')">Voltar</Button>
      </template>
    </PageHeader>

    <div
      v-if="erroGeral"
      role="alert"
      class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
    >
      <p class="break-words">{{ erroGeral }}</p>
    </div>

    <form class="space-y-6" novalidate @submit.prevent="enviar">
      <PessoaRemanejamentoBloco
        v-for="(bloco, indice) in form.pessoas"
        :key="bloco.chave"
        :bloco="bloco"
        :indice="indice"
        :opcoes="opcoes"
        :erros="form.errors"
        :pode-remover="form.pessoas.length > 1"
        :equipamentos-do-usuario="equipamentosDoUsuario(bloco.usuario_id)"
        :equipamentos-por-id="equipamentosPorId"
        :liberados="liberados(bloco)"
        @selecionar-pessoa="selecionarPessoa"
        @atualizar="atualizarCampo"
        @alternar-equipamento="alternarEquipamento"
        @remover="removerPessoa"
      />

      <div class="flex flex-wrap gap-2">
        <Button type="button" variant="secondary" size="md" :icon="PlusIcon" icon-position="left" @click="adicionarPessoa">
          Adicionar pessoa
        </Button>
      </div>

      <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:border-slate-700/50 dark:bg-slate-900/60">
        <FormTextarea v-model="form.observacao" label="Observação" :rows="3" :error="form.errors.observacao" />
      </section>

      <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-700/50">
        <Button type="button" variant="outline" @click="$emit('voltar')">Cancelar</Button>
        <Button type="submit" variant="primary" :loading="form.processing">
          {{ editando ? 'Salvar alterações' : 'Registrar remanejamento' }}
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ArrowLeftIcon, ArrowsRightLeftIcon, PlusIcon } from '@heroicons/vue/24/outline';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PessoaRemanejamentoBloco from '@/Components/Organisms/Inventario/Remanejamentos/PessoaRemanejamentoBloco.vue';
import { useRemanejamentoForm } from '@/Composables/inventario/useRemanejamentoForm';

const props = defineProps({
  remanejamento: { type: Object, default: null },
  opcoes: { type: Object, required: true },
});
defineEmits(['voltar']);

const {
  form, editando, equipamentosPorId, equipamentosDoUsuario, selecionarPessoa, atualizarCampo,
  adicionarPessoa, removerPessoa, alternarEquipamento, liberados, enviar,
} = useRemanejamentoForm(props.remanejamento, props.opcoes);

// Erros que nao pertencem a um bloco: lote vazio, lote ja desfeito, item
// movimentado depois (editar) e configuracao.
const erroGeral = computed(() => form.errors.pessoas || form.errors.remanejamento || form.errors.configuracao || '');
</script>
```

`resources/js/Pages/Inventario/RemanejamentoForm.vue`:

```vue
<template>
  <Head :title="remanejamento ? 'Editar remanejamento' : 'Novo remanejamento'" />
  <RemanejamentoFormTemplate
    :remanejamento="remanejamento"
    :opcoes="opcoes"
    @voltar="router.visit(route('inventario.movimentacoes.index'))"
  />
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RemanejamentoFormTemplate from '@/Templates/Inventario/RemanejamentoFormTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  remanejamento: { type: Object, default: null },
  opcoes: { type: Object, required: true },
});
</script>
```

- [ ] **Step 4: Build em diretorio proprio**

```bash
npx vite build --outDir C:/tmp/vite-task14 --emptyOutDir 2>&1 | tail -15
```
Expected: build sem erro.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Composables/inventario/useRemanejamentoForm.js resources/js/Components/Molecules/Inventario/EquipamentoBusca.vue resources/js/Components/Organisms/Inventario/Remanejamentos/PessoaRemanejamentoBloco.vue resources/js/Templates/Inventario/RemanejamentoFormTemplate.vue resources/js/Pages/Inventario/RemanejamentoForm.vue
git commit -m "✨ feat(inventario): formulario de remanejamento em lote" -- resources/js/Composables/inventario/useRemanejamentoForm.js resources/js/Components/Molecules/Inventario/EquipamentoBusca.vue resources/js/Components/Organisms/Inventario/Remanejamentos/PessoaRemanejamentoBloco.vue resources/js/Templates/Inventario/RemanejamentoFormTemplate.vue resources/js/Pages/Inventario/RemanejamentoForm.vue
```

---

### Task 15: Fechamento da Fase 3

- [ ] **Step 1: Build unico da worktree e suite**

```bash
npx vite build 2>&1 | tail -5
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario
rm -rf /c/tmp/vite-task13 /c/tmp/vite-task14
```
Expected: build em `public/build` sem erro (agora sequencial, depois das duas tasks); suite verde.

- [ ] **Step 2: Sem resto de depuracao e sem entrada nova na sidebar**

```bash
grep -rn "console.log\|debugger" resources/js/Pages/Inventario resources/js/Templates/Inventario resources/js/Components/Organisms/Inventario resources/js/Components/Molecules/Inventario resources/js/Components/Atoms/Inventario resources/js/Composables/inventario
git diff dev --stat -- resources/js/Components/Sidebar.vue
```
Expected: nenhuma ocorrencia; Sidebar sem diff (Movimentações continua em Administração > Inventário).

- [ ] **Step 3: Entrega**

O controlador mergeia a branch em `dev` por uma worktree temporaria e refaz o deploy de homolog (build de frontend incluido). A partir daqui a tela de Movimentações mostra os lotes.

---

## FASE 4 — Verificacao

Entrega: evidencia de que tudo funciona junto — suites, permissoes, telas em 375/840 px claro/escuro e o fluxo ponta a ponta. Sem codigo novo, salvo correcoes (cada uma vira commit `🐛 fix(inventario): ...`, com o teste que a pegou).

### Task 16: Verificacao final — suites, permissoes, telas e fluxo

**Files:** nenhum versionado. Scripts temporarios no scratchpad da sessao (`$SCRATCH`, o diretorio de scratchpad informado no prompt do agente).

- [ ] **Step 1: Suites completas**

```bash
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario tests/Feature/Demandas tests/Unit/Demandas tests/Unit/Ranking
```
Expected: tudo PASS (SKIPPED aceitavel so onde ja era antes).

- [ ] **Step 2: Auditoria de permissoes e rotas**

```bash
bash /c/tmp/teste-demandas.sh php artisan route:list --path=inventario
bash /c/tmp/teste-demandas.sh php artisan permissions:audit
grep -rn "can('inventario\.\|can:inventario\." app/Modules/Inventario routes/modules/inventario.php | grep -o "inventario\.[a-z.]*" | sort -u
```
Expected: cada rota de `inventario.remanejamentos.*` com o `can:` da tabela da spec secao 5; todo slug usado no codigo existe em `config/permissions.php`; o audit nao aponta slug de inventario usado no backend e ausente do config.

- [ ] **Step 3: Dados de conferencia visual em `sdc_test`**

Nomes longos de proposito, para estressar o overflow. Tudo com prefixo `TST-VIS-` (limpo no Step 7).

```bash
CODE=$(cat <<'PHP'
use App\Models\User;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Inventario\DTOs\RemanejamentoData;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Estacao;
use App\Modules\Inventario\Services\RemanejamentoService;
use Spatie\Permission\Models\Permission;
$slugs = ['inventario.equipamentos.view','inventario.emprestimos.view','inventario.emprestimos.create','inventario.emprestimos.return','inventario.emprestimos.export','inventario.remanejamentos.create','inventario.remanejamentos.edit','inventario.remanejamentos.seplag','demandas.chamados.view'];
foreach ($slugs as $s) { Permission::firstOrCreate(['name' => $s, 'guard_name' => 'web']); }
$op = User::where('cpf', '52998224725')->first() ?? User::factory()->create(['cpf' => '52998224725', 'name' => 'TST-VIS Operador', 'email' => 'tst-vis-op@teste.gov.br', 'password' => bcrypt('Tst@Visual123')]);
$op->givePermissionTo($slugs);
$p = fn ($n) => User::factory()->create(['name' => "TST-VIS {$n} da Silva Albuquerque Figueiredo Junior"]);
[$a, $b] = [$p('Ana'), $p('Bia')];
$ea = Estacao::create(['nome' => 'TST-VIS Estação 3º andar ala norte sala 301 mesa 12', 'ponto_rede' => 'PR-3N-301-12', 'user_id' => $a->id]);
$eb = Estacao::create(['nome' => 'TST-VIS Estação 3º andar ala sul sala 315 mesa 04', 'ponto_rede' => 'PR-3S-315-04', 'user_id' => $b->id]);
$eq = fn ($u, $e, $n) => Equipamento::create(['nome' => "TST-VIS {$n} Dell OptiPlex 7090 Micro com monitor P2422H", 'patrimonio' => 'TST-VIS-'.uniqid(), 'user_id' => $u->id, 'estacao_id' => $e->id, 'situacao' => 'em_uso', 'quantidade' => 1]);
$eq($a, $ea, 'Desktop'); $eq($a, $ea, 'Notebook'); $eq($b, $eb, 'Desktop');
$ids = fn ($u) => Equipamento::where('user_id', $u->id)->pluck('id')->all();
$lote = app(RemanejamentoService::class)->registrar(RemanejamentoData::fromArray(['observacao' => 'TST-VIS troca de mesas', 'pessoas' => [
  ['usuario_id' => $a->id, 'estacao_origem_id' => $ea->id, 'estacao_destino_id' => $eb->id, 'equipamento_ids' => array_slice($ids($a), 0, 1)],
  ['usuario_id' => $b->id, 'estacao_origem_id' => $eb->id, 'estacao_destino_id' => $ea->id, 'equipamento_ids' => $ids($b)],
]], $op->id));
DemandaAssunto::firstOrCreate(['nome' => 'TST-VIS REMANEJAMENTO'], ['campos_dinamicos' => [], 'ativo' => true]);
echo "LOTE={$lote->id}\n";
PHP
)
bash /c/tmp/teste-demandas.sh php artisan tinker --execute="$CODE"
```
Expected: imprime `LOTE=<uuid>`; anotar o uuid. Se a fabrica de User exigir outro campo obrigatorio, acrescentar ao array do `create` e repetir.

- [ ] **Step 4: Subir a app da worktree**

```bash
WT=/c/Users/x24679188/Documents/Github/NewSDC/.worktrees/demandas-paridade-chamados/SDC
MSYS_NO_PATHCONV=1 docker run -d --name inventario-preview --network newsdc-dev_default -p 8095:8000 -v "$WT:/app" -w /app \
  -e APP_KEY="$(grep '^APP_KEY=' $WT/.env | cut -d= -f2-)" -e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 \
  -e DB_DATABASE=sdc_test -e DB_USERNAME=sdc -e DB_PASSWORD=secret -e APP_URL=http://localhost:8095 \
  -e QUEUE_CONNECTION=sync -e BROADCAST_CONNECTION=null -e MAIL_MAILER=log \
  -e INVENTARIO_ASSUNTO_CHAMADO_LOTE="TST-VIS REMANEJAMENTO" -e INVENTARIO_SEPLAG_DESTINATARIOS=tst-vis-seplag@teste.gov.br \
  newsdc-swoole-dev:latest php artisan serve --host=0.0.0.0 --port=8000
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8095/login
```
Expected: `200`. `public/build` e o da Task 15.

- [ ] **Step 5: Overflow em 375/840 px, claro e escuro (Playwright do projeto)**

O MCP de Playwright pode estar indisponivel; usar o `@playwright/test` ja instalado em `SDC/node_modules`. Escrever `$SCRATCH/overflow-inventario.cjs`:

```js
const { chromium } = require('C:/Users/x24679188/Documents/Github/NewSDC/.worktrees/demandas-paridade-chamados/SDC/node_modules/@playwright/test');

const BASE = 'http://localhost:8095';
const [lote, saida] = process.argv.slice(2);
const CAMINHOS = {
  lista: '/inventario/movimentacoes',
  novo: '/inventario/remanejamentos/novo',
  editar: `/inventario/remanejamentos/${lote}/editar`,
};

(async () => {
  const browser = await chromium.launch();
  const resultados = [];
  for (const largura of [375, 840]) {
    const page = await browser.newPage({ viewport: { width: largura, height: 900 } });
    await page.goto(`${BASE}/login`);
    await page.locator('#cpf').pressSequentially('52998224725');
    await page.locator('#password').fill('Tst@Visual123');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL((url) => !url.pathname.startsWith('/login'), { timeout: 20000 });

    for (const [nome, caminho] of Object.entries(CAMINHOS)) {
      for (const tema of ['claro', 'escuro']) {
        await page.goto(BASE + caminho);
        await page.waitForLoadState('networkidle');
        await page.evaluate((escuro) => document.documentElement.classList.toggle('dark', escuro), tema === 'escuro');
        if (nome === 'lista') {
          const expandir = page.locator('[title="Ver itens do lote"]').first();
          if (await expandir.count()) await expandir.click();
        }
        const cabe = await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth);
        await page.screenshot({ path: `${saida}/inv-${nome}-${largura}-${tema}.png`, fullPage: true });
        resultados.push({ nome, largura, tema, cabe });
      }
    }
    await page.close();
  }
  await browser.close();
  console.log(JSON.stringify(resultados, null, 2));
  process.exit(resultados.every((r) => r.cabe) ? 0 : 1);
})();
```

```bash
node "$SCRATCH/overflow-inventario.cjs" <uuid-do-Step-3> "$SCRATCH"
```
Expected: 12 linhas com `"cabe": true` e exit 0. Abrir as capturas `inv-*.png` com a ferramenta Read e conferir: lista com linha do lote (data/hora, nomes curtos, badge "N itens movidos", status, acoes) e a tabela expandida (ID / Equipamento / Patrimônio / Usuário destino / Estação destino / Status) em 840 px, blocos em 375 px; formulario com bloco por pessoa e aviso "Serão liberados" no editar (o Notebook da Ana ficou para tras); modo escuro legivel. Qualquer `false`: corrigir com `min-w-0`/`truncate`/`break-words` no componente responsavel, rebuild (`npx vite build`) e repetir.

- [ ] **Step 6: Fluxo ponta a ponta na app de preview**

Logado como o operador do Step 3 (CPF `529.982.247-25`, senha `Tst@Visual123`), em `http://localhost:8095/inventario/movimentacoes`:
1. Novo remanejamento: duas pessoas trocando de mesa, cada uma levando um equipamento -> volta para a listagem com o lote novo.
2. Expandir o lote; Baixar planilha (abre no Excel/LibreOffice com as 10 colunas e um item por equipamento).
3. Editar o lote, trocar o destino de uma pessoa, salvar -> mesmo lote, destino novo.
4. Registrar chamado -> botao vira "Chamado #id" e abre a demanda resolvida em `/demandas/{id}`.
5. Enviar a SEPLAG duas vezes -> tooltip mostra "2 envio(s)"; `docker logs inventario-preview 2>&1 | grep -c "Desbloqueio de pontos de rede"` >= 1 (MAIL_MAILER=log).
6. Desfazer o lote do Step 3 depois de ter criado outro lote com o mesmo equipamento -> aviso vermelho citando o patrimonio e o lote posterior; nada muda.
7. Desfazer o lote mais novo -> volta tudo; depois desfazer o antigo -> funciona.
8. Novo empréstimo e Devolver na secao Empréstimos continuam funcionando.

- [ ] **Step 7: Limpeza**

```bash
docker rm -f inventario-preview
CODE=$(cat <<'PHP'
use App\Models\User;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Inventario\Models\Equipamento;
use App\Modules\Inventario\Models\Estacao;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
$usuarios = User::where('name', 'like', 'TST-VIS%')->pluck('id');
$lotes = Remanejamento::whereIn('registrado_por_id', $usuarios)->pluck('id');
$equipamentos = Equipamento::withTrashed()->where('patrimonio', 'like', 'TST-VIS-%')->pluck('id');
Movimentacao::whereIn('lote_id', $lotes)->orWhereIn('equipamento_id', $equipamentos)->delete();
Remanejamento::whereIn('id', $lotes)->delete();
Equipamento::withTrashed()->whereIn('id', $equipamentos)->forceDelete();
Estacao::where('nome', 'like', 'TST-VIS%')->delete();
App\Modules\Demandas\Models\Demanda::withTrashed()->where('titulo', 'like', 'Remanejamento de %')->whereIn('solicitante_id', $usuarios)->forceDelete();
DemandaAssunto::where('nome', 'TST-VIS REMANEJAMENTO')->delete();
User::withTrashed()->whereIn('id', $usuarios)->forceDelete();
echo "limpo\n";
PHP
)
bash /c/tmp/teste-demandas.sh php artisan tinker --execute="$CODE"
rm -f "$SCRATCH"/overflow-inventario.cjs "$SCRATCH"/inv-*.png
git status --short
grep -rn "dd(\|dump(\|Log::debug\|console.log(\|debugger" app/Modules/Inventario resources/js/Pages/Inventario resources/js/Templates/Inventario resources/js/Components/Organisms/Inventario resources/js/Composables/inventario
```
Expected: `limpo` (se alguma FK de historico de Demandas impedir o `forceDelete`, deixar a demanda e o usuario: e `sdc_test`); nenhum arquivo fora do escopo modificado; nenhum log de depuracao; nada de `tests/` staged.

---

### Task 17: Fechamento da Fase 4

- [ ] **Step 1: Suite final depois das correcoes da Task 16**

```bash
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Inventario tests/Feature/Demandas tests/Unit/Demandas
npx vite build 2>&1 | tail -3
git log --oneline dev..HEAD
```
Expected: tudo PASS; build ok; um commit por task (mais os `🐛 fix` da verificacao, se houver).

- [ ] **Step 2: Entrega**

O controlador mergeia a branch em `dev` por uma worktree temporaria e refaz o deploy de homolog. Relatorio ao usuario: commits da branch, resultado das suites, tabela de overflow (12 combinacoes), pendencias (assunto `REMANEJAMENTO` a criar em Demandas de homolog/producao se nao existir; destinatarios reais da SEPLAG no `.env`; fatias 8–15 da auditoria ficam para as proximas specs).

---

## Self-review

- **Cobertura da spec:** sec. 1 criterios 1-3 -> Tasks 2-4 e 11/13; criterio 4 -> Tasks 4, 7, 8, 10, 11; criterio 5 -> Global Constraints + Tasks 8/10 (config, Mailable, sem IP/e-mail fixo). D1 -> Task 1; D2 -> Task 2; D3 -> Task 3; D4 -> Tasks 2 e 9; D5 -> Task 10; D6 -> Task 8; D7 -> Task 7. Sec. 3.1-3.3 -> Task 1 (principal + `2026_09_28_100000`); 3.4 -> Tasks 7 (openspout) e 8 (config). Sec. 4.1 -> Task 2; 4.2 -> Task 3; 4.3 -> Task 4 (+ `demanda_id` preservado na Task 8); 4.4 -> Task 8; 4.5 -> Tasks 7 e 10. Sec. 5 -> Tasks 6 (slugs/papeis), 11 (rotas, FormRequest, `whereUuid`), 9 (devolver recusa lote). Sec. 6 -> Tasks 13 (lista, expandir, acoes, filtros, paginacao, tempo real) e 14 (formulario, aviso de liberados, erros por campo); canal -> Task 6. Sec. 7 -> Tasks 2/3/4 (422 por item), 8/10 (config ausente, lote desfeito), 10 (registro so apos enfileirar). Sec. 8 -> um arquivo de teste por task; overflow -> Task 16. Sec. 9 respeitada (nenhuma tarefa de dashboard, categorias, historico, compartilhados, etiquetas, Acessos ou importacao).
- **Review Focus:** 1 -> Task 2 `test_nao_libera_emprestado_nem_em_manutencao`; 2 -> Task 4 `test_desfazer_em_ordem_inversa_funciona`; 3 -> Task 3 `test_liberado_movimentado_depois_bloqueia_desfazer`; 4 -> Task 11 `test_id_que_nao_e_uuid_da_404`; 5 -> Task 7 `test_texto_com_formula_sai_neutralizado`.
- **Nomes cruzados conferidos:** `RemanejamentoService::{registrar,desfazer,editar}`, `RemanejamentoData::fromArray`, `PessoaRemanejadaData::fromArray`, `RemanejamentoProibido::{comErros,loteDesfeito,configuracao}`, `Remanejamento::{pessoas,movimentacoes,itensRemanejados,estaAtivo}`, `PlanilhaRemanejamento::{MIME,CABECALHO,linhas,gerar,nomeArquivo}`, `RegistrarChamadoDoLote::executar`, `EnviarRemanejamentoSeplag::executar`, `RemanejamentoSeplagMail($remanejamentoId)`, `RemanejamentoTempoRealObserver::RECURSO` = `inventario-remanejamentos` = canal `listagem.inventario-remanejamentos`, rotas `inventario.remanejamentos.{create,store,edit,update,desfazer,planilha,chamado,seplag}`, props `remanejamentos|movimentacoes|filters|opcoes|pode` e `remanejamento|opcoes`, composables `useRemanejamentoAcoes`/`useRemanejamentoForm`.
- **Decisoes que a spec deixou abertas (resolvidas aqui):** origem informada tem de ser a estacao atual da pessoa (Task 2); emprestado/manutencao nao sao liberados (Task 2); "mais recente" ignora movimentacoes `devolvido` e liberado movimentado depois bloqueia o desfazer (Task 3); editar apaga as linhas antigas do lote depois de revertidas (Task 4); liberacao nao conta como movimentacao ativa no emprestimo avulso (Task 9); `RemanejamentoProibido` estende `ValidationException` e junta mensagens por chave; a tela mantem a secao de Empréstimos avulsos (Task 13); tipo de dispositivo = categoria, com o nome do equipamento como fallback (Task 7); `estacao_origem_ficou_vazia` = true quando nao ha origem (planilha imprime SIM, como o legado).
