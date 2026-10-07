# Ficha cadastral PAE do Anexo B, item 2 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. O usuário pediu execução neste chat; não delegar sem nova instrução.

**Goal:** Registrar os dados cadastrais do item 2 do Anexo B por protocolo PAE, com versões auditáveis e consulta acessível na interface.

**Architecture:** Uma tabela append-only guarda snapshots completos da ficha. Um serviço centraliza pré-preenchimento, normalização, pendências e gravação transacional; controller e página Inertia apenas apresentam e enviam esse contrato. Os municípios ZAS/ZSS continuam sob a triagem da fase B e entram na versão como snapshot lido pelo servidor.

**Tech Stack:** Laravel 12, PHP 8.3, PostgreSQL/JSONB, Inertia 2, Vue 3, Vite.

**Spec:** `docs/superpowers/specs/2026-10-06-pae-c-ficha-anexo-b-design.md`.

## Global Constraints

- Implementar só o item 2 do Anexo B; não alterar status, CCPAE nem demais itens.
- Não editar `DOC.MD`; não inserir emoji no código.
- Usar os slugs `pae.protocolos.view` e `pae.protocolos.edit`; nenhuma permissão nova.
- Toda mudança de schema da fase C fica na migration principal `2026_10_06_120000_create_pae_fichas_anexo_b.php`.
- Executar testes locais antes da implementação de cada comportamento. Arquivos de teste criados para a fase não entram no commit.
- Fazer um commit único e coerente para spec + plano após aprovação do plano, e outro para a feature completa após verificação. Mensagens usam Gitmoji no título, em pt-BR.
- O checkout principal está sujo em outra branch. Trabalhar somente no worktree `feat/pae-gmg83-fase-c`, iniciado em `794714bf`.
- `ENGINEER.MD` e `PAPIROS.MD` foram procurados no repositório e não existem nesta base. Zen/Gemini/Notion MCP não estão disponíveis; a exceção para esta documentação PAE já foi aprovada pelo usuário.

## Review Focus

1. Número `0` informado não deve ser classificado como campo pendente (Task 2).
2. Dois usuários salvando sobre a mesma versão não podem perder alterações (Task 2).
3. Alteração da lista ZAS/ZSS após uma versão não pode reescrever o histórico (Task 2).
4. Protocolo arquivado deve continuar consultável sem aceitar novo salvamento (Task 3).
5. Protocolo antigo sem empreendimento, ficha ou municípios precisa abrir sem erro e mostrar pendências (Task 3).

## Mapa de arquivos

| Responsabilidade | Arquivos |
| --- | --- |
| Persistência histórica | Criar `SDC/database/migrations/2026_10_06_120000_create_pae_fichas_anexo_b.php`, `SDC/app/Modules/Pae/Models/PaeFichaAnexoB.php`; modificar `SDC/app/Modules/Pae/Models/PaeProtocolo.php` |
| Contrato de dados e regra de negócio | Criar `SDC/app/Modules/Pae/Services/PaeFichaAnexoBService.php` |
| HTTP e autorização | Criar `SDC/app/Modules/Pae/Requests/SalvarFichaAnexoBRequest.php`, `SDC/app/Modules/Pae/Controllers/PaeFichaAnexoBController.php`; modificar `SDC/routes/modules/pae.php` |
| Interface | Criar `SDC/resources/js/Pages/PaeFichaAnexoB.vue`; modificar `SDC/resources/js/Components/Atoms/Button/ActionButton.vue`, `SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue`, `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue`, `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue`, `SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue` |
| Correção de pré-preenchimento indevido | Modificar `SDC/resources/js/Composables/pae/usePaeFormulario.js` |
| Testes transitórios | Criar durante o trabalho `SDC/tests/Feature/Pae/PaeFichaAnexoBTest.php`; retirar do staging antes do commit |

---

### Task 1: Schema e modelo de versões

**Files:**
- Create: `SDC/database/migrations/2026_10_06_120000_create_pae_fichas_anexo_b.php`
- Create: `SDC/app/Modules/Pae/Models/PaeFichaAnexoB.php`
- Modify: `SDC/app/Modules/Pae/Models/PaeProtocolo.php`
- Test, temporary: `SDC/tests/Feature/Pae/PaeFichaAnexoBTest.php`

**Interfaces:** Produz relação `PaeProtocolo::fichasAnexoB(): HasMany` e model `PaeFichaAnexoB`, com cast de números/JSON e `versao` inteiro. Task 2 usa ambos. Cada linha representa uma versão completa; nunca usar `update()` para editar uma linha.

