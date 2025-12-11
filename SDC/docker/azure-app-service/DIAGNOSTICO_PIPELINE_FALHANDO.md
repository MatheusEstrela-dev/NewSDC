# 🔍 Diagnóstico: Pipeline Jenkins Falhando

## 📋 O Que Deve Aparecer na Tela de Login

### ✅ Visual Esperado

Quando o deploy for bem-sucedido, a tela de login em `https://newsdc2027.azurewebsites.net/login` deve mostrar:

```
┌─────────────────────────────────────────┐
│                                         │
│     [Logo Defesa Civil]                 │
│     Sistema Integrado de Defesa Civil   │
│                                         │
│     [Formulário de Login]               │
│     CPF: [___________]                  │
│     Senha: [___________]                │
│     ☐ Lembrar-me                        │
│     [Botão: Acessar Sistema]           │
│                                         │
│     © 2025 Governo do Estado de        │
│        Minas Gerais                     │
│     CI/CD Test - Deploy Automático     │ ← ESTE TEXTO
│                                         │
└─────────────────────────────────────────┘
```

### 📍 Localização do Código

O texto está definido em:
- **Arquivo**: `SDC/resources/js/Pages/Auth/Login.vue`
- **Linhas**: 134-139

```vue
<div class="card-footer">
  &copy; 2025 Governo do Estado de Minas Gerais
  <span style="font-size: 0.7rem; opacity: 0.6; display: block; margin-top: 4px;">
    CI/CD Test - Deploy Automático
  </span>
</div>
```

### 🎨 Estilo do Texto

- **Tamanho**: 70% do texto normal (`font-size: 0.7rem`)
- **Cor**: Cinza claro com opacidade 60% (`opacity: 0.6`)
- **Posição**: Abaixo do copyright, em nova linha (`display: block; margin-top: 4px`)

---

## 🚨 Por Que Não Está Aparecendo?

### Possíveis Causas

1. ❌ **Pipeline falhou** - O build não completou com sucesso
2. ❌ **Deploy não executou** - A stage de deploy não rodou
3. ❌ **Webhook não configurado** - GitHub não está disparando o Jenkins
4. ❌ **Build ainda em execução** - Aguardando conclusão

---

## 🔧 Passo 1: Verificar Status do Pipeline

### Acessar Jenkins

1. **URL**: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. **Verificar último build**:
   - Procure por build com status ❌ (vermelho) ou 🟡 (amarelo)
   - Clique no build para ver detalhes

### Verificar Console Output

1. No build que falhou, clique em **"Console Output"**
2. Procure por mensagens de erro no final do log
3. **Erros comuns**:
   - `docker: command not found`
   - `docker-compose: command not found`
   - `Cannot connect to Docker daemon`
   - `Permission denied`
   - `No space left on device`
   - `Failed to push to ACR`

---

## 🔗 Passo 2: Verificar Webhook do GitHub

### URL do Webhook

```
https://jenkinssdc.azurewebsites.net/github-webhook/
```

### Verificar no GitHub

1. Acesse: https://github.com/MatheusEstrela-dev/NewSDC/settings/hooks
2. Verifique se existe um webhook configurado
3. Clique no webhook para ver:
   - **Status**: ✅ Verde = funcionando | ❌ Vermelho = erro
   - **Última entrega**: Verifique se houve tentativas recentes
   - **Payload URL**: Deve ser `https://jenkinssdc.azurewebsites.net/github-webhook/`

### Se o Webhook Não Estiver Configurado

1. Clique em **"Add webhook"**
2. Configure:
   - **Payload URL**: `https://jenkinssdc.azurewebsites.net/github-webhook/`
   - **Content type**: `application/json`
   - **Secret**: (deixe vazio por enquanto)
   - **Which events**: ✅ **Just the push event**
   - **Active**: ✅ Marcado
3. Clique em **"Add webhook"**
4. Após salvar, o GitHub faz um ping de teste
5. Verifique se aparece ✅ verde

---

## 🔍 Passo 3: Diagnosticar Falha do Pipeline

### Erro Comum 1: Docker não encontrado

**Sintoma**:
```
docker: command not found
```

**Causa**: O agente Docker do Jenkins não tem acesso ao Docker host

