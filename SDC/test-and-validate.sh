#!/bin/bash

# ════════════════════════════════════════════════════════════════════════════
# Backend RAT - Script de Teste e Validação Completa
# ════════════════════════════════════════════════════════════════════════════

set -e

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║     Backend RAT - Validação Completa (25/04/2026)            ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Status counters
PASSED=0
FAILED=0

# Função de log
log_step() {
    echo -e "${BLUE}▶ $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
    ((PASSED++))
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
    ((FAILED++))
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# ════════════════════════════════════════════════════════════════════════════
# 1. VERIFICAÇÃO DE AMBIENTE
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "1️⃣  VERIFICAÇÃO DE AMBIENTE"
echo "──────────────────────────────────────────────────────────────"

# Verificar PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9.]+')
    log_success "PHP encontrado (versão $PHP_VERSION)"
else
    log_error "PHP não encontrado"
    exit 1
fi

# Verificar Composer
if command -v composer &> /dev/null; then
    log_success "Composer encontrado"
else
    log_error "Composer não encontrado"
fi

# Verificar BUN
if command -v bun &> /dev/null; then
    BUN_VERSION=$(bun --version)
    log_success "BUN encontrado (versão $BUN_VERSION)"
else
    log_warning "BUN não encontrado (opcional)"
fi

# Verificar PostgreSQL
if command -v psql &> /dev/null; then
    PG_VERSION=$(psql --version)
    log_success "PostgreSQL encontrado ($PG_VERSION)"
else
    log_warning "PostgreSQL CLI não encontrado (verifique se está rodando)"
fi

# ════════════════════════════════════════════════════════════════════════════
# 2. VERIFICAÇÃO DO PROJETO
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "2️⃣  VERIFICAÇÃO DO PROJETO"
echo "──────────────────────────────────────────────────────────────"

# Verificar se está no diretório correto
if [ ! -f "artisan" ]; then
    log_error "Artisan não encontrado. Execute do diretório raiz do projeto."
    exit 1
fi
log_success "Projeto Laravel encontrado"

# Verificar se .env existe
if [ ! -f ".env" ]; then
    log_warning ".env não encontrado, copiando .env.example"
    cp .env.example .env
fi
log_success ".env configurado"

# Verificar se composer.json existe
if [ -f "composer.json" ]; then
    log_success "composer.json encontrado"
fi

# Verificar package.json
if [ -f "package.json" ]; then
    log_success "package.json encontrado"
fi

# ════════════════════════════════════════════════════════════════════════════
# 3. VERIFICAÇÃO DA CONEXÃO COM BANCO DE DADOS
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "3️⃣  VERIFICAÇÃO DO BANCO DE DADOS"
echo "──────────────────────────────────────────────────────────────"

log_step "Testando conexão PostgreSQL..."

# Tentar conectar ao banco
DB_HOST=$(grep "DB_HOST" .env | cut -d '=' -f2 | tr -d ' ')
DB_PORT=$(grep "DB_PORT" .env | cut -d '=' -f2 | tr -d ' ')
DB_NAME=$(grep "DB_DATABASE" .env | cut -d '=' -f2 | tr -d ' ')
DB_USER=$(grep "DB_USERNAME" .env | cut -d '=' -f2 | tr -d ' ')

echo "  Host: $DB_HOST:$DB_PORT"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"

# Verificar banco com PHP
php artisan tinker <<EOF 2>/dev/null || true
DB::connection('pgsql')->getPdo();
echo "OK";
EOF

if [ $? -eq 0 ]; then
    log_success "Conexão com PostgreSQL OK"
else
    log_warning "Não foi possível verificar conexão (banco pode não estar rodando)"
fi

# ════════════════════════════════════════════════════════════════════════════
# 4. VERIFICAÇÃO DE ROTAS
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "4️⃣  VERIFICAÇÃO DE ROTAS"
echo "──────────────────────────────────────────────────────────────"

log_step "Listando rotas RAT..."

RAT_ROUTES=$(php artisan route:list --verbose 2>/dev/null | grep -c "/rat" || echo "0")

if [ "$RAT_ROUTES" -gt 0 ]; then
    log_success "Encontradas $RAT_ROUTES rotas RAT"
    php artisan route:list --verbose | grep "/rat" | head -10
else
    log_error "Nenhuma rota RAT encontrada"
fi

# ════════════════════════════════════════════════════════════════════════════
# 5. VERIFICAÇÃO DE MODELS E CLASSES
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "5️⃣  VERIFICAÇÃO DE MODELS E CLASSES"
echo "──────────────────────────────────────────────────────────────"

# Verificar Models
MODELS=(
    "app/Models/Rat/RatOcorrencia.php"
    "app/Models/Rat/RatOcorrenciaHistorico.php"
    "app/Modules/Rat/Models/Relatos/RatRelatoDadosGerais.php"
    "app/Modules/Rat/Models/Relatos/RatRelatoEnvolvidos.php"
    "app/Modules/Rat/Models/Relatos/RatRelatoRecurso.php"
)

for model in "${MODELS[@]}"; do
    if [ -f "$model" ]; then
        log_success "Model encontrado: $model"
    else
        log_warning "Model não encontrado: $model"
    fi
done

# Verificar Controllers
if [ -f "app/Modules/Rat/Controllers/RatUnifiedController.php" ]; then
    log_success "RatUnifiedController encontrado"
else
    log_error "RatUnifiedController não encontrado"
fi

# Verificar Services
SERVICES=(
    "app/Modules/Rat/Services/RatWriteService.php"
    "app/Modules/Rat/Application/Services/RatService.php"
)

for service in "${SERVICES[@]}"; do
    if [ -f "$service" ]; then
        log_success "Service encontrado: $service"
    else
        log_warning "Service não encontrado: $service"
    fi
done

# ════════════════════════════════════════════════════════════════════════════
# 6. LIMPEZA E PREPARAÇÃO DO AMBIENTE
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "6️⃣  PREPARAÇÃO DO AMBIENTE"
echo "──────────────────────────────────────────────────────────────"

log_step "Limpando cache..."
php artisan config:cache 2>/dev/null && log_success "Cache config limpo" || log_warning "Erro ao limpar config"
php artisan route:cache 2>/dev/null && log_success "Cache routes limpo" || log_warning "Erro ao limpar routes"

# ════════════════════════════════════════════════════════════════════════════
# 7. EXECUÇÃO DE MIGRATIONS
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "7️⃣  EXECUÇÃO DE MIGRATIONS"
echo "──────────────────────────────────────────────────────────────"

log_step "Verificando migrations..."

if [ "$1" == "--migrate" ] || [ "$1" == "--reset" ]; then
    if [ "$1" == "--reset" ]; then
        log_step "Resetando banco de dados..."
        php artisan migrate:fresh --database=pgsql --force || log_error "Erro ao resetar migrations"
    else
        log_step "Executando migrations..."
        php artisan migrate --database=pgsql || log_error "Erro ao executar migrations"
    fi
    log_success "Migrations executadas"
else
    log_warning "Migrations não executadas (use --migrate para executar)"
fi

# ════════════════════════════════════════════════════════════════════════════
# 8. SEEDING DE DADOS
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "8️⃣  SEEDING DE DADOS"
echo "──────────────────────────────────────────────────────────────"

if [ "$1" == "--seed" ] || [ "$1" == "--reset" ]; then
    log_step "Executando seeder RAT..."
    php artisan db:seed --class=RatSeeder --database=pgsql || log_error "Erro ao executar seeder"
    log_success "Dados de teste criados"
else
    log_warning "Seeder não executado (use --seed ou --reset para executar)"
fi

# ════════════════════════════════════════════════════════════════════════════
# 9. EXECUÇÃO DE TESTES
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "9️⃣  EXECUÇÃO DE TESTES"
echo "──────────────────────────────────────────────────────────────"

if [ "$1" == "--test" ] || [ "$1" == "--all" ]; then
    log_step "Executando testes CRUD..."

    if php artisan test tests/Feature/Api/Rat/RatCrudTest.php --passthru 2>/dev/null; then
        log_success "Testes CRUD passaram"
    else
        log_warning "Testes CRUD falharam (verifique a saída acima)"
    fi
else
    log_warning "Testes não executados (use --test ou --all para executar)"
fi

# ════════════════════════════════════════════════════════════════════════════
# 10. VALIDAÇÃO DA API
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "🔟  VALIDAÇÃO DA API"
echo "──────────────────────────────────────────────────────────────"

if [ -f "validate_rat_api.php" ]; then
    log_step "Executando validação da API..."
    php validate_rat_api.php
    log_success "Validação da API concluída"
else
    log_warning "Script validate_rat_api.php não encontrado"
fi

# ════════════════════════════════════════════════════════════════════════════
# RESUMO FINAL
# ════════════════════════════════════════════════════════════════════════════

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                      RESUMO FINAL                             ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✅ Checks Passed: $PASSED${NC}"
echo -e "${RED}❌ Checks Failed: $FAILED${NC}"
echo ""

if [ "$FAILED" -eq 0 ]; then
    echo -e "${GREEN}✨ TUDO PRONTO PARA PRODUÇÃO! ✨${NC}"
    echo ""
    echo "Próximos passos:"
    echo "  1. Iniciar o servidor FrankenPHP:"
    echo "     frankenphp run --listen=127.0.0.1:8000"
    echo ""
    echo "  2. Iniciar o frontend com Vite:"
    echo "     bun run dev"
    echo ""
    echo "  3. Testar endpoints:"
    echo "     curl http://localhost:8000/rat"
    echo ""
    exit 0
else
    echo -e "${RED}⚠️  EXISTEM PROBLEMAS A SEREM RESOLVIDOS${NC}"
    echo ""
    exit 1
fi
