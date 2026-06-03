# PR #1 — Adicionar 10 Slugs de Ações ao Sistema de Permissões

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar 10 novos slugs de ação ao `config/permissions.php` para cobrir ações já em uso no frontend (print, pdf, history, arquivar, validar, attachments, desvincular) e atribuí-los aos roles apropriados.

**Architecture:** Mudança 100% em config (sem migration de schema). `RolesAndPermissionsSeeder` é idempotente (`updateOrCreate`) e propaga automaticamente. Admin (super-admin) ganha tudo via wildcards já existentes. Atribuição explícita só para manager/analyst/operator/viewer conforme regras de menor privilégio definidas no spec.

**Tech Stack:** Laravel 11 + Spatie Permission v6.24 + PostgreSQL 17.

**Spec:** [docs/superpowers/specs/2026-05-28-padronizacao-permissions-actionbutton-design.md](docs/superpowers/specs/2026-05-28-padronizacao-permissions-actionbutton-design.md)

---

## Files Touched

| Arquivo | Modificação |
|---|---|
| [SDC/config/permissions.php](SDC/config/permissions.php) | Adicionar 10 slugs em `modules`; atribuir aos roles em `role_permissions` |
| [SDC/database/seeders/RolesAndPermissionsSeeder.php](SDC/database/seeders/RolesAndPermissionsSeeder.php) | Estender array `actionLabels` em `generatePermissionDescription` com 7 verbos novos |
| [docs/superpowers/plans/2026-05-28-pr1-add-action-slugs.md](docs/superpowers/plans/2026-05-28-pr1-add-action-slugs.md) | Este plano (já criado) |

**Não tocado:** ActionButton.vue, TableActions.vue, controllers, policies, migrations, frontend em geral.

---

## Mapa dos 10 slugs

| # | Slug novo | Módulo.Grupo (chave PHP) | Action key |
|---|---|---|---|
| 1 | `pae.protocolos.arquivar` | `PAE.Protocolos` | `arquivar` |
| 2 | `pae.protocolos.history` | `PAE.Protocolos` | `history` |
| 3 | `pae.protocolos.validar` | `PAE.Protocolos` | `validar` |
| 4 | `pae.protocolos.pdf` | `PAE.Protocolos` | `pdf` |
| 5 | `rat.protocolos.print` | `RAT.Protocolos` | `print` |
| 6 | `rat.protocolos.attachments` | `RAT.Protocolos` | `attachments` |
| 7 | `decretacoes.processos.print` | `DECRETACOES.Processos` | `print` |
| 8 | `humanitaria.beneficiarios.print` | `AJUDA_HUMANITARIA.Beneficiarios` | `print` |
| 9 | `estoque.movimentacoes.history` | `ESTOQUE.Movimentacoes` | `history` |
| 10 | `compdec.usuarios.desvincular` | `COMPDEC.UsuarioVinculo` | `desvincular` |

---

## Regras de atribuição (consolidadas do spec)

| Categoria | Roles |
|---|---|
| `*.view`, `*.print`, `*.history`, `*.pdf` | admin (via wildcard) + manager + analyst + operator + viewer |
| `*.arquivar`, `*.attachments` | admin (via wildcard) + manager + analyst |
| `*.validar` | admin (via wildcard) + manager |
| `*.desvincular` | admin (via wildcard) — somente |

**Admin já recebe todos os novos slugs automaticamente** porque seu bloco `role_permissions.admin` usa wildcards `pae.*`, `rat.*`, `decretacoes.*`, `humanitaria.*`, `estoque.*`, `compdec.*`. **Nada a adicionar em `admin`.**

---

### Task 1: Adicionar 4 slugs em `PAE.Protocolos`

**Files:**
- Modify: `SDC/config/permissions.php:104-111`

- [ ] **Step 1: Editar o array `Protocolos` dentro do módulo `PAE`**

Localize o bloco (em torno da linha 104-111):