**Solução**: Verificar configuração do agente no Jenkinsfile:
```groovy
agent {
    docker {
        image 'php:8.2-cli'
        args '-v /var/run/docker.sock:/var/run/docker.sock --network sdc_network'
    }
}
```

**Verificar**:
- O Jenkins está rodando em container Docker?
- O socket `/var/run/docker.sock` está mapeado corretamente?
- A rede `sdc_network` existe?

### Erro Comum 2: docker-compose.prod.yml não encontrado

**Sintoma**:
```
ERROR: Couldn't find env file: docker-compose.prod.yml
```

**Causa**: O arquivo não existe ou está no caminho errado

**Solução**: Verificar se o arquivo existe:
```bash
ls -la SDC/docker/docker-compose.prod.yml
```

### Erro Comum 3: Falha ao fazer login no Azure/ACR

**Sintoma**:
```
ERROR: Failed to login to ACR
```

**Causa**: Credenciais do Azure não configuradas no Jenkins

**Solução**:
1. Jenkins → **Manage Jenkins** → **Manage Credentials**
2. Verificar se existe:
   - `azure-service-principal` (com AZURE_CLIENT_ID, AZURE_CLIENT_SECRET)
   - `azure-acr-credentials` (com ACR_USERNAME, ACR_PASSWORD)
3. Se não existir, criar as credenciais

### Erro Comum 4: Espaço em disco insuficiente

**Sintoma**:
```
Espaço em disco insuficiente: 3GB. Mínimo: 5GB
```

**Solução**:
```bash
# Limpar Docker
docker system prune -a --volumes -f

# Limpar builds antigos do Jenkins
# Jenkins → Manage Jenkins → Disk Usage
```

### Erro Comum 5: Testes falhando

**Sintoma**:
```
Tests: 142 passed, 3 failed
```

**Solução**: Verificar logs dos testes para identificar qual teste falhou

---

## 🛠️ Passo 4: Executar Build Manualmente

Se o webhook não estiver funcionando, você pode executar o build manualmente:

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique em **"Build Now"** (no menu lateral esquerdo)
3. Aguarde o build iniciar
4. Clique no build em execução para acompanhar os logs

---

## ✅ Passo 5: Verificar se o Deploy Aconteceu

### Verificar App Service

1. **Status do App Service**:
   ```bash
   az webapp show --name newsdc2027 --resource-group DEFESA_CIVIL --query state
   ```

2. **Verificar última reinicialização**:
   ```bash
   az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL
   ```

3. **Verificar imagem atual**:
   ```bash
   az webapp config container show --name newsdc2027 --resource-group DEFESA_CIVIL
   ```

### Verificar se o Texto Aparece

1. Acesse: https://newsdc2027.azurewebsites.net/login
2. Role até o final do card de login
3. Procure o texto abaixo de "© 2025 Governo do Estado de Minas Gerais"
4. Se não aparecer:
   - O deploy ainda não aconteceu
   - O build falhou antes do deploy
   - O cache do navegador está mostrando versão antiga (Ctrl+F5 para forçar atualização)

---

## 📊 Checklist de Diagnóstico

- [ ] Jenkins está acessível: https://jenkinssdc.azurewebsites.net/
- [ ] Job `SDC/build-and-deploy` existe
- [ ] Último build foi executado
- [ ] Console Output do build foi verificado
- [ ] Webhook do GitHub está configurado
- [ ] Webhook está com status ✅ verde
- [ ] Credenciais Azure estão configuradas no Jenkins
- [ ] Arquivo `docker-compose.prod.yml` existe
- [ ] Docker está funcionando no Jenkins
- [ ] App Service está rodando
- [ ] Imagem foi atualizada no App Service

---

## 🚀 Próximos Passos

1. **Verificar logs do build** no Jenkins
2. **Corrigir o erro** encontrado nos logs
3. **Executar build novamente** (manual ou via webhook)
4. **Aguardar deploy** completar (5-10 minutos)
5. **Verificar tela de login** após deploy

---

## 📞 Informações para Debug

**Commit atual**: `97f9f31`  
**Alteração**: Footer da tela de login atualizado  
**URL do Jenkins**: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/  
**URL do App Service**: https://newsdc2027.azurewebsites.net/login  
**URL do Webhook**: https://jenkinssdc.azurewebsites.net/github-webhook/




