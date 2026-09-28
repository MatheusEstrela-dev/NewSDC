# Demandas — paridade com Chamados do cedec-demanda — Plano de implementacao

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fechar a paridade do modulo `Demandas` do NewSDC com o modulo Chamados do `cedec-demanda` (fluxos, telas, dashboard, automacao AD, SLA, Ranking) e importar os chamados legados de forma idempotente.

**Architecture:** Evolui o modulo existente `SDC/app/Modules/Demandas` (DDD: FormRequest -> Controller fino -> DTO -> Service/Workflow -> Repository). O dominio continua ITIL (7 status); a UI exibe a projecao `EtapaDemanda` (Aberto / Em andamento / Concluido / Cancelado). Transicoes de varios passos passam por `DemandaWorkflow::conduzirAte`. Historico em `task_audit_logs` via `HistoricoDemanda`. Frontend em Atomic Design: Page -> Template -> Organisms -> Molecules/Atoms.

**Tech Stack:** PHP 8.3+/8.4, Laravel 12, PostgreSQL, Spatie Permission, Inertia + Vue 3, Tailwind, ApexCharts (`LazyChart`), PHPUnit com `DatabaseTransactions`.

**Spec:** `docs/superpowers/specs/2026-09-24-demandas-paridade-chamados-design.md`

## Global Constraints

- Branch/worktree: `feat/demandas-paridade-chamados` em `NewSDC/.worktrees/demandas-paridade-chamados`. Todo caminho abaixo e relativo a `SDC/` salvo indicacao.
- Todo arquivo PHP novo comeca com `declare(strict_types=1);`; dependencias injetadas como `private readonly`.
- Sem emojis no codigo. Comentarios em pt-BR sem acento, no estilo do modulo.
- Commits: gitmoji + `tipo(escopo): descricao` em pt-BR, SEM trailer `Co-Authored-By`. Um commit por task (cada task e uma unidade completa). Nunca `git add` em `SDC/tests/` (gitignored, regra 10).
- Migrations: ajustes consolidados nas migrations principais; bancos ja migrados recebem `2026_09_24_100000_ajusta_demandas_paridade_chamados.php` idempotente. Nunca `migrate:fresh` em banco com dados.
- Testes rodam contra o banco `sdc_test` (nunca `sdc` nem `sdc_medalhao`), com `DatabaseTransactions`. Dados de teste usam prefixo `TST-` ou ano 2099 quando precisarem de unicidade.
- Logs de depuracao criados durante o trabalho sao removidos antes do commit (regra 6).
- Rotas existentes mantem o nome (`demandas.comments.store`, `demandas.attachments.*`, `admin.demandas.*`).
- Anexos aceitos: `png, jpg, jpeg, pdf, xlsx, xls, csv, txt`, ate 10 MB (10240 KB).
- Protecao de importacao: com `ContextoImportacao` ativo nao ha notificacao, SLA, evento de Ranking nem broadcast.

## Review Focus

1. Demanda `aberta` sem responsavel recebendo "Resolver": o sistema deve atribuir quem resolveu e percorrer aberta -> em_analise -> em_progresso -> resolvida numa unica transacao; se qualquer passo falhar, nada muda (teste em Task 5).
2. Comentario do PROPRIO solicitante numa demanda aberta: nao pode mover o status nem atribuir ninguem (teste em Task 6).
3. Assunto trocado por autosave para um assunto com campos dinamicos obrigatorios diferentes: campos antigos devem ser descartados e os novos validados, sem 500 (teste em Task 4).
4. Importacao executada duas vezes, com um chamado legado alterado entre as execucoes: o alterado e atualizado, os demais ficam intocados, nada duplica (teste em Task 13).
5. Solicitante tentando baixar anexo ou ver historico de demanda alheia por URL direta: 403 (teste em Task 8).

---

## Mapa de arquivos

**Criar**
- `app/Modules/Demandas/Enums/EtapaDemanda.php` — projecao de status para UI.
- `app/Modules/Demandas/Enums/PrioridadeSimples.php` — baixa/media/alta <-> impacto+urgencia.
- `app/Modules/Demandas/Enums/AcaoHistoricoDemanda.php` — acoes gravadas em `task_audit_logs.acao` e rotulos do legado.
- `app/Modules/Demandas/Domain/Exceptions/TransicaoProibidaException.php`
- `app/Modules/Demandas/Domain/Services/ValidadorCamposDinamicos.php`
- `app/Modules/Demandas/Support/ContextoImportacao.php`
- `app/Modules/Demandas/Services/HistoricoDemanda.php`
- `app/Modules/Demandas/Services/DemandaWriteService.php`
- `app/Modules/Demandas/Services/DemandaStatusService.php`
- `app/Modules/Demandas/Services/DemandaAnexoService.php`
- `app/Modules/Demandas/Services/ExecutarAutomacaoDemanda.php`
- `app/Modules/Demandas/Jobs/ExecutarAutomacaoDemandaJob.php`
- `app/Modules/Demandas/Queries/DemandaDashboardQuery.php`
- `app/Modules/Demandas/DTOs/ResolucaoDemandaData.php`
- `app/Modules/Demandas/Requests/{ResolverDemandaRequest,AnexoDemandaRequest,SalvarAssuntoRequest,SalvarCategoriaRequest}.php`
- `app/Modules/Demandas/Controllers/DemandaDashboardController.php`
- `app/Modules/Demandas/Observers/DemandaTempoRealObserver.php`
- `app/Modules/Demandas/Importacao/{EtapaImportacao,RelatorioEtapa,MapaImportacao,MapaLegado,ResolvedorUsuarioLegado}.php`
- `app/Modules/Demandas/Importacao/Etapas/{ImportarUsuarios,ImportarCatalogo,ImportarChamados,ImportarHistorico,ImportarComentarios,ImportarAnexos}.php`
- `app/Modules/Demandas/Console/ImportarLegadoCommand.php`
- `app/Modules/Ranking/Adapters/DemandaAdapter.php`
- `config/demandas.php`
- `database/migrations/2026_09_24_100000_ajusta_demandas_paridade_chamados.php`
- Frontend: ver Tasks 14–18.

**Modificar**
- `app/Modules/Demandas/Enums/StatusDemanda.php` (`etapa()`)
- `app/Modules/Demandas/Domain/Workflows/DemandaWorkflow.php` (momento, `conduzirAte`, `caminho`)
- `app/Modules/Demandas/Services/DemandaInteractionService.php`
- `app/Modules/Demandas/Models/{Demanda,DemandaAssunto}.php`
- `app/Modules/Demandas/DTOs/{CriarDemandaData,AtualizarDemandaData,FiltroDemanda}.php`
- `app/Modules/Demandas/Requests/{StoreDemandaRequest,UpdateDemandaRequest}.php`
- `app/Modules/Demandas/Domain/Contracts/DemandaRepository.php`, `Infrastructure/Persistence/EloquentDemandaRepository.php`
- `app/Modules/Demandas/Controllers/{DemandaController,CatalogoDemandaController}.php`
- `app/Modules/Demandas/Observers/DemandaNotificacaoObserver.php`
- `app/Modules/Demandas/DemandasServiceProvider.php`
- `app/Policies/DemandaPolicy.php`
- `app/Modules/Acessos/Contracts/DiretorioCorporativo.php`, `app/Modules/Acessos/Infrastructure/HttpDiretorioCorporativo.php`
- `app/Modules/Ranking/RankingServiceProvider.php`
- `app/Modules/Shared/Support/CanaisDeListagem.php`
- `config/permissions.php`, `config/database.php`, `config/filesystems.php`
- `routes/modules/demandas.php`, `routes/console.php`
- `database/migrations/2025_01_15_000001_create_tasks_table.php`, `2025_01_15_000007_create_task_audit_logs_table.php`, `2026_09_23_000004_create_demanda_assuntos.php`
- `database/seeders/DemandasPermissionsSeeder.php`

**Remover**
- `database/migrations/2026_09_23_100000_create_demanda_categorias_table.php` (conteudo vai para `2026_09_23_000004`)
- `resources/js/domain/demandas/`, `resources/js/infrastructure/demandas/`

---

### Task 0: Preparar a worktree para testes e build

Sem commit. Deixa o ambiente pronto e registra a baseline.

**Files:** nenhum versionado.

- [ ] **Step 1: Copiar `.env`, `public/build` e criar diretorios de runtime**

```bash
WT=/c/Users/x24679188/Documents/Github/NewSDC/.worktrees/demandas-paridade-chamados/SDC
MAIN=/c/Users/x24679188/Documents/Github/NewSDC/SDC
cp "$MAIN/.env" "$WT/.env"
mkdir -p "$WT/bootstrap/cache" "$WT/storage/framework/cache" "$WT/storage/framework/views" "$WT/storage/framework/testing" "$WT/storage/framework/sessions" "$WT/storage/logs"
cp -r "$MAIN/public/build" "$WT/public/build"
```

- [ ] **Step 2: Instalar vendor pelo container (nunca junction)**

```bash
docker run --rm -v "$WT:/app" -w /app -e COMPOSER_ALLOW_SUPERUSER=1 -e COMPOSER_PROCESS_TIMEOUT=0 \
  newsdc-swoole-dev:latest composer install --ignore-platform-reqs > /c/tmp/composer-demandas.log 2>&1
ls "$WT/vendor/bin/phpunit"
```
Expected: o arquivo existe. Se nao existir, ler `/c/tmp/composer-demandas.log` e repetir.

- [ ] **Step 3: Definir o comando de teste (usado em todas as tasks)**

Salvar em `/c/tmp/teste-demandas.sh` (fora do repo):

```bash
#!/usr/bin/env bash
WT=/c/Users/x24679188/Documents/Github/NewSDC/.worktrees/demandas-paridade-chamados/SDC
APP_KEY=$(grep '^APP_KEY=' "$WT/.env" | cut -d= -f2-)
MSYS_NO_PATHCONV=1 docker run --rm --network newsdc-dev_default -v "$WT:/app" -w /app \
  -e APP_ENV=testing -e APP_KEY="$APP_KEY" -e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 \
  -e DB_DATABASE=sdc_test -e DB_USERNAME=sdc -e DB_PASSWORD=secret -e QUEUE_CONNECTION=sync \
  -e BROADCAST_CONNECTION=null -e RANKING_HABILITADO=false \
  newsdc-swoole-dev:latest "$@"
```

Uso: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas`.

- [ ] **Step 4: Colocar `sdc_test` no schema atual e medir baseline**

```bash
bash /c/tmp/teste-demandas.sh php artisan migrate --force
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Unit/Ranking
```
Expected: migrate termina sem erro; suite Ranking passa. Anotar o resultado como baseline.

---

### Task 1: Schema — criador, automacao do assunto, acoes do historico e ordem do catalogo

**Files:**
- Modify: `database/migrations/2025_01_15_000001_create_tasks_table.php`
- Modify: `database/migrations/2025_01_15_000007_create_task_audit_logs_table.php`
- Modify: `database/migrations/2026_09_23_000004_create_demanda_assuntos.php`
- Delete: `database/migrations/2026_09_23_100000_create_demanda_categorias_table.php`
- Create: `database/migrations/2026_09_24_100000_ajusta_demandas_paridade_chamados.php`
- Modify: `app/Modules/Demandas/Models/Demanda.php`, `app/Modules/Demandas/Models/DemandaAssunto.php`
- Test: `tests/Feature/Demandas/SchemaDemandasTest.php`

**Interfaces:**
- Produces: coluna `tasks.criado_por_id` (bigint nullable FK users), relacao `Demanda::criadoPor(): BelongsTo`; coluna `demanda_assuntos.form_automacao` (jsonb, cast array); `task_audit_logs.acao` como `varchar(40)` sem check constraint.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaDemandasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tasks_tem_criado_por(): void
    {
        $this->assertTrue(Schema::hasColumn('tasks', 'criado_por_id'));
    }

    public function test_assunto_tem_form_automacao(): void
    {
        $this->assertTrue(Schema::hasColumn('demanda_assuntos', 'form_automacao'));
    }

    public function test_acao_do_historico_aceita_valores_novos(): void
    {
        $constraint = DB::selectOne(
            "select count(*) as n from pg_constraint where conname = 'task_audit_logs_acao_check'"
        );
        $this->assertSame(0, (int) $constraint->n);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/SchemaDemandasTest.php`
Expected: FAIL nos tres testes.

- [ ] **Step 3: Consolidar nas migrations principais**

Em `2025_01_15_000001_create_tasks_table.php`, logo apos o bloco `atribuido_para_id`:

```php
            $table->foreignId('criado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Quem registrou a demanda (pode diferir do solicitante)');
```
e junto dos indices: `$table->index(['criado_por_id', 'created_at']);`

Em `2025_01_15_000007_create_task_audit_logs_table.php`, trocar o `enum('acao', [...])->index()` por:

```php
            // String e nao enum: as acoes vivem em AcaoHistoricoDemanda, e enum no
            // banco exigiria migration a cada acao nova.
            $table->string('acao', 40)->index();
```

Substituir todo o conteudo de `2026_09_23_000004_create_demanda_assuntos.php` por:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorias antes de assuntos: a FK de assuntos exige a tabela. Antes as
        // duas viviam em migrations separadas, com a de categorias rodando DEPOIS,
        // e instalacao limpa falhava.
        if (! Schema::hasTable('demanda_categorias')) {
            Schema::create('demanda_categorias', function (Blueprint $table): void {
                $table->id();
                $table->string('nome');
                $table->text('descricao')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('demanda_categorias')->nullOnDelete();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('demanda_assuntos')) {
            Schema::create('demanda_assuntos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('categoria_id')->nullable()->constrained('demanda_categorias')->nullOnDelete();
                $table->string('nome', 150)->unique();
                $table->jsonb('campos_dinamicos')->nullable();
                $table->jsonb('form_automacao')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('tasks', 'assunto_id')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->foreignId('assunto_id')->nullable()->after('subcategoria')
                    ->constrained('demanda_assuntos')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'assunto_id')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('assunto_id');
            });
        }
        Schema::dropIfExists('demanda_assuntos');
        Schema::dropIfExists('demanda_categorias');
    }
};
```

Apagar `database/migrations/2026_09_23_100000_create_demanda_categorias_table.php`.

- [ ] **Step 4: Criar a migration de ajuste para bancos ja migrados**

`database/migrations/2026_09_24_100000_ajusta_demandas_paridade_chamados.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Leva bancos que ja rodaram as migrations principais ao mesmo schema que uma
 * instalacao limpa produz. Em instalacao limpa tudo aqui e no-op: as principais
 * ja criam o estado final.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'criado_por_id')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->foreignId('criado_por_id')->nullable()->after('atribuido_para_id')
                    ->constrained('users')->nullOnDelete();
                $table->index(['criado_por_id', 'created_at']);
            });
        }

        if (! Schema::hasColumn('demanda_assuntos', 'form_automacao')) {
            Schema::table('demanda_assuntos', function (Blueprint $table): void {
                $table->jsonb('form_automacao')->nullable()->after('campos_dinamicos');
            });
        }

        DB::statement('ALTER TABLE task_audit_logs DROP CONSTRAINT IF EXISTS task_audit_logs_acao_check');
        DB::statement('ALTER TABLE task_audit_logs ALTER COLUMN acao TYPE varchar(40)');
    }

    public function down(): void
    {
        // Sem volta para o enum: registros com acoes novas violariam o check.
    }
};
```

- [ ] **Step 5: Ajustar os models**

Em `Demanda.php`: adicionar `'criado_por_id',` ao `$fillable` depois de `'atribuido_para_id',` e a relacao:

```php
    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_id');
    }
```

Em `DemandaAssunto.php`:

```php
    protected $fillable = ['categoria_id', 'nome', 'campos_dinamicos', 'form_automacao', 'ativo'];

    protected $casts = ['campos_dinamicos' => 'array', 'form_automacao' => 'array', 'ativo' => 'boolean'];
```

- [ ] **Step 6: Rodar migrate e o teste**

```bash
bash /c/tmp/teste-demandas.sh php artisan migrate --force
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/SchemaDemandasTest.php
```
Expected: migrate aplica `2026_09_24_100000`; 3 testes PASS.

- [ ] **Step 7: Verificar instalacao limpa em banco descartavel**

```bash
docker exec newsdc_dev_db psql -U sdc -d postgres -c "CREATE DATABASE sdc_demandas_fresh TEMPLATE template_postgis"
bash /c/tmp/teste-demandas.sh sh -c "DB_DATABASE=sdc_demandas_fresh php artisan migrate --force" 2>&1 | tail -5
docker exec newsdc_dev_db psql -U sdc -d postgres -c "DROP DATABASE sdc_demandas_fresh"
```
Expected: migrate completa sem erro de FK em `demanda_assuntos`. Se falhar por outro modulo nao relacionado, registrar a falha e confirmar que `2026_09_23_000004` passou.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2025_01_15_000001_create_tasks_table.php database/migrations/2025_01_15_000007_create_task_audit_logs_table.php database/migrations/2026_09_23_000004_create_demanda_assuntos.php database/migrations/2026_09_23_100000_create_demanda_categorias_table.php database/migrations/2026_09_24_100000_ajusta_demandas_paridade_chamados.php app/Modules/Demandas/Models/Demanda.php app/Modules/Demandas/Models/DemandaAssunto.php
git commit -m "🗃️ db(demandas): criador, automacao do assunto e ordem do catalogo"
```

---

### Task 2: Vocabulario do dominio — etapa, prioridade simples e acoes do historico

**Files:**
- Create: `app/Modules/Demandas/Enums/EtapaDemanda.php`
- Create: `app/Modules/Demandas/Enums/PrioridadeSimples.php`
- Create: `app/Modules/Demandas/Enums/AcaoHistoricoDemanda.php`
- Modify: `app/Modules/Demandas/Enums/StatusDemanda.php`
- Test: `tests/Unit/Demandas/VocabularioDemandaTest.php`

**Interfaces:**
- Produces:
  - `EtapaDemanda::{ABERTO,EM_ANDAMENTO,CONCLUIDO,CANCELADO}` (`string` values `aberto|em_andamento|concluido|cancelado`), `label(): string`, `status(): array<StatusDemanda>`, `static options(): array<array{value:string,label:string}>`.
  - `StatusDemanda::etapa(): EtapaDemanda`.
  - `PrioridadeSimples::{BAIXA,MEDIA,ALTA}` (`baixa|media|alta`), `impacto(): Impacto`, `urgencia(): Urgencia`, `label(): string`, `static dePrioridade(?Prioridade): self`, `static options(): array`.
  - `AcaoHistoricoDemanda` com `rotulo(): string`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Demandas;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\EtapaDemanda;
use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\Prioridade;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Enums\Urgencia;
use PHPUnit\Framework\TestCase;

class VocabularioDemandaTest extends TestCase
{
    public function test_todo_status_tem_uma_etapa(): void
    {
        $esperado = [
            'aberta' => 'aberto', 'em_analise' => 'aberto',
            'em_progresso' => 'em_andamento', 'aguardando_terceiros' => 'em_andamento',
            'resolvida' => 'concluido', 'fechada' => 'concluido',
            'cancelada' => 'cancelado',
        ];
        foreach (StatusDemanda::cases() as $status) {
            $this->assertSame($esperado[$status->value], $status->etapa()->value, $status->value);
        }
    }

    public function test_etapa_devolve_seus_status(): void
    {
        $this->assertSame(
            [StatusDemanda::EM_PROGRESSO, StatusDemanda::AGUARDANDO_TERCEIROS],
            EtapaDemanda::EM_ANDAMENTO->status()
        );
    }

    public function test_prioridade_simples_ida_e_volta_pela_matriz(): void
    {
        foreach (PrioridadeSimples::cases() as $simples) {
            $matriz = Prioridade::calcularPorMatriz($simples->impacto(), $simples->urgencia());
            $this->assertSame($simples, PrioridadeSimples::dePrioridade($matriz), $simples->value);
        }
        $this->assertSame(Impacto::ALTO, PrioridadeSimples::ALTA->impacto());
        $this->assertSame(Urgencia::BAIXA, PrioridadeSimples::BAIXA->urgencia());
        $this->assertSame(PrioridadeSimples::MEDIA, PrioridadeSimples::dePrioridade(null));
    }

    public function test_rotulos_do_legado(): void
    {
        $this->assertSame('Criou o chamado', AcaoHistoricoDemanda::CRIADA->rotulo());
        $this->assertSame('Transferência', AcaoHistoricoDemanda::ATRIBUIDA->rotulo());
        $this->assertSame('Chamado REABERTO', AcaoHistoricoDemanda::REABERTA->rotulo());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Unit/Demandas/VocabularioDemandaTest.php`
Expected: FAIL com "Class ... EtapaDemanda not found".

- [ ] **Step 3: Implementar**

`app/Modules/Demandas/Enums/EtapaDemanda.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Enums;

/**
 * Projecao do status ITIL para a tela.
 *
 * O dominio segue com os sete status; quem usa o sistema ve o fluxo curto que a
 * equipe ja conhecia no cedec-demanda. Nao existe coluna para isto: a etapa e
 * sempre derivada do status, entao as duas nunca divergem.
 */
enum EtapaDemanda: string
{
    case ABERTO = 'aberto';
    case EM_ANDAMENTO = 'em_andamento';
    case CONCLUIDO = 'concluido';
    case CANCELADO = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::ABERTO => 'Aberto',
            self::EM_ANDAMENTO => 'Em andamento',
            self::CONCLUIDO => 'Concluído',
            self::CANCELADO => 'Cancelado',
        };
    }

    /** @return list<StatusDemanda> */
    public function status(): array
    {
        return array_values(array_filter(
            StatusDemanda::cases(),
            fn (StatusDemanda $status): bool => $status->etapa() === $this,
        ));
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $etapa): array => ['value' => $etapa->value, 'label' => $etapa->label()],
            self::cases(),
        );
    }
}
```

Em `StatusDemanda.php`, adicionar depois de `isActive()`:

```php
    /**
     * Etapa exibida na tela. Ver EtapaDemanda.
     */
    public function etapa(): EtapaDemanda
    {
        return match ($this) {
            self::ABERTA, self::EM_ANALISE => EtapaDemanda::ABERTO,
            self::EM_PROGRESSO, self::AGUARDANDO_TERCEIROS => EtapaDemanda::EM_ANDAMENTO,
            self::RESOLVIDA, self::FECHADA => EtapaDemanda::CONCLUIDO,
            self::CANCELADA => EtapaDemanda::CANCELADO,
        };
    }
```

`app/Modules/Demandas/Enums/PrioridadeSimples.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Enums;

/**
 * Prioridade como o legado pedia: baixa, media ou alta.
 *
 * Cada opcao vira um par impacto x urgencia, e a matriz ITIL calcula a
 * prioridade de 1 a 5. A volta (dePrioridade) agrupa as cinco faixas nas tres,
 * para o badge da tela.
 */
enum PrioridadeSimples: string
{
    case BAIXA = 'baixa';
    case MEDIA = 'media';
    case ALTA = 'alta';

    public function label(): string
    {
        return match ($this) {
            self::BAIXA => 'Baixa',
            self::MEDIA => 'Média',
            self::ALTA => 'Alta',
        };
    }

    public function impacto(): Impacto
    {
        return match ($this) {
            self::BAIXA => Impacto::BAIXO,
            self::MEDIA => Impacto::MEDIO,
            self::ALTA => Impacto::ALTO,
        };
    }

    public function urgencia(): Urgencia
    {
        return match ($this) {
            self::BAIXA => Urgencia::BAIXA,
            self::MEDIA => Urgencia::MEDIA,
            self::ALTA => Urgencia::ALTA,
        };
    }

    public static function dePrioridade(?Prioridade $prioridade): self
    {
        return match ($prioridade) {
            Prioridade::CRITICA, Prioridade::ALTA => self::ALTA,
            Prioridade::BAIXA, Prioridade::PLANEJADA => self::BAIXA,
            default => self::MEDIA,
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $p): array => ['value' => $p->value, 'label' => $p->label()],
            self::cases(),
        );
    }
}
```

`app/Modules/Demandas/Enums/AcaoHistoricoDemanda.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Enums;

/**
 * Acoes gravadas em task_audit_logs.acao. Os rotulos repetem os do
 * cedec-demanda, para quem vem do legado reconhecer a aba Historico.
 */
enum AcaoHistoricoDemanda: string
{
    case CRIADA = 'created';
    case EDITADA = 'updated';
    case STATUS_ALTERADO = 'status_changed';
    case ATRIBUIDA = 'assigned';
    case ANEXO_ADICIONADO = 'attachment_added';
    case RESOLVIDA = 'resolved';
    case REABERTA = 'reopened';
    case AUTOMACAO_SOLICITADA = 'automation_requested';
    case AUTOMACAO_CONFIRMADA = 'automation_confirmed';
    case AUTOMACAO_FALHOU = 'automation_failed';
    case IMPORTADA = 'imported';

