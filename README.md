# 🚨 SDC - Sistema da Defesa Civil

> **Sistema de alta performance para gestão e monitoramento de emergências da Defesa Civil**

[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com/)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.4-4FC08D?style=flat&logo=vue.js&logoColor=white)](https://vuejs.org/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker&logoColor=white)](https://www.docker.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📋 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Características Principais](#-características-principais)
- [Arquitetura](#-arquitetura)
- [Stack Tecnológico](#-stack-tecnológico)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Uso](#-uso)
- [Documentação](#-documentação)
- [Performance](#-performance)
- [Contribuindo](#-contribuindo)
- [Licença](#-licença)

---

## 🎯 Sobre o Projeto

O **SDC (Sistema da Defesa Civil)** é uma plataforma moderna e robusta desenvolvida para suportar operações críticas 24/7 da Defesa Civil, capaz de gerenciar **100.000+ usuários simultâneos** com alta disponibilidade e baixa latência.

O sistema foi arquitetado com foco em:
- ⚡ **Alta Performance**: TTFB < 20ms com Laravel Octane
- 🔄 **Escalabilidade**: Arquitetura horizontalmente escalável
- 🛡️ **Confiabilidade**: Sistema crítico 24/7 com redundância
- 🔌 **Integrações**: Hub de integração dinâmica plug-and-play
- 📊 **Monitoramento**: Observabilidade completa com Prometheus/Grafana

---

## ✨ Características Principais

### 🚀 Performance e Escalabilidade
- **Laravel Octane + RoadRunner**: Framework em memória, eliminando boot overhead
- **Inertia.js SSR**: Renderização server-side para SEO e performance
- **Redis Stack**: Cache distribuído e filas de alta performance
- **Load Balancing**: Distribuição de carga com Nginx
- **Rate Limiting Inteligente**: 6 níveis de throttling (60 a 100.000 req/min)

### 🔌 Integrações e Webhooks
- **Hub de Integração Dinâmica**: REST, GraphQL, SOAP, Webhooks
- **Templates Pré-configurados**: Salesforce, SAP, Stripe, HubSpot
- **Webhooks Bidirecionais**: Envio e recebimento com validação HMAC
- **Processamento Assíncrono**: Jobs com priorização e retry automático

### 📚 Documentação e API
- **Swagger/OpenAPI**: Documentação interativa completa
- **Try it Out**: Teste de endpoints diretamente na interface
- **Exemplos de Código**: Snippets prontos para integração
- **Autenticação Integrada**: Teste com tokens reais

### 📊 Monitoramento e Observabilidade
- **Health Checks**: Endpoints de saúde para load balancers
- **Métricas Prometheus**: Coleta de métricas em tempo real
- **Grafana Dashboards**: Visualização de performance e saúde do sistema
- **Activity Logger**: Sistema de logging centralizado com 6 tipos de log

### 🔐 Segurança
- **Laravel Sanctum**: Autenticação stateless para APIs
- **Rate Limiting Multi-camada**: Proteção contra DDoS
- **Validação HMAC**: Segurança em webhooks
- **Security Headers**: Headers de segurança configurados

### 🐳 DevOps e CI/CD
- **Docker Compose**: Ambiente completo containerizado
- **Jenkins Pipeline**: CI/CD automatizado
- **Backup Automatizado**: Backups de banco de dados agendados
- **Multi-ambiente**: Configurações para dev, staging e produção

---

## 🏗️ Arquitetura

```
┌─────────────────────────────────────────────────────────────┐
│                    INTERNET / USUÁRIOS                      │
│                   (100.000+ simultâneos)                    │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                  NGINX (Reverse Proxy)                      │
│  • Rate Limiting (API: 60/min, Webhooks: 1000/min)         │
│  • SSL/TLS Termination                                      │
│  • Load Balancer (3 instâncias app)                        │
│  • Static Assets (CDN)                                      │
└──────────────────────────┬──────────────────────────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   APP #1     │  │   APP #2     │  │   APP #3     │
│  Octane      │  │  Octane      │  │  Octane      │
│  (2 CPU/1GB) │  │  (2 CPU/1GB) │  │  (2 CPU/1GB) │
└──────┬───────┘  └──────┬───────┘  └──────┬───────┘
       │                 │                 │
       └─────────────────┼─────────────────┘
                         │
        ┌────────────────┼────────────────┬──────────────┐
        │                │                │              │
        ▼                ▼                ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  SSR Server  │ │   Database   │ │    Redis     │ │    Backup    │
│  (Inertia)   │ │   (MySQL)    │ │   (Stack)    │ │   Service    │
│  Port: 13714 │ │   Port: 3306 │ │  Port: 6379  │ │   (Cron 6h)  │
└──────────────┘ └──────┬───────┘ └──────┬───────┘ └──────────────┘
                        │                │
                        ▼                ▼
                 ┌──────────────┐ ┌──────────────┐
                 │  DB Replica  │ │ Redis Slave  │
                 │  (Read-only) │ │  (Failover)  │
                 └──────────────┘ └──────────────┘
```

### Componentes Principais

1. **Nginx**: Reverse proxy com rate limiting e load balancing
2. **Laravel Octane**: Aplicação em memória para máxima performance
3. **Inertia SSR**: Renderização server-side para Vue.js
4. **MySQL**: Banco de dados principal com réplica para leitura
5. **Redis**: Cache, sessões e filas de alta performance
6. **Backup Service**: Backups automatizados do banco de dados

---

## 🛠️ Stack Tecnológico

### Backend
- **PHP 8.3+**: Linguagem principal
- **Laravel 12**: Framework PHP moderno
- **Laravel Octane**: High-performance application server
- **RoadRunner**: Application server para PHP
- **MySQL 8.0**: Banco de dados relacional
- **Redis 7.0**: Cache e filas

### Frontend
- **Vue.js 3.4**: Framework JavaScript reativo
- **Inertia.js**: Bridge entre Laravel e Vue
- **Tailwind CSS**: Framework CSS utility-first
- **Vite**: Build tool moderna e rápida

### DevOps & Infraestrutura
- **Docker & Docker Compose**: Containerização
- **Nginx**: Web server e reverse proxy
- **Jenkins**: CI/CD pipeline
- **Prometheus**: Coleta de métricas
- **Grafana**: Visualização de métricas
- **Alertmanager**: Gerenciamento de alertas

### Ferramentas de Desenvolvimento
- **Composer**: Gerenciador de dependências PHP
- **NPM/Bun**: Gerenciador de pacotes JavaScript
- **Makefile**: Automação de tarefas
- **Justfile**: Task runner alternativo

---

## 📦 Requisitos

### Desenvolvimento Local
- **PHP**: 8.3 ou superior
- **Composer**: 2.x
- **Node.js**: 18.x ou superior (ou Bun)
- **Docker**: 20.10+ e Docker Compose 2.0+
- **MySQL**: 8.0+ (ou via Docker)
- **Redis**: 7.0+ (ou via Docker)

### Produção
- **Servidor**: Linux (Ubuntu 22.04+ recomendado)
- **CPU**: Múltiplos cores (recomendado 4+)
- **RAM**: Mínimo 4GB (recomendado 8GB+)
- **Disco**: SSD recomendado
- **Rede**: Conexão estável com alta largura de banda

---

## 🚀 Instalação

### Pré-requisitos

Certifique-se de ter instalado:
- Docker e Docker Compose
- Git

### Passo a Passo

1. **Clone o repositório**
```bash
git clone https://github.com/MatheusEstrela-dev/NewSDC.git
cd NewSDC/SDC
```

2. **Configure o ambiente**
```bash
cp docker/env.example docker/.env
# Edite docker/.env com suas configurações
```

3. **Inicie os containers**
```bash
docker-compose -f docker/docker-compose.yml up -d
```

4. **Instale as dependências**
```bash
# Backend
docker-compose -f docker/docker-compose.yml exec app composer install

# Frontend
docker-compose -f docker/docker-compose.yml exec app npm install
# ou
docker-compose -f docker/docker-compose.yml exec app bun install
```

5. **Configure a aplicação**
```bash
# Copie o arquivo de ambiente
docker-compose -f docker/docker-compose.yml exec app cp .env.example .env

# Gere a chave da aplicação
docker-compose -f docker/docker-compose.yml exec app php artisan key:generate

# Execute as migrações
docker-compose -f docker/docker-compose.yml exec app php artisan migrate

# Compile os assets
docker-compose -f docker/docker-compose.yml exec app npm run build
```

6. **Acesse a aplicação**
- **Frontend**: http://localhost
- **API**: http://localhost/api
- **Swagger**: http://localhost/api/documentation
- **Health Check**: http://localhost/api/health

### Usando Makefile (Alternativa)

O projeto inclui um `Makefile` com comandos úteis:

```bash
# Ver todos os comandos disponíveis
make help

# Iniciar ambiente de desenvolvimento
make dev-up

# Instalar dependências
make install

# Executar migrações
make migrate

# Compilar assets
make build
```

---

## 💻 Uso

### Desenvolvimento

```bash
# Iniciar ambiente de desenvolvimento
docker-compose -f docker/docker-compose.yml up -d

# Executar migrations
docker-compose -f docker/docker-compose.yml exec app php artisan migrate

# Compilar assets em modo desenvolvimento (hot reload)
docker-compose -f docker/docker-compose.yml exec app npm run dev

# Executar testes
docker-compose -f docker/docker-compose.yml exec app php artisan test
```

### Produção

```bash
# Build para produção
docker-compose -f docker/docker-compose.prod.yml build

# Iniciar em produção
docker-compose -f docker/docker-compose.prod.yml up -d

# Compilar assets para produção
docker-compose -f docker/docker-compose.prod.yml exec app npm run build
```

### Comandos Úteis

```bash
# Acessar container da aplicação
docker-compose -f docker/docker-compose.yml exec app bash

# Ver logs
docker-compose -f docker/docker-compose.yml logs -f app

# Executar artisan commands
docker-compose -f docker/docker-compose.yml exec app php artisan [command]

# Acessar MySQL
docker-compose -f docker/docker-compose.yml exec mysql mysql -u root -p

# Acessar Redis CLI
docker-compose -f docker/docker-compose.yml exec redis redis-cli
```

---

## 📚 Documentação

A documentação completa do projeto está disponível na pasta `Doc/`:

### Documentação Principal
- **[Arquitetura Completa](Doc/ARQUITETURA_COMPLETA_OVERVIEW.md)**: Visão geral detalhada da arquitetura
- **[Resumo Completo](Doc/RESUMO_COMPLETO_FINAL.md)**: Resumo de todas as funcionalidades
- **[Guia de Monitoramento](Doc/MONITORING_GUIDE.md)**: Configuração e uso do sistema de monitoramento

### Guias Específicos
- **[Swagger/OpenAPI](Doc/GUIA_PLUGFIELD_SWAGGER.md)**: Documentação da API
- **[Webhooks](Doc/WEBHOOK_API_GUIDE.md)**: Guia de integração via webhooks
- **[Backup MySQL](Doc/BACKUP_DATABASE_MYSQL.md)**: Estratégias de backup
- **[Jenkins Pipeline](Doc/JENKINS_PIPELINE.md)**: Configuração CI/CD
- **[Inertia SSR](Doc/INERTIA_SSR_IMPLEMENTACAO.md)**: Implementação SSR

### Documentação Técnica
- **[Docker](Doc/DOCKER_ARCHITECTURE.md)**: Arquitetura Docker
- **[PHP 8.3](Doc/PHP_8.3_COMPATIBILITY.md)**: Compatibilidade e migração
- **[Health Dashboard](Doc/HEALTH_DASHBOARD_VISUAL.md)**: Dashboard de saúde do sistema

---

## ⚡ Performance

### Métricas de Performance

- **TTFB (Time To First Byte)**: < 20ms
- **Throughput**: 50.000+ requisições/segundo
- **Latência**: < 5ms (Nginx layer)
- **Concorrência**: Suporta 100.000+ usuários simultâneos
- **Uptime**: Sistema crítico 24/7 com redundância

### Otimizações Implementadas

- ✅ Laravel Octane para eliminar boot overhead
- ✅ Redis para cache distribuído
- ✅ Database replication para leitura
- ✅ CDN para assets estáticos
- ✅ GZIP compression
- ✅ Connection pooling
- ✅ Query optimization
- ✅ Eager loading de relacionamentos

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor, siga estes passos:

1. **Fork o projeto**
2. **Crie uma branch para sua feature** (`git checkout -b feature/AmazingFeature`)
3. **Commit suas mudanças** (`git commit -m 'Add some AmazingFeature'`)
4. **Push para a branch** (`git push origin feature/AmazingFeature`)
5. **Abra um Pull Request**

### Padrões de Código

- Siga os padrões PSR-12 para PHP
- Use ESLint/Prettier para JavaScript/Vue
- Escreva testes para novas funcionalidades
- Mantenha a documentação atualizada

---

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

## 👥 Autores

- **Matheus Estrela** - *Desenvolvimento* - [MatheusEstrela-dev](https://github.com/MatheusEstrela-dev)

---

## 🙏 Agradecimentos

- Laravel Community
- Vue.js Team
- Todos os contribuidores de código aberto que tornaram este projeto possível

---

## 📞 Suporte

Para suporte, abra uma [issue](https://github.com/MatheusEstrela-dev/NewSDC/issues) no GitHub.

---

<div align="center">

**Desenvolvido com ❤️ para a Defesa Civil**

[⬆ Voltar ao topo](#-sdc---sistema-da-defesa-civil)

</div>

