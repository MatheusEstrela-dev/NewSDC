# Setup de Produção - NewSDC

## Configuração do Usuário Administrador

### Credenciais do Admin Principal

- **Nome:** Admin Geral
- **Email:** admin@defesa.mg.gov.br
- **CPF:** 12345678900
- **Senha Inicial:** `ChangeMe@2025!` ⚠️ **ALTERAR NO PRIMEIRO LOGIN**
- **Role:** super-admin (acesso total ao sistema)

---

## 🐳 Setup via Docker (Recomendado para Produção)

### 1. Preparar o Ambiente

```bash
cd SDC
cp .env.example .env
```

Edite o `.env` e configure:
```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=newsdc
DB_USERNAME=newsdc_user
DB_PASSWORD=<senha_segura>
```

### 2. Executar Migrations e Seeders

```bash
# Subir containers
docker-compose up -d

# Executar migrations
docker-compose exec app php artisan migrate --force

# Executar seeder de produção (cria admin + roles)
docker-compose exec app php artisan db:seed --class=ProductionSeeder --force

# OU executar seeder padrão (inclui admin + roles)
docker-compose exec app php artisan db:seed --force
```

### 3. Verificar Criação do Admin

```bash
docker-compose exec app php artisan tinker
```

```php
>>> $admin = \App\Models\User::where('email', 'admin@defesa.mg.gov.br')->first();
>>> $admin->roles; // Deve mostrar super-admin
>>> $admin->getAllPermissions()->count(); // Deve mostrar todas as permissões
```

---

## 💻 Setup Manual (Sem Docker)

### 1. Executar Migrations

```bash
cd SDC
php artisan migrate --force
```

### 2. Executar Seeders

```bash
# Opção 1: Seeder principal (recomendado)
php artisan db:seed --force

# Opção 2: Seeder de produção específico
php artisan db:seed --class=ProductionSeeder --force
```

---

## 🔐 Segurança Pós-Instalação

### 1. Alterar Senha do Admin

**IMPORTANTE:** A senha padrão (`ChangeMe@2025!`) deve ser alterada imediatamente após o primeiro login.

### 2. Verificar Permissões

Execute no tinker:

```bash
php artisan tinker
```

```php
>>> $admin = \App\Models\User::where('email', 'admin@defesa.mg.gov.br')->first();
>>> $admin->hasRole('super-admin'); // true
>>> $admin->can('users.create'); // true
>>> $admin->can('system.settings.manage'); // true
```

### 3. Criar Usuários Adicionais

Após login como admin, acesse:
- **Gestão de Usuários:** `/users`
- **Gestão de Roles:** `/roles`
- **Gestão de Permissões:** `/permissions`

---

## 📋 Hierarquia de Roles

O sistema possui 7 níveis de acesso:

| Nível | Role | Descrição |
|-------|------|-----------|
| 0 | super-admin | Acesso total (Admin Geral) |
| 1 | admin | Administrador geral |
| 2 | manager | Gestor de área |
| 3 | analyst | Analista técnico |
| 4 | operator | Operador de sistema |
| 5 | viewer | Somente leitura |
| 6 | user | Usuário padrão |

---

## 🔄 Resetar Senha do Admin (Emergência)

Se você perder a senha do admin, execute:

```bash
php artisan tinker
```

```php
>>> $admin = \App\Models\User::where('email', 'admin@defesa.mg.gov.br')->first();
>>> $admin->password = 'NovaSenhaSegura@2025';
>>> $admin->save();
>>> exit
```

---

## 📊 Módulos com Permissões

O admin tem acesso completo a todos os módulos:

### ✅ Módulos Disponíveis

1. **Users** - Gestão de usuários
2. **Roles** - Gestão de cargos
3. **Permissions** - Gestão de permissões
4. **PAE** - Plano de Auxílio Emergencial
5. **RAT** - Relatório de Atendimento Técnico
6. **TDAP** - Gestão de Produtos e Estoque
7. **Demandas** - Sistema de Chamados/Tasks
8. **BI** - Business Intelligence
9. **Integrations** - Integrações externas
10. **System** - Administração do sistema

---

## 🚨 Troubleshooting

### Admin não consegue fazer login

```bash
# Verificar se o usuário existe
php artisan tinker
>>> \App\Models\User::where('email', 'admin@defesa.mg.gov.br')->exists(); // true

# Verificar role
>>> $admin = \App\Models\User::where('email', 'admin@defesa.mg.gov.br')->first();
>>> $admin->roles; // Deve mostrar super-admin
```

### Admin não tem permissões

```bash
php artisan cache:clear
php artisan permission:cache-reset
php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

Depois, reatribuir a role:

```bash
php artisan tinker
>>> $admin = \App\Models\User::where('email', 'admin@defesa.mg.gov.br')->first();
>>> $admin->assignRole('super-admin');
>>> exit
```

---

## 📝 Comandos Úteis

```bash
# Listar todos os usuários
php artisan tinker
>>> \App\Models\User::with('roles')->get();

# Listar todas as roles
>>> \App\Models\Role::with('permissions')->get();

# Listar permissões de um usuário
>>> \App\Models\User::find(1)->getAllPermissions()->pluck('name');

# Limpar cache de permissões
php artisan permission:cache-reset
```

---

**Gerado em:** 27/12/2025
**Versão:** 1.0
**Sistema:** NewSDC
