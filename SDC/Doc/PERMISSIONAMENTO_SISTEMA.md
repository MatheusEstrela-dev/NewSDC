# Sistema de Permissionamento - NewSDC

## Visao Geral

O sistema utiliza **Spatie/laravel-permission** com hierarquia de cargos e permissoes granulares por modulo.

---

## Estrutura de Permissoes

### Padrao de Nomenclatura
```
{modulo}.{recurso}.{acao}
```

Exemplo: `rat.protocolos.view`, `pae.empreendimentos.create`

---

## Modulos e Permissoes

### SISTEMA (Administracao)

| Recurso | Permissoes |
|---------|------------|
| Usuarios | `users.view`, `users.create`, `users.edit`, `users.delete` |
| Cargos | `roles.view`, `roles.create`, `roles.edit`, `roles.delete` |
| Permissoes | `permissions.view`, `permissions.manage` |
| Configuracoes | `system.logs`, `system.cache`, `system.settings` |

### RAT (Relatorio de Atendimento Tecnico)

| Recurso | Permissoes |
|---------|------------|
| Protocolos | `rat.protocolos.view`, `rat.protocolos.create`, `rat.protocolos.edit`, `rat.protocolos.delete`, `rat.protocolos.finalize` |

### PAE (Plano de Acao Emergencial)

| Recurso | Permissoes |
|---------|------------|
| Empreendimentos | `pae.empreendimentos.view`, `pae.empreendimentos.create`, `pae.empreendimentos.edit`, `pae.empreendimentos.delete`, `pae.empreendimentos.approve` |

### DEMANDAS

| Recurso | Permissoes |
|---------|------------|
| Demandas | `demandas.view`, `demandas.create`, `demandas.edit`, `demandas.delete` |

### DECRETACOES

| Recurso | Permissoes |
|---------|------------|
| Decretacoes | `decretacoes.view`, `decretacoes.create`, `decretacoes.edit`, `decretacoes.delete` |

### AJUDA HUMANITARIA

| Recurso | Permissoes |
|---------|------------|
| Beneficiarios | `ajuda_humanitaria.beneficiarios.view`, `ajuda_humanitaria.beneficiarios.create`, `ajuda_humanitaria.beneficiarios.edit`, `ajuda_humanitaria.beneficiarios.delete` |

### ORGAOS (COMPDEC)

| Recurso | Permissoes |
|---------|------------|
| Orgaos | `compdec.view`, `compdec.create`, `compdec.edit`, `compdec.delete` |

### TDAP (Termo de Doacao)

| Recurso | Permissoes |
|---------|------------|
| Dashboard | `tdap.dashboard.view` |
| Produtos | `tdap.products.view`, `tdap.products.create`, `tdap.products.edit`, `tdap.products.delete` |
| Recebimentos | `tdap.recebimentos.view`, `tdap.recebimentos.create`, `tdap.recebimentos.edit` |
| Movimentacoes | `tdap.movimentacoes.view`, `tdap.movimentacoes.create`, `tdap.movimentacoes.edit` |

### TREINAMENTO

| Recurso | Permissoes |
|---------|------------|
| Treinamentos | `treinamentos.view`, `treinamentos.create`, `treinamentos.edit`, `treinamentos.delete` |

### METEOROLOGIA (INMET)

| Recurso | Permissoes |
|---------|------------|
| Dados | `inmet.view`, `meteorologia.view` |

### VISTORIA

| Recurso | Permissoes |
|---------|------------|
| Vistorias | `vistoria.view`, `vistoria.create`, `vistoria.edit`, `vistoria.delete` |

### BI (Business Intelligence)

| Recurso | Permissoes |
|---------|------------|
| Dashboards | `bi.dashboards.view`, `bi.dashboards.create`, `bi.dashboards.export` |

### INTEGRACOES

| Recurso | Permissoes |
|---------|------------|
| APIs | `integrations.apis.view`, `integrations.apis.create`, `integrations.apis.edit`, `integrations.apis.execute` |
| Webhooks | `integrations.webhooks.send`, `integrations.webhooks.logs` |

---

## Hierarquia de Cargos

| Nivel | Cargo | Descricao |
|-------|-------|-----------|
| 0 | `super-admin` | Acesso total ao sistema |
| 1 | `admin` | Administrador geral |
| 2 | `manager` | Gestor de area |
| 3 | `analyst` | Analista tecnico |
| 4 | `operator` | Operador |
| 5 | `viewer` | Somente leitura |
| 6 | `user` | Usuario padrao |

---

## Tabelas do Banco de Dados

### `roles` (Cargos)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | bigint | PK |
| name | varchar | Nome do cargo |
| slug | varchar | Identificador unico (indexado) |
| guard_name | varchar | Guard do Laravel |
| hierarchy_level | int | Nivel hierarquico (0 = super-admin) |
| description | text | Descricao do cargo |
| is_active | boolean | Ativo/Inativo |

