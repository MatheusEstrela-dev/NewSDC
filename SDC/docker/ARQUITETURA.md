# 🏗️ SDC - Arquitetura Docker Completa

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Processo de Inicialização](#processo-de-inicialização)
3. [Topologia de Rede](#topologia-de-rede)
4. [Containers e Comunicação](#containers-e-comunicação)
5. [Fluxo de Requisições](#fluxo-de-requisições)
6. [Diagnóstico de Performance](#diagnóstico-de-performance)

---

## 🎯 Visão Geral

A arquitetura Docker do SDC é composta por **7 containers principais** organizados em uma **bridge network isolada** (`sdc_network`), permitindo comunicação eficiente entre serviços enquanto mantém isolamento do host.

### Diagrama de Arquitetura

```
┌─────────────────────────────────────────────────────────────────┐
│                    HOST (Windows/Linux/Mac)                    │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │         Docker Network: sdc_network (172.25.0.0/16)      │  │
│  │                                                           │  │
│  │  ┌──────────────┐      ┌──────────────┐                  │  │
│  │  │   Nginx      │◄─────┤   Cliente    │                  │  │
│  │  │  (Port 80)   │      │  (Browser)   │                  │  │
│  │  └──────┬───────┘      └──────────────┘                  │  │
│  │         │                                                 │  │
│  │         │ HTTP/HTTPS                                      │  │
│  │         ▼                                                 │  │
│  │  ┌──────────────┐                                         │  │
│  │  │     App      │                                         │  │
│  │  │  (Laravel)   │                                         │  │
│  │  │ Port: 8000   │                                         │  │
│  │  └──┬───────┬───┘                                         │  │
│  │     │       │                                             │  │
│  │     │       │                                             │  │
│  │     │       │                                             │  │
│  │  ┌──▼───┐ ┌─▼────┐  ┌──────────┐  ┌──────────┐          │  │
│  │  │  DB  │ │Redis │  │ Mailhog  │  │  Node    │          │  │
│  │  │MySQL │ │Cache │  │  Email   │  │  Vite    │          │  │
│  │  │:3306 │ │:6379 │  │  :1025   │  │  :5173   │          │  │
│  │  └──────┘ └──────┘  └──────────┘  └──────────┘          │  │
│  │                                                           │  │
│  │  ┌──────────────┐  ┌──────────────┐  (Profile: tools)  │  │
│  │  │ phpMyAdmin   │  │Redis Commander│                    │  │
│  │  │   :8080      │  │    :8081     │                    │  │
│  │  └──────────────┘  └──────────────┘                    │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🚀 Processo de Inicialização

### Fase 1: Preparação do Ambiente

```bash
docker compose -f docker/docker-compose.yml up -d
```

**O que acontece:**

1. **Docker Compose lê o arquivo `docker-compose.yml`**
   - Parse da configuração YAML
   - Validação de sintaxe
   - Resolução de variáveis de ambiente

2. **Criação da Network**
   ```
   Network: sdc-dev_sdc_network
   Type: bridge
   Subnet: 172.25.0.0/16
   Gateway: 172.25.0.1
   ```

3. **Criação dos Volumes**
   - `sdc-dev_db_data_dev` - Dados persistentes do MySQL
   - `sdc-dev_redis_data_dev` - Dados persistentes do Redis
   - `sdc-dev_vendor_dev` - Dependências PHP (otimização)
   - `sdc-dev_node_modules_dev` - Dependências Node (otimização)

### Fase 2: Inicialização dos Containers Base

#### 2.1. Container `db` (MySQL)

**Ordem de inicialização:** 1º (sem dependências)

**Processo:**
```
1. Pull da imagem mysql:8.0 (se necessário)
2. Criação do container sdc_db_dev
3. Montagem do volume db_data_dev
4. Aplicação das configurações:
   - MYSQL_ROOT_PASSWORD
   - MYSQL_DATABASE=sdc
   - MYSQL_USER=sdc
   - Character set: utf8mb4
5. Execução do entrypoint do MySQL
6. Inicialização do servidor MySQL
7. Health check: mysqladmin ping (a cada 10s)
8. Status: HEALTHY ✅
```

**Tempo estimado:** 15-30 segundos

**Importância:**
- **Crítico**: Sem o MySQL, a aplicação não pode funcionar
- Armazena todos os dados persistentes da aplicação
- Health check garante que está pronto antes de outros containers iniciarem

**Comunicação:**
- **Recebe conexões de:** `app`, `phpmyadmin`
- **Porta interna:** 3306
- **Porta exposta:** 3306 (host)
- **Hostname na network:** `db`

---

#### 2.2. Container `redis`

**Ordem de inicialização:** 2º (sem dependências)

**Processo:**
```
1. Pull da imagem redis:7-alpine
2. Criação do container sdc_redis_dev
3. Montagem do volume redis_data_dev
4. Configuração:
   - appendonly yes (persistência)
   - maxmemory 256mb
   - maxmemory-policy allkeys-lru
5. Inicialização do servidor Redis
6. Health check: redis-cli ping (a cada 10s)
7. Status: HEALTHY ✅
```

**Tempo estimado:** 2-5 segundos

**Importância:**
- **Alta**: Cache, sessões e filas dependem do Redis
- Melhora drasticamente a performance da aplicação
- Armazena sessões de usuários
- Gerencia filas de jobs assíncronos

**Comunicação:**
- **Recebe conexões de:** `app`, `redis-commander`
- **Porta interna:** 6379
- **Porta exposta:** 6379 (host)
- **Hostname na network:** `redis`

---

#### 2.3. Container `mailhog`

**Ordem de inicialização:** 3º (sem dependências)

**Processo:**
```
1. Pull da imagem mailhog/mailhog:latest
2. Criação do container sdc_mailhog_dev
3. Inicialização do MailHog
   - SMTP server na porta 1025
   - Web UI na porta 8025
4. Status: RUNNING ✅
```

**Tempo estimado:** 1-2 segundos

**Importância:**
- **Média**: Essencial para desenvolvimento
- Captura todos os emails enviados pela aplicação
- Permite testar funcionalidades de email sem SMTP real
- Interface web para visualizar emails

**Comunicação:**
- **Recebe conexões de:** `app` (SMTP)
- **Porta interna:** 1025 (SMTP), 8025 (Web UI)
- **Porta exposta:** 1025, 8025 (host)
- **Hostname na network:** `mailhog`

---

### Fase 3: Inicialização da Aplicação

#### 3.1. Container `app` (Laravel)

**Ordem de inicialização:** 4º (depende de `db` e `redis` estarem HEALTHY)

**Processo:**
```
1. Build da imagem (se necessário):
   - Base: php:8.3-fpm-alpine
   - Instalação de extensões PHP
   - Instalação de Composer
   - Instalação de Xdebug
   - Configuração de PHP-FPM
2. Criação do container sdc_app_dev
3. Montagem de volumes:
   - Código fonte: ../:/var/www:cached
   - vendor_dev (isolado)
   - node_modules_dev (isolado)
4. Execução do entrypoint.dev.sh:
   - Criação de diretórios necessários
   - Ajuste de permissões
   - Execução como usuário www-data
5. Comando: php artisan serve --host=0.0.0.0 --port=8000
6. Laravel inicia o servidor de desenvolvimento
7. Health check: curl http://localhost:8000 (a cada 30s)
8. Status: HEALTHY ✅
```

**Tempo estimado:** 30-60 segundos (primeira vez), 10-20s (subsequentes)

**Importância:**
- **CRÍTICO**: Container principal da aplicação
- Executa toda a lógica de negócio
- Serve as requisições HTTP
- Conecta-se ao MySQL e Redis

**Comunicação:**
- **Recebe conexões de:** `nginx`
- **Faz conexões para:**
  - `db:3306` (MySQL)
  - `redis:6379` (Redis)
  - `mailhog:1025` (SMTP)
- **Porta interna:** 8000 (Laravel), 9000 (PHP-FPM)
- **Porta exposta:** 8000, 9000 (host)
- **Hostname na network:** `app`

**Dependências:**
```yaml
depends_on:
  db:
    condition: service_healthy  # Espera MySQL estar pronto
  redis:
    condition: service_healthy   # Espera Redis estar pronto
```

---

#### 3.2. Container `nginx`

**Ordem de inicialização:** 5º (depende de `app` estar criado)

**Processo:**
```
1. Pull da imagem nginx:1.25-alpine
2. Criação do container sdc_nginx_dev
3. Montagem de volumes:
   - Código: ../:/var/www:ro (read-only)
   - Config: ./nginx/dev.conf
   - Logs: ./logs/nginx
4. Aplicação da configuração Nginx
5. Inicialização do Nginx
6. Health check: wget http://localhost/health (a cada 30s)
7. Status: HEALTHY ✅
```

**Tempo estimado:** 2-5 segundos

**Importância:**
- **CRÍTICO**: Ponto de entrada da aplicação
- Reverse proxy para o Laravel
- Serve arquivos estáticos
- Proxy para Vite HMR (Hot Module Replacement)
- Gerencia SSL/TLS (em produção)

**Comunicação:**
- **Recebe conexões de:** Cliente (browser) na porta 80/443
- **Faz conexões para:**
  - `app:8000` (Laravel Octane/Artisan Serve)
  - `app:9000` (PHP-FPM - fallback)
  - `node:5173` (Vite HMR - se disponível)
- **Porta interna:** 80, 443
- **Porta exposta:** 80, 443 (host)
- **Hostname na network:** `nginx`

**Configuração de Proxy:**
```nginx
# Proxy para Laravel
location / {
    proxy_pass http://app:8000;
}

# Proxy para Vite HMR
location ^~ /@vite/ {
    proxy_pass http://node:5173;
}
```

---

### Fase 4: Containers Opcionais (Profile: tools)

#### 4.1. Container `phpmyadmin`

**Inicialização:** Apenas com `--profile tools`

**Processo:**
```
1. Pull da imagem phpmyadmin:latest
2. Criação do container sdc_phpmyadmin_dev
3. Configuração:
   - PMA_HOST=db
   - PMA_USER=root
4. Inicialização do phpMyAdmin
5. Status: RUNNING ✅
```

**Importância:**
- **Baixa**: Ferramenta de desenvolvimento
- Interface web para gerenciar MySQL
- Útil para debug e administração

**Comunicação:**
- **Recebe conexões de:** Cliente (browser) na porta 8080
- **Faz conexões para:** `db:3306` (MySQL)
- **Hostname na network:** `phpmyadmin`

---

#### 4.2. Container `redis-commander`

**Inicialização:** Apenas com `--profile tools`

**Processo:**
```
1. Pull da imagem rediscommander/redis-commander:latest
2. Criação do container sdc_redis_commander_dev
3. Configuração:
   - REDIS_HOSTS=local:redis:6379
4. Inicialização do Redis Commander
5. Status: RUNNING ✅
```

**Importância:**
- **Baixa**: Ferramenta de desenvolvimento
- Interface web para gerenciar Redis
- Visualização de chaves, valores e estatísticas

**Comunicação:**
- **Recebe conexões de:** Cliente (browser) na porta 8081
- **Faz conexões para:** `redis:6379` (Redis)
- **Hostname na network:** `redis-commander`

---

## 🌐 Topologia de Rede

### Network: `sdc-dev_sdc_network`

**Tipo:** Bridge Network  
**Subnet:** 172.25.0.0/16  
**Gateway:** 172.25.0.1  
**Driver:** bridge

### Distribuição de IPs (Automática)

```
172.25.0.1  → Gateway (Docker)
172.25.0.2  → nginx
172.25.0.3  → app
172.25.0.4  → db
172.25.0.5  → redis
172.25.0.6  → mailhog
172.25.0.7  → phpmyadmin (se ativo)
172.25.0.8  → redis-commander (se ativo)
```

### Resolução DNS Interna

Docker fornece resolução DNS automática usando os **hostnames** definidos:

```bash
# Dentro de qualquer container, você pode usar:
ping db              # Resolve para 172.25.0.4
ping redis           # Resolve para 172.25.0.5
ping app             # Resolve para 172.25.0.3
ping mailhog         # Resolve para 172.25.0.6
```

**Exemplo de uso no código Laravel:**
```php
// .env
DB_HOST=db           // Não precisa do IP!
REDIS_HOST=redis     // Não precisa do IP!
MAIL_HOST=mailhog    // Não precisa do IP!
```

---

## 🔄 Containers e Comunicação

### Matriz de Comunicação

| Container | Recebe de | Envia para | Protocolo | Porta |
|-----------|-----------|------------|-----------|-------|
| **nginx** | Cliente (80/443) | `app:8000`, `node:5173` | HTTP/HTTPS | 80, 443 |
| **app** | `nginx` | `db:3306`, `redis:6379`, `mailhog:1025` | HTTP, MySQL, Redis, SMTP | 8000, 9000 |
| **db** | `app`, `phpmyadmin` | - | MySQL | 3306 |
| **redis** | `app`, `redis-commander` | - | Redis | 6379 |
| **mailhog** | `app` | - | SMTP | 1025 |
| **phpmyadmin** | Cliente (8080) | `db:3306` | HTTP, MySQL | 8080 |
| **redis-commander** | Cliente (8081) | `redis:6379` | HTTP, Redis | 8081 |

### Detalhamento por Container

#### 1. Container `nginx` (Reverse Proxy)

**Função:** Gateway HTTP/HTTPS da aplicação

**Comunicação Externa:**
- **Porta 80:** HTTP (desenvolvimento)
- **Porta 443:** HTTPS (produção)

**Comunicação Interna:**
```nginx
# Proxy para aplicação Laravel
upstream octane {
    server app:8000;  # Laravel Octane/Artisan Serve
}

# Proxy para PHP-FPM (fallback)
upstream php-fpm {
    server app:9000;  # PHP-FPM
}

# Proxy para Vite HMR
upstream vite {
    server node:5173;  # Vite Dev Server
}
```

**Fluxo de Requisição:**
```
Cliente → nginx:80 → app:8000 → Resposta
```

**Importância:**
- ✅ **Isolamento**: Cliente não acessa diretamente o Laravel
- ✅ **Performance**: Serve arquivos estáticos diretamente
- ✅ **Segurança**: Pode adicionar rate limiting, SSL, etc.
- ✅ **Flexibilidade**: Pode rotear para múltiplos backends

---

#### 2. Container `app` (Laravel)

**Função:** Aplicação principal - Lógica de negócio

**Comunicação com MySQL (`db`):**
```php
// config/database.php
'mysql' => [
    'host' => env('DB_HOST', 'db'),  // Resolve para 172.25.0.4
    'port' => env('DB_PORT', '3306'),
    // ...
]
```

**Fluxo:**
```
app → db:3306 → MySQL → Resposta
```

**Comunicação com Redis (`redis`):**
```php
// config/database.php
'redis' => [
    'host' => env('REDIS_HOST', 'redis'),  // Resolve para 172.25.0.5
    'port' => env('REDIS_PORT', '6379'),
    // ...
]
```

**Fluxo:**
```
app → redis:6379 → Redis → Resposta
```

**Comunicação com Mailhog (`mailhog`):**
```php
// config/mail.php
'smtp' => [
    'host' => env('MAIL_HOST', 'mailhog'),  // Resolve para 172.25.0.6
    'port' => env('MAIL_PORT', '1025'),
    // ...
]
```

**Fluxo:**
```
app → mailhog:1025 → SMTP → Email capturado
```

**Importância:**
- ✅ **Core da aplicação**: Toda lógica de negócio
- ✅ **Isolamento**: Não exposto diretamente ao cliente
- ✅ **Escalabilidade**: Pode ter múltiplas réplicas (produção)

---

#### 3. Container `db` (MySQL)

**Função:** Banco de dados relacional

**Comunicação:**
```
app → db:3306 → MySQL Server
phpmyadmin → db:3306 → MySQL Server
```

**Configuração de Acesso:**
```yaml
environment:
  MYSQL_ROOT_PASSWORD: root
  MYSQL_DATABASE: sdc
  MYSQL_USER: sdc
  MYSQL_PASSWORD: secret
```

**Importância:**
- ✅ **Persistência**: Dados duradouros
- ✅ **ACID**: Transações garantidas
- ✅ **Relacionamentos**: Dados estruturados
- ✅ **Performance**: Índices e otimizações

**Health Check:**
```yaml
healthcheck:
  test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
  interval: 10s
  timeout: 5s
  retries: 5
```

---

#### 4. Container `redis`

**Função:** Cache, Sessões e Filas

**Comunicação:**
```
app → redis:6379 → Redis Server
redis-commander → redis:6379 → Redis Server
```

**Configuração:**
```yaml
command: redis-server --appendonly yes --maxmemory 256mb
```

**Uso no Laravel:**
```php
// Cache
Cache::put('key', 'value', 3600);  // → redis:6379

// Session
Session::put('user_id', 123);      // → redis:6379

// Queue
dispatch(new Job());                // → redis:6379
```

**Importância:**
- ✅ **Performance**: Cache reduz carga no MySQL
- ✅ **Sessões**: Armazena sessões de usuários
- ✅ **Filas**: Processamento assíncrono
- ✅ **Pub/Sub**: Comunicação entre processos

---

#### 5. Container `mailhog`

**Função:** Captura e visualização de emails

**Comunicação:**
```
app → mailhog:1025 → SMTP Server
Cliente → mailhog:8025 → Web UI
```

**Importância:**
- ✅ **Desenvolvimento**: Testa emails sem SMTP real
- ✅ **Debug**: Visualiza conteúdo dos emails
- ✅ **Isolamento**: Não envia emails reais

---

## 📥 Fluxo de Requisições

### Requisição HTTP Completa

```
1. Cliente (Browser)
   ↓
   GET http://localhost/
   ↓
2. Nginx (Porta 80)
   ├─ Verifica se é arquivo estático
   ├─ Se não, proxy para app:8000
   └─ Proxy headers: X-Real-IP, X-Forwarded-For
   ↓
3. App (Laravel - Porta 8000)
   ├─ Recebe requisição HTTP
   ├─ Processa rota
   ├─ Carrega Controller
   ├─ Executa lógica de negócio
   │  ├─ Query MySQL? → db:3306
   │  ├─ Cache? → redis:6379
   │  └─ Email? → mailhog:1025
   ├─ Renderiza view (Inertia.js)
   └─ Retorna resposta HTTP
   ↓
4. Nginx
   ├─ Recebe resposta
   ├─ Adiciona headers
   └─ Envia para cliente
   ↓
5. Cliente (Browser)
   └─ Renderiza página
```

### Requisição com Assets (Vite HMR)

```
1. Cliente (Browser)
   ↓
   GET http://localhost/@vite/client
   ↓
2. Nginx
   ├─ Detecta /@vite/
   └─ Proxy para node:5173
   ↓
3. Node (Vite Dev Server)
   ├─ WebSocket connection
   ├─ Hot Module Replacement
   └─ Envia atualizações em tempo real
   ↓
4. Cliente (Browser)
   └─ Atualiza código sem reload
```

### Requisição de Banco de Dados

```
1. App (Laravel)
   ↓
   User::find(1)
   ↓
2. Laravel Eloquent
   ├─ Prepara query SQL
   └─ Conecta em db:3306
   ↓
3. MySQL (db container)
   ├─ Executa query
   ├─ Retorna dados
   └─ Fecha conexão
   ↓
4. App (Laravel)
   ├─ Processa resultado
   └─ Retorna para controller
```

### Requisição de Cache

```
1. App (Laravel)
   ↓
   Cache::get('key')
   ↓
2. Laravel Cache
   ├─ Conecta em redis:6379
   └─ GET key
   ↓
3. Redis (redis container)
   ├─ Verifica memória
   ├─ Retorna valor (se existe)
   └─ Retorna null (se não existe)
   ↓
4. App (Laravel)
   └─ Retorna valor ou busca no MySQL
```

---

## 🔍 Diagnóstico de Performance

### Problema Identificado: LCP de 27.20s

**Causa Raiz:**
O Vite dev server rodando dentro do Docker causava latência significativa no carregamento de assets.

**Solução:**
1. **Executar Vite no host** (fora do Docker)
   ```bash
   # No host
   npm run dev
   ```

2. **Nginx proxy para Vite no host**
   ```nginx
   upstream vite {
       server host.docker.internal:5173;  # Host, não container
   }
   ```

3. **Resultado:**
   - LCP reduzido de 27.20s para < 2.5s
   - Hot reload mais rápido
   - Menor uso de recursos do Docker

### Monitoramento de Containers

**Verificar status:**
```bash
docker compose -f docker/docker-compose.yml ps
```

**Ver logs:**
```bash
# Todos os containers
docker compose -f docker/docker-compose.yml logs -f

# Container específico
docker compose -f docker/docker-compose.yml logs -f app
```

**Verificar recursos:**
```bash
docker stats
```

**Verificar network:**
```bash
docker network inspect sdc-dev_sdc_network
```

### Troubleshooting

**Container não inicia:**
1. Verificar logs: `docker compose logs <service>`
2. Verificar dependências: `docker compose ps`
3. Verificar health checks: `docker inspect <container>`

**Problemas de conexão:**
1. Verificar network: `docker network ls`
2. Verificar DNS: `docker exec <container> ping <hostname>`
3. Verificar portas: `docker port <container>`

**Performance lenta:**
1. Verificar recursos: `docker stats`
2. Verificar volumes: `docker volume ls`
3. Considerar executar Vite no host

---

## 📚 Referências

- [Docker Networking](https://docs.docker.com/network/)
- [Docker Compose](https://docs.docker.com/compose/)
- [Laravel Octane](https://laravel.com/docs/octane)
- [Nginx Reverse Proxy](https://nginx.org/en/docs/http/ngx_http_proxy_module.html)
- [Vite HMR](https://vitejs.dev/guide/features.html#hot-module-replacement)

---

**Última atualização:** 2024-11-26  
**Versão:** 1.0.0

