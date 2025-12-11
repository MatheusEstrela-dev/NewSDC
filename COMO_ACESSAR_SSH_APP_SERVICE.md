# 🔐 Como Acessar SSH do Azure App Service

## 📋 Métodos para Acessar o SSH

### Método 1: Azure CLI (Recomendado)

#### Pré-requisitos

- Azure CLI instalado
- Login realizado no Azure

#### Passos

1. **Verificar se está logado:**

```bash
az account show
```

2. **Se não estiver logado, fazer login:**

```bash
az login
```

3. **Conectar ao App Service via SSH:**

```bash
az webapp ssh --name newsdc2027 --resource-group DEFESA_CIVIL
```

4. **Se pedir confirmação, digite `y` ou `yes`**

5. **Você estará conectado! Navegue para o diretório da aplicação:**

```bash
cd /home/site/wwwroot
```

---

### Método 2: Azure Portal - Console Kudu

#### Passos

1. **Acesse o Azure Portal:**

   - https://portal.azure.com

2. **Navegue até o App Service:**

   - **App Services** → **newsdc2027**

3. **Acesse o Console Kudu:**

   - No menu lateral, procure por **"Advanced Tools"** ou **"Ferramentas Avançadas"**
   - Clique em **"Go"** ou **"Ir"**
   - OU acesse diretamente: https://newsdc2027.scm.azurewebsites.net

4. **Abra o Console:**

   - Clique em **"Debug Console"** → **"Bash"** ou **"PowerShell"**

5. **Navegue para o diretório da aplicação:**

```bash
cd /home/site/wwwroot
```

---

### Método 3: Via Browser (SSH Web)

#### Passos

1. **Acesse diretamente:**

   - https://newsdc2027.scm.azurewebsites.net/webssh/host

2. **Você será redirecionado para uma interface web SSH**

3. **Digite os comandos diretamente no terminal web**

---

## 🎯 Comandos Úteis Após Conectar

### Verificar diretório atual

```bash
pwd
# Deve mostrar: /home/site/wwwroot
```

### Listar arquivos

```bash
ls -la
```

### Verificar se Laravel está instalado

```bash
php artisan --version
```

### Verificar variáveis de ambiente

```bash
env | grep DB_
```

### Executar o comando para verificar/corrigir usuário (DOCKER)

**⚠️ IMPORTANTE**: Como a aplicação roda em Docker, você precisa executar dentro do container:

```bash
# 1. Listar containers
docker ps

# 2. Executar comando no container
docker exec -it $(docker ps -q) php artisan app:create-test-user --fix
```

**Ver guia completo**: `EXECUTAR_COMANDOS_DOCKER_APP_SERVICE.md`

### Verificar logs do Laravel

```bash
tail -f storage/logs/laravel.log
```

### Verificar se o usuário existe no banco

```bash
php artisan tinker
```

No Tinker:

```php
\App\Models\User::where('cpf', '12345678900')->first();
exit
```

---

## 🐛 Troubleshooting

### Problema: "az: command not found"

**Solução**: Instale o Azure CLI:

- **Windows**: Baixe do site oficial ou use: `winget install -e --id Microsoft.AzureCLI`
- **Linux**: `curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash`
- **Mac**: `brew install azure-cli`

### Problema: "az login" não funciona

**Solução**:

1. Tente: `az login --use-device-code`
2. Ou abra o navegador e faça login manualmente

### Problema: "Resource not found" ao conectar

**Solução**: Verifique o nome do App Service e Resource Group:

```bash
# Listar App Services
az webapp list --resource-group DEFESA_CIVIL --query "[].name" -o table

# Verificar se o App Service existe
az webapp show --name newsdc2027 --resource-group DEFESA_CIVIL
```

### Problema: "SSH not enabled"

**Solução**:

1. No Azure Portal, vá em **Configuration** → **General settings**
2. Ative **"SSH"** ou **"Always On"**
3. Salve as alterações

### Problema: Não consigo navegar para /home/site/wwwroot

**Solução**:

```bash
# Verificar onde você está
pwd

# Listar diretórios
ls -la

# Tentar navegar
cd /home
cd site
cd wwwroot

# OU usar caminho completo
cd /home/site/wwwroot
```

---

## 📝 Exemplo Completo de Sessão SSH

```bash
# 1. Conectar
az webapp ssh --name newsdc2027 --resource-group DEFESA_CIVIL

# 2. Aguardar conexão (pode demorar alguns segundos)
# Você verá algo como:
# Welcome to Azure App Service on Linux
# ...

# 3. Navegar para o diretório da aplicação
cd /home/site/wwwroot

# 4. Verificar se está no lugar certo
pwd
# Output: /home/site/wwwroot

# 5. Verificar versão do Laravel
php artisan --version

# 6. Executar comando para verificar usuário
php artisan app:create-test-user --fix

# 7. Sair quando terminar
exit
```

---

## 🔗 Links Úteis

- **Azure Portal**: https://portal.azure.com
- **Kudu Console**: https://newsdc2027.scm.azurewebsites.net
- **SSH Web**: https://newsdc2027.scm.azurewebsites.net/webssh/host
- **Documentação Azure CLI**: https://docs.microsoft.com/cli/azure/

---

## ✅ Checklist Rápido

- [ ] Azure CLI instalado
- [ ] Login realizado (`az login`)
- [ ] Conectado ao App Service (`az webapp ssh`)
- [ ] Navegado para `/home/site/wwwroot`
- [ ] Executado `php artisan app:create-test-user --fix`
- [ ] Verificado que usuário foi criado/corrigido
- [ ] Testado login no navegador

---

**Data**: 10/12/2025
**App Service**: newsdc2027
**Resource Group**: DEFESA_CIVIL
