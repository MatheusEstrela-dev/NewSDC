# Treinamento EAD, Fase 1 (Base) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Curso EAD com pastas e itens (PDF e projeto), progresso retomável por aluno, sala de aula nos dois escopos (servidor no SDC e cidadão no portal), área do professor e público/aprovação configuráveis por treinamento.

**Architecture:** Tudo no módulo `App\Modules\Treinamento`, sobre o banco dedicado `sdc_treinamento` (schema `treinamento`). Regras (trilha, progresso, projeto, conteúdo, professor) ficam em services únicos; cada escopo (aluno servidor, aluno cidadão, professor) tem controllers e rotas próprios e finos que só resolvem "quem é o aluno" e delegam. No frontend, um template de sala de aula compartilhado, com uma página Inertia fina por escopo.

**Tech Stack:** Laravel 12 + Octane/Swoole, PostgreSQL (conexão `treinamento_connection` -> `pgsql_treinamento`), Spatie Permission, Inertia + Vue 3, ziggy-js, vuedraggable (já instalado), pdfjs-dist (novo).

**Spec:** `docs/superpowers/specs/2026-11-09-treinamento-ead-design.md` (Fase 1 = seção 10, item 1). Provas, conclusão, certificado verificável = Fase 2 (outro plano). Vídeo = Fase 3.

## Global Constraints

- Base: branch `feat/treinamento-ead` criada a partir de `dev` (o checkout principal do NewSDC está em outra branch com mudanças locais: trabalhar em worktree de `dev`).
- Tabelas do Treinamento vivem em `sdc_treinamento`, schema `treinamento`, nomes sempre qualificados (`treinamento.x`). Models usam o trait `App\Support\Database\UsaConexaoDeTreinamento`; transações via `App\Modules\Treinamento\Support\ConexaoTreinamento::transacao()`.
- Sem FK para `users` (outro banco): colunas de usuário são `bigint` indexado (`*_fkidx`), nome buscado em segunda consulta.
- Regra de ouro de migrations: toda DDL desta fase entra **consolidada** na migration principal `SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php`, em SQL **idempotente** (`CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS`, `DROP CONSTRAINT IF EXISTS` + `ADD CONSTRAINT`). Nenhuma migration nova.
- Arquivos: disco novo `treinamentos` via `$azureOrLocal` em `config/filesystems.php`; servidos por stream do Laravel (`$disk->response()` / `$disk->download()`), só por endpoint autorizado. Limite de upload 50 MB (o Swoole aceita 64 MB).
- Rotas `/treinamentos/aluno/...` e `/treinamentos/professor/...` declaradas **antes** de `/{id}` em `routes/modules/treinamento.php` (o wildcard casaria primeiro).
- Sem emojis no código. Logs de depuração removidos após o teste. DRY/SOLID.
- Testes em `SDC/tests/Feature/Treinamento/Ead/` rodam localmente e **não entram em commit** (regra de ouro 10). Commits atômicos por task, gitmoji, pt-BR, **sem** trailer `Co-Authored-By`.
- Comando de teste (host, a partir de `SDC/`; em worktree, as mesmas variáveis no container descartável `newsdc-swoole-dev:latest`):

```bash
export PATH="/c/laragon/bin/php/php-8.3.16-Win32-vs16-x64:$PATH"
APP_CONFIG_CACHE=/c/tmp/nao-existe-config.php \
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5434 DB_DATABASE=sdc DB_USERNAME=sdc DB_PASSWORD=secret \
DB_TREINAMENTO_CONNECTION=pgsql_treinamento TREINAMENTO_DB_HOST=127.0.0.1 TREINAMENTO_DB_PORT=5434 TREINAMENTO_DB_DATABASE=sdc_treinamento \
  php -d extension=pdo_pgsql -d extension=pgsql vendor/bin/phpunit <caminho-ou-filtro>
```
  Abaixo, `TEST <x>` = esse comando com `<x>`.
- Fixtures de teste: nada com data de hoje ou valor único plausível (o banco de dev é compartilhado). Títulos com prefixo `TST-EAD-`, CPF de cidadão de teste começando com `999`.

## Review Focus

1. Aluno chamando direto o endpoint de item (arquivo, progresso, entrega) de pasta ainda travada, de outro curso, ou com inscrição `PENDENTE`: esperado 403, sem gravar nada. (Task 6)
2. Cidadão acessando pelo portal um curso `SERVIDORES` (por slug) ou servidor abrindo pelo SDC um curso `CIDADAOS`: esperado 404. (Task 6)
3. Professor não designado abrindo conteúdo, alunos ou correções de outro curso, ou passando `modulo`/`item` de outro curso na URL do próprio curso: esperado 403/404. (Tasks 4 e 5)
4. Item excluído (soft) depois de haver progresso, pasta vazia, curso sem itens: a trilha não quebra e o percentual não divide por zero (0 itens = 0%). (Task 6)
5. Posição de progresso negativa, maior que o total ou menor que a já gravada; dois POSTs simultâneos: a posição nunca regride, nunca passa do total e não duplica linha. (Task 6)

---

## File Structure

**Backend (`SDC/app/Modules/Treinamento/`)**
- `Enums/PublicoAlvo.php`, `Enums/TipoItem.php`, `Enums/StatusProgresso.php`, `Enums/StatusEntrega.php` (novos); `Enums/TipoTreinamento.php` (+ `EAD`).
- `Models/ModuloItem.php`, `Models/TreinamentoProfessor.php`, `Models/ProgressoItem.php`, `Models/ProgressoPasta.php`, `Models/ProjetoEntrega.php` (novos); `Models/Treinamento.php`, `Models/Modulo.php` (alterados).
- `Services/ProfessorService.php`, `Services/ConteudoCursoService.php`, `Services/ArquivoCursoService.php`, `Services/TrilhaService.php`, `Services/ProgressoService.php`, `Services/ProjetoService.php`, `Services/AcompanhamentoService.php` (novos); `Services/InscricaoService.php` (alterado).
- `DTOs/EstadoTrilha.php`, `DTOs/EstadoPasta.php`, `DTOs/EstadoItem.php` (novos).
- `Support/InscricaoDoAluno.php` (novo: resolve a inscrição aprovada do aluno atual).
- `Http/Middleware/LecionaCurso.php` (novo).
- `Controllers/Aluno/*`, `Controllers/Professor/*`, `Controllers/Admin/Professor*`, `Controllers/Portal/Sala*` (novos, finos).
- `Requests/*` (novos por endpoint de escrita).

**Outros backend:** `SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php`, `SDC/config/filesystems.php`, `SDC/config/permissions.php`, `SDC/database/seeders/TreinamentoPermissionsSeeder.php` (novo), `SDC/routes/modules/treinamento.php`, `SDC/routes/modules/treinamento-portal.php`, `SDC/app/Modules/Treinamento/Controllers/Portal/CatalogoController.php`.

