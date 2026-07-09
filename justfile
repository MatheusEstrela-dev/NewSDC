# Justfile para o projeto SDC
# Uso: just <comando>

# Variáveis
docker_app := "newsdc_app"
docker_db := "newsdc_db"

# Compose v2 (plugin `docker compose`); o binario legado docker-compose nao existe na VM
compose_file := "SDC/docker/docker-compose.yml"
compose := "docker compose -f " + compose_file

# Lista todos os comandos disponíveis
default:
    @just --list

# ==================== DOCKER ====================

# Inicia os containers Docker
up:
    {{compose}} up -d

# Para os containers Docker
down:
    {{compose}} down

# Reinicia os containers Docker
restart:
    {{compose}} restart

# Mostra os logs dos containers
logs container="app":
    docker logs -f {{docker_app}} 2>&1 | grep -v "\"GET /health"

# Acessa o shell do container da aplicação
shell:
    docker exec -it {{docker_app}} bash

# Acessa o MySQL do container
db:
    docker exec -it {{docker_db}} mysql -u root -proot sdc

# ==================== LARAVEL ====================

# Limpa todos os caches do Laravel
clear:
    docker exec {{docker_app}} php artisan config:clear
    docker exec {{docker_app}} php artisan route:clear
    docker exec {{docker_app}} php artisan cache:clear
    docker exec {{docker_app}} php artisan view:clear

# Executa as migrations
migrate:
    docker exec {{docker_app}} php artisan migrate --force

# Executa as migrations com seed
migrate-seed:
    docker exec {{docker_app}} php artisan migrate:fresh --seed --force

# Rollback das migrations
migrate-rollback:
    docker exec {{docker_app}} php artisan migrate:rollback

# Lista as rotas
routes:
    docker exec {{docker_app}} php artisan route:list

# Abre o tinker
tinker:
    docker exec -it {{docker_app}} php artisan tinker

# Executa os testes
test:
    docker exec {{docker_app}} php artisan test

# ==================== TDAP ====================

# Executa as migrations do módulo TDAP
tdap-migrate:
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_01_26_000001_create_tdap_products_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_01_26_000002_create_tdap_product_lotes_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_01_26_000003_create_tdap_product_compositions_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_01_26_000004_create_tdap_recebimentos_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_01_26_000005_create_tdap_recebimento_itens_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_01_26_000006_create_tdap_movimentacoes_table.php --force
    @echo "✅ Migrations do TDAP executadas com sucesso!"

# Verifica as tabelas do TDAP
tdap-tables:
    docker exec {{docker_db}} mysql -u root -proot sdc -e "SHOW TABLES LIKE 'tdap%';"

# Cria produtos de exemplo no TDAP
tdap-seed:
    docker exec {{docker_app}} php artisan tinker --execute="App\Modules\Tdap\Domain\Entities\Product::create(['codigo' => 'CB-001', 'nome' => 'Cesta Básica Tipo 1', 'tipo' => 'cesta_basica', 'grupo_risco' => 'ALIMENTO', 'estoque_minimo' => 50, 'estoque_maximo' => 500, 'dias_alerta_validade' => 30]);"
    docker exec {{docker_app}} php artisan tinker --execute="App\Modules\Tdap\Domain\Entities\Product::create(['codigo' => 'KL-001', 'nome' => 'Kit Limpeza Completo', 'tipo' => 'kit_limpeza', 'grupo_risco' => 'QUIMICO', 'estoque_minimo' => 30, 'estoque_maximo' => 300, 'dias_alerta_validade' => 90]);"
    docker exec {{docker_app}} php artisan tinker --execute="App\Modules\Tdap\Domain\Entities\Product::create(['codigo' => 'COL-001', 'nome' => 'Colchão Solteiro', 'tipo' => 'colchao', 'grupo_risco' => 'GERAL', 'estoque_minimo' => 20, 'estoque_maximo' => 200, 'volume_unitario_m3' => 0.5]);"
    @echo "✅ Produtos de exemplo criados!"

# Remove o módulo TDAP (rollback migrations)
tdap-rollback:
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_01_26_000006_create_tdap_movimentacoes_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_01_26_000005_create_tdap_recebimento_itens_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_01_26_000004_create_tdap_recebimentos_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_01_26_000003_create_tdap_product_compositions_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_01_26_000002_create_tdap_product_lotes_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_01_26_000001_create_tdap_products_table.php --force
    @echo "✅ Migrations do TDAP revertidas!"