    public function rotulo(): string
    {
        return match ($this) {
            self::CRIADA => 'Criou o chamado',
            self::EDITADA => 'Edição',
            self::STATUS_ALTERADO => 'Alteração de Status',
            self::ATRIBUIDA => 'Transferência',
            self::ANEXO_ADICIONADO => 'Novo Anexo',
            self::RESOLVIDA => 'Resolução',
            self::REABERTA => 'Chamado REABERTO',
            self::AUTOMACAO_SOLICITADA => 'Automação solicitada',
            self::AUTOMACAO_CONFIRMADA => 'Automação confirmada',
            self::AUTOMACAO_FALHOU => 'Automação falhou',
            self::IMPORTADA => 'Importado do sistema anterior',
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Unit/Demandas/VocabularioDemandaTest.php`
Expected: 4 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Demandas/Enums/EtapaDemanda.php app/Modules/Demandas/Enums/PrioridadeSimples.php app/Modules/Demandas/Enums/AcaoHistoricoDemanda.php app/Modules/Demandas/Enums/StatusDemanda.php
git commit -m "✨ feat(demandas): etapa, prioridade simples e acoes do historico"
```

---

### Task 3: Workflow com transicoes de varios passos, historico e contexto de importacao

**Files:**
- Create: `app/Modules/Demandas/Domain/Exceptions/TransicaoProibidaException.php`
- Modify: `app/Modules/Demandas/Domain/Workflows/DemandaWorkflow.php`
- Modify: `app/Modules/Demandas/Domain/Guards/ExigeAtribuicaoParaProgresso.php`
- Create: `app/Modules/Demandas/Services/HistoricoDemanda.php`
- Create: `app/Modules/Demandas/Support/ContextoImportacao.php`
- Modify: `app/Modules/Demandas/DemandasServiceProvider.php`
- Test: `tests/Feature/Demandas/DemandaWorkflowTest.php`

**Interfaces:**
- Produces:
  - `TransicaoProibidaException extends \DomainException`.
  - `DemandaWorkflow::transitar(Demanda $demanda, StatusDemanda $novoStatus, int $usuarioId, ?CarbonInterface $momento = null): Demanda`
  - `DemandaWorkflow::conduzirAte(Demanda $demanda, StatusDemanda $alvo, int $usuarioId, ?CarbonInterface $momento = null): Demanda` — percorre o menor caminho permitido; tudo numa transacao.
  - `DemandaWorkflow::caminho(StatusDemanda $de, StatusDemanda $para): list<StatusDemanda>` (static; lanca `TransicaoProibidaException` se nao houver caminho; `[]` se iguais).
  - `HistoricoDemanda::registrar(Demanda $demanda, ?int $userId, AcaoHistoricoDemanda $acao, string $detalhes, array $metadata = [], ?string $campo = null, ?string $anterior = null, ?string $novo = null, ?CarbonInterface $em = null): DemandaAuditLog` — `detalhes` e `rotulo` vao em `metadata`.
  - `ContextoImportacao::ativo(): bool`, `durante(callable $fn): mixed` (registrado como `scoped`).

- [ ] **Step 1: Write the failing test**

Criar tambem o helper de fixtures usado por todas as Feature tests seguintes.

`tests/Feature/Demandas/Concerns/CriaDemandas.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas\Concerns;

use App\Models\User;
use App\Modules\Demandas\Enums\Impacto;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAssunto;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

trait CriaDemandas
{
    protected const PERMISSOES_SOLICITANTE = [
        'demandas.chamados.view', 'demandas.chamados.create',
    ];

    protected const PERMISSOES_GESTOR = [
        'demandas.chamados.view', 'demandas.chamados.create', 'demandas.chamados.edit',
        'demandas.chamados.export', 'demandas.chamados.manage', 'demandas.chamados.resolver',
        'demandas.chamados.automatizar', 'demandas.dashboard.view', 'demandas.chamados.delete',
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

    protected function gestor(): User
    {
        return $this->usuarioCom(self::PERMISSOES_GESTOR);
    }

    protected function solicitante(): User
    {
        return $this->usuarioCom(self::PERMISSOES_SOLICITANTE);
    }

    protected function demanda(User $solicitante, array $atributos = []): Demanda
    {
        return Demanda::create(array_merge([
            'tipo' => TipoDemanda::SOLICITACAO,
            'titulo' => 'TST- demanda de teste',
            'descricao' => 'descricao',
            'status' => StatusDemanda::ABERTA,
            'impacto' => Impacto::MEDIO,
            'urgencia' => Urgencia::MEDIA,
            'solicitante_id' => $solicitante->id,
            'criado_por_id' => $solicitante->id,
        ], $atributos));
    }

    protected function assunto(array $campos = [], ?array $automacao = null): DemandaAssunto
    {
        return DemandaAssunto::create([
            'nome' => 'TST- assunto '.uniqid(),
            'campos_dinamicos' => $campos,
            'form_automacao' => $automacao,
            'ativo' => true,
        ]);
    }
}
```

`tests/Feature/Demandas/DemandaWorkflowTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Modules\Demandas\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Demandas\Domain\Workflows\DemandaWorkflow;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Services\HistoricoDemanda;
use App\Modules\Demandas\Support\ContextoImportacao;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaWorkflowTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    public function test_caminho_mais_curto_de_aberta_ate_resolvida(): void
    {
        $this->assertSame(
            [StatusDemanda::EM_ANALISE, StatusDemanda::EM_PROGRESSO, StatusDemanda::RESOLVIDA],
            DemandaWorkflow::caminho(StatusDemanda::ABERTA, StatusDemanda::RESOLVIDA)
        );
        $this->assertSame([], DemandaWorkflow::caminho(StatusDemanda::ABERTA, StatusDemanda::ABERTA));
    }

    public function test_caminho_impossivel_lanca_transicao_proibida(): void
    {
        $this->expectException(TransicaoProibidaException::class);
        DemandaWorkflow::caminho(StatusDemanda::CANCELADA, StatusDemanda::ABERTA);
    }

    public function test_conduzir_ate_resolvida_com_momento_informado(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($gestor, ['atribuido_para_id' => $gestor->id]);
        $momento = CarbonImmutable::parse('2099-01-10 11:56:00');

        $resultado = app(DemandaWorkflow::class)
            ->conduzirAte($demanda, StatusDemanda::RESOLVIDA, $gestor->id, $momento);

        $this->assertSame(StatusDemanda::RESOLVIDA, $resultado->status);
        $this->assertSame('2099-01-10 11:56:00', $resultado->resolvido_em->format('Y-m-d H:i:s'));
        $this->assertGreaterThan(0, $resultado->tempo_total_resolucao);
    }

    public function test_falha_no_meio_do_caminho_nao_deixa_estado_parcial(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($gestor); // sem responsavel: guard barra em_progresso

        try {
            app(DemandaWorkflow::class)->conduzirAte($demanda, StatusDemanda::RESOLVIDA, $gestor->id);
            $this->fail('Deveria ter barrado');
        } catch (\DomainException) {
        }

        $this->assertSame(StatusDemanda::ABERTA, $demanda->fresh()->status);
    }

    public function test_historico_grava_rotulo_detalhes_e_data_informada(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($gestor);
        $em = CarbonImmutable::parse('2099-02-01 08:00:00');

        $log = app(HistoricoDemanda::class)->registrar(
            $demanda, $gestor->id, AcaoHistoricoDemanda::CRIADA, 'Chamado aberto', em: $em
        );

        $this->assertSame('created', $log->fresh()->acao);
        $this->assertSame('Criou o chamado', $log->fresh()->metadata['rotulo']);
        $this->assertSame('Chamado aberto', $log->fresh()->metadata['detalhes']);
        $this->assertSame('2099-02-01 08:00:00', $log->fresh()->created_at->format('Y-m-d H:i:s'));
    }

    public function test_contexto_de_importacao_so_vale_dentro_do_bloco(): void
    {
        $contexto = app(ContextoImportacao::class);
        $this->assertFalse($contexto->ativo());
        $dentro = $contexto->durante(fn () => $contexto->ativo());
        $this->assertTrue($dentro);
        $this->assertFalse($contexto->ativo());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaWorkflowTest.php`
Expected: FAIL ("Class ... TransicaoProibidaException not found").

- [ ] **Step 3: Implementar excecao, workflow e guard**

`app/Modules/Demandas/Domain/Exceptions/TransicaoProibidaException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Exceptions;

use DomainException;

final class TransicaoProibidaException extends DomainException
{
    public static function entre(string $de, string $para): self
    {
        return new self(sprintf('Não é possível levar a demanda de "%s" para "%s".', $de, $para));
    }
}
```

Substituir `DemandaWorkflow.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Workflows;

use App\Modules\Demandas\Domain\Events\DemandaResolvidaV1;
use App\Modules\Demandas\Domain\Events\StatusAlteradoV1;
use App\Modules\Demandas\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Demandas\Domain\Guards\ExigeAtribuicaoParaProgresso;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class DemandaWorkflow
{
    public function __construct(private readonly ExigeAtribuicaoParaProgresso $atribuicao) {}

    public function transitar(Demanda $demanda, StatusDemanda $novoStatus, int $usuarioId, ?CarbonInterface $momento = null): Demanda
    {
        return DB::transaction(function () use ($demanda, $novoStatus, $usuarioId, $momento): Demanda {
            $demanda = Demanda::query()->lockForUpdate()->findOrFail($demanda->getKey());
            $anterior = $demanda->status;
            if (! $anterior->canTransitionTo($novoStatus)) {
                throw TransicaoProibidaException::entre($anterior->label(), $novoStatus->label());
            }
            $this->atribuicao->check($demanda, $novoStatus);

            $demanda->status = $novoStatus;
            if ($novoStatus === StatusDemanda::RESOLVIDA) {
                $resolvidoEm = $momento ?? now();
                $demanda->resolvido_em = $resolvidoEm;
                $demanda->tempo_total_resolucao = (int) round(abs($demanda->created_at->diffInMinutes($resolvidoEm)));
            } elseif ($anterior === StatusDemanda::RESOLVIDA) {
                $demanda->resolvido_em = null;
                $demanda->tempo_total_resolucao = null;
            }
            $demanda->save();

            DB::afterCommit(static function () use ($demanda, $anterior, $novoStatus, $usuarioId): void {
                event(StatusAlteradoV1::create($demanda->id, $anterior->value, $novoStatus->value, $usuarioId));
                if ($novoStatus === StatusDemanda::RESOLVIDA) {
                    event(DemandaResolvidaV1::create($demanda->id, $usuarioId));
                }
            });

            return $demanda;
        });
    }

    /**
     * Leva a demanda ate o status alvo pelo menor caminho da maquina de estados.
     *
     * Existe porque a tela fala em etapas: "Em andamento" a partir de aberta sao
     * dois passos (aberta -> em_analise -> em_progresso). Uma transacao so, para
     * que um guard barrando no meio nao deixe a demanda num passo intermediario.
     */
    public function conduzirAte(Demanda $demanda, StatusDemanda $alvo, int $usuarioId, ?CarbonInterface $momento = null): Demanda
    {
        return DB::transaction(function () use ($demanda, $alvo, $usuarioId, $momento): Demanda {
            $atual = Demanda::query()->lockForUpdate()->findOrFail($demanda->getKey());
            foreach (self::caminho($atual->status, $alvo) as $passo) {
                $atual = $this->transitar($atual, $passo, $usuarioId, $passo === $alvo ? $momento : null);
            }

            return $atual;
        });
    }

    /**
     * Busca em largura sobre getAllowedTransitions(). Sete estados: custo nulo.
     *
     * @return list<StatusDemanda>
     */
    public static function caminho(StatusDemanda $de, StatusDemanda $para): array
    {
        if ($de === $para) {
            return [];
        }

        $fila = [[$de, []]];
        $visitados = [$de->value => true];
        while ($fila !== []) {
            [$status, $trilha] = array_shift($fila);
            foreach ($status->getAllowedTransitions() as $proximo) {
                if (isset($visitados[$proximo->value])) {
                    continue;
                }
                $novaTrilha = [...$trilha, $proximo];
                if ($proximo === $para) {
                    return $novaTrilha;
                }
                $visitados[$proximo->value] = true;
                $fila[] = [$proximo, $novaTrilha];
            }
        }

        throw TransicaoProibidaException::entre($de->label(), $para->label());
    }
}
```

- [ ] **Step 4: Implementar historico e contexto de importacao**

`app/Modules/Demandas/Services/HistoricoDemanda.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAuditLog;
use Carbon\CarbonInterface;

/**
 * Unico ponto de escrita da aba Historico. Casos de uso chamam isto uma vez por
 * acao do usuario; o workflow nao registra nada, para uma resolucao de tres
 * passos aparecer como um fato so.
 */
final class HistoricoDemanda
{
    public function registrar(
        Demanda $demanda,
        ?int $userId,
        AcaoHistoricoDemanda $acao,
        string $detalhes,
        array $metadata = [],
        ?string $campo = null,
        ?string $anterior = null,
        ?string $novo = null,
        ?CarbonInterface $em = null,
    ): DemandaAuditLog {
        $log = new DemandaAuditLog([
            'task_id' => $demanda->getKey(),
            'user_id' => $userId,
            'acao' => $acao->value,
            'campo' => $campo,
            'valor_anterior' => $anterior,
            'valor_novo' => $novo,
            'metadata' => array_merge($metadata, ['rotulo' => $acao->rotulo(), 'detalhes' => $detalhes]),
        ]);
        if ($em !== null) {
            $log->created_at = $em;
        }
        $log->save();

        return $log;
    }
}
```

`app/Modules/Demandas/Support/ContextoImportacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Support;

/**
 * Marca que a escrita em curso e carga do legado, nao acao de usuario.
 *
 * Registrado como scoped: sob Octane o estado morre com a requisicao, entao uma
 * importacao nunca silencia notificacao de outra requisicao do mesmo worker.
 */
final class ContextoImportacao
{
    private bool $ativo = false;

    public function ativo(): bool
    {
        return $this->ativo;
    }

    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function durante(callable $fn): mixed
    {
        $anterior = $this->ativo;
        $this->ativo = true;
        try {
            return $fn();
        } finally {
            $this->ativo = $anterior;
        }
    }
}
```

Em `DemandasServiceProvider::register()` substituir o comentario por:

```php
        $this->app->scoped(ContextoImportacao::class);
```
com `use App\Modules\Demandas\Support\ContextoImportacao;`.

- [ ] **Step 5: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaWorkflowTest.php`
Expected: 6 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Demandas/Domain app/Modules/Demandas/Services/HistoricoDemanda.php app/Modules/Demandas/Support/ContextoImportacao.php app/Modules/Demandas/DemandasServiceProvider.php
git commit -m "✨ feat(demandas): workflow com caminho entre etapas e historico unico"
```

---

### Task 4: Abrir e editar com campos dinamicos por assunto

**Files:**
- Create: `app/Modules/Demandas/Domain/Services/ValidadorCamposDinamicos.php`
- Create: `app/Modules/Demandas/Services/DemandaWriteService.php`
- Modify: `app/Modules/Demandas/DTOs/CriarDemandaData.php`, `app/Modules/Demandas/DTOs/AtualizarDemandaData.php`
- Modify: `app/Modules/Demandas/Requests/StoreDemandaRequest.php`, `app/Modules/Demandas/Requests/UpdateDemandaRequest.php`
- Test: `tests/Feature/Demandas/DemandaWriteServiceTest.php`

**Interfaces:**
- Consumes: `HistoricoDemanda::registrar`, `ContextoImportacao::ativo`, `SlaEngine::iniciarSlaParaDemanda(Demanda)`, `PrioridadeSimples`.
- Produces:
  - Formato de `campos_dinamicos`: `list<array{label: string, tipo: 'text'|'checkbox'}>` (igual ao legado); valores em `campos_customizados` indexados pela `label`.
  - `ValidadorCamposDinamicos::normalizar(?DemandaAssunto $assunto, array $valores): array` — devolve so as labels do assunto; `text` obrigatorio (string nao vazia), `checkbox` vira bool; lanca `Illuminate\Validation\ValidationException` com chave `campos_customizados.<label>`.
  - `CriarDemandaData` ganha `int $criadoPorId`, `array $camposCustomizados`; `solicitanteId` pode vir do request (gestor).
  - `AtualizarDemandaData` ganha `?array $camposCustomizados`.
  - `DemandaWriteService::abrir(CriarDemandaData $dados): Demanda`, `DemandaWriteService::atualizar(Demanda $demanda, AtualizarDemandaData $dados, int $userId): Demanda`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Modules\Demandas\DTOs\AtualizarDemandaData;
use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Services\DemandaWriteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaWriteServiceTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    private function dados(int $userId, ?int $assuntoId, array $campos, PrioridadeSimples $p = PrioridadeSimples::MEDIA): CriarDemandaData
    {
        return new CriarDemandaData(
            tipo: TipoDemanda::SOLICITACAO,
            titulo: 'TST- abrir',
            descricao: 'descricao',
            categoria: null,
            subcategoria: null,
            assuntoId: $assuntoId,
            urgencia: $p->urgencia(),
            impacto: $p->impacto(),
            solicitanteId: $userId,
            criadoPorId: $userId,
            camposCustomizados: $campos,
        );
    }

    public function test_abrir_grava_criador_campos_e_historico(): void
    {
        $user = $this->solicitante();
        $assunto = $this->assunto([
            ['label' => 'Login AD', 'tipo' => 'text'],
            ['label' => 'Urgente para hoje', 'tipo' => 'checkbox'],
        ]);

        $demanda = app(DemandaWriteService::class)->abrir(
            $this->dados($user->id, $assunto->id, ['Login AD' => 'M1234567', 'Intruso' => 'x'], PrioridadeSimples::ALTA)
        );

        $this->assertSame(StatusDemanda::ABERTA, $demanda->status);
        $this->assertSame($user->id, $demanda->criado_por_id);
        $this->assertSame(['Login AD' => 'M1234567', 'Urgente para hoje' => false], $demanda->campos_customizados);
        $this->assertSame(PrioridadeSimples::ALTA, PrioridadeSimples::dePrioridade($demanda->prioridade));
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'created']);
    }

    public function test_campo_texto_obrigatorio_vazio_gera_erro_por_campo(): void
    {
        $user = $this->solicitante();
        $assunto = $this->assunto([['label' => 'Login AD', 'tipo' => 'text']]);

        try {
            app(DemandaWriteService::class)->abrir($this->dados($user->id, $assunto->id, ['Login AD' => '  ']));
            $this->fail('Deveria validar');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('campos_customizados.Login AD', $e->errors());
        }
    }

    public function test_trocar_assunto_descarta_campos_antigos_e_valida_os_novos(): void
    {
        $user = $this->gestor();
        $antigo = $this->assunto([['label' => 'Patrimonio', 'tipo' => 'text']]);
        $novo = $this->assunto([['label' => 'Login AD', 'tipo' => 'text']]);
        $demanda = $this->demanda($user, ['assunto_id' => $antigo->id, 'campos_customizados' => ['Patrimonio' => '123']]);

        $atualizada = app(DemandaWriteService::class)->atualizar($demanda, new AtualizarDemandaData(
            tipo: null, titulo: null, descricao: null, categoria: null, subcategoria: null,
            assuntoId: $novo->id, urgencia: null, impacto: null,
            camposCustomizados: ['Login AD' => 'M7654321'],
            presentes: ['assunto_id', 'campos_customizados'],
        ), $user->id);

        $this->assertSame(['Login AD' => 'M7654321'], $atualizada->campos_customizados);
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'updated', 'campo' => 'assunto_id']);
    }

    public function test_trocar_assunto_sem_os_campos_novos_nao_explode(): void
    {
        $user = $this->gestor();
        $novo = $this->assunto([['label' => 'Login AD', 'tipo' => 'text']]);
        $demanda = $this->demanda($user);

        $this->expectException(ValidationException::class);
        app(DemandaWriteService::class)->atualizar($demanda, new AtualizarDemandaData(
            tipo: null, titulo: null, descricao: null, categoria: null, subcategoria: null,
            assuntoId: $novo->id, urgencia: null, impacto: null, camposCustomizados: null,
            presentes: ['assunto_id'],
        ), $user->id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaWriteServiceTest.php`
Expected: FAIL (construtor de `CriarDemandaData` sem `criadoPorId`).

- [ ] **Step 3: Validador de campos dinamicos**

`app/Modules/Demandas/Domain/Services/ValidadorCamposDinamicos.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Services;

use App\Modules\Demandas\Models\DemandaAssunto;
use Illuminate\Validation\ValidationException;

/**
 * Confere os valores da demanda contra os campos declarados no assunto.
 *
 * Formato herdado do cedec-demanda: [{label, tipo: text|checkbox}], com o valor
 * guardado pela label. Texto e obrigatorio (o legado marcava com asterisco);
 * checkbox ausente vale false. Chave que o assunto nao declara e descartada, para
 * um assunto trocado nao carregar resposta de pergunta que ninguem fez.
 */
final class ValidadorCamposDinamicos
{
    public function normalizar(?DemandaAssunto $assunto, array $valores): array
    {
        $campos = $assunto?->campos_dinamicos ?? [];
        $normalizados = [];
        $erros = [];

        foreach ($campos as $campo) {
            $label = (string) ($campo['label'] ?? '');
            if ($label === '') {
                continue;
            }
            $valor = $valores[$label] ?? null;

            if (($campo['tipo'] ?? 'text') === 'checkbox') {
                $normalizados[$label] = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
                continue;
            }

            $texto = is_scalar($valor) ? trim((string) $valor) : '';
            if ($texto === '') {
                $erros['campos_customizados.'.$label] = "Preencha o campo \"{$label}\".";
                continue;
            }
            $normalizados[$label] = mb_substr($texto, 0, 500);
        }

        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }

        return $normalizados;
    }
}
```

- [ ] **Step 4: DTOs e FormRequests**

Em `CriarDemandaData.php`: adicionar ao construtor (depois de `solicitanteId`) `public int $criadoPorId,` e `public array $camposCustomizados = [],`, remover `tags`, e trocar `fromRequest`/`toArray` por:

```php
    public static function fromRequest(StoreDemandaRequest $request): self
    {
        $data = $request->validated();
        $simples = PrioridadeSimples::tryFrom($data['prioridade_simples'] ?? '') ?? PrioridadeSimples::MEDIA;
        $autorId = (int) $request->user()->id;

        return new self(
            tipo: TipoDemanda::tryFrom($data['tipo'] ?? '') ?? TipoDemanda::SOLICITACAO,
            titulo: $data['titulo'],
            descricao: $data['descricao'],
            categoria: $data['categoria'] ?? null,
            subcategoria: $data['subcategoria'] ?? null,
            assuntoId: isset($data['assunto_id']) ? (int) $data['assunto_id'] : null,
            urgencia: isset($data['urgencia']) ? Urgencia::from($data['urgencia']) : $simples->urgencia(),
            impacto: isset($data['impacto']) ? Impacto::from($data['impacto']) : $simples->impacto(),
            solicitanteId: isset($data['solicitante_id']) ? (int) $data['solicitante_id'] : $autorId,
            criadoPorId: $autorId,
            atribuidoParaId: isset($data['responsavel_id']) ? (int) $data['responsavel_id'] : null,
            camposCustomizados: $data['campos_customizados'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'categoria' => $this->categoria,
            'subcategoria' => $this->subcategoria,
            'assunto_id' => $this->assuntoId,
            'urgencia' => $this->urgencia,
            'impacto' => $this->impacto,
            'solicitante_id' => $this->solicitanteId,
            'criado_por_id' => $this->criadoPorId,
            'atribuido_para_id' => $this->atribuidoParaId,
        ];
    }
```
(ordem dos parametros do construtor: `tipo, titulo, descricao, categoria, subcategoria, assuntoId, urgencia, impacto, solicitanteId, criadoPorId, atribuidoParaId = null, camposCustomizados = []`; importar `App\Modules\Demandas\Enums\PrioridadeSimples`.)

Em `AtualizarDemandaData.php`: adicionar `public ?array $camposCustomizados,` antes de `presentes`; no `fromRequest` `camposCustomizados: $data['campos_customizados'] ?? null,`; e em `toArray()` NAO incluir `campos_customizados` (quem trata e o service).

`StoreDemandaRequest::rules()` passa a ser:

```php
        return [
            'tipo' => ['nullable', new Enum(TipoDemanda::class)],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'subcategoria' => ['nullable', 'string', 'max:100'],
            'assunto_id' => ['nullable', 'integer', Rule::exists('demanda_assuntos', 'id')->where('ativo', true)],
            'prioridade_simples' => ['nullable', new Enum(PrioridadeSimples::class)],
            'urgencia' => ['nullable', new Enum(Urgencia::class)],
            'impacto' => ['nullable', new Enum(Impacto::class)],
            'campos_customizados' => ['nullable', 'array'],
            'solicitante_id' => [Rule::prohibitedIf(! $this->user()->can('demandas.chamados.manage')), 'nullable', 'integer', 'exists:users,id'],
            'responsavel_id' => [Rule::prohibitedIf(! $this->user()->can('demandas.chamados.manage')), 'nullable', 'integer', 'exists:users,id'],
        ];
```

`UpdateDemandaRequest::rules()` ganha `'campos_customizados' => ['sometimes', 'nullable', 'array'],`.

- [ ] **Step 5: Service de escrita**

`app/Modules/Demandas/Services/DemandaWriteService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\Domain\Events\DemandaCriadaV1;
use App\Modules\Demandas\Domain\Services\ValidadorCamposDinamicos;
use App\Modules\Demandas\DTOs\AtualizarDemandaData;
use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Support\ContextoImportacao;
use Illuminate\Support\Facades\DB;

/**
 * Abrir e editar demanda. Publico para outros contextos: o Inventario abre
 * chamado de lote por aqui, nunca criando Demanda direto.
 */
final class DemandaWriteService
{
    public function __construct(
        private readonly DemandaRepository $repository,
        private readonly ValidadorCamposDinamicos $validador,
        private readonly HistoricoDemanda $historico,
        private readonly SlaEngine $sla,
        private readonly ContextoImportacao $contexto,
    ) {}

    public function abrir(CriarDemandaData $dados): Demanda
    {
        $assunto = $dados->assuntoId !== null ? DemandaAssunto::find($dados->assuntoId) : null;
        $campos = $this->validador->normalizar($assunto, $dados->camposCustomizados);

        return DB::transaction(function () use ($dados, $campos): Demanda {
            $demanda = new Demanda($dados->toArray());
            $demanda->status = StatusDemanda::ABERTA;
            $demanda->campos_customizados = $campos;
            $this->repository->save($demanda);

            $this->historico->registrar(
                $demanda, $dados->criadoPorId, AcaoHistoricoDemanda::CRIADA,
                'Chamado aberto com status inicial: Em aberto.'
            );

            if (! $this->contexto->ativo()) {
                $this->sla->iniciarSlaParaDemanda($demanda);
                DB::afterCommit(static fn () => event(DemandaCriadaV1::create(
                    $demanda->id,
                    $demanda->protocolo,
                    $demanda->tipo->value,
                    (string) ($demanda->prioridade?->value ?? 3),
                    (int) $demanda->solicitante_id,
                )));
            }

            return $demanda;
        });
    }

    public function atualizar(Demanda $demanda, AtualizarDemandaData $dados, int $userId): Demanda
    {
        return DB::transaction(function () use ($demanda, $dados, $userId): Demanda {
            $antes = $demanda->only(['titulo', 'descricao', 'assunto_id']);
            $demanda->fill($dados->toArray());

            $trocouAssunto = $demanda->isDirty('assunto_id');
            if ($trocouAssunto || $dados->camposCustomizados !== null) {
                $assunto = $demanda->assunto_id !== null ? DemandaAssunto::find($demanda->assunto_id) : null;
                $demanda->campos_customizados = $this->validador->normalizar(
                    $assunto,
                    $dados->camposCustomizados ?? ($trocouAssunto ? [] : ($demanda->campos_customizados ?? [])),
                );
            }

            $this->repository->save($demanda);

            foreach ($antes as $campo => $valorAnterior) {
                if ((string) $valorAnterior === (string) $demanda->{$campo}) {
                    continue;
                }
                $this->historico->registrar(
                    $demanda, $userId, AcaoHistoricoDemanda::EDITADA,
                    $campo === 'assunto_id' ? 'Assunto alterado.' : 'Descrição alterada.',
                    campo: $campo, anterior: (string) $valorAnterior, novo: (string) $demanda->{$campo},
                );
            }

            return $demanda;
        });
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaWriteServiceTest.php`
Expected: 4 PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Demandas/Domain/Services app/Modules/Demandas/Services/DemandaWriteService.php app/Modules/Demandas/DTOs app/Modules/Demandas/Requests/StoreDemandaRequest.php app/Modules/Demandas/Requests/UpdateDemandaRequest.php
git commit -m "✨ feat(demandas): abrir e editar com campos dinamicos por assunto"
```

---

### Task 5: Mudar etapa, resolver com datas e reabrir

**Files:**
- Create: `app/Modules/Demandas/Services/DemandaStatusService.php`
- Create: `app/Modules/Demandas/DTOs/ResolucaoDemandaData.php`
- Create: `app/Modules/Demandas/Requests/ResolverDemandaRequest.php`
- Test: `tests/Feature/Demandas/DemandaStatusServiceTest.php`

**Interfaces:**
- Consumes: `DemandaWorkflow::conduzirAte`, `DemandaWorkflow::transitar`, `HistoricoDemanda::registrar`.
- Produces:
  - `ResolucaoDemandaData(CarbonImmutable $abertaEm, CarbonImmutable $resolvidaEm)` + `static fromRequest(ResolverDemandaRequest)`.
  - `DemandaStatusService::alterarStatus(Demanda $demanda, StatusDemanda $alvo, int $userId): Demanda` — recusa `RESOLVIDA` (usar `resolver`); em `EM_PROGRESSO` sem responsavel atribui `$userId`.
  - `DemandaStatusService::resolver(Demanda $demanda, ResolucaoDemandaData $dados, int $userId): Demanda`.
  - `DemandaStatusService::reabrir(Demanda $demanda, int $userId): Demanda` — so de `RESOLVIDA`.
  - `DemandaStatusService::assumirSeSemResponsavel(Demanda $demanda, int $userId): Demanda` (publico, usado na Task 6).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Modules\Demandas\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Demandas\DTOs\ResolucaoDemandaData;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Services\DemandaStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaStatusServiceTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    public function test_resolver_demanda_aberta_sem_responsavel_atribui_e_percorre_o_caminho(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($this->solicitante());
        $abertura = CarbonImmutable::parse('2099-03-01 09:00:00');
        $fechamento = CarbonImmutable::parse('2099-03-02 10:30:00');
        $this->travarRelogio('2099-03-05 12:00:00');

        $resolvida = app(DemandaStatusService::class)
            ->resolver($demanda, new ResolucaoDemandaData($abertura, $fechamento), $gestor->id);

        $this->assertSame(StatusDemanda::RESOLVIDA, $resolvida->status);
        $this->assertSame($gestor->id, $resolvida->atribuido_para_id);
        $this->assertSame('2099-03-01 09:00:00', $resolvida->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2099-03-02 10:30:00', $resolvida->resolvido_em->format('Y-m-d H:i:s'));
        $this->assertSame(1530, $resolvida->tempo_total_resolucao);
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'resolved']);
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'updated', 'campo' => 'created_at']);
    }

    public function test_reabrir_volta_para_em_andamento_e_limpa_resolucao(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($gestor, ['atribuido_para_id' => $gestor->id]);
        $service = app(DemandaStatusService::class);
        $service->resolver($demanda, new ResolucaoDemandaData(CarbonImmutable::now()->subHour(), CarbonImmutable::now()), $gestor->id);

        $reaberta = $service->reabrir($demanda->fresh(), $gestor->id);

        $this->assertSame(StatusDemanda::EM_PROGRESSO, $reaberta->status);
        $this->assertNull($reaberta->resolvido_em);
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'reopened']);
    }

    public function test_reabrir_demanda_que_nao_esta_resolvida_e_proibido(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($gestor);

        $this->expectException(TransicaoProibidaException::class);
        app(DemandaStatusService::class)->reabrir($demanda, $gestor->id);
    }

    public function test_alterar_para_em_andamento_assume_a_demanda(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($this->solicitante());

        $resultado = app(DemandaStatusService::class)->alterarStatus($demanda, StatusDemanda::EM_PROGRESSO, $gestor->id);

        $this->assertSame(StatusDemanda::EM_PROGRESSO, $resultado->status);
        $this->assertSame($gestor->id, $resultado->atribuido_para_id);
    }

    public function test_alterar_status_para_resolvida_exige_o_fluxo_de_resolucao(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($gestor, ['atribuido_para_id' => $gestor->id]);

        $this->expectException(TransicaoProibidaException::class);
        app(DemandaStatusService::class)->alterarStatus($demanda, StatusDemanda::RESOLVIDA, $gestor->id);
    }

    private function travarRelogio(string $quando): void
    {
        $this->travel(0);
        \Illuminate\Support\Carbon::setTestNow($quando);
        CarbonImmutable::setTestNow($quando);
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaStatusServiceTest.php`
Expected: FAIL ("Class ... ResolucaoDemandaData not found").

- [ ] **Step 3: DTO e FormRequest**

`app/Modules/Demandas/DTOs/ResolucaoDemandaData.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Requests\ResolverDemandaRequest;
use Carbon\CarbonImmutable;

final readonly class ResolucaoDemandaData
{
    public function __construct(
        public CarbonImmutable $abertaEm,
        public CarbonImmutable $resolvidaEm,
    ) {}

    public static function fromRequest(ResolverDemandaRequest $request): self
    {
        $data = $request->validated();

        return new self(
            CarbonImmutable::parse($data['aberta_em']),
            CarbonImmutable::parse($data['resolvida_em']),
        );
    }
}
```

`app/Modules/Demandas/Requests/ResolverDemandaRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use App\Modules\Demandas\Models\Demanda;
use Illuminate\Foundation\Http\FormRequest;

class ResolverDemandaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demanda = Demanda::find($this->route('id'));

        return $demanda !== null && $this->user()->can('resolver', $demanda);
    }

    public function rules(): array
    {
        return [
            'aberta_em' => ['required', 'date', 'before_or_equal:resolvida_em', 'before_or_equal:now'],
            'resolvida_em' => ['required', 'date', 'after_or_equal:aberta_em', 'before_or_equal:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'aberta_em.before_or_equal' => 'A data não pode ser posterior ao fechamento nem ao momento atual.',
            'resolvida_em.after_or_equal' => 'A data não pode ser anterior à abertura.',
            'resolvida_em.before_or_equal' => 'A data de fechamento não pode estar no futuro.',
        ];
    }
}
```

- [ ] **Step 4: Service de status**

`app/Modules/Demandas/Services/DemandaStatusService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Demandas\Domain\Workflows\DemandaWorkflow;
use App\Modules\Demandas\DTOs\ResolucaoDemandaData;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use Illuminate\Support\Facades\DB;

/**
 * Mudancas de status pedidas pela tela. Cada metodo e uma acao do usuario e
 * grava UMA linha de historico, por mais passos que o workflow percorra.
 */
final class DemandaStatusService
{
    public function __construct(
        private readonly DemandaWorkflow $workflow,
        private readonly HistoricoDemanda $historico,
    ) {}

    public function alterarStatus(Demanda $demanda, StatusDemanda $alvo, int $userId): Demanda
    {
        if ($alvo === StatusDemanda::RESOLVIDA) {
            throw new TransicaoProibidaException('Use "Resolver chamado" para concluir, informando as datas.');
        }

        return DB::transaction(function () use ($demanda, $alvo, $userId): Demanda {
            if ($alvo === StatusDemanda::EM_PROGRESSO) {
                $demanda = $this->assumirSeSemResponsavel($demanda, $userId);
            }
            $resultado = $this->workflow->conduzirAte($demanda, $alvo, $userId);
            $this->historico->registrar(
                $resultado, $userId, AcaoHistoricoDemanda::STATUS_ALTERADO,
                'Status alterado manualmente para: '.$alvo->etapa()->label().'.',
                campo: 'status', novo: $alvo->value,
            );

            return $resultado;
        });
    }

