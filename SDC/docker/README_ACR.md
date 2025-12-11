# 🚀 Push de Imagens para Azure Container Registry (ACR)

Este guia explica como fazer push das imagens Docker do ambiente de desenvolvimento (`sdc-dev`) para o Azure Container Registry.

## 📋 Pré-requisitos

1. **Azure CLI instalado**
   ```bash
   # Windows (via winget)
   winget install -e --id Microsoft.AzureCLI
   
   # Linux/Mac
   curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash
   ```

2. **Docker Desktop rodando**

3. **Acesso ao Azure Container Registry**
   - Nome do ACR (ex: `meuacr`)
   - Permissões de push no registro

## 🔐 Método 1: Usando Make (Recomendado)

### Windows (PowerShell)

```powershell
# 1. Login no ACR
make acr-login ACR=seuacr

# 2. Push das imagens
make acr-push-windows ACR=seuacr TAG=dev-latest
```

### Linux/Mac

```bash
# 1. Login no ACR
make acr-login ACR=seuacr

# 2. Push das imagens
make acr-push ACR=seuacr TAG=dev-latest
```

## 🔐 Método 2: Usando Scripts Diretamente

### Windows (PowerShell)

```powershell
# Login e push
.\docker\push-to-acr.ps1 -AcrName "seuacr" -Tag "dev-latest"

# Apenas login
.\docker\push-to-acr.ps1 -AcrName "seuacr" -LoginOnly
```

### Linux/Mac

```bash
# Dar permissão de execução
chmod +x docker/push-to-acr.sh

# Login e push
./docker/push-to-acr.sh -n "seuacr" -t "dev-latest"

# Com resource group
./docker/push-to-acr.sh -n "seuacr" -g "meu-rg" -t "dev-latest"

# Apenas login
./docker/push-to-acr.sh -n "seuacr" --login-only
```

## 🔐 Método 3: Manual

### 1. Login no Azure

```bash
az login
```

### 2. Login no ACR

```bash
az acr login --name seuacr
```

### 3. Tag da Imagem

```bash
# Tag da imagem local para o formato ACR
docker tag sdc-dev-app:latest seuacr.azurecr.io/sdc-dev-app:dev-latest
```

### 4. Push da Imagem

```bash
docker push seuacr.azurecr.io/sdc-dev-app:dev-latest
```

## 📦 Imagens Enviadas

O script faz push das seguintes imagens:

| Imagem Local | Imagem no ACR | Tag |
|-------------|---------------|-----|
| `sdc-dev-app:latest` | `seuacr.azurecr.io/sdc-dev-app` | `dev-latest` (ou especificada) |

## ✅ Verificar Imagens no ACR

```bash
# Listar repositórios
az acr repository list --name seuacr --output table

# Listar tags de uma imagem
az acr repository show-tags --name seuacr --repository sdc-dev-app --output table
```

## 🔄 Usar Imagens do ACR

### No Azure Container Instances (ACI)

```bash
az container create \
  --resource-group meu-rg \
  --name sdc-dev \
  --image seuacr.azurecr.io/sdc-dev-app:dev-latest \
  --registry-login-server seuacr.azurecr.io \
  --registry-username seuacr \
  --registry-password $(az acr credential show --name seuacr --query "passwords[0].value" -o tsv) \
  --cpu 2 \
  --memory 4
```

### No Docker Compose (Azure)

Atualize o `docker-compose.yml` para usar a imagem do ACR:

```yaml
services:
  app:
    image: seuacr.azurecr.io/sdc-dev-app:dev-latest
    # ... resto da configuração
```

### Pull Local

```bash
# Login no ACR
az acr login --name seuacr

# Pull da imagem
docker pull seuacr.azurecr.io/sdc-dev-app:dev-latest
```

## 🔧 Troubleshooting

### Erro: "unauthorized: authentication required"

**Solução**: Faça login novamente no ACR:
```bash
az acr login --name seuacr
```

### Erro: "repository name must be lowercase"

**Solução**: O nome do repositório no ACR deve ser em minúsculas. O script já faz isso automaticamente.

### Erro: "denied: requested access to the resource is denied"

**Solução**: Verifique se você tem permissões de push no ACR:
```bash
az acr show --name seuacr --query "loginServer" -o tsv
az role assignment list --scope $(az acr show --name seuacr --query id -o tsv) --query "[?roleDefinitionName=='AcrPush']"
```

## 📝 Notas

- As imagens são taggeadas com `dev-latest` por padrão, mas você pode especificar outra tag
- O script verifica se as imagens locais existem antes de fazer push
- O processo inclui login automático no Azure se necessário
- Para produção, use tags semânticas (ex: `v1.0.0`, `2025-12-08`)

## 🚀 Próximos Passos

Após fazer push das imagens:

1. Configure o Azure Container Instances ou Azure Kubernetes Service
2. Atualize os arquivos de configuração para usar as imagens do ACR
3. Configure CI/CD para fazer push automático após builds bem-sucedidos