**Frontend (`SDC/resources/js/`)**
- `Composables/treinamento/useRotasSala.js`, `Composables/treinamento/usePdf.js`.
- `Components/Organisms/Treinamento/Sala/TrilhaLateral.vue`, `.../Sala/VisualizadorPdf.vue`, `.../Sala/ItemProjeto.vue`.
- `Templates/Treinamento/SalaDeAulaTemplate.vue`.
- `Pages/Treinamento/Aluno/{MeusCursos,SalaDeAula}.vue`, `Pages/Treinamento/Portal/SalaDeAula.vue`, `Pages/Treinamento/Professor/{MeusCursos,Conteudo,Alunos,Correcoes,Previa}.vue`.
- Alterados: `Templates/Treinamento/TreinamentoCreateTemplate.vue`, `TreinamentoEditTemplate.vue`, `Pages/Treinamento/TreinamentoShow.vue`, `Pages/Treinamento/Portal/MinhasInscricoes.vue`.

**Testes (locais, fora do commit):** `SDC/tests/Feature/Treinamento/Ead/EadTestCase.php` + um arquivo por task.

---

### Task 1: Schema da Fase 1 + base de testes

**Files:**
- Modify: `SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php`
- Create (local): `SDC/tests/Feature/Treinamento/Ead/EadTestCase.php`, `SDC/tests/Feature/Treinamento/Ead/SchemaEadTest.php`

**Interfaces:**
- Produces (DDL, schema `treinamento`):
  - `treinamentos` + `publico_alvo varchar(20) NOT NULL DEFAULT 'CIDADAOS'` com `CHECK (publico_alvo IN ('SERVIDORES','CIDADAOS'))`; + `inscricao_aprovacao_automatica boolean NOT NULL DEFAULT false`; `treinamentos_tipo_check` passa a `('PRESENCIAL','ONLINE','EAD')`.
  - `modulos` + `deleted_at timestamp(0)`.
  - `modulo_itens` (`id`, `modulo_id` FK -> modulos ON DELETE CASCADE, `tipo varchar(20)` CHECK `('PDF','PROJETO','PROVA','VIDEO')`, `titulo varchar(255) NOT NULL`, `descricao text`, `ordem integer NOT NULL DEFAULT 0`, `obrigatorio boolean NOT NULL DEFAULT true`, `arquivo_path varchar(500)`, `arquivo_nome varchar(255)`, `arquivo_mime varchar(100)`, `arquivo_tamanho bigint`, `total_paginas integer`, timestamps, `deleted_at`); índice `(modulo_id, ordem)`.
  - `treinamento_professores` (`id`, `treinamento_id` FK CASCADE, `user_id bigint NOT NULL`, `created_at`, `UNIQUE (treinamento_id, user_id)`); índice `user_id` (`treinamento_professores_user_id_fkidx`).
  - `progresso_itens` (`id`, `inscricao_id` FK CASCADE, `modulo_item_id` FK CASCADE, `status varchar(20) NOT NULL` CHECK `('EM_ANDAMENTO','CONCLUIDO')`, `posicao integer NOT NULL DEFAULT 0`, `concluido_em timestamp(0)`, timestamps, `UNIQUE (inscricao_id, modulo_item_id)`).
  - `progresso_pastas` (`id`, `inscricao_id` FK CASCADE, `modulo_id` FK CASCADE, `concluida_em timestamp(0) NOT NULL`, `UNIQUE (inscricao_id, modulo_id)`).
  - `projeto_entregas` (`id`, `inscricao_id` FK CASCADE, `modulo_item_id` FK CASCADE, `arquivo_path`, `arquivo_nome`, `arquivo_mime`, `arquivo_tamanho`, `comentario text`, `status varchar(20) NOT NULL DEFAULT 'ENVIADA'` CHECK `('ENVIADA','APROVADA','DEVOLVIDA')`, `parecer text`, `corrigido_por bigint`, `corrigido_em timestamp(0)`, timestamps); índices `(modulo_item_id, status)`, `(inscricao_id, modulo_item_id)`, `corrigido_por` (`_fkidx`).
- Produces (testes): `abstract class EadTestCase extends Tests\TestCase` com `DatabaseTransactions`, que acrescenta `ConexaoTreinamento::nome()` em `$connectionsToTransact` no `setUpTraits()`, e helpers `criarCurso(array $attrs = []): Treinamento` (EAD, `TST-EAD-` no título, `EM_ANDAMENTO`, publicado), `criarServidor(array $permissoes = []): User`, `criarCidadao(): Cidadao` (CPF `999` + 8 dígitos aleatórios), `inscrever(Treinamento, Model $inscrito, StatusInscricao $status = APROVADA): Inscricao`, `criarPasta(Treinamento, int $ordem): Modulo`, `criarItem(Modulo, TipoItem, int $ordem, array $attrs = []): ModuloItem`.