```php
'Protocolos' => [
    'view' => 'pae.protocolos.view',
    'create' => 'pae.protocolos.create',
    'edit' => 'pae.protocolos.edit',
    'delete' => 'pae.protocolos.delete',
    'atribuir' => 'pae.protocolos.atribuir',
    'export' => 'pae.protocolos.export',
],
```

Substitua por:

```php
'Protocolos' => [
    'view' => 'pae.protocolos.view',
    'create' => 'pae.protocolos.create',
    'edit' => 'pae.protocolos.edit',
    'delete' => 'pae.protocolos.delete',
    'atribuir' => 'pae.protocolos.atribuir',
    'arquivar' => 'pae.protocolos.arquivar',
    'history' => 'pae.protocolos.history',
    'validar' => 'pae.protocolos.validar',
    'pdf' => 'pae.protocolos.pdf',
    'export' => 'pae.protocolos.export',
],
```

- [ ] **Step 2: Salvar e validar sintaxe PHP**

Run: `php -l SDC/config/permissions.php`
Expected: `No syntax errors detected in SDC/config/permissions.php`

---

### Task 2: Adicionar 2 slugs em `RAT.Protocolos`

**Files:**
- Modify: `SDC/config/permissions.php:113-122`

- [ ] **Step 1: Editar o array `Protocolos` dentro do módulo `RAT`**

Localize o bloco (em torno da linha 113-122):

```php
'RAT' => [
    'Protocolos' => [
        'view' => 'rat.protocolos.view',
        'create' => 'rat.protocolos.create',
        'edit' => 'rat.protocolos.edit',
        'delete' => 'rat.protocolos.delete',
        'finalize' => 'rat.protocolos.finalize',
        'export' => 'rat.protocolos.export',
    ],
],
```

Substitua por:

```php
'RAT' => [
    'Protocolos' => [
        'view' => 'rat.protocolos.view',
        'create' => 'rat.protocolos.create',
        'edit' => 'rat.protocolos.edit',
        'delete' => 'rat.protocolos.delete',
        'finalize' => 'rat.protocolos.finalize',
        'print' => 'rat.protocolos.print',
        'attachments' => 'rat.protocolos.attachments',
        'export' => 'rat.protocolos.export',
    ],
],
```

- [ ] **Step 2: Validar sintaxe**

Run: `php -l SDC/config/permissions.php`
Expected: `No syntax errors detected`

---

### Task 3: Adicionar 1 slug em `DECRETACOES.Processos`

**Files:**
- Modify: `SDC/config/permissions.php:133-141`

- [ ] **Step 1: Editar o array `Processos` dentro do módulo `DECRETACOES`**

Localize:

```php
'DECRETACOES' => [
    'Processos' => [
        'view' => 'decretacoes.processos.view',
        'create' => 'decretacoes.processos.create',
        'edit' => 'decretacoes.processos.edit',
        'delete' => 'decretacoes.processos.delete',
        'export' => 'decretacoes.processos.export',
    ],
],
```

Substitua por:

```php
'DECRETACOES' => [
    'Processos' => [
        'view' => 'decretacoes.processos.view',
        'create' => 'decretacoes.processos.create',
        'edit' => 'decretacoes.processos.edit',
        'delete' => 'decretacoes.processos.delete',
        'print' => 'decretacoes.processos.print',
        'export' => 'decretacoes.processos.export',
    ],
],
```

- [ ] **Step 2: Validar sintaxe**

Run: `php -l SDC/config/permissions.php`
Expected: `No syntax errors detected`

---

### Task 4: Adicionar 1 slug em `AJUDA_HUMANITARIA.Beneficiarios`

**Files:**
- Modify: `SDC/config/permissions.php:142-150`

- [ ] **Step 1: Editar o array `Beneficiarios` dentro do módulo `AJUDA_HUMANITARIA`**

Localize:

```php
'AJUDA_HUMANITARIA' => [
    'Beneficiarios' => [
        'view' => 'humanitaria.beneficiarios.view',
        'create' => 'humanitaria.beneficiarios.create',
        'edit' => 'humanitaria.beneficiarios.edit',
        'delete' => 'humanitaria.beneficiarios.delete',
        'export' => 'humanitaria.beneficiarios.export',
    ],
],
```

