# ============================================================================
# SDC - Makefile para Docker
# Comandos facilitados para desenvolvimento e produção
# ============================================================================

.PHONY: help dev prod build up down restart logs shell db-shell redis-shell test migrate fresh seed backup clean

# Cores
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

# ==========================================
# Help
# ==========================================
help: ## Mostra esta ajuda
	@echo ''
	@echo '${GREEN}SDC - Sistema de Defesa Civil${RESET}'
	@echo ''
	@echo 'Uso:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  ${YELLOW}%-15s${RESET} %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# ==========================================
# Desenvolvimento
# ==========================================
dev: ## Inicia ambiente de desenvolvimento
	docker compose -f docker/docker-compose.yml up -d
	@echo "${GREEN}Ambiente de desenvolvimento iniciado!${RESET}"
	@echo "App: http://localhost"
	@echo "Mailhog: http://localhost:8025"

dev-full: ## Inicia desenvolvimento com todas as ferramentas
	docker compose -f docker/docker-compose.yml --profile tools up -d
	@echo "${GREEN}Ambiente completo iniciado!${RESET}"
	@echo "App: http://localhost"
	@echo "phpMyAdmin: http://localhost:8080"
	@echo "Redis Commander: http://localhost:8081"
	@echo "Mailhog: http://localhost:8025"

dev-build: ## Rebuild do ambiente de desenvolvimento
	docker compose -f docker/docker-compose.yml build --no-cache
	docker compose -f docker/docker-compose.yml up -d

dev-down: ## Para ambiente de desenvolvimento
	docker compose -f docker/docker-compose.yml down

dev-logs: ## Ver logs de desenvolvimento
	docker compose -f docker/docker-compose.yml logs -f

dev-monitoring: ## Inicia ambiente com monitoring stack completo
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.monitoring.yml up -d
	@echo "${GREEN}Ambiente com monitoring iniciado!${RESET}"
	@echo "App: http://localhost:8000"
	@echo "Grafana: http://localhost:3000 (admin/admin123)"
	@echo "Prometheus: http://localhost:9090"
	@echo "Mailhog: http://localhost:8025"

monitoring-down: ## Para monitoring stack
	docker compose -f docker/docker-compose.monitoring.yml down

monitoring-logs: ## Ver logs do monitoring stack
	docker compose -f docker/docker-compose.monitoring.yml logs -f

# ==========================================
# Produção
# ==========================================
prod: ## Inicia ambiente de produção
	docker compose -f docker/docker-compose.prod.yml up -d
	@echo "${GREEN}Ambiente de produção iniciado!${RESET}"

prod-build: ## Build de produção
	docker compose -f docker/docker-compose.prod.yml build --no-cache
	docker compose -f docker/docker-compose.prod.yml up -d

prod-down: ## Para ambiente de produção
	docker compose -f docker/docker-compose.prod.yml down

prod-logs: ## Ver logs de produção
	docker compose -f docker/docker-compose.prod.yml logs -f

prod-scale: ## Scale dos workers (ex: make prod-scale N=5)
	docker compose -f docker/docker-compose.prod.yml up -d --scale app=$(N) --scale queue=$(N)

# ==========================================
# Genéricos
# ==========================================
build: ## Build das imagens Docker
	docker compose -f docker/docker-compose.yml build

up: ## Inicia os containers
	docker compose -f docker/docker-compose.yml up -d

down: ## Para os containers
	docker compose -f docker/docker-compose.yml down

restart: ## Reinicia os containers
	docker compose -f docker/docker-compose.yml restart

logs: ## Ver logs
	docker compose -f docker/docker-compose.yml logs -f

ps: ## Lista containers
	docker compose -f docker/docker-compose.yml ps

# ==========================================
# Shell Access
# ==========================================
shell: ## Acessa shell do container app
	docker compose -f docker/docker-compose.yml exec app bash

