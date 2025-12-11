# 🔍 Verificar Usuário no Banco de Dados

## Problema

O login está falhando mesmo com as credenciais corretas:
- CPF: `12345678900`
- Senha: `password`

## 🔧 Solução: Verificar e Corrigir o Usuário

### Passo 1: Conectar ao App Service

```bash
az webapp ssh --name newsdc2027 --resource-group DEFESA_CIVIL
```

### Passo 2: Verificar se o usuário existe

```bash
cd /home/site/wwwroot
php artisan tinker
```

No Tinker, execute:

```php
// Verificar se usuário existe
$user = \App\Models\User::where('cpf', '12345678900')->first();

if ($user) {
    echo "Usuário encontrado:\n";
    echo "ID: " . $user->id . "\n";
    echo "Nome: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "CPF: " . $user->cpf . "\n";
    echo "CPF length: " . strlen($user->cpf) . "\n";
    echo "CPF bytes: " . bin2hex($user->cpf) . "\n";
    
    // Verificar senha
    $passwordOk = \Hash::check('password', $user->password);
    echo "Senha 'password' está correta: " . ($passwordOk ? 'SIM' : 'NÃO') . "\n";
} else {
    echo "Usuário NÃO encontrado!\n";
}
```

### Passo 3: Verificar todos os usuários

```php
// Listar todos os usuários
$users = \App\Models\User::all();
foreach ($users as $user) {
    echo "ID: {$user->id} | CPF: '{$user->cpf}' (length: " . strlen($user->cpf) . ") | Nome: {$user->name}\n";
}
```

### Passo 4: Corrigir o CPF se necessário

Se o CPF estiver com formatação ou espaços:

```php
// Atualizar CPF removendo formatação
$user = \App\Models\User::where('cpf', 'like', '%12345678900%')->first();
if ($user) {
    $user->cpf = '12345678900';
    $user->save();
    echo "CPF corrigido!\n";
}
```

### Passo 5: Recriar o usuário se necessário

Se o usuário não existir ou estiver com problemas:

```php
// Deletar usuário antigo se existir
\App\Models\User::where('cpf', '12345678900')->delete();

// Criar novo usuário
$user = \App\Models\User::create([
    'name' => 'Admin Geral',
    'email' => 'admin@defesa.mg.gov.br',
    'cpf' => '12345678900', // SEM formatação, apenas números
    'password' => \Hash::make('password'),
]);

echo "Usuário criado com sucesso!\n";
echo "ID: " . $user->id . "\n";
echo "CPF: " . $user->cpf . "\n";
```

### Passo 6: Verificar senha

```php
$user = \App\Models\User::where('cpf', '12345678900')->first();
if ($user) {
    // Testar diferentes variações de senha
    $passwords = ['password', 'Password', 'PASSWORD'];
    foreach ($passwords as $pwd) {
        $check = \Hash::check($pwd, $user->password);
        echo "Senha '{$pwd}': " . ($check ? 'CORRETA' : 'incorreta') . "\n";
    }
}
```

## 🐛 Problemas Comuns

### Problema 1: CPF com formatação no banco

**Sintoma**: CPF armazenado como `123.456.789-00` ao invés de `12345678900`

**Solução**:
```php
// No Tinker
$user = \App\Models\User::where('cpf', 'like', '%12345678900%')->first();
if ($user) {
    $user->cpf = '12345678900';
    $user->save();
}
```

### Problema 2: CPF com espaços

**Sintoma**: CPF armazenado como ` 12345678900 ` (com espaços)

**Solução**:
```php
// No Tinker
$user = \App\Models\User::where('cpf', 'like', '%12345678900%')->first();
if ($user) {
    $user->cpf = trim($user->cpf);
    $user->save();
}
```

### Problema 3: Senha não está hasheada corretamente

**Sintoma**: Senha armazenada como texto plano

**Solução**:
```php
// No Tinker
$user = \App\Models\User::where('cpf', '12345678900')->first();
if ($user) {
    $user->password = \Hash::make('password');
    $user->save();
}
```

### Problema 4: Múltiplos usuários com CPF similar

**Sintoma**: Vários usuários encontrados

**Solução**:
```php
// No Tinker - Listar todos
$users = \App\Models\User::where('cpf', 'like', '%12345678900%')->get();
foreach ($users as $user) {
    echo "ID: {$user->id} | CPF: '{$user->cpf}'\n";
}

// Deletar duplicados e manter apenas um
$users = \App\Models\User::where('cpf', 'like', '%12345678900%')->get();
if ($users->count() > 1) {
    // Manter o primeiro, deletar os outros
    $keep = $users->first();
    $users->where('id', '!=', $keep->id)->each->delete();
    echo "Duplicados removidos!\n";
}
```

## ✅ Script Completo de Verificação

Execute este script completo no Tinker:

```php
// 1. Verificar se usuário existe
$user = \App\Models\User::where('cpf', '12345678900')->first();

if (!$user) {
    echo "❌ Usuário não encontrado. Criando...\n";
    $user = \App\Models\User::create([
        'name' => 'Admin Geral',
        'email' => 'admin@defesa.mg.gov.br',
        'cpf' => '12345678900',
        'password' => \Hash::make('password'),
    ]);
    echo "✅ Usuário criado!\n";
} else {
    echo "✅ Usuário encontrado!\n";
}

// 2. Verificar e corrigir CPF
if ($user->cpf !== '12345678900') {
    echo "⚠️  CPF incorreto: '{$user->cpf}'. Corrigindo...\n";
    $user->cpf = '12345678900';
    $user->save();
    echo "✅ CPF corrigido!\n";
}

// 3. Verificar senha
$passwordOk = \Hash::check('password', $user->password);
if (!$passwordOk) {
    echo "⚠️  Senha incorreta. Corrigindo...\n";
    $user->password = \Hash::make('password');
    $user->save();
    echo "✅ Senha corrigida!\n";
}

// 4. Verificação final
echo "\n📋 Dados finais do usuário:\n";
echo "ID: {$user->id}\n";
echo "Nome: {$user->name}\n";
echo "Email: {$user->email}\n";
echo "CPF: '{$user->cpf}' (length: " . strlen($user->cpf) . ")\n";
echo "Senha 'password' está correta: " . (\Hash::check('password', $user->password) ? 'SIM ✅' : 'NÃO ❌') . "\n";
```

## 🎯 Teste Final

Após executar o script acima:

1. Saia do Tinker: `exit`
2. Acesse: https://newsdc2027.azurewebsites.net/login
3. Digite CPF: `12345678900` (o sistema formatará automaticamente)
4. Digite senha: `password`
5. Clique em "Acessar Sistema"

O login deve funcionar agora! ✅

---

**Data**: 10/12/2025
**Status**: Aguardando verificação e correção do usuário no banco