- [ ] **Step 1: Escrever `SchemaEadTest`**: `test_tabelas_da_fase_1_existem` (para cada uma das 5 tabelas novas, `Schema::connection(ConexaoTreinamento::nome())->hasTable("treinamento.$t")` é true), `test_colunas_novas_de_treinamentos` (`hasColumns('treinamento.treinamentos', ['publico_alvo','inscricao_aprovacao_automatica'])`), `test_tipo_ead_aceito` (insert com `tipo='EAD'` não lança), `test_publico_invalido_rejeitado` (insert com `publico_alvo='AMBOS'` lança `QueryException`).
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/SchemaEadTest.php`; esperado FAIL (tabela não existe).
- [ ] **Step 3: Acrescentar a DDL** à migration principal: novos métodos privados `criarEad(): void` (tabelas novas) e `ajustarTabelasExistentes(): void` (ALTERs e troca do CHECK), chamados no fim de `up()` depois de `criarTreinamento()`. `down()` não muda (o `DROP SCHEMA ... CASCADE` já leva tudo).
- [ ] **Step 4: Aplicar no banco de dev** (a migration já está registrada como rodada; ela é idempotente):

```bash
docker exec newsdc_dev_db psql -U sdc -d sdc_treinamento -c "DELETE FROM migrations WHERE migration = '2026_11_05_210000_create_treinamento_schemas_tables';"
docker exec newsdc_dev_app php artisan migrate --database=pgsql_treinamento --path=database/migrations/treinamento --force
```
  Esperado: `2026_11_05_210000_create_treinamento_schemas_tables ... DONE`. Rodar a segunda linha de novo depois de apagar o registro outra vez também deve passar (prova da idempotência).
- [ ] **Step 5: Rodar o teste**: `TEST tests/Feature/Treinamento/Ead/SchemaEadTest.php`; esperado PASS (4 testes).
- [ ] **Step 6: Commit**

```bash
git add SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php
git commit -m "🗃️ db(treinamento): schema do EAD (itens, progresso, entregas, professores, publico)"
```

---

### Task 2: Enums, models e campos do treinamento (público, aprovação automática, tipo EAD)

**Files:**
- Create: `Enums/PublicoAlvo.php`, `Enums/TipoItem.php`, `Enums/StatusProgresso.php`, `Enums/StatusEntrega.php`, `Models/ModuloItem.php`, `Models/TreinamentoProfessor.php`, `Models/ProgressoItem.php`, `Models/ProgressoPasta.php`, `Models/ProjetoEntrega.php`
- Modify: `Enums/TipoTreinamento.php`, `Models/Treinamento.php`, `Models/Modulo.php`, `Requests/StoreTreinamentoRequest.php`, `Requests/UpdateTreinamentoRequest.php`, `Resources/TreinamentoResource.php`, `Resources/TreinamentoListResource.php`, `Controllers/TreinamentoCreateController.php`, `Controllers/TreinamentoEditController.php`, `SDC/resources/js/Templates/Treinamento/TreinamentoCreateTemplate.vue`, `TreinamentoEditTemplate.vue`
- Test (local): `tests/Feature/Treinamento/Ead/TreinamentoCamposEadTest.php`

**Interfaces:**
- Produces:
  - `enum PublicoAlvo: string { SERVIDORES; CIDADAOS; getLabel(): string; aceita(Model $inscrito): bool; static toSelectArray(): array }`. `aceita`: `SERVIDORES` só `App\Models\User`, `CIDADAOS` só `Cidadao`. Labels: "Só servidores", "Só cidadãos".
  - `TipoTreinamento::EAD = 'EAD'`, label "EAD (curso com trilha)".
  - `enum TipoItem: string { PDF; PROJETO; getLabel() }` (PROVA e VIDEO entram nas Fases 2 e 3).
  - `enum StatusProgresso: string { EM_ANDAMENTO; CONCLUIDO }`, `enum StatusEntrega: string { ENVIADA; APROVADA; DEVOLVIDA; getLabel() }`.
  - `Treinamento`: fillable/casts `publico_alvo` => `PublicoAlvo`, `inscricao_aprovacao_automatica` => bool; `isEad(): bool`; `scopeParaPublico($q, PublicoAlvo $p)`; `professores(): HasMany` (TreinamentoProfessor).
  - `Modulo`: `SoftDeletes`; `itens(): HasMany` (ModuloItem, `orderBy('ordem')`).
  - `ModuloItem` (`treinamento.modulo_itens`, SoftDeletes, cast `tipo` => TipoItem): `modulo(): BelongsTo`, `temArquivo(): bool`.
  - `TreinamentoProfessor` (`treinamento.treinamento_professores`, `UPDATED_AT = null`), `ProgressoItem`, `ProgressoPasta` (sem timestamps automáticos), `ProjetoEntrega` (cast `status` => StatusEntrega): todos com `UsaConexaoDeTreinamento`.
- Validação: `publico_alvo` => `required|in:SERVIDORES,CIDADAOS`; `inscricao_aprovacao_automatica` => `boolean`; `tipo` aceita `EAD`.

- [ ] **Step 1: Escrever `TreinamentoCamposEadTest`**: `test_store_exige_publico_alvo` (POST `treinamentos.admin.store` sem `publico_alvo` -> erro de sessão em `publico_alvo`); `test_store_grava_ead_publico_e_aprovacao` (POST com `tipo=EAD`, `publico_alvo=SERVIDORES`, `inscricao_aprovacao_automatica=1` -> registro com esses valores e `isEad()` true); `test_publico_aceita_inscrito_certo` (`PublicoAlvo::SERVIDORES->aceita(new User)` true, `->aceita(new Cidadao)` false; o inverso para `CIDADAOS`); `test_scope_para_publico` (curso SERVIDORES não volta em `Treinamento::paraPublico(PublicoAlvo::CIDADAOS)`).
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/TreinamentoCamposEadTest.php`; FAIL (classe `PublicoAlvo` não existe).
- [ ] **Step 3: Implementar** enums, models e relações acima; requests; resources passam a expor `publico_alvo`, `publico_alvo_label`, `inscricao_aprovacao_automatica`; Create/Edit controllers passam `publicos => PublicoAlvo::toSelectArray()`; templates ganham o select "Público" (obrigatório) e o toggle "Aprovar inscrições automaticamente".
- [ ] **Step 4: Rodar**: mesmo comando; PASS (4).
- [ ] **Step 5: Build do front**: `cd SDC && npx vite build`; esperado sem erro.
- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/Treinamento/Enums SDC/app/Modules/Treinamento/Models SDC/app/Modules/Treinamento/Requests/StoreTreinamentoRequest.php SDC/app/Modules/Treinamento/Requests/UpdateTreinamentoRequest.php SDC/app/Modules/Treinamento/Resources SDC/app/Modules/Treinamento/Controllers/TreinamentoCreateController.php SDC/app/Modules/Treinamento/Controllers/TreinamentoEditController.php SDC/resources/js/Templates/Treinamento
git commit -m "✨ feat(treinamento): publico, aprovacao automatica e tipo EAD no treinamento"
```

---

### Task 3: Público e aprovação automática no catálogo e na inscrição

**Files:**
- Modify: `Services/InscricaoService.php`, `Controllers/Portal/CatalogoController.php`
- Test (local): `tests/Feature/Treinamento/Ead/InscricaoPublicoTest.php`

**Interfaces:**
- Consumes: `PublicoAlvo::aceita()`, `Treinamento::paraPublico()` (Task 2).
- Produces: `InscricaoService::inscrever(Treinamento, Model $inscrito): Inscricao` passa a (1) lançar `\DomainException('Este treinamento nao e destinado ao seu perfil.')` se `!$treinamento->publico_alvo->aceita($inscrito)`; (2) criar já `APROVADA` (`data_aprovacao = now()`, `aprovado_por_id = null`) quando `inscricao_aprovacao_automatica`. O envio do e-mail de confirmação (hoje dentro de `aprovar()`) é extraído para `private function notificarAprovacao(Inscricao $i): void` e chamado nos dois caminhos.
- Produces: `CatalogoController::index/show` filtram `paraPublico(PublicoAlvo::CIDADAOS)`; `show` de curso SERVIDORES responde 404.

- [ ] **Step 1: Escrever `InscricaoPublicoTest`**: `test_cidadao_nao_se_inscreve_em_curso_de_servidores` (expectException DomainException); `test_servidor_nao_se_inscreve_em_curso_de_cidadaos`; `test_aprovacao_automatica_cria_aprovada` (status APROVADA, `data_aprovacao` não nula); `test_aprovacao_manual_cria_pendente`; `test_portal_nao_lista_curso_de_servidores` (cidadão logado no guard `cidadao`, GET `portal.treinamento.catalogo`, prop `treinamentos.data` sem o id do curso SERVIDORES); `test_portal_detalhe_de_curso_de_servidores_da_404`.
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/InscricaoPublicoTest.php`.
- [ ] **Step 3: Implementar** conforme Interfaces.
- [ ] **Step 4: Rodar**: PASS (6). Rodar também `TEST tests/Feature/Treinamento` inteiro para garantir que nada antigo quebrou.
- [ ] **Step 5: Commit**