    public function resolver(Demanda $demanda, ResolucaoDemandaData $dados, int $userId): Demanda
    {
        return DB::transaction(function () use ($demanda, $dados, $userId): Demanda {
            $demanda = Demanda::query()->lockForUpdate()->findOrFail($demanda->getKey());

            $aberturaAnterior = $demanda->created_at;
            if (! $aberturaAnterior->equalTo($dados->abertaEm)) {
                $demanda->created_at = $dados->abertaEm;
                $demanda->saveQuietly();
                $this->historico->registrar(
                    $demanda, $userId, AcaoHistoricoDemanda::EDITADA, 'Data de abertura ajustada na resolução.',
                    campo: 'created_at', anterior: $aberturaAnterior->toIso8601String(), novo: $dados->abertaEm->toIso8601String(),
                );
            }

            $demanda = $this->assumirSeSemResponsavel($demanda, $userId);
            $resolvida = $this->workflow->conduzirAte($demanda, StatusDemanda::RESOLVIDA, $userId, $dados->resolvidaEm);
            $this->historico->registrar(
                $resolvida, $userId, AcaoHistoricoDemanda::RESOLVIDA,
                'Chamado resolvido em '.$dados->resolvidaEm->format('d/m/Y H:i').'.',
            );

            return $resolvida;
        });
    }

    public function reabrir(Demanda $demanda, int $userId): Demanda
    {
        if ($demanda->status !== StatusDemanda::RESOLVIDA) {
            throw new TransicaoProibidaException('Só é possível reabrir uma demanda concluída.');
        }

        return DB::transaction(function () use ($demanda, $userId): Demanda {
            $reaberta = $this->workflow->transitar($demanda, StatusDemanda::EM_PROGRESSO, $userId);
            $this->historico->registrar(
                $reaberta, $userId, AcaoHistoricoDemanda::REABERTA,
                'Chamado REABERTO. Status alterado para: Em andamento.',
            );

            return $reaberta;
        });
    }

