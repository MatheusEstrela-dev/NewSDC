# 🗄️ Justfile - Gerenciamento de Banco de Dados

> **Guia completo para gerenciar o banco de dados do SDC usando Just**

---

## 📌 O que é Just?

**Just** é um task runner moderno, similar ao Make, mas com sintaxe mais simples e funcionalidades avançadas.

### Por que usar Just ao invés de Make?

| Recurso | Make | Just |
|---------|------|------|
| **Sintaxe** | Complexa, tabs obrigatórias | Simples e intuitiva |
| **Parâmetros** | Limitado | Suporte nativo |
| **Variáveis** | Limitado | Rico e flexível |
| **Mensagens** | Básico | Colorido e formatado |
| **Receitas** | Bash only | Multi-linguagem |

---

## 🚀 Instalação

### Windows (via Chocolatey)
```bash
choco install just
```

### Linux/macOS
```bash
curl --proto '=https' --tlsv1.2 -sSf https://just.systems/install.sh | bash
```

### Verificar instalação
```bash
just --version
```

---

## 📋 Comandos Disponíveis

### 🆘 Ajuda

```bash
# Listar todos os comandos
just

# Ajuda detalhada com exemplos
just help

# Ver informações do ambiente
just info
```

---

## 🔄 Migrations

### Executar Migrations

```bash
# Executar migrations pendentes
just migrate

# Com output detalhado
just migrate-verbose

# Ver status (quais já foram executadas)
just migrate-status
```

### Criar Migrations

```bash
# Criar migration genérica
just migrate-create add_column_to_users

# Criar migration para nova tabela
just migrate-table create_webhooks_table webhooks

# Criar migration para modificar tabela existente
just migrate-modify add_status_to_users users
```

**Resultado**: Migration criada em `database/migrations/`

---

## ⏮️ Rollback

### Reverter Migrations

```bash
# Reverter última batch
just rollback

# Reverter N batches
just rollback-steps 3

# Reverter TODAS as migrations (PERIGOSO!)
just rollback-all
```

### Refresh (Reverter + Executar)

```bash
# Refresh da última batch
just migrate-refresh

# Refresh de N batches
just migrate-refresh-steps 2
```

**Exemplo de uso**:
```bash
# Cenário: Você executou 3 migrations e quer desfazer todas
just rollback-steps 3

# Cenário: Corrigiu a migration e quer executar novamente
just migrate-refresh
```

---

## 🌱 Seeds

### Executar Seeds

```bash
# Executar todos os seeders
just seed

# Executar seeder específico
just seed-class UsersTableSeeder

# Criar novo seeder
just seed-create ProductsTableSeeder
```

**Exemplo completo**:
```bash
# 1. Criar seeder
just seed-create CategoriesSeeder

# 2. Editar database/seeders/CategoriesSeeder.php
# ... adicionar lógica ...

# 3. Executar seeder específico
just seed-class CategoriesSeeder
```

---

## 🔥 Fresh (Resetar Banco)

### ⚠️ ATENÇÃO: Comandos Perigosos!

```bash
# Dropar TUDO, recriar e popular (DEV)
just fresh

# Fresh sem seeds
just fresh-no-seed

# Fresh em PRODUÇÃO (confirmação dupla necessária)
just fresh-prod
```

**O que acontece**:
1. ❌ Dropa todas as tabelas
2. 🏗️ Recria estrutura via migrations
3. 🌱 Popula com seeds (se `--seed`)

**Quando usar**:
- ✅ Desenvolvimento: estrutura mudou muito
- ✅ Testes: limpar dados de teste
- ❌ NUNCA em produção sem backup!

---

## 💾 Backup & Restore

### Criar Backups

```bash
# Backup manual
just backup manual

# Backup com nome personalizado
just backup "antes-de-deploy"

# Backup antes de migration perigosa
just backup-before-migrate

# Backup automático (para cron)
just backup-auto
```

**Onde ficam**: `storage/backups/`

### Gerenciar Backups

```bash
# Listar backups disponíveis
just backup-list

# Exemplo de saída:
# backup-manual-20250121-143022.sql   (45 MB)
# backup-auto-20250121-120000.sql     (44 MB)
```

### Restaurar Backup

```bash
# Restaurar backup específico
just backup-restore storage/backups/backup-manual-20250121-143022.sql
```

**⚠️ ATENÇÃO**: Sobrescreve banco atual!

---

## 🔧 Manutenção

### Otimização

```bash
# Otimizar todas as tabelas
just optimize

# Reparar tabelas corrompidas
just repair

# Analisar performance
just analyze

# Verificar integridade
just check
```

### Cache Laravel

```bash
# Limpar todos os caches
just cache-clear

# Otimizar para produção
just cache-optimize
```

**Diferença**:
- `cache-clear`: Limpa tudo (dev)
- `cache-optimize`: Cria caches otimizados (prod)

---

## 📊 Informações & Status

### Status do Banco

```bash
# Status completo
just status

# Listar todas as tabelas
just tables

# Contar registros em cada tabela
just count

# Ver tamanho do banco
just db-size

# Ver tamanho de cada tabela
just table-sizes
```

