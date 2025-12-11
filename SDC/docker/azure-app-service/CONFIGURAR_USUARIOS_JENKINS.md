# 👥 Configuração de Usuários do Jenkins

## ✅ Usuários Configurados

O Jenkins está configurado com os seguintes usuários:

1. **admin** - Administrador padrão
   - Senha padrão: `admin123`
   - Variável: `JENKINS_ADMIN_PASSWORD`

2. **omlioes** - Usuário de desenvolvimento
   - Senha padrão: `omlioes123`
   - Variável: `JENKINS_OMLIOES_PASSWORD`

3. **matheus.estrela** - Usuário de desenvolvimento
   - Senha padrão: `matheus123`
   - Variável: `JENKINS_MATHEUS_PASSWORD`

## 🔧 Como Alterar Senhas

### Opção 1: Via Variáveis de Ambiente (Recomendado)

Adicione ao `docker-compose.yml` do Jenkins:

```yaml
environment:
  - JENKINS_ADMIN_PASSWORD=sua_senha_segura_admin
  - JENKINS_OMLIOES_PASSWORD=sua_senha_segura_omlioes
  - JENKINS_MATHEUS_PASSWORD=sua_senha_segura_matheus
```

### Opção 2: Via Interface do Jenkins

1. Acesse: `http://localhost:8090` (ou `https://jenkinssdc.azurewebsites.net`)
2. Faça login com um usuário admin
3. **Manage Jenkins** → **Manage Users**
4. Clique no usuário desejado
5. **Configure** → Altere a senha
6. **Save**

### Opção 3: Via Script Groovy

Execute no Jenkins Script Console:

```groovy
import hudson.security.*
import jenkins.model.*

def instance = Jenkins.getInstance()
def realm = instance.getSecurityRealm() as HudsonPrivateSecurityRealm

// Alterar senha do omlioes
def user = realm.getUser('omlioes')
user.setPassword('nova_senha_segura')

// Alterar senha do matheus.estrela
def user2 = realm.getUser('matheus.estrela')
user2.setPassword('nova_senha_segura')

instance.save()
```

## 🔐 Configuração de Permissões

Todos os usuários configurados têm permissões completas (Full Control) após login, conforme configurado em `casc.yaml`:

```yaml
authorizationStrategy:
  loggedInUsersCanDoAnything:
    allowAnonymousRead: false
```

Isso significa que qualquer usuário autenticado pode:
- ✅ Criar e executar jobs
- ✅ Configurar pipelines
- ✅ Acessar todas as funcionalidades do Jenkins

## 📝 Verificar Usuários Configurados

### Via Interface Web

1. Acesse: `http://localhost:8090/manage`
2. **Manage Users** → Veja lista de usuários

### Via API

```bash
# Listar usuários
curl -u admin:admin123 http://localhost:8090/securityRealm/user/

# Verificar usuário específico
curl -u admin:admin123 http://localhost:8090/securityRealm/user/omlioes/api/json
```

## 🚀 Aplicar Mudanças

Após alterar as configurações:

```bash
# Reiniciar container do Jenkins
docker compose restart jenkins

# Ou recriar
docker compose up -d --force-recreate jenkins
```

## ⚠️ Importante

1. **Mude as senhas padrão em produção!**
2. Use senhas fortes (mínimo 12 caracteres)
3. Configure variáveis de ambiente para senhas
4. Não commite senhas no repositório

## 📋 Checklist

- [ ] Senhas padrão alteradas
- [ ] Variáveis de ambiente configuradas
- [ ] Usuários podem fazer login
- [ ] Permissões verificadas
- [ ] Senhas não estão no repositório




