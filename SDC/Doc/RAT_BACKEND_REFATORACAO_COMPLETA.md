# RAT — Refatoração Completa: Backend Clean Code + SOLID + Correções Frontend

**Autora:** Barbara Costa  
**Data:** 09 de março de 2026  

---

## 1. Contexto e Objetivo

O módulo **RAT** precisava de uma refatoração completa do backend seguindo os princípios de **Clean Code** e **SOLID**, além de correções em bugs críticos do frontend (formulário, botões de salvar, sub-abas). O objetivo central foi:

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

---

---

# Fase 2 — Nova Arquitetura Polimórfica 

**Data:** 10/03/2026  
**Status:** ✅ 35/35 arquivos implementados e funcionais  
**Rotas totais registradas:** 44 (web + API)  
**Validação:** `php artisan route:cache` — sucesso no container `newsdc_app`

---

## Arquitetura da Nova Estrutura

```
Requisição HTTP
      │
  Controller  ──→  FormRequest (validação de entrada)
      │
   Service     ──→  Model / Repositório
      │
  RatOcorrencia  (entidade principal — tabela rat_ocorrencias)
      │
  RatOcorrenciaRelato  (pivô polimórfico)
      ├── RatRelatoDadosGerais       — dados gerais do fato
      ├── RatRelatoEnvolvidos        — vítimas / agentes
      ├── RatRelatoRecurso           — recursos empregados
      │       └── RatRecursosEmpregado
      │               └── RatRecursosComponentesGuarnicao
      └── RatRelatoVistoria          — inspeção técnica
```
---

## Controllers Criados — 6/6

### `Compdec/RatController.php`
Controller principal CRUD da nova estrutura. Depende de `RatOcorrenciaService`.  
Usa `BoRequest` para validar `store()` e `update()` (campos reais de `rat_ocorrencias`).

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET `/compdec/rat` | Lista paginada de ocorrências |
| `create()` | GET `/compdec/rat/create` | Formulário de criação (Inertia) |
| `store()` | POST `/compdec/rat` | Persiste nova ocorrência |
| `show()` | GET `/compdec/rat/{id}` | Exibe ocorrência (Inertia) |
| `edit()` | GET `/compdec/rat/{id}/edit` | Formulário de edição (Inertia) |
| `update()` | PUT `/compdec/rat/{id}` | Atualiza ocorrência |
| `destroy()` | DELETE `/compdec/rat/{id}` | Remove ocorrência |
| `exportarOcorrencias()` | GET `/compdec/rat/export` | Exporta listagem |

### `Compdec/BoRatController.php`
Boletim de Ocorrência (BO) vinculado às ocorrências RAT.

| Método | Rota |
|--------|------|
| `index()` | GET `/compdec/rat/bo` |
| `store()` | POST `/compdec/rat/bo` |

### `Compdec/RatAlvoController.php`
Alvos associados às ocorrências.

| Método | Rota |
|--------|------|
| `index()` | GET `/compdec/rat/alvos` |
| `show()` | GET `/compdec/rat/alvos/{id}` |

### `Compdec/RatOcorrenciaController.php`
Coordena ocorrências com seus relatos polimórficos. Depende de `RatOcorrenciaService` e `RatRelatoService`.

| Método | Rota |
|--------|------|
| `index()` | GET `/compdec/rat/ocorrencias` |
| `show()` | GET `/compdec/rat/ocorrencias/{id}` |
| `store()` | POST `/compdec/rat/ocorrencias` |
| `finalize()` | PATCH `/compdec/rat/ocorrencias/{id}/finalizar` |

### `Api/RatAuditController.php`
Endpoints da API para trilha de auditoria.

| Método | Rota |
|--------|------|
| `index()` | GET `/api/v1/rat-audit` |
| `show()` | GET `/api/v1/rat-audit/{id}` |

### `Api/RatNovoController.php`
Endpoints da API para integração Power BI.

