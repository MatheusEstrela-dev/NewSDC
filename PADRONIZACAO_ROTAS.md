# 📋 Padronização de Rotas - NewSDC

## 🎯 Objetivo

Este documento estabelece o padrão de organização de rotas seguindo a arquitetura modular do projeto NewSDC, baseada nos conceitos do Blueprint Arquitetural (papiro2).

---

## 🏗️ Arquitetura de Rotas

### Estrutura de Diretórios

```
SDC/routes/
├── web.php              # Rotas principais da aplicação (web middleware)
├── api.php              # Rotas da API (api middleware)
├── auth.php             # Rotas de autenticação
└── modules/             # Rotas dos módulos de negócio
    ├── ajuda-humanitaria.php
    ├── compdec.php
    ├── decretacoes.php
    ├── tdap.php
    ├── treinamento.php
    └── ...
```

---

## ✅ Padrão Correto de Implementação

### 1. Registro no `web.php`

Todos os módulos **DEVEM** ser registrados dentro do grupo `auth` do `web.php`:

```php
Route::middleware('auth')->group(function () {

    // ... outras rotas autenticadas ...

    // ========================================================================
    // MÓDULOS DE NEGÓCIO
    // ========================================================================

    // Módulo: Decretações
    require __DIR__.'/modules/decretacoes.php';

    // Módulo: Ajuda Humanitária
    require __DIR__.'/modules/ajuda-humanitaria.php';

    // Módulo: TDAP (Gestão de Depósito)
    require __DIR__.'/modules/tdap.php';

    // Módulo: Compdec (Órgãos e Competências)
    require __DIR__.'/modules/compdec.php';

    // Módulo: Treinamento
    require __DIR__.'/modules/treinamento.php';
});
```

### 2. Arquivo de Rotas do Módulo

**Template Padrão:**

```php
<?php

use App\Modules\[Modulo]\Presentation\Http\Controllers\[Controller];
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo [Nome do Módulo]
|--------------------------------------------------------------------------
*/

// Já está dentro do middleware auth do web.php, então não precisa redefinir
Route::prefix('[prefixo]')->name('[prefixo].')->group(function () {

    // Rotas públicas (para todos usuários autenticados)
    Route::get('/', [Controller]::class)->name('index');
    Route::get('/{id}', [Controller]::class)->name('show');

    // Rotas protegidas (com permissões específicas)
    Route::middleware('can:[permissao]')->group(function () {
        Route::post('/', [Controller]::class)->name('store');
        Route::put('/{id}', [Controller]::class)->name('update');
        Route::delete('/{id}', [Controller]::class)->name('destroy');
    });
});
```

---

## 🚫 Anti-Padrões (O que NÃO fazer)

### ❌ 1. NÃO Duplicar Middlewares

**Errado:**
```php
// ERRADO: Middleware 'auth' redundante
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/compdec/orgaos', OrgaoIndexController::class);
});
```

**Correto:**
```php
// CORRETO: Aproveita o middleware do web.php
Route::prefix('compdec')->name('compdec.')->group(function () {
    Route::get('/orgaos', OrgaoIndexController::class)->name('index');
});
```

### ❌ 2. NÃO Repetir Prefixos nas Rotas

**Errado:**
```php
Route::prefix('compdec')->group(function () {
    Route::get('/compdec/orgaos', ...); // ❌ Vai gerar: /compdec/compdec/orgaos
});
```

**Correto:**
```php
Route::prefix('compdec')->group(function () {
    Route::get('/orgaos', ...); // ✅ Gera: /compdec/orgaos
});
```

### ❌ 3. NÃO Esquecer de Registrar no web.php

**Se o módulo NÃO estiver registrado no web.php:**
- As rotas não serão carregadas
- Você receberá erro 404
- O Laravel pode redirecionar erroneamente para a API

---

## 📚 Exemplos Práticos

### Exemplo 1: Módulo Simples (Decretações)

**Arquivo: `routes/modules/decretacoes.php`**

```php
<?php

use App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoIndexController;
use App\Modules\Decretacoes\Presentation\Http\Controllers\ProcessoShowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo Decretações
|--------------------------------------------------------------------------
*/

// Já está dentro do middleware auth do web.php, então não precisa redefinir
Route::prefix('decretacoes')->name('decretacoes.')->group(function () {

    // Index - Lista de processos
    Route::get('/', ProcessoIndexController::class)->name('index');

    // Show - Visualizar processo
    Route::get('/{id}', ProcessoShowController::class)->name('show');
});
```

**URLs Geradas:**
- `GET /decretacoes` → `decretacoes.index`
- `GET /decretacoes/{id}` → `decretacoes.show`

### Exemplo 2: Módulo com Permissões (Ajuda Humanitária)

**Arquivo: `routes/modules/ajuda-humanitaria.php`**

