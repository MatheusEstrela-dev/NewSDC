# RAT — Refatoração Completa: Backend Clean Code + SOLID + Correções Frontend

**Autora:** Barbara Costa  
**Data:** 09 de março de 2026  
**Branch:** `feat/rat-backend`  
**Stack:** Laravel 12 · PHP 8.3 · Octane/RoadRunner · Vue 3 · Inertia.js · Vite

---

## 1. Contexto e Objetivo

O módulo **RAT (Relatório de Atendimento de Tristeza)** precisava de uma refatoração completa do backend seguindo os princípios de **Clean Code** e **SOLID**, além de correções em bugs críticos do frontend (formulário, botões de salvar, sub-abas). O objetivo central foi:

- Garantir que cada classe tenha **responsabilidade única (SRP)**
- Limitar cada método público a **no máximo 10 linhas** (exceto queries de banco de dados)
- Cada classe deve ter entre **3 e 5 métodos públicos**
- Usar **Injeção de Dependência (DIP)** via interfaces
- **Remover 14 tabelas legadas** do banco de dados não utilizadas
- Garantir comunicação correta entre **frontend Vue 3 ↔ backend Laravel ↔ banco de dados**

---

## 2. Arquitetura do Módulo RAT

```
app/Modules/Rat/
├── Application/
│   └── Services/
│       └── RatService.php              ← Orquestrador de casos de uso (5 métodos)
├── Config/
│   └── RatActionsConfig.php
├── Controllers/
│   ├── RatAttachmentController.php     ← Upload/remoção de arquivos (2 métodos)
│   ├── RatController.php               ← Navegação web: index/create/store/show/destroy
│   ├── RatDataController.php           ← Exportação CSV e JSON
│   ├── RatFinalizeController.php       ← [NOVO] Single-action: finaliza RAT
│   └── RatWriteController.php          ← Atualização PUT/PATCH
├── Domain/
│   └── Repositories/
│       └── RatRepositoryInterface.php  ← Interface contrato do repositório
├── DTOs/
│   └── RatFilterDTO.php
├── Enums/
│   ├── Status.php
│   ├── Protocolo.php
│   └── Localizacao.php
├── Http/
│   ├── Requests/
│   │   ├── ListRatRequest.php
│   │   └── UpdateRatRequest.php        ← Validação granular de campos aninhados
│   └── Resources/
│       ├── RatResource.php             ← Resource completo com status_label, atualizado_por
│       └── RatListResource.php         ← Resource leve com status_label, data_fato
├── Infrastructure/
│   └── Persistence/
│       └── EloquentRatRepository.php   ← Implementação Eloquent do repositório
├── Models/
│   └── Rat.php                         ← Model com updater() BelongsTo
├── Services/
│   ├── RatAttachmentService.php        ← [NOVO] Upload/remoção isolado (4 métodos)
│   ├── RatExportService.php
│   ├── RatFilterService.php            ← Filtros portáveis (sem raw MySQL)
│   ├── RatProtocoloService.php         ← [NOVO] Geração de protocolo sequencial
│   ├── RatStatisticsService.php
│   └── RatWriteService.php             ← Escrita: create/update/saveDraft/finalize
└── RatServiceProvider.php              ← Registro de bindings e singletons
```

---

## 3. Novos Arquivos Criados

### 3.1 `RatFinalizeController.php`
**Caminho:** `app/Modules/Rat/Controllers/RatFinalizeController.php`  
**Padrão:** Single-Action Controller (Invokable)  
**Motivo:** A finalização de um RAT é uma operação distinta das operações CRUD. Isolá-la em um controller próprio respeita SRP — o `RatController` não precisa saber das regras de finalização.

```php
class RatFinalizeController extends Controller
{
    public function __invoke(string $id): RedirectResponse
    {
        $rat = $this->writeService->finalize($id);
        return redirect()->route('rat.show', $rat->id)
            ->with('success', 'RAT finalizado com sucesso!');
    }
}
```

**Regras aplicadas:**
- Guard clause no `finalize()`: aborta com 404 se RAT não existe, 422 se já está finalizado
- Rota `PATCH /rat/{id}/finalize` passa a usar `RatFinalizeController::class` diretamente

---

### 3.2 `RatProtocoloService.php`
**Caminho:** `app/Modules/Rat/Services/RatProtocoloService.php`  
**Motivo:** O código anterior usava `random_int(1, 99999)` para gerar o protocolo, o que podia gerar **colisões** e não era sequencial. Um protocolo de governo precisa ser único e rastreável.