```bash
git add SDC/app/Modules/Treinamento/Services/InscricaoService.php SDC/app/Modules/Treinamento/Controllers/Portal/CatalogoController.php
git commit -m "✨ feat(treinamento): catalogo e inscricao respeitam publico e aprovacao automatica"
```

---

### Task 4: Professor: permissão, vínculo ao curso e acesso restrito

**Files:**
- Create: `Services/ProfessorService.php`, `Http/Middleware/LecionaCurso.php`, `Controllers/Admin/ProfessorStoreController.php`, `Controllers/Admin/ProfessorDestroyController.php`, `Controllers/Admin/ProfessorCandidatosController.php`, `Requests/DesignarProfessorRequest.php`, `SDC/database/seeders/TreinamentoPermissionsSeeder.php`
- Modify: `SDC/config/permissions.php`, `Models/Treinamento.php` (`donosNotificacao`), `Services/InscricaoService.php`, `SDC/routes/modules/treinamento.php`, `SDC/resources/js/Pages/Treinamento/TreinamentoShow.vue`, `Controllers/TreinamentoShowController.php`
- Test (local): `tests/Feature/Treinamento/Ead/ProfessorVinculoTest.php`

**Interfaces:**
- Produces:
  - Permissão `treinamento.professor.acessar` em `config/permissions.php`, grupo `'Professor' => ['acessar' => 'treinamento.professor.acessar']` dentro de `'TREINAMENTO'`. Seeder no padrão de `DemandasPermissionsSeeder`, lendo `config('permissions.modules.TREINAMENTO')`.
  - `ProfessorService`: `designar(Treinamento $t, int $userId): TreinamentoProfessor` (idempotente; `DomainException` se o usuário não tiver a permissão ou estiver inscrito como aluno no curso); `remover(Treinamento $t, int $userId): void`; `leciona(int $userId, Treinamento $t): bool`; `cursosDe(int $userId): Collection` (Treinamentos EAD, mais recentes primeiro); `candidatos(?string $busca): Collection` (Users com a permissão; `id`, `name`, `email`; até 20).
  - `LecionaCurso` (alias `leciona.curso`): lê o parâmetro de rota `treinamento` (model) e aborta 403 se `!leciona(auth()->id(), $treinamento)`.
  - `Treinamento::donosNotificacao()` passa a retornar `created_by` + ids dos professores (sem repetir).
  - `InscricaoService::inscrever` lança `DomainException('Professor do curso nao pode se inscrever como aluno.')` quando o inscrito é User que leciona o curso.
  - Rotas (permissão `treinamento.cursos.edit`): `POST /treinamentos/{treinamento}/professores` -> `treinamentos.professores.store` (body `user_id`); `DELETE /treinamentos/{treinamento}/professores/{userId}` -> `treinamentos.professores.destroy`; `GET /treinamentos/professores/candidatos?busca=` -> `treinamentos.professores.candidatos` (JSON). O de candidatos fica antes de `/{id}`.
  - `TreinamentoShowController` passa `professores` (`[{id, name}]`) quando o curso é EAD; `TreinamentoShow.vue` mostra a seção "Professores" com busca + adicionar/remover (só com `treinamento.cursos.edit`).

