# Plano: Implementar Finalize RAT com Motor CRUD

## Objetivo
Validar a integracao do Motor CRUD Inteligente com o sistema de permissionamento hierarquico atraves da funcionalidade "Finalizar RAT".

---

## Arquitetura Base Existente (Core/Actions)

A arquitetura ja implementada fornece:

1. **ActionConfigDTO** - Transporta configuracoes com:
   - `requiredLevel` - nivel minimo para executar acao
   - `getActionLevelMap()` - mapeamento nivel por acao
   - `userHasRequiredLevel()` - verificacao de nivel

2. **ActionPolicy** - Server-side Enforcement:
   - `canExecute()` - verificacao completa (permissao + nivel + entidade)
   - Registra Gates automaticamente via `registerGates()`

3. **AbstractModuleActions** - Base para modulos:
   - `canExecute()` - verifica condicoes da entidade
   - `getConditionalOverrides()` - overrides por status
   - `createActionConfig()` - helper para criar DTOs

---

## Arquivos a Modificar

### Backend

| # | Arquivo | Alteracao |
|---|---------|-----------|
| 1 | `app/Core/Actions/Enums/ActionType.php` | Adicionar case FINALIZE |
| 2 | `app/Core/Actions/DTOs/ActionConfigDTO.php` | Adicionar 'finalize' no getActionLevelMap e fromActionType |
| 3 | `app/Modules/Rat/Config/RatActionsConfig.php` | Adicionar FINALIZE nas acoes + condicoes |
| 4 | `app/Modules/Rat/Application/DTOs/FinalizeRatDTO.php` | CRIAR - DTO para transporte |
| 5 | `app/Modules/Rat/Application/Services/RatService.php` | CRIAR - Logica de negocio |
| 6 | `app/Modules/Rat/Presentation/Http/Controllers/RatIndexController.php` | Adicionar metodo finalize() |
| 7 | `routes/modules/rat.php` | Adicionar rota PATCH /rat/{id}/finalize |
| 8 | `app/Modules/Rat/RatServiceProvider.php` | Registrar RatService |

### Frontend

| # | Arquivo | Alteracao |
|---|---------|-----------|
| 9 | `resources/js/domain/actions/ActionTypes.js` | Adicionar FINALIZE |

---

## Detalhamento das Alteracoes

### 1. ActionType.php
- Adicionar `case FINALIZE = 'finalize'`
- label: 'Finalizar'
- variant: 'success'
- icon: 'CheckIcon'

### 2. ActionConfigDTO.php
- Em `getActionLevelMap()`: adicionar `'finalize' => config('permissions.levels.manager', 2)`
- Em `fromActionType()`: adicionar FINALIZE em requiresConfirmation e confirmationMessage

### 3. RatActionsConfig.php
- Adicionar `ActionType::FINALIZE` em `getAvailableActions()`
- Configurar em `getDefaultConfig()` com order: 7
- Em `getConditionalOverrides()`: desabilitar se status = 'finalizado' ou 'rascunho'
- Sobrescrever `canExecute()` para validar status

### 4-5. FinalizeRatDTO e RatService
- DTO: readonly class com id e userId
- Service: injeta RatRepositoryInterface, valida regras de dominio

### 6. RatIndexController
- Injetar RatService via construtor
- Metodo `finalize(int $id)` com Gate::authorize + Service

### 7. Rotas
- `Route::patch('/{id}/finalize', ...)->middleware('can:rat.finalize')`

### 9. ActionTypes.js
- Adicionar em ActionTypes, ActionDefaults e ActionToPropMap

---

## Fluxo de Validacao (Defense in Depth)

```
[1. Frontend - useActionConfig]
isActionEnabled('finalize')
  |-- isSuperAdmin? -> bypass
  |-- overrides[finalize] === false? -> block
  |-- moduleConfig.finalize.enabled? -> block
  |-- can('rat.finalize')? -> block
  |-- userLevel > requiredLevel(2)? -> block
  |-- return true

[2. Controller - Gate]
Gate::authorize('rat.finalize', $rat)
  |-- ActionPolicy.canExecute()
  |   |-- isSuperAdmin? -> bypass
  |   |-- actionConfig.enabled? -> block
  |   |-- checkPermission()? -> block
  |   |-- checkLevel()? -> block
  |   |-- RatActionsConfig.canExecute($entity)? -> block

[3. Service - Regras de Dominio]
RatService.finalize(FinalizeRatDTO)
  |-- status === 'finalizado'? -> DomainException
  |-- status === 'rascunho'? -> DomainException
  |-- Repository.update()
```

---

## Principios Validados

| Principio | Implementacao |
|-----------|---------------|
| **SRP** | DTO (dados), Service (negocio), Controller (orquestracao), Policy (seguranca) |
| **OCP** | ActionType enum extensivel sem modificar logica existente |
| **LSP** | RatActionsConfig substitui AbstractModuleActions |
| **ISP** | Interfaces segregadas (ActionConfigInterface, ModuleActionsInterface) |
| **DIP** | Service depende de RatRepositoryInterface, nao EloquentRatRepository |
| **DRY** | Configuracao centralizada, createActionConfig() reutilizado |
| **Defense in Depth** | 3 camadas independentes de validacao |