**Formato do protocolo:** `RAT-AAAA-NNNNN`  
**Exemplo:** `RAT-2026-00042`

```php
public function generate(): string
{
    $year     = (int) date('Y');
    $sequence = $this->repository->getLatestSequence($year) + 1;
    return sprintf('RAT-%d-%05d', $year, $sequence);
}
```

**Segurança concorrente:** Chamado dentro de `DB::transaction()` com `lockForUpdate()` no repositório para evitar condição de corrida em acessos simultâneos.

---

### 3.3 `RatAttachmentService.php`
**Caminho:** `app/Modules/Rat/Services/RatAttachmentService.php`  
**Motivo:** O `RatAttachmentController` tinha ~80 linhas com lógica de negócio embutida (validação de MIME, criação de UUID, operações de disco, montagem de metadados). Toda essa lógica foi extraída para o serviço.

**4 métodos públicos:**
| Método | Responsabilidade |
|---|---|
| `getAllowedMimes(): array` | Lista MIME types permitidos |
| `getMaxKb(): int` | Tamanho máximo (10 MB = 10240 KB) |
| `store(Rat $rat, UploadedFile $file): array` | Upload + persistência de metadados no JSON |
| `destroy(Rat $rat, string $anexoId): void` | Remove do disco e do JSON |

**2 métodos privados:**
- `persist(string $ratId, UploadedFile $file): array` — grava arquivo com UUID no disco
- `buildMetadata(...): array` — monta o array de metadados do anexo

**Armazenamento dos arquivos:** `storage/app/public/rat/{ratId}/{uuid}.{extensao}`

---

### 4. Migração de Remoção das Tabelas Legadas
**Arquivo:** `database/migrations/2026_03_09_200001_drop_rat_legacy_tables.php`  
**Status:** ✅ Executada com sucesso

**14 tabelas removidas** (arquitetura relacional abandonada, substituída por colunas JSON):

| Tabela Removida | Motivo |
|---|---|
| `rat_recursos_componentes_guarnicao` | Substituída por `rats.recursos` JSON |
| `rat_recursos_empregados` | Substituída por `rats.recursos` JSON |
| `rat_relato_recursos` | Substituída por `rats.recursos` JSON |
| `rat_ocorrencia_relatos` | Substituída por `rats.historico` JSON |
| `rat_ocorrencias` | Substituída por tabela `rats` |
| `rat_relato_envolvidos` | Substituída por `rats.envolvidos` JSON |
| `rat_relato_vistoria` | Substituída por `rats.vistoria` JSON |
| `rat_bem_afetado` | Substituída por `rats.vistoria` JSON |
| `rat_encaminhamento` | Substituída por `rats.historico` JSON |
| `rat_orgao_acionado` | Não utilizada |
| `rat_patologia` | Não utilizada |
| `rat_redec` | Não utilizada |
| `rat_dados_gerais` | Substituída por `rats.dados_gerais` JSON |
| `rat_veiculos` | Substituída por `rats.recursos` JSON |

---

## 4. Arquivos Modificados — Backend PHP

### 4.1 `EloquentRatRepository.php`
**Caminho:** `app/Modules/Rat/Infrastructure/Persistence/EloquentRatRepository.php`

**Alterações:**
1. `findById()` — adicionado eager load de `updater:id,name` além do `creator`:
   ```php
   return Rat::with(['creator:id,name', 'updater:id,name', 'orgaoEmissor'])->find($id);
   ```
2. Adicionado novo método `getLatestSequence(int $year): int` — busca o maior número de sequência no protocolo do ano, com `lockForUpdate()` para uso seguro em transações:
   ```php
   public function getLatestSequence(int $year): int
   {
       $latest = Rat::where('protocolo', 'like', "RAT-{$year}-%")
           ->lockForUpdate()
           ->orderByDesc('protocolo')
           ->value('protocolo');
       if (!$latest) return 0;
       return (int) substr($latest, strrpos($latest, '-') + 1);
   }
   ```
3. `update()` — corrigido comentário: `'annexos' é gerenciado exclusivamente pelo RatAttachmentService`

---

### 4.2 `RatRepositoryInterface.php`
**Caminho:** `app/Modules/Rat/Domain/Repositories/RatRepositoryInterface.php`

**Alteração:** Adicionado contrato do novo método:
```php
public function getLatestSequence(int $year): int;
```

---

### 4.3 `RatWriteService.php`
**Caminho:** `app/Modules/Rat/Services/RatWriteService.php`