# ==================== AJUDA HUMANITÁRIA ====================

# Executa as migrations do módulo Ajuda Humanitária
ajuda-migrate:
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120000_create_beneficiarios_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120100_create_abrigos_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120200_create_membros_familia_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120300_create_beneficiario_abrigo_pivot_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120400_create_doacoes_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120500_create_itens_doacao_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120600_create_auxilios_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120700_create_itens_auxilio_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120800_create_estoques_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_120900_create_movimentacoes_estoque_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2025_12_28_121000_create_movimentacoes_financeiras_table.php --force
    @echo "✅ Migrations do módulo Ajuda Humanitária executadas com sucesso!"

# Verifica as tabelas do módulo Ajuda Humanitária
ajuda-tables:
    docker exec {{docker_db}} mysql -u root -proot sdc -e "SHOW TABLES LIKE 'beneficiarios';"
    docker exec {{docker_db}} mysql -u root -proot sdc -e "SHOW TABLES LIKE 'abrigos';"
    docker exec {{docker_db}} mysql -u root -proot sdc -e "SHOW TABLES LIKE 'doacoes';"
    docker exec {{docker_db}} mysql -u root -proot sdc -e "SHOW TABLES LIKE 'auxilios';"
    docker exec {{docker_db}} mysql -u root -proot sdc -e "SHOW TABLES LIKE 'estoques';"

# Remove o módulo Ajuda Humanitária (rollback migrations)
ajuda-rollback:
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_121000_create_movimentacoes_financeiras_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120900_create_movimentacoes_estoque_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120800_create_estoques_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120700_create_itens_auxilio_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120600_create_auxilios_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120500_create_itens_doacao_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120400_create_doacoes_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120300_create_beneficiario_abrigo_pivot_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120200_create_membros_familia_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120100_create_abrigos_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2025_12_28_120000_create_beneficiarios_table.php --force
    @echo "✅ Migrations do módulo Ajuda Humanitária revertidas!"

# ==================== RAT (REGISTRO DE ATIVIDADES) ====================

# Executa as migrations do módulo RAT
rat-migrate:
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_131610_create_rat_bem_afetado_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_131811_create_rat_encaminhamento_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_132039_create_rat_ocorrencia_relatos_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_132204_create_rat_ocorrencias_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_132344_create_rat_acionado_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_132614_create_rat_patologia_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_132732_create_rat_recursos_componentes_guarnicao_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_132940_create_rat_recursos_empregados_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_133127_create_rat_redec_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_133300_create_rat_dados_gerais_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_133452_create_rat_relato_envolvidos_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_133724_create_rat_relato_recursos_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_134052_create_rat_relato_vistoria_table.php --force
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_134152_create_rat_veiculos_table.php --force
    @echo "✅ Migrations do módulo RAT executadas com sucesso!"

# Verifica as tabelas do módulo RAT
rat-tables:
    docker exec {{docker_db}} mysql -u root -proot sdc -e "SHOW TABLES LIKE 'rat%';"

# Remove o módulo RAT (rollback migrations)
rat-rollback:
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_134152_create_rat_veiculos_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_134052_create_rat_relato_vistoria_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_133724_create_rat_relato_recursos_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_133452_create_rat_relato_envolvidos_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_133300_create_rat_dados_gerais_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_133127_create_rat_redec_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_132940_create_rat_recursos_empregados_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_132732_create_rat_recursos_componentes_guarnicao_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_132614_create_rat_patologia_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_132344_create_rat_acionado_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_132204_create_rat_ocorrencias_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_132039_create_rat_ocorrencia_relatos_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_131811_create_rat_encaminhamento_table.php --force
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_131610_create_rat_bem_afetado_table.php --force
    @echo "✅ Migrations do módulo RAT revertidas!"

# ==================== FRONTEND ====================

# Build do frontend
build:
    cd SDC && npm run build

# Dev do frontend (watch)
dev:
    cd SDC && bun run dev

# Instala dependências do NPM
npm-install:
    cd SDC && npm install

# ==================== PERMISSOES (SISTEMA) ====================

