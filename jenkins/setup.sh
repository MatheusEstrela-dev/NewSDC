#!/bin/bash
# ===== JENKINS SETUP SCRIPT =====
# Script para configuração inicial do Jenkins no Docker
# Resolve TODOS os problemas de permissão e configuração

set -e

echo "=========================================="
echo "Jenkins Docker Setup Script"
echo "=========================================="

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para printar com cores
print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar se está rodando no Linux
if [[ "$OSTYPE" != "linux-gnu"* ]]; then
    print_error "Este script deve ser executado no Linux"
    exit 1
fi

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    print_error "Docker não está instalado!"
    exit 1
fi

print_info "Docker encontrado: $(docker --version)"

# Verificar se Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose não está instalado!"
    exit 1
fi

print_info "Docker Compose encontrado: $(docker-compose --version)"

# ===== RESOLVER PROBLEMA 1: UID/GID =====
print_info "Configurando permissões de diretórios..."

# Criar diretórios necessários
mkdir -p jenkins_home
mkdir -p jenkins_cache/.m2
mkdir -p jenkins_cache/.gradle
mkdir -p jenkins_agent_workdir
mkdir -p jenkins_backups
mkdir -p jenkins/logs
mkdir -p jenkins/ssl

# Ajustar permissões (UID 1000 = usuário jenkins no container)
print_info "Ajustando UID/GID para 1000:1000 (usuário jenkins)..."
chown -R 1000:1000 jenkins_home
chown -R 1000:1000 jenkins_cache
chown -R 1000:1000 jenkins_agent_workdir

print_info "Permissões configuradas com sucesso!"

# ===== OBTER GID DO DOCKER =====
print_info "Detectando GID do grupo Docker..."

DOCKER_GID=$(getent group docker | cut -d: -f3)

if [ -z "$DOCKER_GID" ]; then
    print_warning "Grupo docker não encontrado. Usando GID padrão 999"
    DOCKER_GID=999
else
    print_info "Docker GID: $DOCKER_GID"
fi

# ===== CONFIGURAR ARQUIVO .env =====
if [ ! -f jenkins/.env ]; then
    print_info "Criando arquivo .env..."
    cp jenkins/.env.example jenkins/.env

    # Substituir DOCKER_GID no .env
    sed -i "s/DOCKER_GID=999/DOCKER_GID=$DOCKER_GID/" jenkins/.env

    print_warning "IMPORTANTE: Edite o arquivo jenkins/.env e configure:"
    print_warning "  - JENKINS_ADMIN_PASSWORD"
    print_warning "  - JENKINS_ADMIN_EMAIL"
    print_warning "  - GIT_REPO_URL"
    print_warning "  - DB_PASSWORD e outras credenciais"
else
    print_info "Arquivo .env já existe"
fi

# ===== GERAR CERTIFICADOS SSL AUTO-ASSINADOS =====
if [ ! -f jenkins/ssl/jenkins.crt ]; then
    print_info "Gerando certificados SSL auto-assinados..."

    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout jenkins/ssl/jenkins.key \
        -out jenkins/ssl/jenkins.crt \
        -subj "/C=BR/ST=State/L=City/O=SDC/CN=jenkins.local"

    chmod 644 jenkins/ssl/jenkins.crt
    chmod 600 jenkins/ssl/jenkins.key

    print_info "Certificados SSL criados!"
else
    print_info "Certificados SSL já existem"
fi

# ===== VERIFICAR ESPAÇO EM DISCO =====
print_info "Verificando espaço em disco..."

AVAILABLE_SPACE=$(df -BG . | tail -1 | awk '{print $4}' | sed 's/G//')

if [ "$AVAILABLE_SPACE" -lt 10 ]; then
    print_error "Espaço em disco insuficiente! Mínimo: 10GB, Disponível: ${AVAILABLE_SPACE}GB"
    exit 1
fi

print_info "Espaço disponível: ${AVAILABLE_SPACE}GB"

# ===== VERIFICAR MEMÓRIA =====
print_info "Verificando memória disponível..."

TOTAL_MEM=$(free -g | awk '/^Mem:/{print $2}')

if [ "$TOTAL_MEM" -lt 4 ]; then
    print_warning "Memória total: ${TOTAL_MEM}GB. Recomendado: mínimo 4GB"
    print_warning "Ajuste JAVA_OPTS no docker-compose.yml se necessário"
else
    print_info "Memória total: ${TOTAL_MEM}GB"
fi

# ===== CRIAR REDE DOCKER SE NÃO EXISTIR =====
print_info "Verificando rede Docker sdc_network..."

if ! docker network inspect sdc_network &> /dev/null; then
    print_warning "Rede sdc_network não existe. Criando..."
    docker network create sdc_network
    print_info "Rede sdc_network criada!"
else
    print_info "Rede sdc_network já existe"
fi

# ===== CONSTRUIR IMAGEM JENKINS =====
print_info "Construindo imagem Docker do Jenkins..."

docker-compose -f docker-compose.jenkins.yml build --build-arg DOCKER_GID=$DOCKER_GID

print_info "Imagem construída com sucesso!"

# ===== SUMMARY =====
echo ""
echo "=========================================="
print_info "Setup concluído com sucesso!"
echo "=========================================="
echo ""
print_info "Próximos passos:"
echo "  1. Edite o arquivo jenkins/.env com suas credenciais"
echo "  2. Inicie o Jenkins com: docker-compose -f docker-compose.jenkins.yml up -d"
echo "  3. Aguarde 2-3 minutos para inicialização completa"
echo "  4. Acesse: http://localhost:8080 ou https://localhost:443"
echo "  5. Login: admin / [senha configurada no .env]"
echo ""
print_warning "Checklist de Segurança:"
echo "  [ ] Alterar senha padrão do admin"
echo "  [ ] Configurar certificado SSL válido (em produção)"
echo "  [ ] Configurar firewall para portas 8080 e 443"
echo "  [ ] Configurar backup automático"
echo "  [ ] Adicionar chaves SSH para Git"
echo ""
print_info "Para monitorar logs: docker-compose -f docker-compose.jenkins.yml logs -f jenkins"
print_info "Para parar: docker-compose -f docker-compose.jenkins.yml down"
echo "=========================================="