**Alterações principais:**
- Injetado `RatProtocoloService` no construtor
- `create()` agora usa `DB::transaction()` para atomicidade + `RatProtocoloService::generate()` para protocolo seguro
- **Removido** `random_int(1, 99999)` — não gerava protocolo único nem rastreável
- Adicionado método `finalize(string $id): Rat` com guard clauses (abort 404/422)
- Adicionado helper privado `buildInitialData(string $protocolo): array`

**4 métodos públicos finais:** `create()` · `update()` · `saveDraft()` · `finalize()`

---

### 4.4 `RatController.php`
**Caminho:** `app/Modules/Rat/Controllers/RatController.php`

**Alterações:**
- Removido método `finalize()` — movido para `RatFinalizeController`
- Removido `use App\Modules\Rat\Enums\Status` (não mais necessário)
- Atualizado DocBlock para documentar 5 métodos públicos: `index · create · store · show · destroy`

---

### 4.5 `RatAttachmentController.php`
**Caminho:** `app/Modules/Rat/Controllers/RatAttachmentController.php`

**Antes:** ~80 linhas com toda lógica de upload embutida (validação MIME, UUID, Storage, montagem de metadados)

**Depois:** ~45 linhas — apenas recebe HTTP e delega ao `RatAttachmentService`:
```php
public function store(Request $request, string $id): JsonResponse
{
    $request->validate([
        'file' => [
            'required', 'file',
            'max:' . $this->attachmentService->getMaxKb(),
            'mimetypes:' . implode(',', $this->attachmentService->getAllowedMimes()),
        ],
    ]);
    $rat   = Rat::findOrFail($id);
    $anexo = $this->attachmentService->store($rat, $request->file('file'));
    return response()->json($anexo, 201);
}
```

---

### 4.6 `Application/Services/RatService.php`
**Caminho:** `app/Modules/Rat/Application/Services/RatService.php`

**Alterações:**
- Removidos métodos `update()`, `saveDraft()` e `finalize()` — controllers chamam `RatWriteService` diretamente, evitando intermediário desnecessário
- Removido `use App\Modules\Rat\Enums\Status`
- **5 métodos públicos finais:** `createNew()` · `getIndexData()` · `findById()` · `delete()` · `export()`

---

### 4.7 `UpdateRatRequest.php`
**Caminho:** `app/Modules/Rat/Http/Requests/UpdateRatRequest.php`

**Antes:** Validação apenas no nível do array raiz (ex.: `'comunicacao' => ['nullable', 'array']`)

**Depois:** Validação granular com tipos e regras corretas para cada campo aninhado:

| Campo | Regra |
|---|---|
| `comunicacao.tipo_solicitacao` | `Rule::in(['telefone','radio','pessoal','sistema','email','outro'])` |
| `comunicacao.telefone_contato` | `max:20` |
| `local.uf` | `size:2` |
| `local.pais_id` | `integer` |
| `endereco.cep` | `max:10` |
| `endereco.tipo_localizacao` | `Rule::in(['urbana','rural','rodovia','estrada','mata','montanha','rio','lago','outros'])` |
| `endereco.latitude` | `numeric`, `between:-90,90` |
| `endereco.longitude` | `numeric`, `between:-180,180` |

Adicionado `use Illuminate\Validation\Rule`.

---

### 4.8 `RatResource.php`
**Caminho:** `app/Modules/Rat/Http/Resources/RatResource.php`

**Campos adicionados:**
```php
'status_label'   => Status::tryFrom($this->status)?->getLabel() ?? $this->status,
'atualizado_por' => $this->updater?->name,
```

---

### 4.9 `RatListResource.php`
**Caminho:** `app/Modules/Rat/Http/Resources/RatListResource.php`

**Campos adicionados:**
```php
'status_label' => Status::tryFrom($this->status)?->getLabel() ?? $this->status,
'data_fato'    => $this->dados_gerais['data_fato'] ?? null,
```

---

### 4.10 `Rat.php` (Model)
**Caminho:** `app/Modules/Rat/Models/Rat.php`

**Adicionado relacionamento:**
```php
public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(\App\Models\User::class, 'updated_by');
}
```

---

### 4.11 `RatFilterService.php`
**Caminho:** `app/Modules/Rat/Services/RatFilterService.php`

**Antes (MySQL-only, não portável):**
```php
$query->whereRaw(
    "JSON_UNQUOTE(JSON_EXTRACT(`local`, '$.municipio')) LIKE ?",
    ["%{$municipio}%"]
);
```