    public function assumirSeSemResponsavel(Demanda $demanda, int $userId): Demanda
    {
        if ($demanda->atribuido_para_id !== null) {
            return $demanda;
        }
        $demanda->atribuido_para_id = $userId;
        $demanda->save();
        $this->historico->registrar(
            $demanda, $userId, AcaoHistoricoDemanda::ATRIBUIDA, 'Responsável definido ao iniciar o atendimento.',
            campo: 'atribuido_para_id', novo: (string) $userId,
        );

        return $demanda;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaStatusServiceTest.php`
Expected: 5 PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Demandas/Services/DemandaStatusService.php app/Modules/Demandas/DTOs/ResolucaoDemandaData.php app/Modules/Demandas/Requests/ResolverDemandaRequest.php
git commit -m "✨ feat(demandas): resolver com datas, reabrir e mudar etapa"
```

---

### Task 6: Comentario inicia atendimento e transferencia registra historico

**Files:**
- Modify: `app/Modules/Demandas/Services/DemandaInteractionService.php`
- Test: `tests/Feature/Demandas/DemandaInteractionServiceTest.php`

**Interfaces:**
- Consumes: `DemandaStatusService::assumirSeSemResponsavel`, `DemandaWorkflow::conduzirAte`, `HistoricoDemanda::registrar`.
- Produces: `comentar(Demanda, int $autorId, string $conteudo, bool $interno): DemandaComentario` (assinatura inalterada); `atribuir(Demanda, int $responsavelId, int $autorId): void` (ganha `$autorId`).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Services\DemandaInteractionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaInteractionServiceTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    public function test_primeiro_comentario_de_terceiro_inicia_atendimento(): void
    {
        $solicitante = $this->solicitante();
        $gestor = $this->gestor();
        $demanda = $this->demanda($solicitante);

        app(DemandaInteractionService::class)->comentar($demanda, $gestor->id, 'Vou verificar.', false);

        $demanda->refresh();
        $this->assertSame(StatusDemanda::EM_PROGRESSO, $demanda->status);
        $this->assertSame($gestor->id, $demanda->atribuido_para_id);
        $this->assertNotNull($demanda->primeira_resposta_em);
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'status_changed']);
    }

    public function test_comentario_do_proprio_solicitante_nao_muda_nada(): void
    {
        $solicitante = $this->solicitante();
        $demanda = $this->demanda($solicitante);

        app(DemandaInteractionService::class)->comentar($demanda, $solicitante->id, 'Alguma novidade?', false);

        $demanda->refresh();
        $this->assertSame(StatusDemanda::ABERTA, $demanda->status);
        $this->assertNull($demanda->atribuido_para_id);
        $this->assertNull($demanda->primeira_resposta_em);
    }

    public function test_comentario_interno_nao_inicia_atendimento(): void
    {
        $demanda = $this->demanda($this->solicitante());

        app(DemandaInteractionService::class)->comentar($demanda, $this->gestor()->id, 'nota interna', true);

        $this->assertSame(StatusDemanda::ABERTA, $demanda->fresh()->status);
    }

    public function test_transferencia_registra_historico_e_ignora_mesmo_responsavel(): void
    {
        $gestor = $this->gestor();
        $outro = $this->gestor();
        $demanda = $this->demanda($gestor, ['atribuido_para_id' => $gestor->id]);
        $service = app(DemandaInteractionService::class);

        $service->atribuir($demanda, $gestor->id, $gestor->id);
        $this->assertDatabaseMissing('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'assigned']);

        $service->atribuir($demanda, $outro->id, $gestor->id);
        $this->assertSame($outro->id, $demanda->fresh()->atribuido_para_id);
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'assigned', 'valor_novo' => (string) $outro->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaInteractionServiceTest.php`
Expected: FAIL (status continua `aberta`; `atribuir` com 3 argumentos).

- [ ] **Step 3: Implementar**

Substituir `DemandaInteractionService.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Domain\Events\ComentarioAdicionadoV1;
use App\Modules\Demandas\Domain\Workflows\DemandaWorkflow;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaComentario;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DemandaInteractionService
{
    public function __construct(
        private readonly DemandaWorkflow $workflow,
        private readonly DemandaStatusService $status,
        private readonly HistoricoDemanda $historico,
    ) {}

    /**
     * Regra do legado: a primeira resposta publica de quem nao e o solicitante
     * tira a demanda de "Aberto". Aqui ela tambem assume o atendimento, porque o
     * dominio nao aceita demanda em andamento sem responsavel.
     */
    public function comentar(Demanda $demanda, int $autorId, string $conteudo, bool $interno): DemandaComentario
    {
        return DB::transaction(function () use ($demanda, $autorId, $conteudo, $interno): DemandaComentario {
            $comentario = $demanda->comments()->create([
                'user_id' => $autorId,
                'tipo' => 'comentario',
                'conteudo' => $conteudo,
                'interno' => $interno,
            ]);

            $respostaDeTerceiro = ! $interno && $autorId !== (int) $demanda->solicitante_id;
            if ($respostaDeTerceiro && $demanda->primeira_resposta_em === null) {
                $demanda->primeira_resposta_em = now();
                $demanda->save();
            }

            if ($respostaDeTerceiro && $demanda->status === StatusDemanda::ABERTA) {
                $demanda = $this->status->assumirSeSemResponsavel($demanda, $autorId);
                $demanda = $this->workflow->conduzirAte($demanda, StatusDemanda::EM_PROGRESSO, $autorId);
                $this->historico->registrar(
                    $demanda, $autorId, AcaoHistoricoDemanda::STATUS_ALTERADO,
                    'Alteração de Status Automática: primeiro comentário levou a demanda para Em andamento.',
                    ['automatico' => true], campo: 'status', novo: StatusDemanda::EM_PROGRESSO->value,
                );
            }

            DB::afterCommit(static fn () => event(ComentarioAdicionadoV1::create(
                $demanda->id,
                $comentario->id,
                $autorId,
                $interno,
            )));

            return $comentario;
        });
    }

    public function atribuir(Demanda $demanda, int $responsavelId, int $autorId): void
    {
        $anterior = $demanda->atribuido_para_id;
        if ($anterior !== null && (int) $anterior === $responsavelId) {
            return;
        }

        DB::transaction(function () use ($demanda, $responsavelId, $autorId, $anterior): void {
            $demanda->atribuido_para_id = $responsavelId;
            $demanda->save();

            $nomes = User::query()->whereIn('id', array_filter([$anterior, $responsavelId]))->pluck('name', 'id');
            $this->historico->registrar(
                $demanda, $autorId, AcaoHistoricoDemanda::ATRIBUIDA,
                sprintf('Responsável alterado de %s para %s.', $nomes[$anterior] ?? 'ninguém', $nomes[$responsavelId] ?? '#'.$responsavelId),
                campo: 'atribuido_para_id', anterior: $anterior === null ? null : (string) $anterior, novo: (string) $responsavelId,
            );
        });
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaInteractionServiceTest.php`
Expected: 4 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Demandas/Services/DemandaInteractionService.php
git commit -m "✨ feat(demandas): primeiro comentario inicia atendimento e transferencia no historico"
```

---

### Task 7: Anexos com tipos do legado e disk configuravel

**Files:**
- Create: `config/demandas.php`
- Create: `app/Modules/Demandas/Services/DemandaAnexoService.php`
- Create: `app/Modules/Demandas/Requests/AnexoDemandaRequest.php`
- Test: `tests/Feature/Demandas/DemandaAnexoServiceTest.php`

**Interfaces:**
- Produces:
  - `config('demandas.anexos.disk')` (default `local`), `config('demandas.anexos.mimes')`, `config('demandas.anexos.max_kb')` (10240).
  - `DemandaAnexoService::anexar(Demanda $demanda, UploadedFile $arquivo, int $userId): DemandaAnexo`.
  - `DemandaAnexoService::baixar(Demanda $demanda, DemandaAnexo $anexo): StreamedResponse` (404 se de outra demanda ou ausente).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Modules\Demandas\Services\DemandaAnexoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaAnexoServiceTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    public function test_anexar_grava_arquivo_registro_e_historico(): void
    {
        Storage::fake('local');
        $user = $this->gestor();
        $demanda = $this->demanda($user);

        $anexo = app(DemandaAnexoService::class)->anexar($demanda, UploadedFile::fake()->create('planilha.csv', 10, 'text/csv'), $user->id);

        Storage::disk('local')->assertExists($anexo->path);
        $this->assertStringStartsWith('demandas/'.$demanda->id.'/', $anexo->path);
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'attachment_added']);
    }

    public function test_baixar_anexo_de_outra_demanda_e_404(): void
    {
        Storage::fake('local');
        $user = $this->gestor();
        $a = $this->demanda($user);
        $b = $this->demanda($user);
        $anexo = app(DemandaAnexoService::class)->anexar($a, UploadedFile::fake()->create('x.pdf', 5, 'application/pdf'), $user->id);

        $this->expectException(NotFoundHttpException::class);
        app(DemandaAnexoService::class)->baixar($b, $anexo);
    }

    public function test_regras_do_request_seguem_o_legado(): void
    {
        $this->assertSame(['png', 'jpg', 'jpeg', 'pdf', 'xlsx', 'xls', 'csv', 'txt'], config('demandas.anexos.mimes'));
        $this->assertSame(10240, config('demandas.anexos.max_kb'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaAnexoServiceTest.php`
Expected: FAIL ("Class ... DemandaAnexoService not found").

- [ ] **Step 3: Implementar**

`config/demandas.php`:

```php
<?php

declare(strict_types=1);

return [
    'anexos' => [
        'disk' => env('DEMANDAS_ANEXOS_DISK', 'local'),
        // Mesma lista do cedec-demanda (AnexoRequest), mais jpeg.
        'mimes' => ['png', 'jpg', 'jpeg', 'pdf', 'xlsx', 'xls', 'csv', 'txt'],
        'max_kb' => 10240,
    ],

    'importacao' => [
        'conexao' => env('DEMANDAS_LEGADO_CONEXAO', 'cedec_demanda_legacy'),
        'disk' => env('DEMANDAS_LEGADO_DISK', 'legado_demandas'),
        'lote' => 200,
    ],

    'automacao' => [
        'timeout_segundos' => 15,
        'tentativas' => 3,
        'backoff_segundos' => [10, 30],
    ],
];
```

`app/Modules/Demandas/Requests/AnexoDemandaRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use App\Modules\Demandas\Models\Demanda;
use Illuminate\Foundation\Http\FormRequest;

class AnexoDemandaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demanda = Demanda::find($this->route('id'));

        return $demanda !== null && $this->user()->can('comment', $demanda);
    }

    public function rules(): array
    {
        return [
            'arquivo' => [
                'required', 'file',
                'max:'.config('demandas.anexos.max_kb'),
                'mimes:'.implode(',', config('demandas.anexos.mimes')),
            ],
        ];
    }
}
```

`app/Modules/Demandas/Services/DemandaAnexoService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAnexo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class DemandaAnexoService
{
    public function __construct(private readonly HistoricoDemanda $historico) {}

    public function anexar(Demanda $demanda, UploadedFile $arquivo, int $userId): DemandaAnexo
    {
        $disk = (string) config('demandas.anexos.disk');
        $path = $arquivo->store('demandas/'.$demanda->id, $disk);
        abort_if($path === false, 500, 'Falha ao armazenar o anexo.');

        try {
            return DB::transaction(function () use ($demanda, $arquivo, $userId, $path): DemandaAnexo {
                $anexo = $demanda->attachments()->create([
                    'user_id' => $userId,
                    'nome_original' => $arquivo->getClientOriginalName(),
                    'nome_arquivo' => basename($path),
                    'mime_type' => $arquivo->getMimeType(),
                    'tamanho_bytes' => $arquivo->getSize(),
                    'path' => $path,
                ]);
                $this->historico->registrar(
                    $demanda, $userId, AcaoHistoricoDemanda::ANEXO_ADICIONADO,
                    'Arquivo anexado: '.$arquivo->getClientOriginalName().'.',
                );

                return $anexo;
            });
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }
    }

    public function baixar(Demanda $demanda, DemandaAnexo $anexo): StreamedResponse
    {
        $disk = Storage::disk((string) config('demandas.anexos.disk'));
        abort_unless((int) $anexo->task_id === (int) $demanda->id, 404);
        abort_unless($disk->exists($anexo->path), 404);

        return $disk->download($anexo->path, $anexo->nome_original);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaAnexoServiceTest.php`
Expected: 3 PASS.

- [ ] **Step 5: Commit**

```bash
git add config/demandas.php app/Modules/Demandas/Services/DemandaAnexoService.php app/Modules/Demandas/Requests/AnexoDemandaRequest.php
git commit -m "✨ feat(demandas): anexos com tipos do legado e historico"
```

---

### Task 8: Visibilidade, filtros e permissoes

**Files:**
- Modify: `app/Policies/DemandaPolicy.php`
- Modify: `app/Modules/Demandas/DTOs/FiltroDemanda.php`
- Modify: `app/Modules/Demandas/Domain/Contracts/DemandaRepository.php`
- Modify: `app/Modules/Demandas/Infrastructure/Persistence/EloquentDemandaRepository.php`
- Modify: `config/permissions.php`
- Modify: `database/seeders/DemandasPermissionsSeeder.php`
- Test: `tests/Feature/Demandas/DemandaVisibilidadeTest.php`

**Interfaces:**
- Produces:
  - Slugs novos: `demandas.chamados.resolver`, `demandas.chamados.automatizar`, `demandas.dashboard.view`.
  - `DemandaPolicy::view` inclui criador; novos `resolver(User, Demanda)`, `automatizar(User, Demanda)`; `update` inclui solicitante (regra R6).
  - `FiltroDemanda` campos: `search, etapa, status, prioridade (PrioridadeSimples value), assuntoId, solicitanteId, responsavelId, criadoPorId, dataInicial, dataFinal`; `toArray()` com chaves `search, etapa, status, prioridade, assunto_id, solicitante_id, responsavel_id, criado_por_id, data_inicial, data_final`.
  - `DemandaRepository::getStatistics(int $viewerId, bool $manage): array{total:int, abertas:int, em_andamento:int, concluidas:int, resolvidas_hoje:int}`.
  - `EloquentDemandaRepository::escopoVisivel(Builder $q, int $viewerId, bool $manage): Builder` (publico, reusado pelo dashboard e pelo CSV).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\Enums\StatusDemanda;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaVisibilidadeTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    public function test_criador_ve_a_demanda_que_abriu_para_outra_pessoa(): void
    {
        $criador = $this->solicitante();
        $demanda = $this->demanda($this->solicitante(), ['criado_por_id' => $criador->id]);

        $this->assertTrue($criador->can('view', $demanda));
    }

    public function test_terceiro_sem_gestao_nao_ve_nem_baixa_anexo(): void
    {
        $terceiro = $this->solicitante();
        $demanda = $this->demanda($this->solicitante());

        $this->actingAs($terceiro)->get('/demandas/'.$demanda->id)->assertForbidden();
        $this->actingAs($terceiro)->get('/demandas/'.$demanda->id.'/anexos/999999')->assertForbidden();
    }

    public function test_filtro_por_etapa_e_intervalo_de_datas(): void
    {
        $gestor = $this->gestor();
        $this->demanda($gestor, ['titulo' => 'TST- em andamento', 'status' => StatusDemanda::EM_PROGRESSO, 'atribuido_para_id' => $gestor->id]);
        $this->demanda($gestor, ['titulo' => 'TST- aberta']);

        $pagina = app(DemandaRepository::class)->paginate(
            ['etapa' => 'em_andamento', 'search' => 'TST-', 'data_inicial' => now()->toDateString()], 50, $gestor->id, true
        );

        $titulos = collect($pagina->items())->pluck('titulo');
        $this->assertContains('TST- em andamento', $titulos);
        $this->assertNotContains('TST- aberta', $titulos);
    }

    public function test_estatisticas_tem_resolvidas_hoje(): void
    {
        $gestor = $this->gestor();
        $this->demanda($gestor, ['status' => StatusDemanda::RESOLVIDA, 'resolvido_em' => now(), 'atribuido_para_id' => $gestor->id]);

        $stats = app(DemandaRepository::class)->getStatistics($gestor->id, false);

        $this->assertSame(1, $stats['resolvidas_hoje']);
        $this->assertSame(1, $stats['concluidas']);
    }

    public function test_config_de_permissoes_tem_os_slugs_novos(): void
    {
        $modulo = config('permissions.modules.DEMANDAS');
        $this->assertSame('demandas.chamados.resolver', $modulo['Chamados']['resolver']);
        $this->assertSame('demandas.chamados.automatizar', $modulo['Chamados']['automatizar']);
        $this->assertSame('demandas.dashboard.view', $modulo['Dashboard']['view']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaVisibilidadeTest.php`
Expected: FAIL (criador sem acesso; `etapa` ignorado; chave `resolvidas_hoje` ausente).

- [ ] **Step 3: Permissoes e seeder**

Em `config/permissions.php`, bloco `'DEMANDAS'`:

```php
        'DEMANDAS' => [
            'Chamados' => [
                'view' => 'demandas.chamados.view',
                'create' => 'demandas.chamados.create',
                'edit' => 'demandas.chamados.edit',
                'delete' => 'demandas.chamados.delete',
                'export' => 'demandas.chamados.export',
                'manage' => 'demandas.chamados.manage',
                'resolver' => 'demandas.chamados.resolver',
                'automatizar' => 'demandas.chamados.automatizar',
            ],
            'Dashboard' => [
                'view' => 'demandas.dashboard.view',
            ],
        ],
```
Nas `role_permissions`, onde o papel ja tem `demandas.chamados.manage` listado explicitamente (linhas ~712-716), acrescentar `'demandas.chamados.resolver', 'demandas.chamados.automatizar', 'demandas.dashboard.view',`. Papeis com `'demandas.*'` ja cobrem. Conferir com:

```bash
bash /c/tmp/teste-demandas.sh php artisan permissions:audit 2>&1 | grep -i demandas
```
(se o comando tiver outro nome, `php artisan list | grep -i permission` e usar o de `AuditPermissionsCommand`).

Substituir o corpo de `DemandasPermissionsSeeder::run()` para ler a fonte unica:

```php
    public function run(): void
    {
        // Fonte unica: config/permissions.php. Os slugs antigos (demandas.view-own,
        // demandas.manage...) nao sao mais usados por rota nenhuma.
        foreach (config('permissions.modules.DEMANDAS', []) as $acoes) {
            foreach ($acoes as $slug) {
                Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
            }
        }
    }
```
(remover `use Spatie\Permission\Models\Role;` se ficar sem uso.)

- [ ] **Step 4: Policy**

Em `DemandaPolicy.php`:

```php
    public function view(User $user, Demanda $demanda): bool
    {
        return $this->viewAny($user) && (
            $user->can('demandas.chamados.manage') || $this->envolvido($user, $demanda)
        );
    }

    public function update(User $user, Demanda $demanda): bool
    {
        if ($user->can('demandas.chamados.manage')) {
            return true;
        }

        return $user->can('demandas.chamados.edit') && (
            (int) $demanda->atribuido_para_id === (int) $user->id
            || (int) $demanda->solicitante_id === (int) $user->id
        );
    }

    public function resolver(User $user, Demanda $demanda): bool
    {
        return $user->can('demandas.chamados.resolver') && (
            $user->can('demandas.chamados.manage') || (int) $demanda->atribuido_para_id === (int) $user->id
        );
    }

    public function automatizar(User $user, Demanda $demanda): bool
    {
        return $user->can('demandas.chamados.automatizar') && $this->view($user, $demanda);
    }

    private function envolvido(User $user, Demanda $demanda): bool
    {
        $id = (int) $user->id;

        return (int) $demanda->solicitante_id === $id
            || (int) $demanda->atribuido_para_id === $id
            || (int) $demanda->criado_por_id === $id;
    }
```

- [ ] **Step 5: Filtro e repositorio**

Substituir `FiltroDemanda.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\DTOs;

use App\Modules\Demandas\Enums\EtapaDemanda;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\StatusDemanda;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class FiltroDemanda
{
    public function __construct(
        public ?string $search = null,
        public ?string $etapa = null,
        public ?string $status = null,
        public ?string $prioridade = null,
        public ?int $assuntoId = null,
        public ?int $solicitanteId = null,
        public ?int $responsavelId = null,
        public ?int $criadoPorId = null,
        public ?string $dataInicial = null,
        public ?string $dataFinal = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'etapa' => ['nullable', Rule::enum(EtapaDemanda::class)],
            'status' => ['nullable', Rule::enum(StatusDemanda::class)],
            'prioridade' => ['nullable', Rule::enum(PrioridadeSimples::class)],
            'assunto_id' => ['nullable', 'integer'],
            'solicitante_id' => ['nullable', 'integer'],
            'responsavel_id' => ['nullable', 'integer'],
            'criado_por_id' => ['nullable', 'integer'],
            'data_inicial' => ['nullable', 'date'],
            'data_final' => ['nullable', 'date', 'after_or_equal:data_inicial'],
        ]);
        $int = static fn (string $k): ?int => isset($data[$k]) ? (int) $data[$k] : null;

        return new self(
            search: $data['search'] ?? null,
            etapa: $data['etapa'] ?? null,
            status: $data['status'] ?? null,
            prioridade: $data['prioridade'] ?? null,
            assuntoId: $int('assunto_id'),
            solicitanteId: $int('solicitante_id'),
            responsavelId: $int('responsavel_id'),
            criadoPorId: $int('criado_por_id'),
            dataInicial: $data['data_inicial'] ?? null,
            dataFinal: $data['data_final'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'etapa' => $this->etapa,
            'status' => $this->status,
            'prioridade' => $this->prioridade,
            'assunto_id' => $this->assuntoId,
            'solicitante_id' => $this->solicitanteId,
            'responsavel_id' => $this->responsavelId,
            'criado_por_id' => $this->criadoPorId,
            'data_inicial' => $this->dataInicial,
            'data_final' => $this->dataFinal,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
```

Em `EloquentDemandaRepository.php`, substituir `paginate` e `getStatistics` e acrescentar `escopoVisivel` e `aplicarFiltros`:

```php
    public function escopoVisivel(Builder $query, int $viewerId, bool $manage): Builder
    {
        if ($manage) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($viewerId): void {
            $q->where('solicitante_id', $viewerId)
                ->orWhere('atribuido_para_id', $viewerId)
                ->orWhere('criado_por_id', $viewerId);
        });
    }

    public function aplicarFiltros(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $termo = '%'.$filters['search'].'%';
            $query->where(function (Builder $q) use ($termo): void {
                $q->where('titulo', 'ilike', $termo)
                    ->orWhere('protocolo', 'ilike', $termo)
                    ->orWhere('descricao', 'ilike', $termo);
            });
        }
        if (! empty($filters['etapa'])) {
            $query->whereIn('status', array_map(
                fn (StatusDemanda $s): string => $s->value,
                EtapaDemanda::from($filters['etapa'])->status(),
            ));
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['prioridade'])) {
            $valores = array_values(array_filter(
                array_map(fn (Prioridade $p): ?int => PrioridadeSimples::dePrioridade($p)->value === $filters['prioridade'] ? $p->value : null, Prioridade::cases())
            ));
            $query->whereIn('prioridade', $valores);
        }
        foreach (['assunto_id' => 'assunto_id', 'solicitante_id' => 'solicitante_id', 'responsavel_id' => 'atribuido_para_id', 'criado_por_id' => 'criado_por_id'] as $chave => $coluna) {
            if (! empty($filters[$chave])) {
                $query->where($coluna, (int) $filters[$chave]);
            }
        }
        if (! empty($filters['data_inicial'])) {
            $query->whereDate('created_at', '>=', $filters['data_inicial']);
        }
        if (! empty($filters['data_final'])) {
            $query->whereDate('created_at', '<=', $filters['data_final']);
        }

        return $query;
    }

    public function paginate(array $filters, int $perPage, int $viewerId, bool $manage, ?int $page = null): LengthAwarePaginator
    {
        $query = Demanda::query()->with(['solicitante:id,name', 'atribuidoPara:id,name', 'criadoPor:id,name', 'assunto:id,nome,categoria_id', 'assunto.categoria:id,nome']);
        $this->escopoVisivel($query, $viewerId, $manage);
        $this->aplicarFiltros($query, $filters);

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page)->withQueryString();
    }

    public function getStatistics(int $viewerId, bool $manage): array
    {
        $query = $this->escopoVisivel(Demanda::query(), $viewerId, $manage);
        $emLista = static fn (EtapaDemanda $e): string => "'".implode("','", array_map(fn (StatusDemanda $s) => $s->value, $e->status()))."'";

        $row = $query
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN ('.$emLista(EtapaDemanda::ABERTO).') THEN 1 ELSE 0 END) as abertas')
            ->selectRaw('SUM(CASE WHEN status IN ('.$emLista(EtapaDemanda::EM_ANDAMENTO).') THEN 1 ELSE 0 END) as em_andamento')
            ->selectRaw('SUM(CASE WHEN status IN ('.$emLista(EtapaDemanda::CONCLUIDO).') THEN 1 ELSE 0 END) as concluidas')
            ->selectRaw('SUM(CASE WHEN resolvido_em >= ? THEN 1 ELSE 0 END) as resolvidas_hoje', [now()->startOfDay()])
            ->first();

        return [
            'total' => (int) $row->total,
            'abertas' => (int) $row->abertas,
            'em_andamento' => (int) $row->em_andamento,
            'concluidas' => (int) $row->concluidas,
            'resolvidas_hoje' => (int) $row->resolvidas_hoje,
        ];
    }
```
Imports: `Illuminate\Database\Eloquent\Builder`, `App\Modules\Demandas\Enums\{EtapaDemanda,Prioridade,PrioridadeSimples,StatusDemanda}`. Em `findById`, acrescentar `'criadoPor'`, `'assunto.categoria'` ao `with`. Em `DemandaRepository` (contrato), atualizar o docblock de `getStatistics` para o novo array shape; assinaturas sem mudanca.

- [ ] **Step 6: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaVisibilidadeTest.php`
Expected: 5 PASS. (`test_terceiro_sem_gestao...` pode depender da Task 9 para a rota de anexo; se o download retornar 404 antes do 403, mantenha o teste falhando e confirme na Task 9 — o controller passa a autorizar antes de buscar o anexo.)

- [ ] **Step 7: Commit**

```bash
git add app/Policies/DemandaPolicy.php app/Modules/Demandas/DTOs/FiltroDemanda.php app/Modules/Demandas/Domain/Contracts/DemandaRepository.php app/Modules/Demandas/Infrastructure/Persistence/EloquentDemandaRepository.php config/permissions.php database/seeders/DemandasPermissionsSeeder.php
git commit -m "🔒 security(demandas): visibilidade do criador, filtros e slugs de resolver e automatizar"
```

---

### Task 9: HTTP — controller fino, rotas novas e tempo real

**Files:**
- Modify: `app/Modules/Demandas/Controllers/DemandaController.php`
- Modify: `routes/modules/demandas.php`
- Create: `app/Modules/Demandas/Observers/DemandaTempoRealObserver.php`
- Modify: `app/Modules/Demandas/Observers/DemandaNotificacaoObserver.php`
- Modify: `app/Modules/Shared/Support/CanaisDeListagem.php`
- Modify: `app/Modules/Demandas/DemandasServiceProvider.php`
- Test: `tests/Feature/Demandas/DemandaHttpTest.php`

**Interfaces:**
- Consumes: Tasks 3–8.
- Produces (props Inertia usados pelo frontend):
  - `Demandas/DemandasIndex`: `demandas` (paginator; cada item com `id, protocolo, titulo, status, etapa, etapa_label, prioridade_simples, prioridade_label, created_at, resolvido_em, assunto{id,nome,categoria{nome}}, solicitante{id,name}, atribuido_para{id,name}, criado_por{id,name}, legado_id`), `estatisticas` (Task 8 shape), `filtros`, `opcoes{etapas, prioridades, assuntos[{value,label}], usuarios[{value,label}]}`, `pode{criar, exportar, gerir}`.
  - `Demandas/DemandasShow`: `demanda` (item acima + `descricao, campos_customizados, primeira_resposta_em, prazo_resolucao, sla_resolucao_violado`), `campos` (campos_dinamicos do assunto), `comentarios[{id, conteudo, interno, created_at, autor}]`, `historico[{id, rotulo, detalhes, created_at, autor}]`, `anexos[{id, nome_original, tamanho_bytes, created_at, autor, url}]`, `assuntos[{value,label}]`, `usuarios[{value,label}]`, `automacao{disponivel:bool, acao:?string}`, `pode{editar, gerir, resolver, reabrir, automatizar, comentarInterno}`.
  - Rotas novas: `demandas.resolver` POST `/demandas/{id}/resolver`, `demandas.reabrir` POST `/demandas/{id}/reabrir`, `demandas.automacao` POST `/demandas/{id}/automacao` (controller na Task 11; nesta task a rota e registrada so na Task 11).
  - Canal `listagem.demandas` com permissao `demandas.chamados.view`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Http\Middleware\VerifyCsrfToken;
use App\Modules\Demandas\Enums\StatusDemanda;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaHttpTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutVite();
    }

    public function test_index_entrega_props_da_tela(): void
    {
        $gestor = $this->gestor();
        $this->demanda($gestor, ['titulo' => 'TST- listagem']);

        $this->actingAs($gestor)->get('/demandas?search=TST-')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Demandas/DemandasIndex')
                ->has('demandas.data.0', fn (Assert $item) => $item
                    ->where('etapa', 'aberto')->where('etapa_label', 'Aberto')->etc())
                ->has('estatisticas.resolvidas_hoje')
                ->has('opcoes.etapas', 4)
                ->where('pode.gerir', true));
    }

    public function test_store_cria_e_redireciona_para_o_detalhe(): void
    {
        $user = $this->solicitante();
        $assunto = $this->assunto([['label' => 'Login AD', 'tipo' => 'text']]);

        $resposta = $this->actingAs($user)->post('/demandas', [
            'titulo' => 'TST- via http', 'descricao' => 'x', 'assunto_id' => $assunto->id,
            'prioridade_simples' => 'alta', 'campos_customizados' => ['Login AD' => 'M1'],
        ]);

        $resposta->assertRedirect();
        $this->assertDatabaseHas('tasks', ['titulo' => 'TST- via http', 'criado_por_id' => $user->id]);
    }

    public function test_show_esconde_comentario_interno_do_solicitante(): void
    {
        $solicitante = $this->solicitante();
        $demanda = $this->demanda($solicitante);
        $demanda->comments()->create(['user_id' => $this->gestor()->id, 'tipo' => 'comentario', 'conteudo' => 'segredo', 'interno' => true]);

        $this->actingAs($solicitante)->get('/demandas/'.$demanda->id)
            ->assertInertia(fn (Assert $page) => $page->component('Demandas/DemandasShow')->has('comentarios', 0));
    }

    public function test_resolver_via_http_valida_datas(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($gestor, ['atribuido_para_id' => $gestor->id]);

        $this->actingAs($gestor)->post("/demandas/{$demanda->id}/resolver", [
            'aberta_em' => now()->toDateTimeString(), 'resolvida_em' => now()->subDay()->toDateTimeString(),
        ])->assertSessionHasErrors('aberta_em');

        $this->actingAs($gestor)->post("/demandas/{$demanda->id}/resolver", [
            'aberta_em' => now()->subDay()->toDateTimeString(), 'resolvida_em' => now()->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(StatusDemanda::RESOLVIDA, $demanda->fresh()->status);

        $this->actingAs($gestor)->post("/demandas/{$demanda->id}/reabrir")->assertSessionHasNoErrors();
        $this->assertSame(StatusDemanda::EM_PROGRESSO, $demanda->fresh()->status);
    }

    public function test_transicao_invalida_volta_com_erro_e_nao_500(): void
    {
        $gestor = $this->gestor();
        $demanda = $this->demanda($gestor, ['status' => StatusDemanda::CANCELADA]);

        $this->actingAs($gestor)->post("/admin/demandas/{$demanda->id}/status", ['status' => 'em_progresso'])
            ->assertSessionHasErrors('status');
    }

    public function test_autosave_da_descricao(): void
    {
        $solicitante = $this->usuarioCom([...self::PERMISSOES_SOLICITANTE, 'demandas.chamados.edit']);
        $demanda = $this->demanda($solicitante);

        $this->actingAs($solicitante)->put("/admin/demandas/{$demanda->id}", ['descricao' => 'nova descricao'])
            ->assertSessionHasNoErrors();

        $this->assertSame('nova descricao', $demanda->fresh()->descricao);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaHttpTest.php`
Expected: FAIL (props `demandas`/`etapa` ausentes; rota `/resolver` 404).

- [ ] **Step 3: Controller**

Substituir `DemandaController.php` por:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\DTOs\AtualizarDemandaData;
use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\DTOs\FiltroDemanda;
use App\Modules\Demandas\DTOs\ResolucaoDemandaData;
use App\Modules\Demandas\Enums\EtapaDemanda;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAnexo;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Models\DemandaAuditLog;
use App\Modules\Demandas\Models\DemandaComentario;
use App\Modules\Demandas\Requests\AnexoDemandaRequest;
use App\Modules\Demandas\Requests\ResolverDemandaRequest;
use App\Modules\Demandas\Requests\StoreDemandaRequest;
use App\Modules\Demandas\Requests\UpdateDemandaRequest;
use App\Modules\Demandas\Services\DemandaAnexoService;
use App\Modules\Demandas\Services\DemandaCsvExporter;
use App\Modules\Demandas\Services\DemandaInteractionService;
use App\Modules\Demandas\Services\DemandaStatusService;
use App\Modules\Demandas\Services\DemandaWriteService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DemandaController extends Controller
{
    public function __construct(
        private readonly DemandaRepository $repository,
        private readonly DemandaWriteService $escrita,
        private readonly DemandaStatusService $status,
        private readonly DemandaInteractionService $interactions,
        private readonly DemandaAnexoService $anexos,
        private readonly DemandaCsvExporter $csvExporter,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Demanda::class);
        $user = $request->user();
        $gerir = $user->can('demandas.chamados.manage');
        $filtros = FiltroDemanda::fromRequest($request)->toArray();

        $demandas = $this->repository->paginate($filtros, 15, (int) $user->id, $gerir)
            ->through(fn (Demanda $d): array => $this->resumo($d));

        return Inertia::render('Demandas/DemandasIndex', [
            'demandas' => $demandas,
            'estatisticas' => $this->repository->getStatistics((int) $user->id, $gerir),
            'filtros' => $filtros,
            'opcoes' => [
                'etapas' => EtapaDemanda::options(),
                'prioridades' => PrioridadeSimples::options(),
                'assuntos' => $this->opcoesAssunto(),
                'usuarios' => $gerir ? $this->opcoesUsuario() : [],
            ],
            'pode' => [
                'criar' => $user->can('demandas.chamados.create'),
                'exportar' => $user->can('demandas.chamados.export'),
                'gerir' => $gerir,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Demanda::class);
        $gerir = $request->user()->can('demandas.chamados.manage');

        return Inertia::render('Demandas/DemandasCreate', [
            'assuntos' => DemandaAssunto::query()->where('ativo', true)->with('categoria:id,nome')->orderBy('nome')
                ->get(['id', 'nome', 'categoria_id', 'campos_dinamicos'])
                ->map(fn (DemandaAssunto $a): array => [
                    'value' => $a->id, 'label' => $a->nome,
                    'categoria' => $a->categoria?->nome, 'campos' => $a->campos_dinamicos ?? [],
                ]),
            'prioridades' => PrioridadeSimples::options(),
            'usuarios' => $gerir ? $this->opcoesUsuario() : [],
            'pode' => ['gerir' => $gerir],
        ]);
    }

    public function store(StoreDemandaRequest $request): RedirectResponse
    {
        $demanda = $this->escrita->abrir(CriarDemandaData::fromRequest($request));

        return redirect()->route('demandas.show', $demanda->id)->with('success', 'Demanda aberta: '.$demanda->protocolo);
    }

    public function show(Request $request, int $id): Response
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('view', $demanda);
        $user = $request->user();
        $gerir = $user->can('demandas.chamados.manage');
        $automacao = $demanda->assunto?->form_automacao;

        $comentarios = $demanda->comments
            ->when(! $gerir, fn ($c) => $c->where('interno', false))
            ->sortBy('created_at')->values()
            ->map(fn (DemandaComentario $c): array => [
                'id' => $c->id, 'conteudo' => $c->conteudo, 'interno' => (bool) $c->interno,
                'created_at' => $c->created_at?->toIso8601String(), 'autor' => $c->user?->name,
            ]);

        $historico = $demanda->auditLogs->sortByDesc('created_at')->values()
            ->map(fn (DemandaAuditLog $l): array => [
                'id' => $l->id, 'rotulo' => $l->metadata['rotulo'] ?? $l->acao,
                'detalhes' => $l->metadata['detalhes'] ?? null,
                'created_at' => $l->created_at?->toIso8601String(), 'autor' => $l->user?->name,
            ]);

        return Inertia::render('Demandas/DemandasShow', [
            'demanda' => array_merge($this->resumo($demanda), [
                'descricao' => $demanda->descricao,
                'campos_customizados' => $demanda->campos_customizados ?? [],
                'primeira_resposta_em' => $demanda->primeira_resposta_em?->toIso8601String(),
                'prazo_resolucao' => $demanda->prazo_resolucao?->toIso8601String(),
                'sla_resolucao_violado' => (bool) $demanda->sla_resolucao_violado,
            ]),
            'campos' => $demanda->assunto?->campos_dinamicos ?? [],
            'comentarios' => $comentarios,
            'historico' => $historico,
            'anexos' => $demanda->attachments->map(fn (DemandaAnexo $a): array => [
                'id' => $a->id, 'nome_original' => $a->nome_original, 'tamanho_bytes' => (int) $a->tamanho_bytes,
                'created_at' => $a->created_at?->toIso8601String(), 'autor' => $a->user?->name,
                'url' => route('demandas.attachments.download', [$demanda->id, $a->id]),
            ])->values(),
            'assuntos' => $this->opcoesAssunto(),
            'usuarios' => $gerir ? $this->opcoesUsuario() : [],
            'automacao' => ['disponivel' => is_array($automacao) && isset($automacao['acao']), 'acao' => $automacao['acao'] ?? null],
            'pode' => [
                'editar' => $user->can('update', $demanda),
                'gerir' => $gerir,
                'resolver' => $user->can('resolver', $demanda) && $demanda->status->isActive(),
                'reabrir' => $user->can('resolver', $demanda) && $demanda->status === StatusDemanda::RESOLVIDA,
                'automatizar' => $user->can('automatizar', $demanda),
                'comentarInterno' => $gerir,
            ],
        ]);
    }

    public function update(int $id, UpdateDemandaRequest $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->escrita->atualizar($demanda, AtualizarDemandaData::fromRequest($request), (int) $request->user()->id);

        return redirect()->back()->with('success', 'Alterações salvas.');
    }

    public function addComment(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('comment', $demanda);
        $data = $request->validate([
            'conteudo' => ['required', 'string', 'max:10000'],
            'interno' => ['sometimes', 'boolean'],
        ]);
        abort_if(($data['interno'] ?? false) && ! $request->user()->can('demandas.chamados.manage'), 403);

        try {
            $this->interactions->comentar($demanda, (int) $request->user()->id, $data['conteudo'], (bool) ($data['interno'] ?? false));
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['conteudo' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Comentário registrado.');
    }

    public function assign(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('manage', $demanda);
        $data = $request->validate(['responsavel_id' => ['required', 'integer', 'exists:users,id']]);
        $this->interactions->atribuir($demanda, (int) $data['responsavel_id'], (int) $request->user()->id);

        return redirect()->back()->with('success', 'Responsável atualizado.');
    }

    public function changeStatus(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('update', $demanda);
        $data = $request->validate(['status' => ['required', Rule::enum(StatusDemanda::class)]]);

        try {
            $this->status->alterarStatus($demanda, StatusDemanda::from($data['status']), (int) $request->user()->id);
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Status atualizado.');
    }

    public function resolver(int $id, ResolverDemandaRequest $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        try {
            $this->status->resolver($demanda, ResolucaoDemandaData::fromRequest($request), (int) $request->user()->id);
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['resolvida_em' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Chamado resolvido.');
    }

    public function reabrir(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('resolver', $demanda);
        try {
            $this->status->reabrir($demanda, (int) $request->user()->id);
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Chamado reaberto.');
    }

    public function addAttachment(int $id, AnexoDemandaRequest $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->anexos->anexar($demanda, $request->file('arquivo'), (int) $request->user()->id);

        return redirect()->back()->with('success', 'Anexo enviado.');
    }

    public function downloadAttachment(int $id, int $anexo)
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('view', $demanda);

        return $this->anexos->baixar($demanda, DemandaAnexo::findOrFail($anexo));
    }

    public function destroy(int $id): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('delete', $demanda);
        $this->repository->delete($demanda);

        return redirect()->route('demandas.index')->with('success', 'Demanda removida.');
    }

    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()->can('demandas.chamados.manage'), 403);

        return $this->index($request);
    }

    public function export(Request $request)
    {
        $this->authorize('export', Demanda::class);

        return $this->csvExporter->download(
            FiltroDemanda::fromRequest($request)->toArray(),
            (int) $request->user()->id,
            $request->user()->can('demandas.chamados.manage'),
        );
    }

    private function resumo(Demanda $d): array
    {
        $simples = PrioridadeSimples::dePrioridade($d->prioridade);

        return [
            'id' => $d->id,
            'protocolo' => $d->protocolo,
            'titulo' => $d->titulo,
            'status' => $d->status->value,
            'status_label' => $d->status->label(),
            'etapa' => $d->status->etapa()->value,
            'etapa_label' => $d->status->etapa()->label(),
            'prioridade_simples' => $simples->value,
            'prioridade_label' => $simples->label(),
            'prioridade_itil' => $d->prioridade?->label(),
            'created_at' => $d->created_at?->toIso8601String(),
            'resolvido_em' => $d->resolvido_em?->toIso8601String(),
            'assunto' => $d->assunto ? ['id' => $d->assunto->id, 'nome' => $d->assunto->nome, 'categoria' => $d->assunto->categoria?->nome] : null,
            'solicitante' => $d->solicitante ? ['id' => $d->solicitante->id, 'name' => $d->solicitante->name] : null,
            'atribuido_para' => $d->atribuidoPara ? ['id' => $d->atribuidoPara->id, 'name' => $d->atribuidoPara->name] : null,
            'criado_por' => $d->criadoPor ? ['id' => $d->criadoPor->id, 'name' => $d->criadoPor->name] : null,
            'legado_id' => $d->campos_customizados['_legado']['id'] ?? null,
        ];
    }

    /** @return list<array{value:int,label:string}> */
    private function opcoesAssunto(): array
    {
        return DemandaAssunto::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome'])
            ->map(fn (DemandaAssunto $a): array => ['value' => $a->id, 'label' => $a->nome])->all();
    }

    /** @return list<array{value:int,label:string}> */
    private function opcoesUsuario(): array
    {
        return User::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $u): array => ['value' => $u->id, 'label' => $u->name])->all();
    }
}
```

Atencao: `campos_customizados._legado` e reservado; `ValidadorCamposDinamicos` descarta chaves nao declaradas, entao em `DemandaWriteService::atualizar` preservar `_legado` quando existir — acrescentar logo apos o `normalizar`:

```php
                $legado = $demanda->getOriginal('campos_customizados')['_legado'] ?? null;
                if ($legado !== null) {
                    $demanda->campos_customizados = [...$demanda->campos_customizados, '_legado' => $legado];
                }
```
(`getOriginal` com cast `array` devolve o array; se vier string JSON, decodificar com `json_decode(..., true)`.) E no `ValidadorCamposDinamicos`, os valores lidos ignoram `_legado` naturalmente, pois so labels declaradas sao lidas.

- [ ] **Step 4: Rotas**

Em `routes/modules/demandas.php`, dentro do grupo, logo antes de `Route::get('/demandas/{id}', ...)`:

```php
    Route::post('/demandas/{id}/resolver', [DemandaController::class, 'resolver'])
        ->name('demandas.resolver')
        ->whereNumber('id')
        ->middleware('can:demandas.chamados.resolver');

    Route::post('/demandas/{id}/reabrir', [DemandaController::class, 'reabrir'])
        ->name('demandas.reabrir')
        ->whereNumber('id')
        ->middleware('can:demandas.chamados.resolver');
```
e adicionar `->whereNumber('id')` a `demandas.show`, para `/demandas/dashboard` (Task 10) nunca cair nele.

- [ ] **Step 5: Tempo real e silencio durante importacao**

`app/Modules/Demandas/Observers/DemandaTempoRealObserver.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Observers;

use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Support\ContextoImportacao;
use App\Modules\Shared\Events\RecursoAtualizado;

/**
 * Avisa as listagens abertas que algo mudou. O evento nao carrega dado: cada
 * tela recarrega pela propria rota, que ja aplica a visibilidade do usuario.
 */
class DemandaTempoRealObserver
{
    public function __construct(private readonly ContextoImportacao $contexto) {}

    public function saved(Demanda $demanda): void
    {
        $this->avisar();
    }

    public function deleted(Demanda $demanda): void
    {
        $this->avisar();
    }

    private function avisar(): void
    {
        if ($this->contexto->ativo()) {
            return;
        }
        RecursoAtualizado::dispatch('demandas');
    }
}
```

Em `CanaisDeListagem::MAPA` acrescentar:

```php
        // routes/modules/demandas.php -> can:demandas.chamados.view. A listagem
        // recorta por envolvido no servidor; o canal so diz "recarregue".
        'demandas' => 'demandas.chamados.view',
```

Em `DemandaNotificacaoObserver::updated`, primeira linha:

```php
        if (app(\App\Modules\Demandas\Support\ContextoImportacao::class)->ativo()) {
            return;
        }
```

Em `DemandasServiceProvider::boot()` acrescentar `Demanda::observe(DemandaTempoRealObserver::class);`.

- [ ] **Step 6: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas`
Expected: todos PASS (incluindo `DemandaVisibilidadeTest::test_terceiro_sem_gestao...`).

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Demandas/Controllers/DemandaController.php routes/modules/demandas.php app/Modules/Demandas/Observers app/Modules/Shared/Support/CanaisDeListagem.php app/Modules/Demandas/DemandasServiceProvider.php app/Modules/Demandas/Services/DemandaWriteService.php
git commit -m "✨ feat(demandas): rotas de resolver e reabrir, props das telas e tempo real"
```

---

### Task 10: Dashboard de chamados e export quantitativo

**Files:**
- Create: `app/Modules/Demandas/Queries/DemandaDashboardQuery.php`
- Create: `app/Modules/Demandas/Controllers/DemandaDashboardController.php`
- Modify: `routes/modules/demandas.php`
- Test: `tests/Feature/Demandas/DemandaDashboardTest.php`

**Interfaces:**
- Consumes: `EloquentDemandaRepository::escopoVisivel`, `getStatistics`.
- Produces:
  - `DemandaDashboardQuery::serieMensal(int $viewerId, bool $manage, int $meses = 10): list<array{mes: string /*Y-m*/, abertas: int, resolvidas: int}>` — sempre `$meses` itens, do mais antigo ao atual, com zero nos meses vazios.
  - `DemandaDashboardQuery::recentes(int $viewerId, bool $manage, int $limite = 5): list<Demanda>`.
  - `DemandaDashboardQuery::quantitativoPorAssunto(int $viewerId, bool $manage, ?string $de, ?string $ate): list<array{assunto: string, total: int, concluidas: int}>`.
  - Page `Demandas/DemandasDashboard` com props `estatisticas`, `serie`, `recentes` (formato `resumo`), `pode{exportar}`.
  - Rotas `demandas.dashboard` GET `/demandas/dashboard` e `demandas.dashboard.export` GET `/demandas/dashboard/export` (CSV `assunto;total;concluidas`).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Queries\DemandaDashboardQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaDashboardTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    public function test_serie_tem_dez_meses_com_zeros(): void
    {
        $gestor = $this->gestor();
        $this->demanda($gestor, ['status' => StatusDemanda::RESOLVIDA, 'resolvido_em' => now(), 'atribuido_para_id' => $gestor->id]);

        $serie = app(DemandaDashboardQuery::class)->serieMensal($gestor->id, false);

        $this->assertCount(10, $serie);
        $this->assertSame(now()->format('Y-m'), $serie[9]['mes']);
        $this->assertSame(1, $serie[9]['abertas']);
        $this->assertSame(1, $serie[9]['resolvidas']);
        $this->assertSame(0, $serie[0]['abertas']);
    }

    public function test_pagina_do_dashboard(): void
    {
        $this->withoutVite();
        $gestor = $this->gestor();

        $this->actingAs($gestor)->get('/demandas/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Demandas/DemandasDashboard')
                ->has('serie', 10)->has('estatisticas.resolvidas_hoje')->has('recentes'));
    }

    public function test_export_quantitativo_em_csv(): void
    {
        $gestor = $this->gestor();
        $assunto = $this->assunto();
        $this->demanda($gestor, ['assunto_id' => $assunto->id]);

        $resposta = $this->actingAs($gestor)->get('/demandas/dashboard/export');

        $resposta->assertOk();
        $this->assertStringContainsString($assunto->nome.';1;0', $resposta->streamedContent());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaDashboardTest.php`
Expected: FAIL ("Class ... DemandaDashboardQuery not found").

- [ ] **Step 3: Query**

`app/Modules/Demandas/Queries/DemandaDashboardQuery.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Queries;

use App\Modules\Demandas\Enums\EtapaDemanda;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Infrastructure\Persistence\EloquentDemandaRepository;
use App\Modules\Demandas\Models\Demanda;
use Illuminate\Support\Facades\DB;

final class DemandaDashboardQuery
{
    public function __construct(private readonly EloquentDemandaRepository $repository) {}

    /** @return list<array{mes: string, abertas: int, resolvidas: int}> */
    public function serieMensal(int $viewerId, bool $manage, int $meses = 10): array
    {
        $inicio = now()->startOfMonth()->subMonths($meses - 1);
        $base = fn () => $this->repository->escopoVisivel(Demanda::query(), $viewerId, $manage);

        $abertas = $base()->where('created_at', '>=', $inicio)
            ->selectRaw("to_char(created_at, 'YYYY-MM') as mes, count(*) as total")
            ->groupBy('mes')->pluck('total', 'mes');
        $resolvidas = $base()->where('resolvido_em', '>=', $inicio)
            ->selectRaw("to_char(resolvido_em, 'YYYY-MM') as mes, count(*) as total")
            ->groupBy('mes')->pluck('total', 'mes');

        $serie = [];
        for ($i = 0; $i < $meses; $i++) {
            $mes = $inicio->copy()->addMonths($i)->format('Y-m');
            $serie[] = ['mes' => $mes, 'abertas' => (int) ($abertas[$mes] ?? 0), 'resolvidas' => (int) ($resolvidas[$mes] ?? 0)];
        }

        return $serie;
    }

    /** @return list<Demanda> */
    public function recentes(int $viewerId, bool $manage, int $limite = 5): array
    {
        return $this->repository->escopoVisivel(Demanda::query(), $viewerId, $manage)
            ->with(['solicitante:id,name', 'atribuidoPara:id,name', 'criadoPor:id,name', 'assunto:id,nome,categoria_id', 'assunto.categoria:id,nome'])
            ->latest()->limit($limite)->get()->all();
    }

    /** @return list<array{assunto: string, total: int, concluidas: int}> */
    public function quantitativoPorAssunto(int $viewerId, bool $manage, ?string $de, ?string $ate): array
    {
        $concluidas = implode("','", array_map(fn (StatusDemanda $s) => $s->value, EtapaDemanda::CONCLUIDO->status()));

        return $this->repository->escopoVisivel(Demanda::query(), $viewerId, $manage)
            ->leftJoin('demanda_assuntos', 'demanda_assuntos.id', '=', 'tasks.assunto_id')
            ->when($de, fn ($q) => $q->whereDate('tasks.created_at', '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate('tasks.created_at', '<=', $ate))
            ->groupBy('demanda_assuntos.nome')
            ->orderByDesc(DB::raw('count(*)'))
            ->get([
                DB::raw("coalesce(demanda_assuntos.nome, 'Sem assunto') as assunto"),
                DB::raw('count(*) as total'),
                DB::raw("sum(case when tasks.status in ('{$concluidas}') then 1 else 0 end) as concluidas"),
            ])
            ->map(fn ($r): array => ['assunto' => (string) $r->assunto, 'total' => (int) $r->total, 'concluidas' => (int) $r->concluidas])
            ->all();
    }
}
```
Nota: `escopoVisivel` usa colunas sem prefixo (`solicitante_id`); no join de `quantitativoPorAssunto` nao ha ambiguidade porque `demanda_assuntos` nao tem essas colunas.

- [ ] **Step 4: Controller e rotas**

`app/Modules/Demandas/Controllers/DemandaDashboardController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Queries\DemandaDashboardQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DemandaDashboardController extends Controller
{
    public function __construct(
        private readonly DemandaRepository $repository,
        private readonly DemandaDashboardQuery $query,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $gerir = $user->can('demandas.chamados.manage');
        $id = (int) $user->id;

        return Inertia::render('Demandas/DemandasDashboard', [
            'estatisticas' => $this->repository->getStatistics($id, $gerir),
            'serie' => $this->query->serieMensal($id, $gerir),
            'recentes' => array_map(fn (Demanda $d): array => [
                'id' => $d->id, 'protocolo' => $d->protocolo, 'titulo' => $d->titulo,
                'etapa' => $d->status->etapa()->value, 'etapa_label' => $d->status->etapa()->label(),
                'prioridade_simples' => PrioridadeSimples::dePrioridade($d->prioridade)->value,
                'prioridade_label' => PrioridadeSimples::dePrioridade($d->prioridade)->label(),
                'solicitante' => $d->solicitante?->name, 'created_at' => $d->created_at?->toIso8601String(),
            ], $this->query->recentes($id, $gerir)),
            'pode' => ['exportar' => $user->can('demandas.chamados.export')],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate(['de' => ['nullable', 'date'], 'ate' => ['nullable', 'date', 'after_or_equal:de']]);
        $linhas = $this->query->quantitativoPorAssunto(
            (int) $request->user()->id, $request->user()->can('demandas.chamados.manage'), $data['de'] ?? null, $data['ate'] ?? null,
        );

        return response()->streamDownload(function () use ($linhas): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['assunto', 'total', 'concluidas'], ';');
            foreach ($linhas as $l) {
                // Formula injection: nome de assunto vem de cadastro livre.
                $assunto = preg_match('/^[=+\-@]/', $l['assunto']) ? "'".$l['assunto'] : $l['assunto'];
                fputcsv($out, [$assunto, $l['total'], $l['concluidas']], ';');
            }
            fclose($out);
        }, 'demandas-quantitativo-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
```

Em `routes/modules/demandas.php`, ANTES de `Route::get('/demandas/nova', ...)`:

```php
    Route::get('/demandas/dashboard', [DemandaDashboardController::class, 'index'])
        ->name('demandas.dashboard')
        ->middleware('can:demandas.dashboard.view');

    Route::get('/demandas/dashboard/export', [DemandaDashboardController::class, 'export'])
        ->name('demandas.dashboard.export')
        ->middleware('can:demandas.chamados.export');
```
com `use App\Modules\Demandas\Controllers\DemandaDashboardController;`.

Em `DemandasServiceProvider::$bindings` o repositorio concreto ja e resolvido por classe; nada a registrar para a query.

- [ ] **Step 5: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaDashboardTest.php`
Expected: 3 PASS (o teste de pagina so passa depois da Task 18 criar o componente Vue se o Inertia validar existencia; `withoutVite()` evita o manifest. Se `inertia.testing.ensure_pages_exist` estiver ligado, criar um `DemandasDashboard.vue` minimo agora com `<template><div /></template>` — ele e substituido na Task 18.)

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Demandas/Queries app/Modules/Demandas/Controllers/DemandaDashboardController.php routes/modules/demandas.php
git commit -m "✨ feat(demandas): dashboard de chamados e export quantitativo"
```

---

### Task 11: Automacao AD pelo assunto

**Files:**
- Modify: `app/Modules/Acessos/Contracts/DiretorioCorporativo.php`
- Modify: `app/Modules/Acessos/Infrastructure/HttpDiretorioCorporativo.php`
- Create: `app/Modules/Demandas/Services/ExecutarAutomacaoDemanda.php`
- Create: `app/Modules/Demandas/Jobs/ExecutarAutomacaoDemandaJob.php`
- Modify: `app/Modules/Demandas/Controllers/DemandaController.php` (metodo `automatizar`)
- Modify: `routes/modules/demandas.php`
- Create: `app/Modules/Demandas/Requests/SalvarAssuntoRequest.php`, `app/Modules/Demandas/Requests/SalvarCategoriaRequest.php`
- Modify: `app/Modules/Demandas/Controllers/CatalogoDemandaController.php`
- Test: `tests/Feature/Demandas/DemandaAutomacaoTest.php`

**Interfaces:**
- Consumes: `DiretorioCorporativo`, `HistoricoDemanda::registrar`.
- Produces:
  - `DiretorioCorporativo::solicitarAtivacao(string $login, string $operationId): array`.
  - `form_automacao`: `{acao: 'desbloquear'|'ativar'|'resetar', campo_login: string}`.
  - `ExecutarAutomacaoDemanda::solicitar(Demanda $demanda, int $userId): string` (devolve `operationId`; lanca `DomainException` se o assunto nao tem automacao, se o login for invalido ou ausente).
  - `ExecutarAutomacaoDemandaJob(int $demandaId, string $acao, string $login, string $operationId, int $userId)`.
  - Rota `demandas.automacao` POST `/demandas/{id}/automacao` (`can:demandas.chamados.automatizar`).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Http\Middleware\VerifyCsrfToken;
use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Demandas\Jobs\ExecutarAutomacaoDemandaJob;
use App\Modules\Demandas\Services\ExecutarAutomacaoDemanda;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaAutomacaoTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    private array $chamadas = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $teste = $this;
        $this->app->instance(DiretorioCorporativo::class, new class($teste) implements DiretorioCorporativo {
            public bool $falhar = false;
            public function __construct(private $teste) {}
            public function consultar(string $login): array { return []; }
            public function solicitarDesbloqueio(string $login, string $operationId): array { return $this->registrar('desbloquear', $login, $operationId); }
            public function solicitarReset(string $login, string $operationId): array { return $this->registrar('resetar', $login, $operationId); }
            public function solicitarAtivacao(string $login, string $operationId): array { return $this->registrar('ativar', $login, $operationId); }
            private function registrar(string $acao, string $login, string $op): array
            {
                if ($this->falhar) { throw new RuntimeException('AD fora do ar'); }
                $this->teste->registrarChamada([$acao, $login, $op]);
                return ['status' => 'ok', 'senha' => 'NAO-PODE-VAZAR'];
            }
        });
    }

    public function registrarChamada(array $c): void
    {
        $this->chamadas[] = $c;
    }

    public function test_desbloqueio_pelo_login_do_campo_dinamico(): void
    {
        $gestor = $this->gestor();
        $assunto = $this->assunto([['label' => 'Login AD', 'tipo' => 'text']], ['acao' => 'desbloquear', 'campo_login' => 'Login AD']);
        $demanda = $this->demanda($gestor, ['assunto_id' => $assunto->id, 'campos_customizados' => ['Login AD' => 'M1234567']]);

        $this->actingAs($gestor)->post("/demandas/{$demanda->id}/automacao")->assertSessionHasNoErrors();

        $this->assertSame('desbloquear', $this->chamadas[0][0]);
        $this->assertSame('M1234567', $this->chamadas[0][1]);
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'automation_requested']);
        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'automation_confirmed']);
        $this->assertDatabaseMissing('task_audit_logs', ['task_id' => $demanda->id, 'metadata->resposta->senha' => 'NAO-PODE-VAZAR']);
    }

    public function test_login_com_caracteres_de_shell_e_recusado(): void
    {
        $gestor = $this->gestor();
        $assunto = $this->assunto([['label' => 'Login AD', 'tipo' => 'text']], ['acao' => 'ativar', 'campo_login' => 'Login AD']);
        $demanda = $this->demanda($gestor, ['assunto_id' => $assunto->id, 'campos_customizados' => ['Login AD' => 'm1; rm -rf /']]);

        $this->expectException(\DomainException::class);
        app(ExecutarAutomacaoDemanda::class)->solicitar($demanda, $gestor->id);
    }

    public function test_falha_do_ad_registra_falhou_e_nao_muda_status(): void
    {
        $gestor = $this->gestor();
        $assunto = $this->assunto([['label' => 'Login AD', 'tipo' => 'text']], ['acao' => 'resetar', 'campo_login' => 'Login AD']);
        $demanda = $this->demanda($gestor, ['assunto_id' => $assunto->id, 'campos_customizados' => ['Login AD' => 'M1']]);
        $this->app->make(DiretorioCorporativo::class)->falhar = true;

        $job = new ExecutarAutomacaoDemandaJob($demanda->id, 'resetar', 'M1', 'demanda:'.$demanda->id.':resetar:1', $gestor->id);
        $job->failed(new RuntimeException('AD fora do ar'));

        $this->assertDatabaseHas('task_audit_logs', ['task_id' => $demanda->id, 'acao' => 'automation_failed']);
        $this->assertSame('aberta', $demanda->fresh()->status->value);
    }

    public function test_operation_id_incrementa_por_tentativa(): void
    {
        $gestor = $this->gestor();
        $assunto = $this->assunto([['label' => 'Login AD', 'tipo' => 'text']], ['acao' => 'desbloquear', 'campo_login' => 'Login AD']);
        $demanda = $this->demanda($gestor, ['assunto_id' => $assunto->id, 'campos_customizados' => ['Login AD' => 'M1']]);
        $service = app(ExecutarAutomacaoDemanda::class);

        $primeiro = $service->solicitar($demanda, $gestor->id);
        $segundo = $service->solicitar($demanda, $gestor->id);

        $this->assertSame('demanda:'.$demanda->id.':desbloquear:1', $primeiro);
        $this->assertSame('demanda:'.$demanda->id.':desbloquear:2', $segundo);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaAutomacaoTest.php`
Expected: FAIL (classe anonima nao implementa `solicitarAtivacao` inexistente no contrato / job inexistente).

- [ ] **Step 3: Porta AD**

Em `DiretorioCorporativo.php` acrescentar:

```php
    public function solicitarAtivacao(string $login, string $operationId): array;
```

Em `HttpDiretorioCorporativo.php` acrescentar:

```php
    public function solicitarAtivacao(string $login, string $operationId): array
    {
        return $this->request('POST', '/accounts/'.rawurlencode($login).'/enable', $operationId);
    }
```
e trocar `->timeout(10)` por `->timeout((int) config('demandas.automacao.timeout_segundos', 15))`. Verificar que nenhuma outra classe implementa `DiretorioCorporativo` (`grep -rn "implements DiretorioCorporativo" app`) e, se houver, acrescentar o metodo nela.

- [ ] **Step 4: Caso de uso e job**

`app/Modules/Demandas/Services/ExecutarAutomacaoDemanda.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Jobs\ExecutarAutomacaoDemandaJob;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAuditLog;
use DomainException;

/**
 * Botao "Automacao" do chamado. No legado era shell_exec de script Python com
 * assunto fixo no codigo; aqui a acao vem do assunto e o login do campo que o
 * solicitante preencheu, e a chamada ao AD vai para fila.
 */
final class ExecutarAutomacaoDemanda
{
    public const ACOES = ['desbloquear', 'ativar', 'resetar'];

    // sAMAccountName: letras, digitos, ponto, hifen e sublinhado; ate 20.
    private const LOGIN_VALIDO = '/^[A-Za-z0-9._-]{1,20}$/';

    public function __construct(private readonly HistoricoDemanda $historico) {}

    public function solicitar(Demanda $demanda, int $userId): string
    {
        $config = $demanda->assunto?->form_automacao;
        $acao = $config['acao'] ?? null;
        if (! in_array($acao, self::ACOES, true)) {
            throw new DomainException('O assunto desta demanda não tem automação configurada.');
        }

        $login = trim((string) ($demanda->campos_customizados[$config['campo_login'] ?? ''] ?? ''));
        if (preg_match(self::LOGIN_VALIDO, $login) !== 1) {
            throw new DomainException('Login AD ausente ou inválido no campo "'.($config['campo_login'] ?? '').'".');
        }

        $sequencia = DemandaAuditLog::query()
            ->where('task_id', $demanda->id)
            ->where('acao', AcaoHistoricoDemanda::AUTOMACAO_SOLICITADA->value)
            ->count() + 1;
        $operationId = sprintf('demanda:%d:%s:%d', $demanda->id, $acao, $sequencia);

        $this->historico->registrar(
            $demanda, $userId, AcaoHistoricoDemanda::AUTOMACAO_SOLICITADA,
            sprintf('Solicitado "%s" para o login %s.', $acao, $login),
            ['operation_id' => $operationId],
        );

        ExecutarAutomacaoDemandaJob::dispatch($demanda->id, $acao, $login, $operationId, $userId)->afterCommit();

        return $operationId;
    }
}
```

`app/Modules/Demandas/Jobs/ExecutarAutomacaoDemandaJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Jobs;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Services\HistoricoDemanda;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Chamada ao diretorio corporativo fora da requisicao. Idempotente pelo
 * operationId (Idempotency-Key no adaptador HTTP): retry nao desbloqueia duas
 * vezes. Resposta do AD nunca vai para historico ou log -- reset devolve senha.
 */
class ExecutarAutomacaoDemandaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 30;

    public function __construct(
        public readonly int $demandaId,
        public readonly string $acao,
        public readonly string $login,
        public readonly string $operationId,
        public readonly int $userId,
    ) {}

    public function tries(): int
    {
        return (int) config('demandas.automacao.tentativas', 3);
    }

    public function backoff(): array
    {
        return config('demandas.automacao.backoff_segundos', [10, 30]);
    }

    public function handle(DiretorioCorporativo $diretorio, HistoricoDemanda $historico): void
    {
        match ($this->acao) {
            'desbloquear' => $diretorio->solicitarDesbloqueio($this->login, $this->operationId),
            'ativar' => $diretorio->solicitarAtivacao($this->login, $this->operationId),
            'resetar' => $diretorio->solicitarReset($this->login, $this->operationId),
        };

        $demanda = Demanda::find($this->demandaId);
        if ($demanda !== null) {
            $historico->registrar(
                $demanda, $this->userId, AcaoHistoricoDemanda::AUTOMACAO_CONFIRMADA,
                sprintf('"%s" confirmado pelo diretório para %s.', $this->acao, $this->login),
                ['operation_id' => $this->operationId],
            );
        }
    }

    public function failed(Throwable $erro): void
    {
        $demanda = Demanda::find($this->demandaId);
        if ($demanda === null) {
            return;
        }
        app(HistoricoDemanda::class)->registrar(
            $demanda, $this->userId, AcaoHistoricoDemanda::AUTOMACAO_FALHOU,
            sprintf('"%s" falhou: %s', $this->acao, mb_substr($erro->getMessage(), 0, 200)),
            ['operation_id' => $this->operationId],
        );
    }
}
```

Nota: com `QUEUE_CONNECTION=sync` a excecao do `handle` sobe para a requisicao; o `failed` so roda em fila real. Por isso o teste de falha chama `failed()` direto, e o controller trata excecao da fila sincrona (abaixo).

- [ ] **Step 5: Controller, rota e catalogo**

Em `DemandaController` injetar `private readonly ExecutarAutomacaoDemanda $automacao,` e acrescentar:

```php
    public function automatizar(int $id, Request $request): RedirectResponse
    {
        $demanda = $this->repository->findById($id) ?? abort(404);
        $this->authorize('automatizar', $demanda);

        try {
            $this->automacao->solicitar($demanda, (int) $request->user()->id);
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['automacao' => $e->getMessage()]);
        } catch (\Throwable) {
            // Fila sincrona (dev/teste) propaga a falha do AD; o historico ja
            // registrou a solicitacao e o estado da demanda nao muda.
            return redirect()->back()->withErrors(['automacao' => 'O diretório corporativo não respondeu. Tente novamente.']);
        }

        return redirect()->back()->with('success', 'Automação solicitada. Acompanhe no histórico.');
    }