- [ ] **Step 1: Criar o teste de persistência que falha.** No teste transitório, usar `RefreshDatabase`, uma `PaeProtocolo::factory()->create()` e `User::factory()->create()`. Inserir duas versões do mesmo protocolo, verificar `versao` 1 e 2 e que uma segunda versão 1 lança violação de unicidade. Verificar que soft delete/arquivamento do protocolo não apaga as linhas.

```php
$protocolo = PaeProtocolo::factory()->create();
$protocolo->fichasAnexoB()->create(['versao' => 1, 'criado_por' => $user->id, 'created_at' => now()]);
$this->assertSame(1, $protocolo->fichasAnexoB()->count());
```

- [ ] **Step 2: Rodar apenas `PaeFichaAnexoBTest` e confirmar falha por tabela/relação inexistente.** Usar o ambiente PHP 8.3/PostgreSQL do projeto; o PHP 8.1 do host não satisfaz `composer.json`.

```powershell
cd SDC
php artisan test --filter=PaeFichaAnexoBTest
```

- [ ] **Step 3: Criar a migration única.** Usar FK para protocolo e usuário; `unique(['protocolo_id', 'versao'])`; índice `(protocolo_id, created_at)`; escalares nullable para permitir rascunho; `jsonb` nullable para listas não declaradas; `municipios_snapshot` JSONB não nulo com `[]` como default no serviço. `created_at` é a data de gravação, sem `updated_at`.

```php
Schema::create('pae_fichas_anexo_b', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('protocolo_id')->constrained('pae_protocolos')->cascadeOnDelete();
    $table->unsignedInteger('versao');
    $table->string('nome_barragem')->nullable();
    $table->string('nome_mina')->nullable();
    $table->string('metodo_construtivo', 100)->nullable();
    $table->decimal('volume_reservatorio', 15, 2)->nullable();
    $table->foreignId('municipio_sede_id')->nullable()->constrained('municipios');
    $table->string('municipio_sede_nome')->nullable();
    $table->decimal('latitude', 10, 7)->nullable();
    $table->decimal('longitude', 10, 7)->nullable();
    $table->text('tipo_rejeito')->nullable();
    $table->string('toxicidade')->nullable();
    $table->decimal('extensao_zas_km', 10, 3)->nullable();
    $table->unsignedBigInteger('populacao_zas')->nullable();
    $table->unsignedBigInteger('populacao_zas_mobilidade_reduzida')->nullable();
    $table->unsignedBigInteger('populacao_zss')->nullable();
    $table->jsonb('cursos_agua')->nullable();
    $table->unsignedInteger('edificacoes_hospitalares')->nullable();
    $table->unsignedInteger('edificacoes_escolares')->nullable();
    $table->unsignedInteger('edificacoes_prisionais')->nullable();
    $table->unsignedInteger('edificacoes_outras')->nullable();
    $table->jsonb('estruturas_associadas')->nullable();
    $table->jsonb('municipios_snapshot');
    $table->foreignId('criado_por')->constrained('users');
    $table->timestampTz('created_at');
    $table->unique(['protocolo_id', 'versao']);
    $table->index(['protocolo_id', 'created_at']);
});
```

- [ ] **Step 4: Criar model e relação, executar o teste até passar.** `down()` remove só esta tabela. `municipios_snapshot` armazena `{municipio_id, nome, na_zas, na_zss}`. Os campos numéricos e JSON recebem casts no model; `created_at` usa `datetime`; `public $timestamps = false`.

```php
public function fichasAnexoB(): HasMany
{
    return $this->hasMany(PaeFichaAnexoB::class, 'protocolo_id')->orderBy('versao');
}
```

### Task 2: Regra de negócio e histórico

**Files:**
- Create: `SDC/app/Modules/Pae/Services/PaeFichaAnexoBService.php`
- Extend temporary test: `SDC/tests/Feature/Pae/PaeFichaAnexoBTest.php`

**Interfaces:** `visualizar(PaeProtocolo $protocolo, ?int $versao = null): array` devolve `ficha`, `versoes`, `municipios_atuais`, `pendencias` e `municipios_alterados`; `salvar(PaeProtocolo $protocolo, array $dados, User $usuario): PaeFichaAnexoB`; `pendencias(array $ficha, array $municipios): array`. Helpers privados `normalizar(array $dados, Collection $municipios): array` e `igual(PaeFichaAnexoB $atual, array $conteudo): bool` isolam o snapshot e a comparação. Task 3 passa somente `$request->validated()` para `salvar` e acrescenta `protocolo`/`can_edit` às props da página.