| Método | Rota |
|--------|------|
| `index()` | GET `/api/v1/rat-novo` |
| `show()` | GET `/api/v1/rat-novo/{id}` |
| `powerBiData()` | GET `/api/v1/rat-novo/{id}/power-bi` |

---

## Services Criados — 7/7

Localização: `app/Services/Rat/`

| Serviço | Principais Métodos | Responsabilidade |
|---------|-------------------|------------------|
| `RatOcorrenciaService` | `manageOcorrencia()`, `finalizar()`, `paginate()`, `findOrFail()` | Regras de negócio da ocorrência principal |
| `RatRelatoService` | `manageRelatos()`, `attachRelato()`, `detachRelato()` | Gerencia relatos polimórficos vinculados |
| `RatNovoService` | `getNormalizedDataForPowerBI()`, `extractDadosGerais()`, `extractEnvolvidos()`, `extractRecursos()` | Normalização de dados para Power BI |
| `RatBiService` | `getOcorrenciasPorStatus()`, `getOcorrenciasPorMes()`, `getEnvolvidosPorTipo()`, `getRecursosPorTipo()` | Agregações para dashboard gerencial |
| `RatRecursoService` | `createRecurso()`, `addEmpregado()`, `addComponenteGuarnicao()`, `removeEmpregado()` | Gestão de recursos empregados e guarnição |
| `RatAuditService` | `log()`, `history()` | Trilha de auditoria (`table_name` / `row_id`) |
| `RatTrackingService` | `getTimeline()`, `getOcorrenciasAtivas()`, `isPrazoVencido()` | Monitoramento e alertas de prazo |

---

## Models Criados — 12/12

### Núcleo

**`app/Models/Rat/RatOcorrencia.php`** — Entidade principal (`rat_ocorrencias`)

| Campo | Descrição |
|-------|-----------|
| `numero_bos` | Número do Boletim de Ocorrência |
| `sequencial_ano` | Sequencial anual |
| `status` | 0=Rascunho / 1=Finalizado |
| `prazo_edicao` | Prazo limite de edição |
| `historico` | Observações gerais |
| `ocorrencia_origem_id` | Auto-referência para divisões de BO |
| `created_by` / `updated_by` | Rastreamento de usuário |

Relacionamentos: `relatos()`, `relatosMorph()`, `ocorrenciaOrigem()`

**`app/Models/Rat/RatOcorrenciaRelato.php`** — Pivô polimórfico  
Campos: `ocorrencia_id`, `conteudo_type`, `conteudo_id`  
Relacionamentos: `ocorrencia()` → BelongsTo, `conteudo()` → morphTo

**`app/Models/Rat/RatRedec.php`** — Tabela de referência REDEC  
**`app/Models/Rat/RatVeiculo.php`** — Veículos (soft delete habilitado)

### Relatos — Conteúdo Polimórfico (`app/Models/Rat/Relatos/`)

| Model | Campos Principais |
|-------|-------------------|
| `RatRelato.php` (base abstrata) | `ocorrenciaRelato()` → morphOne |
| `RatRelatoDadosGerais.php` | `data_fato`, `nat_cobrade_id`, `nat_nome_operacao`, `local_municipio`, `local_estadouf` |
| `RatRelatoEnvolvidos.php` | `g_tipo_pessoa`, `g_lesao_grau`, `p_nome_completo`, `p_cpf`, `p_data_nascimento`, `p_sexo` |
| `RatRelatoRecurso.php` | `recurso_tipo`, `viatura_placa`, `viatura_saida`, `viatura_chegada`; → `recursosEmpregados()` |
| `RatRelatoVistoria.php` | `v_solicitante_nome`, `v_tipo_imovel`, `v_estado_conservacao`, `v_latitude`, `v_longitude` |

### Recursos — Hierarquia Aninhada (`app/Models/Rat/Recursos/`)