Substitua por:

```php
'AJUDA_HUMANITARIA' => [
    'Beneficiarios' => [
        'view' => 'humanitaria.beneficiarios.view',
        'create' => 'humanitaria.beneficiarios.create',
        'edit' => 'humanitaria.beneficiarios.edit',
        'delete' => 'humanitaria.beneficiarios.delete',
        'print' => 'humanitaria.beneficiarios.print',
        'export' => 'humanitaria.beneficiarios.export',
    ],
],
```

- [ ] **Step 2: Validar sintaxe**

Run: `php -l SDC/config/permissions.php`
Expected: `No syntax errors detected`

---

### Task 5: Adicionar 1 slug em `ESTOQUE.Movimentacoes`

**Files:**
- Modify: `SDC/config/permissions.php:330-335`

- [ ] **Step 1: Editar o array `Movimentacoes` dentro do módulo `ESTOQUE`**

Localize:

```php
'Movimentacoes' => [
    'view'    => 'estoque.movimentacoes.view',
    'create'  => 'estoque.movimentacoes.create',
    'approve' => 'estoque.movimentacoes.approve',
    'export'  => 'estoque.movimentacoes.export',
],
```

Substitua por:

```php
'Movimentacoes' => [
    'view'    => 'estoque.movimentacoes.view',
    'create'  => 'estoque.movimentacoes.create',
    'approve' => 'estoque.movimentacoes.approve',
    'history' => 'estoque.movimentacoes.history',
    'export'  => 'estoque.movimentacoes.export',
],
```

- [ ] **Step 2: Validar sintaxe**

Run: `php -l SDC/config/permissions.php`
Expected: `No syntax errors detected`

---

### Task 6: Adicionar 1 slug em `COMPDEC.UsuarioVinculo`

**Files:**
- Modify: `SDC/config/permissions.php:281-283`

- [ ] **Step 1: Editar o array `UsuarioVinculo` dentro do módulo `COMPDEC`**

Localize:

```php
'UsuarioVinculo' => [
    'manage' => 'compdec.usuarios.manage',
],
```

Substitua por:

```php
'UsuarioVinculo' => [
    'manage' => 'compdec.usuarios.manage',
    'desvincular' => 'compdec.usuarios.desvincular',
],
```

- [ ] **Step 2: Validar sintaxe**

Run: `php -l SDC/config/permissions.php`
Expected: `No syntax errors detected`

---

### Task 7: Estender `actionLabels` no Seeder

**Files:**
- Modify: `SDC/database/seeders/RolesAndPermissionsSeeder.php:178-193`

- [ ] **Step 1: Adicionar 7 labels descritivas para as ações novas**

Localize o método `generatePermissionDescription` (em torno da linha 176-197):

```php
$actionLabels = [
    'view' => 'Visualizar',
    'create' => 'Criar',
    'edit' => 'Editar',
    'delete' => 'Deletar',
    'approve' => 'Aprovar',
    'assign' => 'Atribuir',
    'finalize' => 'Finalizar',
    'manage' => 'Gerenciar',
    'execute' => 'Executar',
    'export' => 'Exportar',
    'send' => 'Enviar',
    'logs' => 'Visualizar Logs',
    'cache' => 'Limpar Cache',
    'settings' => 'Configuracoes',
];
```

Substitua por:

```php
$actionLabels = [
    'view' => 'Visualizar',
    'create' => 'Criar',
    'edit' => 'Editar',
    'delete' => 'Deletar',
    'approve' => 'Aprovar',
    'assign' => 'Atribuir',
    'atribuir' => 'Atribuir',
    'finalize' => 'Finalizar',
    'manage' => 'Gerenciar',
    'execute' => 'Executar',
    'export' => 'Exportar',
    'send' => 'Enviar',
    'logs' => 'Visualizar Logs',
    'cache' => 'Limpar Cache',
    'settings' => 'Configuracoes',
    'print' => 'Imprimir',
    'pdf' => 'Gerar PDF',
    'history' => 'Visualizar Historico',
    'arquivar' => 'Arquivar',
    'validar' => 'Validar',
    'attachments' => 'Gerenciar Anexos',
    'desvincular' => 'Desvincular',
];
```

