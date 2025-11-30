# Dependências do Projeto SDC

## Dependências PHP (Composer)

### Core Framework
- **laravel/framework**: ^10.10 - Framework Laravel principal
- **php**: ^8.4 - Versão PHP 8.4

### Autenticação e Autorização
- **laravel/sanctum**: ^3.2 - Autenticação API com tokens
- **laravel/breeze**: ^1.29 - Scaffolding de autenticação

### Frontend Stack (Inertia.js Stack)
- **inertiajs/inertia-laravel**: ^0.6.8 - Bridge Laravel-Vue.js
- **tightenco/ziggy**: ^2.0 - Rotas Laravel para JavaScript

### Utilitários
- **guzzlehttp/guzzle**: ^7.2 - Cliente HTTP
- **laravel/tinker**: ^2.8 - REPL para Laravel

### Dependências de Desenvolvimento
- **laravel/sail**: ^1.18 - Ambiente de desenvolvimento Docker
- **laravel/pint**: ^1.0 - Formatação de código PHP
- **phpunit/phpunit**: ^10.1 - Framework de testes
- **mockery/mockery**: ^1.4.4 - Mock objects para testes
- **fakerphp/faker**: ^1.9.1 - Geração de dados falsos
- **nunomaduro/collision**: ^7.0 - Tratamento de erros em CLI
- **spatie/laravel-ignition**: ^2.0 - Página de erro elegante

## Dependências JavaScript (NPM)

### Frontend Framework
- **vue**: ^3.4.0 - Framework Vue.js 3
- **@inertiajs/vue3**: ^1.0.0 - Adaptador Inertia para Vue 3

### Build Tools
- **vite**: ^5.0.0 - Build tool e dev server
- **@vitejs/plugin-vue**: ^5.0.0 - Plugin Vue para Vite
- **laravel-vite-plugin**: ^1.0.0 - Plugin Laravel para Vite

### Estilização
- **tailwindcss**: ^3.2.1 - Framework CSS utilitário
- **@tailwindcss/forms**: ^0.5.3 - Plugin de formulários Tailwind
- **autoprefixer**: ^10.4.12 - PostCSS plugin
- **postcss**: ^8.4.31 - Processador CSS

### Utilitários
- **axios**: ^1.6.4 - Cliente HTTP para JavaScript

## Pacotes Essenciais Recomendados

### Backend (a serem adicionados)
```bash
# Validação e transformação de dados
composer require spatie/laravel-data

# Logs e monitoramento
composer require spatie/laravel-activity-log
composer require barryvdh/laravel-debugbar --dev

# API Resources
composer require spatie/laravel-query-builder
composer require spatie/laravel-fractal

# Otimização e Cache
composer require spatie/laravel-responsecache

# Jobs e Queues
composer require laravel/horizon

# Geração de documentação API
composer require darkaonline/l5-swagger

# Testes
composer require pestphp/pest --dev
composer require pestphp/pest-plugin-laravel --dev
```

### Frontend (a serem adicionados)
```bash
# Componentes UI
npm install @headlessui/vue
npm install @heroicons/vue

# Gerenciamento de estado
npm install pinia

# Validação de formulários
npm install vee-validate
npm install yup

# Utilitários
npm install lodash-es
npm install dayjs

# Ícones
npm install lucide-vue-next
```

## Estrutura de Pastas (SOLID)

```
app/
├── Domain/                    # Lógica de negócio pura
│   ├── User/
│   │   ├── Models/           # Modelos de domínio
│   │   ├── Enums/            # Enumerações
│   │   ├── ValueObjects/     # Value Objects
│   │   └── Events/           # Eventos de domínio
│   └── ...
├── Application/              # Casos de uso
│   ├── User/
│   │   ├── Actions/          # Actions/Commands
│   │   ├── Queries/          # Queries
│   │   └── DTOs/             # Data Transfer Objects
│   └── ...
├── Infrastructure/           # Implementações técnicas
│   ├── Persistence/
│   │   └── Repositories/    # Implementações de repositórios
│   ├── Services/            # Serviços externos
│   └── Cache/               # Cache implementations
└── Presentation/            # Camada de apresentação
    ├── Http/
    │   ├── Controllers/     # Controllers HTTP
    │   ├── Requests/        # Form Requests
    │   ├── Resources/       # API Resources
    │   └── Middleware/      # Middlewares
    └── Console/
        └── Commands/        # Comandos Artisan
```

## URLs de Referência

- **Inertia.js**: https://inertiajs.com/
- **Vue.js**: https://vuejs.org/
- **Tailwind CSS**: https://tailwindcss.com/
- **Laravel**: https://laravel.com/docs
- **Vite**: https://vitejs.dev/

## Comandos Docker

```bash
# Construir containers
docker-compose build

# Iniciar ambiente
docker-compose up -d

# Instalar dependências PHP
docker-compose run --rm app composer install

# Instalar dependências Node
docker-compose run --rm node npm install

# Rodar migrações
docker-compose run --rm app php artisan migrate

# Rodar testes
docker-compose run --rm app php artisan test

# Parar containers
docker-compose down
```

## Requisitos do Sistema

- Docker >= 20.10
- Docker Compose >= 2.0
- Git
- Composer >= 2.0 (para desenvolvimento local sem Docker)
- Node.js >= 20 (para desenvolvimento local sem Docker)
- PHP >= 8.4 (para desenvolvimento local sem Docker)