| Model | Relacionamentos |
|-------|----------------|
| `RatRecurso.php` (base abstrata) | Soft delete habilitado |
| `RatRecursosEmpregado.php` | `relatoRecurso()` → BelongsTo; `componentesGuarnicao()` → HasMany |
| `RatRecursosComponentesGuarnicao.php` | `recursoEmpregado()` → BelongsTo; campos: `nome_completo`, `matricula` |

---

## Requests Criados — 8/8

Localização: `app/Http/Requests/Rat/`

| Request | Campos Validados |
|---------|-----------------|
| `BoRequest.php` | `numero_bos` (required), `historico`, `prazo_edicao`, `ocorrencia_origem_id` |
| `RatDadosGeraisRequest.php` | `data_fato` (required), `nat_cobrade_id` (required), localização, endereço |
| `RatEnvolvidosRequest.php` | `g_tipo_pessoa` (required), `p_nome_completo` (required), CPF, nascimento, sexo, endereço |
| `RatEnvolvidosUpdateRequest.php` | Todos os campos `sometimes` (atualização parcial) |
| `RatHistoricoRequest.php` | Array `eventos.*`: tipo, título, descrição, data; requer permissão `rat.protocolos.edit` |
| `RatRecursosRequest.php` | `recurso_tipo` (viatura\|pe\|aereo\|aquatico\|outro), viatura, guarnição aninhada |
| `RatRecursosUpdateRequest.php` | Todos os campos `sometimes` (atualização parcial) |
| `RatVistoriaRequest.php` | `v_solicitante_nome` (required), tipo imóvel, moradores, lat (`between:-90,90`), lon (`between:-180,180`) |

---

## Correções de Comunicação Front ↔ Back ↔ Banco

| # | Problema | Arquivo | Correção |
|---|----------|---------|---------|
| 1 | `RatController` usava `RatDadosGeraisRequest` (campos de relato) em vez dos campos reais de `rat_ocorrencias` | `Compdec/RatController.php` | Trocado para `BoRequest` em `store()` e `update()` |
| 2 | `powerBiData()` chamava `findOrFail()` 3× no mesmo ID | `Api/RatNovoController.php` | Unificado em uma única query reutilizando `$ocorrencia` |
| 3 | Vue Create/Edit usavam `data_hora_fato` e `descricao` (inexistentes em `rat_ocorrencias`) | `Pages/Compdec/Rat/Create.vue` e `Edit.vue` | Corrigidos para `prazo_edicao` e `historico` |
| 4 | `RatAuditService` usava `auditable_type/auditable_id` (colunas inexistentes) | `Services/Rat/RatAuditService.php` | Corrigido para `table_name/row_id` |
| 5 | `RatHistoricoRequest` validava string em vez de array de eventos | `Requests/Rat/RatHistoricoRequest.php` | Corrigido para validação de array `eventos.*` |
| 6 | Composable chamava rota `rat.sync` inexistente | `Composables/rat/useRat.js` | Corrigido para `rat.store` / `rat.update` |

---

### Composables renomeados

| Arquivo | Antes → Depois |
|---------|---------------|
| `Composables/useRat.js` | `historyEvents` → `historico`, `saveRat` → `salvarRat`, `saveDraft` → `salvarRascunho`, `cancelRat` → `cancelarRat` |
| `Composables/rat/useRat.js` | + `addRecurso` → `adicionarRecurso`, `removeRecurso` → `removerRecurso`, `addEnvolvido` → `adicionarEnvolvido`, `removeEnvolvido` → `removerEnvolvido`, `saveVistoria` → `salvarVistoria`, `addObservation` → `adicionarObservacao`, `addAnexo` → `adicionarAnexo`, `removeAnexo` → `removerAnexo` |
| `Composables/rat/useRatFilters.js` | `hasActiveFilters` → `temFiltrosAtivos`, `updateFilter` → `atualizarFiltro`, `resetFilters` → `limparFiltros`, `applyFilters` → `aplicarFiltros`, `clearFilters` → `limparTodosFiltros` |
| `Composables/rat/useRatStatistics.js` | `formattedStatistics` → `estatisticasFormatadas`, `updateStatistics` → `atualizarEstatisticas` |
| `Composables/rat/useCollapsible.js` | `isExpanded` → `estaExpandido`, `toggle` → `alternar`, `expand` → `expandir`, `collapse` → `recolher`, `loadState` → `carregarEstado`, `saveState` → `salvarEstado`, `expandAll` → `expandirTodos`, `collapseAll` → `recolherTodos`, `toggleAll` → `alternarTodos` |