- [ ] **Step 2: Validar sintaxe**

Run: `php -l SDC/database/seeders/RolesAndPermissionsSeeder.php`
Expected: `No syntax errors detected`

---

### Task 8: Atribuir slugs novos ao role `manager`

**Files:**
- Modify: `SDC/config/permissions.php:373-505` (bloco `manager` em `role_permissions`)

- [ ] **Step 1: Adicionar 9 entradas no array de permissões do manager**

Localize o bloco do `manager`. Adicione os slugs nas seções apropriadas. Para facilitar, encontre cada bloco de comentário e estenda:

**a) Seção "PAE" do manager** (em torno da linha 375-384), localize:

```php
// PAE - CRUD completo exceto delete
'pae.empreendimentos.view',
'pae.empreendimentos.create',
'pae.empreendimentos.edit',
'pae.empreendimentos.approve',
'pae.empreendimentos.export',
'pae.protocolos.view',
'pae.protocolos.create',
'pae.protocolos.edit',
'pae.protocolos.atribuir',
'pae.protocolos.export',
```

Substitua por:

```php
// PAE - CRUD completo exceto delete
'pae.empreendimentos.view',
'pae.empreendimentos.create',
'pae.empreendimentos.edit',
'pae.empreendimentos.approve',
'pae.empreendimentos.export',
'pae.protocolos.view',
'pae.protocolos.create',
'pae.protocolos.edit',
'pae.protocolos.atribuir',
'pae.protocolos.arquivar',
'pae.protocolos.history',
'pae.protocolos.validar',
'pae.protocolos.pdf',
'pae.protocolos.export',
```

**b) Seção "RAT" do manager**, localize:

```php
// RAT - CRUD completo exceto delete
'rat.protocolos.view',
'rat.protocolos.create',
'rat.protocolos.edit',
'rat.protocolos.finalize',
'rat.protocolos.export',
```

Substitua por:

```php
// RAT - CRUD completo exceto delete
'rat.protocolos.view',
'rat.protocolos.create',
'rat.protocolos.edit',
'rat.protocolos.finalize',
'rat.protocolos.print',
'rat.protocolos.attachments',
'rat.protocolos.export',
```

**c) Seção "Decretacoes" do manager**, localize:

```php
// Decretacoes - CRUD completo exceto delete
'decretacoes.processos.view',
'decretacoes.processos.create',
'decretacoes.processos.edit',
'decretacoes.processos.export',
```

Substitua por:

```php
// Decretacoes - CRUD completo exceto delete
'decretacoes.processos.view',
'decretacoes.processos.create',
'decretacoes.processos.edit',
'decretacoes.processos.print',
'decretacoes.processos.export',
```

**d) Seção "Ajuda Humanitaria" do manager**, localize:

```php
// Ajuda Humanitaria - CRUD completo exceto delete
'humanitaria.beneficiarios.view',
'humanitaria.beneficiarios.create',
'humanitaria.beneficiarios.edit',
'humanitaria.beneficiarios.export',
```

Substitua por:

```php
// Ajuda Humanitaria - CRUD completo exceto delete
'humanitaria.beneficiarios.view',
'humanitaria.beneficiarios.create',
'humanitaria.beneficiarios.edit',
'humanitaria.beneficiarios.print',
'humanitaria.beneficiarios.export',
```

**e) Seção "Estoque" do manager**, localize (em torno da linha 498-501):

```php
'estoque.movimentacoes.view',
'estoque.movimentacoes.create',
'estoque.movimentacoes.approve',
'estoque.movimentacoes.export',
```

Substitua por:

```php
'estoque.movimentacoes.view',
'estoque.movimentacoes.create',
'estoque.movimentacoes.approve',
'estoque.movimentacoes.history',
'estoque.movimentacoes.export',
```

- [ ] **Step 2: Validar sintaxe**

Run: `php -l SDC/config/permissions.php`
Expected: `No syntax errors detected`

---

