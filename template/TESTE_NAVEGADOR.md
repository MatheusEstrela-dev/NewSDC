# 🌐 Guia de Teste no Navegador - SDC

Este guia explica como testar a aplicação no navegador e verificar se a sidebar está funcionando corretamente.

## 🚀 Acessar a Aplicação

### URLs Disponíveis

1. **Aplicação Principal**: http://localhost
   - Acesse via Nginx (porta 80)
   - Redireciona para a página de login

2. **Vite Dev Server**: http://localhost:5173
   - Servidor de desenvolvimento do Vite
   - Hot Module Replacement (HMR) ativo

3. **Jenkins**: http://localhost:8080
   - Interface do Jenkins para CI/CD

4. **MailHog**: http://localhost:8025
   - Interface web para visualizar emails enviados

## 📋 Checklist de Teste

### 1. Verificar Containers

```bash
cd SDC
docker-compose -f docker-compose.dev.yml ps
```

Todos os containers devem estar com status "Up":
- ✅ sdc_app_dev (PHP-FPM)
- ✅ sdc_nginx (Nginx)
- ✅ sdc_node (Node.js/Vite)
- ✅ sdc_db (MySQL)
- ✅ sdc_redis (Redis)

### 2. Verificar Logs

```bash
# Logs do container PHP
docker logs sdc_app_dev

# Logs do container Node (Vite)
docker logs sdc_node

# Logs do Nginx
docker logs sdc_nginx
```

### 3. Testar Página de Login

1. Acesse: **http://localhost**
2. Você deve ver a página de login com:
   - Logo da Defesa Civil
   - Campos de CPF e Senha
   - Botão "Acessar Sistema"
   - Design com gradiente azul escuro

### 4. Testar Sidebar (Após Login)

Após fazer login, você deve ver:

#### ✅ Sidebar Esquerda (Escura)
- Logo "SDC MG" com ícone azul "S"
- Texto "SISTEMA INTEGRADO"
- Seção **PRINCIPAL**:
  - Visão Geral (link para dashboard)
  - RAT
- Seção **MÓDULOS DE GESTÃO**:
  - TDAP (expansível)
    - PMDA (submenu)
    - Relatórios (submenu)
    - Configurações (submenu)
  - Vistoria
- **Perfil do Usuário** (parte inferior):
  - Avatar com iniciais
  - Nome do usuário
  - Email do usuário
  - Botão "Sair" (vermelho)

#### ✅ Área de Conteúdo
- Conteúdo principal à direita da sidebar
- Fundo claro (#f8fafc)

#### ✅ Footer
- Logo MG
- Texto "CEDEC - Defesa Civil de Minas Gerais"
- Copyright "© 2025 Todos os direitos reservados"
- Links: Termos, Privacidade, Suporte

## 🔍 Verificações Específicas da Sidebar

### Teste 1: Navegação
- [ ] Clicar em "Visão Geral" deve levar ao dashboard
- [ ] Item ativo deve ter destaque azul
- [ ] Hover nos itens deve mostrar efeito visual

### Teste 2: Submenu TDAP
- [ ] Clicar em "TDAP" deve expandir/colapsar submenu
- [ ] Submenu deve mostrar: PMDA, Relatórios, Configurações
- [ ] Ícone de seta deve rotacionar ao expandir

### Teste 3: Perfil do Usuário
- [ ] Avatar deve mostrar iniciais do nome
- [ ] Nome e email devem aparecer corretamente
- [ ] Botão "Sair" deve fazer logout

### Teste 4: Responsividade
- [ ] Em telas menores, sidebar deve se adaptar
- [ ] Menu mobile deve funcionar (se implementado)

## 🐛 Troubleshooting

### Problema: Sidebar não aparece

**Solução 1**: Verificar se o componente está sendo importado
```bash
docker exec -it sdc_node npm run build
```

**Solução 2**: Verificar console do navegador (F12)
- Procure por erros JavaScript
- Verifique se os componentes estão sendo carregados

**Solução 3**: Limpar cache do navegador
- Ctrl + Shift + R (hard refresh)
- Ou limpar cache completamente

### Problema: Estilos não estão aplicados

**Solução**: Verificar se o Tailwind está compilando
```bash
docker exec -it sdc_node npm run dev
```

### Problema: Erro 500 no servidor

**Solução**: Verificar logs do PHP
```bash
docker logs sdc_app_dev
docker exec -it sdc_app_dev tail -f storage/logs/laravel.log
```

### Problema: Rotas não funcionam

**Solução**: Verificar rotas
```bash
docker exec -it sdc_app_dev php artisan route:list
```

## 🔧 Comandos Úteis

### Reconstruir containers
```bash
cd SDC
docker-compose -f docker-compose.dev.yml down
docker-compose -f docker-compose.dev.yml up -d --build
```

### Reinstalar dependências
```bash
docker exec -it sdc_app_dev composer install
docker exec -it sdc_node npm install
```

### Limpar cache do Laravel
```bash
docker exec -it sdc_app_dev php artisan cache:clear
docker exec -it sdc_app_dev php artisan config:clear
docker exec -it sdc_app_dev php artisan view:clear
```

### Verificar permissões
```bash
docker exec -it sdc_app_dev chmod -R 775 storage bootstrap/cache
docker exec -it sdc_app_dev chown -R www-data:www-data storage bootstrap/cache
```

## 📸 O que você deve ver

### Página de Login
- Fundo: Gradiente azul escuro (#06315c → #001224)
- Card central branco com logo
- Campos de CPF e Senha
- Botão "Acessar Sistema"

### Dashboard (Após Login)
- **Sidebar esquerda**: Escura, fixa, com navegação
- **Conteúdo central**: Fundo claro, área de trabalho
- **Footer**: Informações do CEDEC

## 🎯 Próximos Passos

1. ✅ Testar login
2. ✅ Verificar sidebar
3. ✅ Testar navegação
4. ⏳ Criar rotas faltantes (PMDA, RAT, Vistoria)
5. ⏳ Implementar páginas correspondentes
6. ⏳ Adicionar sidebar direita (timeline de atividades)

---

**Última atualização**: 2025-01-27