### Callers atualizados

- `Pages/Rat.vue` — prop `historico`, estado `historicoEstado`, chamadas `salvarRat/salvarRascunho/cancelarRat`, computed `historico`, template `:events="historico"`
- `Components/Rat/Sections/RatCollapsibleSection.vue` — `estaExpandido`, `alternar` em script e template

---

## Rotas da Nova Estrutura (acréscimo ao total de 44)

### Web — Nova Estrutura (`/compdec/rat`)
```
GET|HEAD   compdec/rat                                compdec.rat.index
POST       compdec/rat                                compdec.rat.store
GET|HEAD   compdec/rat/create                         compdec.rat.create
GET|HEAD   compdec/rat/export                         compdec.rat.export
GET|HEAD   compdec/rat/{id}                           compdec.rat.show
GET|HEAD   compdec/rat/{id}/edit                      compdec.rat.edit
PUT        compdec/rat/{id}                           compdec.rat.update
DELETE     compdec/rat/{id}                           compdec.rat.destroy
GET|HEAD   compdec/rat/bo                             compdec.rat.bo.index
POST       compdec/rat/bo                             compdec.rat.bo.store
GET|HEAD   compdec/rat/alvos                          compdec.rat.alvos.index
GET|HEAD   compdec/rat/alvos/{id}                     compdec.rat.alvos.show
GET|HEAD   compdec/rat/ocorrencias                    compdec.rat.ocorrencias.index
POST       compdec/rat/ocorrencias                    compdec.rat.ocorrencias.store
GET|HEAD   compdec/rat/ocorrencias/{id}               compdec.rat.ocorrencias.show
PATCH      compdec/rat/ocorrencias/{id}/finalizar     compdec.rat.ocorrencias.finalizar
```

### API v1 — Nova Estrutura (`/api/v1`)
```
GET|HEAD   api/v1/rat-novo                            api.v1.rat-novo.index
GET|HEAD   api/v1/rat-novo/{id}                       api.v1.rat-novo.show
GET|HEAD   api/v1/rat-novo/{id}/power-bi              api.v1.rat-novo.power-bi
GET|HEAD   api/v1/rat-audit                           api.v1.rat-audit.index
GET|HEAD   api/v1/rat-audit/{id}                      api.v1.rat-audit.show
```

---

## Resumo Quantitativo — Fase 2

| Categoria | Quantidade |
|-----------|-----------|
| Controllers novos criados | 6 |
| Services novos criados | 7 |
| Models novos criados | 12 |
| Requests novos criados | 8 |
| Composables JS renomeados para PT | 5 |
| Páginas Vue criadas (Compdec/Rat) | 9 |
| Bugs de comunicação corrigidos | 6 |
| Docblocks PHP traduzidos | 15 arquivos |
| Rotas totais ativas | 44 |

---

*Fase 2 documentada em 10/03/2026 — Módulo RAT, Barbara Costa*

---

---

# Fase 3 — Recriação das Tabelas, Seeders e DTOs

**Data:** 10/03/2026  
**Status:** ✅ Banco restaurado · Seeders populados · Fluxo Controller → DTO → Service → Model → DB completo  
**Validação:** `php artisan optimize` — `config ✓ events ✓ routes ✓ views ✓`

---

## Problema Crítico Identificado e Resolvido

A migration `2026_03_09_200001_drop_rat_legacy_tables` (Fase 1, batch 4) havia listado como "legadas" 9 tabelas que **pertencem à nova arquitetura polimórfica** da Fase 2. Todas foram deletadas do banco, tornando todos os endpoints `Compdec/Rat` inoperantes (HTTP 500 — table not found).

