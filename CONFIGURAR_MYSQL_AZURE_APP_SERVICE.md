# 🗄️ Configurar MySQL no Azure App Service

## 📊 Situação Atual

- ✅ **Local (Docker Compose)**: MySQL rodando em container separado (`db`)
- ❌ **Azure App Service**: Usando SQLite (não tem MySQL configurado)
- ⚠️ **Problema**: Aplicação não consegue autenticar porque não há banco de dados MySQL no Azure

## 🎯 Soluções Possíveis

### Opção 1: Azure Database for MySQL (Recomendado para Produção)

Criar um serviço gerenciado do Azure:

```bash
# Criar MySQL Flexible Server
az mysql flexible-server create \
  --resource-group DEFESA_CIVIL \
  --name sdc-mysql \
  --location brazilsouth \
  --admin-user sdcadmin \
  --admin-password "SuaSenhaSegura123!" \
  --sku-name Standard_B1ms \
  --tier Burstable \
  --version 8.0.21 \
  --storage-size 32 \
  --public-access 0.0.0.0
```

Depois configurar no App Service:

```bash
az webapp config appsettings set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --settings \
    "DB_CONNECTION=mysql" \
    "DB_HOST=sdc-mysql.mysql.database.azure.com" \
    "DB_PORT=3306" \
    "DB_DATABASE=sdc" \
    "DB_USERNAME=sdcadmin" \
    "DB_PASSWORD=SuaSenhaSegura123!"
```

### Opção 2: Container MySQL no Azure Container Instances (Mais Barato)

Criar um container MySQL separado:

```bash
# Criar container MySQL
az container create \
  --resource-group DEFESA_CIVIL \
  --name sdc-mysql \
  --image mysql:8.0 \
  --cpu 1 \
  --memory 2 \
  --environment-variables \
    MYSQL_ROOT_PASSWORD=root \
    MYSQL_DATABASE=sdc \
    MYSQL_USER=sdc \
    MYSQL_PASSWORD=secret \
  --ports 3306 \
  --ip-address Public
```

Depois configurar no App Service apontando para o IP do container.

### Opção 3: Continuar com SQLite (Temporário - Não Recomendado)

SQLite funciona, mas:
- ❌ Não é adequado para produção
- ❌ Não suporta múltiplas conexões simultâneas
- ❌ Dados podem ser perdidos se o container reiniciar

## ✅ Solução Rápida: Configurar SQLite Temporariamente

Se quiser testar rapidamente enquanto não configura o MySQL:

```bash
# Já está configurado como SQLite
# Apenas garantir que o entrypoint cria o arquivo
```

O entrypoint já está configurado para criar SQLite se necessário.

## 🔧 Configurar MySQL no App Service

### Passo 1: Criar MySQL no Azure

```bash
# Criar MySQL Flexible Server (mais simples)
az mysql flexible-server create \
  --resource-group DEFESA_CIVIL \
  --name sdc-mysql-server \
  --location brazilsouth \
  --admin-user sdcadmin \
  --admin-password "SenhaSegura123!" \
  --sku-name Standard_B1ms \
  --tier Burstable \
  --version 8.0.21 \
  --storage-size 32 \
  --public-access 0.0.0.0-255.255.255.255
```

### Passo 2: Obter Endpoint do MySQL

```bash
# Obter o FQDN do servidor
az mysql flexible-server show \
  --resource-group DEFESA_CIVIL \
  --name sdc-mysql-server \
  --query "fullyQualifiedDomainName" -o tsv
```

### Passo 3: Configurar App Service

```bash
# Configurar variáveis de ambiente
az webapp config appsettings set \
  --name newsdc2027 \
  --resource-group DEFESA_CIVIL \
  --settings \
    "DB_CONNECTION=mysql" \
    "DB_HOST=sdc-mysql-server.mysql.database.azure.com" \
    "DB_PORT=3306" \
    "DB_DATABASE=sdc" \
    "DB_USERNAME=sdcadmin" \
    "DB_PASSWORD=SenhaSegura123!"
```

### Passo 4: Reiniciar App Service

```bash
az webapp restart --name newsdc2027 --resource-group DEFESA_CIVIL
```

### Passo 5: Executar Migrations

O entrypoint já executa migrations automaticamente, mas você pode verificar:

```bash
# Ver logs para confirmar
az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL
```

## 📋 Checklist

- [ ] MySQL criado no Azure (Flexible Server ou Container)
- [ ] Variáveis de ambiente configuradas no App Service
- [ ] App Service reiniciado
- [ ] Migrations executadas (automático via entrypoint)
- [ ] Usuário de teste criado (automático via entrypoint)
- [ ] Testar login

## 🎯 Recomendação

**Para desenvolvimento/teste**: Use SQLite temporariamente (já está configurado)

**Para produção**: Crie um Azure Database for MySQL Flexible Server

---

**Data**: 10/12/2025  
**Status**: Aguardando configuração do MySQL no Azure


