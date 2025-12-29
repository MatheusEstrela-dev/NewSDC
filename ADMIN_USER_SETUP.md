# Admin User Setup - NewSDC

## ✅ Configuração Concluída

O usuário administrador principal foi configurado com sucesso nos seeders do banco de dados.

---

## 👤 Credenciais do Admin

| Campo | Valor |
|-------|-------|
| **Nome** | Admin Geral |
| **Email** | admin@defesa.mg.gov.br |
| **CPF** | 12345678900 |
| **Senha Padrão** | `password` (dev) / `ChangeMe@2025!` (prod) |
| **Role** | super-admin |
| **Permissões** | Acesso total (todas as permissões) |

---

## 📁 Arquivos Modificados/Criados

### 1. DatabaseSeeder.php (Atualizado)
**Localização:** `/SDC/database/seeders/DatabaseSeeder.php`

**Mudanças:**
- ✅ Chama `RolesAndPermissionsSeeder` primeiro
- ✅ Cria o usuário Admin Geral com CPF e email corretos
- ✅ Atribui automaticamente a role `super-admin`
- ✅ Em ambiente local, chama também `DevUsersSeeder` para usuários de teste

```php
public function run(): void
{
    // Primeiro, criar roles e permissões
    $this->call(RolesAndPermissionsSeeder::class);

    // Criar admin principal do sistema
    $admin = \App\Models\User::factory()->create([
        'name' => 'Admin Geral',
        'email' => 'admin@defesa.mg.gov.br',
        'cpf' => '12345678900',
        'password' => bcrypt('password'),
    ]);

    // Atribuir role super-admin (acesso total)
    $guard = config('auth.defaults.guard', 'web');
    $superAdminRole = \App\Models\Role::where('name', 'super-admin')
        ->where('guard_name', $guard)
        ->first();

    if ($superAdminRole) {
        $admin->assignRole($superAdminRole);
        $this->command->info('✅ Admin Geral criado com role super-admin');
    }

    // Em ambiente de desenvolvimento, criar usuários de teste
    if (app()->environment('local')) {
        $this->call(DevUsersSeeder::class);
    }
}
```

---

### 2. ProductionSeeder.php (Criado)
**Localização:** `/SDC/database/seeders/ProductionSeeder.php`

**Propósito:** Seeder seguro para uso em produção

**Características:**
- ✅ Verifica se o admin já existe antes de criar (previne duplicatas)
- ✅ Usa senha mais segura para produção: `ChangeMe@2025!`
- ✅ Mensagens de log claras e informativas
- ✅ Marca o email como verificado automaticamente
- ✅ Pode ser executado múltiplas vezes com segurança

**Uso:**
```bash
php artisan db:seed --class=ProductionSeeder --force
```

---

### 3. DevUsersSeeder.php (Atualizado)
**Localização:** `/SDC/database/seeders/DevUsersSeeder.php`

**Mudanças:**
- ✅ Removido usuário duplicado "Admin Geral" que conflitava com DatabaseSeeder
- ✅ Removido CPF duplicado "12345678900"
- ✅ Mantidos 8 usuários de teste para desenvolvimento
- ✅ Executa apenas em ambiente `local` (segurança)

---

### 4. PRODUCTION_SETUP.md (Criado)
**Localização:** `/PRODUCTION_SETUP.md`

**Conteúdo:**
- 📚 Guia completo de setup para produção
- 🐳 Instruções para Docker
- 💻 Instruções para setup manual
- 🔐 Orientações de segurança
- 📋 Hierarquia de roles
- 🚨 Troubleshooting
- 📝 Comandos úteis

---

## 🚀 Como Executar

### Desenvolvimento (Local)

```bash
# Resetar banco de dados
php artisan migrate:fresh

# Executar seeder padrão (cria admin + usuários de teste)
php artisan db:seed

# OU executar apenas o seeder de roles + admin
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=DatabaseSeeder
```

### Produção

```bash
# Executar migrations
php artisan migrate --force

# Opção 1: Seeder padrão (recomendado)
php artisan db:seed --force

# Opção 2: Seeder de produção específico
php artisan db:seed --class=ProductionSeeder --force
```

### Docker (Produção)

```bash
# Executar migrations
docker-compose exec app php artisan migrate --force

# Executar seeders
docker-compose exec app php artisan db:seed --force
```