- [ ] **Step 1: Testar primeiro a regra completa.** Casos: pré-preenchimento legado sem escrita; zero informado como completo; `null` versus `[]` para rios/estruturas; duas gravações diferentes geram versões e timeline; repetição idêntica é idempotente; `base_versao` obsoleta é rejeitada; mudança em `pae_protocolo_municipios` aparece só na versão seguinte; nomes antigos persistem após alteração do catálogo. Usar dados de fixture explícitos e `assertDatabaseCount`, `assertSame`, `assertThrows`.

```php
$primeira = $service->salvar($protocolo, ['base_versao' => 0, 'nome_barragem' => 'Barragem A'], $user);
$mesma = $service->salvar($protocolo, ['base_versao' => 1, 'nome_barragem' => 'Barragem A'], $user);
$this->assertSame($primeira->id, $mesma->id);
$this->assertSame(1, $protocolo->fichasAnexoB()->count());
```

- [ ] **Step 2: Rodar o teste e confirmar a falha por serviço inexistente.**

```powershell
cd SDC
php artisan test --filter=PaeFichaAnexoBTest
```

- [ ] **Step 3: Implementar o serviço.** `visualizar` carrega a versão pedida ou mais recente; sem versão, monta apenas um rascunho a partir de `PaeEmpnto`. `salvar` bloqueia o protocolo com `lockForUpdate`, confere `base_versao`, recusa arquivado, lê municípios atuais com `municipio:id,nome`, ordena por ID, resolve `municipio_sede_nome`, normaliza strings/listas e só cria versão se o conteúdo mudou. Comparar campos de negócio, não IDs, autor ou datas. Registrar `TimelinePae::registrar($locked, 'ficha_anexo_b', 'Ficha cadastral Anexo B salva na versão '.$numero.'.', $usuario)` na mesma transação. No conflito, lançar `ValidationException::withMessages(['base_versao' => 'A ficha foi alterada por outra pessoa. Recarregue a página.'])`.

```php
return DB::transaction(function () use ($protocolo, $dados, $usuario): PaeFichaAnexoB {
    $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
    if ($locked->arquivado) {
        throw ValidationException::withMessages(['protocolo' => 'Protocolo arquivado: a ficha está somente para consulta.']);
    }
    $atual = $locked->fichasAnexoB()->orderByDesc('versao')->first();
    if ((int) $dados['base_versao'] !== ($atual?->versao ?? 0)) {
        throw ValidationException::withMessages(['base_versao' => 'A ficha foi alterada por outra pessoa. Recarregue a página.']);
    }
    $municipios = $locked->municipiosImpactados()->with('municipio:id,nome')->get();
    $conteudo = $this->normalizar($dados, $municipios);
    if ($atual && $this->igual($atual, $conteudo)) {
        return $atual;
    }
    $numero = ($atual?->versao ?? 0) + 1;
    $ficha = $locked->fichasAnexoB()->create([
        ...$conteudo, 'versao' => $numero, 'criado_por' => $usuario->id, 'created_at' => now(),
    ]);
    TimelinePae::registrar($locked, 'ficha_anexo_b', 'Ficha cadastral Anexo B salva na versão '.$numero.'.', $usuario);
    return $ficha;
});
```

- [ ] **Step 4: Calcular pendências sem falsos positivos e repetir o teste.** Escalares `null`/string vazia pendem; zero não. `cursos_agua` e `estruturas_associadas` pendem somente se `null`. Checar existência de município ZAS e ZSS no snapshot da versão ou, no rascunho, na lista atual. Exibir aviso se `municipios_snapshot` difere da lista canônica.

```php
$ausente = static fn ($v): bool => $v === null || (is_string($v) && trim($v) === '');
$temZas = collect($municipios)->contains(fn (array $m): bool => $m['na_zas']);
$temZss = collect($municipios)->contains(fn (array $m): bool => $m['na_zss']);
```

### Task 3: Rotas, validação e autorização

**Files:**
- Create: `SDC/app/Modules/Pae/Requests/SalvarFichaAnexoBRequest.php`
- Create: `SDC/app/Modules/Pae/Controllers/PaeFichaAnexoBController.php`
- Modify: `SDC/routes/modules/pae.php`
- Extend temporary test: `SDC/tests/Feature/Pae/PaeFichaAnexoBTest.php`

