# 🔐 Corrigir Problema de Login - Credenciais Não Funcionam

## 📊 Status Atual

✅ **Deploy funcionando** - CI/CD está ativo e deploy automático funcionando
❌ **Login falhando** - Erro: "These credentials do not match our records"

## 🔴 Problema

O sistema está retornando erro ao tentar fazer login, mesmo com as credenciais corretas.

**Erro exibido:**
```
These credentials do not match our records.
```

## 🔍 Causas Possíveis

### 1. Banco de Dados Não Inicializado

O banco de dados pode não ter sido inicializado no Azure App Service. As migrations e seeders podem não ter sido executadas.

### 2. Usuário Não Existe

O usuário de teste pode não ter sido criado no banco de dados.

## ✅ Solução

### Opção 1: Executar Migrations e Seeders Manualmente (Recomendado)

Conecte-se ao App Service via SSH ou Console e execute:

```bash
# 1. Conectar ao App Service via SSH
az webapp ssh --name newsdc2027 --resource-group DEFESA_CIVIL

# 2. Navegar para o diretório da aplicação
cd /home/site/wwwroot

# 3. Executar migrations
php artisan migrate --force

# 4. Executar seeders (cria o usuário de teste)
php artisan db:seed --force --class=DatabaseSeeder

# 5. Verificar se o usuário foi criado
php artisan tinker --execute="echo \App\Models\User::where('cpf', '12345678900')->first() ? 'Usuário existe' : 'Usuário não existe';"
```

### Opção 2: Via Azure Portal - Console Kudu

1. Acesse: https://newsdc2027.scm.azurewebsites.net
2. Vá em **Debug Console** → **Bash** ou **PowerShell**
3. Execute os comandos acima

### Opção 3: Criar Usuário Manualmente via Tinker

```bash
# Conectar ao App Service
az webapp ssh --name newsdc2027 --resource-group DEFESA_CIVIL

# Executar Tinker
php artisan tinker

# No Tinker, execute:
\App\Models\User::create([
    'name' => 'Admin Geral',
    'email' => 'admin@defesa.mg.gov.br',
    'cpf' => '12345678900',
    'password' => bcrypt('password'),
]);
```

## 📋 Credenciais de Login

Após executar os seeders, use estas credenciais:

- **CPF**: `12345678900` (sem formatação - digite apenas números)
- **Senha**: `password`

**IMPORTANTE**: 
- O CPF deve ser digitado **sem formatação** (apenas números)
- O sistema automaticamente formata visualmente (123.456.789-00)
- Mas internamente envia apenas os números (12345678900)

## 🔍 Verificar se Funcionou

### 1. Verificar se o usuário existe:

```bash
php artisan tinker --execute="\$user = \App\Models\User::where('cpf', '12345678900')->first(); echo \$user ? 'Usuário encontrado: ' . \$user->name : 'Usuário não encontrado';"
```

### 2. Testar login:

1. Acesse: https://newsdc2027.azurewebsites.net/login
2. Digite CPF: `12345678900` (o sistema formatará automaticamente)
3. Digite senha: `password`
4. Clique em "Acessar Sistema"

## 🐛 Troubleshooting

### Problema: "Connection refused" ao conectar ao banco

**Solução**: Verifique as variáveis de ambiente do App Service:
- `DB_HOST` - deve apontar para o servidor MySQL
- `DB_DATABASE` - nome do banco (geralmente `sdc`)
- `DB_USERNAME` - usuário do banco
- `DB_PASSWORD` - senha do banco

### Problema: "Table 'users' doesn't exist"

**Solução**: Execute as migrations:
```bash
php artisan migrate --force
```

### Problema: "User already exists" ao executar seeder

**Solução**: Isso é normal. O seeder verifica se o usuário já existe antes de criar.

### Problema: Login ainda não funciona após criar usuário

**Solução**: 
1. Verifique se o CPF está sendo enviado sem formatação
2. Verifique se a senha está correta (case-sensitive)
3. Limpe o cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## 📊 Status do Entrypoint

O entrypoint de produção (`entrypoint.prod.sh`) foi atualizado para:
- ✅ Executar migrations automaticamente se necessário
- ✅ Executar seeders se o usuário não existir
- ✅ Verificar se migrations já foram executadas antes de rodar novamente

**Nota**: O entrypoint só executa migrations/seeders na primeira inicialização ou se detectar que não foram executadas.

## 🎯 Próximos Passos

1. [ ] Conectar ao App Service via SSH
2. [ ] Executar migrations: `php artisan migrate --force`
3. [ ] Executar seeders: `php artisan db:seed --force --class=DatabaseSeeder`
4. [ ] Verificar se usuário foi criado
5. [ ] Testar login com CPF: `12345678900` e senha: `password`
6. [ ] Confirmar que login funciona

---

**Data**: 10/12/2025
**Status**: Aguardando execução de migrations/seeders no App Service