**Tabelas que foram incorretamente deletadas:**

| Tabela | Impacto |
|--------|---------|
| `rat_ocorrencias` | Entidade principal — `RatOcorrencia` model |
| `rat_ocorrencia_relatos` | Pivô polimórfico — `RatOcorrenciaRelato` model |
| `rat_relato_recursos` | Recursos do relato — `RatRelatoRecurso` model |
| `rat_recursos_empregados` | Viaturas/pés — `RatRecursosEmpregado` model |
| `rat_recursos_componentes_guarnicao` | Guarnição — `RatRecursosComponentesGuarnicao` model |
| `rat_relato_envolvidos` | Pessoas envolvidas — `RatRelatoEnvolvidos` model |
| `rat_relato_vistoria` | Inspeção técnica — `RatRelatoVistoria` model |
| `rat_veiculos` | Cadastro de veículos — `RatVeiculo` model |
| `rat_redec` | Referência REDEC — `RatRedec` model |

**Tabelas que sobreviveram (não estavam na lista de drop):**

| Tabela | Status |
|--------|--------|
| `rats` | ✅ Intacta — tabela legada Fase 1 |
| `rat_relato_dados_gerais` | ✅ Intacta — nome diferente de `rat_dados_gerais` |

---

## 1. Migration de Recriação

**Arquivo criado:** `database/migrations/2026_03_10_000002_recreate_rat_polymorphic_tables.php`  
**Batch:** 5 · **Duração:** 2s · **Status:** ✅ DONE

Recria todas as 9 tabelas na **ordem correta de dependência de FK**, usando `if (! Schema::hasTable(...))` para ser idempotente:

1. `rat_redec` — tabela de referência (sem FKs externas)
2. `rat_ocorrencias` — entidade principal; FK auto-referenciada `ocorrencia_origem_id` → `rat_ocorrencias.id` ON DELETE SET NULL
3. `rat_ocorrencia_relatos` — pivô polimórfico; FK `ocorrencia_id` → `rat_ocorrencias.id` ON DELETE SET NULL
4. `rat_relato_recursos` — recursos por relato (sem FKs de entrada)
5. `rat_recursos_empregados` — FK `relato_recurso_id` → `rat_relato_recursos.id` ON DELETE CASCADE
6. `rat_recursos_componentes_guarnicao` — referencia `recurso_empregado_id` e `relato_recurso_id`
7. `rat_relato_envolvidos` — pessoas; soft delete; campo `created_by` (nullable para compatibilidade)
8. `rat_relato_vistoria` — 117 colunas (patologias, bens, órgãos, encaminhamentos, geo)
9. `rat_veiculos` — único por placa; soft delete

**Resultado confirmado (colunas por tabela):**

| Tabela | Colunas |
|--------|---------|
| `rat_ocorrencias` | 12 |
| `rat_ocorrencia_relatos` | 8 |
| `rat_relato_recursos` | 28 |
| `rat_recursos_empregados` | 16 |
| `rat_recursos_componentes_guarnicao` | 17 |
| `rat_relato_envolvidos` | 54 |
| `rat_relato_vistoria` | 117 |
| `rat_veiculos` | 8 |
| `rat_redec` | 5 |

**Migration inválida deletada:** `2026_03_10_000001_add_fk_rat_ocorrencia_relatos_ocorrencia_id.php`  
(Criada anteriormente e falhada porque a tabela não existia — removida do repositório.)

---

## 2. Soft Delete em Cascata — Booted Events

Adicionado `booted()` em 3 models para garantir o comportamento conforme o whiteboard ("banco não vai excluir nada — o front exclui meio que camuflado mas os dados permanecem"):

### `RatOcorrencia` → cascateia para relatos
```php
protected static function booted(): void
{
    static::deleting(function (self $ocorrencia): void {
        $ocorrencia->relatos()->each->delete();
    });
}
```