**Interfaces:** `GET pae.protocolo.ficha-anexo-b.show` recebe `?versao=N`; `PUT pae.protocolo.ficha-anexo-b.salvar` recebe `base_versao` e campos da ficha; controller renderiza `PaeFichaAnexoB` e redireciona de volta para versão vigente após salvar. Task 4 usa estes nomes de rota e props do Task 2.

- [ ] **Step 1: Testar contrato HTTP antes das rotas.** Cobrir usuário com view, com edit, sem permissão; versão de outro protocolo inexistente; corpo com `protocolo_id` forjado; legado sem `pae_empnto_id`; arquivado; latitude 91, longitude -181, contagens negativas, mobilidade maior que ZAS; erro de conflito em `base_versao`.

```php
$this->actingAs($viewer)->get(route('pae.protocolo.ficha-anexo-b.show', $protocolo))->assertOk();
$this->actingAs($viewer)->put(route('pae.protocolo.ficha-anexo-b.salvar', $protocolo), ['base_versao' => 0])->assertForbidden();
$this->actingAs($editor)->put(route('pae.protocolo.ficha-anexo-b.salvar', $protocolo), ['base_versao' => 0, 'latitude' => 91])->assertSessionHasErrors('latitude');
```

- [ ] **Step 2: Rodar o teste para confirmar falha por rota inexistente.**

```powershell
cd SDC
php artisan test --filter=PaeFichaAnexoBTest
```

- [ ] **Step 3: Criar o `FormRequest` com regras exatas.** `base_versao` obrigatório, inteiro não negativo. Os demais campos são nullable e validados por tipo/limite. `cursos_agua` e `estruturas_associadas`: `nullable|array|max:100`, cada entrada `string|max:255`. Exigir presença das chaves de listas no corpo para distinguir `null` de `[]`, mas permitir `null`; `validated()` descarta campos fora do contrato, inclusive `protocolo_id` forjado. Validar mobilidade `lte:populacao_zas` quando ambos informados, com `withValidator`; não usar `required` nos dados cadastrais, porque rascunho incompleto é válido.

```php
return [
    'base_versao' => ['required', 'integer', 'min:0'],
    'latitude' => ['nullable', 'numeric', 'between:-90,90'],
    'longitude' => ['nullable', 'numeric', 'between:-180,180'],
    'populacao_zas' => ['nullable', 'integer', 'min:0'],
    'populacao_zas_mobilidade_reduzida' => ['nullable', 'integer', 'min:0'],
    'cursos_agua' => ['present', 'nullable', 'array', 'max:100'],
    'cursos_agua.*' => ['string', 'max:255'],
];
```

No `withValidator`, quando ambos os campos de população ZAS forem numéricos, adicionar erro em `populacao_zas_mobilidade_reduzida` se seu valor for maior que `populacao_zas`. Repetir o mesmo padrão `nullable` e limites da migration para todos os demais escalares do mapa de arquivos.

- [ ] **Step 4: Criar controller e rotas, repetir teste.** Controller usa route model binding e serviço; `GET` permite histórico em leitura, `PUT` depende de edit e arquivamento. Não alterar `pae_empntos`, `pae_forms`, status, CCPAE ou slugs ACL.

```php
Route::get('/protocolo/{paeProtocolo}/ficha-anexo-b', [PaeFichaAnexoBController::class, 'show'])
    ->name('protocolo.ficha-anexo-b.show')->middleware('can:pae.protocolos.view');
Route::put('/protocolo/{paeProtocolo}/ficha-anexo-b', [PaeFichaAnexoBController::class, 'salvar'])
    ->name('protocolo.ficha-anexo-b.salvar')->middleware('can:pae.protocolos.edit');
```

### Task 4: Página e acesso pela lista de protocolos

**Files:**
- Create: `SDC/resources/js/Pages/PaeFichaAnexoB.vue`
- Modify: `SDC/resources/js/Components/Atoms/Button/ActionButton.vue`
- Modify: `SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue`
- Modify: `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue`
- Modify: `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue`
- Modify: `SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue`
- Modify: `SDC/resources/js/Composables/pae/usePaeFormulario.js`

**Interfaces:** Página Inertia recebe `protocolo`, `ficha`, `versoes`, `municipios_atuais`, `pendencias`, `municipios_alterados`, `can_edit` do controller. `base_versao` vai no PUT; `?versao=N` abre versão histórica. O link no card e na tabela usa `pae.protocolo.ficha-anexo-b.show`.