**Depois (Laravel portable, funciona em MySQL e SQLite):**
```php
$query->where('local->municipio', 'like', "%{$municipio}%");
```

---

### 4.12 `RatServiceProvider.php`
**Caminho:** `app/Modules/Rat/RatServiceProvider.php`

**Adicionados singletons** para os novos serviços:
```php
$this->app->singleton(RatProtocoloService::class);
$this->app->singleton(RatAttachmentService::class);
```

---

### 4.13 `routes/modules/rat.php`
**Alteração:** Rota `finalize` atualizada para usar o controller invokable:
```php
// Antes:
Route::patch('/{id}/finalize', [RatController::class, 'finalize'])

// Depois:
Route::patch('/{id}/finalize', RatFinalizeController::class)
```
Adicionado `use App\Modules\Rat\Controllers\RatFinalizeController`.

---

## 5. Arquivo Removido

| Arquivo | Motivo |
|---|---|
| `app/Modules/Rat/Services/RatService.php` | Service legado que usava `int $id` e `extends BaseService` — conflitava com a arquitetura atual que usa UUID strings. Todos os casos de uso foram redistribuídos nas classes corretas. |

---

## 6. Modificações Frontend (Vue 3)

### 6.1 Problema: Botões Salvar não funcionavam
**Arquivo:** `resources/js/Components/Rat/RatForm.vue`

**Causa:** Os botões não tinham `type="button"`, causando submit do formulário HTML ao invés de emitir o evento Vue. Além disso, `$emit('save', localData.value)` dentro do template Vue emitia `undefined` por usar `.value` de uma ref diretamente no template (o compilador Vue 3 faz auto-unwrap).

**Correções aplicadas:**
- Adicionado `type="button"` em todos os 3 botões de ação (Cancelar, Salvar Rascunho, Finalizar RAT)
- Corrigido emit para usar `localData.value` corretamente no contexto do `<script setup>`

---

### 6.2 Problema: RatForm não hidratava dados do banco ao abrir edição
**Arquivo:** `resources/js/Components/Rat/RatForm.vue`

**Causa:** `localData` estava sendo inicializado com `props.rat.dados_gerais` mas o watch de atualização não incluía `comunicacao`, `local` e `endereco`.

**Correção:** Watch atualiza todos os campos de `localData` quando `props.rat` muda.

---

### 6.3 Problema: Sub-abas (Recursos, Envolvidos, Vistoria, Histórico) sem botão Salvar
**Arquivos:** `RatResources.vue`, `RatInvolved.vue`, `RatInspection.vue`, `RatHistory.vue`

**Correção:** Adicionado footer `rat-actions-footer` com botão "Salvar [Nome da Aba]" em cada componente, emitindo evento `'save'` capturado por `Rat.vue`.

**No `Rat.vue`:**
```js
function handleSaveFromSubTab() {
  saveRat({
    dadosGerais: props.rat?.dados_gerais ?? {},
    comunicacao: props.rat?.comunicacao ?? {},
    local:       props.rat?.local ?? {},
    endereco:    props.rat?.endereco ?? {},
  });
}
```

---

### 6.4 Problema: Aba Anexos sem botão Salvar (correção desta sessão)
**Arquivo:** `resources/js/Components/Rat/RatAttachments.vue`

**Causa:** Todas as outras abas (Recursos, Envolvidos, Vistoria, Histórico) já tinham o footer de ações com botão "Salvar". A aba de Anexos foi esquecida.

**Correção:**
1. Adicionado footer `rat-actions-footer` com botão "Salvar Anexos" (desabilitado durante upload em progresso)
2. Adicionado `'save'` ao array `defineEmits`
3. Em `Rat.vue`, adicionado `@save="handleSaveFromSubTab"` no componente `<RatAttachments>`

```html
<!-- Footer adicionado em RatAttachments.vue -->
<div class="rat-actions-footer mt-4">
  <button type="button" @click="$emit('save')" :disabled="uploading">
    Salvar Anexos
  </button>
</div>
```

---

### 6.5 `RatHistory.vue` — correção de estado inicial
**Arquivo:** `resources/js/Components/Rat/RatHistory.vue`

**Causa:** O componente inicializava `historyEvents` como objeto `{}` ao invés de array `[]`, causando erro ao tentar fazer `.push()` ou `.length`.

**Correção:**
```js
const historyEvents = ref(Array.isArray(props.events) ? [...props.events] : []);
```

---