```

Rota (junto de resolver/reabrir):

```php
    Route::post('/demandas/{id}/automacao', [DemandaController::class, 'automatizar'])
        ->name('demandas.automacao')
        ->whereNumber('id')
        ->middleware('can:demandas.chamados.automatizar');
```

`app/Modules/Demandas/Requests/SalvarAssuntoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use App\Modules\Demandas\Services\ExecutarAutomacaoDemanda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarAssuntoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demandas.chamados.manage');
    }

    public function rules(): array
    {
        $assunto = $this->route('assunto');
        $sometimes = $assunto !== null ? ['sometimes'] : [];

        return [
            'nome' => [...$sometimes, 'required', 'string', 'max:150', Rule::unique('demanda_assuntos', 'nome')->ignore($assunto?->id)],
            'categoria_id' => ['sometimes', 'nullable', 'integer', 'exists:demanda_categorias,id'],
            'ativo' => ['sometimes', 'boolean'],
            'campos_dinamicos' => ['sometimes', 'nullable', 'array', 'max:20'],
            'campos_dinamicos.*.label' => ['required', 'string', 'max:100', 'distinct'],
            'campos_dinamicos.*.tipo' => ['required', Rule::in(['text', 'checkbox'])],
            'form_automacao' => ['sometimes', 'nullable', 'array'],
            'form_automacao.acao' => ['required_with:form_automacao', Rule::in(ExecutarAutomacaoDemanda::ACOES)],
            'form_automacao.campo_login' => ['required_with:form_automacao', 'string', Rule::in(
                collect($this->input('campos_dinamicos', $assunto?->campos_dinamicos ?? []))
                    ->where('tipo', 'text')->pluck('label')->all()
            )],
        ];
    }

    public function messages(): array
    {
        return ['form_automacao.campo_login.in' => 'O login deve vir de um campo de texto deste assunto.'];
    }
}
```

`app/Modules/Demandas/Requests/SalvarCategoriaRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demandas.chamados.manage');
    }

    public function rules(): array
    {
        $categoria = $this->route('categoria');
        $sometimes = $categoria !== null ? ['sometimes'] : [];
        $pai = Rule::exists('demanda_categorias', 'id')->whereNull('parent_id');
        if ($categoria !== null) {
            $pai = $pai->whereNot('id', $categoria->id);
        }

        return [
            'nome' => [...$sometimes, 'required', 'string', 'max:100'],
            'descricao' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'parent_id' => ['sometimes', 'nullable', 'integer', $pai],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
```

Em `CatalogoDemandaController`: `index` passa a incluir `'campos_dinamicos', 'form_automacao'` no `get` de assuntos; os quatro metodos trocam `Request $request` + `$request->validate([...])` por `SalvarCategoriaRequest $request` / `SalvarAssuntoRequest $request` e `$data = $request->validated();`.

- [ ] **Step 6: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/DemandaAutomacaoTest.php`
Expected: 4 PASS. Depois rodar a suite inteira: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas` — tudo PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Acessos/Contracts/DiretorioCorporativo.php app/Modules/Acessos/Infrastructure/HttpDiretorioCorporativo.php app/Modules/Demandas/Services/ExecutarAutomacaoDemanda.php app/Modules/Demandas/Jobs app/Modules/Demandas/Controllers app/Modules/Demandas/Requests routes/modules/demandas.php
git commit -m "✨ feat(demandas): automacao AD configurada pelo assunto via porta do diretorio"
```

---

### Task 12: SLA ligado e agendado; Ranking pontua demanda resolvida

**Files:**
- Modify: `app/Modules/Demandas/DemandasServiceProvider.php`
- Modify: `routes/console.php`
- Create: `app/Modules/Ranking/Adapters/DemandaAdapter.php`
- Modify: `app/Modules/Ranking/RankingServiceProvider.php`
- Test: `tests/Feature/Demandas/DemandaSlaTest.php`, `tests/Unit/Ranking/DemandaAdapterTest.php`

**Interfaces:**
- Consumes: `DemandaResolvidaV1` (`aggregateId`, `resolvidoPorId`, `occurredAt`, `eventId`, `eventName()` = `demanda.resolvida`), `FatoNormalizado`.
- Produces: `DemandaAdapter::modulo()` = `Demandas`; `ruleKeys()` = `['demandas.entrega_aceita']`; chave canonica `demanda:{id}:entrega_aceita`; familia `demandas_entrega_aceita`.

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Ranking/DemandaAdapterTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Ranking;

use App\Modules\Demandas\Domain\Events\DemandaResolvidaV1;
use App\Modules\Demandas\Domain\Events\StatusAlteradoV1;
use App\Modules\Ranking\Adapters\DemandaAdapter;
use PHPUnit\Framework\TestCase;

class DemandaAdapterTest extends TestCase
{
    public function test_resolucao_vira_entrega_aceita_de_quem_resolveu(): void
    {
        $evento = DemandaResolvidaV1::create(42, 7);
        $fato = (new DemandaAdapter())->normalizar($evento);

        $this->assertNotNull($fato);
        $this->assertSame('demanda:42:entrega_aceita', $fato->chaveCanonica);
        $this->assertSame('demandas.entrega_aceita', $fato->ruleKey);
        $this->assertSame('demandas_entrega_aceita', $fato->familia);
        $this->assertSame(7, $fato->creditedUserId);
        $this->assertTrue($fato->autoriaComprovada);
    }

    public function test_reabrir_e_resolver_de_novo_gera_a_mesma_chave(): void
    {
        $a = (new DemandaAdapter())->normalizar(DemandaResolvidaV1::create(42, 7));
        $b = (new DemandaAdapter())->normalizar(DemandaResolvidaV1::create(42, 9));

        $this->assertSame($a->chaveCanonica, $b->chaveCanonica);
        $this->assertNotSame($a->eventId, $b->eventId);
    }

    public function test_outros_eventos_nao_sao_suportados(): void
    {
        $this->assertFalse((new DemandaAdapter())->suporta(StatusAlteradoV1::create(1, 'aberta', 'em_analise', 1)));
    }
}
```

`tests/Feature/Demandas/DemandaSlaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Models\SlaDefinicao;
use App\Modules\Demandas\Services\DemandaWriteService;
use App\Modules\Demandas\Support\ContextoImportacao;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Demandas\Concerns\CriaDemandas;
use Tests\TestCase;

class DemandaSlaTest extends TestCase
{
    use CriaDemandas;
    use DatabaseTransactions;

    private function abrir(int $userId)
    {
        return app(DemandaWriteService::class)->abrir(new CriarDemandaData(
            tipo: TipoDemanda::SOLICITACAO, titulo: 'TST- sla', descricao: 'x', categoria: null, subcategoria: null,
            assuntoId: null, urgencia: PrioridadeSimples::MEDIA->urgencia(), impacto: PrioridadeSimples::MEDIA->impacto(),
            solicitanteId: $userId, criadoPorId: $userId,
        ));
    }

    public function test_abrir_inicia_sla_quando_ha_definicao(): void
    {
        $user = $this->solicitante();
        if (SlaDefinicao::where('ativo', true)->doesntExist()) {
            $this->markTestSkipped('Sem SlaDefinicao ativa em sdc_test; criar uma via factory/seed se o schema exigir campos.');
        }

        $demanda = $this->abrir($user->id);

        $this->assertDatabaseHas('task_sla_instances', ['task_id' => $demanda->id]);
    }

    public function test_importacao_nao_inicia_sla(): void
    {
        $user = $this->solicitante();

        $demanda = app(ContextoImportacao::class)->durante(fn () => $this->abrir($user->id));

        $this->assertDatabaseMissing('task_sla_instances', ['task_id' => $demanda->id]);
    }

    public function test_verificador_agendado_a_cada_cinco_minutos(): void
    {
        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'demandas:verificar-slas'));

        $this->assertCount(1, $eventos);
        $this->assertSame('*/5 * * * *', $eventos->first()->expression);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Unit/Ranking/DemandaAdapterTest.php tests/Feature/Demandas/DemandaSlaTest.php`
Expected: FAIL (adaptador inexistente; comando nao agendado).

- [ ] **Step 3: Implementar**

`app/Modules/Ranking/Adapters/DemandaAdapter.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters;

use App\Core\Events\DomainEvent;
use App\Modules\Demandas\Domain\Events\DemandaResolvidaV1;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\DTOs\FatoNormalizado;

/**
 * Demanda resolvida = entrega aceita, para quem resolveu. Mover card, comentar e
 * transferir valem zero e nem chegam aqui. Reabrir e resolver de novo produz a
 * mesma chave canonica, entao o livro segura o premio duplo.
 */
final class DemandaAdapter implements ModuleAdapter
{
    public function modulo(): string { return 'Demandas'; }

    public function ruleKeys(): array { return ['demandas.entrega_aceita']; }

    public function suporta(DomainEvent $evento): bool
    {
        return $evento instanceof DemandaResolvidaV1;
    }

    public function normalizar(DomainEvent $evento): ?FatoNormalizado
    {
        if (! $evento instanceof DemandaResolvidaV1) {
            return null;
        }
        $autor = $evento->resolvidoPorId > 0 ? $evento->resolvidoPorId : null;

        return new FatoNormalizado(
            eventId: $evento->eventId, eventName: $evento->eventName(), modulo: 'Demandas',
            chaveCanonica: 'demanda:'.$evento->aggregateId.':entrega_aceita', familia: 'demandas_entrega_aceita',
            ruleKey: 'demandas.entrega_aceita', ocorridoEm: $evento->occurredAt, competenciaEm: $evento->occurredAt,
            autoriaComprovada: $autor !== null, evidenciaComprovada: true, validada: false,
            actorUserId: $autor, creditedUserId: $autor, entregueEm: $evento->occurredAt,
            contexto: ['demanda_id' => $evento->aggregateId, 'fonte' => 'demanda.resolvida'],
        );
    }
}
```
Conferir os nomes dos parametros de `FatoNormalizado` no arquivo (`entregueEm`, `contexto`) — sao os mesmos usados por `PmdaAdapter`.

Em `RankingServiceProvider`: acrescentar `DemandaAdapter::class` ao array `$adaptadores` e `DemandaResolvidaV1::class` ao array `$eventos`, com os imports.

Em `DemandasServiceProvider::boot()`:

```php
        if ($this->app->runningInConsole()) {
            $this->commands([SlaVerificadorCommand::class]);
        }
```
com `use App\Modules\Demandas\Console\SlaVerificadorCommand;`.

Em `routes/console.php`, junto dos demais agendamentos de modulo:

```php
// Demandas: marca SLA vencido. Sem SlaDefinicao ativa o comando nao faz nada.
Schedule::command('demandas:verificar-slas')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Unit/Ranking/DemandaAdapterTest.php tests/Feature/Demandas/DemandaSlaTest.php`
Expected: PASS (o primeiro teste de SLA pode ficar SKIPPED se nao houver definicao; aceitavel). Rodar tambem `bash /c/tmp/teste-demandas.sh php artisan ranking:verify-catalog` — Demandas deve aparecer com adaptador registrado.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Ranking/Adapters/DemandaAdapter.php app/Modules/Ranking/RankingServiceProvider.php app/Modules/Demandas/DemandasServiceProvider.php routes/console.php
git commit -m "✨ feat(demandas): SLA agendado e pontuacao de demanda resolvida no ranking"
```

---

### Task 13: Importacao dos chamados do cedec-demanda

**Files:**
- Modify: `config/database.php` (conexao `cedec_demanda_legacy`), `config/filesystems.php` (disk `legado_demandas`)
- Create: `app/Modules/Demandas/Importacao/EtapaImportacao.php`, `RelatorioEtapa.php`, `MapaImportacao.php`, `MapaLegado.php`, `ResolvedorUsuarioLegado.php`
- Create: `app/Modules/Demandas/Importacao/Etapas/{ImportarUsuarios,ImportarCatalogo,ImportarChamados,ImportarHistorico,ImportarComentarios,ImportarAnexos}.php`
- Create: `app/Modules/Demandas/Console/ImportarLegadoCommand.php`
- Modify: `app/Modules/Demandas/DemandasServiceProvider.php`
- Test: `tests/Feature/Demandas/ImportacaoLegadoTest.php`

**Interfaces:**
- Consumes: `ContextoImportacao::durante`, `HistoricoDemanda::registrar`, `PrioridadeSimples`, tabela `cedec_demanda_import_maps`.
- Produces:
  - `interface EtapaImportacao { public function nome(): string; public function executar(bool $dryRun, ?string $desde, int $lote): RelatorioEtapa; }`
  - `RelatorioEtapa` (mutavel): `lidos, importados, atualizados, ignorados, rejeitados:int`, `motivos: array<string,int>`, `rejeitar(string $motivo)`, `toArray()`.
  - `MapaImportacao::alvo(string $tabela, string $id): ?int`, `::hash(string $tabela, string $id): ?string`, `::registrar(string $tabela, string $id, string $alvoTabela, ?int $alvoId, string $hash, string $status, ?string $motivo = null): void`.
  - `MapaLegado::status(string $legado): StatusDemanda`, `::hash(array $linha): string`.
  - `ResolvedorUsuarioLegado::resolver(?int $legadoId): ?int` (le o mapa `users`).
  - Comando `demandas:importar-legado {--dry-run} {--etapa=} {--desde=} {--lote=200}`.
  - Tabelas de origem (MySQL cedec-demanda): `users(id,name,email,cpf,login)`, `chamados_categorias(id,nome,descricao)`, `assuntos(id,nome,campos_dinamicos,form_automacao)`, `chamados(id,criador_id,solicitante_id,destinatario_id,encaminhado_para_id,categoria_id,assunto_id,titulo,mensagem,grupo,obs,tipo,status,prioridade,data_hora,data_fechamento,dados_adicionais,created_at,updated_at)`, `historico_chamados(id,chamado_id,user_id,acao,detalhes,created_at)`, `comentarios(id,chamado_id,user_id,comentario,created_at)`, `anexos(id,chamado_id,caminho_arquivo,nome_original,created_at)`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Demandas;

use App\Models\User;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportacaoLegadoTest extends TestCase
{
    use DatabaseTransactions;

    private const CONEXAO = 'cedec_demanda_legacy';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.'.self::CONEXAO => array_merge(
            config('database.connections.pgsql'), ['search_path' => 'legado_demandas_teste']
        )]);
        DB::purge(self::CONEXAO);
        DB::connection(self::CONEXAO)->statement('DROP SCHEMA IF EXISTS legado_demandas_teste CASCADE');
        DB::connection(self::CONEXAO)->statement('CREATE SCHEMA legado_demandas_teste');
        $this->criarTabelasLegadas();
        Storage::fake('local');
        Storage::fake('legado_demandas');
    }

    protected function tearDown(): void
    {
        DB::connection(self::CONEXAO)->statement('DROP SCHEMA IF EXISTS legado_demandas_teste CASCADE');
        parent::tearDown();
    }

    private function criarTabelasLegadas(): void
    {
        $s = Schema::connection(self::CONEXAO);
        $s->create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('cpf')->nullable(); $t->string('login')->nullable(); });
        $s->create('chamados_categorias', function (Blueprint $t) { $t->id(); $t->string('nome'); $t->string('descricao')->nullable(); });
        $s->create('assuntos', function (Blueprint $t) { $t->id(); $t->string('nome'); $t->json('campos_dinamicos')->nullable(); $t->json('form_automacao')->nullable(); });
        $s->create('chamados', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('criador_id')->nullable(); $t->unsignedBigInteger('solicitante_id');
            $t->unsignedBigInteger('destinatario_id')->nullable(); $t->unsignedBigInteger('encaminhado_para_id')->nullable();
            $t->unsignedBigInteger('categoria_id'); $t->integer('assunto_id')->nullable(); $t->string('titulo'); $t->text('mensagem');
            $t->string('grupo')->nullable(); $t->text('obs')->nullable(); $t->string('tipo'); $t->string('status'); $t->string('prioridade');
            $t->timestamp('data_hora')->nullable(); $t->timestamp('data_fechamento')->nullable(); $t->json('dados_adicionais')->nullable(); $t->timestamps();
        });
        $s->create('historico_chamados', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('chamado_id'); $t->unsignedBigInteger('user_id'); $t->string('acao'); $t->text('detalhes')->nullable(); $t->timestamps(); });
        $s->create('comentarios', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('chamado_id'); $t->unsignedBigInteger('user_id'); $t->text('comentario'); $t->timestamps(); });
        $s->create('anexos', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('chamado_id'); $t->string('caminho_arquivo'); $t->string('nome_original'); $t->timestamps(); });
    }

    private function semearLegado(User $maria, User $davi): void
    {
        $l = DB::connection(self::CONEXAO);
        $l->table('users')->insert([
            ['id' => 10, 'name' => 'Maria', 'email' => 'x@x', 'cpf' => $maria->cpf, 'login' => 'M1'],
            ['id' => 20, 'name' => 'Davi', 'email' => $davi->email, 'cpf' => null, 'login' => 'D1'],
            ['id' => 30, 'name' => 'Fantasma', 'email' => 'fantasma@nao.existe', 'cpf' => '99999999999', 'login' => 'F1'],
        ]);
        $l->table('chamados_categorias')->insert(['id' => 1, 'nome' => 'TST- Software']);
        $l->table('assuntos')->insert(['id' => 3, 'nome' => 'TST- DESBLOQUEIO', 'campos_dinamicos' => json_encode([['label' => 'Login AD', 'tipo' => 'text']]), 'form_automacao' => null]);
        $l->table('chamados')->insert([
            ['id' => 199, 'criador_id' => 20, 'solicitante_id' => 10, 'destinatario_id' => 20, 'encaminhado_para_id' => null, 'categoria_id' => 1, 'assunto_id' => 3,
             'titulo' => 'TST- pendrive', 'mensagem' => 'Criacao pendrive', 'grupo' => null, 'obs' => null, 'tipo' => 'suporte', 'status' => 'concluido', 'prioridade' => 'baixa',
             'data_hora' => '2026-08-28 11:47:00', 'data_fechamento' => '2026-08-29 09:00:00', 'dados_adicionais' => json_encode(['Login AD' => 'M1']),
             'created_at' => '2026-08-28 11:47:00', 'updated_at' => '2026-08-29 09:00:00'],
            ['id' => 200, 'criador_id' => 30, 'solicitante_id' => 30, 'destinatario_id' => null, 'encaminhado_para_id' => null, 'categoria_id' => 1, 'assunto_id' => null,
             'titulo' => 'TST- orfao', 'mensagem' => 'x', 'grupo' => null, 'obs' => null, 'tipo' => 'aviso', 'status' => 'em aberto', 'prioridade' => 'media',
             'data_hora' => null, 'data_fechamento' => null, 'dados_adicionais' => null, 'created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-01 10:00:00'],
        ]);
        $l->table('historico_chamados')->insert(['chamado_id' => 199, 'user_id' => 20, 'acao' => 'Criou o chamado', 'detalhes' => 'Chamado aberto', 'created_at' => '2026-08-28 11:47:00']);
        $l->table('comentarios')->insert(['chamado_id' => 199, 'user_id' => 20, 'comentario' => 'Feito', 'created_at' => '2026-08-29 08:00:00']);
        $l->table('anexos')->insert([
            ['chamado_id' => 199, 'caminho_arquivo' => 'chamados/199/print.png', 'nome_original' => 'print.png', 'created_at' => '2026-08-28 12:00:00'],
            ['chamado_id' => 199, 'caminho_arquivo' => 'chamados/199/sumiu.pdf', 'nome_original' => 'sumiu.pdf', 'created_at' => '2026-08-28 12:00:00'],
        ]);
        Storage::disk('legado_demandas')->put('chamados/199/print.png', 'png-bytes');
    }

    public function test_importacao_completa_idempotente_e_silenciosa(): void
    {
        Event::fake();
        $maria = User::factory()->create(['cpf' => '123.456.789-09']);
        $davi = User::factory()->create(['email' => 'davi.tst@exemplo.gov.br']);
        $this->semearLegado($maria, $davi);

        $this->artisan('demandas:importar-legado')->assertSuccessful();

        $demanda = Demanda::where('titulo', 'TST- pendrive')->firstOrFail();
        $this->assertSame(StatusDemanda::RESOLVIDA, $demanda->status);
        $this->assertSame($maria->id, $demanda->solicitante_id);
        $this->assertSame($davi->id, $demanda->atribuido_para_id);
        $this->assertSame($davi->id, $demanda->criado_por_id);
        $this->assertSame('2026-08-28 11:47:00', $demanda->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-29 09:00:00', $demanda->resolvido_em->format('Y-m-d H:i:s'));
        $this->assertSame('M1', $demanda->campos_customizados['Login AD']);
        $this->assertSame(199, $demanda->campos_customizados['_legado']['id']);
        $this->assertSame('suporte', $demanda->campos_customizados['_legado']['tipo']);
        $this->assertStringContainsString('-2026-', $demanda->protocolo);
        $this->assertSame(1, $demanda->comments()->count());
        $this->assertSame(2, $demanda->attachments()->count());
        $this->assertDatabaseMissing('tasks', ['titulo' => 'TST- orfao']);
        $this->assertDatabaseHas('cedec_demanda_import_maps', ['source_table' => 'chamados', 'source_id' => '200', 'status' => 'rejeitado']);
        $this->assertDatabaseHas('cedec_demanda_import_maps', ['source_table' => 'anexos', 'status' => 'rejeitado']);
        Event::assertNotDispatched(\App\Modules\Demandas\Domain\Events\DemandaResolvidaV1::class);
        Event::assertNotDispatched(\App\Modules\Shared\Events\RecursoAtualizado::class);
        $this->assertDatabaseMissing('task_sla_instances', ['task_id' => $demanda->id]);

        // segunda execucao: nada duplica
        $this->artisan('demandas:importar-legado')->assertSuccessful();
        $this->assertSame(1, Demanda::where('titulo', 'TST- pendrive')->count());
        $this->assertSame(1, $demanda->comments()->count());

        // alteracao na origem: so o alterado atualiza
        DB::connection(self::CONEXAO)->table('chamados')->where('id', 199)->update(['titulo' => 'TST- pendrive v2']);
        $this->artisan('demandas:importar-legado', ['--etapa' => 'chamados'])->assertSuccessful();
        $this->assertSame('TST- pendrive v2', $demanda->fresh()->titulo);
        $this->assertSame(1, Demanda::where('titulo', 'like', 'TST- pendrive%')->count());
    }

    public function test_dry_run_nao_grava(): void
    {
        $maria = User::factory()->create(['cpf' => '123.456.789-09']);
        $davi = User::factory()->create(['email' => 'davi.tst@exemplo.gov.br']);
        $this->semearLegado($maria, $davi);

        $this->artisan('demandas:importar-legado', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseMissing('tasks', ['titulo' => 'TST- pendrive']);
        $this->assertDatabaseMissing('cedec_demanda_import_maps', ['source_table' => 'chamados', 'source_id' => '199']);
    }
}
```

Nota: se `User::factory()` nao aceitar `cpf` (coluna com mascara/unique), usar um CPF de teste valido e unico (`'123.456.789-09'` pode colidir com dado real; trocar por um gerado com prefixo 0000...).

- [ ] **Step 2: Run test to verify it fails**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/ImportacaoLegadoTest.php`
Expected: FAIL ("Command demandas:importar-legado is not defined").

- [ ] **Step 3: Conexao e disk**

Em `config/database.php`, depois de `legado_gestaocedec`:

```php
        // Somente leitura, consumida por demandas:importar-legado. MySQL do
        // cedec-demanda (chamados, comentarios, anexos, historico). Nenhuma
        // migration nem model aponta para ela.
        'cedec_demanda_legacy' => [
            'driver' => 'mysql',
            'host' => env('DB_CEDEC_DEMANDA_HOST', '127.0.0.1'),
            'port' => env('DB_CEDEC_DEMANDA_PORT', '3306'),
            'database' => env('DB_CEDEC_DEMANDA_DATABASE', 'cedec_demanda'),
            'username' => env('DB_CEDEC_DEMANDA_USERNAME', 'root'),
            'password' => env('DB_CEDEC_DEMANDA_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('DB_CEDEC_DEMANDA_SSL_CA'),
            ]) : [],
        ],
```

Em `config/filesystems.php`, depois de `legado_rat`:

```php
        // Anexos do cedec-demanda (storage/app/public do legado). Somente leitura
        // pelo ETL de demandas; caminho_arquivo e relativo a esta raiz.
        'legado_demandas' => [
            'driver' => 'local',
            'root' => env('LEGADO_DEMANDAS_ANEXOS_ROOT', storage_path('app/legado_demandas')),
            'visibility' => 'private',
            'throw' => false,
        ],
```

- [ ] **Step 4: Infra da importacao**

`app/Modules/Demandas/Importacao/EtapaImportacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

interface EtapaImportacao
{
    /** Nome curto usado em --etapa (usuarios, catalogo, chamados, historico, comentarios, anexos). */
    public function nome(): string;

    public function executar(bool $dryRun, ?string $desde, int $lote): RelatorioEtapa;
}
```

`app/Modules/Demandas/Importacao/RelatorioEtapa.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

final class RelatorioEtapa
{
    public int $lidos = 0;
    public int $importados = 0;
    public int $atualizados = 0;
    public int $ignorados = 0;
    public int $rejeitados = 0;

    /** @var array<string, int> */
    public array $motivos = [];

    public function __construct(public readonly string $etapa) {}

    public function rejeitar(string $motivo): void
    {
        $this->rejeitados++;
        $this->motivos[$motivo] = ($this->motivos[$motivo] ?? 0) + 1;
    }

    public function toArray(): array
    {
        return [$this->etapa, $this->lidos, $this->importados, $this->atualizados, $this->ignorados, $this->rejeitados];
    }
}
```

`app/Modules/Demandas/Importacao/MapaImportacao.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

use Illuminate\Support\Facades\DB;

/**
 * Correspondencia origem -> destino em cedec_demanda_import_maps. E ela que torna
 * a carga idempotente: registro com hash igual e pulado, com hash diferente e
 * atualizado no mesmo destino, e nunca se liga nada por id numerico igual.
 */
final class MapaImportacao
{
    private const TABELA = 'cedec_demanda_import_maps';

    public function alvo(string $tabela, string $id): ?int
    {
        $v = DB::table(self::TABELA)->where(['source_table' => $tabela, 'source_id' => $id, 'status' => 'importado'])->value('target_id');

        return $v === null ? null : (int) $v;
    }

    public function hash(string $tabela, string $id): ?string
    {
        return DB::table(self::TABELA)->where(['source_table' => $tabela, 'source_id' => $id])->value('source_hash');
    }

    public function registrar(string $tabela, string $id, string $alvoTabela, ?int $alvoId, string $hash, string $status, ?string $motivo = null): void
    {
        DB::table(self::TABELA)->updateOrInsert(
            ['source_table' => $tabela, 'source_id' => $id],
            [
                'target_table' => $alvoTabela, 'target_id' => $alvoId, 'source_hash' => $hash,
                'status' => $status, 'rejection_reason' => $motivo,
                'imported_at' => $status === 'importado' ? now() : null, 'updated_at' => now(), 'created_at' => now(),
            ],
        );
    }
}
```

`app/Modules/Demandas/Importacao/MapaLegado.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

use App\Modules\Demandas\Enums\StatusDemanda;

/**
 * Traducao dos valores do cedec-demanda. Status alem do enum da migration
 * (resolvido, fechado, reaberto) aparecem no codigo do legado e sao aceitos.
 */
final class MapaLegado
{
    public static function status(string $legado): StatusDemanda
    {
        return match (mb_strtolower(trim($legado))) {
            'em andamento', 'reaberto' => StatusDemanda::EM_PROGRESSO,
            'concluido', 'resolvido', 'fechado' => StatusDemanda::RESOLVIDA,
            default => StatusDemanda::ABERTA,
        };
    }

    public static function hash(array|object $linha): string
    {
        return hash('sha256', json_encode((array) $linha, JSON_UNESCAPED_UNICODE));
    }

    public static function json(mixed $valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }
        $decodificado = is_string($valor) ? json_decode($valor, true) : null;

        return is_array($decodificado) ? $decodificado : [];
    }
}
```

`app/Modules/Demandas/Importacao/ResolvedorUsuarioLegado.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

final class ResolvedorUsuarioLegado
{
    public function __construct(private readonly MapaImportacao $mapa) {}

    public function resolver(?int $legadoId): ?int
    {
        return $legadoId === null ? null : $this->mapa->alvo('users', (string) $legadoId);
    }
}
```

- [ ] **Step 5: Etapas**

Todas as etapas seguem o mesmo esqueleto: ler a conexao `config('demandas.importacao.conexao')` em lotes por `id` (`orderBy('id')->chunkById($lote, ...)`), calcular hash, pular se igual, gravar numa `DB::transaction` por lote, registrar no mapa. O esqueleto comum fica numa classe base para nao repetir.

`app/Modules/Demandas/Importacao/Etapas/EtapaBase.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Importacao\EtapaImportacao;
use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Importacao\MapaLegado;
use App\Modules\Demandas\Importacao\RelatorioEtapa;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class EtapaBase implements EtapaImportacao
{
    public function __construct(protected readonly MapaImportacao $mapa) {}

    /** Tabela de origem no cedec-demanda. */
    abstract protected function tabelaOrigem(): string;

    /** Tabela de destino no NewSDC, gravada no mapa. */
    abstract protected function tabelaDestino(): string;

    /**
     * Grava UMA linha. Devolve o id de destino, ou string com o motivo da
     * rejeicao. Recebe o id ja mapeado quando e atualizacao.
     */
    abstract protected function gravar(object $linha, ?int $destinoExistente): int|string;

    public function executar(bool $dryRun, ?string $desde, int $lote): RelatorioEtapa
    {
        $relatorio = new RelatorioEtapa($this->nome());

        $this->consulta($desde)->chunkById($lote, function ($linhas) use ($dryRun, $relatorio): void {
            // Um lote = uma transacao no destino. Em dry-run a transacao sempre
            // termina em rollback: o relatorio sai completo e nada fica gravado.
            DB::beginTransaction();
            try {
                foreach ($linhas as $linha) {
                    $this->processar($linha, $relatorio);
                }
                $dryRun ? DB::rollBack() : DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        });

        return $relatorio;
    }

    protected function processar(object $linha, RelatorioEtapa $relatorio): void
    {
        $relatorio->lidos++;
        $id = (string) $linha->id;
        $hash = MapaLegado::hash($linha);
        if ($this->mapa->hash($this->tabelaOrigem(), $id) === $hash) {
            $relatorio->ignorados++;

            return;
        }

        $existente = $this->mapa->alvo($this->tabelaOrigem(), $id);
        $resultado = $this->gravar($linha, $existente);
        if (is_string($resultado)) {
            $relatorio->rejeitar($resultado);
            $this->mapa->registrar($this->tabelaOrigem(), $id, $this->tabelaDestino(), null, $hash, 'rejeitado', $resultado);

            return;
        }

        $existente === null ? $relatorio->importados++ : $relatorio->atualizados++;
        $this->mapa->registrar($this->tabelaOrigem(), $id, $this->tabelaDestino(), $resultado, $hash, 'importado');
    }

    protected function consulta(?string $desde): Builder
    {
        $q = $this->origem()->table($this->tabelaOrigem());
        if ($desde !== null && $this->temUpdatedAt()) {
            $q->where('updated_at', '>=', $desde);
        }

        return $q;
    }

    protected function temUpdatedAt(): bool
    {
        return true;
    }

    protected function origem(): ConnectionInterface
    {
        return DB::connection((string) config('demandas.importacao.conexao'));
    }
}
```

Nota sobre `--dry-run`: dentro de `DatabaseTransactions` o `beginTransaction` vira savepoint, entao o rollback do lote nao desfaz a transacao do teste. Em dry-run, etapas seguintes nao acham o mapa das anteriores (foi desfeito) e rejeitam por `chamado_nao_importado` — esperado, e o relatorio deixa isso visivel.

`Etapas/ImportarUsuarios.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Models\User;

/** So mapeia; nunca cria conta. CPF, depois e-mail. Ambiguidade rejeita. */
final class ImportarUsuarios extends EtapaBase
{
    public function nome(): string { return 'usuarios'; }
    protected function tabelaOrigem(): string { return 'users'; }
    protected function tabelaDestino(): string { return 'users'; }
    protected function temUpdatedAt(): bool { return false; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        $cpf = preg_replace('/\D/', '', (string) ($linha->cpf ?? ''));
        if ($cpf !== '' && strlen($cpf) === 11) {
            $ids = User::query()->whereRaw("regexp_replace(coalesce(cpf, ''), '\\D', '', 'g') = ?", [$cpf])->limit(2)->pluck('id');
            if ($ids->count() === 1) {
                return (int) $ids->first();
            }
            if ($ids->count() > 1) {
                return 'cpf_ambiguo';
            }
        }

        $email = mb_strtolower(trim((string) ($linha->email ?? '')));
        if ($email !== '') {
            $ids = User::query()->whereRaw('lower(email) = ?', [$email])->limit(2)->pluck('id');
            if ($ids->count() === 1) {
                return (int) $ids->first();
            }
            if ($ids->count() > 1) {
                return 'email_ambiguo';
            }
        }

        return 'usuario_sem_correspondencia';
    }
}
```

`Etapas/ImportarCatalogo.php` (categorias e assuntos; duas origens, entao sobrescreve `executar`):

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Importacao\MapaLegado;
use App\Modules\Demandas\Importacao\RelatorioEtapa;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Models\DemandaCategoria;

/** Nome e a chave natural: o que ja foi cadastrado no catalogo e reaproveitado. */
final class ImportarCatalogo extends EtapaBase
{
    private string $origemAtual = 'chamados_categorias';

    public function nome(): string { return 'catalogo'; }
    protected function tabelaOrigem(): string { return $this->origemAtual; }
    protected function tabelaDestino(): string { return $this->origemAtual === 'assuntos' ? 'demanda_assuntos' : 'demanda_categorias'; }
    protected function temUpdatedAt(): bool { return false; }

    public function executar(bool $dryRun, ?string $desde, int $lote): RelatorioEtapa
    {
        $this->origemAtual = 'chamados_categorias';
        $categorias = parent::executar($dryRun, $desde, $lote);
        $this->origemAtual = 'assuntos';
        $assuntos = parent::executar($dryRun, $desde, $lote);

        foreach (['lidos', 'importados', 'atualizados', 'ignorados', 'rejeitados'] as $campo) {
            $categorias->{$campo} += $assuntos->{$campo};
        }
        $categorias->motivos = array_merge($categorias->motivos, $assuntos->motivos);

        return $categorias;
    }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        $nome = trim((string) $linha->nome);
        if ($nome === '') {
            return 'nome_vazio';
        }

        if ($this->origemAtual === 'chamados_categorias') {
            $categoria = DemandaCategoria::query()->whereRaw('lower(nome) = ?', [mb_strtolower($nome)])->first()
                ?? DemandaCategoria::create(['nome' => $nome, 'descricao' => $linha->descricao ?? null, 'ativo' => true]);

            return (int) $categoria->id;
        }

        $assunto = DemandaAssunto::query()->whereRaw('lower(nome) = ?', [mb_strtolower($nome)])->first() ?? new DemandaAssunto(['nome' => mb_substr($nome, 0, 150), 'ativo' => true]);
        $assunto->campos_dinamicos = MapaLegado::json($linha->campos_dinamicos ?? null);
        $automacao = MapaLegado::json($linha->form_automacao ?? null);
        $assunto->form_automacao = $automacao === [] ? null : $automacao;
        $assunto->save();

        return (int) $assunto->id;
    }
}
```

`Etapas/ImportarChamados.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Importacao\MapaLegado;
use App\Modules\Demandas\Importacao\ResolvedorUsuarioLegado;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Services\HistoricoDemanda;
use Carbon\CarbonImmutable;

