# Justfile para o projeto SDC
# Uso: just <comando>

# Variáveis
docker_app := "newsdc_app"
docker_db := "newsdc_db"
docker_nginx := "newsdc_nginx"

# Lista todos os comandos disponíveis
default:
    @just --list

# ==================== DOCKER ====================

# Inicia os containers Docker
up:
    cd SDC && docker-compose up -d

# Para os containers Docker
down:
    cd SDC && docker-compose down

# Reinicia os containers Docker
restart:
    cd SDC && docker-compose restart

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

# ==================== FRONTEND ====================

# Build do frontend
build:
    cd SDC && npm run build

# Dev do frontend (watch)
dev:
    cd SDC && npm run dev

# Instala dependências do NPM
npm-install:
    cd SDC && npm install

# ==================== PERMISSÕES ====================

# Corrige permissões dos arquivos
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
    @docker ps --format "  - {{.Names}}: {{.Status}}"
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
    cd SDC && docker-compose up -d
    sleep 5
    just migrate
    just clear
    @echo "✅ Aplicação reiniciada!"

# Setup completo do projeto
setup:
    cd SDC && docker-compose up -d
    sleep 10
    just migrate
    just tdap-migrate
    just ajuda-migrate
    just clear
    cd SDC && npm install
    just build
    @echo "✅ Setup completo finalizado!"