### 6.6 `useRat.js` — Composable central
**Arquivo:** `resources/js/Composables/useRat.js`

**Criado do zero** para centralizar o estado reativo do módulo RAT:
- Gerencia: `rat`, `recursos`, `envolvidos`, `vistoria`, `historyEvents`, `anexos`, `tabs`
- `saveRat()` — envia `PUT /rat/{id}` com todos os dados do formulário + sub-abas
- `saveDraft()` — envia `PATCH /rat/{id}/draft`
- `cancelRat()` — redireciona para `rat.index`

---

## 7. Fluxo Completo de Dados: Frontend → Backend → Banco

```
[Usuário preenche formulário]
        ↓
[Vue 3 - RatForm.vue]
  localData = { dadosGerais, comunicacao, local, endereco }
        ↓ emit('save', localData.value)
[Vue 3 - Rat.vue]
  handleSave(data) → saveRat(data)
        ↓
[Composable - useRat.js]
  saveRat() → router.put(route('rat.update', id), data)
        ↓ Inertia PUT /rat/{id}
[Backend - routes/modules/rat.php]
  Route::put('/{id}', [RatWriteController::class, 'update'])
        ↓
[RatWriteController::update()]
  $this->writeService->update($id, $request->validated())
        ↓
[RatWriteService::update()]
  $this->repository->update($id, array_merge($data, ['status' => 'em_andamento']))
        ↓
[EloquentRatRepository::update()]
  $rat->update([
    'dados_gerais' => $data['dadosGerais'],
    'comunicacao'  => $data['comunicacao'],
    'local'        => $data['local'],
    'endereco'     => $data['endereco'],
    'recursos'     => $data['recursos'],
    'envolvidos'   => $data['envolvidos'],
    'vistoria'     => $data['vistoria'],
    'historico'    => $data['historico'],
    'status'       => 'em_andamento',
    'updated_by'   => auth()->id(),
  ])
        ↓
[MySQL - tabela `rats`]
  Colunas JSON atualizadas: dados_gerais, comunicacao, local, endereco,
  recursos, envolvidos, vistoria, historico, status, updated_by
        ↓
[Resposta] redirect()->route('rat.edit', $id) com flash 'success'
```

---

## 8. Fluxo de Upload de Anexo

```
[Usuário seleciona arquivo]
        ↓
[RatAttachmentsSection.vue]
  processFiles() → emit('upload-file', { file, tempId })
        ↓ otimismo: anexo temporário exibido imediatamente
[RatAttachments.vue]
  handleUploadFile() → axios.post('/rat/{id}/attachments', FormData)
        ↓
[RatAttachmentController::store()]
  → RatAttachmentService::store($rat, $file)
        ↓
[RatAttachmentService::store()]
  persist() → $file->storeAs("rat/{id}", "{uuid}.{ext}", 'public')
  buildMetadata() → { id, nome, tamanho, tipo, url, path, data_upload }
  $rat->update(['anexos' => [...$existing, $anexo]])
        ↓
[Disco] storage/app/public/rat/{ratId}/{uuid}.ext
[Banco] rats.anexos JSON array atualizado
        ↓
[Resposta HTTP 201] { id, nome, tamanho, tipo, url, path, data_upload }
        ↓
[RatAttachments.vue] substitui entrada temporária pelo dado real do servidor
```

---

## 9. Estrutura do Banco de Dados (Tabela `rats`)

```sql
CREATE TABLE rats (
  id              CHAR(36) PRIMARY KEY,        -- UUID
  protocolo       VARCHAR(20) UNIQUE,           -- RAT-AAAA-NNNNN
  status          ENUM('rascunho','em_andamento','finalizado'),
  tem_vistoria    TINYINT(1) DEFAULT 0,
  dados_gerais    JSON,    -- data_fato, data_inicio/termino_atividade, nat_cobrade_id, etc.
  local           JSON,    -- municipio, uf, pais_id, municipio_id
  endereco        JSON,    -- cep, logradouro, numero, bairro, lat/lng, tipo_localizacao
  comunicacao     JSON,    -- tipo_solicitacao, data_comunicacao, telefone, nome_solicitante
  recursos        JSON,    -- array de viaturas/equipamentos empregados
  envolvidos      JSON,    -- array de pessoas envolvidas
  vistoria        JSON,    -- dados de vistoria imobiliária
  historico       JSON,    -- array de registros de histórico/observações
  anexos          JSON,    -- array de metadados de arquivos
  orgao_emissor_id INT FK,
  created_by      BIGINT FK → users.id,
  updated_by      BIGINT FK → users.id,
  created_at      TIMESTAMP,
  updated_at      TIMESTAMP
);
```