### Task 9: Atribuir slugs novos ao role `analyst`

**Files:**
- Modify: `SDC/config/permissions.php:506-598` (bloco `analyst` em `role_permissions`)

- [ ] **Step 1: Adicionar 8 entradas no array de permissões do analyst**

**a) Seção "PAE" do analyst**, localize:

```php
// PAE - view, create, edit
'pae.empreendimentos.view',
'pae.empreendimentos.create',
'pae.empreendimentos.edit',
'pae.protocolos.view',
'pae.protocolos.create',
'pae.protocolos.edit',
'pae.protocolos.atribuir',
```

Substitua por:

```php
// PAE - view, create, edit
'pae.empreendimentos.view',
'pae.empreendimentos.create',
'pae.empreendimentos.edit',
'pae.protocolos.view',
'pae.protocolos.create',
'pae.protocolos.edit',
'pae.protocolos.atribuir',
'pae.protocolos.arquivar',
'pae.protocolos.history',
'pae.protocolos.pdf',
```

**b) Seção "RAT" do analyst**, localize:

```php
// RAT - view, create, edit
'rat.protocolos.view',
'rat.protocolos.create',
'rat.protocolos.edit',
```

Substitua por:

```php
// RAT - view, create, edit
'rat.protocolos.view',
'rat.protocolos.create',
'rat.protocolos.edit',
'rat.protocolos.print',
'rat.protocolos.attachments',
```

**c) Seção "Decretacoes" do analyst**, localize:

```php
// Decretacoes - view, create, edit
'decretacoes.processos.view',
'decretacoes.processos.create',
'decretacoes.processos.edit',
```

Substitua por:

```php
// Decretacoes - view, create, edit
'decretacoes.processos.view',
'decretacoes.processos.create',
'decretacoes.processos.edit',
'decretacoes.processos.print',
```

**d) Seção "Ajuda Humanitaria" do analyst**, localize:

```php
// Ajuda Humanitaria - view, create, edit
'humanitaria.beneficiarios.view',
'humanitaria.beneficiarios.create',
'humanitaria.beneficiarios.edit',
```

Substitua por:

```php
// Ajuda Humanitaria - view, create, edit
'humanitaria.beneficiarios.view',
'humanitaria.beneficiarios.create',
'humanitaria.beneficiarios.edit',
'humanitaria.beneficiarios.print',
```

**e) Seção "Estoque" do analyst**, localize (em torno da linha 594-595):

```php
'estoque.movimentacoes.view',
'estoque.movimentacoes.create',
```

Substitua por:

```php
'estoque.movimentacoes.view',
'estoque.movimentacoes.create',
'estoque.movimentacoes.history',
```

- [ ] **Step 2: Validar sintaxe**

Run: `php -l SDC/config/permissions.php`
Expected: `No syntax errors detected`

---

### Task 10: Atribuir slugs novos aos roles `operator` e `viewer`

**Files:**
- Modify: `SDC/config/permissions.php:599-685`

- [ ] **Step 1: Estender o role `operator`**

Localize a seção `'operator' => [` (em torno da linha 599). Adicione apenas os slugs de leitura/utilidade nas seções apropriadas:

**operator – PAE**, localize:

```php
// PAE - view, create
'pae.empreendimentos.view',
'pae.empreendimentos.create',
'pae.protocolos.view',
'pae.protocolos.create',
```

Substitua por:

```php
// PAE - view, create
'pae.empreendimentos.view',
'pae.empreendimentos.create',
'pae.protocolos.view',
'pae.protocolos.create',
'pae.protocolos.history',
'pae.protocolos.pdf',
```

**operator – RAT**, localize:

```php
// RAT - view, create
'rat.protocolos.view',
'rat.protocolos.create',
```

Substitua por:

```php
// RAT - view, create
'rat.protocolos.view',
'rat.protocolos.create',
'rat.protocolos.print',
```

**operator – Decretacoes**, localize:

```php
// Decretacoes - view
'decretacoes.processos.view',
```

Substitua por:

```php
// Decretacoes - view
'decretacoes.processos.view',
'decretacoes.processos.print',
```