**Exemplo de saída `just count`**:
```
┌──────────────────┬────────────┐
│ Tabela           │ Registros  │
├──────────────────┼────────────┤
│ users            │ 1,245      │
│ webhook_logs     │ 8,934      │
│ integrations     │ 23         │
└──────────────────┴────────────┘
```

### Informações de Tabelas

```bash
# Ver estrutura de uma tabela
just describe users

# Ver índices de uma tabela
just indexes users
```

---

## 🐚 Acesso Direto

### MySQL CLI

```bash
# Acesso ao MySQL
just mysql

# Executar query SQL direta
just query "SELECT COUNT(*) FROM users"

# Exemplo: Ver últimos registros
just query "SELECT * FROM webhook_logs ORDER BY id DESC LIMIT 10"
```

### Shell dos Containers

```bash
# Acesso ao shell do app (Laravel)
just shell

# Acesso ao shell do banco de dados
just db-shell
```

---

## 🧪 Testes

### Banco de Testes

```bash
# Executar migrations no banco de testes
just test-migrate

# Resetar banco de testes
just test-fresh
```

**Configuração**: Usa database definido em `config/database.php` → `testing`

---

## 🚀 Workflows Completos

### Setup Inicial

```bash
# Setup completo do banco (primeira vez)
just setup
```

**O que faz**:
1. ✅ Executa migrations
2. ✅ Popula com seeds
3. ✅ Otimiza caches
4. ✅ Mostra status final

### Deploy em Produção

```bash
# Deploy seguro com backup automático
ENV=prod just deploy
```

**O que faz**:
1. 💾 Cria backup pré-deploy
2. 🔄 Executa migrations
3. ⚡ Otimiza caches
4. 📊 Mostra status final

### Manutenção Completa

```bash
# Manutenção periódica do banco
just maintenance
```

**O que faz**:
1. 💾 Backup pré-manutenção
2. 🔍 Verifica integridade
3. 📊 Analisa performance
4. ⚡ Otimiza tabelas
5. 🧹 Limpa caches

### Diagnóstico

```bash
# Diagnóstico completo do banco
just diagnose
```

**O que mostra**:
- ℹ️ Informações do ambiente
- 📊 Status das migrations
- 💾 Tamanho de tabelas
- 🔢 Contagem de registros

---

## 🌍 Ambientes (Dev vs Prod)

### Desenvolvimento (Padrão)

```bash
# Comandos padrão rodam em DEV
just migrate
just seed
just fresh
```

### Produção

```bash
# Usar ENV=prod para produção
ENV=prod just migrate
ENV=prod just status
ENV=prod just backup manual
```

### Como funciona

```bash
# Variável de ambiente determina os comandos
env := env_var_or_default("ENV", "dev")

# Seleciona docker-compose correto
# dev  → docker/docker-compose.yml
# prod → docker/docker-compose.prod.yml
```

---

## 🗑️ Comandos Perigosos

### ☢️ Nuke (Destruir Tudo)

```bash
# Dropar e recriar banco do ZERO
just nuke
```

**⚠️ EXTREMO CUIDADO**:
- Requer confirmação: Digite `DESTRUIR`
- Delay de 5 segundos para cancelar
- **IRREVERSÍVEL** - todos os dados perdidos!

**Quando usar**:
- ✅ Ambiente de desenvolvimento corrompido
- ✅ Testes locais
- ❌ JAMAIS em produção!

---

## 📖 Exemplos de Uso Comum

### Cenário 1: Nova Feature com Migration

```bash
# 1. Criar migration
just migrate-create add_status_to_webhooks

# 2. Editar migration em database/migrations/
# ... adicionar código ...

# 3. Executar migration
just migrate

# 4. Verificar se funcionou
just migrate-status
just describe webhooks
```

---

### Cenário 2: Erro na Migration - Corrigir

```bash
# 1. Reverter migration problemática
just rollback

# 2. Corrigir código da migration
# ... editar arquivo ...

# 3. Executar novamente
just migrate

# Ou: Fazer tudo de uma vez
just migrate-refresh
```

---

### Cenário 3: Deploy em Produção

```bash
# 1. Verificar status atual
ENV=prod just status

# 2. Criar backup manual (segurança extra)
ENV=prod just backup "antes-deploy-v2.0"

# 3. Executar deploy completo
ENV=prod just deploy

# 4. Verificar se tudo OK
ENV=prod just status
ENV=prod just count
```

---

### Cenário 4: Problema em Produção - Rollback

```bash
# 1. Identificar problema
ENV=prod just migrate-status

# 2. Criar backup do estado atual
ENV=prod just backup "antes-rollback"

# 3. Reverter migrations problemáticas
ENV=prod just rollback-steps 2

# 4. Verificar restauração
ENV=prod just status

# 5. Se necessário, restaurar backup antigo
ENV=prod just backup-restore storage/backups/backup-antes-deploy-v2.0-*.sql
```

---

### Cenário 5: Banco Lento - Manutenção

