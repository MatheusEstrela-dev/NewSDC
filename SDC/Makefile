# ============================================================================
# SDC - MAKEFILE COMPLETO PARA DOCKER
# Instrumentalização completa do ambiente com um único comando
# ============================================================================
# Uso:
#   make start            # Bridge + build-all + up-all (stack ~15 containers, sem Ollama)
#   make bridge           # Só cria a rede bridge sdc_network
#   make build-all        # Build completo de toda stack
#   make up-all           # Sobe toda stack (dev + monitoring)
#   make dev              # Ambiente dev padrão
#   make prod             # Ambiente produção
#   make monitor          # Apenas monitoring
#   make help             # Ajuda completa
# ============================================================================

.PHONY: help
.DEFAULT_GOAL := help

# ============================================================================
# CORES E FORMATAÇÃO (Cross-Platform)
# ============================================================================
ifeq ($(OS),Windows_NT)
    # Windows - Usar códigos ANSI diretos (requer terminal moderno)
    GREEN  := [32m
    YELLOW := [33m
    BLUE   := [34m
    RED    := [31m
    CYAN   := [36m
    WHITE  := [37m
    BOLD   := [1m
    RESET  := [0m
    
    # User ID fallback para Windows
    HOST_UID := 1000
    HOST_GID := 1000
else
    # Linux/Mac - Usar tput para detecção correta
    GREEN  := $(shell tput -Txterm setaf 2)
    YELLOW := $(shell tput -Txterm setaf 3)
    BLUE   := $(shell tput -Txterm setaf 4)
    RED    := $(shell tput -Txterm setaf 1)
    CYAN   := $(shell tput -Txterm setaf 6)
    WHITE  := $(shell tput -Txterm setaf 7)
    BOLD   := $(shell tput -Txterm bold)
    RESET  := $(shell tput -Txterm sgr0)

    # User ID correto do host
    HOST_UID := $(shell id -u)
    HOST_GID := $(shell id -g)
endif

# ============================================================================
# VARIÁVEIS DE AMBIENTE
# ============================================================================
COMPOSE_DEV := docker compose -f docker/docker-compose.yml
COMPOSE_PROD := docker compose -f docker/docker-compose.prod.yml

# Build args
DOCKER_BUILDKIT ?= 1
COMPOSE_DOCKER_CLI_BUILD ?= 1

# Exportar variáveis
export DOCKER_BUILDKIT
export COMPOSE_DOCKER_CLI_BUILD
export HOST_UID
export HOST_GID

# ============================================================================
# 📚 HELP & DOCUMENTAÇÃO
# ============================================================================

help: ## 📚 Mostra ajuda completa e categorizada
	@echo ''
	@echo '${BOLD}${CYAN}╔════════════════════════════════════════════════════════════════════╗${RESET}'
	@echo '${BOLD}${CYAN}║     SDC - Sistema de Defesa Civil - Docker Management Tool         ║${RESET}'
	@echo '${BOLD}${CYAN}╚════════════════════════════════════════════════════════════════════╝${RESET}'
	@echo ''
	@echo '${BOLD}📦 BUILD & SETUP:${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## 📦/ {printf "  ${YELLOW}%-20s${RESET} %s\n", $$1, substr($$2, 4)}' $(MAKEFILE_LIST)
	@echo ''
	@echo '${BOLD}🚀 AMBIENTE COMPLETO (ONE-COMMAND):${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## 🚀/ {printf "  ${GREEN}%-20s${RESET} %s\n", $$1, substr($$2, 4)}' $(MAKEFILE_LIST)
	@echo ''
	@echo '${BOLD}🔧 DESENVOLVIMENTO:${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## 🔧/ {printf "  ${GREEN}%-20s${RESET} %s\n", $$1, substr($$2, 4)}' $(MAKEFILE_LIST)
	@echo ''
	@echo '${BOLD}🏭 PRODUÇÃO:${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## 🏭/ {printf "  ${BLUE}%-20s${RESET} %s\n", $$1, substr($$2, 4)}' $(MAKEFILE_LIST)
	@echo ''
	@echo '${BOLD}📊 MONITORAMENTO:${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## 📊/ {printf "  ${CYAN}%-20s${RESET} %s\n", $$1, substr($$2, 4)}' $(MAKEFILE_LIST)
	@echo ''
	@echo '${BOLD}🐚 ACESSO & DEBUG:${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## 🐚/ {printf "  ${WHITE}%-20s${RESET} %s\n", $$1, substr($$2, 4)}' $(MAKEFILE_LIST)
	@echo ''
	@echo '${BOLD}🗑️  LIMPEZA:${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## 🗑/ {printf "  ${RED}%-20s${RESET} %s\n", $$1, substr($$2, 5)}' $(MAKEFILE_LIST)
	@echo ''
	@echo '${BOLD}💾 BANCO DE DADOS:${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## 💾/ {printf "  ${YELLOW}%-20s${RESET} %s\n", $$1, substr($$2, 4)}' $(MAKEFILE_LIST)
	@echo ''
	@echo '${BOLD}🎨 FRONTEND:${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## 🎨/ {printf "  ${CYAN}%-20s${RESET} %s\n", $$1, substr($$2, 4)}' $(MAKEFILE_LIST)
	@echo ''
	@echo '${BOLD}Exemplos de uso:${RESET}'
	@echo '  ${GREEN}make start${RESET}                 # Bridge + build-all + up-all (sem Ollama)'
	@echo '  ${GREEN}make bridge${RESET}               # Só inicia a rede bridge'
	@echo '  ${GREEN}make build-all${RESET}              # Build completo de toda stack'
	@echo '  ${GREEN}make up-all${RESET}                 # Sobe ambiente completo (dev + monitoring)'
	@echo '  ${GREEN}make dev-full${RESET}               # Desenvolvimento com todas ferramentas'
	@echo '  ${BLUE}make prod${RESET}                   # Ambiente de produção'
	@echo '  ${CYAN}make status${RESET}                 # Status de todos containers'
	@echo ''

# ============================================================================
# 🚀 COMANDOS ONE-COMMAND (BUILD & UP COMPLETO)
# ============================================================================

build-all: ## 🚀 Build completo de TODA a stack (dev + monitoring + tools)
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo "${BOLD}${CYAN}  BUILD COMPLETO DE TODA A STACK${RESET}"
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo ""
	@echo "${YELLOW}→ Construindo imagens de desenvolvimento...${RESET}"
	@$(COMPOSE_DEV) build --parallel
	@echo "${GREEN}✓ Build de desenvolvimento concluído${RESET}"
	@echo ""
	@echo "${BOLD}${GREEN}✓ BUILD COMPLETO FINALIZADO COM SUCESSO!${RESET}"

build-dev: ## 📦 Build apenas desenvolvimento
	@echo "${YELLOW}→ Build de desenvolvimento...${RESET}"
	@$(COMPOSE_DEV) build --parallel
	@echo "${GREEN}✓ Build concluído${RESET}"

build-prod: ## 📦 Build apenas produção
	@echo "${YELLOW}→ Build de produção...${RESET}"
	@$(COMPOSE_PROD) build --parallel
	@echo "${GREEN}✓ Build concluído${RESET}"

build-clean: ## 📦 Build limpo (sem cache)
	@echo "${YELLOW}→ Build limpo (sem cache)...${RESET}"
	@$(COMPOSE_DEV) build --no-cache --parallel
	@echo "${GREEN}✓ Build limpo concluído${RESET}"

up-all: network-init ## 🚀 Sobe TODA stack (app + db + redis + monitoring + tools)
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo "${BOLD}${CYAN}  SUBINDO STACK COMPLETA${RESET}"
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo ""
	@echo "${YELLOW}→ Iniciando todos os serviços...${RESET}"
	@$(COMPOSE_DEV) --profile tools --profile ollama up -d
	@echo ""
	@echo "${GREEN}✓ Stack completa iniciada!${RESET}"
	@echo ""
	@$(MAKE) urls

down-all: ## 🚀 Para TODA stack
	@echo "${YELLOW}→ Parando todos os serviços...${RESET}"
	@$(COMPOSE_DEV) --profile tools --profile ollama down
	@echo "${GREEN}✓ Todos os serviços parados${RESET}"

restart-all: ## 🚀 Reinicia TODA stack
	@echo "${YELLOW}→ Reiniciando stack completa...${RESET}"
	@$(MAKE) down-all
	@$(MAKE) up-all

# ============================================================================
# 🔧 DESENVOLVIMENTO
# ============================================================================

dev: network-init ## 🔧 Ambiente de desenvolvimento padrão
	@echo "${YELLOW}→ Iniciando ambiente de desenvolvimento...${RESET}"
	@$(COMPOSE_DEV) up -d app nginx db redis mailhog queue node
	@echo "${GREEN}✓ Ambiente de desenvolvimento iniciado!${RESET}"
	@echo ""
	@echo "${CYAN}Serviços disponíveis:${RESET}"
	@echo "  App (Laravel):     ${GREEN}http://localhost:18001${RESET}"
	@echo "  Nginx:             ${GREEN}http://localhost:18082${RESET}"
	@echo "  Vite (HMR):        ${GREEN}http://localhost:15175${RESET}"
	@echo "  Mailhog:           ${GREEN}http://localhost:18026${RESET}"
	@echo "  MySQL:             ${GREEN}localhost:13307${RESET}"
	@echo "  Redis:             ${GREEN}localhost:16380${RESET}"

dev-full: network-init ## 🔧 Desenvolvimento + ferramentas (phpmyadmin, redis-commander)
	@echo "${YELLOW}→ Iniciando ambiente completo...${RESET}"
	@$(COMPOSE_DEV) --profile tools up -d
	@echo "${GREEN}✓ Ambiente completo iniciado!${RESET}"
	@echo ""
	@$(MAKE) urls

dev-minimal: network-init ## 🔧 Desenvolvimento mínimo (apenas app + db + redis)
	@echo "${YELLOW}→ Iniciando ambiente mínimo...${RESET}"
	@$(COMPOSE_DEV) up -d app db redis
	@echo "${GREEN}✓ Ambiente mínimo iniciado!${RESET}"

dev-build: ## 🔧 Rebuild desenvolvimento
	@echo "${YELLOW}→ Rebuild e reinicio do ambiente...${RESET}"
	@$(COMPOSE_DEV) down
	@$(COMPOSE_DEV) build
	@$(COMPOSE_DEV) up -d
	@echo "${GREEN}✓ Rebuild concluído${RESET}"

dev-logs: ## 🔧 Logs desenvolvimento (follow)
	@$(COMPOSE_DEV) logs -f

dev-ps: ## 🔧 Status dos containers dev
	@$(COMPOSE_DEV) ps

# ============================================================================
# 🏭 PRODUÇÃO
# ============================================================================

prod: ## 🏭 Ambiente de produção completo
	@echo "${YELLOW}→ Iniciando ambiente de produção...${RESET}"
	@$(COMPOSE_PROD) up -d
	@echo "${GREEN}✓ Ambiente de produção iniciado!${RESET}"

prod-build: ## 🏭 Build e deploy produção
	@echo "${YELLOW}→ Build de produção...${RESET}"
	@$(COMPOSE_PROD) build --no-cache
	@$(COMPOSE_PROD) up -d
	@echo "${GREEN}✓ Deploy de produção concluído${RESET}"

prod-logs: ## 🏭 Logs produção
	@$(COMPOSE_PROD) logs -f

prod-down: ## 🏭 Para produção
	@$(COMPOSE_PROD) down

prod-scale: ## 🏭 Scale workers (uso: make prod-scale N=5)
	@$(COMPOSE_PROD) up -d --scale queue=$(N)

# ============================================================================
# 📊 MONITORAMENTO
# ============================================================================

monitor: network-init ## 📊 Sobe stack de monitoramento completa
	@echo "${YELLOW}→ Iniciando stack de monitoramento...${RESET}"
	@$(COMPOSE_DEV) up -d prometheus grafana alertmanager node-exporter cadvisor mysql-exporter redis-exporter nginx-exporter blackbox-exporter
	@echo "${GREEN}✓ Stack de monitoramento iniciada!${RESET}"
	@echo ""
	@echo "${CYAN}Dashboards disponíveis:${RESET}"
	@echo "  Grafana:           ${GREEN}http://localhost:3000${RESET} (admin/admin@123)"
	@echo "  Prometheus:        ${GREEN}http://localhost:9090${RESET}"
	@echo "  Alertmanager:      ${GREEN}http://localhost:9093${RESET}"
	@echo "  cAdvisor:          ${GREEN}http://localhost:8080${RESET}"
	@echo ""
	@echo "  Node Exporter:     ${CYAN}http://localhost:9100/metrics${RESET}"
	@echo "  MySQL Exporter:    ${CYAN}http://localhost:9104/metrics${RESET}"
	@echo "  Redis Exporter:    ${CYAN}http://localhost:9121/metrics${RESET}"
	@echo "  Nginx Exporter:    ${CYAN}http://localhost:9113/metrics${RESET}"

monitor-down: ## 📊 Para monitoramento
	@echo "${YELLOW}→ Parando stack de monitoramento...${RESET}"
	@$(COMPOSE_DEV) stop prometheus grafana alertmanager node-exporter cadvisor mysql-exporter redis-exporter nginx-exporter blackbox-exporter
	@echo "${GREEN}✓ Monitoramento parado${RESET}"

monitor-logs: ## 📊 Logs do monitoramento
	@$(COMPOSE_DEV) logs -f prometheus grafana alertmanager

monitor-restart: ## 📊 Reinicia monitoramento
	@$(MAKE) monitor-down
	@$(MAKE) monitor

# ============================================================================
# 🐚 ACESSO & DEBUG
# ============================================================================

shell: ## 🐚 Shell app (www-data)
	@$(COMPOSE_DEV) exec app bash

shell-root: ## 🐚 Shell app (root)
	@$(COMPOSE_DEV) exec -u root app bash

shell-db: ## 🐚 MySQL CLI
	@$(COMPOSE_DEV) exec db mysql -u root -proot sdc

shell-redis: ## 🐚 Redis CLI
	@$(COMPOSE_DEV) exec redis redis-cli

shell-queue: ## 🐚 Shell queue worker
	@$(COMPOSE_DEV) exec queue bash

shell-node: ## 🐚 Shell node container
	@$(COMPOSE_DEV) exec node sh

# ============================================================================
# 🗑️ LIMPEZA & MANUTENÇÃO
# ============================================================================

clean: ## 🗑️ Para e remove containers (preserva volumes)
	@echo "${YELLOW}→ Limpando containers...${RESET}"
	@$(COMPOSE_DEV) down
	@echo "${GREEN}✓ Containers removidos${RESET}"

clean-volumes: ## 🗑️ Remove containers E volumes (⚠️  APAGA DADOS!)
	@echo "${RED}${BOLD}⚠️  ATENÇÃO: Isso vai APAGAR todos os dados do banco!${RESET}"
	@echo "${YELLOW}Pressione Ctrl+C para cancelar...${RESET}"
	@sleep 3
	@$(COMPOSE_DEV) down -v
	@echo "${GREEN}✓ Containers e volumes removidos${RESET}"

clean-images: ## 🗑️ Remove containers, volumes E imagens
	@echo "${RED}${BOLD}⚠️  ATENÇÃO: Isso vai remover TUDO!${RESET}"
	@echo "${YELLOW}Pressione Ctrl+C para cancelar...${RESET}"
	@sleep 5
	@$(COMPOSE_DEV) down -v --rmi all
	@echo "${GREEN}✓ Tudo removido${RESET}"

clean-system: ## 🗑️ Limpeza completa do Docker system
	@echo "${YELLOW}→ Limpando sistema Docker...${RESET}"
	@docker system prune -af --volumes
	@echo "${GREEN}✓ Sistema limpo${RESET}"

nuke: clean-images clean-system ## 🗑️ DESTRUIÇÃO TOTAL (⚠️  USA COM CUIDADO!)
	@echo "${RED}${BOLD}💣 NUKE EXECUTADO!${RESET}"

# ============================================================================
# 💾 BANCO DE DADOS
# ============================================================================

db-migrate: ## 💾 Executar migrations
	@$(COMPOSE_DEV) exec app php artisan migrate --force

db-rollback: ## 💾 Rollback migrations
	@$(COMPOSE_DEV) exec app php artisan migrate:rollback --force

db-fresh: ## 💾 Fresh migrations + seed
	@echo "${RED}⚠️  Isso vai APAGAR todos os dados!${RESET}"
	@echo "${YELLOW}Pressione Ctrl+C para cancelar...${RESET}"
	@sleep 3
	@$(COMPOSE_DEV) exec app php artisan migrate:fresh --seed --force

db-seed: ## 💾 Executar seeds
	@$(COMPOSE_DEV) exec app php artisan db:seed --force

db-backup: ## 💾 Backup do banco
	@echo "${YELLOW}→ Criando backup...${RESET}"
	@mkdir -p storage/backups
	@$(COMPOSE_DEV) exec db mysqldump -u root -proot sdc > storage/backups/backup-$(shell date +%Y%m%d-%H%M%S).sql
	@echo "${GREEN}✓ Backup criado em storage/backups/${RESET}"

db-restore: ## 💾 Restore backup (uso: make db-restore FILE=arquivo.sql)
	@echo "${YELLOW}→ Restaurando backup: $(FILE)${RESET}"
	@$(COMPOSE_DEV) exec -T db mysql -u root -proot sdc < $(FILE)
	@echo "${GREEN}✓ Backup restaurado${RESET}"

# ============================================================================
# 🎨 FRONTEND (Node.js + Vite)
# ============================================================================

npm-install: ## 🎨 Install dependências npm
	@$(COMPOSE_DEV) exec node npm install --legacy-peer-deps

npm-update: ## 🎨 Update dependências npm
	@$(COMPOSE_DEV) exec node npm update

npm-dev: ## 🎨 Vite dev server
	@$(COMPOSE_DEV) exec node npm run dev

npm-build: ## 🎨 Build produção
	@$(COMPOSE_DEV) exec node npm run build

npm-build-vite: ## 🎨 Build produção usando npx vite build
	@$(COMPOSE_DEV) exec node npx vite build

npm-clean: ## 🎨 Limpar node_modules e cache
	@$(COMPOSE_DEV) exec node sh -c "rm -rf node_modules/.vite && npm cache clean --force"

# ============================================================================
# ⚙️  LARAVEL ARTISAN
# ============================================================================

cache-clear: ## ⚙️  Limpa todos os caches
	@echo "${YELLOW}→ Limpando caches...${RESET}"
	@$(COMPOSE_DEV) exec app php artisan cache:clear
	@$(COMPOSE_DEV) exec app php artisan config:clear
	@$(COMPOSE_DEV) exec app php artisan route:clear
	@$(COMPOSE_DEV) exec app php artisan view:clear
	@echo "${GREEN}✓ Caches limpos${RESET}"

cache-optimize: ## ⚙️  Otimiza caches (produção)
	@echo "${YELLOW}→ Otimizando caches...${RESET}"
	@$(COMPOSE_DEV) exec app php artisan config:cache
	@$(COMPOSE_DEV) exec app php artisan route:cache
	@$(COMPOSE_DEV) exec app php artisan view:cache
	@$(COMPOSE_DEV) exec app php artisan event:cache
	@echo "${GREEN}✓ Caches otimizados${RESET}"

artisan: ## ⚙️  Executar artisan (uso: make artisan CMD="migrate")
	@$(COMPOSE_DEV) exec app php artisan $(CMD)

# ============================================================================
# 🌐 NETWORK & BRIDGE (15 containers, sem Ollama)
# ============================================================================

bridge: ## 📦 Cria a rede bridge sdc_network (para os ~15 containers, sem Ollama)
	@echo "${YELLOW}→ Iniciando bridge (sdc_network)...${RESET}"
	@docker network inspect sdc_network >/dev/null 2>&1 || \
		(echo "${YELLOW}→ Criando network bridge...${RESET}" && \
		 docker network create --driver bridge --subnet 172.26.0.0/16 sdc_network)
	@echo "${GREEN}✓ Bridge sdc_network OK${RESET}"

# Um comando: bridge + build-all + up-all (stack completa sem Ollama)
start: bridge build-all up-all ## 🚀 Bridge + build all + sobe toda stack (COM Ollama)
	@echo ""
	@echo "${BOLD}${GREEN}✓ Stack iniciada (bridge + build + up). Ollama não incluído.${RESET}"
	@$(MAKE) urls

network-init: bridge ## 🌐 Alias: inicializa network (chama bridge)

network-inspect: ## 🌐 Inspeciona network
	@docker network inspect sdc_network

network-clean: ## 🌐 Remove network
	@docker network rm sdc_network 2>/dev/null || true

# ============================================================================
# 📱 MOBILE DEV (NativePHP)
# ============================================================================

mobile-dev: ## 📱 Inicia NativePHP Jump Server (QR Code)
	@./scripts/start-mobile.sh

# ============================================================================
# 📋 STATUS & INFO
# ============================================================================

status: ## 📋 Status completo de todos containers
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo "${BOLD}${CYAN}  STATUS COMPLETO DA STACK${RESET}"
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo ""
	@$(COMPOSE_DEV) ps
	@echo ""
	@echo "${BOLD}${CYAN}Containers ativos:${RESET}"
	@docker ps --filter "name=newsdc" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

ps: status ## 📋 Alias para status

urls: ## 📋 Mostra todas URLs disponíveis
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo "${BOLD}${CYAN}  URLS E PORTAS DISPONÍVEIS${RESET}"
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo ""
	@echo "${BOLD}🚀 APLICAÇÃO:${RESET}"
	@echo "  App (Laravel):     ${GREEN}http://localhost:18001${RESET}"
	@echo "  Nginx:             ${GREEN}http://localhost:18082${RESET}"
	@echo "  Vite (HMR):        ${GREEN}http://localhost:15175${RESET}"
	@echo ""
	@echo "${BOLD}💾 BANCO DE DADOS:${RESET}"
	@echo "  MySQL:             ${GREEN}localhost:13307${RESET} (user: sdc, pass: secret)"
	@echo "  Redis:             ${GREEN}localhost:16380${RESET}"
	@echo "  phpMyAdmin:        ${GREEN}http://localhost:8086${RESET}"
	@echo "  Redis Commander:   ${GREEN}http://localhost:8087${RESET}"
	@echo ""
	@echo "${BOLD}📧 FERRAMENTAS:${RESET}"
	@echo "  Mailhog:           ${GREEN}http://localhost:18026${RESET}"
	@echo ""
	@echo "${BOLD}📊 MONITORAMENTO:${RESET}"
	@echo "  Grafana:           ${GREEN}http://localhost:3000${RESET} (admin/admin@123)"
	@echo "  Prometheus:        ${GREEN}http://localhost:9090${RESET}"
	@echo "  Alertmanager:      ${GREEN}http://localhost:9093${RESET}"
	@echo "  cAdvisor:          ${GREEN}http://localhost:8080${RESET}"
	@echo ""
	@echo "${BOLD}📈 EXPORTERS:${RESET}"
	@echo "  Node Exporter:     ${CYAN}http://localhost:9100/metrics${RESET}"
	@echo "  MySQL Exporter:    ${CYAN}http://localhost:9104/metrics${RESET}"
	@echo "  Redis Exporter:    ${CYAN}http://localhost:9121/metrics${RESET}"
	@echo "  Nginx Exporter:    ${CYAN}http://localhost:9113/metrics${RESET}"
	@echo "  Blackbox Exporter: ${CYAN}http://localhost:9115/metrics${RESET}"

logs: ## 📋 Logs de todos containers (follow)
	@$(COMPOSE_DEV) logs -f

logs-app: ## 📋 Logs apenas app
	@$(COMPOSE_DEV) logs -f app

logs-nginx: ## 📋 Logs apenas nginx
	@$(COMPOSE_DEV) logs -f nginx

logs-db: ## 📋 Logs apenas database
	@$(COMPOSE_DEV) logs -f db

logs-queue: ## 📋 Logs queue worker
	@$(COMPOSE_DEV) logs -f queue

# ============================================================================
# 🧪 TESTES
# ============================================================================

test: ## 🧪 Executar testes
	@$(COMPOSE_DEV) exec app php artisan test

test-coverage: ## 🧪 Testes com coverage
	@$(COMPOSE_DEV) exec app php artisan test --coverage

test-filter: ## 🧪 Testes filtrados (uso: make test-filter FILTER="NomeDoTeste")
	@$(COMPOSE_DEV) exec app php artisan test --filter=$(FILTER)

# ============================================================================
# 🔄 WORKFLOWS COMPLETOS
# ============================================================================

setup: build-all up-all db-migrate cache-optimize ## 🔄 Setup inicial completo
	@echo ""
	@echo "${BOLD}${GREEN}════════════════════════════════════════════════════════════${RESET}"
	@echo "${BOLD}${GREEN}  ✓ SETUP COMPLETO FINALIZADO!${RESET}"
	@echo "${BOLD}${GREEN}════════════════════════════════════════════════════════════${RESET}"
	@echo ""
	@$(MAKE) urls

reset: clean-volumes build-all up-all db-migrate db-seed ## 🔄 Reset completo (⚠️  APAGA DADOS!)
	@echo ""
	@echo "${BOLD}${GREEN}✓ RESET COMPLETO FINALIZADO!${RESET}"
	@echo ""
	@$(MAKE) urls

deploy-prod: ## 🔄 Deploy completo produção
	@echo "${BOLD}${CYAN}→ DEPLOY PRODUÇÃO${RESET}"
	git pull origin main
	@$(MAKE) prod-build
	@$(COMPOSE_PROD) exec app php artisan migrate --force
	@$(COMPOSE_PROD) exec app php artisan config:cache
	@$(COMPOSE_PROD) exec app php artisan route:cache
	@$(COMPOSE_PROD) exec app php artisan view:cache
	@echo "${BOLD}${GREEN}✓ DEPLOY CONCLUÍDO!${RESET}"

# ============================================================================
# 🏥 HEALTHCHECK
# ============================================================================

health: ## 🏥 Healthcheck de todos serviços
	@echo "${BOLD}${CYAN}Verificando saúde dos serviços...${RESET}"
	@echo ""
	@echo -n "${YELLOW}App:         ${RESET}"
	@curl -sf http://localhost:8001 > /dev/null && echo "${GREEN}✓ OK${RESET}" || echo "${RED}✗ FALHOU${RESET}"
	@echo -n "${YELLOW}Nginx:       ${RESET}"
	@curl -sf http://localhost:8082 > /dev/null && echo "${GREEN}✓ OK${RESET}" || echo "${RED}✗ FALHOU${RESET}"
	@echo -n "${YELLOW}Vite:        ${RESET}"
	@curl -sf http://localhost:5173 > /dev/null && echo "${GREEN}✓ OK${RESET}" || echo "${RED}✗ FALHOU${RESET}"
	@echo -n "${YELLOW}Grafana:     ${RESET}"
	@curl -sf http://localhost:3000/api/health > /dev/null && echo "${GREEN}✓ OK${RESET}" || echo "${RED}✗ FALHOU${RESET}"
	@echo -n "${YELLOW}Prometheus:  ${RESET}"
	@curl -sf http://localhost:9090/-/healthy > /dev/null && echo "${GREEN}✓ OK${RESET}" || echo "${RED}✗ FALHOU${RESET}"

# ============================================================================
# 📝 INFO
# ============================================================================

info: ## 📝 Informações do ambiente
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo "${BOLD}${CYAN}  INFORMAÇÕES DO AMBIENTE${RESET}"
	@echo "${BOLD}${CYAN}════════════════════════════════════════════════════════════${RESET}"
	@echo ""
	@echo "${BOLD}Host:${RESET}"
	@echo "  UID:               ${GREEN}$(HOST_UID)${RESET}"
	@echo "  GID:               ${GREEN}$(HOST_GID)${RESET}"
	@echo "  Docker Buildkit:   ${GREEN}$(DOCKER_BUILDKIT)${RESET}"
	@echo ""
	@echo "${BOLD}Docker:${RESET}"
	@docker version --format '  Versão:            ${GREEN}{{.Server.Version}}${RESET}'
	@echo ""
	@echo "${BOLD}Docker Compose:${RESET}"
	@docker compose version --short | xargs -I {} echo "  Versão:            ${GREEN}{}${RESET}"
	@echo ""
	@echo "${BOLD}Uso de recursos:${RESET}"
	@docker system df
	@echo ""

version: ## 📝 Versão das ferramentas
	@echo "${BOLD}Versões instaladas:${RESET}"
	@echo "Docker:          $(shell docker --version)"
	@echo "Docker Compose:  $(shell docker compose version)"
	@echo "Make:            $(shell make --version | head -1)"

# ============================================================================
# FIM
# ============================================================================