**operator – Ajuda Humanitaria**, localize:

```php
// Ajuda Humanitaria - view, create
'humanitaria.beneficiarios.view',
'humanitaria.beneficiarios.create',
```

Substitua por:

```php
// Ajuda Humanitaria - view, create
'humanitaria.beneficiarios.view',
'humanitaria.beneficiarios.create',
'humanitaria.beneficiarios.print',
```

**operator – Estoque**, localize (em torno da linha 649-650):

```php
'estoque.movimentacoes.view',
'estoque.movimentacoes.create',
```

Substitua por:

```php
'estoque.movimentacoes.view',
'estoque.movimentacoes.create',
'estoque.movimentacoes.history',
```

- [ ] **Step 2: Estender o role `viewer`**

Localize a seção `'viewer' => [` (em torno da linha 652). Adicione slugs de leitura nas posições apropriadas.

**viewer – PAE**, localize:

```php
'pae.empreendimentos.view',
'pae.protocolos.view',
```

Substitua por:

```php
'pae.empreendimentos.view',
'pae.protocolos.view',
'pae.protocolos.history',
'pae.protocolos.pdf',
```

**viewer – RAT**, localize:

```php
'rat.protocolos.view',
```

Substitua por:

```php
'rat.protocolos.view',
'rat.protocolos.print',
```

**viewer – Decretacoes**, localize:

```php
'decretacoes.processos.view',
```

Substitua por:

```php
'decretacoes.processos.view',
'decretacoes.processos.print',
```

**viewer – Humanitaria**, localize:

```php
'humanitaria.beneficiarios.view',
```

Substitua por:

```php
'humanitaria.beneficiarios.view',
'humanitaria.beneficiarios.print',
```

**viewer – Estoque**, localize (em torno da linha 684):

```php
'estoque.movimentacoes.view',
```

Substitua por:

```php
'estoque.movimentacoes.view',
'estoque.movimentacoes.history',
```

- [ ] **Step 3: Validar sintaxe**

Run: `php -l SDC/config/permissions.php`
Expected: `No syntax errors detected`

---

### Task 11: Validação manual em ambiente local

**Files:** N/A (verificação)

- [ ] **Step 1: Rodar o seeder em ambiente local**

Run a partir do diretório `SDC/`:

```
php artisan db:seed --class=RolesAndPermissionsSeeder
```

Expected (output):
```
Roles e Permissions sincronizadas via config/permissions.php

Hierarquia de Cargos:
  Nivel 0: Desenvolvedor (super-admin)
  Nivel 1: Administrador (admin)
  ...
```

Sem warnings ou exceptions.

- [ ] **Step 2: Verificar contagem de slugs no banco**

Run:

```
php artisan tinker --execute="echo DB::table('permissions')->count();"
```

Expected: número anterior + 10 (se a base já tinha 154 → agora 164). Se for outro número base, anotar antes/depois.

- [ ] **Step 3: Listar os 10 slugs recém-adicionados**

Run:

```
php artisan tinker --execute="echo DB::table('permissions')->whereIn('name', ['pae.protocolos.arquivar','pae.protocolos.history','pae.protocolos.validar','pae.protocolos.pdf','rat.protocolos.print','rat.protocolos.attachments','decretacoes.processos.print','humanitaria.beneficiarios.print','estoque.movimentacoes.history','compdec.usuarios.desvincular'])->count();"
```

Expected: `10`

- [ ] **Step 4: Verificar que admin recebeu todos os 10 via wildcard**

Run:

```
php artisan tinker --execute="\$admin = App\Models\Role::where('slug','admin')->first(); \$news=['pae.protocolos.arquivar','pae.protocolos.history','pae.protocolos.validar','pae.protocolos.pdf','rat.protocolos.print','rat.protocolos.attachments','decretacoes.processos.print','humanitaria.beneficiarios.print','estoque.movimentacoes.history','compdec.usuarios.desvincular']; foreach(\$news as \$n){ echo \$n.': '.(\$admin->hasPermissionTo(\$n) ? 'OK' : 'MISS').PHP_EOL; }"
```

