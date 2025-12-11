# 👁️ O Que Deve Aparecer na Tela de Login

## ✅ Visual Esperado Após Deploy Bem-Sucedido

Quando você acessar `https://newsdc2027.azurewebsites.net/login`, deve ver:

```
┌─────────────────────────────────────────────────────┐
│                                                       │
│              [Logo Defesa Civil MG]                  │
│        Sistema Integrado de Defesa Civil             │
│                                                       │
│  ┌─────────────────────────────────────────────┐    │
│  │  👤  CPF                                     │    │
│  │  ___.___.___-__                              │    │
│  └─────────────────────────────────────────────┘    │
│                                                       │
│  ┌─────────────────────────────────────────────┐    │
│  │  🔒  Senha                                👁 │    │
│  │  ••••••••                                    │    │
│  └─────────────────────────────────────────────┘    │
│                                                       │
│  ☐ Lembrar-me          Esqueceu a senha?            │
│                                                       │
│  ┌─────────────────────────────────────────────┐    │
│  │         Acessar Sistema                       │    │
│  └─────────────────────────────────────────────┘    │
│                                                       │
│  © 2025 Governo do Estado de Minas Gerais           │
│  CI/CD Test - Deploy Automático                    │ ← ESTE TEXTO
│                                                       │
└─────────────────────────────────────────────────────┘
```

## 📍 Onde Está o Texto

### Localização no Código

**Arquivo**: `SDC/resources/js/Pages/Auth/Login.vue`  
**Linhas**: 134-139

```vue
<div class="card-footer">
  &copy; 2025 Governo do Estado de Minas Gerais
  <span style="font-size: 0.7rem; opacity: 0.6; display: block; margin-top: 4px;">
    CI/CD Test - Deploy Automático
  </span>
</div>
```

### Estilo Aplicado

- **Tamanho da fonte**: `0.7rem` (70% do tamanho normal)
- **Opacidade**: `0.6` (60% - texto cinza claro)
- **Display**: `block` (nova linha)
- **Margem superior**: `4px` (espaçamento do copyright)

## 🔍 Como Verificar

### Passo 1: Acessar a Tela de Login

1. Abra o navegador
2. Acesse: **https://newsdc2027.azurewebsites.net/login**
3. Role a página até o final do card de login

### Passo 2: Procurar o Texto

1. Procure por: **"© 2025 Governo do Estado de Minas Gerais"**
2. Logo abaixo, em uma nova linha, deve aparecer:
   - **"CI/CD Test - Deploy Automático"**
   - Em texto menor e mais claro (cinza)

### Passo 3: Se Não Aparecer

**Possíveis causas:**

1. ❌ **Deploy ainda não aconteceu**
   - Aguarde 5-10 minutos após o build completar
   - Verifique se o build do Jenkins foi bem-sucedido

2. ❌ **Build falhou antes do deploy**
   - Verifique os logs do Jenkins
   - Corrija os erros encontrados

3. ❌ **Cache do navegador**
   - Pressione **Ctrl + F5** (Windows) ou **Cmd + Shift + R** (Mac)
   - Ou limpe o cache do navegador

4. ❌ **Deploy não executou**
   - Verifique se você está na branch `main` ou `master`
   - O deploy automático só acontece nessas branches

## 🚀 Verificar Status do Deploy

### 1. Verificar Build do Jenkins

Acesse: https://jenkinssdc.azurewebsites.net/job/SDC/job/build-and-deploy/lastBuild/

**O que procurar:**
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

## 📸 Captura de Tela Esperada

Quando funcionando corretamente, você deve ver:

```
Footer do Card de Login:
─────────────────────────
© 2025 Governo do Estado de Minas Gerais
CI/CD Test - Deploy Automático
```

Onde:
- Primeira linha: Texto normal, preto
- Segunda linha: Texto menor (70%), cinza claro (60% opacidade)

## ✅ Checklist de Verificação

- [ ] Acessei a URL: https://newsdc2027.azurewebsites.net/login
- [ ] Rolei até o final do card de login
- [ ] Vi o texto "© 2025 Governo do Estado de Minas Gerais"
- [ ] Vi o texto "CI/CD Test - Deploy Automático" abaixo
- [ ] O texto está menor e mais claro que o copyright
- [ ] Limpei o cache do navegador (Ctrl+F5)
- [ ] Verifiquei que o build do Jenkins foi bem-sucedido
- [ ] Aguardei 5-10 minutos após o build completar

## 🆘 Se Ainda Não Aparecer

1. **Verifique o código no repositório:**
   ```bash
   git log --oneline -5
   git show HEAD:SDC/resources/js/Pages/Auth/Login.vue | grep -A 5 "card-footer"
   ```

2. **Verifique se o build compilou os assets:**
   - O Jenkinsfile deve ter executado: `npm run build`
   - Verifique os logs do stage "Build Frontend Assets"

3. **Verifique se o App Service está usando a imagem correta:**
   ```bash
   az webapp config container show --name newsdc2027 --resource-group DEFESA_CIVIL
   ```

4. **Execute o script de verificação:**
   ```bash
   ./SDC/docker/azure-app-service/verificar-pipeline.sh
   ```

---

**Última atualização**: Commit `97f9f31` - Footer da tela de login atualizado




