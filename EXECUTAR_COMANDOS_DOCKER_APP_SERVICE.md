# 🐳 Executar Comandos no Container Docker do App Service

## 🔍 Problema

Quando você acessa via SSH no Azure App Service, você está no **sistema host (Kudu)**, não dentro do **container Docker** onde sua aplicação está rodando.

Por isso `php artisan` não funciona diretamente - o PHP está dentro do container, não no host.

## ✅ Solução: Executar Comandos Dentro do Container

### Método 1: Via SSH do App Service (Recomendado)

1. **Conectar ao App Service via SSH:**
```bash
az webapp ssh --name newsdc2027 --resource-group DEFESA_CIVIL
```

2. **Listar containers em execução:**
```bash
docker ps
```

Você verá algo como:
```
CONTAINER ID   IMAGE                                    STATUS
4cc6edc29437   apidover.azurecr.io/sdc-dev-app:latest  Up 2 minutes
```

3. **Executar comando dentro do container:**
```bash
# Substitua CONTAINER_ID pelo ID do container
docker exec -it CONTAINER_ID php artisan app:create-test-user --fix
```

**Exemplo completo:**
```bash
# 1. Listar containers
docker ps

# 2. Executar comando (use o CONTAINER_ID que apareceu)
docker exec -it 4cc6edc29437 php artisan app:create-test-user --fix

# OU usar o nome do container (se disponível)
docker exec -it $(docker ps -q) php artisan app:create-test-user --fix
```

---

### Método 2: Via Kudu Console (Navegador)

1. **Acesse o Kudu Console:**
   - https://newsdc2027.scm.azurewebsites.net
   - Clique em **"Debug Console"** → **"Bash"**

2. **Execute os mesmos comandos:**
```bash
# Listar containers
docker ps

# Executar comando no container
docker exec -it $(docker ps -q) php artisan app:create-test-user --fix
```

---

## 📋 Comandos Úteis

### Verificar se container está rodando
```bash
docker ps
```

### Ver logs do container
```bash
docker logs $(docker ps -q)
# OU
docker logs -f $(docker ps -q)  # Seguir logs em tempo real
```

### Executar comandos Artisan
```bash
# Verificar/corrigir usuário
docker exec -it $(docker ps -q) php artisan app:create-test-user --fix

# Executar migrations
docker exec -it $(docker ps -q) php artisan migrate --force

# Executar seeders
docker exec -it $(docker ps -q) php artisan db:seed --force

# Verificar versão do Laravel
docker exec -it $(docker ps -q) php artisan --version

# Acessar Tinker
docker exec -it $(docker ps -q) php artisan tinker
```

### Verificar variáveis de ambiente do container
```bash
docker exec $(docker ps -q) env | grep DB_
```

### Acessar shell interativo do container
```bash
docker exec -it $(docker ps -q) sh
# OU
docker exec -it $(docker ps -q) bash
```

Dentro do shell do container:
```bash
cd /var/www
php artisan app:create-test-user --fix
exit
```

---

## 🎯 Script Completo para Corrigir Usuário

Execute este script completo no SSH do App Service:

```bash
# 1. Listar containers
echo "📋 Containers em execução:"
docker ps

# 2. Obter ID do container
CONTAINER_ID=$(docker ps -q)
echo "🐳 Container ID: $CONTAINER_ID"

# 3. Verificar se container existe
if [ -z "$CONTAINER_ID" ]; then
    echo "❌ Nenhum container encontrado!"
    exit 1
fi

# 4. Executar comando para verificar/corrigir usuário
echo "🔧 Executando comando no container..."
docker exec -it $CONTAINER_ID php artisan app:create-test-user --fix

# 5. Verificar logs se necessário
echo "📋 Últimas linhas dos logs:"
docker logs --tail 20 $CONTAINER_ID
```

---

## 🔍 Troubleshooting

### Problema: "docker: command not found"

**Solução**: Você está no lugar errado. Use o SSH do App Service:
```bash
az webapp ssh --name newsdc2027 --resource-group DEFESA_CIVIL
```

### Problema: "No containers running"

**Solução**: O container pode não ter iniciado. Verifique:
```bash
# Ver todos os containers (incluindo parados)
docker ps -a

# Ver logs do último container
docker logs $(docker ps -aq | head -1)
```

### Problema: "Cannot connect to the Docker daemon"

**Solução**: Isso não deve acontecer no App Service. Se acontecer, reinicie o App Service:
```bash
az webapp restart --name newsdc2027 --resource-group DEFESA_CIVIL
```

### Problema: "exec: \"php\": executable file not found"

**Solução**: O PHP pode não estar no PATH do container. Tente:
```bash
# Usar caminho completo
docker exec -it $(docker ps -q) /usr/local/bin/php artisan app:create-test-user --fix

# OU verificar onde está o PHP
docker exec -it $(docker ps -q) which php
```

---

## 📝 Exemplo Completo de Sessão

```bash
# 1. Conectar ao App Service
az webapp ssh --name newsdc2027 --resource-group DEFESA_CIVIL

# 2. Listar containers
docker ps
# Output:
# CONTAINER ID   IMAGE                                    STATUS
# 4cc6edc29437   apidover.azurecr.io/sdc-dev-app:latest  Up 5 minutes

# 3. Executar comando no container
docker exec -it 4cc6edc29437 php artisan app:create-test-user --fix

# Output esperado:
# ✅ Usuário encontrado (ID: 1)
# ✅ CPF correto: '12345678900'
# ✅ Senha 'password' está correta
# 
# 📋 Dados do usuário:
#    ID: 1
#    Nome: Admin Geral
#    Email: admin@defesa.mg.gov.br
#    CPF: '12345678900' (length: 11)
#    Senha 'password': CORRETA ✅

# 4. Sair
exit
```

---

## 🎯 Resumo Rápido

**Para executar comandos PHP/Artisan no App Service com Docker:**

```bash
# 1. Conectar
az webapp ssh --name newsdc2027 --resource-group DEFESA_CIVIL

# 2. Executar no container
docker exec -it $(docker ps -q) php artisan app:create-test-user --fix
```

**Pronto!** ✅

---

**Data**: 10/12/2025  
**App Service**: newsdc2027  
**Container**: Docker (apidover.azurecr.io/sdc-dev-app)