- [ ] **Step 1: Escrever `ProfessorVinculoTest`**: `test_admin_designa_professor` (POST como admin com `cursos.edit` -> linha em `treinamento_professores`); `test_nao_designa_usuario_sem_permissao_de_professor` (422/erro de sessão); `test_designar_duas_vezes_nao_duplica`; `test_professor_inscrito_como_aluno_nao_pode_ser_designado`; `test_professor_nao_pode_se_inscrever_no_proprio_curso`; `test_middleware_bloqueia_professor_de_outro_curso` (no próprio teste, registrar `Route::middleware(['web', 'auth', 'leciona.curso'])->get('/_teste-leciona/{treinamento}', fn () => 'ok')`; professor designado em outro curso -> 403, designado no curso -> 200); `test_donos_notificacao_inclui_professores`.
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/ProfessorVinculoTest.php`.
- [ ] **Step 3: Implementar**; registrar o middleware no `TreinamentoServiceProvider` (`$router->aliasMiddleware('leciona.curso', LecionaCurso::class)`).
- [ ] **Step 4: Rodar o seeder em dev**: `docker exec newsdc_dev_app php artisan db:seed --class=TreinamentoPermissionsSeeder --force`; esperado sem erro e a permissão existindo (`php artisan user:permissions <email>` após atribuir).
- [ ] **Step 5: Rodar o teste**: PASS (7). `npx vite build` sem erro.
- [ ] **Step 6: Commit**

```bash
git add SDC/config/permissions.php SDC/database/seeders/TreinamentoPermissionsSeeder.php SDC/app/Modules/Treinamento/Services/ProfessorService.php SDC/app/Modules/Treinamento/Http/Middleware/LecionaCurso.php SDC/app/Modules/Treinamento/Controllers/Admin SDC/app/Modules/Treinamento/Requests/DesignarProfessorRequest.php SDC/app/Modules/Treinamento/Models/Treinamento.php SDC/app/Modules/Treinamento/Services/InscricaoService.php SDC/app/Modules/Treinamento/TreinamentoServiceProvider.php SDC/app/Modules/Treinamento/Controllers/TreinamentoShowController.php SDC/routes/modules/treinamento.php SDC/resources/js/Pages/Treinamento/TreinamentoShow.vue
git commit -m "✨ feat(treinamento): professor designado por curso com acesso restrito"
```

---

### Task 5: Conteúdo do curso (pastas e itens) e área do professor

**Files:**
- Create: `Services/ConteudoCursoService.php`, `Services/ArquivoCursoService.php`, `Controllers/Professor/{MeusCursosController,ConteudoController,PastaStoreController,PastaUpdateController,PastaDestroyController,PastaReordenarController,ItemStoreController,ItemUpdateController,ItemDestroyController,ItemReordenarController,ItemArquivoController,PublicarController}.php`, `Requests/Professor/{SalvarPastaRequest,SalvarItemRequest,ReordenarRequest}.php`, `SDC/resources/js/Pages/Treinamento/Professor/{MeusCursos,Conteudo}.vue`, `SDC/resources/js/Composables/treinamento/usePdf.js`
- Modify: `SDC/config/filesystems.php`, `SDC/routes/modules/treinamento.php`, `SDC/package.json` (pdfjs-dist)
- Test (local): `tests/Feature/Treinamento/Ead/ConteudoCursoTest.php`

**Interfaces:**
- Consumes: `LecionaCurso`, `ProfessorService::cursosDe()` (Task 4); models da Task 2.
- Produces:
  - Disco `'treinamentos' => $azureOrLocal(env('AZURE_STORAGE_CONTAINER_TREINAMENTOS', 'sdc-treinamentos'), 'TREINAMENTO', 'app/treinamentos')`.
  - `ArquivoCursoService`: `guardar(UploadedFile $arquivo, string $diretorio): array` (retorna `['arquivo_path','arquivo_nome','arquivo_mime','arquivo_tamanho']`, nome físico `Str::uuid().'.'.ext`); `apagar(?string $path): void`; `responder(string $path, string $nome, bool $download): StreamedResponse` (inline para PDF, attachment com `$download`). Diretórios: `cursos/{treinamento_id}/itens/{item_id}` e `cursos/{treinamento_id}/entregas/{inscricao_id}`.
  - `ConteudoCursoService`: `criarPasta(Treinamento $t, array $dados): Modulo` (`titulo`, `descricao?`, `carga_horaria`; `ordem` = última + 1); `atualizarPasta(Modulo $m, array $dados): Modulo`; `excluirPasta(Modulo $m): void` (soft delete da pasta e dos itens); `reordenarPastas(Treinamento $t, array $ids): void`; `criarItem(Modulo $m, array $dados, ?UploadedFile $arquivo): ModuloItem` (`tipo`, `titulo`, `descricao?`, `obrigatorio`, `total_paginas?`; PDF exige arquivo e `total_paginas >= 1`; PROJETO aceita arquivo de apoio opcional); `atualizarItem(ModuloItem $i, array $dados, ?UploadedFile $arquivo): ModuloItem` (troca de arquivo apaga o anterior); `excluirItem(ModuloItem $i): void` (soft); `reordenarItens(Modulo $m, array $ids): void`; `garantirDoCurso(Treinamento $t, Modulo|ModuloItem $alvo): void` (404 se não pertence). `reordenar*` lança `DomainException` se a lista não for exatamente o conjunto de ids ativos.
  - Rotas (prefixo `/treinamentos/professor`, nome `treinamentos.professor.`, `can:treinamento.professor.acessar`; as com `{treinamento}` também `leciona.curso`): `GET /` -> `index`; `GET /{treinamento}/conteudo` -> `conteudo`; `POST /{treinamento}/pastas` -> `pastas.store`; `PUT /{treinamento}/pastas/{modulo}` -> `pastas.update`; `DELETE /{treinamento}/pastas/{modulo}` -> `pastas.destroy`; `POST /{treinamento}/pastas-ordem` -> `pastas.ordem`; `POST /{treinamento}/pastas/{modulo}/itens` -> `itens.store`; `POST /{treinamento}/itens/{item}` -> `itens.update` (multipart, por isso POST); `DELETE /{treinamento}/itens/{item}` -> `itens.destroy`; `POST /{treinamento}/pastas/{modulo}/itens-ordem` -> `itens.ordem`; `GET /{treinamento}/itens/{item}/arquivo` -> `itens.arquivo`; `POST /{treinamento}/publicar` -> `publicar` (reusa `TreinamentoService::publicar`; `DomainException` se o curso não tiver nenhum item ativo).
  - Página `Professor/MeusCursos.vue` (cards: título, público, status, nº de alunos) e `Professor/Conteudo.vue` (layout do rascunho 3, P1: menu do curso, pastas com `vuedraggable`, "+ Nova pasta", "+ Adicionar item", modal de item; no upload de PDF o `usePdf().contarPaginas(file): Promise<number>` preenche `total_paginas`).
  - `pdfjs-dist@4.10.38`; worker configurado em `usePdf.js` com `GlobalWorkerOptions.workerSrc = new URL('pdfjs-dist/build/pdf.worker.min.mjs', import.meta.url).toString()`.

- [ ] **Step 1: Escrever `ConteudoCursoTest`** (com `Storage::fake('treinamentos')`): `test_professor_cria_pasta_na_ordem_seguinte`; `test_cria_item_pdf_com_arquivo_e_paginas` (arquivo existe no disco fake; `total_paginas` = 12); `test_pdf_sem_total_paginas_e_rejeitado` (erro de sessão `total_paginas`); `test_arquivo_nao_pdf_e_rejeitado` (upload `.exe` -> erro `arquivo`); `test_reordenar_itens_com_id_de_outra_pasta_falha`; `test_item_de_outro_curso_na_url_da_404` (Review Focus 3); `test_excluir_item_e_soft_delete` (`withTrashed()` ainda acha); `test_professor_nao_designado_recebe_403_no_conteudo` (Review Focus 3); `test_publicar_curso_sem_itens_falha`.
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/ConteudoCursoTest.php`.
- [ ] **Step 3: Implementar backend** conforme Interfaces (requests: PDF `mimes:pdf|max:51200`; projeto `file|max:51200` opcional; `tipo` `in:PDF,PROJETO`).
- [ ] **Step 4: Rodar**: PASS (9).
- [ ] **Step 5: Instalar pdfjs e implementar as páginas**: `cd SDC && npm install pdfjs-dist@4.10.38`; páginas e composable conforme Interfaces; `npx vite build` sem erro.
- [ ] **Step 6: Smoke manual**: como professor designado em dev, criar 2 pastas, subir um PDF real (conferir que o nº de páginas veio certo), arrastar para reordenar, recarregar e ver a ordem mantida.
- [ ] **Step 7: Commit**

```bash
git add SDC/config/filesystems.php SDC/app/Modules/Treinamento/Services/ConteudoCursoService.php SDC/app/Modules/Treinamento/Services/ArquivoCursoService.php SDC/app/Modules/Treinamento/Controllers/Professor SDC/app/Modules/Treinamento/Requests/Professor SDC/routes/modules/treinamento.php SDC/resources/js/Pages/Treinamento/Professor/MeusCursos.vue SDC/resources/js/Pages/Treinamento/Professor/Conteudo.vue SDC/resources/js/Composables/treinamento/usePdf.js SDC/package.json SDC/package-lock.json
git commit -m "✨ feat(treinamento): area do professor para montar pastas e itens do curso"
```

---

### Task 6: Trilha, progresso e entrega de projeto (núcleo compartilhado + endpoints dos dois escopos)

**Files:**
- Create: `DTOs/EstadoTrilha.php`, `DTOs/EstadoPasta.php`, `DTOs/EstadoItem.php`, `Services/TrilhaService.php`, `Services/ProgressoService.php`, `Services/ProjetoService.php`, `Support/InscricaoDoAluno.php`, `Requests/RegistrarProgressoRequest.php`, `Requests/EnviarEntregaRequest.php`, `Controllers/Aluno/{SalaDeAulaController,ItemArquivoController,ProgressoController,EntregaStoreController}.php`, `Controllers/Portal/{SalaDeAulaController,SalaItemArquivoController,SalaProgressoController,SalaEntregaStoreController}.php`
- Modify: `SDC/routes/modules/treinamento.php`, `SDC/routes/modules/treinamento-portal.php`
- Test (local): `tests/Feature/Treinamento/Ead/TrilhaServiceTest.php`, `tests/Feature/Treinamento/Ead/SalaEndpointsTest.php`

