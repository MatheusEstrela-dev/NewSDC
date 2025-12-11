# Sistema de Autenticação Bearer Token - Completo

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Arquitetura do Sistema](#arquitetura-do-sistema)
3. [Hierarquia de Cargos e Permissões](#hierarquia-de-cargos-e-permissões)
4. [Instalação e Configuração](#instalação-e-configuração)
5. [Autenticação com Bearer Token](#autenticação-com-bearer-token)
6. [Uso de Gates](#uso-de-gates)
7. [Uso de Middlewares](#uso-de-middlewares)
8. [Exemplos Práticos](#exemplos-práticos)
9. [API Reference](#api-reference)
10. [Troubleshooting](#troubleshooting)

---

## Visão Geral

Sistema completo de autenticação e autorização usando **Laravel Sanctum** com **Bearer Tokens**, implementando:

- ✅ **Autenticação via Bearer Token** (JWT-like)
- ✅ **Sistema de Roles** (Cargos/Papéis)
- ✅ **Sistema de Permissions** (Permissões granulares)
- ✅ **Gates** para autorização em código
- ✅ **Middlewares** para proteção de rotas
- ✅ **Hierarquia de acesso** (7 níveis)
- ✅ **CRUD completo** de permissions por módulo

---

## Arquitetura do Sistema

```
┌────────────────────────────────────────────────────────────────┐
│                        USER (Usuário)                           │
│  - id, name, email, cpf, password                               │
└────────────────────┬───────────────────────────────────────────┘
                     │
                     │ N:N (role_user)
                     │
┌────────────────────▼───────────────────────────────────────────┐
│                         ROLES (Cargos)                          │
│  - id, name, slug, description, is_active                       │
│  Exemplos: super-admin, admin, manager, analyst                │
└────────────────────┬───────────────────────────────────────────┘
                     │
                     │ N:N (permission_role)
                     │
┌────────────────────▼───────────────────────────────────────────┐
│                    PERMISSIONS (Permissões)                     │
│  - id, name, slug, description, group, is_active                │
│  Exemplos: users.view, users.create, pae.approve               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                  PERSONAL_ACCESS_TOKENS                          │
│  - id, tokenable_id, name, token, abilities, last_used_at       │
│  Armazena tokens Bearer gerados para cada usuário               │
└─────────────────────────────────────────────────────────────────┘
```

### Fluxo de Autenticação

```
1. Login → API: POST /api/auth/login
              ↓
2. Validação de credenciais (email + password)
              ↓
3. Criação de Bearer Token com abilities do usuário
              ↓
4. Retorno do token para o cliente
              ↓
5. Cliente armazena token (localStorage, cookies)
              ↓
6. Requisições subsequentes incluem:
   Authorization: Bearer {token}
              ↓
7. Laravel Sanctum valida token e retorna User
              ↓
8. Gates/Middlewares verificam permissões
              ↓
9. Acesso concedido ou negado (403 Forbidden)
```

---

## Hierarquia de Cargos e Permissões

### Níveis de Acesso (do maior para o menor)

| Nível | Cargo | Slug | Descrição | Permissões |
|-------|-------|------|-----------|------------|
| **0** | Super Administrador | `super-admin` | Acesso total (bypass de todas as verificações) | TODAS |
| **1** | Administrador | `admin` | Administração geral do sistema | Gerenciamento completo exceto system config |
| **2** | Gestor | `manager` | Gestão de áreas e aprovações | Aprovar, criar, editar módulos |
| **3** | Analista | `analyst` | Criação e edição de registros | Criar e editar (sem aprovar/deletar) |
| **4** | Operador | `operator` | Operações básicas | Criar registros básicos |
| **5** | Visualizador | `viewer` | Somente leitura | Visualizar apenas |
| **6** | Usuário | `user` | Acesso mínimo | Acesso limitado a módulos básicos |

### Permissões por Módulo

#### 🔐 USERS (Gestão de Usuários)
- `users.view` - Visualizar usuários
- `users.create` - Criar novos usuários
- `users.edit` - Editar usuários
- `users.delete` - Deletar usuários

#### 🎭 ROLES (Gestão de Cargos)
- `roles.view` - Visualizar cargos
- `roles.create` - Criar cargos
- `roles.edit` - Editar cargos
- `roles.delete` - Deletar cargos

#### 🔑 PERMISSIONS (Gestão de Permissões)
- `permissions.view` - Visualizar permissões
- `permissions.manage` - Gerenciar permissões

#### 🏢 PAE (Plano de Auxílio Emergencial)
- `pae.empreendimentos.view` - Visualizar empreendimentos
- `pae.empreendimentos.create` - Criar empreendimentos
- `pae.empreendimentos.edit` - Editar empreendimentos
- `pae.empreendimentos.delete` - Deletar empreendimentos
- `pae.empreendimentos.approve` - **Aprovar empreendimentos** (Gestor+)

#### 📝 RAT (Relatório de Atendimento Técnico)
- `rat.protocolos.view` - Visualizar protocolos
- `rat.protocolos.create` - Criar protocolos
- `rat.protocolos.edit` - Editar protocolos
- `rat.protocolos.delete` - Deletar protocolos
- `rat.protocolos.finalize` - **Finalizar protocolos** (Gestor+)

#### 📊 BI (Business Intelligence)
- `bi.dashboards.view` - Visualizar dashboards
- `bi.reports.export` - Exportar relatórios
- `bi.dashboards.create` - Criar dashboards personalizados

#### 🔗 INTEGRATIONS (Integrações)
- `integrations.view` - Visualizar integrações
- `integrations.create` - Criar integrações
- `integrations.edit` - Editar integrações
- `integrations.execute` - Executar integrações

#### 🪝 WEBHOOKS
- `webhooks.send` - Enviar webhooks
- `webhooks.logs.view` - Visualizar logs de webhooks

#### ⚙️ SYSTEM (Administração)
- `system.logs.view` - Visualizar logs do sistema
- `system.cache.clear` - Limpar cache
- `system.settings.manage` - Configurações do sistema

### Matriz de Permissões por Cargo

| Permissão | Super Admin | Admin | Gestor | Analista | Operador | Visualizador | Usuário |
|-----------|-------------|-------|--------|----------|----------|--------------|---------|
| users.* | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| roles.* | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| pae.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| pae.create | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| pae.edit | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| pae.delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| pae.approve | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| rat.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| rat.create | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| rat.finalize | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| bi.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| bi.export | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| system.* | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Instalação e Configuração

### 1. Executar Migrations

```bash
# No ambiente de produção (Docker)
docker exec -it newsdc2027 php artisan migrate

# Ou localmente
cd SDC
php artisan migrate
```

Isso criará as tabelas:
- `roles`
- `permissions`
- `role_user`
- `permission_role`
- `personal_access_tokens` (já existe)

### 2. Popular Roles e Permissions (Seeder)

```bash
# No Docker
docker exec -it newsdc2027 php artisan db:seed --class=RolesAndPermissionsSeeder

# Ou localmente
php artisan db:seed --class=RolesAndPermissionsSeeder
```

Isso criará:
- **7 Roles** (super-admin, admin, manager, analyst, operator, viewer, user)
- **38 Permissions** organizadas por módulo
- **Associações** role-permission

### 3. Criar Primeiro Super Admin

```php
// Via Tinker
php artisan tinker

$user = User::create([
    'name' => 'Super Admin',
    'email' => 'admin@example.com',
    'cpf' => '00000000000',
    'password' => Hash::make('senha-super-secreta'),
]);

$superAdminRole = Role::where('slug', 'super-admin')->first();
$user->assignRoles([$superAdminRole->id]);
```

Ou via SQL direto:

```sql
INSERT INTO users (name, email, cpf, password, created_at, updated_at)
VALUES ('Super Admin', 'admin@example.com', '00000000000', '$2y$12$...', NOW(), NOW());

INSERT INTO role_user (user_id, role_id, created_at, updated_at)
SELECT LAST_INSERT_ID(), id, NOW(), NOW()
FROM roles WHERE slug = 'super-admin';
```

---

## Autenticação com Bearer Token

### Registrar Novo Usuário

**Endpoint:** `POST /api/auth/register`

**Request:**
```json
{
  "name": "João Silva",
  "email": "joao@example.com",
  "cpf": "12345678901",
  "password": "senha123",
  "password_confirmation": "senha123"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "cpf": "12345678901",
      "roles": ["user"],
      "permissions": [
        "pae.empreendimentos.view",
        "rat.protocolos.view"
      ]
    },
    "token": "1|laravel_sanctum_abc123...",
    "token_type": "Bearer"
  }
}
```

### Login

**Endpoint:** `POST /api/auth/login`

**Request:**
```json
{
  "email": "joao@example.com",
  "password": "senha123"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "cpf": "12345678901",
      "roles": ["analyst"],
      "permissions": [
        "pae.empreendimentos.view",
        "pae.empreendimentos.create",
        "pae.empreendimentos.edit",
        "rat.protocolos.view",
        "rat.protocolos.create",
        "rat.protocolos.edit",
        "bi.dashboards.view",
        "bi.reports.export"
      ]
    },
    "token": "2|laravel_sanctum_xyz789...",
    "token_type": "Bearer"
  }
}
```

### Usar Token em Requisições

**Todas as requisições protegidas devem incluir o header:**

```
Authorization: Bearer 2|laravel_sanctum_xyz789...
```

**Exemplo com cURL:**
```bash
curl -X GET https://newsdc2027.azurewebsites.net/api/auth/me \
  -H "Authorization: Bearer 2|laravel_sanctum_xyz789..." \
  -H "Accept: application/json"
```

**Exemplo com JavaScript (Axios):**
```javascript
const token = localStorage.getItem('auth_token');

axios.get('/api/auth/me', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

### Obter Dados do Usuário Autenticado

**Endpoint:** `GET /api/auth/me`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "cpf": "12345678901",
      "email_verified_at": null,
      "roles": [
        {
          "id": 4,
          "name": "Analista",
          "slug": "analyst"
        }
      ],
      "permissions": [
        "pae.empreendimentos.view",
        "pae.empreendimentos.create",
        ...
      ]
    }
  }
}
```

### Logout

**Endpoint:** `POST /api/auth/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

### Logout de Todos os Dispositivos

**Endpoint:** `POST /api/auth/logout-all`

**Response:**
```json
{
  "success": true,
  "message": "Logged out from all devices"
}
```

### Refresh Token

**Endpoint:** `POST /api/auth/refresh`

**Response:**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "3|laravel_sanctum_new123...",
    "token_type": "Bearer"
  }
}
```

---

## Uso de Gates

### Em Controllers

```php
use Illuminate\Support\Facades\Gate;

class EmpreendimentoController extends Controller
{
    public function index()
    {
        // Verificar permissão antes de executar
        if (Gate::denies('pae.empreendimentos.view')) {
            abort(403, 'Você não tem permissão para visualizar empreendimentos');
        }

        $empreendimentos = Empreendimento::all();
        return response()->json($empreendimentos);
    }

    public function store(Request $request)
    {
        Gate::authorize('pae.empreendimentos.create');

        // Código para criar empreendimento
    }

    public function approve($id)
    {
        // Apenas Gestores e superiores podem aprovar
        if (Gate::denies('pae.empreendimentos.approve')) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas gestores podem aprovar empreendimentos',
            ], 403);
        }

        // Lógica de aprovação
    }
}
```

### Em Blade Views (se usar)

```blade
@can('pae.empreendimentos.create')
    <button>Criar Novo Empreendimento</button>
@endcan

@cannot('users.delete')
    <p>Você não tem permissão para deletar usuários</p>
@endcannot
```

### Diretamente no Code

```php
// Verificar role
if (auth()->user()->hasRole('admin')) {
    // Usuário é admin
}

// Verificar qualquer role
if (auth()->user()->hasAnyRole(['admin', 'manager'])) {
    // Usuário é admin OU manager
}

// Verificar permission
if (auth()->user()->hasPermission('users.delete')) {
    // Usuário pode deletar usuários
}

// Verificar qualquer permission
if (auth()->user()->hasAnyPermission(['users.edit', 'users.delete'])) {
    // Usuário pode editar OU deletar
}

// Obter todas as permissions
$permissions = auth()->user()->getAllPermissions();
// ['users.view', 'users.create', ...]
```

---

## Uso de Middlewares

### Proteger Rotas com Role

```php
// routes/api.php

// Apenas Admins
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::apiResource('/admin/users', UserController::class);
});

// Admins OU Managers
Route::middleware(['auth:sanctum', 'role:admin,manager'])->group(function () {
    Route::post('/empreendimentos/{id}/approve', [EmpreendimentoController::class, 'approve']);
});

// Super Admin apenas
Route::middleware(['auth:sanctum', 'role:super-admin'])->group(function () {
    Route::post('/system/reset', [SystemController::class, 'reset']);
});
```

### Proteger Rotas com Permission

```php
// Apenas quem pode visualizar usuários
Route::middleware(['auth:sanctum', 'permission:users.view'])->get('/users', [UserController::class, 'index']);

// Múltiplas permissions (OR)
Route::middleware(['auth:sanctum', 'permission:users.edit,users.delete'])->group(function () {
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

// PAE: criar OU editar
Route::middleware(['auth:sanctum', 'permission:pae.empreendimentos.create,pae.empreendimentos.edit'])->group(function () {
    Route::post('/pae/empreendimentos', [EmpreendimentoController::class, 'store']);
    Route::put('/pae/empreendimentos/{id}', [EmpreendimentoController::class, 'update']);
});
```

### Combinar Múltiplos Middlewares

```php
// Deve ser Admin E ter permissão de deletar
Route::middleware(['auth:sanctum', 'role:admin', 'permission:users.delete'])
    ->delete('/users/{id}', [UserController::class, 'destroy']);
```

### Usar Gate Middleware (can)

```php
// Usando gate
Route::middleware(['auth:sanctum', 'can:pae.empreendimentos.approve'])
    ->post('/empreendimentos/{id}/approve', [EmpreendimentoController::class, 'approve']);
```

---

## Exemplos Práticos

### Exemplo 1: Criar Empreendimento (Analista)

**Usuário:** João (Analista)
**Permissões:** `pae.empreendimentos.view`, `pae.empreendimentos.create`, `pae.empreendimentos.edit`

**Request:**
```bash
POST /api/v1/pae/empreendimentos
Authorization: Bearer {token}
Content-Type: application/json

{
  "nome": "Empreendimento Teste",
  "descricao": "Descrição do empreendimento",
  "status": "rascunho"
}
```

**✅ Sucesso:** João pode criar porque tem `pae.empreendimentos.create`

---

### Exemplo 2: Aprovar Empreendimento (Gestor)

**Usuário:** Maria (Gestora)
**Permissões:** Inclui `pae.empreendimentos.approve`

**Request:**
```bash
POST /api/pae/empreendimentos/123/approve
Authorization: Bearer {token}
```

**Response (Controller):**
```php
public function approve($id)
{
    if (Gate::denies('pae.empreendimentos.approve')) {
        return response()->json([
            'success' => false,
            'message' => 'Apenas gestores podem aprovar',
        ], 403);
    }

    $empreendimento = Empreendimento::findOrFail($id);
    $empreendimento->update(['status' => 'aprovado']);

    return response()->json([
        'success' => true,
        'message' => 'Empreendimento aprovado',
    ]);
}
```

**✅ Sucesso:** Maria pode aprovar porque tem a permissão

---

### Exemplo 3: Deletar Usuário (Admin)

**Usuário:** Carlos (Admin)
**Permissões:** `users.delete`

**Request:**
```bash
DELETE /api/users/45
Authorization: Bearer {token}
```

**Rota protegida:**
```php
Route::middleware(['auth:sanctum', 'permission:users.delete'])
    ->delete('/users/{id}', [UserController::class, 'destroy']);
```

**✅ Sucesso:** Carlos pode deletar porque é Admin com `users.delete`

---

### Exemplo 4: Acesso Negado (Operador tentando deletar)

**Usuário:** Pedro (Operador)
**Permissões:** Não tem `users.delete`

**Request:**
```bash
DELETE /api/users/45
Authorization: Bearer {token}
```

**❌ Erro (403 Forbidden):**
```json
{
  "success": false,
  "message": "Forbidden - Insufficient permission",
  "required_permissions": ["users.delete"]
}
```

---

## API Reference

### Endpoints de Autenticação

| Método | Endpoint | Auth | Descrição |
|--------|----------|------|-----------|
| POST | `/api/auth/register` | ❌ | Registrar novo usuário |
| POST | `/api/auth/login` | ❌ | Login e obter token |
| POST | `/api/auth/logout` | ✅ | Logout (revoga token atual) |
| POST | `/api/auth/logout-all` | ✅ | Logout de todos os dispositivos |
| GET | `/api/auth/me` | ✅ | Dados do usuário autenticado |
| POST | `/api/auth/refresh` | ✅ | Renovar token |
| GET | `/api/auth/tokens` | ✅ | Listar todos os tokens ativos |
| DELETE | `/api/auth/tokens/{id}` | ✅ | Revogar token específico |

### Métodos do Model User

```php
// ROLES
$user->roles(); // Relationship
$user->hasRole('admin'); // bool
$user->hasAnyRole(['admin', 'manager']); // bool
$user->hasAllRoles(['admin', 'manager']); // bool
$user->assignRoles([1, 2]); // void
$user->removeRoles([1]); // void
$user->syncRoles([1, 2, 3]); // void

// PERMISSIONS
$user->hasPermission('users.view'); // bool
$user->hasAnyPermission(['users.view', 'users.edit']); // bool
$user->getAllPermissions(); // array

// TOKENS
$user->createTokenWithAbilities('token-name'); // NewAccessToken
$user->createTokenWithCustomAbilities('token-name', ['users.view']); // NewAccessToken
$user->tokens(); // Relationship
$user->currentAccessToken(); // PersonalAccessToken
```

### Métodos do Model Role

```php
$role->users(); // Relationship
$role->permissions(); // Relationship
$role->hasPermission('users.view'); // bool
$role->hasAnyPermission(['users.view', 'users.edit']); // bool
$role->hasAllPermissions(['users.view', 'users.edit']); // bool
$role->givePermissions([1, 2]); // void
$role->revokePermissions([1]); // void
$role->syncPermissions([1, 2, 3]); // void
$role->getPermissionSlugs(); // array
```

---

## Troubleshooting

### Erro: "Unauthenticated"

**Causa:** Token não foi enviado ou é inválido

**Solução:**
```bash
# Verificar se header está correto
Authorization: Bearer {token-completo}

# Verificar se token existe no banco
SELECT * FROM personal_access_tokens WHERE token = SHA256('{plain-text-token}');
```

### Erro: "Forbidden - Insufficient permission"

**Causa:** Usuário não tem a permissão necessária

**Solução:**
```php
// Verificar permissions do usuário
$user = User::find(1);
dd($user->getAllPermissions());

// Verificar roles
dd($user->roles);

// Atribuir permissão via role
$role = Role::find(4); // Analista
$permission = Permission::where('slug', 'pae.empreendimentos.create')->first();
$role->givePermissions([$permission->id]);
```

### Erro: "Call to undefined method hasRole()"

**Causa:** Model User não tem os métodos implementados

**Solução:** Verificar se User.php tem os métodos de roles/permissions implementados (já feito neste projeto)

### Token expira muito rápido

**Configuração:** Editar `config/sanctum.php`

```php
'expiration' => 60 * 24 * 30, // 30 dias
```

### Super Admin não bypass gates

**Causa:** Gate::before não configurado

**Solução:** Verificar `AuthServiceProvider.php` tem:

```php
Gate::before(function ($user, $ability) {
    if ($user->hasRole('super-admin')) {
        return true;
    }
});
```

---

## Segurança

### Boas Práticas

1. **Nunca expor tokens em URLs**
   ```bash
   # ❌ ERRADO
   GET /api/users?token=abc123

   # ✅ CORRETO
   GET /api/users
   Authorization: Bearer abc123
   ```

2. **Armazenar tokens com segurança no frontend**
   - ✅ `httpOnly cookies` (melhor)
   - ✅ `localStorage` com HTTPS
   - ❌ `sessionStorage` exposto

3. **Implementar rate limiting**
   ```php
   // Já implementado no projeto
   Route::middleware('throttle:60,1')->post('/auth/login');
   ```

4. **Revogar tokens antigos**
   ```php
   // Deletar tokens não usados há 30 dias
   PersonalAccessToken::where('last_used_at', '<', now()->subDays(30))->delete();
   ```

5. **Validar sempre a origem**
   ```php
   // Configurar CORS corretamente
   // config/cors.php
   ```

---

## Próximos Passos

### Funcionalidades Futuras

- [ ] **Two-Factor Authentication (2FA)**
- [ ] **OAuth2 Integration** (Google, Microsoft)
- [ ] **Audit Log** (registrar todas as ações)
- [ ] **IP Whitelisting** por role
- [ ] **Token Expiration** configurável por role
- [ ] **API Rate Limiting** por role
- [ ] **Dashboard de Permissões** (admin)

---

**Documentação criada em:** 10/12/2025
**Versão:** 1.0.0
**Autor:** Sistema de Autenticação SDC
**Laravel Version:** 11.x
**Sanctum Version:** 4.x
