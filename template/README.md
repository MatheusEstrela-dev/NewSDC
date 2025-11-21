# SDC - Sistema de Controle

Aplicação Laravel com Vue.js, Inertia.js e Tailwind CSS, seguindo princípios SOLID e Domain-Driven Design.

## Stack Tecnológica

### Backend
- **PHP 8.4**
- **Laravel 12**
- **MySQL 8.0**
- **Redis**

### Frontend
- **Vue.js 3**
- **Inertia.js**
- **Tailwind CSS**
- **Vite**

### DevOps
- **Docker & Docker Compose**
- **Jenkins** (CI/CD)
- **Nginx**

## Requisitos

- Docker >= 20.10
- Docker Compose >= 2.0
- Git

## Instalação

### Ambiente de Desenvolvimento Local

```bash
# Clone o repositório
git clone <repository-url>
cd sdc

# Copie o arquivo de ambiente
cp .env.example .env

# Construa e inicie os containers de desenvolvimento
docker-compose -f docker-compose.dev.yml up -d --build

# Instale as dependências
docker-compose -f docker-compose.dev.yml run --rm app composer install
docker-compose -f docker-compose.dev.yml run --rm node npm install

# Configure a aplicação
docker-compose -f docker-compose.dev.yml run --rm app php artisan key:generate
docker-compose -f docker-compose.dev.yml run --rm app php artisan migrate
```

### Ambiente de Produção

```bash
# Construa e inicie os containers de produção
docker-compose -f docker-compose.prod.yml up -d --build

# Execute as configurações de produção
docker-compose -f docker-compose.prod.yml run --rm app php artisan config:cache
docker-compose -f docker-compose.prod.yml run --rm app php artisan route:cache
docker-compose -f docker-compose.prod.yml run --rm app php artisan view:cache
```

## Comandos Úteis

### Desenvolvimento

```bash
# Iniciar ambiente
docker-compose -f docker-compose.dev.yml up -d

# Ver logs
docker-compose -f docker-compose.dev.yml logs -f

# Parar containers
docker-compose -f docker-compose.dev.yml down

# Hot reload frontend
docker-compose -f docker-compose.dev.yml run --rm node npm run dev
```

### Produção

```bash
# Build de assets
docker-compose -f docker-compose.prod.yml run --rm node npm run build

# Otimizar autoloader
docker-compose -f docker-compose.prod.yml run --rm app composer install --optimize-autoloader --no-dev
```

## Serviços Docker

### Desenvolvimento
| Serviço | Porta | Descrição |
|---------|-------|-----------|
| nginx | 80, 443 | Servidor web |
| app | 9000 | PHP-FPM |
| db | 3306 | MySQL |
| redis | 6379 | Cache e filas |
| mailhog | 1025, 8025 | SMTP de teste |
| node | 5173 | Vite dev server |

### Produção
| Serviço | Porta | Descrição |
|---------|-------|-----------|
| nginx | 80, 443 | Servidor web |
| app | 9000 | PHP-FPM (otimizado) |
| db | - | MySQL (interno) |
| redis | - | Cache e filas (interno) |

## Documentação

- [Dependências](DEPENDENCIAS.md) - Lista completa de dependências
- [Arquitetura](README_ARCHITECTURE.md) - Arquitetura SOLID
- [Inertia.js](https://inertiajs.com/)
- [Vue.js 3](https://vuejs.org/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Laravel 10](https://laravel.com/docs/10.x)

## Licença

MIT