# Executa a migration consolidada do sistema de permissionamento
perm-migrate:
    docker exec {{docker_app}} php artisan migrate --path=database/migrations/2026_02_10_000001_enhance_permission_system.php --force
    @echo "Migration de permissionamento executada!"

# Rollback da migration de permissionamento
perm-rollback:
    docker exec {{docker_app}} php artisan migrate:rollback --path=database/migrations/2026_02_10_000001_enhance_permission_system.php --force
    @echo "Migration de permissionamento revertida!"

# Limpa o cache de permissoes (Spatie)
perm-cache-clear:
    docker exec {{docker_app}} php artisan permission:cache-reset
    @echo "Cache de permissoes limpo!"

# ==================== PERMISSOES (ARQUIVOS) ====================

# Corrige permissoes dos arquivos
fix-permissions:
    docker exec {{docker_app}} chown -R www-data:www-data /var/www/storage
    docker exec {{docker_app}} chown -R www-data:www-data /var/www/bootstrap/cache
    docker exec {{docker_app}} chmod -R 775 /var/www/storage
    docker exec {{docker_app}} chmod -R 775 /var/www/bootstrap/cache

# ==================== UTILITÁRIOS ====================

# Exibe informações sobre o projeto
info:
    @echo "📦 Projeto: SDC - Sistema de Defesa Civil"
    @echo "🐳 Containers:"
    @docker ps --format "  - {{'{{'}}.Names}}"
    @echo ""
    @echo "🌐 URLs:"
    @echo "  - App: http://localhost:8001"
    @echo "  - MailHog: http://localhost:8025"
    @echo ""
    @echo "📊 Módulos disponíveis:"
    @echo "  - RAT (Registro de Atividades)"
    @echo "  - Demandas (Tasks/Chamados)"
    @echo "  - TDAP (Gestão de Depósito)"
    @echo "  - PAE (Plano de Ação Emergencial)"
    @echo "  - Decretações (Reconhecimento de Desastres)"
    @echo "  - Ajuda Humanitária (Beneficiários, Abrigos, Doações) 🆕"

# Inicia o Bifrost Jump (Bridge para Mobile) com QR Code
jump:
    docker exec -it {{docker_app}} php artisan native:jump

# Inicia o Bifrost Jump direto para Android com QR Code (IP fixo)
jump-android:
    docker exec -it {{docker_app}} php artisan native:jump --platform=android --http-port=9000 --ip=10.183.11.182

# Reinicia a aplicação completamente
fresh: down
    {{compose}} up -d
    sleep 5
    just migrate
    just clear
    @echo "✅ Aplicação reiniciada!"

# Setup completo do projeto
setup:
    {{compose}} up -d
    sleep 10
    just migrate
    just tdap-migrate
    just ajuda-migrate
    just rat-migrate
    just clear
    cd SDC && npm install
    just build
    @echo "✅ Setup completo finalizado!"

# ==================== PROD VM (Ubuntu 10.160.131.30 - stack newsdc-dev) ====================
# Stack homologada na VM: compose.dev.yml (bridge/projeto newsdc-dev).
# Build passa pelo proxy Prodemge; runtime nao precisa de proxy (rede interna).
# O proxy do daemon (systemd) so cobre o docker pull; o RUN apk/composer do
# build roda isolado e exige os --build-arg abaixo (hostname validado na VM).

vm_proxy := "http://proxy.prodemge.gov.br:8080"
vm_no_proxy := "localhost,127.0.0.1,10.160.131.30,*.prodemge.gov.br"

# Maiusculas e minusculas: apk/curl leem HTTPS_PROXY; pecl/PEAR e wget leem http_proxy.
# Ambas sao build-args predefinidos do Docker e viram env nos RUN do build.
vm_proxy_upper := "--build-arg HTTP_PROXY=" + vm_proxy + " --build-arg HTTPS_PROXY=" + vm_proxy + " --build-arg NO_PROXY=" + vm_no_proxy
vm_proxy_lower := "--build-arg http_proxy=" + vm_proxy + " --build-arg https_proxy=" + vm_proxy + " --build-arg no_proxy=" + vm_no_proxy
vm_build_args := vm_proxy_upper + " " + vm_proxy_lower

