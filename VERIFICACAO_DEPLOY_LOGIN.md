# 🔍 Verificação: O Que Deve Aparecer na Tela de Login

## ✅ O Que Deve Aparecer

### Visual Esperado na Tela de Login

Quando você acessar **https://newsdc2027.azurewebsites.net/login**, no rodapé do card de login, você deve ver:

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

### 📍 Detalhes do Texto "CI/CD Test - Deploy Automático"

**Localização**: Abaixo do copyright, em uma nova linha

**Aparência**:
- **Tamanho**: 70% do texto normal (menor)
- **Cor**: Cinza claro (opacidade 60%)
- **Posição**: Nova linha abaixo de "© 2025 Governo do Estado de Minas Gerais"

**Código** (em `SDC/resources/js/Pages/Auth/Login.vue`):
```vue
<div class="card-footer">
  &copy; 2025 Governo do Estado de Minas Gerais
  <span style="font-size: 0.7rem; opacity: 0.6; display: block; margin-top: 4px;">
    CI/CD Test - Deploy Automático
  </span>
</div>
```

---

## 🚨 Problema Identificado e Corrigido

### ❌ Erro no Jenkins

O Jenkins estava falhando ao tentar clonar o repositório com o seguinte erro:

```
ERROR: Error cloning remote repo 'origin'
remote: Invalid username or token. Password authentication is not supported for Git operations.
fatal: Authentication failed for 'https://github.com/user/repo.git/'
```

### 🔧 Causa do Problema

1. **URL incorreta**: O Jenkins estava usando uma URL placeholder (`https://github.com/user/repo.git`)
2. **Incompatibilidade**: Estava usando credencial SSH (`git-ssh-key`) mas tentando clonar via HTTPS
3. **Path do Jenkinsfile**: Estava configurado como `Jenkinsfile` mas deveria ser `SDC/Jenkinsfile`

### ✅ Correção Aplicada

Arquivo corrigido: `SDC/docker/jenkins/casc.yaml`

**Mudanças**:
- ✅ URL alterada para: `git@github.com:MatheusEstrela-dev/NewSDC.git` (SSH)
- ✅ Mantida credencial SSH (`git-ssh-key`) - agora compatível
- ✅ Path do Jenkinsfile corrigido para: `SDC/Jenkinsfile`

---

## 🔄 Próximos Passos

### 1. Reconfigurar o Jenkins

O Jenkins precisa ser reiniciado ou a configuração precisa ser recarregada para aplicar as mudanças do `casc.yaml`.

**Opção A: Reiniciar o Jenkins** (se tiver acesso)
```bash
# No container do Jenkins
docker restart jenkins-container
```

**Opção B: Recarregar Configuração** (via interface web)
1. Acesse: https://jenkinssdc.azurewebsites.net/manage
2. Vá em **Manage Jenkins** → **Configuration as Code**
3. Clique em **Reload configuration**

### 2. Verificar Credenciais SSH

Certifique-se de que a credencial SSH está configurada no Jenkins:

1. Acesse: https://jenkinssdc.azurewebsites.net/credentials/
2. Verifique se existe a credencial `git-ssh-key`
3. Se não existir, adicione:
   - **Kind**: SSH Username with private key
   - **ID**: `git-ssh-key`
   - **Username**: `git`
   - **Private Key**: Cole a chave SSH privada do GitHub

### 3. Configurar Variável de Ambiente (Opcional)

Se preferir usar variável de ambiente para a URL do repositório:

1. **Manage Jenkins** → **Configure System**
2. **Global properties** → **Environment variables**
3. Adicione:
   - **Name**: `GIT_REPO_URL`
   - **Value**: `git@github.com:MatheusEstrela-dev/NewSDC.git`

### 4. Testar o Pipeline

Após corrigir, faça um novo commit ou dispare o build manualmente:

1. Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/
2. Clique em **Build Now**
3. Verifique os logs para confirmar que o checkout funcionou

---

## ✅ Checklist de Verificação

### No Jenkins:
- [ ] Build executou com sucesso (status verde)
- [ ] Stage "Checkout" completou sem erros
- [ ] Stage "Deploy to Azure App Service" completou
- [ ] Mensagem: "✅ Deploy para Azure App Service concluído!"

### Na Tela de Login:
- [ ] Acessei: https://newsdc2027.azurewebsites.net/login
- [ ] Rolei até o final do card de login
- [ ] Vi o texto "© 2025 Governo do Estado de Minas Gerais"
- [ ] Vi o texto "CI/CD Test - Deploy Automático" abaixo
- [ ] O texto está menor e mais claro (cinza)
- [ ] Limpei o cache do navegador (Ctrl+F5 ou Cmd+Shift+R)

---

## 🆘 Se Ainda Não Aparecer

### 1. Verificar Build do Jenkins

Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/lastBuild/console

**O que procurar**:
- ✅ Status: **SUCCESS** (verde)
- ✅ Stage "Deploy to Azure App Service" completou
- ✅ Mensagem: "✅ Deploy para Azure App Service concluído!"

### 2. Verificar App Service

```bash
# Ver status
az webapp show --name newsdc2027 --resource-group DEFESA_CIVIL --query state

# Ver logs recentes
az webapp log tail --name newsdc2027 --resource-group DEFESA_CIVIL
```

### 3. Verificar Imagem no ACR

```bash
# Ver última tag
az acr repository show-tags --name apidover --repository sdc-dev-app --orderby time_desc --output table
```

### 4. Aguardar Deploy

- O deploy pode levar 5-10 minutos após o build completar
- O App Service precisa reiniciar para aplicar a nova imagem
- Verifique os logs do App Service para confirmar reinicialização

---

## 📝 Resumo

**O que foi corrigido**:
- ✅ URL do repositório atualizada para o repositório correto
- ✅ Mudado de HTTPS para SSH (compatível com credencial SSH)
- ✅ Path do Jenkinsfile corrigido

**O que deve aparecer**:
- ✅ Texto "CI/CD Test - Deploy Automático" no rodapé do login
- ✅ Texto menor e cinza claro, abaixo do copyright

**Próximo passo**:
- 🔄 Reconfigurar/Reiniciar o Jenkins para aplicar as mudanças
- 🧪 Fazer um novo build e verificar se o checkout funciona
- 👀 Verificar a tela de login após o deploy completar