/**
 * Grava direto no model, sem DemandaWriteService nem workflow: a carga preserva
 * datas e estado finais do legado, sem simular transicoes nem disparar eventos.
 */
final class ImportarChamados extends EtapaBase
{
    public function __construct(
        MapaImportacao $mapa,
        private readonly ResolvedorUsuarioLegado $usuarios,
        private readonly HistoricoDemanda $historico,
    ) {
        parent::__construct($mapa);
    }

    public function nome(): string { return 'chamados'; }
    protected function tabelaOrigem(): string { return 'chamados'; }
    protected function tabelaDestino(): string { return 'tasks'; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        $solicitante = $this->usuarios->resolver((int) $linha->solicitante_id);
        if ($solicitante === null) {
            return 'solicitante_nao_mapeado';
        }
        $criador = $linha->criador_id !== null ? $this->usuarios->resolver((int) $linha->criador_id) : $solicitante;
        if ($criador === null) {
            return 'criador_nao_mapeado';
        }
        $responsavel = $linha->destinatario_id !== null ? $this->usuarios->resolver((int) $linha->destinatario_id) : null;
        if ($linha->destinatario_id !== null && $responsavel === null) {
            return 'destinatario_nao_mapeado';
        }

        $status = MapaLegado::status((string) $linha->status);
        $prioridade = PrioridadeSimples::tryFrom((string) $linha->prioridade) ?? PrioridadeSimples::MEDIA;
        $criadoEm = CarbonImmutable::parse($linha->data_hora ?? $linha->created_at);
        $fechadoEm = $status === StatusDemanda::RESOLVIDA
            ? CarbonImmutable::parse($linha->data_fechamento ?? $linha->updated_at)
            : null;
        $assuntoId = $linha->assunto_id !== null ? $this->mapa->alvo('assuntos', (string) $linha->assunto_id) : null;

        $demanda = $destinoExistente !== null ? Demanda::withTrashed()->find($destinoExistente) : null;
        $novo = $demanda === null;
        $demanda ??= new Demanda();

        $demanda->forceFill([
            'tipo' => TipoDemanda::SOLICITACAO,
            'titulo' => mb_substr((string) $linha->titulo, 0, 255),
            'descricao' => (string) $linha->mensagem,
            'status' => $status,
            'impacto' => $prioridade->impacto(),
            'urgencia' => $prioridade->urgencia(),
            'solicitante_id' => $solicitante,
            'criado_por_id' => $criador,
            'atribuido_para_id' => $responsavel ?? ($status === StatusDemanda::ABERTA ? null : $criador),
            'assunto_id' => $assuntoId,
            'campos_customizados' => array_merge(MapaLegado::json($linha->dados_adicionais ?? null), [
                '_legado' => array_filter([
                    'id' => (int) $linha->id,
                    'tipo' => $linha->tipo,
                    'grupo' => $linha->grupo,
                    'obs' => $linha->obs,
                    'encaminhado_para_id' => $linha->encaminhado_para_id !== null ? $this->usuarios->resolver((int) $linha->encaminhado_para_id) : null,
                ], static fn ($v) => $v !== null && $v !== ''),
            ]),
            'resolvido_em' => $fechadoEm,
            'tempo_total_resolucao' => $fechadoEm !== null ? (int) round(abs($criadoEm->diffInMinutes($fechadoEm))) : null,
        ]);

        if ($novo) {
            // Protocolo com o ano original: sequencia gerada pelo proprio model,
            // mas o prefixo de ano vem da abertura legada.
            $demanda->protocolo = sprintf('%s-%d-%s', TipoDemanda::SOLICITACAO->getProtocoloPrefix(), $criadoEm->year, 'L'.str_pad((string) $linha->id, 6, '0', STR_PAD_LEFT));
        }
        $demanda->created_at = $criadoEm;
        $demanda->updated_at = CarbonImmutable::parse($linha->updated_at ?? $criadoEm);
        $demanda->timestamps = false;
        $demanda->saveQuietly();
        $demanda->timestamps = true;

        if ($novo) {
            $this->historico->registrar(
                $demanda, $criador, AcaoHistoricoDemanda::IMPORTADA,
                'Chamado #'.$linha->id.' importado do cedec-demanda.', ['legado_id' => (int) $linha->id], em: $criadoEm,
            );
        }

        return (int) $demanda->id;
    }
}
```
Protocolo legado: `SOL-2026-L000199`. O `L` impede colisao com a sequencia numerica do model (`gerarProtocolo` usa `max` sobre o padrao `PREFIX-ANO-%` e `preg_match('/-(\d+)$/')`; conferir que `L000199` nao quebra esse `max` — como `max` e lexicografico e `L` > digitos, o `max` passaria a devolver o legado. Corrigir `Demanda::gerarProtocolo` para filtrar `->where('protocolo', 'not like', "{$prefix}-{$ano}-L%")` e incluir isso neste commit.)

`Etapas/ImportarHistorico.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Importacao\ResolvedorUsuarioLegado;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Services\HistoricoDemanda;
use Carbon\CarbonImmutable;

final class ImportarHistorico extends EtapaBase
{
    public function __construct(
        MapaImportacao $mapa,
        private readonly ResolvedorUsuarioLegado $usuarios,
        private readonly HistoricoDemanda $historico,
    ) {
        parent::__construct($mapa);
    }

    public function nome(): string { return 'historico'; }
    protected function tabelaOrigem(): string { return 'historico_chamados'; }
    protected function tabelaDestino(): string { return 'task_audit_logs'; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        if ($destinoExistente !== null) {
            return $destinoExistente; // log e imutavel; hash novo so reconfirma
        }
        $demandaId = $this->mapa->alvo('chamados', (string) $linha->chamado_id);
        if ($demandaId === null) {
            return 'chamado_nao_importado';
        }

        $log = $this->historico->registrar(
            Demanda::withTrashed()->findOrFail($demandaId),
            $this->usuarios->resolver((int) $linha->user_id),
            $this->acao((string) $linha->acao),
            trim((string) ($linha->detalhes ?? '')) ?: (string) $linha->acao,
            ['legado_acao' => (string) $linha->acao],
            em: CarbonImmutable::parse($linha->created_at),
        );

        return (int) $log->id;
    }

    private function acao(string $legado): AcaoHistoricoDemanda
    {
        $a = mb_strtolower($legado);

        return match (true) {
            str_contains($a, 'criou') => AcaoHistoricoDemanda::CRIADA,
            str_contains($a, 'transfer') => AcaoHistoricoDemanda::ATRIBUIDA,
            str_contains($a, 'status') => AcaoHistoricoDemanda::STATUS_ALTERADO,
            str_contains($a, 'anexo') => AcaoHistoricoDemanda::ANEXO_ADICIONADO,
            default => AcaoHistoricoDemanda::EDITADA,
        };
    }
}
```
Rotulo exibido: `HistoricoDemanda` grava `metadata.rotulo` com o rotulo do enum; para o historico legado preservar o texto original, o controller da Task 9 ja exibe `metadata.rotulo` — ajustar `HistoricoDemanda::registrar` para aceitar `metadata['rotulo']` ja informado (`array_merge(['rotulo' => $acao->rotulo()], $metadata, ['detalhes' => $detalhes])`) e aqui passar `['legado_acao' => ..., 'rotulo' => (string) $linha->acao]`.

`Etapas/ImportarComentarios.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Importacao\ResolvedorUsuarioLegado;
use App\Modules\Demandas\Models\DemandaComentario;
use Carbon\CarbonImmutable;

final class ImportarComentarios extends EtapaBase
{
    public function __construct(MapaImportacao $mapa, private readonly ResolvedorUsuarioLegado $usuarios)
    {
        parent::__construct($mapa);
    }

    public function nome(): string { return 'comentarios'; }
    protected function tabelaOrigem(): string { return 'comentarios'; }
    protected function tabelaDestino(): string { return 'task_comments'; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        $demandaId = $this->mapa->alvo('chamados', (string) $linha->chamado_id);
        if ($demandaId === null) {
            return 'chamado_nao_importado';
        }
        $autor = $this->usuarios->resolver((int) $linha->user_id);
        if ($autor === null) {
            return 'autor_nao_mapeado';
        }

        $comentario = $destinoExistente !== null ? DemandaComentario::find($destinoExistente) : null;
        $comentario ??= new DemandaComentario();
        $comentario->forceFill([
            'task_id' => $demandaId, 'user_id' => $autor, 'tipo' => 'comentario',
            'conteudo' => (string) $linha->comentario, 'interno' => false,
        ]);
        $comentario->created_at = CarbonImmutable::parse($linha->created_at);
        $comentario->timestamps = false;
        $comentario->saveQuietly();

        return (int) $comentario->id;
    }
}
```

`Etapas/ImportarAnexos.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Models\DemandaAnexo;
use Illuminate\Support\Facades\Storage;

/**
 * Copia o arquivo do disco legado. Arquivo ausente e rejeitado com motivo e
 * aparece no relatorio; o registro nao e criado apontando para lugar nenhum.
 */
final class ImportarAnexos extends EtapaBase
{
    public function nome(): string { return 'anexos'; }
    protected function tabelaOrigem(): string { return 'anexos'; }
    protected function tabelaDestino(): string { return 'task_attachments'; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        if ($destinoExistente !== null) {
            return $destinoExistente;
        }
        $demandaId = $this->mapa->alvo('chamados', (string) $linha->chamado_id);
        if ($demandaId === null) {
            return 'chamado_nao_importado';
        }

        $origem = Storage::disk((string) config('demandas.importacao.disk'));
        $caminho = ltrim((string) $linha->caminho_arquivo, '/');
        if ($caminho === '' || str_contains($caminho, '..') || ! $origem->exists($caminho)) {
            return 'arquivo_ausente';
        }

        $conteudo = $origem->get($caminho);
        $destino = 'demandas/'.$demandaId.'/legado-'.$linha->id.'-'.basename($caminho);
        Storage::disk((string) config('demandas.anexos.disk'))->put($destino, $conteudo);

        $anexo = DemandaAnexo::create([
            'task_id' => $demandaId,
            'user_id' => null,
            'nome_original' => (string) $linha->nome_original,
            'nome_arquivo' => basename($destino),
            'mime_type' => $origem->mimeType($caminho) ?: 'application/octet-stream',
            'tamanho_bytes' => strlen((string) $conteudo),
            'path' => $destino,
        ]);

        return (int) $anexo->id;
    }
}
```
Se `task_attachments.user_id` for NOT NULL, usar o criador da demanda (`Demanda::find($demandaId)->criado_por_id`).

- [ ] **Step 6: Comando e registro**

`app/Modules/Demandas/Console/ImportarLegadoCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Console;

use App\Modules\Demandas\Importacao\EtapaImportacao;
use App\Modules\Demandas\Support\ContextoImportacao;
use Illuminate\Console\Command;

class ImportarLegadoCommand extends Command
{
    protected $signature = 'demandas:importar-legado
        {--dry-run : Simula e mostra o relatorio sem gravar}
        {--etapa= : usuarios, catalogo, chamados, historico, comentarios ou anexos}
        {--desde= : So registros com updated_at a partir desta data (delta de corte)}
        {--lote=200 : Tamanho do lote}';

    protected $description = 'Importa chamados do cedec-demanda para Demandas, de forma idempotente';

    /** @param iterable<EtapaImportacao> $etapas */
    public function handle(ContextoImportacao $contexto): int
    {
        /** @var iterable<EtapaImportacao> $etapas */
        $etapas = app()->tagged('demandas.importacao.etapas');
        $filtro = $this->option('etapa');
        $linhas = [];

        $contexto->durante(function () use ($etapas, $filtro, &$linhas): void {
            foreach ($etapas as $etapa) {
                if ($filtro !== null && $etapa->nome() !== $filtro) {
                    continue;
                }
                $relatorio = $etapa->executar((bool) $this->option('dry-run'), $this->option('desde'), max(1, (int) $this->option('lote')));
                $linhas[] = $relatorio->toArray();
                foreach ($relatorio->motivos as $motivo => $n) {
                    $this->warn(sprintf('  %s: %d rejeitado(s) por %s', $etapa->nome(), $n, $motivo));
                }
            }
        });

        $this->table(['etapa', 'lidos', 'importados', 'atualizados', 'ignorados', 'rejeitados'], $linhas);
        if ($this->option('dry-run')) {
            $this->info('Dry-run: nada foi gravado.');
        }

        return self::SUCCESS;
    }
}
```

Em `DemandasServiceProvider::register()`:

```php
        // Ordem importa: cada etapa depende do mapa gravado pela anterior.
        $this->app->tag([
            ImportarUsuarios::class,
            ImportarCatalogo::class,
            ImportarChamados::class,
            ImportarHistorico::class,
            ImportarComentarios::class,
            ImportarAnexos::class,
        ], 'demandas.importacao.etapas');
```
e em `boot()` acrescentar `ImportarLegadoCommand::class` ao `$this->commands([...])` da Task 12. Imports correspondentes.

- [ ] **Step 7: Run test to verify it passes**

Run: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas/ImportacaoLegadoTest.php`
Expected: 2 PASS. Rodar a suite: `bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas tests/Unit/Demandas tests/Unit/Ranking` — tudo PASS.

- [ ] **Step 8: Dry-run contra o MySQL real do legado (somente leitura)**

Subir o MySQL do Laragon (memoria `etl-compdec-plancon`), apontar `DB_CEDEC_DEMANDA_*` no `.env` da worktree para a copia local do cedec-demanda e rodar:

```bash
bash /c/tmp/teste-demandas.sh php artisan config:clear
bash /c/tmp/teste-demandas.sh php artisan demandas:importar-legado --dry-run
```
Expected: tabela com contagens por etapa; anotar rejeitados por motivo para o relatorio de homologacao. NAO rodar sem `--dry-run` contra `sdc` nesta task.

- [ ] **Step 9: Commit**

```bash
git add config/database.php config/filesystems.php app/Modules/Demandas/Importacao app/Modules/Demandas/Console/ImportarLegadoCommand.php app/Modules/Demandas/DemandasServiceProvider.php app/Modules/Demandas/Services/HistoricoDemanda.php app/Modules/Demandas/Models/Demanda.php
git commit -m "🗃️ db(demandas): importacao idempotente dos chamados do cedec-demanda"
```

---

### Task 14: Frontend — base (atoms, composables, limpeza de mocks)

**Files:**
- Modify: `resources/js/Components/Atoms/Demandas/DemandaStatusBadge.vue`
- Modify: `resources/js/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue`
- Create: `resources/js/Composables/demandas/{useDemandaFilters,useDemandaAutosave,useDemandaResolucao,index}.js`
- Create: `resources/js/Support/demandasFormat.js`
- Delete: `resources/js/domain/demandas/`, `resources/js/infrastructure/demandas/`
- Test: `npm run build` (sem teste unitario JS no projeto)

**Interfaces:**
- Produces:
  - `<DemandaStatusBadge :etapa="'aberto'|'em_andamento'|'concluido'|'cancelado'" :label="string" />`
  - `<DemandaPrioridadeBadge :prioridade="'baixa'|'media'|'alta'" :label="string" :itil="string|null" />`
  - `useDemandaFilters(filtrosIniciais, rota = 'demandas.index') -> { local, aplicar(extra?), limpar(), filtrarEtapa(etapa) }`
  - `useDemandaAutosave(demandaId) -> { salvando, salvar(campos) }` (PUT `admin.demandas.update`, `preserveScroll`)
  - `useDemandaResolucao(demanda) -> { form, erroAbertura, erroFechamento, enviar() }`
  - `formatarDataHora(iso) -> 'dd/mm/aaaa HH:MM'`, `formatarBytes(n)`.