**Interfaces:**
- Consumes: `ArquivoCursoService` (Task 5), models/enums (Task 2).
- Produces:
  - `final readonly class EstadoItem { public function __construct(public ModuloItem $item, public bool $liberado, public ?StatusProgresso $status, public int $posicao, public ?StatusEntrega $entrega, public ?string $parecer) ; toArray(): array }`
  - `final readonly class EstadoPasta { __construct(public Modulo $modulo, public bool $liberada, public bool $concluida, public array $itens /* list<EstadoItem> */); toArray(): array }`
  - `final readonly class EstadoTrilha { __construct(public float $percentual, public ?int $proximoItemId, public int $concluidos, public int $total, public array $pastas /* list<EstadoPasta> */); toArray(): array }`
  - `TrilhaService`: `estado(Inscricao $i): EstadoTrilha`; `itemLiberado(Inscricao $i, ModuloItem $item): bool`; `atualizarPastas(Inscricao $i): void`.
  - `ProgressoService`: `registrarPosicao(Inscricao $i, ModuloItem $item, int $posicao): ProgressoItem`; `concluir(Inscricao $i, ModuloItem $item): ProgressoItem`.
  - `ProjetoService`: `enviar(Inscricao $i, ModuloItem $item, UploadedFile $arquivo, ?string $comentario): ProjetoEntrega`; `corrigir(ProjetoEntrega $e, User $professor, bool $aprovar, ?string $parecer): void` (usado na Task 8); `ultimaEntrega(Inscricao $i, ModuloItem $item): ?ProjetoEntrega`.
  - `InscricaoDoAluno::aprovada(Treinamento $t, ?Model $inscrito): Inscricao` (404 se o público não aceita o inscrito, 403 se não há inscrição `APROVADA`).
  - Rotas aluno servidor (prefixo `/treinamentos/aluno`, nome `treinamentos.aluno.`, `can:treinamento.cursos.view`, antes de `/{id}`): `GET /cursos/{treinamento}` -> `sala`; `GET /cursos/{treinamento}/itens/{item}/arquivo` -> `itens.arquivo` (`?download=1`); `POST /cursos/{treinamento}/itens/{item}/progresso` -> `itens.progresso` (JSON `{posicao}`, responde `EstadoTrilha::toArray()`); `POST /cursos/{treinamento}/itens/{item}/entregas` -> `itens.entregas`.
  - Rotas aluno cidadão (dentro de `auth:cidadao` em `treinamento-portal.php`): `GET /cursos/{slug}` -> `portal.treinamento.cursos.sala`; `GET /cursos/{slug}/itens/{item}/arquivo` -> `portal.treinamento.cursos.itens.arquivo`; `POST /cursos/{slug}/itens/{item}/progresso` -> `portal.treinamento.cursos.itens.progresso`; `POST /cursos/{slug}/itens/{item}/entregas` -> `portal.treinamento.cursos.itens.entregas`.
  - As páginas Inertia renderizadas nesta task são `Treinamento/Aluno/SalaDeAula` e `Treinamento/Portal/SalaDeAula`, com props `curso` (`id`, `slug`, `titulo`, `carga_horaria`), `estado` (`EstadoTrilha::toArray()`), `escopo` (`'aluno'|'portal'`). Os arquivos `.vue` vêm na Task 7; aqui os testes usam `assertInertia` só no componente e nas props.

**Regras (algoritmo que as assinaturas não determinam):**
- Itens e pastas considerados: só os não excluídos, em `ordem`.
- Pasta 1 é liberada se a inscrição está `APROVADA`. Pasta N+1 é liberada se a pasta N está concluída.
- Pasta concluída = existe `progresso_pastas` para (inscrição, pasta) **ou** todos os itens obrigatórios ativos dela estão `CONCLUIDO`. Pasta sem item obrigatório conta como concluída. `atualizarPastas()` percorre as pastas em ordem e grava `progresso_pastas` (`concluida_em = now()`) para cada pasta concluída ainda sem registro, parando na primeira não concluída. Uma vez gravada, a pasta não reabre (spec 8.7).
- Item liberado = pasta liberada.
- `percentual` = `round(100 * concluidos / total, 1)`, `total` = itens obrigatórios ativos de todas as pastas e `concluidos` = obrigatórios `CONCLUIDO` + obrigatórios não concluídos de pastas já gravadas em `progresso_pastas`; `total = 0` dá `0.0`.
- `proximoItemId` = primeiro item liberado não concluído na ordem; `null` se não houver.
- `registrarPosicao`: exige `itemLiberado` (senão `AuthorizationException`) e tipo PDF; `posicao` validada `integer|min:1` no request; grava `max(posicao_atual, min(posicao, total_paginas))` via `upsert` na chave única (inscrição, item); conclui quando a posição chega a `total_paginas` (PDF de 1 página conclui no primeiro registro); depois chama `atualizarPastas()`.
- `enviar`: exige item PROJETO liberado e que a última entrega não esteja `ENVIADA` nem `APROVADA` (`DomainException`); cria uma linha por envio; marca o progresso do item como `EM_ANDAMENTO`; registra na trilha `RegistroDeAcao::registrarNoProtocolo(Treinamento::class, $id, AcaoTrilha::Relacionado, 'nova entrega de projeto')`.
- Arquivo do item: exige `itemLiberado`; 404 se o item não tem arquivo.