---

## 10. Rotas do Módulo RAT

| Método | URI | Nome | Controller | Permissão |
|---|---|---|---|---|
| GET | `/rat` | `rat.index` | `RatController@index` | `rat.protocolos.view` |
| GET | `/rat/create` | `rat.create` | `RatController@create` | `rat.protocolos.create` |
| POST | `/rat` | `rat.store` | `RatController@store` | `rat.protocolos.create` |
| GET | `/rat/{id}/edit` | `rat.edit` | `RatController@show` | `rat.protocolos.edit` |
| GET | `/rat/{id}` | `rat.show` | `RatController@show` | `rat.protocolos.view` |
| PUT | `/rat/{id}` | `rat.update` | `RatWriteController@update` | `rat.protocolos.edit` |
| PATCH | `/rat/{id}/draft` | `rat.draft` | `RatWriteController@draft` | `rat.protocolos.edit` |
| PATCH | `/rat/{id}/finalize` | `rat.finalize` | `RatFinalizeController` | `rat.protocolos.finalize` |
| DELETE | `/rat/{id}` | `rat.destroy` | `RatController@destroy` | `rat.protocolos.delete` |
| GET | `/rat/{id}/json` | `rat.show.json` | `RatDataController@showJson` | `rat.protocolos.view` |
| GET | `/rat/export` | `rat.export` | `RatDataController@export` | `rat.protocolos.export` |
| POST | `/rat/{id}/attachments` | `rat.attachments.store` | `RatAttachmentController@store` | `rat.protocolos.edit` |
| DELETE | `/rat/{id}/attachments/{anexoId}` | `rat.attachments.destroy` | `RatAttachmentController@destroy` | `rat.protocolos.edit` |

---

## 11. Princípios SOLID Aplicados

### S — Single Responsibility Principle
| Classe | Responsabilidade única |
|---|---|
| `RatFinalizeController` | Receber PATCH /finalize e delegar |
| `RatProtocoloService` | Gerar protocolo sequencial único |
| `RatAttachmentService` | Gerenciar arquivos físicos e metadados |
| `RatWriteService` | Escrever/atualizar dados do RAT |
| `RatFilterService` | Construir filtros de query |
| `EloquentRatRepository` | Acesso a dados via Eloquent |

### O — Open/Closed Principle
- Adicionar novo tipo de export não requer alteração no `RatService`
- Adicionar nova validação de anexo não requer alteração no controller

### L — Liskov Substitution Principle
- `EloquentRatRepository` pode ser substituída por qualquer implementação de `RatRepositoryInterface` (ex.: para testes com repositório em memória)

### I — Interface Segregation Principle
- `RatRepositoryInterface` define apenas os métodos necessários para o módulo RAT

### D — Dependency Inversion Principle  
- Controllers dependem de Services (abstrações), não de repositórios diretamente
- Services dependem de `RatRepositoryInterface`, não de `EloquentRatRepository`

---

## 12. Verificações Realizadas

```bash
# Todos os services e controllers resolvem corretamente no container
php artisan tinker --execute="
  App::make(App\Modules\Rat\Controllers\RatFinalizeController::class);
  App::make(App\Modules\Rat\Services\RatProtocoloService::class);
  App::make(App\Modules\Rat\Services\RatAttachmentService::class);
  echo 'ALL_RESOLVED';
"
# Saída: ALL_RESOLVED ✅

# Migração executada com sucesso
php artisan migrate
# 2026_03_09_200001_drop_rat_legacy_tables ... 662.74ms DONE ✅

# Build do frontend concluído
npm run build
# ✓ built in 29.58s ✅

# Todas as rotas registradas sem erro
php artisan route:list --path=rat
# 23 rotas listadas ✅

# Octane recarregado
php artisan octane:reload
# INFO Reloading workers... ✅
```

---

## 13. Resumo Quantitativo das Alterações

| Categoria | Quantidade |
|---|---|
| Arquivos PHP novos criados | 3 |
| Arquivos PHP modificados | 12 |
| Arquivos PHP deletados | 1 |
| Migração criada e executada | 1 |
| Tabelas legadas removidas do banco | 14 |
| Componentes Vue modificados | 12 |
| Composables Vue criados/modificados | 1 |
| Rotas alteradas | 1 |
| Build frontend | ✅ 29.58s |

---

*Documentação elaborada por Barbara Costa — 09/03/2026*
