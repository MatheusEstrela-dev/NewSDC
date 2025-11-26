# 🐳 SDC - Arquitetura Docker

Arquitetura Docker otimizada para **Desenvolvimento** e **Produção** do Sistema de Defesa Civil (SDC).

## 📋 Índice

- [Requisitos](#requisitos)
- [Desenvolvimento](#desenvolvimento)
- [Produção](#produção)
- [Arquitetura](#arquitetura)
- [Monitoramento](#monitoramento)
- [Backup](#backup)
- [Troubleshooting](#troubleshooting)

> 📖 **Documentação Completa:**
> - [ARQUITETURA.md](./ARQUITETURA.md) - Arquitetura completa, comunicação entre containers e topologia de rede
> - [FLUXO_INICIALIZACAO.md](./FLUXO_INICIALIZACAO.md) - Processo de inicialização passo a passo com timeline detalhada

## 📦 Requisitos

### Desenvolvimento
- Docker Desktop 4.x+
- Docker Compose v2.x+
- 8GB RAM mínimo
- 20GB espaço em disco

### Produção
- Docker Engine 24.x+
- Docker Compose v2.x+ ou Docker Swarm
- 16GB RAM mínimo (recomendado 32GB)
- 100GB SSD
- Linux (Ubuntu 22.04+ recomendado)

## 🚀 Desenvolvimento

### Quick Start

```bash
# 1. Clone o repositório
git clone https://github.com/seu-repo/sdc.git
cd sdc

# 2. Copie o arquivo de ambiente
cp docker/env.example .env

# 3. Inicie o ambiente
make dev

# ou sem Makefile:
docker compose -f docker/docker-compose.yml up -d
```

### Serviços Disponíveis

| Serviço | URL | Descrição |
|---------|-----|-----------|
| App | http://localhost | Aplicação Laravel |
| Mailhog | http://localhost:8025 | Email testing |
| phpMyAdmin | http://localhost:8080 | DB Management (profile: tools) |
| Redis Commander | http://localhost:8081 | Redis UI (profile: tools) |

### Comandos Úteis

```bash
# Ver logs
make logs

# Acessar shell do container
make shell

# Rodar migrations
make migrate

# Limpar caches
make cache-clear

# Rebuild
make dev-build

# Parar ambiente
make dev-down
```

### Hot Reload

O ambiente de desenvolvimento possui hot reload automático:

- **PHP**: Alterações são refletidas imediatamente (OPcache desabilitado)
- **Frontend**: Vite HMR configurado (porta 5173)

Para rodar o Vite separadamente (melhor performance no Windows):

```bash
# No host (fora do Docker)
npm run dev

# Ou via Docker
make npm-dev
```

### Debugging com Xdebug

1. Configure seu IDE (VSCode/PHPStorm) para ouvir na porta 9003
2. Adicione breakpoints no código
3. Acesse a aplicação com `?XDEBUG_TRIGGER=1` ou configure a extensão do browser

## 🏭 Produção

### Arquitetura de Alta Disponibilidade

```
                    ┌─────────────┐
                    │   Traefik   │
                    │ (Load Bal)  │
                    └─────┬───────┘
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
   ┌────▼────┐      ┌────▼────┐      ┌────▼────┐
   │  App 1  │      │  App 2  │      │  App 3  │
   │(Octane) │      │(Octane) │      │(Octane) │
   └────┬────┘      └────┬────┘      └────┬────┘
        │                 │                 │
        └────────┬────────┴────────┬───────┘
                 │                 │
          ┌──────▼──────┐   ┌─────▼──────┐
          │MySQL Primary│   │Redis Master│
          │  (Master)   │   │            │
          └──────┬──────┘   └─────┬──────┘
                 │                 │
          ┌──────▼──────┐   ┌─────▼──────┐
          │MySQL Replica│   │Redis Replic│
          │ (Read-only) │   │            │
          └─────────────┘   └────────────┘
```

### Deploy

```bash
# 1. Configure variáveis de produção
cp docker/env.example .env
vim .env  # Ajuste para produção

# 2. Build e deploy
make deploy

# ou:
docker compose -f docker/docker-compose.prod.yml build
docker compose -f docker/docker-compose.prod.yml up -d
```

### Scaling

```bash
# Aumentar réplicas da aplicação
make prod-scale N=5

# ou:
docker compose -f docker/docker-compose.prod.yml up -d --scale app=5 --scale queue=3
```

### SSL/TLS

O Traefik gerencia certificados SSL automaticamente via Let's Encrypt:

1. Configure `APP_DOMAIN` e `ACME_EMAIL` no `.env`
2. Aponte o DNS para o servidor
3. O certificado será obtido automaticamente

### Health Checks

Todos os serviços possuem health checks configurados:

```bash
# Verificar status
docker compose -f docker/docker-compose.prod.yml ps

# Health check manual
curl http://localhost/health
```

## 🏗️ Arquitetura

Para entender completamente como os containers se comunicam, a ordem de inicialização e a topologia de rede, consulte a [documentação completa de arquitetura](./ARQUITETURA.md).

**Resumo rápido:**
- **7 containers principais** em uma bridge network isolada
- **Nginx** como reverse proxy (porta 80/443)
- **Laravel** como aplicação principal (porta 8000)
- **MySQL** para dados persistentes (porta 3306)
- **Redis** para cache/sessões/filas (porta 6379)
- **Mailhog** para captura de emails (porta 8025)
- **Ferramentas opcionais** (phpMyAdmin, Redis Commander)

## 📊 Monitoramento

### Stack de Observabilidade

- **Prometheus**: Coleta de métricas
- **Grafana**: Dashboards e visualização
- **Loki**: Agregação de logs
- **Promtail**: Coleta de logs

### Acessos

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| Grafana | https://grafana.seu-dominio.com | admin / (definido no .env) |
| Prometheus | https://prometheus.seu-dominio.com | (basic auth) |
| Traefik Dashboard | https://traefik.seu-dominio.com | (basic auth) |

### Alertas Configurados

- Application Down
- High Response Time (>2s)
- High Error Rate (>5%)
- MySQL Down / High Connections / Slow Queries
- Redis Down / High Memory
- High CPU/Memory/Disk Usage
- Container Issues

## 💾 Backup

### Automático

Backups são executados automaticamente a cada 6 horas:
- MySQL: dump completo compactado
- Redis: snapshot RDB
- Files: storage (exceto logs/cache)

### Manual

```bash
# Executar backup manual
make backup

# ou:
docker compose -f docker/docker-compose.prod.yml exec backup /backup/backup.sh
```

### Restore

```bash
# MySQL
gunzip < backup_file.sql.gz | mysql -u root -p database_name

# Redis
redis-cli DEBUG RELOAD

# Files
tar -xzf backup_files.tar.gz -C /var/www/storage
```

### Upload para S3

Configure as variáveis AWS no `.env`:
```
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
BACKUP_S3_BUCKET=sdc-backups
```

## 🔧 Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker compose logs -f app

# Verificar status
docker compose ps

# Reiniciar serviço específico
docker compose restart app
```

### Problemas de permissão

```bash
# Ajustar UID/GID no .env
HOST_UID=$(id -u)
HOST_GID=$(id -g)

# Rebuild
make dev-build
```

### MySQL não conecta

```bash
# Verificar se está pronto
docker compose exec db mysqladmin ping -h localhost

# Ver logs
docker compose logs db
```

### Performance lenta (Windows/Mac)

1. Use volumes nomeados para `vendor` e `node_modules`
2. Execute o Vite no host ao invés do container
3. Aumente recursos do Docker Desktop

### Limpar tudo

```bash
# Limpar containers e volumes
make clean

# Limpar TUDO (imagens incluídas)
make clean-all
```

## 📁 Estrutura de Arquivos

```
docker/
├── config/
│   ├── php/
│   │   ├── dev.ini
│   │   └── xdebug.ini
│   ├── php-fpm/
│   │   └── dev.conf
│   └── roadrunner/
│       └── .rr.prod.yaml
├── mysql/
│   ├── dev.cnf
│   ├── prod-primary.cnf
│   └── prod-replica.cnf
├── nginx/
│   ├── dev.conf
│   ├── prod.conf
│   └── proxy_params.conf
├── monitoring/
│   ├── prometheus.yml
│   ├── alerts/
│   ├── loki.yml
│   └── promtail.yml
├── backup/
│   └── backup-prod.sh
├── scripts/
│   ├── entrypoint.dev.sh
│   ├── healthcheck.sh
│   └── healthcheck.prod.sh
├── docker-compose.yml          # Desenvolvimento
├── docker-compose.prod.yml     # Produção
├── Dockerfile.dev
├── Dockerfile.prod
├── env.example
└── README.md
```

## 🔐 Segurança

### Produção

- Todos os serviços internos em rede isolada
- Apenas Traefik exposto publicamente
- SSL/TLS obrigatório
- Headers de segurança configurados
- Rate limiting implementado
- Secrets via variáveis de ambiente

### Recomendações

1. Nunca exponha MySQL/Redis diretamente
2. Use senhas fortes e únicas
3. Mantenha imagens atualizadas
4. Configure firewall do host
5. Monitore logs de segurança

## 📞 Suporte

Em caso de problemas:

1. Verifique os logs: `make logs`
2. Consulte este README
3. Abra uma issue no repositório