- [ ] **Step 1: Escrever `TrilhaServiceTest`**: `test_so_a_primeira_pasta_abre_no_inicio`; `test_concluir_pdf_libera_a_pasta_seguinte` (2 pastas com 1 PDF de 3 págs; posições 1, 3 -> pasta 2 liberada, `progresso_pastas` com 1 linha); `test_posicao_nao_regride_nem_passa_do_total` (5 depois 2 -> 5; 99 em PDF de 10 -> 10 e CONCLUIDO) (Review Focus 5); `test_pdf_de_uma_pagina_conclui_ao_abrir`; `test_item_novo_em_pasta_concluida_nao_reabre` (conclui a pasta 1, cria item obrigatório novo nela -> pasta 2 continua liberada); `test_item_excluido_nao_conta_e_curso_vazio_da_zero` (Review Focus 4); `test_item_opcional_nao_trava`; `test_percentual_e_proximo_item`; `test_projeto_enviado_nao_aceita_novo_envio_ate_devolucao`.
- [ ] **Step 2: Escrever `SalaEndpointsTest`**: `test_servidor_abre_sala_do_curso_de_servidores` (200, componente `Treinamento/Aluno/SalaDeAula`, `estado.pastas` com 2 itens); `test_cidadao_abre_sala_pelo_slug`; `test_cidadao_nao_abre_curso_de_servidores_pelo_portal` (404) (Review Focus 2); `test_servidor_nao_abre_curso_de_cidadaos_pelo_sdc` (404); `test_inscricao_pendente_recebe_403`; `test_progresso_em_item_de_pasta_travada_da_403_e_nao_grava` (Review Focus 1); `test_arquivo_de_item_travado_da_403`; `test_arquivo_de_item_liberado_faz_stream_inline` (header `content-type: application/pdf`); `test_entrega_grava_arquivo_e_linha`.
- [ ] **Step 3: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/TrilhaServiceTest.php` e `TEST tests/Feature/Treinamento/Ead/SalaEndpointsTest.php`.
- [ ] **Step 4: Implementar** DTOs, services, resolver, requests (`EnviarEntregaRequest`: `arquivo required|file|max:51200`, `comentario nullable|string|max:2000`) e os 8 controllers. Os controllers do portal só diferem dos do aluno no inscrito (`Auth::guard('cidadao')->user()`), na busca do curso (por `link_publico_slug`, `publicado()`) e no `escopo`.
- [ ] **Step 5: Rodar os dois**: PASS (9 + 9).
- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/Treinamento/DTOs SDC/app/Modules/Treinamento/Services/TrilhaService.php SDC/app/Modules/Treinamento/Services/ProgressoService.php SDC/app/Modules/Treinamento/Services/ProjetoService.php SDC/app/Modules/Treinamento/Support/InscricaoDoAluno.php SDC/app/Modules/Treinamento/Requests/RegistrarProgressoRequest.php SDC/app/Modules/Treinamento/Requests/EnviarEntregaRequest.php SDC/app/Modules/Treinamento/Controllers/Aluno SDC/app/Modules/Treinamento/Controllers/Portal/SalaDeAulaController.php SDC/app/Modules/Treinamento/Controllers/Portal/SalaItemArquivoController.php SDC/app/Modules/Treinamento/Controllers/Portal/SalaProgressoController.php SDC/app/Modules/Treinamento/Controllers/Portal/SalaEntregaStoreController.php SDC/routes/modules/treinamento.php SDC/routes/modules/treinamento-portal.php
git commit -m "✨ feat(treinamento): trilha com progresso e entrega de projeto nos dois escopos"
```

---

### Task 7: Sala de aula (frontend compartilhado) e "Meus cursos" do aluno

**Files:**
- Create: `SDC/resources/js/Composables/treinamento/useRotasSala.js`, `Components/Organisms/Treinamento/Sala/{TrilhaLateral,VisualizadorPdf,ItemProjeto}.vue`, `Templates/Treinamento/SalaDeAulaTemplate.vue`, `Pages/Treinamento/Aluno/{SalaDeAula,MeusCursos}.vue`, `Pages/Treinamento/Portal/SalaDeAula.vue`, `Pages/Treinamento/Professor/Previa.vue`, `SDC/app/Modules/Treinamento/Controllers/Aluno/MeusCursosController.php`, `SDC/app/Modules/Treinamento/Controllers/Professor/PreviaController.php`
- Modify: `SDC/resources/js/Pages/Treinamento/Portal/MinhasInscricoes.vue`, `Controllers/Portal/MinhasInscricoesController.php`, `SDC/routes/modules/treinamento.php`
- Test (local): `tests/Feature/Treinamento/Ead/MeusCursosTest.php`

**Interfaces:**
- Consumes: rotas e props da Task 6; `usePdf.js` (Task 5).
- Produces:
  - `useRotasSala(escopo: 'aluno'|'portal'|'previa', chave: number|string)` -> `{ arquivo(itemId, download=false): string, progresso(itemId): string|null, entrega(itemId): string|null }` com ziggy `route()`; em `previa`, `progresso`/`entrega` retornam `null` e o arquivo usa `treinamentos.professor.itens.arquivo`.
  - `SalaDeAulaTemplate.vue` props `{ curso: Object, estado: Object, escopo: String }`: layout da Tela B (lateral com anel de progresso, pastas e itens com estado/cadeado; área principal com o item atual; rodapé Anterior/Próximo). Abre em `estado.proximoItemId` (ou no primeiro item). Em `previa`, mostra a faixa "Pré-visualização: o progresso não é salvo" e não chama progresso nem entrega.
  - `VisualizadorPdf.vue` props `{ url: String, posicaoInicial: Number, totalPaginas: Number }`, emite `pagina(n)`; paginação e zoom; o template envia o progresso com debounce de 1 s e substitui `estado` pela resposta.
  - `ItemProjeto.vue` props `{ item: Object, urlEntrega: String|null, urlArquivo: String|null }`: enunciado, arquivo de apoio, envio (arquivo + comentário), estado e parecer da última entrega.
  - `Aluno/MeusCursos.vue` (rota `GET /treinamentos/aluno` -> `treinamentos.aluno.index`): "Meus cursos" (inscrições do servidor em cursos EAD, com % e "Continuar") e "Cursos disponíveis" (EAD `SERVIDORES` publicados e abertos, com "Inscrever-se", reusando `treinamentos.inscrever`).
  - `MinhasInscricoesController` passa `percentual` e `sala_url` para inscrições EAD `APROVADA`; `MinhasInscricoes.vue` mostra "Abrir curso".
  - `PreviaController` (`GET /treinamentos/professor/{treinamento}/previa` -> `treinamentos.professor.previa`) monta um `EstadoTrilha` com tudo liberado e nada concluído (`TrilhaService::estadoPrevia(Treinamento $t): EstadoTrilha`, acrescentar ao service).