- [ ] **Step 1: Verificar quem usa os mocks antes de remover**

```bash
grep -rn "domain/demandas\|infrastructure/demandas\|useDemandas" resources/js --include=*.vue --include=*.js
```
Expected: so arquivos dentro das proprias pastas, `Pages/Demandas/*` (reescritas nas Tasks 15–17) ou `Templates/Demandas/*`. Qualquer outro consumidor: ajustar para props reais antes de apagar.

- [ ] **Step 2: Atoms**

`resources/js/Components/Atoms/Demandas/DemandaStatusBadge.vue`:

```vue
<template>
  <Badge :cor="COR[etapa] ?? 'slate'" size="sm">{{ label }}</Badge>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';

defineProps({
  etapa: { type: String, required: true },
  label: { type: String, required: true },
});

// Mesmas cores das capturas do legado: aberto em vermelho, concluido em verde.
const COR = { aberto: 'red', em_andamento: 'amber', concluido: 'emerald', cancelado: 'slate' };
</script>
```

`resources/js/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue`:

```vue
<template>
  <Badge :cor="COR[prioridade] ?? 'slate'" size="sm" :title="itil ? `Prioridade ITIL: ${itil}` : undefined">
    {{ label }}
  </Badge>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';

defineProps({
  prioridade: { type: String, required: true },
  label: { type: String, required: true },
  itil: { type: String, default: null },
});

const COR = { baixa: 'sky', media: 'amber', alta: 'orange' };
</script>
```

- [ ] **Step 3: Formatadores e composables**

`resources/js/Support/demandasFormat.js`:

```js
const DATA_HORA = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' });

export function formatarDataHora(iso) {
  if (!iso) return '—';
  return DATA_HORA.format(new Date(iso)).replace(',', '');
}

export function formatarBytes(bytes) {
  if (!bytes) return '0 B';
  const unidades = ['B', 'KB', 'MB'];
  const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), unidades.length - 1);
  return `${(bytes / 1024 ** i).toFixed(i === 0 ? 0 : 1)} ${unidades[i]}`;
}

// datetime-local exige 'YYYY-MM-DDTHH:MM' no fuso local.
export function paraInputLocal(iso) {
  const d = iso ? new Date(iso) : new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
```

`resources/js/Composables/demandas/useDemandaFilters.js`:

```js
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const VAZIO = {
  search: '', etapa: '', prioridade: '', assunto_id: '', solicitante_id: '',
  responsavel_id: '', criado_por_id: '', data_inicial: '', data_final: '',
};

export function useDemandaFilters(filtrosIniciais = {}, rota = 'demandas.index') {
  const local = reactive({ ...VAZIO, ...filtrosIniciais });

  function aplicar(extra = {}) {
    const params = Object.fromEntries(
      Object.entries({ ...local, ...extra }).filter(([, v]) => v !== '' && v !== null && v !== undefined),
    );
    router.get(route(rota), params, { preserveState: true, preserveScroll: true, replace: true });
  }

  function limpar() {
    Object.assign(local, VAZIO);
    aplicar();
  }

  function filtrarEtapa(etapa) {
    local.etapa = local.etapa === etapa ? '' : etapa;
    aplicar();
  }

  return { local, aplicar, limpar, filtrarEtapa };
}
```

`resources/js/Composables/demandas/useDemandaAutosave.js`:

```js
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

export function useDemandaAutosave(demandaId) {
  const salvando = ref(false);

  function salvar(campos) {
    salvando.value = true;
    router.put(route('admin.demandas.update', demandaId), campos, {
      preserveScroll: true,
      preserveState: true,
      onFinish: () => { salvando.value = false; },
    });
  }

  return { salvando, salvar };
}
```

`resources/js/Composables/demandas/useDemandaResolucao.js`:

```js
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { formatarDataHora, paraInputLocal } from '@/Support/demandasFormat';

export function useDemandaResolucao(demanda) {
  const form = useForm({
    aberta_em: paraInputLocal(demanda.created_at),
    resolvida_em: paraInputLocal(null),
  });

  // Mesma validacao cruzada do legado, antes de ir ao servidor; o FormRequest
  // repete a regra e e ele quem decide.
  const erroAbertura = computed(() => (form.aberta_em > form.resolvida_em
    ? `A data não pode ser posterior ao fechamento (${formatarDataHora(form.resolvida_em)}).` : form.errors.aberta_em));
  const erroFechamento = computed(() => (form.resolvida_em < form.aberta_em
    ? `A data não pode ser anterior à abertura (${formatarDataHora(form.aberta_em)}).` : form.errors.resolvida_em));

  function enviar() {
    if (erroAbertura.value || erroFechamento.value) return;
    form.post(route('demandas.resolver', demanda.id), { preserveScroll: true });
  }

  return { form, erroAbertura, erroFechamento, enviar };
}
```

`resources/js/Composables/demandas/index.js`:

```js
export { useDemandaFilters } from './useDemandaFilters';
export { useDemandaAutosave } from './useDemandaAutosave';
export { useDemandaResolucao } from './useDemandaResolucao';
```

- [ ] **Step 4: Remover mocks**

```bash
git rm -r resources/js/domain/demandas resources/js/infrastructure/demandas
```
Se `resources/js/Composables/useDemandas.js` ou `Templates/Demandas/DemandasIndexTemplate.vue` antigos existirem e so servirem as paginas reescritas, remover tambem (o template e recriado na Task 15).

- [ ] **Step 5: Build**

```bash
cd SDC && npm install && npx vite build
```
Expected: build sem erros (as Pages antigas ainda compilam ou foram ajustadas no Step 1). Se o `prebuild` do Ziggy exigir PHP, gerar antes: `bash /c/tmp/teste-demandas.sh php artisan ziggy:generate` (ou o script do `package.json`).

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/Atoms/Demandas resources/js/Composables/demandas resources/js/Support/demandasFormat.js
git commit -m "♻️ refactor(demandas): badges por etapa, composables e remocao dos mocks"
```

---

### Task 15: Frontend — listagem (Index)

**Files:**
- Create: `resources/js/Templates/Demandas/DemandasIndexTemplate.vue`
- Create: `resources/js/Components/Organisms/Demandas/Statistics/DemandasStatisticsCards.vue`
- Create: `resources/js/Components/Organisms/Demandas/Filters/DemandasFiltersSection.vue`
- Create: `resources/js/Components/Organisms/Demandas/Table/DemandasTable.vue`
- Modify (reescrever): `resources/js/Pages/Demandas/DemandasIndex.vue`

**Interfaces:**
- Consumes: props da Task 9 (`demandas, estatisticas, filtros, opcoes, pode`), `useDemandaFilters`, badges da Task 14.

- [ ] **Step 1: Organisms**

`Components/Organisms/Demandas/Statistics/DemandasStatisticsCards.vue`:

```vue
<template>
  <StatCardsGrid :colunas="4" :espaco-inferior="false">
    <StatCard title="Abertos" :value="estatisticas.abertas" variant="danger" :icon="InboxIcon" clickable @click="$emit('filtrar', 'aberto')" />
    <StatCard title="Em andamento" :value="estatisticas.em_andamento" variant="warning" :icon="ClockIcon" clickable @click="$emit('filtrar', 'em_andamento')" />
    <StatCard title="Resolvidos hoje" :value="estatisticas.resolvidas_hoje" variant="success" :icon="CheckCircleIcon" />
    <StatCard title="Concluídos" :value="estatisticas.concluidas" variant="info" :icon="CheckBadgeIcon" clickable @click="$emit('filtrar', 'concluido')" />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import InboxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';

defineProps({ estatisticas: { type: Object, required: true } });
defineEmits(['filtrar']);
</script>
```

`Components/Organisms/Demandas/Filters/DemandasFiltersSection.vue`:

```vue
<template>
  <FilterSection title="Filtros" :columns="4">
    <FilterField v-model="filtros.search" label="Busca" placeholder="Título, protocolo ou descrição" @keyup.enter="$emit('aplicar')" />
    <FilterField v-model="filtros.assunto_id" label="Assunto" type="select" :options="comTodos(opcoes.assuntos)" />
    <FilterField v-model="filtros.etapa" label="Status" type="select" :options="comTodos(opcoes.etapas)" />
    <FilterField v-model="filtros.prioridade" label="Prioridade" type="select" :options="comTodos(opcoes.prioridades)" />
    <template v-if="opcoes.usuarios.length">
      <FilterField v-model="filtros.solicitante_id" label="Solicitante" type="select" :options="comTodos(opcoes.usuarios)" />
      <FilterField v-model="filtros.responsavel_id" label="Responsável" type="select" :options="comTodos(opcoes.usuarios)" />
      <FilterField v-model="filtros.criado_por_id" label="Quem abriu" type="select" :options="comTodos(opcoes.usuarios)" />
    </template>
    <FilterField v-model="filtros.data_inicial" label="Data inicial" type="date" />
    <FilterField v-model="filtros.data_final" label="Data final" type="date" />
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
  opcoes: { type: Object, required: true },
});
defineEmits(['aplicar', 'limpar']);

const comTodos = (lista) => [{ value: '', label: 'Todos' }, ...lista];
</script>
```
Conferir no `FilterField.vue` o formato de `options` (`{value,label}`) e se `type="date"`/`select` existem; se `options` usar outro formato, adaptar `comTodos`.

`Components/Organisms/Demandas/Table/DemandasTable.vue` (tabela em `lg+`, blocos abaixo, igual ao `PrefeituraTable`):

```vue
<template>
  <div v-if="isDesktop" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-900/50">
          <tr>
            <th v-for="col in COLUNAS" :key="col" scope="col" :class="TH">{{ col }}</th>
            <th scope="col" class="table-actions-head w-16 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <tr v-for="d in demandas" :key="d.id" class="table-row-solid transition-colors">
            <td :class="TD_FORTE">#{{ d.legado_id ?? d.id }}</td>
            <td :class="TD"><span class="block max-w-[14rem] truncate" :title="d.titulo">{{ d.titulo }}</span></td>
            <td :class="TD"><span class="block max-w-[10rem] truncate">{{ d.assunto?.nome ?? '—' }}</span></td>
            <td :class="TD">{{ d.assunto?.categoria ?? '—' }}</td>
            <td :class="TD"><span class="block max-w-[8rem] truncate">{{ d.criado_por?.name ?? '—' }}</span></td>
            <td :class="TD"><span class="block max-w-[8rem] truncate">{{ d.solicitante?.name ?? '—' }}</span></td>
            <td :class="TD"><span class="block max-w-[8rem] truncate">{{ d.atribuido_para?.name ?? '—' }}</span></td>
            <td :class="TD"><DemandaStatusBadge :etapa="d.etapa" :label="d.etapa_label" /></td>
            <td :class="TD"><DemandaPrioridadeBadge :prioridade="d.prioridade_simples" :label="d.prioridade_label" :itil="d.prioridade_itil" /></td>
            <td :class="TD">{{ formatarDataHora(d.created_at) }}</td>
            <td :class="TD">{{ formatarDataHora(d.resolvido_em) }}</td>
            <td class="table-actions-cell whitespace-nowrap px-3 py-2 text-right">
              <ActionButton action="view" module="demandas" resource="chamados" :allowed="true" :show-label="false" size="sm" tooltip-text="Abrir demanda" @click="$emit('abrir', d.id)" />
            </td>
          </tr>
          <tr v-if="demandas.length === 0">
            <td :colspan="COLUNAS.length + 1" class="px-3 py-10">
              <ListEmptyState title="Nenhuma demanda encontrada" helper="Ajuste os filtros ou use Limpar para ver todas." />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div v-else class="space-y-3">
    <article v-for="d in demandas" :key="d.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60" @click="$emit('abrir', d.id)">
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="text-xs text-slate-500 dark:text-slate-400">#{{ d.legado_id ?? d.id }} · {{ d.protocolo }}</p>
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ d.titulo }}</h3>
        </div>
        <DemandaStatusBadge :etapa="d.etapa" :label="d.etapa_label" />
      </header>
      <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
        <div><dt class="text-slate-500">Assunto</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ d.assunto?.nome ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Prioridade</dt><dd><DemandaPrioridadeBadge :prioridade="d.prioridade_simples" :label="d.prioridade_label" /></dd></div>
        <div><dt class="text-slate-500">Solicitante</dt><dd class="truncate text-slate-800 dark:text-slate-200">{{ d.solicitante?.name ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Abertura</dt><dd class="text-slate-800 dark:text-slate-200">{{ formatarDataHora(d.created_at) }}</dd></div>
      </dl>
    </article>
    <ListEmptyState v-if="demandas.length === 0" title="Nenhuma demanda encontrada" helper="Ajuste os filtros ou use Limpar para ver todas." />
  </div>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import { useMobile } from '@/Composables/useMobile';
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({ demandas: { type: Array, required: true } });
defineEmits(['abrir']);

const { isDesktop } = useMobile();
const COLUNAS = ['ID', 'Título', 'Assunto', 'Categoria', 'Quem abriu', 'Solicitante', 'Responsável', 'Status', 'Prioridade', 'Abertura', 'Fechamento'];
const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'whitespace-nowrap px-3 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100';
</script>
```
Conferir se `ActionButton` aceita `module="demandas"` (ele consulta config de acoes); se exigir registro, usar o mesmo `module`/`resource` que a pagina antiga usava, ou `Button` com icone.

- [ ] **Step 2: Template e Page**

`Templates/Demandas/DemandasIndexTemplate.vue`:

```vue
<template>
  <div class="space-y-6 pb-8">
    <PageHeader title="Demandas" description="Chamados abertos para a equipe CEDEC" :icon-image="moduleIcon('demandas')" variant="gradient" :espaco-inferior="false">
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <Button v-if="pode.exportar" variant="secondary" size="md" :icon="DownloadIcon" icon-position="left" @click="$emit('exportar')">Exportar</Button>
          <Button v-if="pode.criar" variant="primary" size="md" :icon="PlusIcon" icon-position="left" @click="$emit('criar')">Nova demanda</Button>
        </div>
      </template>
    </PageHeader>

    <DemandasStatisticsCards :estatisticas="estatisticas" @filtrar="(e) => $emit('filtrar-etapa', e)" />

    <DemandasFiltersSection :filtros="filtros" :opcoes="opcoes" @aplicar="$emit('aplicar')" @limpar="$emit('limpar')" />

    <ListContainer title="Todas as demandas" :icon="DocumentTextIcon" :count="paginacao.total">
      <DemandasTable :demandas="demandas" @abrir="(id) => $emit('abrir', id)" />
      <Pagination class="mt-4" :pagination="paginacao" @page-change="(p) => $emit('pagina', p)" />
    </ListContainer>
  </div>
</template>

<script setup>
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import DemandasStatisticsCards from '@/Components/Organisms/Demandas/Statistics/DemandasStatisticsCards.vue';
import DemandasFiltersSection from '@/Components/Organisms/Demandas/Filters/DemandasFiltersSection.vue';
import DemandasTable from '@/Components/Organisms/Demandas/Table/DemandasTable.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  demandas: { type: Array, required: true },
  paginacao: { type: Object, required: true },
  estatisticas: { type: Object, required: true },
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
  pode: { type: Object, required: true },
});
defineEmits(['criar', 'exportar', 'aplicar', 'limpar', 'filtrar-etapa', 'abrir', 'pagina']);
</script>
```
Regra de ritmo: pai com `space-y-6`, filhos com `:espaco-inferior="false"` onde a prop existe; nenhum `mb-6` nos filhos.

`Pages/Demandas/DemandasIndex.vue` (substituir inteiro):

```vue
<template>
  <DemandasIndexTemplate
    :demandas="demandas.data"
    :paginacao="demandas"
    :estatisticas="estatisticas"
    :filtros="local"
    :opcoes="opcoes"
    :pode="pode"
    @criar="router.visit(route('demandas.create'))"
    @exportar="exportar"
    @aplicar="aplicar()"
    @limpar="limpar"
    @filtrar-etapa="filtrarEtapa"
    @abrir="(id) => router.visit(route('demandas.show', id))"
    @pagina="(p) => aplicar({ page: p })"
  />
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasIndexTemplate from '@/Templates/Demandas/DemandasIndexTemplate.vue';
import { useDemandaFilters } from '@/Composables/demandas';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  demandas: { type: Object, required: true },
  estatisticas: { type: Object, required: true },
  filtros: { type: Object, default: () => ({}) },
  opcoes: { type: Object, required: true },
  pode: { type: Object, required: true },
});

const { local, aplicar, limpar, filtrarEtapa } = useDemandaFilters(props.filtros);
useAtualizacaoAoVivo({ canal: 'listagem.demandas', evento: '.RecursoAtualizado', props: ['demandas', 'estatisticas'] });

function exportar() {
  window.location.href = route('admin.demandas.export', Object.fromEntries(Object.entries(local).filter(([, v]) => v !== '')));
}
</script>
```
Conferir a assinatura exata de `useAtualizacaoAoVivo` e o nome do evento em `RatIndex.vue`.

- [ ] **Step 3: Build e conferencia visual**

```bash
cd SDC && npx vite build
```
Expected: sem erros. A conferencia no navegador fica para a Task 19.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Templates/Demandas/DemandasIndexTemplate.vue resources/js/Components/Organisms/Demandas/Statistics resources/js/Components/Organisms/Demandas/Filters resources/js/Components/Organisms/Demandas/Table resources/js/Pages/Demandas/DemandasIndex.vue
git commit -m "🎨 style(demandas): listagem em atomic design com filtros do legado"
```

---

### Task 16: Frontend — detalhe (Show)

**Files:**
- Create: `resources/js/Templates/Demandas/DemandasShowTemplate.vue`
- Create: `resources/js/Components/Organisms/Demandas/Show/{DemandaDescricaoCard,DemandaAssuntoCard,DemandaAbasAtividade,DemandaEnvolvidosCard,DemandaInformacoesCard,DemandaAnexosCard,DemandaResolverCard,DemandaAutomacaoButton}.vue`
- Create: `resources/js/Components/Molecules/Demandas/{DemandaHistoricoItem,DemandaComentarioForm,CampoDinamico}.vue`
- Modify (reescrever): `resources/js/Pages/Demandas/DemandasShow.vue`

**Interfaces:**
- Consumes: props da Task 9 (`demanda, campos, comentarios, historico, anexos, assuntos, usuarios, automacao, pode`); rotas `admin.demandas.update`, `admin.demandas.assign`, `admin.demandas.change-status`, `demandas.comments.store`, `demandas.attachments.store`, `demandas.resolver`, `demandas.reabrir`, `demandas.automacao`.
- Produces: `CampoDinamico` (`campo {label,tipo}`, `v-model`, `disabled`) reusado na Task 17.

- [ ] **Step 1: Molecules**

`Components/Molecules/Demandas/CampoDinamico.vue`:

```vue
<template>
  <label v-if="campo.tipo === 'checkbox'" class="flex items-center gap-2 rounded-lg border border-slate-200 p-2 text-sm dark:border-slate-700">
    <input type="checkbox" :checked="!!modelValue" :disabled="disabled" class="rounded" @change="$emit('update:modelValue', $event.target.checked)" />
    <span class="text-slate-700 dark:text-slate-200">{{ campo.label }}</span>
  </label>
  <div v-else>
    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ campo.label }} <span class="text-red-500">*</span></label>
    <input type="text" :value="modelValue ?? ''" :disabled="disabled" maxlength="500"
      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
      @input="$emit('update:modelValue', $event.target.value)" @blur="$emit('blur')" />
    <p v-if="erro" class="mt-1 text-xs text-red-500">{{ erro }}</p>
  </div>
</template>

<script setup>
defineProps({
  campo: { type: Object, required: true },
  modelValue: { type: [String, Boolean, Number], default: null },
  disabled: { type: Boolean, default: false },
  erro: { type: String, default: null },
});
defineEmits(['update:modelValue', 'blur']);
</script>
```
Se o projeto tiver `Molecules/Form/FormField` com input/checkbox equivalentes, usar os atoms dele dentro deste componente em vez dos `input` crus (conferir `ls resources/js/Components/Molecules/Form`).

`Components/Molecules/Demandas/DemandaHistoricoItem.vue`:

```vue
<template>
  <li class="rounded-xl border border-slate-200 p-4 dark:border-slate-700/50">
    <div class="flex flex-wrap items-start justify-between gap-2">
      <p class="font-semibold text-slate-900 dark:text-slate-100">{{ item.rotulo }}</p>
      <time class="text-xs text-slate-500">{{ formatarDataHora(item.created_at) }}</time>
    </div>
    <p v-if="item.detalhes" class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ item.detalhes }}</p>
    <p v-if="item.autor" class="mt-2 text-xs text-slate-500">Por: {{ item.autor }}</p>
  </li>
</template>

<script setup>
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({ item: { type: Object, required: true } });
</script>
```

`Components/Molecules/Demandas/DemandaComentarioForm.vue`:

```vue
<template>
  <form class="space-y-2" @submit.prevent="enviar">
    <textarea v-model="form.conteudo" rows="3" maxlength="10000" placeholder="Escreva um comentário"
      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
    <p v-if="form.errors.conteudo" class="text-xs text-red-500">{{ form.errors.conteudo }}</p>
    <div class="flex items-center justify-between gap-2">
      <label v-if="podeInterno" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
        <input v-model="form.interno" type="checkbox" class="rounded" /> Interno (não visível ao solicitante)
      </label>
      <Button type="submit" variant="primary" size="sm" :disabled="form.processing || !form.conteudo.trim()">Comentar</Button>
    </div>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  demandaId: { type: Number, required: true },
  podeInterno: { type: Boolean, default: false },
});

const form = useForm({ conteudo: '', interno: false });

function enviar() {
  form.post(route('demandas.comments.store', props.demandaId), { preserveScroll: true, onSuccess: () => form.reset() });
}
</script>
```

- [ ] **Step 2: Organisms do detalhe**

`Show/DemandaDescricaoCard.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-3 text-lg font-bold text-slate-900 dark:text-slate-100">Descrição</h2>
    <textarea v-model="texto" rows="5" :disabled="!podeEditar"
      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
      @blur="salvarSeMudou" />
    <p v-if="podeEditar" class="mt-2 text-xs text-slate-500">{{ salvando ? 'Salvando…' : 'Clique fora da caixa de texto para salvar as alterações automaticamente.' }}</p>
  </section>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useDemandaAutosave } from '@/Composables/demandas';

const props = defineProps({
  demandaId: { type: Number, required: true },
  descricao: { type: String, default: '' },
  podeEditar: { type: Boolean, default: false },
});

const texto = ref(props.descricao ?? '');
watch(() => props.descricao, (v) => { texto.value = v ?? ''; });
const { salvando, salvar } = useDemandaAutosave(props.demandaId);

function salvarSeMudou() {
  if (texto.value !== (props.descricao ?? '') && texto.value.trim() !== '') salvar({ descricao: texto.value });
}
</script>
```

`Show/DemandaAssuntoCard.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-3 text-lg font-bold text-slate-900 dark:text-slate-100">Assunto</h2>
    <FilterField :model-value="assuntoId ?? ''" label="" type="select" :options="assuntos" :disabled="!podeEditar"
      @update:model-value="(v) => salvar({ assunto_id: v || null, campos_customizados: {} })" />
    <div v-if="campos.length" class="mt-4 grid gap-3 sm:grid-cols-2">
      <CampoDinamico v-for="c in campos" :key="c.label" :campo="c" :model-value="valores[c.label]" :disabled="!podeEditar"
        :erro="erros[`campos_customizados.${c.label}`]"
        @update:model-value="(v) => (valores[c.label] = v)" @blur="salvarCampos" />
    </div>
  </section>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import CampoDinamico from '@/Components/Molecules/Demandas/CampoDinamico.vue';
import { useDemandaAutosave } from '@/Composables/demandas';

const props = defineProps({
  demandaId: { type: Number, required: true },
  assuntoId: { type: Number, default: null },
  assuntos: { type: Array, default: () => [] },
  campos: { type: Array, default: () => [] },
  valoresIniciais: { type: Object, default: () => ({}) },
  podeEditar: { type: Boolean, default: false },
});

const valores = reactive({ ...props.valoresIniciais });
watch(() => props.valoresIniciais, (v) => Object.assign(valores, v));
const erros = computed(() => usePage().props.errors ?? {});
const { salvar } = useDemandaAutosave(props.demandaId);

function salvarCampos() {
  const somenteDoAssunto = Object.fromEntries(props.campos.map((c) => [c.label, valores[c.label] ?? (c.tipo === 'checkbox' ? false : '')]));
  salvar({ campos_customizados: somenteDoAssunto });
}
</script>
```
Trocar o assunto envia `campos_customizados: {}`; se o novo assunto tiver campo texto obrigatorio, o servidor devolve 422 por campo (Review Focus 3) e a tela mostra os campos novos com o erro. Para nao travar a troca, o controller pode aceitar a troca sem campos quando `campos_customizados` vier vazio — decisao: manter a validacao (o assunto novo exige os dados) e o erro aparece sob o campo.

`Show/DemandaAbasAtividade.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <nav class="flex border-b border-slate-200 dark:border-slate-700/50" role="tablist">
      <button v-for="aba in ABAS" :key="aba.id" type="button" role="tab" :aria-selected="ativa === aba.id"
        class="px-5 py-3 text-sm font-medium" :class="ativa === aba.id ? 'border-b-2 border-orange-500 text-orange-600 dark:text-orange-400' : 'text-slate-500'"
        @click="ativa = aba.id">
        {{ aba.label }} ({{ aba.id === 'comentarios' ? comentarios.length : historico.length }})
      </button>
    </nav>
    <div class="p-5">
      <div v-if="ativa === 'comentarios'" class="space-y-4">
        <ul class="space-y-3">
          <li v-for="c in comentarios" :key="c.id" class="rounded-xl border border-slate-200 p-4 dark:border-slate-700/50" :class="c.interno ? 'bg-amber-50 dark:bg-amber-900/10' : ''">
            <div class="flex flex-wrap justify-between gap-2 text-xs text-slate-500">
              <span class="font-semibold text-slate-700 dark:text-slate-200">{{ c.autor }}<span v-if="c.interno"> · interno</span></span>
              <time>{{ formatarDataHora(c.created_at) }}</time>
            </div>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ c.conteudo }}</p>
          </li>
          <li v-if="!comentarios.length" class="text-sm text-slate-500">Nenhum comentário ainda.</li>
        </ul>
        <DemandaComentarioForm :demanda-id="demandaId" :pode-interno="podeInterno" />
      </div>
      <ul v-else class="space-y-3">
        <DemandaHistoricoItem v-for="h in historico" :key="h.id" :item="h" />
      </ul>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import DemandaComentarioForm from '@/Components/Molecules/Demandas/DemandaComentarioForm.vue';
import DemandaHistoricoItem from '@/Components/Molecules/Demandas/DemandaHistoricoItem.vue';
import { formatarDataHora } from '@/Support/demandasFormat';

defineProps({
  demandaId: { type: Number, required: true },
  comentarios: { type: Array, default: () => [] },
  historico: { type: Array, default: () => [] },
  podeInterno: { type: Boolean, default: false },
});

const ABAS = [{ id: 'comentarios', label: 'Comentários' }, { id: 'historico', label: 'Histórico' }];
const ativa = ref('historico');
</script>
```
Se houver `Composables/core/useTabs` ou um molecule de abas, usa-lo em vez do `nav` proprio.

`Show/DemandaEnvolvidosCard.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100"><UserIcon class="h-5 w-5" /> Envolvidos</h2>
    <p class="text-xs text-slate-500">Solicitante</p>
    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ demanda.solicitante?.name ?? '—' }}</p>
    <p v-if="demanda.criado_por && demanda.criado_por.id !== demanda.solicitante?.id" class="mt-1 text-xs text-slate-500">Aberto por {{ demanda.criado_por.name }}</p>
    <p class="mt-4 text-xs text-slate-500">Responsável</p>
    <FilterField v-if="podeGerir" :model-value="demanda.atribuido_para?.id ?? ''" label="" type="select"
      :options="[{ value: '', label: 'Ninguém' }, ...usuarios]" @update:model-value="transferir" />
    <p v-else class="font-semibold text-slate-900 dark:text-slate-100">{{ demanda.atribuido_para?.name ?? 'Ninguém' }}</p>
  </section>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import UserIcon from '@/Components/Icons/UserIcon.vue';

const props = defineProps({
  demanda: { type: Object, required: true },
  usuarios: { type: Array, default: () => [] },
  podeGerir: { type: Boolean, default: false },
});

function transferir(id) {
  if (!id || id === props.demanda.atribuido_para?.id) return;
  router.post(route('admin.demandas.assign', props.demanda.id), { responsavel_id: id }, { preserveScroll: true });
}
</script>
```

`Show/DemandaInformacoesCard.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-slate-100">Informações</h2>
    <p class="text-xs text-slate-500">Status</p>
    <FilterField v-if="podeEditar && opcoesEtapa.length > 1" :model-value="demanda.etapa" label="" type="select" :options="opcoesEtapa" @update:model-value="mudar" />
    <DemandaStatusBadge v-else :etapa="demanda.etapa" :label="demanda.etapa_label" />
    <p v-if="erroStatus" class="mt-1 text-xs text-red-500">{{ erroStatus }}</p>
    <dl class="mt-4 space-y-3 text-sm">
      <div><dt class="text-xs text-slate-500">Categoria</dt><dd class="text-slate-900 dark:text-slate-100">{{ demanda.assunto?.categoria ?? '—' }}</dd></div>
      <div><dt class="text-xs text-slate-500">Prioridade</dt><dd><DemandaPrioridadeBadge :prioridade="demanda.prioridade_simples" :label="demanda.prioridade_label" :itil="demanda.prioridade_itil" /></dd></div>
      <div><dt class="text-xs text-slate-500">Data de abertura</dt><dd class="text-slate-900 dark:text-slate-100">{{ formatarDataHora(demanda.created_at) }}</dd></div>
      <div v-if="demanda.prazo_resolucao"><dt class="text-xs text-slate-500">Prazo (SLA)</dt>
        <dd :class="demanda.sla_resolucao_violado ? 'text-red-500' : 'text-slate-900 dark:text-slate-100'">{{ formatarDataHora(demanda.prazo_resolucao) }}<span v-if="demanda.sla_resolucao_violado"> · vencido</span></dd></div>
      <div v-if="demanda.legado_id"><dt class="text-xs text-slate-500">Chamado no sistema anterior</dt><dd class="text-slate-900 dark:text-slate-100">#{{ demanda.legado_id }}</dd></div>
    </dl>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import { formatarDataHora } from '@/Support/demandasFormat';

const props = defineProps({
  demanda: { type: Object, required: true },
  podeEditar: { type: Boolean, default: false },
});
const emit = defineEmits(['concluir']);

// "Concluido" nao muda status direto: abre o card de resolucao, que pede datas.
const ALVO = { em_andamento: 'em_progresso', cancelado: 'cancelada' };
const opcoesEtapa = computed(() => {
  if (!['aberto', 'em_andamento'].includes(props.demanda.etapa)) return [];
  const base = [{ value: props.demanda.etapa, label: props.demanda.etapa_label }];
  if (props.demanda.etapa === 'aberto') base.push({ value: 'em_andamento', label: 'Em andamento' });
  base.push({ value: 'concluido', label: 'Concluído' }, { value: 'cancelado', label: 'Cancelado' });
  return base;
});
const erroStatus = computed(() => usePage().props.errors?.status);

function mudar(etapa) {
  if (etapa === props.demanda.etapa) return;
  if (etapa === 'concluido') { emit('concluir'); return; }
  router.post(route('admin.demandas.change-status', props.demanda.id), { status: ALVO[etapa] }, { preserveScroll: true });
}
</script>
```