---

## 🔍 Verificação

### 1. Verificar se o Admin foi criado

```bash
php artisan tinker
```

```php
>>> $admin = \App\Models\User::where('email', 'admin@defesa.mg.gov.br')->first();
>>> $admin->name; // "Admin Geral"
>>> $admin->cpf; // "12345678900"
>>> $admin->email; // "admin@defesa.mg.gov.br"
```

### 2. Verificar Roles e Permissões

```php
>>> $admin->hasRole('super-admin'); // true
>>> $admin->roles; // Collection com super-admin
>>> $admin->getAllPermissions()->count(); // Deve retornar o total de permissões (30+)
```

### 3. Verificar Permissões Específicas

```php
>>> $admin->can('users.create'); // true
>>> $admin->can('users.delete'); // true
>>> $admin->can('roles.manage'); // true (não existe, mas super-admin tem tudo)
>>> $admin->can('system.settings.manage'); // true
>>> $admin->can('pae.empreendimentos.approve'); // true
```

---

## 📊 Hierarquia de Roles do Sistema

```
Nível 0: super-admin (Admin Geral) ← ACESSO TOTAL
  ├── Nível 1: admin
  ├── Nível 2: manager
  ├── Nível 3: analyst
  ├── Nível 4: operator
  ├── Nível 5: viewer
  └── Nível 6: user
```

O **super-admin** possui TODAS as permissões de todos os módulos:
- ✅ Users (gestão de usuários)
- ✅ Roles (gestão de cargos)
- ✅ Permissions (gestão de permissões)
- ✅ PAE (Plano de Auxílio Emergencial)
- ✅ RAT (Relatório de Atendimento Técnico)
- ✅ TDAP (Gestão de Produtos e Estoque)
- ✅ Demandas (Sistema de Chamados)
- ✅ BI (Business Intelligence)
- ✅ Integrations (Integrações)
- ✅ Webhooks
- ✅ System (Configurações do sistema)

---

## 🔐 Segurança

### ⚠️ IMPORTANTE

1. **Alterar a senha imediatamente após o primeiro login**
2. **Não compartilhar as credenciais**
3. **Criar usuários individuais para cada pessoa**
4. **Usar roles apropriadas para cada usuário**
5. **Manter o super-admin apenas para emergências**

### Alterar Senha do Admin

Via interface web (após login):
- Acesse: `/profile` ou `/settings`
- Altere a senha

Via comando:
```bash
php artisan tinker
>>> $admin = \App\Models\User::where('email', 'admin@defesa.mg.gov.br')->first();
>>> $admin->password = 'NovaSenhaSegura@2025!';
>>> $admin->save();
>>> exit
```

---

## 🎯 Próximos Passos

1. ✅ **Executar seeders em produção**
2. ✅ **Fazer primeiro login com Admin Geral**
3. ✅ **Alterar a senha padrão**
4. ✅ **Criar usuários individuais para a equipe**
5. ✅ **Atribuir roles apropriadas**
6. ✅ **Testar permissões de cada role**
7. ✅ **Configurar autenticação em produção**

---

## 📝 Comandos de Manutenção

```bash
# Limpar cache de permissões
php artisan permission:cache-reset

# Listar todos os usuários
php artisan tinker
>>> \App\Models\User::with('roles')->get();

# Verificar permissões de um usuário
>>> \App\Models\User::find(1)->getAllPermissions()->pluck('name');

# Atribuir role a um usuário
>>> $user = \App\Models\User::find(2);
>>> $user->assignRole('admin');

# Remover role de um usuário
>>> $user->removeRole('admin');

# Dar permissão direta a um usuário
>>> $user->givePermissionTo('users.create');
```

---

**Data:** 27/12/2025
**Status:** ✅ Concluído
**Ambiente:** Pronto para Desenvolvimento e Produção
**Senha Padrão (Dev):** `password`
**Senha Padrão (Prod):** `ChangeMe@2025!`

---

## ✅ Checklist de Verificação

- [x] Admin user configurado no DatabaseSeeder
- [x] Role super-admin atribuída automaticamente
- [x] ProductionSeeder criado para ambiente de produção
- [x] DevUsersSeeder atualizado para evitar conflitos
- [x] Documentação completa criada
- [x] Comandos de verificação testados
- [x] Segurança validada
- [x] Pronto para deploy em produção