# Setup completo na VM: build (se nao houver imagem) + sobe stack + APP_KEY + migrations + caches
prod-setup:
    @if [ -z "$(docker images -q newsdc-swoole-dev:latest)" ]; then \
        echo "Imagem nao encontrada - buildando via proxy Prodemge..."; \
        just prod-build; \
    fi
    docker compose -f {{dev_compose}} up -d
    @echo "Aguardando containers subirem..."
    sleep 10
    @grep -qs "^APP_KEY=base64" SDC/docker/.env || echo "AVISO: APP_KEY nao encontrada em SDC/docker/.env - e de la que o compose injeta a chave (app e queue compartilham); key:generate no container nao resolve"
    -docker exec {{dev_app}} php artisan storage:link
    docker exec {{dev_app}} php artisan migrate --force
    docker exec {{dev_app}} php artisan optimize:clear
    @echo "Setup PROD finalizado. App: http://10.160.131.30:8000"

# Sobe a stack da VM (sem build)
prod-up:
    docker compose -f {{dev_compose}} up -d

# Para a stack da VM (mantem volumes pgdata/redisdata)
prod-down:
    docker compose -f {{dev_compose}} down

# Reinicia a stack da VM
prod-restart:
    docker compose -f {{dev_compose}} restart

# Rebuild forcado da imagem via proxy Prodemge (apos mudanca no Dockerfile)
prod-build:
    docker compose -f {{dev_compose}} build {{vm_build_args}} app

# Status dos containers + saude do endpoint
prod-status:
    @docker compose -f {{dev_compose}} ps
    @curl -s -o /dev/null -w "Health: HTTP %{http_code}\n" http://localhost:8000/health || echo "App nao respondeu na 8000"

# Logs da stack na VM. Uso: just prod-logs db
prod-logs svc="app":
    docker compose -f {{dev_compose}} logs -f --tail=200 {{svc}}

# Migrations na VM
prod-migrate:
    docker exec {{dev_app}} php artisan migrate --force

# Backup do banco (pg_dump) em /opt/sdc/backups ou ./backups
prod-backup:
    @mkdir -p backups
    docker compose -f {{dev_compose}} exec -T db pg_dump -U sdc -d sdc > backups/sdc_$(date +%F_%H%M).sql
    @echo "Dump gerado em backups/"

# ==================== PROXY CORPORATIVO (Prodemge) ====================

proxy_url := "http://proxycamg.prodemge.gov.br:8080"

# Necessario p/ `just npm-install`, `just build` e o composer install do build dev.
# Ativa o proxy Prodemge para npm e composer (persistente, roda 1x)
proxy-on:
    npm config set proxy {{proxy_url}}
    npm config set https-proxy {{proxy_url}}
    composer config -g http-proxy {{proxy_url}}
    composer config -g https-proxy {{proxy_url}}
    @echo "Proxy Prodemge ATIVADO (npm + composer): {{proxy_url}}"

# Remove o proxy Prodemge (use fora da rede corporativa).
proxy-off:
    -npm config delete proxy
    -npm config delete https-proxy
    -composer config -g --unset http-proxy
    -composer config -g --unset https-proxy
    @echo "Proxy Prodemge REMOVIDO (npm + composer)."

# Mostra o proxy configurado em npm e composer.
proxy-status:
    @echo "npm proxy:        $(npm config get proxy)"
    @echo "npm https-proxy:  $(npm config get https-proxy)"
    @echo "composer http:    $(composer config -g http-proxy 2>/dev/null || echo '(nao definido)')"
    @echo "composer https:   $(composer config -g https-proxy 2>/dev/null || echo '(nao definido)')"

# ==================== DEV LOCAL (Swoole self-contained) ====================

dev_compose := "SDC/docker/compose.dev.yml"
dev_app := "newsdc_dev_app"
dev_db := "newsdc_dev_db"

# Builda a imagem newsdc-swoole-dev (primeira vez ou apos mudanca no Dockerfile)
dev-build:
    docker compose -f {{dev_compose}} build app