`Show/DemandaAnexosCard.vue`:

```vue
<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-3 flex items-center gap-2 text-lg font-bold text-slate-900 dark:text-slate-100"><PaperClipIcon class="h-5 w-5" /> Anexos ({{ anexos.length }})</h2>
    <ul v-if="anexos.length" class="space-y-2">
      <li v-for="a in anexos" :key="a.id" class="flex items-center justify-between gap-2 text-sm">
        <a :href="a.url" class="min-w-0 truncate text-orange-600 hover:underline dark:text-orange-400">{{ a.nome_original }}</a>
        <span class="shrink-0 text-xs text-slate-500">{{ formatarBytes(a.tamanho_bytes) }}</span>
      </li>
    </ul>
    <p v-else class="text-sm text-slate-500">Nenhum arquivo anexado a esta demanda.</p>
    <form v-if="podeAnexar" class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4 dark:border-slate-700/50" @submit.prevent="enviar">
      <input ref="entrada" type="file" :accept="ACEITOS" class="min-w-0 flex-1 text-sm" @change="form.arquivo = $event.target.files[0] ?? null" />
      <Button type="submit" variant="primary" size="sm" :disabled="!form.arquivo || form.processing">Anexar</Button>
      <p v-if="form.errors.arquivo" class="w-full text-xs text-red-500">{{ form.errors.arquivo }}</p>
    </form>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';
import PaperClipIcon from '@/Components/Icons/PaperClipIcon.vue';
import { formatarBytes } from '@/Support/demandasFormat';

const props = defineProps({
  demandaId: { type: Number, required: true },
  anexos: { type: Array, default: () => [] },
  podeAnexar: { type: Boolean, default: true },
});

const ACEITOS = '.png,.jpg,.jpeg,.pdf,.xlsx,.xls,.csv,.txt';
const entrada = ref(null);
const form = useForm({ arquivo: null });

function enviar() {
  form.post(route('demandas.attachments.store', props.demandaId), {
    forceFormData: true, preserveScroll: true,
    onSuccess: () => { form.reset(); if (entrada.value) entrada.value.value = ''; },
  });
}
</script>
```
Se `Molecules/Upload/DropZone.vue` atender (seletor + lista), usa-lo no lugar do `input` cru.

`Show/DemandaResolverCard.vue`:

```vue
<template>
  <section ref="raiz" class="rounded-xl border border-emerald-300 bg-white p-5 shadow-sm dark:border-emerald-700/50 dark:bg-slate-900/60">
    <template v-if="podeResolver">
      <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-emerald-600 dark:text-emerald-400"><CheckCircleIcon class="h-5 w-5" /> Resolver chamado</h2>
      <label class="block text-sm text-slate-700 dark:text-slate-200">Data/hora de abertura
        <input v-model="form.aberta_em" type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
      </label>
      <p v-if="erroAbertura" class="mt-1 text-xs text-red-500">{{ erroAbertura }}</p>
      <label class="mt-4 block text-sm text-slate-700 dark:text-slate-200">Data/hora de fechamento
        <input v-model="form.resolvida_em" type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
      </label>
      <p v-if="erroFechamento" class="mt-1 text-xs text-red-500">{{ erroFechamento }}</p>
      <Button class="mt-4 w-full" variant="success" size="md" :disabled="form.processing || !!erroAbertura || !!erroFechamento" @click="enviar">Confirmar resolução</Button>
    </template>
    <template v-else-if="podeReabrir">
      <p class="text-sm text-slate-600 dark:text-slate-300">Resolvido em {{ formatarDataHora(demanda.resolvido_em) }}.</p>
      <Button class="mt-3 w-full" variant="secondary" size="md" @click="reabrir">Reabrir chamado</Button>
    </template>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import { useDemandaResolucao } from '@/Composables/demandas';
import { formatarDataHora } from '@/Support/demandasFormat';

const props = defineProps({
  demanda: { type: Object, required: true },
  podeResolver: { type: Boolean, default: false },
  podeReabrir: { type: Boolean, default: false },
});

const raiz = ref(null);
const { form, erroAbertura, erroFechamento, enviar } = useDemandaResolucao(props.demanda);

function reabrir() {
  router.post(route('demandas.reabrir', props.demanda.id), {}, { preserveScroll: true });
}

defineExpose({ focar: () => raiz.value?.scrollIntoView({ behavior: 'smooth', block: 'center' }) });
</script>
```
Conferir se `Button` tem `variant="success"`; se nao, usar `primary`.

`Show/DemandaAutomacaoButton.vue`:

```vue
<template>
  <div>
    <Button variant="primary" size="md" :icon="BoltIcon" icon-position="left" :disabled="enviando" @click="executar">
      {{ enviando ? 'Solicitando…' : 'Automação' }}
    </Button>
    <p v-if="erro" class="mt-1 text-xs text-red-500">{{ erro }}</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';
import BoltIcon from '@/Components/Icons/BoltIcon.vue';

const props = defineProps({ demandaId: { type: Number, required: true } });
const enviando = ref(false);
const erro = computed(() => usePage().props.errors?.automacao);

function executar() {
  enviando.value = true;
  router.post(route('demandas.automacao', props.demandaId), {}, { preserveScroll: true, onFinish: () => { enviando.value = false; } });
}
</script>
```

- [ ] **Step 3: Template e Page**

`Templates/Demandas/DemandasShowTemplate.vue`:

```vue
<template>
  <div class="space-y-6 pb-8">
    <PageHeader :title="`Demanda ${demanda.protocolo}`" :description="demanda.titulo" :icon-image="moduleIcon('demandas')" variant="gradient" :espaco-inferior="false">
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <DemandaStatusBadge :etapa="demanda.etapa" :label="demanda.etapa_label" />
          <DemandaPrioridadeBadge :prioridade="demanda.prioridade_simples" :label="demanda.prioridade_label" :itil="demanda.prioridade_itil" />
          <DemandaAutomacaoButton v-if="automacao.disponivel && pode.automatizar" :demanda-id="demanda.id" />
        </div>
      </template>
    </PageHeader>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="min-w-0 space-y-6 lg:col-span-2">
        <DemandaDescricaoCard :demanda-id="demanda.id" :descricao="demanda.descricao" :pode-editar="pode.editar" />
        <DemandaAssuntoCard :demanda-id="demanda.id" :assunto-id="demanda.assunto?.id ?? null" :assuntos="assuntos" :campos="campos"
          :valores-iniciais="demanda.campos_customizados" :pode-editar="pode.editar" />
        <DemandaAbasAtividade :demanda-id="demanda.id" :comentarios="comentarios" :historico="historico" :pode-interno="pode.comentarInterno" />
      </div>
      <aside class="min-w-0 space-y-6">
        <DemandaEnvolvidosCard :demanda="demanda" :usuarios="usuarios" :pode-gerir="pode.gerir" />
        <DemandaInformacoesCard :demanda="demanda" :pode-editar="pode.editar" @concluir="resolver?.focar()" />
        <DemandaAnexosCard :demanda-id="demanda.id" :anexos="anexos" />
        <DemandaResolverCard v-if="pode.resolver || pode.reabrir" ref="resolver" :demanda="demanda" :pode-resolver="pode.resolver" :pode-reabrir="pode.reabrir" />
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import DemandaDescricaoCard from '@/Components/Organisms/Demandas/Show/DemandaDescricaoCard.vue';
import DemandaAssuntoCard from '@/Components/Organisms/Demandas/Show/DemandaAssuntoCard.vue';
import DemandaAbasAtividade from '@/Components/Organisms/Demandas/Show/DemandaAbasAtividade.vue';
import DemandaEnvolvidosCard from '@/Components/Organisms/Demandas/Show/DemandaEnvolvidosCard.vue';
import DemandaInformacoesCard from '@/Components/Organisms/Demandas/Show/DemandaInformacoesCard.vue';
import DemandaAnexosCard from '@/Components/Organisms/Demandas/Show/DemandaAnexosCard.vue';
import DemandaResolverCard from '@/Components/Organisms/Demandas/Show/DemandaResolverCard.vue';
import DemandaAutomacaoButton from '@/Components/Organisms/Demandas/Show/DemandaAutomacaoButton.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  demanda: { type: Object, required: true },
  campos: { type: Array, default: () => [] },
  comentarios: { type: Array, default: () => [] },
  historico: { type: Array, default: () => [] },
  anexos: { type: Array, default: () => [] },
  assuntos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  automacao: { type: Object, required: true },
  pode: { type: Object, required: true },
});

const resolver = ref(null);
</script>
```

`Pages/Demandas/DemandasShow.vue` (substituir inteiro):

```vue
<template>
  <DemandasShowTemplate v-bind="$props" />
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasShowTemplate from '@/Templates/Demandas/DemandasShowTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  demanda: { type: Object, required: true },
  campos: { type: Array, default: () => [] },
  comentarios: { type: Array, default: () => [] },
  historico: { type: Array, default: () => [] },
  anexos: { type: Array, default: () => [] },
  assuntos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  automacao: { type: Object, required: true },
  pode: { type: Object, required: true },
});
</script>
```

- [ ] **Step 4: Build**

Run: `cd SDC && npx vite build`
Expected: sem erros.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Templates/Demandas/DemandasShowTemplate.vue resources/js/Components/Organisms/Demandas/Show resources/js/Components/Molecules/Demandas resources/js/Pages/Demandas/DemandasShow.vue
git commit -m "🎨 style(demandas): detalhe com descricao, historico, anexos e resolucao"
```

---

### Task 17: Frontend — criacao e catalogo com campos e automacao

**Files:**
- Create: `resources/js/Templates/Demandas/DemandasFormTemplate.vue`
- Modify (reescrever): `resources/js/Pages/Demandas/DemandasCreate.vue`
- Modify: `resources/js/Pages/Demandas/Catalogo.vue`
- Create: `resources/js/Components/Organisms/Demandas/Catalogo/AssuntoCamposEditor.vue`
- Modify ou remover: `resources/js/Components/Organisms/Demandas/Modals/NovaDemandaModal.vue`

**Interfaces:**
- Consumes: props da Task 9 `create` (`assuntos[{value,label,categoria,campos}]`, `prioridades`, `usuarios`, `pode.gerir`); catalogo `categorias`, `assuntos` (com `campos_dinamicos`, `form_automacao`); rotas `demandas.store`, `admin.demandas.assuntos.update`.

- [ ] **Step 1: Form Template e Page**

`Templates/Demandas/DemandasFormTemplate.vue`:

```vue
<template>
  <form class="space-y-6 pb-8" @submit.prevent="$emit('enviar')">
    <PageHeader title="Nova demanda" description="Descreva o que precisa; o assunto define as informações pedidas" :icon-image="moduleIcon('demandas')" variant="gradient" :espaco-inferior="false" />

    <FormSection title="Assunto e prioridade" :cols="2">
      <FilterField v-model="form.assunto_id" label="Assunto" type="select" :options="[{ value: '', label: 'Selecione' }, ...assuntos]" />
      <FilterField v-model="form.prioridade_simples" label="Prioridade" type="select" :options="prioridades" />
      <FilterField v-if="podeGerir" v-model="form.solicitante_id" label="Solicitante (em nome de)" type="select" :options="[{ value: '', label: 'Eu mesmo' }, ...usuarios]" />
      <FilterField v-if="podeGerir" v-model="form.responsavel_id" label="Responsável" type="select" :options="[{ value: '', label: 'Definir depois' }, ...usuarios]" />
    </FormSection>

    <FormSection v-if="campos.length" title="Informações do assunto" :cols="2">
      <CampoDinamico v-for="c in campos" :key="c.label" :campo="c" v-model="form.campos_customizados[c.label]" :erro="form.errors[`campos_customizados.${c.label}`]" />
    </FormSection>

    <FormSection title="Descrição" :cols="1">
      <FilterField v-model="form.titulo" label="Título" placeholder="Resumo curto" />
      <p v-if="form.errors.titulo" class="text-xs text-red-500">{{ form.errors.titulo }}</p>
      <textarea v-model="form.descricao" rows="6" placeholder="Detalhe a demanda"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
      <p v-if="form.errors.descricao" class="text-xs text-red-500">{{ form.errors.descricao }}</p>
    </FormSection>

    <div class="flex justify-end gap-2">
      <Button type="button" variant="secondary" size="md" @click="$emit('cancelar')">Cancelar</Button>
      <Button type="submit" variant="primary" size="md" :disabled="form.processing">Abrir demanda</Button>
    </div>
  </form>
</template>

<script setup>
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import CampoDinamico from '@/Components/Molecules/Demandas/CampoDinamico.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  form: { type: Object, required: true },
  assuntos: { type: Array, default: () => [] },
  campos: { type: Array, default: () => [] },
  prioridades: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  podeGerir: { type: Boolean, default: false },
});
defineEmits(['enviar', 'cancelar']);
</script>
```
Se `Molecules/Form` tiver `FormField`/`FormTextarea`, preferi-los a `FilterField`/`textarea`.

`Pages/Demandas/DemandasCreate.vue` (substituir inteiro):

```vue
<template>
  <DemandasFormTemplate :form="form" :assuntos="assuntos" :campos="campos" :prioridades="prioridades" :usuarios="usuarios" :pode-gerir="pode.gerir"
    @enviar="form.post(route('demandas.store'))" @cancelar="router.visit(route('demandas.index'))" />
</template>

<script setup>
import { computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasFormTemplate from '@/Templates/Demandas/DemandasFormTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  assuntos: { type: Array, default: () => [] },
  prioridades: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  pode: { type: Object, default: () => ({ gerir: false }) },
});

const form = useForm({
  titulo: '', descricao: '', assunto_id: '', prioridade_simples: 'media',
  solicitante_id: '', responsavel_id: '', campos_customizados: {},
});

const campos = computed(() => props.assuntos.find((a) => a.value === Number(form.assunto_id))?.campos ?? []);

// Mesmo comportamento do legado: trocar o assunto zera os campos, com checkbox em false.
watch(campos, (lista) => {
  form.campos_customizados = Object.fromEntries(lista.map((c) => [c.label, c.tipo === 'checkbox' ? false : '']));
});

form.transform((dados) => Object.fromEntries(Object.entries(dados).filter(([, v]) => v !== '')));
</script>
```

- [ ] **Step 2: Editor de campos e automacao no catalogo**

`Components/Organisms/Demandas/Catalogo/AssuntoCamposEditor.vue`:

```vue
<template>
  <div class="space-y-4">
    <div class="space-y-2">
      <div v-for="(campo, i) in form.campos_dinamicos" :key="i" class="flex flex-wrap items-center gap-2">
        <input v-model="campo.label" type="text" maxlength="100" placeholder="Ex.: Login AD"
          class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
        <select v-model="campo.tipo" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
          <option value="text">Texto</option>
          <option value="checkbox">Checkbox</option>
        </select>
        <Button type="button" variant="danger" size="sm" @click="form.campos_dinamicos.splice(i, 1)">Remover</Button>
      </div>
      <Button type="button" variant="secondary" size="sm" @click="form.campos_dinamicos.push({ label: '', tipo: 'text' })">Adicionar campo</Button>
    </div>

    <fieldset class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
      <legend class="px-1 text-sm font-semibold text-slate-700 dark:text-slate-200">Automação AD</legend>
      <div class="flex flex-wrap gap-2">
        <select v-model="acao" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
          <option value="">Sem automação</option>
          <option value="desbloquear">Desbloquear conta</option>
          <option value="ativar">Ativar conta</option>
          <option value="resetar">Resetar senha</option>
        </select>
        <select v-if="acao" v-model="campoLogin" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
          <option value="">Campo com o login</option>
          <option v-for="c in camposTexto" :key="c.label" :value="c.label">{{ c.label }}</option>
        </select>
      </div>
      <p v-if="form.errors['form_automacao.campo_login']" class="mt-1 text-xs text-red-500">{{ form.errors['form_automacao.campo_login'] }}</p>
    </fieldset>

    <Button type="button" variant="primary" size="sm" :disabled="form.processing" @click="salvar">Salvar assunto</Button>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({ assunto: { type: Object, required: true } });

const form = useForm({
  campos_dinamicos: (props.assunto.campos_dinamicos ?? []).map((c) => ({ ...c })),
  form_automacao: props.assunto.form_automacao ?? null,
});
const acao = ref(props.assunto.form_automacao?.acao ?? '');
const campoLogin = ref(props.assunto.form_automacao?.campo_login ?? '');
const camposTexto = computed(() => form.campos_dinamicos.filter((c) => c.tipo === 'text' && c.label.trim()));

function salvar() {
  form.form_automacao = acao.value ? { acao: acao.value, campo_login: campoLogin.value } : null;
  form.campos_dinamicos = form.campos_dinamicos.filter((c) => c.label.trim());
  form.put(route('admin.demandas.assuntos.update', props.assunto.id), { preserveScroll: true });
}
</script>
```

Em `Pages/Demandas/Catalogo.vue`: para cada assunto listado, acrescentar um `CollapsibleSection` (`Components/Molecules/CollapsibleSection.vue`) "Campos e automação" contendo `<AssuntoCamposEditor :assunto="assunto" />`. Manter o restante da pagina como esta.

- [ ] **Step 3: NovaDemandaModal**

```bash
grep -rn "NovaDemandaModal" resources/js --include=*.vue
```
Se nenhum arquivo alem do proprio o importar, `git rm resources/js/Components/Organisms/Demandas/Modals/NovaDemandaModal.vue`. Se houver chamador (ex.: widget do dashboard), trocar a acao do chamador por `router.visit(route('demandas.create'))` e remover o modal.

- [ ] **Step 4: Build**

Run: `cd SDC && npx vite build`
Expected: sem erros.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Templates/Demandas/DemandasFormTemplate.vue resources/js/Pages/Demandas/DemandasCreate.vue resources/js/Pages/Demandas/Catalogo.vue resources/js/Components/Organisms/Demandas/Catalogo resources/js/Components/Organisms/Demandas/Modals
git commit -m "🎨 style(demandas): abertura com campos do assunto e catalogo com automacao"
```

---

### Task 18: Frontend — dashboard e navegacao

**Files:**
- Create (ou substituir o placeholder da Task 10): `resources/js/Pages/Demandas/DemandasDashboard.vue`
- Create: `resources/js/Templates/Demandas/DemandasDashboardTemplate.vue`
- Modify: `resources/js/Components/Sidebar.vue`, `resources/js/Organisms/CommandPalette.vue` (caminho real: `Components/Organisms/CommandPalette.vue`), `resources/js/Components/Molecules/Navigation/BottomNavigation.vue`

**Interfaces:**
- Consumes: props da Task 10 (`estatisticas, serie, recentes, pode.exportar`), `DemandasStatisticsCards` (Task 15), `LazyChart` (`type, height, options, series`).

- [ ] **Step 1: Template e Page**

`Templates/Demandas/DemandasDashboardTemplate.vue`:

```vue
<template>
  <div class="space-y-6 pb-8">
    <PageHeader title="Painel de demandas" description="Volume e andamento dos chamados" :icon-image="moduleIcon('demandas')" variant="gradient" :espaco-inferior="false">
      <template #actions>
        <Button v-if="pode.exportar" variant="secondary" size="md" :icon="DownloadIcon" icon-position="left" @click="$emit('exportar')">Exportar quantitativo</Button>
      </template>
    </PageHeader>

    <DemandasStatisticsCards :estatisticas="estatisticas" @filtrar="(e) => $emit('filtrar-etapa', e)" />

    <ListContainer title="Últimos 10 meses" :icon="ChartIcon">
      <div class="h-72 min-w-0">
        <LazyChart type="bar" height="100%" :options="opcoesGrafico" :series="series" />
      </div>
    </ListContainer>

    <ListContainer title="Mais recentes" :icon="DocumentTextIcon" :count="recentes.length">
      <ul class="divide-y divide-slate-200 dark:divide-slate-700/50">
        <li v-for="d in recentes" :key="d.id" class="flex cursor-pointer flex-wrap items-center justify-between gap-2 py-3" @click="$emit('abrir', d.id)">
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ d.titulo }}</p>
            <p class="text-xs text-slate-500">{{ d.protocolo }} · {{ d.solicitante ?? '—' }} · {{ formatarDataHora(d.created_at) }}</p>
          </div>
          <div class="flex gap-2">
            <DemandaStatusBadge :etapa="d.etapa" :label="d.etapa_label" />
            <DemandaPrioridadeBadge :prioridade="d.prioridade_simples" :label="d.prioridade_label" />
          </div>
        </li>
        <li v-if="!recentes.length" class="py-6 text-center text-sm text-slate-500">Nenhuma demanda ainda.</li>
      </ul>
    </ListContainer>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ChartIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import DemandasStatisticsCards from '@/Components/Organisms/Demandas/Statistics/DemandasStatisticsCards.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { formatarDataHora } from '@/Support/demandasFormat';

const props = defineProps({
  estatisticas: { type: Object, required: true },
  serie: { type: Array, required: true },
  recentes: { type: Array, default: () => [] },
  pode: { type: Object, required: true },
});
defineEmits(['exportar', 'abrir', 'filtrar-etapa']);

const MES = new Intl.DateTimeFormat('pt-BR', { month: 'short', year: '2-digit' });
const series = computed(() => [
  { name: 'Abertas', data: props.serie.map((s) => s.abertas) },
  { name: 'Resolvidas', data: props.serie.map((s) => s.resolvidas) },
]);
const opcoesGrafico = computed(() => ({
  chart: { toolbar: { show: false }, background: 'transparent' },
  xaxis: { categories: props.serie.map((s) => MES.format(new Date(`${s.mes}-01T12:00:00`))) },
  colors: ['#f97316', '#10b981'],
  dataLabels: { enabled: false },
  legend: { position: 'top' },
  theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
}));
</script>
```
Se o projeto tiver um icone de grafico (`ls resources/js/Components/Icons | grep -i chart`), usa-lo no lugar do `ClipboardDocumentListIcon`.

`Pages/Demandas/DemandasDashboard.vue`:

```vue
<template>
  <DemandasDashboardTemplate v-bind="$props"
    @exportar="window.location.href = route('demandas.dashboard.export')"
    @abrir="(id) => router.visit(route('demandas.show', id))"
    @filtrar-etapa="(e) => router.visit(route('demandas.index', { etapa: e }))" />
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasDashboardTemplate from '@/Templates/Demandas/DemandasDashboardTemplate.vue';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  estatisticas: { type: Object, required: true },
  serie: { type: Array, required: true },
  recentes: { type: Array, default: () => [] },
  pode: { type: Object, required: true },
});

const window = globalThis.window;
useAtualizacaoAoVivo({ canal: 'listagem.demandas', evento: '.RecursoAtualizado', props: ['estatisticas', 'serie', 'recentes'] });
</script>
```

- [ ] **Step 2: Navegacao**

Em `Sidebar.vue`, substituir o `NavItem` unico de DEMANDAS (linhas ~127-134) por dois itens, mantendo o de catalogo:

```vue
        <NavItem
          v-if="hasPermission(['demandas.dashboard.view']) && route().has('demandas.dashboard')"
          :href="route('demandas.dashboard')"
          :active="isRouteActive('demandas.dashboard')"
          icon="checkbadge"
          :collapsed="isCollapsed"
        >
          Painel de demandas
        </NavItem>
        <NavItem
          v-if="canSeeDemandas && _routes.hasDemandas"
          :href="route('demandas.index')"
          :active="isRouteActive('demandas.index') || isRouteActive('demandas.show') || isRouteActive('demandas.create')"
          icon="checkbadge"
          :collapsed="isCollapsed"
        >
          DEMANDAS
        </NavItem>
```
Em `CommandPalette.vue` e `BottomNavigation.vue`: procurar a entrada `demandas.index` (`grep -n "demandas" ...`) e, onde houver lista de atalhos, acrescentar `demandas.dashboard` com o mesmo gate de permissao. Se nao houver entrada de Demandas nesses arquivos, nao acrescentar (fora do escopo).

- [ ] **Step 3: Build**

Run: `cd SDC && npx vite build`
Expected: sem erros.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Templates/Demandas/DemandasDashboardTemplate.vue resources/js/Pages/Demandas/DemandasDashboard.vue resources/js/Components/Sidebar.vue resources/js/Components/Organisms/CommandPalette.vue resources/js/Components/Molecules/Navigation/BottomNavigation.vue
git commit -m "✨ feat(demandas): painel de chamados e navegacao"
```

---

### Task 19: Verificacao final — suite, build, telas e permissoes

Sem codigo novo, salvo correcoes encontradas (cada correcao vira commit `🐛 fix(demandas): ...`).

- [ ] **Step 1: Suite completa do modulo e regressao do Ranking**

```bash
bash /c/tmp/teste-demandas.sh php vendor/bin/phpunit tests/Feature/Demandas tests/Unit/Demandas tests/Unit/Ranking tests/Feature/RankingContinuationTest.php
```
Expected: tudo PASS (SKIPPED aceitavel apenas no teste de SLA sem definicao).

- [ ] **Step 2: Auditoria de permissoes e rotas**

```bash
bash /c/tmp/teste-demandas.sh php artisan route:list --path=demandas
bash /c/tmp/teste-demandas.sh php artisan permissions:audit
```
Expected: rotas `demandas.dashboard`, `demandas.resolver`, `demandas.reabrir`, `demandas.automacao` listadas com middleware `can:`; nenhuma divergencia de slug de Demandas no audit (usar o nome real do comando de `AuditPermissionsCommand`).

- [ ] **Step 3: Subir a app da worktree e medir overflow**

```bash
WT=/c/Users/x24679188/Documents/Github/NewSDC/.worktrees/demandas-paridade-chamados/SDC
MSYS_NO_PATHCONV=1 docker run -d --name demandas-preview --network newsdc-dev_default -p 8095:8000 -v "$WT:/app" -w /app \
  -e APP_KEY="$(grep '^APP_KEY=' $WT/.env | cut -d= -f2-)" -e DB_CONNECTION=pgsql -e DB_HOST=db -e DB_PORT=5432 \
  -e DB_DATABASE=sdc_test -e DB_USERNAME=sdc -e DB_PASSWORD=secret -e APP_URL=http://localhost:8095 \
  newsdc-swoole-dev:latest php artisan serve --host=0.0.0.0 --port=8000
```
Com um usuario gestor de `sdc_test`, abrir `/demandas/dashboard`, `/demandas`, `/demandas/nova` e `/demandas/{id}` em 375 px e 840 px (Playwright MCP ou DevTools) e rodar no console:

```js
document.documentElement.scrollWidth <= window.innerWidth
```
Expected: `true` nas 8 combinacoes; modo escuro (`html.dark`) legivel. Conferir contra as capturas do legado: colunas da listagem, cards do detalhe (Descricao, Assunto, Comentarios/Historico, Envolvidos, Informacoes, Anexos, Resolver).

Ao terminar: `docker rm -f demandas-preview`.

- [ ] **Step 4: Fluxo manual ponta a ponta**

Na app de preview: abrir demanda com assunto de campos dinamicos -> comentar como outro usuario (vira Em andamento e atribui) -> anexar arquivo -> transferir -> resolver com data de abertura ajustada -> reabrir -> resolver. Conferir a aba Historico com uma linha por acao, nos rotulos do legado.

- [ ] **Step 5: Limpeza**

```bash
git status --short
grep -rn "dd(\|dump(\|console.log(\|Log::debug" app/Modules/Demandas resources/js/Pages/Demandas resources/js/Components/Organisms/Demandas resources/js/Templates/Demandas
```
Expected: nenhum log de depuracao; nenhum arquivo em `tests/` staged; nada fora do escopo modificado.

- [ ] **Step 6: Relatorio para o usuario**

Resumo com: commits da branch (`git log --oneline dev..HEAD`), resultado da suite, resultado do dry-run da importacao (contagens e rejeitados por motivo), pendencias abertas (contrato real do AD; homologacao da carga em replica; ensaio do corte com `--desde`).

---

## Self-review

- Cobertura da spec: D1–D7 -> Tasks 1–13; secao 3.1 -> Task 2; 3.2 R1 -> Task 6, R2/R3 -> Task 5, R4 -> Task 6, R5/R6 -> Task 8, R7 -> Task 4; 3.3 -> Task 1; 3.4 -> Task 3; 3.5 -> Tasks 1, 8, 12, 14; 4.1 -> Tasks 4–11; 4.2 -> Tasks 9–11; 4.3 -> Task 8; 4.4 -> Tasks 9, 12; 5 -> Tasks 14–18; 6 -> Task 13; 7 -> Task 11; 8 -> Task 12; 9 -> Tasks 5, 7, 9, 11, 13; 10 -> todas; 11 (fora do escopo) respeitado.
- Review Focus: item 1 -> Task 5 (`test_resolver_demanda_aberta_sem_responsavel...`) e Task 3 (`test_falha_no_meio...`); item 2 -> Task 6; item 3 -> Task 4; item 4 -> Task 13; item 5 -> Task 8/9.
- Nomes cruzados conferidos: `conduzirAte`, `assumirSeSemResponsavel`, `HistoricoDemanda::registrar`, `escopoVisivel`, `PrioridadeSimples::dePrioridade`, `ContextoImportacao::durante`, rotas `admin.demandas.update|assign|change-status|export`, `demandas.comments.store`, `demandas.attachments.store|download`.