- [ ] **Step 1: Escrever `MeusCursosTest`**: `test_meus_cursos_lista_inscricoes_ead_com_percentual`; `test_cursos_disponiveis_so_mostra_ead_de_servidores_publicados`; `test_minhas_inscricoes_do_portal_traz_link_da_sala_para_ead_aprovada`; `test_previa_libera_tudo_e_nao_tem_progresso`.
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/MeusCursosTest.php`.
- [ ] **Step 3: Implementar** controllers e `estadoPrevia`; rodar o teste; PASS (4).
- [ ] **Step 4: Implementar** composable, organismos, template e páginas; `npx vite build` sem erro.
- [ ] **Step 5: Smoke manual nos dois escopos** (dev): servidor inscrito num curso EAD `SERVIDORES` abre a sala, lê o PDF até o fim, a pasta 2 abre, recarrega e volta na página certa; cidadão faz o mesmo num curso `CIDADAOS` pelo portal; envio de projeto aparece como "enviado"; professor abre a prévia e nada é gravado.
- [ ] **Step 6: Commit**

```bash
git add SDC/resources/js/Composables/treinamento/useRotasSala.js SDC/resources/js/Components/Organisms/Treinamento/Sala SDC/resources/js/Templates/Treinamento/SalaDeAulaTemplate.vue SDC/resources/js/Pages/Treinamento/Aluno SDC/resources/js/Pages/Treinamento/Portal/SalaDeAula.vue SDC/resources/js/Pages/Treinamento/Portal/MinhasInscricoes.vue SDC/resources/js/Pages/Treinamento/Professor/Previa.vue SDC/app/Modules/Treinamento/Controllers/Aluno/MeusCursosController.php SDC/app/Modules/Treinamento/Controllers/Professor/PreviaController.php SDC/app/Modules/Treinamento/Controllers/Portal/MinhasInscricoesController.php SDC/app/Modules/Treinamento/Services/TrilhaService.php SDC/routes/modules/treinamento.php
git commit -m "✨ feat(treinamento): sala de aula EAD para servidor e cidadao"
```

---

### Task 8: Professor: alunos e correções de projeto

**Files:**
- Create: `Services/AcompanhamentoService.php`, `Mail/EntregaCorrigidaMail.php`, `SDC/resources/views/emails/treinamento/entrega_corrigida.blade.php`, `Controllers/Professor/{AlunosController,CorrecoesController,CorrecaoStoreController,EntregaArquivoController}.php`, `Requests/Professor/CorrigirEntregaRequest.php`, `SDC/resources/js/Pages/Treinamento/Professor/{Alunos,Correcoes}.vue`
- Modify: `Services/ProjetoService.php` (`corrigir`), `SDC/routes/modules/treinamento.php`
- Test (local): `tests/Feature/Treinamento/Ead/AcompanhamentoTest.php`

**Interfaces:**
- Consumes: `TrilhaService::estado()`, `ProjetoService` (Task 6), `ArquivoCursoService::responder()` (Task 5).
- Produces:
  - `AcompanhamentoService::alunos(Treinamento $t, bool $soTravados = false): Collection` de arrays `{inscricao_id, nome, origem, percentual, onde_esta, travado}`. `origem`: `'Servidor'` para User; para Cidadao, `'Cidadão'`. `onde_esta`: título da pasta e do `proximoItemId`, ou `'Concluiu todos os itens'`. `travado` = a última entrega de algum projeto liberado está `ENVIADA` (esperando correção). Nunca inclui CPF, e-mail nem telefone (spec 8.6). Nomes de User buscados em uma única segunda consulta (outro banco).
  - `AcompanhamentoService::pendentes(Treinamento $t): Collection` de entregas `ENVIADA` (mais antigas primeiro) com aluno, pasta, item, data, comentário.
  - `ProjetoService::corrigir(ProjetoEntrega $e, User $professor, bool $aprovar, ?string $parecer)`: só `ENVIADA`; grava `status`, `parecer`, `corrigido_por`, `corrigido_em`; aprovar -> `ProgressoService::concluir()` + `TrilhaService::atualizarPastas()`; devolver exige `parecer` (`DomainException`); envia `EntregaCorrigidaMail` (fila) ao e-mail do inscrito, se houver.
  - Rotas (grupo do professor com `leciona.curso`): `GET /{treinamento}/alunos?travados=1` -> `treinamentos.professor.alunos`; `GET /{treinamento}/correcoes` -> `treinamentos.professor.correcoes`; `POST /{treinamento}/entregas/{entrega}/correcao` -> `treinamentos.professor.entregas.correcao` (`aprovar` bool, `parecer` nullable `max:2000`); `GET /{treinamento}/entregas/{entrega}/arquivo` -> `treinamentos.professor.entregas.arquivo`. Entrega de outro curso -> 404.
  - Páginas `Professor/Alunos.vue` (tabela do rascunho 3, P2, com filtro "Travados" e exportação fora do escopo) e `Professor/Correcoes.vue` (fila com baixar arquivo, aprovar, devolver com parecer). O menu lateral do curso (Conteúdo, Alunos, Correções com contador, Prévia, Publicar) vira um componente compartilhado pelas páginas do professor: `Components/Organisms/Treinamento/Professor/MenuCurso.vue`.

- [ ] **Step 1: Escrever `AcompanhamentoTest`**: `test_lista_alunos_com_origem_percentual_sem_dados_pessoais` (array de cada aluno não tem chaves `cpf`, `email`, `telefone`); `test_filtro_travados_traz_quem_espera_correcao`; `test_aprovar_entrega_conclui_item_e_libera_pasta`; `test_devolver_sem_parecer_falha`; `test_devolver_permite_novo_envio`; `test_correcao_envia_email` (`Mail::fake()`, `assertQueued(EntregaCorrigidaMail::class)`); `test_entrega_de_outro_curso_da_404`.
- [ ] **Step 2: Rodar e ver falhar**: `TEST tests/Feature/Treinamento/Ead/AcompanhamentoTest.php`.
- [ ] **Step 3: Implementar backend**; rodar; PASS (7).
- [ ] **Step 4: Implementar páginas e `MenuCurso.vue`** (e trocar o menu provisório de `Conteudo.vue` por ele); `npx vite build` sem erro.
- [ ] **Step 5: Smoke manual**: aluno envia projeto, aparece em Correções com contador; devolver com parecer, o aluno vê o parecer e reenvia; aprovar, a pasta seguinte abre para o aluno.
- [ ] **Step 6: Commit**

```bash
git add SDC/app/Modules/Treinamento/Services/AcompanhamentoService.php SDC/app/Modules/Treinamento/Services/ProjetoService.php SDC/app/Modules/Treinamento/Mail/EntregaCorrigidaMail.php SDC/resources/views/emails/treinamento/entrega_corrigida.blade.php SDC/app/Modules/Treinamento/Controllers/Professor SDC/app/Modules/Treinamento/Requests/Professor/CorrigirEntregaRequest.php SDC/resources/js/Pages/Treinamento/Professor SDC/resources/js/Components/Organisms/Treinamento/Professor SDC/routes/modules/treinamento.php
git commit -m "✨ feat(treinamento): acompanhamento de alunos e correcao de projetos pelo professor"
```

---

### Task 9: Verificação final da fase

- [ ] **Step 1: Suite do módulo**: `TEST tests/Feature/Treinamento`; esperado tudo verde (os 8 arquivos da fase).
- [ ] **Step 2: Rotas**: `docker exec newsdc_dev_app php artisan route:list --path=treinamentos` mostra `aluno` e `professor` antes de `treinamentos/{id}`; `--path=portal-treinamento` mostra as 4 rotas de `cursos`.
- [ ] **Step 3: Recarregar o Octane** para servir as rotas novas: `docker exec newsdc_dev_app php artisan octane:reload`. Classe nova de módulo exige `composer dump-autoload` no container antes.
- [ ] **Step 4: Smoke ponta a ponta** (dev): admin cria curso EAD `SERVIDORES` com aprovação automática e designa um professor; professor monta 2 pastas (PDF + projeto) e publica; servidor se inscreve, entra na hora, lê, envia projeto; professor devolve e depois aprova; servidor chega a 100%. Repetir o caminho do aluno com um curso `CIDADAOS` pelo portal.
- [ ] **Step 5: Limpar** dados de smoke criados no banco de dev e listar ao usuário o que foi apagado.