shell-root: ## Acessa shell como root
	docker compose -f docker/docker-compose.yml exec -u root app bash

db-shell: ## Acessa MySQL CLI
	docker compose -f docker/docker-compose.yml exec db mysql -u root -p

redis-shell: ## Acessa Redis CLI
	docker compose -f docker/docker-compose.yml exec redis redis-cli

# ==========================================
# Laravel Commands
# ==========================================
artisan: ## Executa artisan (ex: make artisan CMD="migrate")
	docker compose -f docker/docker-compose.yml exec app php artisan $(CMD)

migrate: ## Executa migrations
	docker compose -f docker/docker-compose.yml exec app php artisan migrate

fresh: ## Fresh migrations com seeds
	docker compose -f docker/docker-compose.yml exec app php artisan migrate:fresh --seed

seed: ## Executa seeds
	docker compose -f docker/docker-compose.yml exec app php artisan db:seed

cache-clear: ## Limpa todos os caches
	docker compose -f docker/docker-compose.yml exec app php artisan cache:clear
	docker compose -f docker/docker-compose.yml exec app php artisan config:clear
	docker compose -f docker/docker-compose.yml exec app php artisan route:clear
	docker compose -f docker/docker-compose.yml exec app php artisan view:clear

optimize: ## Otimiza para produção
	docker compose -f docker/docker-compose.yml exec app php artisan config:cache
	docker compose -f docker/docker-compose.yml exec app php artisan route:cache
	docker compose -f docker/docker-compose.yml exec app php artisan view:cache

# ==========================================
# Testing
# ==========================================
test: ## Executa testes
	docker compose -f docker/docker-compose.yml exec app php artisan test

test-coverage: ## Testes com coverage
	docker compose -f docker/docker-compose.yml exec app php artisan test --coverage

# ==========================================
# Assets - Node.js
# ==========================================
npm-install: ## Instala dependências npm (Node.js)
	docker compose -f docker/docker-compose.yml exec node npm install

npm-dev: ## Build de desenvolvimento (Node.js)
	docker compose -f docker/docker-compose.yml exec node npm run dev

npm-build: ## Build de produção (Node.js)
	docker compose -f docker/docker-compose.yml exec node npm run build

# ==========================================
# Backup
# ==========================================
backup: ## Executa backup manual
	docker compose -f docker/docker-compose.yml exec backup /backup/backup.sh

# ==========================================
# Cleanup
# ==========================================
clean: ## Limpa volumes e imagens não utilizadas
	docker compose -f docker/docker-compose.yml down -v
	docker system prune -f

clean-all: ## Limpa TUDO (cuidado!)
	docker compose -f docker/docker-compose.yml down -v --rmi all
	docker system prune -af --volumes

# ==========================================
# Monitoramento (Produção)
# ==========================================
monitor: ## Abre dashboards de monitoramento
	@echo "Grafana: http://localhost:3000"
	@echo "Prometheus: http://localhost:9090"
	@echo "Traefik: http://localhost:8080"

# ==========================================
# Deploy
# ==========================================
deploy: ## Deploy para produção
	@echo "${YELLOW}Iniciando deploy...${RESET}"
	git pull origin main
	docker compose -f docker/docker-compose.prod.yml build
	docker compose -f docker/docker-compose.prod.yml up -d
	docker compose -f docker/docker-compose.prod.yml exec app php artisan migrate --force
	docker compose -f docker/docker-compose.prod.yml exec app php artisan config:cache
	docker compose -f docker/docker-compose.prod.yml exec app php artisan route:cache
	docker compose -f docker/docker-compose.prod.yml exec app php artisan view:cache
	@echo "${GREEN}Deploy concluído!${RESET}"

rollback: ## Rollback para versão anterior
	docker compose -f docker/docker-compose.prod.yml down
	git checkout HEAD~1
	docker compose -f docker/docker-compose.prod.yml up -d
	@echo "${YELLOW}Rollback executado!${RESET}"