- [ ] **Step 1: Criar a página com layout existente e estado `useForm`.** Montar seções da tabela da spec; usar controles numéricos que preservem string vazia como `null` no envio; opções explícitas “Não informado” versus “Nenhum” nas duas listas; mostrar erros por campo, pendências, fonte do pré-preenchimento, data/autor da versão e aviso de municípios alterados. Histórico em seletor ou links para `?versao=N`, em modo leitura. Botão Salvar oculto para arquivado, versão histórica ou falta de edit.

```js
const form = useForm({
  base_versao: props.ficha?.versao ?? 0,
  nome_barragem: props.ficha?.nome_barragem ?? null,
  cursos_agua: props.ficha?.cursos_agua ?? null,
  estruturas_associadas: props.ficha?.estruturas_associadas ?? null,
});
function salvar() {
  form.put(route('pae.protocolo.ficha-anexo-b.salvar', props.protocolo.id), { preserveScroll: true });
}
```

- [ ] **Step 2: Conectar a ação nas visualizações de grade e tabela.** Usar nova ação `ficha` com ícone de documento em `ActionButton`, `aliasOverride: 'view'` e rótulo “Ficha cadastral”. Propagar evento `ficha` pela grade até template e chamar `router.visit(route('pae.protocolo.ficha-anexo-b.show', id))`. Garantir que a ação apareça para quem pode ver, inclusive em protocolos arquivados.

```js
{ action: 'ficha', label: 'Ficha cadastral', aliasOverride: 'view', placement: 'menu', handler: () => $emit('ficha', protocolo.id) }
```

- [ ] **Step 3: Remover o fallback errado do relatório técnico.** `numero_zas` continua vindo de `formulario?.numero_zas`, mas não de `empreendimento?.pop_zas`.

```js
numero_zas: formulario?.numero_zas ?? '',
```

- [ ] **Step 4: Compilar o frontend e testar no navegador.** Conferir lista em grade e tabela, rascunho legado, campos `0`, erro de validação, salvamento, histórico, lista municipal alterada, usuário de leitura e arquivado, em desktop e largura móvel. O build deve terminar sem erro; nenhum log temporário permanece no código.

```powershell
cd SDC
npm run build
```

### Task 5: Verificação integrada e commits atômicos

**Files:** Todos os arquivos acima; nenhum arquivo de teste novo entra no commit.

**Interfaces:** Entrega a feature completa, documentação previamente aprovada e árvore limpa após commits.

- [ ] **Step 1: Executar migration e suíte PAE no runtime PHP 8.3/PostgreSQL, depois build.** Confirmar `route:list` das duas rotas, `php -l` nos PHP alterados, testes de fase C e regressões PAE. Se o runtime do worktree não estiver disponível, preparar dependências locais isoladas antes de declarar verificação; não usar o PHP 8.1 do host para Laravel 12.

```powershell
cd SDC
php artisan migrate --pretend
php artisan route:list --path=pae
php artisan test --filter=Pae
npm run build
```

- [ ] **Step 2: Conferir escopo de Git e ausência de logs.** Usar `git diff --check`, `git status --short`, `git diff --name-only`; garantir que nenhuma mudança do checkout principal entrou no worktree e que os testes transitórios estão fora do staging.

```powershell
git diff --check
git status --short
git diff --name-only
```

- [ ] **Step 3: Versionar documentação aprovada e feature, em dois commits coerentes.** O commit de documentação inclui spec + plano juntos. O commit da feature inclui migration, backend e frontend após verificação da feature inteira. Não fazer commits fragmentados por arquivo nem incluir testes novos; usar `git diff --cached --name-only` antes de cada commit.

```powershell
git add docs/superpowers/specs/2026-10-06-pae-c-ficha-anexo-b-design.md docs/superpowers/plans/2026-10-06-pae-c-ficha-anexo-b.md
git commit -m '📝 docs(pae): especifica fase C da ficha do Anexo B'
git add SDC/database/migrations/2026_10_06_120000_create_pae_fichas_anexo_b.php SDC/app/Modules/Pae SDC/routes/modules/pae.php SDC/resources/js/Pages/PaeFichaAnexoB.vue SDC/resources/js/Components/Atoms/Button/ActionButton.vue SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosGrid.vue SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue SDC/resources/js/Templates/Pae/PaeProtocolosIndexTemplate.vue SDC/resources/js/Composables/pae/usePaeFormulario.js
git diff --cached --name-only
git commit -m '✨ feat(pae): ficha cadastral versionada do Anexo B'
```

- [ ] **Step 4: Informar SHA, verificação e limitações reais.** Integração na `dev` ou push só após pedido explícito do usuário; a branch de fase C fica pronta para revisão.