```php
<?php

use App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios\BeneficiarioIndexController;
use App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios\BeneficiarioStoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas: Módulo Ajuda Humanitária
|--------------------------------------------------------------------------
*/

// Já está dentro do middleware auth do web.php, então não precisa redefinir
Route::prefix('ajuda-humanitaria')->name('ajuda-humanitaria.')->group(function () {

    // Beneficiários
    Route::prefix('beneficiarios')->name('beneficiarios.')->group(function () {
        Route::get('/', BeneficiarioIndexController::class)->name('index');
        Route::post('/', BeneficiarioStoreController::class)->name('store');
        Route::get('/{id}', BeneficiarioShowController::class)->name('show');
        Route::put('/{id}', BeneficiarioUpdateController::class)->name('update');
        Route::delete('/{id}', BeneficiarioDestroyController::class)->name('destroy');
    });
});
```

**URLs Geradas:**
- `GET /ajuda-humanitaria/beneficiarios` → `ajuda-humanitaria.beneficiarios.index`
- `POST /ajuda-humanitaria/beneficiarios` → `ajuda-humanitaria.beneficiarios.store`
- `GET /ajuda-humanitaria/beneficiarios/{id}` → `ajuda-humanitaria.beneficiarios.show`

### Exemplo 3: Módulo com Rotas Administrativas (TDAP)

**Arquivo: `routes/modules/tdap.php`**

```php
<?php

use App\Modules\Tdap\Presentation\Http\Controllers\TdapDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do Módulo TDAP (Gestão de Depósito)
|--------------------------------------------------------------------------
*/

// Já está dentro do middleware auth do web.php, então não precisa redefinir
Route::prefix('tdap')->name('tdap.')->group(function () {

    // Dashboard
    Route::get('/', [TdapDashboardController::class, 'index'])->name('dashboard');

    // Produtos
    Route::prefix('produtos')->name('products.')->group(function () {
        Route::get('/', [TdapProductsController::class, 'index'])->name('index');
        Route::post('/', [TdapProductsController::class, 'store'])
            ->middleware('can:tdap.products.create')
            ->name('store');
    });
});

// Rotas de administração do TDAP (já está dentro do middleware auth)
Route::middleware('can:tdap.admin')
    ->prefix('admin/tdap')
    ->name('admin.tdap.')
    ->group(function () {
        Route::get('/', [TdapDashboardController::class, 'index'])->name('dashboard');
    });
```

**URLs Geradas:**
- `GET /tdap` → `tdap.dashboard`
- `GET /tdap/produtos` → `tdap.products.index`
- `POST /tdap/produtos` → `tdap.products.store` (requer permissão)
- `GET /admin/tdap` → `admin.tdap.dashboard` (requer permissão admin)

---

## 🔍 Checklist de Revisão

Antes de criar ou modificar rotas de um módulo, verifique:

- [ ] O módulo está registrado no `web.php`?
- [ ] O arquivo de rotas NÃO duplica middlewares (`auth`, `web`)?
- [ ] Os prefixos NÃO estão sendo repetidos nas URIs?
- [ ] As rotas seguem o padrão REST (index, show, store, update, destroy)?
- [ ] As permissões estão corretamente aplicadas onde necessário?
- [ ] A nomenclatura de rotas segue o padrão: `[modulo].[recurso].[acao]`?

---

## 🛠️ Comandos Úteis

### Listar Rotas de um Módulo

```bash
# Ver rotas de Ajuda Humanitária
php artisan route:list --path=ajuda-humanitaria

# Ver rotas de TDAP
php artisan route:list --path=tdap

# Ver rotas de Compdec
php artisan route:list --path=compdec
```

### Verificar Nome de Rota

```bash
# Buscar rota específica
php artisan route:list --name=ajuda-humanitaria.beneficiarios.index
```

### Limpar Cache de Rotas

```bash
php artisan route:clear
php artisan route:cache
```

---

## 📖 Referências

- **Blueprint Arquitetural**: [BLUEPRINT_ARQUITETURAL_NEWSDC.md](BLUEPRINT_ARQUITETURAL_NEWSDC.md)
- **Papiro 2**: [.claude/commands/papiro2.md](.claude/commands/papiro2.md)
- **Laravel Routes**: https://laravel.com/docs/routing

---

## 🔄 Histórico de Mudanças

| Data       | Mudança                                              | Autor  |
|------------|------------------------------------------------------|--------|
| 2025-12-28 | Criação do documento de padronização                 | Claude |
| 2025-12-28 | Correção de rotas duplicadas e middlewares redundantes | Claude |
| 2025-12-28 | Adição de módulos faltantes ao web.php              | Claude |

---

## ✅ Status Atual dos Módulos

| Módulo             | Registrado | Padronizado | Rotas Testadas |
|-------------------|-----------|-------------|----------------|
| Decretações       | ✅        | ✅          | ✅             |
| Ajuda Humanitária | ✅        | ✅          | ✅             |
| TDAP              | ✅        | ✅          | ✅             |
| Compdec           | ✅        | ✅          | ✅             |
| Treinamento       | ✅        | ✅          | ✅             |
| Permissions       | ✅        | ✅          | ✅             |
| RAT               | ✅        | ⚠️          | ✅             |
| PAE               | ✅        | ⚠️          | ✅             |

**Legenda:**
- ✅ OK
- ⚠️ Precisa revisão
- ❌ Pendente

---

**Desenvolvido por:** Matheus Nanda
**Arquitetura:** Claude Sonnet 4.5
**Data:** 28/12/2025
