# Setup de Super Admin - Guia Rápido

**Versão:** 1.0.0
**Data:** 2025-12-23

---

## Métodos para Tornar Usuário Super Admin

### Método 1: Script Automatizado (Recomendado)

#### Windows (PowerShell ou CMD)

```bash
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC
.\setup-superadmin.bat
```

O script irá:
1. Verificar se o Docker está rodando
2. Perguntar o email do usuário
3. Executar o comando Artisan no container
4. Criar o usuário se não existir
5. Atribuir o cargo Super Admin

#### Linux/Mac (Bash)

```bash
cd /path/to/NewSDC/SDC
./setup-superadmin.sh
```

---

### Método 2: Comando Artisan Direto

#### Dentro do Container Docker

```bash
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC

# Executar comando no container
docker compose exec app php artisan user:make-superadmin usuario@exemplo.com
```

#### Se o usuário não existir:

O comando irá perguntar:
1. Deseja criar este usuário? (y/n)
2. Nome do usuário
3. Senha (mínimo 8 caracteres)

---

### Método 3: Usando Justfile

Adicione ao `Justfile`:

```justfile
# Tornar usuário Super Admin
superadmin email:
    @echo "🔐 Tornando {{email}} Super Admin..."
    {{_app}} php artisan user:make-superadmin {{email}}
    @echo "✅ Pronto!"
```

**Uso:**
```bash
just superadmin usuario@exemplo.com
```

---

### Método 4: SQL Direto (Avançado)

Se preferir executar SQL manualmente:

```sql
-- 1. Encontrar o ID do usuário
SELECT id, name, email FROM users WHERE email = 'usuario@exemplo.com';
-- Anote o ID (ex: 1)

-- 2. Encontrar o ID do role Super Admin
SELECT id, name, slug FROM roles WHERE slug = 'super-admin';
-- Anote o ID (ex: 1)

-- 3. Atribuir role ao usuário
INSERT INTO role_user (user_id, role_id, created_at)
VALUES (1, 1, NOW())
ON DUPLICATE KEY UPDATE role_id = role_id;

-- 4. Verificar
SELECT u.name, u.email, r.name AS role
FROM users u
JOIN role_user ru ON u.id = ru.user_id
JOIN roles r ON ru.role_id = r.id
WHERE u.email = 'usuario@exemplo.com';
```

**Executar no Docker:**

```bash
docker compose exec db mysql -u root -p sdc_database

# Cole os comandos SQL acima
```

---

## Criar Usuário de Teste do Zero

### 1. Criar Usuário + Tornar Super Admin (Tudo de Uma Vez)

```bash
docker compose exec app php artisan user:make-superadmin teste@sdc.mg.gov.br
```

Quando perguntar se deseja criar:
- **Deseja criar este usuário?** Responder: `yes`
- **Nome do usuário:** Digitar: `Administrador Teste`
- **Senha:** Digitar: `SenhaForte@123`

---

### 2. Criar Múltiplos Usuários de Teste

Criar arquivo `database/seeders/TestUsersSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Super Admin Teste',
                'email' => 'superadmin@sdc.mg.gov.br',
                'password' => bcrypt('Admin@2025'),
                'role' => 'super-admin',
            ],
            [
                'name' => 'Admin Teste',
                'email' => 'admin@sdc.mg.gov.br',
                'password' => bcrypt('Admin@2025'),
                'role' => 'admin',
            ],
            [
                'name' => 'Gestor Teste',
                'email' => 'gestor@sdc.mg.gov.br',
                'password' => bcrypt('Gestor@2025'),
                'role' => 'manager',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::where('slug', $userData['role'])->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }

            $this->command->info("✅ Usuário criado: {$user->email} ({$userData['role']})");
        }
    }
}
```

**Executar:**

```bash
docker compose exec app php artisan db:seed --class=TestUsersSeeder
```

---

## Verificar se Funcionou

### 1. Verificar via Artisan

```bash
docker compose exec app php artisan tinker

# No tinker:
>>> $user = App\Models\User::where('email', 'usuario@exemplo.com')->first();
>>> $user->roles;
>>> $user->hasRole('super-admin');
>>> exit
```

### 2. Verificar via SQL

```bash
docker compose exec db mysql -u root -p sdc_database

# No MySQL:
SELECT
    u.id,
    u.name,
    u.email,
    r.name AS role,
    r.slug AS role_slug
FROM users u
LEFT JOIN role_user ru ON u.id = ru.user_id
LEFT JOIN roles r ON ru.role_id = r.id
WHERE u.email = 'usuario@exemplo.com';
```

### 3. Verificar via Interface Web

1. Acesse: `http://localhost/login`
2. Faça login com o usuário
3. Vá em: **Sidebar > Administração > Permissionamento**
4. Se acessar sem erro 403, está funcionando!

---

## Troubleshooting

### Erro: "Cargo Super Admin não encontrado"

**Solução:**

```bash
docker compose exec app php artisan db:seed --class=RolesAndPermissionsSeeder
```

### Erro: "Usuário já é Super Admin"

O usuário já possui o cargo. Para verificar:

```bash
docker compose exec app php artisan tinker

>>> $user = App\Models\User::where('email', 'usuario@exemplo.com')->first();
>>> $user->roles->pluck('slug');
```

### Erro: "SQLSTATE[42S02]: Base table or table 'roles' doesn't exist"

As migrations não foram executadas. Execute:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=RolesAndPermissionsSeeder
```

### Erro: "Connection refused" ao acessar Docker

O container não está rodando. Execute:

```bash
docker compose up -d
```

---

## Comandos Úteis

### Listar Todos os Usuários com seus Cargos

```bash
docker compose exec app php artisan tinker

>>> App\Models\User::with('roles')->get()->map(function($u) {
...     return [
...         'name' => $u->name,
...         'email' => $u->email,
...         'roles' => $u->roles->pluck('name')->join(', ')
...     ];
... });
```

### Remover Cargo de um Usuário

```bash
docker compose exec app php artisan tinker

>>> $user = App\Models\User::where('email', 'usuario@exemplo.com')->first();
>>> $user->roles()->detach(); // Remove todos os cargos
>>> $user->roles; // Verifica
```

### Ver Todas as Permissões do Super Admin

```bash
docker compose exec app php artisan tinker

>>> $superAdmin = App\Models\Role::where('slug', 'super-admin')->first();
>>> $superAdmin->permissions->pluck('slug');
```

---

## Referências

- **Comando Artisan:** `app/Console/Commands/MakeSuperAdmin.php`
- **Script Windows:** `setup-superadmin.bat`
- **Script Linux/Mac:** `setup-superadmin.sh`
- **Documentação Principal:** `Doc/MODULO_PERMISSIONAMENTO_UI.md`

---

**Documento gerado em:** 2025-12-23
**Versão:** 1.0.0
**Autor:** Sistema Automatizado
**Status:** COMPLETO