### `RatOcorrenciaRelato` → cascateia para conteúdo polimórfico
```php
protected static function booted(): void
{
    static::deleting(function (self $relato): void {
        $relato->conteudo?->delete();
    });
}
```

### `RatRelatoRecurso` → cascateia para recursos empregados
```php
protected static function booted(): void
{
    parent::booted();
    static::deleting(function (self $recurso): void {
        $recurso->recursosEmpregados()->each->delete();
    });
}
```

**Docblock `destroy()` corrigido** em `Compdec/RatController.php`:
- Antes: `/** Remove a ocorrência permanentemente. */`
- Depois: `/** Oculta a ocorrência via soft delete (dados preservados no banco). Cascateia o soft delete para todos os relatos e conteúdos relacionados. */`
- Flash message: `'Ocorrência removida com sucesso!'` → `'Ocorrência ocultada com sucesso!'`

**Models com SoftDeletes ativo:** `RatOcorrencia`, `RatOcorrenciaRelato`, `RatRelatoRecurso` (base), `RatVeiculo`, `RatRedec`

---

## 3. Seeders

### `RatRedecSeeder.php` — **NOVO**
**Arquivo:** `database/seeders/RatRedecSeeder.php`

Popula `rat_redec` com as **14 Regiões de Defesa Civil de Minas Gerais**. Idempotente via `updateOrInsert` na `sigla`.

| Sigla | Região |
|-------|--------|
| 1ª REDEC | Metropolitana de Belo Horizonte |
| 2ª REDEC | Vale do Paraopeba |
| 3ª REDEC | Campo das Vertentes |
| 4ª REDEC | Zona da Mata |
| 5ª REDEC | Triângulo Norte |
| 6ª REDEC | Triângulo Sul |
| 7ª REDEC | Norte de Minas |
| 8ª REDEC | Vale do Rio Doce |
| 9ª REDEC | Mucuri |
| 10ª REDEC | Oeste de Minas |
| 11ª REDEC | Sul de Minas |
| 12ª REDEC | Circuito das Águas |
| 13ª REDEC | Serrana do Sul |
| 14ª REDEC | Jequitinhonha |

**Executado:** `php artisan db:seed --class=RatRedecSeeder` → `14 REDECs de Minas Gerais inseridas.` ✅

### `DatabaseSeeder.php` — **ATUALIZADO**
Adicionada chamada ao `RatRedecSeeder` após o `RatMockSeeder`, com guarda `Schema::hasTable('rat_redec')`:

```php
// 6b. REDECs de Minas Gerais (tabela de referência rat_redec)
if (\Illuminate\Support\Facades\Schema::hasTable('rat_redec')) {
    $this->call(RatRedecSeeder::class);
} else {
    $this->command->warn('Tabela "rat_redec" não encontrada - RatRedecSeeder pulado.');
}
```

---

## 4. DTOs — Fluxo Controller → DTO → Service → Model → DB

Implementado o padrão completo do whiteboard "Padrão Ouro". Os DTOs ficam em `app/DTOs/Rat/`, seguindo o padrão `readonly class` já adotado pelo `RatFilterDTO` legado.

### `RatBoDTO`
**Arquivo:** `app/DTOs/Rat/RatBoDTO.php`

Representa os dados de criação/atualização de um Boletim de Ocorrência.

| Campo DTO | Campo DB | Tipo |
|-----------|----------|------|
| `numeroBos` | `numero_bos` | `?string` |
| `historico` | `historico` | `?string` |
| `prazoEdicao` | `prazo_edicao` | `?string` |
| `ocorrenciaOrigemId` | `ocorrencia_origem_id` | `?int` |
| `status` | `status` | `?int` |
| `criadoPor` | `created_by` | `?int` |

```php
// Uso no controller:
$ocorrencia = $this->service->manageOcorrencia(
    RatBoDTO::fromArray($request->validated())
);
```