```bash
# 1. Criar backup preventivo
just backup "pre-manutencao"

# 2. Ver quais tabelas são maiores
just table-sizes

# 3. Executar manutenção completa
just maintenance

# 4. Comparar performance
just db-size
just count
```

---

### Cenário 6: Popular Dados de Teste

```bash
# 1. Resetar banco
just fresh

# 2. Ou: Apenas adicionar mais seeds
just seed

# 3. Ou: Seed específico
just seed-class ProductsSeeder

# 4. Verificar dados
just count
just query "SELECT * FROM products LIMIT 5"
```

---

## 🔄 Backup Automático (Cron)

### Configurar Backup Diário

**Linux (crontab)**:
```bash
# Editar crontab
crontab -e

# Adicionar linha (backup diário às 3h da manhã)
0 3 * * * cd /caminho/projeto/SDC && just backup-auto
```

**Windows (Task Scheduler)**:
```powershell
# Criar tarefa no agendador de tarefas
schtasks /create /tn "SDC Backup" /tr "cd C:\projeto\SDC && just backup-auto" /sc daily /st 03:00
```

### O que o backup-auto faz:
1. ✅ Cria backup com timestamp
2. ✅ Mantém apenas últimos 7 backups
3. ✅ Rotaciona automaticamente

---

## 🛠️ Customização

### Modificar Variáveis

Edite o [Justfile](c:\Users\kdes\Documentos\GitHub\New_SDC\SDC\Justfile):

```bash
# Alterar compose files
compose := "docker compose -f docker/docker-compose.yml"

# Alterar containers
app := compose + " exec app"
db := compose + " exec db"

# Alterar ambiente padrão
env := env_var_or_default("ENV", "dev")
```

### Adicionar Novos Comandos

```bash
# Exemplo: Adicionar comando personalizado
my-command:
    @echo "Meu comando personalizado"
    {{_app}} php artisan custom:command
```

---

## 📊 Métricas e Performance

### Monitorar Tamanho do Banco

```bash
# Ver crescimento ao longo do tempo
just db-size

# Exemplo de saída:
# Database: sdc_db
# Size: 245.67 MB
```

### Identificar Tabelas Grandes

```bash
just table-sizes

# Exemplo de saída:
# ┌──────────────────┬────────────┐
# │ Tabela           │ Size (MB)  │
# ├──────────────────┼────────────┤
# │ webhook_logs     │ 156.23     │
# │ audit_logs       │ 45.67      │
# │ users            │ 12.34      │
# └──────────────────┴────────────┘
```

---

## 🚨 Troubleshooting

### Erro: "justfile not found"

**Solução**: Execute comandos na pasta `SDC/`
```bash
cd SDC
just migrate
```

---

### Erro: "command not found: docker"

**Solução**: Docker não está rodando
```bash
# Verificar status
docker ps

# Iniciar Docker Desktop (Windows/Mac)
# Ou: systemctl start docker (Linux)
```

---

### Erro: "Access denied for user"

**Solução**: Variáveis de ambiente incorretas
```bash
# Verificar .env
cat .env | grep DB_

# Verificar no container
just shell
env | grep DB_
```

---

### Backup falha

**Solução**: Criar diretório de backups
```bash
mkdir -p storage/backups
chmod 777 storage/backups
```

---

## 📚 Referências

### Arquivos Relacionados

| Arquivo | Descrição |
|---------|-----------|
| **Justfile** | Definição dos comandos - `SDC/Justfile` |
| **Makefile** | Comandos gerais Docker - `SDC/Makefile` |
| **Migrations** | Estrutura do banco - `SDC/database/migrations/` |
| **Seeders** | Dados iniciais - `SDC/database/seeders/` |

### Links Úteis

- 📖 [Just Documentation](https://just.systems/man/en/)
- 🐳 [Docker Compose](https://docs.docker.com/compose/)
- 🎯 [Laravel Migrations](https://laravel.com/docs/migrations)
- 🌱 [Laravel Seeding](https://laravel.com/docs/seeding)

---

## 🎓 Comparação: Make vs Just

### Sintaxe Make (Antigo)

```makefile
migrate:
	docker compose -f docker/docker-compose.yml exec app php artisan migrate
```

### Sintaxe Just (Novo)

```just
migrate:
    @echo "🔄 Executando migrations..."
    {{_app}} php artisan migrate --force
    @echo "✅ Migrations concluídas!"
```

**Vantagens Just**:
- ✅ Variáveis reutilizáveis (`{{_app}}`)
- ✅ Mensagens coloridas
- ✅ Sintaxe mais limpa
- ✅ Parâmetros nativos
- ✅ Condicional baseado em ENV

---

## 🔄 Migration para Just

### Se você usa Make atualmente:

```bash
# Antes (Make)
make migrate
make seed
make fresh

# Agora (Just)
just migrate
just seed
just fresh
```

**Ambos podem coexistir!** Mantenha o Makefile para comandos gerais e use Justfile para banco de dados.

---

**🗄️ Justfile Database - v1.0.0**

*Última atualização: 2025-01-21*