# Aplica o proxy Prodemge (proxy-on) e repassa HTTP(S)_PROXY ao docker build,
# pois o composer install roda DENTRO da imagem. Fora da rede: rode `just proxy-off`.
# Os bind-mounts ja dao hot-reload do codigo, entao nao rebuilda a cada subida;
# para forcar rebuild apos mudar o Dockerfile use `just dev-build` antes.
# Proxy + build (so se a imagem nao existir) + sobe o stack dev essencial (app, queue, db, redis, mailhog)
dev-start: proxy-on
    @if [ -z "$(docker images -q newsdc-swoole-dev:latest)" ]; then \
        echo "Imagem dev nao encontrada - buildando (via proxy Prodemge)..."; \
        HTTP_PROXY={{proxy_url}} HTTPS_PROXY={{proxy_url}} docker compose -f {{dev_compose}} build app; \
    fi
    docker compose -f {{dev_compose}} up -d
    @echo "App:     http://localhost:19444"
    @echo "Mailhog: http://localhost:8025"
    @echo "Postgres host:5433  Redis host:6380"

# Sobe stack dev (app + db + redis + mailhog)
dev-up:
    docker compose -f {{dev_compose}} up -d
    @echo "App:     http://localhost:19444"
    @echo "Mailhog: http://localhost:8025"
    @echo "Postgres host:5433  Redis host:6380"

# Derruba stack dev (mantem volumes)
dev-down:
    docker compose -f {{dev_compose}} down

# Logs (default: app). Uso: just dev-logs db
dev-logs svc="app":
    docker compose -f {{dev_compose}} logs -f --tail=200 {{svc}}

# Shell no container app
dev-shell:
    docker exec -it {{dev_app}} sh

# psql no banco dev
dev-db:
    docker exec -it {{dev_db}} psql -U sdc -d sdc

# redis-cli no redis dev
dev-redis:
    docker exec -it newsdc_dev_redis redis-cli

# Status dos containers + URLs uteis
dev-status:
    @docker compose -f {{dev_compose}} ps
    @echo ""
    @echo "URLs:"
    @echo "  http://localhost:19444   (app)"
    @echo "  http://localhost:8081     (Vite HMR no host)"
    @echo "  http://localhost:8025    (mailhog UI)"

# Restart so do container app (apos mudanca de config/.env)
dev-restart:
    docker compose -f {{dev_compose}} restart app

# Migrate dentro do container dev
dev-migrate:
    docker exec {{dev_app}} php artisan migrate --force

# Reset destrutivo: down + remove volumes (dados perdidos). Pede confirmacao.
dev-clean:
    @echo "ATENCAO: vai remover volumes pgdata e redisdata (dados perdidos)."
    @echo "Ctrl+C para abortar; ENTER para continuar."
    @read _
    docker compose -f {{dev_compose}} down -v

# Vite dev server no HOST (rodar em terminal separado)
dev-vite:
    cd SDC && bun run dev

# ==================== ENV SWITCH ====================

# Troca para ambiente LOCAL (Docker)
env-local:
    cp SDC/.env SDC/.env.prod.bak
    cp SDC/.env.local SDC/.env
    cp SDC/.env.local SDC/docker/.env
    cd SDC/docker && docker compose up -d --force-recreate app
    @echo "Ambiente: LOCAL (Docker)"

# Troca para ambiente PROD (Google Cloud DB)
env-prod:
    cp SDC/.env SDC/.env.local
    cp SDC/.env.prod SDC/.env
    cp SDC/.env.prod SDC/docker/.env
    cd SDC/docker && docker compose up -d --force-recreate app
    @echo "Ambiente: PROD (Google Cloud)"

# Switch rapido entre LOCAL <-> PROD
switch:
    @if grep -qE "DB_HOST=(db|localhost|127.0.0.1|newsdc_db)" SDC/.env; then just env-prod; else just env-local; fi

# Mostra qual ambiente esta ativo
env-status:
    @grep "DB_HOST=" SDC/.env | head -1

# ==================== NGROK (TUNEL PUBLICO) ====================

ngrok_domain := "parasitic-portfolio-module.ngrok-free.dev"
ngrok_port := "8000"

# Inicia o tunel ngrok: https://{{ngrok_domain}} -> http://localhost:{{ngrok_port}}.
# O dominio reservado deve bater com TUNNEL_URL/APP_URL do SDC/.env.
# Requer ngrok instalado e autenticado (ngrok config add-authtoken <token>).
# Roda em foreground; use Ctrl+C para encerrar o tunel.
ngrok:
    ngrok http {{ngrok_port}} --domain={{ngrok_domain}}

# Mostra o status/tuneis ativos via API local do ngrok (web inspector na 4040).
ngrok-status:
    @curl -s http://localhost:4040/api/tunnels || echo "ngrok nao esta rodando (porta 4040 indisponivel)."