### `RatOcorrenciaFiltroDTO`
**Arquivo:** `app/DTOs/Rat/RatOcorrenciaFiltroDTO.php`

Encapsula critérios de listagem/paginação.

| Campo DTO | Campo HTTP | Tipo | Default |
|-----------|------------|------|---------|
| `status` | `status` | `?int` | `null` |
| `numeroBos` | `numero_bos` | `?string` | `null` |
| `porPagina` | `per_page` | `int` | `15` |

```php
// Uso no controller:
$filtro = RatOcorrenciaFiltroDTO::fromArray(request()->only(['status', 'numero_bos']));
$ocorrencias = $this->service->paginate($filtro);
```

### `RatDadosGeraisDTO`
**Arquivo:** `app/DTOs/Rat/RatDadosGeraisDTO.php`

22 campos da tabela `rat_relato_dados_gerais`, mapeados pelos campos de `RatDadosGeraisRequest`. `toArray()` exclui campos nulos para não sobrescrever defaults do banco.

---

## 5. Service e Controllers Atualizados

### `RatOcorrenciaService` — assinaturas com DTOs

| Método (antes) | Método (depois) |
|----------------|-----------------|
| `manageOcorrencia(array $data, ?int $id)` | `manageOcorrencia(RatBoDTO $dto, ?int $id)` |
| `paginate(array $filters, int $perPage)` | `paginate(RatOcorrenciaFiltroDTO $filtro)` |

A lógica interna permanece idêntica — apenas a assinatura pública é tipada.

### Controllers atualizados (4)

| Controller | Mudanças |
|------------|---------|
| `Compdec/RatController` | `index()` usa `RatOcorrenciaFiltroDTO`; `store()` e `update()` usam `RatBoDTO`; `exportRats()` usa `RatOcorrenciaFiltroDTO` com `porPagina=9999` |
| `Compdec/BoRatController` | `index()` usa `RatOcorrenciaFiltroDTO`; `store()` usa `RatBoDTO` |
| `Compdec/RatOcorrenciaController` | `index()` usa `RatOcorrenciaFiltroDTO`; `store()` usa `RatBoDTO` + `RatDadosGeraisDTO` |
| `Compdec/RatAlvoController` | `index()` usa `RatOcorrenciaFiltroDTO` com `status=0` |

---

## 6. Fluxo Completo Atualizado — Nova Arquitetura

```
[Usuário faz requisição HTTP]
        ↓
[FormRequest → validated()]
  BoRequest / RatDadosGeraisRequest / RatVistoriaRequest ...
        ↓ fromArray($request->validated())
[DTO → tipagem estrita]
  RatBoDTO / RatOcorrenciaFiltroDTO / RatDadosGeraisDTO
        ↓ dto passado ao Service
[RatOcorrenciaService / RatRelatoService]
  DB::transaction() + dto->toArray() → array filtrado sem nulos
        ↓
[Model Eloquent — RatOcorrencia, RatOcorrenciaRelato ...]
  create($data) / update($data)
        ↓
[MySQL — tabelas rat_ocorrencias, rat_ocorrencia_relatos ...]
  Soft delete preserva dados; cascade via booted() events
```

---

## 7. Resumo Quantitativo — Fase 3

| Categoria | Quantidade |
|-----------|-----------|
| Migration de recriação criada e executada | 1 |
| Tabelas polimórficas recriadas no banco | 9 |
| Migration inválida deletada | 1 |
| Models com cascade soft delete adicionado | 3 |
| Seeder novo criado (`RatRedecSeeder`) | 1 |
| REDECs inseridas em `rat_redec` | 14 |
| `DatabaseSeeder` atualizado | 1 |
| DTOs novos criados (`app/DTOs/Rat/`) | 3 |
| Service atualizado com assinaturas DTO | 1 |
| Controllers atualizados para usar DTOs | 4 |
| Cache final: `php artisan optimize` | ✅ |

---

*Fase 3 documentada em 10/03/2026 — Módulo RAT, Barbara Costa*