Expected: 10 linhas com `OK`.

- [ ] **Step 5: Verificar manager (deve ter 9 dos 10 — todos menos `compdec.usuarios.desvincular`)**

Run:

```
php artisan tinker --execute="\$m = App\Models\Role::where('slug','manager')->first(); \$news=['pae.protocolos.arquivar','pae.protocolos.history','pae.protocolos.validar','pae.protocolos.pdf','rat.protocolos.print','rat.protocolos.attachments','decretacoes.processos.print','humanitaria.beneficiarios.print','estoque.movimentacoes.history','compdec.usuarios.desvincular']; foreach(\$news as \$n){ echo \$n.': '.(\$m->hasPermissionTo(\$n) ? 'OK' : 'MISS').PHP_EOL; }"
```

Expected: 9 linhas `OK`, 1 `MISS` em `compdec.usuarios.desvincular`.

- [ ] **Step 6: Smoke test visual** — abrir `/admin/permissions/roles/2` (Administrador) na aplicação e confirmar que os novos slugs aparecem listados nas seções PAE/RAT/DECRETACOES/HUMANITARIA/ESTOQUE.

---

### Task 12: Commit consolidado e push

**Files:** N/A (operações git)

- [ ] **Step 1: Verificar diff**

Run:

```
git status
git diff SDC/config/permissions.php SDC/database/seeders/RolesAndPermissionsSeeder.php
```

Expected: apenas estes 2 arquivos modificados + o plano novo em `docs/superpowers/plans/` + o spec já existente.

> **Atenção:** outros arquivos modificados existentes em `dev` (Handler.php, AppServiceProvider.php, composer.*, etc.) **não devem entrar** neste commit. Stagear arquivos explicitamente.

- [ ] **Step 2: Stage seletivo**

Run:

```
git add SDC/config/permissions.php
git add SDC/database/seeders/RolesAndPermissionsSeeder.php
git add docs/superpowers/plans/2026-05-28-pr1-add-action-slugs.md
git add docs/superpowers/specs/2026-05-28-padronizacao-permissions-actionbutton-design.md
```

- [ ] **Step 3: Commit (sem Co-Authored-By, conforme preferência do repo)**

Run:

```
git commit -m "feat(permissions): adiciona 10 slugs de acoes (print, pdf, history, arquivar, validar, attachments, desvincular)

- PAE.Protocolos: arquivar, history, validar, pdf
- RAT.Protocolos: print, attachments
- DECRETACOES.Processos: print
- AJUDA_HUMANITARIA.Beneficiarios: print
- ESTOQUE.Movimentacoes: history
- COMPDEC.UsuarioVinculo: desvincular

Atribui aos roles manager/analyst/operator/viewer conforme regra de menor privilegio.
Admin recebe via wildcards existentes. Inclui spec e plano da padronizacao fase 1."
```

Expected: hook pré-commit passa, commit criado.

- [ ] **Step 4: Push da branch**

Run:

```
git push -u origin feat/permissions-add-action-slugs
```

Expected: branch publicada no remoto.

- [ ] **Step 5: Abrir PR (opcional, decisão do usuário no fim)**

Comando se quiser usar gh CLI:

```
gh pr create --base dev --title "feat(permissions): adiciona 10 slugs de acoes (fase 1)" --body-file docs/superpowers/specs/2026-05-28-padronizacao-permissions-actionbutton-design.md
```

Lembrar de marcar **"Create a merge commit"** ao fazer merge (conforme preferência `--no-ff`).

---

## Critério de sucesso do PR #1

- `php -l` passa nos 2 arquivos modificados.
- Seeder roda sem erros.
- `permissions` no banco aumentou em exatamente 10.
- Admin tem todos os 10 slugs (Task 11 Step 4 mostra 10 `OK`).
- Manager tem 9 dos 10 (Task 11 Step 5).
- Tela `/admin/permissions/roles/2` lista os novos slugs.
- Nenhum slug existente foi removido ou renomeado (contagem antiga preservada).
- Apenas 4 arquivos no commit: config, seeder, spec, plano.
