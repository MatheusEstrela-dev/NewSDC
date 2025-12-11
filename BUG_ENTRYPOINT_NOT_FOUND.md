# Bug: entrypoint.prod.sh não encontrado após COPY . .

## Data
2025-12-10

## Descrição do Bug

O build do Docker está falhando porque o arquivo `docker/scripts/entrypoint.prod.sh` não é encontrado após o comando `COPY . .` no Dockerfile.

### Erro Completo

```
Step 20/23 : RUN cp /var/www/docker/scripts/entrypoint.prod.sh /start.sh     && chmod +x /start.sh
 ---> Running in ffe5c966f7c7
cp: can't stat '/var/www/docker/scripts/entrypoint.prod.sh': No such file or directory
The command '/bin/sh -c cp /var/www/docker/scripts/entrypoint.prod.sh /start.sh     && chmod +x /start.sh' returned a non-zero code: 1
```

### Contexto

- **Build #6** do Jenkins
- **Commit**: `3beecd62cd080dcc62fc81d2960db1556def729f`
- **Dockerfile**: `SDC/docker/Dockerfile.prod`
- **Linha do erro**: Step 20/23

### Análise

O problema ocorre porque:

1. O `COPY . .` na linha 15 do Dockerfile copia todo o conteúdo do diretório `SDC/` para `/var/www/`
2. O comando `RUN cp /var/www/docker/scripts/entrypoint.prod.sh /start.sh` tenta copiar o arquivo após o `COPY . .`
3. O arquivo não está sendo encontrado em `/var/www/docker/scripts/entrypoint.prod.sh`

### Possíveis Causas

1. **Arquivo não está no Git**: O arquivo pode não estar sendo commitado no repositório
2. **Arquivo excluído pelo .dockerignore**: O `.dockerignore` pode estar excluindo o arquivo ou diretório
3. **Caminho incorreto**: O caminho relativo pode estar incorreto no contexto do build do ACR

### Verificações Realizadas

- ✅ Arquivo existe localmente: `SDC/docker/scripts/entrypoint.prod.sh`
- ✅ `.dockerignore` não exclui o diretório `docker/`
- ✅ Arquivo está no Git: Commit `e840de3c30fa4423b26c5b109be544b142d74830` (autor: KvN)
- ❌ Arquivo não está sendo encontrado após `COPY . .` no build do Docker

### Causa Raiz

🔍 **O arquivo ESTÁ no Git, mas não está sendo copiado corretamente pelo `COPY . .`**

O problema ocorre porque:
1. O `az acr build` está sendo executado no diretório `SDC/` (conforme `dir('SDC')` no Jenkinsfile)
2. O contexto do build é `SDC/`, então o `COPY . .` copia de `SDC/` para `/var/www/`
3. O arquivo deveria estar em `/var/www/docker/scripts/entrypoint.prod.sh` após o `COPY . .`
4. Mas o comando `cp /var/www/docker/scripts/entrypoint.prod.sh /start.sh` falha porque o arquivo não é encontrado

**Possível causa**: O arquivo pode não estar sendo enviado no contexto do build do ACR, ou o caminho está incorreto.

### Soluções Tentadas

1. **Tentativa 1**: Copiar o arquivo antes do `COPY . .`
   ```dockerfile
   COPY docker/scripts/entrypoint.prod.sh /start.sh
   RUN chmod +x /start.sh
   COPY . .
   ```
   - **Resultado**: ❌ Falhou - arquivo não encontrado no contexto do build

2. **Tentativa 2**: Copiar o arquivo após o `COPY . .` usando `cp`
   ```dockerfile
   COPY . .
   RUN cp /var/www/docker/scripts/entrypoint.prod.sh /start.sh && chmod +x /start.sh
   ```
   - **Resultado**: ❌ Falhou - arquivo não encontrado após `COPY . .`

### Solução Proposta

**Opção 1**: Adicionar debug para verificar o que está sendo copiado:
```dockerfile
COPY . .
RUN ls -la /var/www/docker/scripts/ || echo "Diretório não encontrado"
RUN find /var/www -name "entrypoint.prod.sh" || echo "Arquivo não encontrado"
RUN cp /var/www/docker/scripts/entrypoint.prod.sh /start.sh && chmod +x /start.sh
```

**Opção 2**: Usar `COPY` direto do arquivo (se o contexto do build permitir):
```dockerfile
# Copiar arquivos de dependências primeiro
COPY composer.json composer.lock* ./
COPY package.json package-lock.json* ./

# Copiar script de entrypoint
COPY docker/scripts/entrypoint.prod.sh /start.sh
RUN chmod +x /start.sh

# Copiar resto do código
COPY . .
```

**Opção 3**: Verificar se o contexto do build do `az acr build` está correto no Jenkinsfile

### Arquivos Relacionados

- `SDC/docker/Dockerfile.prod` (linha 20 - Step 20/23)
- `SDC/docker/scripts/entrypoint.prod.sh`
- `SDC/.dockerignore`
- `Jenkinsfile` (stage "Build and Push to ACR", linha ~124-133)

### Contexto do Build ACR

O `az acr build` está sendo executado assim:

```groovy
dir('SDC') {
    sh """
        az acr build \\
            --registry ${ACR_NAME} \\
            --resource-group ${ACR_RESOURCE_GROUP} \\
            --image sdc-dev-app:${ACR_TAG} \\
            --image sdc-dev-app:latest \\
            --file docker/Dockerfile.prod \\
            --platform linux \\
            .
    """
}
```

**Observação importante**: 
- O comando é executado dentro de `dir('SDC')`, então o contexto do build é `SDC/`
- O Dockerfile está em `docker/Dockerfile.prod` (relativo a `SDC/`)
- O `COPY . .` no Dockerfile copia de `SDC/` para `/var/www/`
- Portanto, o arquivo deveria estar em `/var/www/docker/scripts/entrypoint.prod.sh`

### Logs do Build

Build com erro (#6) disponível em:
https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/6/console

### Status

🔴 **ABERTO** - Arquivo está no Git mas não está sendo copiado corretamente no build do Docker

### Próximos Passos

1. Adicionar comandos de debug no Dockerfile para verificar o que está sendo copiado
2. Verificar se o contexto do build do `az acr build` está correto
3. Testar usar `COPY docker/scripts/entrypoint.prod.sh /start.sh` diretamente antes do `COPY . .`
4. Verificar se há algum problema com o `.dockerignore` que possa estar excluindo o arquivo