### `permissions` (Permissoes)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| id | bigint | PK |
| name | varchar | Nome da permissao (ex: users.view) |
| slug | varchar | Identificador unico (indexado) |
| guard_name | varchar | Guard do Laravel |
| description | text | Descricao da permissao |
| group | varchar | Grupo/Categoria (ex: usuarios) |
| module | varchar(50) | Modulo de negocio (ex: sistema) |
| is_active | boolean | Ativo/Inativo |
| is_immutable | boolean | Protegida contra alteracao |

### `model_has_roles` (Usuario <-> Cargo)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| role_id | bigint | FK para roles |
| model_type | varchar | Tipo do modelo (App\Models\User) |
| model_id | bigint | ID do usuario |

### `model_has_permissions` (Permissoes Diretas)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| permission_id | bigint | FK para permissions |
| model_type | varchar | Tipo do modelo (App\Models\User) |
| model_id | bigint | ID do usuario |

### `role_has_permissions` (Cargo <-> Permissao)
| Campo | Tipo | Descricao |
|-------|------|-----------|
| permission_id | bigint | FK para permissions |
| role_id | bigint | FK para roles |

---

## Controle de Visibilidade no Frontend

### Arquivo: `resources/js/Components/Sidebar.vue`

Cada modulo tem um computed para controlar visibilidade:

```javascript
// PRINCIPAL
canSeeRat        -> ['rat.protocolos.view', 'rat.view']
canSeeDemandas   -> ['demandas.view']
canSeePae        -> ['pae.empreendimentos.view', 'pae.view']

// MODULOS DE GESTAO
canSeeDecretacoes      -> ['decretacoes.view']
canSeeAjudaHumanitaria -> ['ajuda_humanitaria.view']
canSeeOrgaos           -> ['compdec.view', 'orgaos.view']
canSeeTdap             -> ['tdap.view', 'tdap.products.view']
canSeeTreinamento      -> ['treinamentos.view']
canSeeMeteorologia     -> ['inmet.view', 'meteorologia.view']
canSeeVistoria         -> ['vistoria.view']

// ADMINISTRACAO (RESTRITO)
canSeeAdmin -> ['users.view', 'roles.view', 'permissions.view', 'permissions.manage']
```

### Como Ativar Restricao em um Modulo

1. Localizar o computed no Sidebar.vue
2. Descomentar a linha `hasPermission()`
3. Comentar o `return true`

Exemplo:
```javascript
const canSeeRat = computed(() => {
  return hasPermission(['rat.protocolos.view', 'rat.view']);
  // return true; // Comentar esta linha
});
```

---

## Arquivos Principais

| Arquivo | Descricao |
|---------|-----------|
| `config/permissions.php` | Fonte de verdade para modulos e permissoes |
| `database/seeders/RolesAndPermissionsSeeder.php` | Popula banco a partir do config |
| `app/Models/Role.php` | Model de cargos (extends Spatie) |
| `app/Models/Permission.php` | Model de permissoes (extends Spatie) |
| `app/Http/Middleware/HandleInertiaRequests.php` | Envia permissoes para frontend |
| `resources/js/Components/Sidebar.vue` | Controle de visibilidade de menus |

---

## Comandos Uteis

```bash
# Rodar migrations
php artisan migrate

# Popular permissoes e cargos
php artisan db:seed --class=RolesAndPermissionsSeeder

# Limpar cache de permissoes
php artisan permission:cache-reset
```

---

## Fluxo de Permissoes

```
Usuario
   |
   +-- Cargos (model_has_roles)
   |      |
   |      +-- Permissoes do Cargo (role_has_permissions)
   |
   +-- Permissoes Diretas (model_has_permissions)

Permissao Final = Permissoes do Cargo + Permissoes Diretas
```

---

## Verificacao de Permissao

### No Backend (Laravel)
```php
// Via middleware
$this->middleware('can:users.view');

// No controller
if ($user->can('users.edit')) { ... }

// Verificar role
if ($user->hasRole('admin')) { ... }
```

### No Frontend (Vue)
```javascript
// Via props do Inertia
const user = usePage().props.auth.user;

// Verificar permissao
user.permissions.includes('users.view');

// Verificar role
user.roles.some(r => r.slug === 'admin');
```

---

## Troubleshooting e Casos Conhecidos

### 1. Usuario com acesso indevido ao modulo Administracao
**Sintoma:** Usuario com cargo `manager` visualizando menu "ADMINISTRACAO".
**Causa:**
1. Cargo `manager` possuia permissao `users.view` indevidamente no `config/permissions.php`.
2. Usuario possuia cargo oculto `Externo` com permissoes de admin.
3. Usuario possuia permissao direta `permissions.manage`.

**Solucao:**
1. Remover `users.view` do cargo `manager`.
2. Revogar permissoes de admin do cargo `Externo`.
3. Revogar permissoes diretas do usuario (`$user->revokePermissionTo('permissions.manage')`).
4. Rodar `php artisan db:seed --class=RolesAndPermissionsSeeder`.

