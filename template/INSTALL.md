# 📦 Guia de Instalação de Dependências - SDC

Este guia explica como instalar as dependências do projeto SDC dentro dos containers Docker.

## 🚀 Instalação Automática (Recomendado)

### Opção 1: Instalação Automática ao Iniciar Containers

As dependências são instaladas automaticamente quando você inicia os containers pela primeira vez:

```bash
cd SDC
docker-compose -f docker-compose.dev.yml up -d
```

O script de entrypoint (`docker/entrypoint.dev.sh`) verifica e instala automaticamente:
- ✅ Dependências PHP (Composer)
- ✅ Arquivo .env (se não existir)
- ✅ Chave da aplicação Laravel
- ✅ Permissões de diretórios

### Opção 2: Usando o Script Helper

Execute o script helper que instala todas as dependências:

```bash
cd SDC
chmod +x docker/install-dependencies.sh
./docker/install-dependencies.sh
```

Este script:
- Verifica se os containers estão rodando
- Instala dependências PHP no container `sdc_app_dev`
- Instala dependências Node.js no container `sdc_node`

## 📋 Instalação Manual

### Dependências PHP (Composer)

```bash
# Entrar no container PHP
docker exec -it sdc_app_dev bash

# Instalar dependências
composer install

# Ou se preferir sem dev dependencies
composer install --no-dev --optimize-autoloader
```

### Dependências Node.js (NPM)

```bash
# Entrar no container Node
docker exec -it sdc_node sh

# Instalar dependências
npm install

# Ou usar npm ci para instalação limpa
npm ci
```

Ou execute diretamente sem entrar no container:

```bash
# Instalar dependências Node.js
docker exec -it sdc_node npm install

# Instalar dependências PHP
docker exec -it sdc_app_dev composer install
```

## 🔧 Configuração Inicial

### 1. Configurar Arquivo .env

```bash
# Copiar arquivo de exemplo
cp .env.example .env

# Editar variáveis de ambiente
nano .env
```

### 2. Gerar Chave da Aplicação

```bash
docker exec -it sdc_app_dev php artisan key:generate
```

### 3. Executar Migrations

```bash
docker exec -it sdc_app_dev php artisan migrate
```

### 4. Criar Link Simbólico para Storage

```bash
docker exec -it sdc_app_dev php artisan storage:link
```

## 🔄 Atualizar Dependências

### Atualizar Dependências PHP

```bash
docker exec -it sdc_app_dev composer update
```

### Atualizar Dependências Node.js

```bash
docker exec -it sdc_node npm update
```

## 🏗️ Build de Produção

Para produção, as dependências são instaladas automaticamente durante o build da imagem:

```bash
docker-compose -f docker-compose.prod.yml build
```

O `Dockerfile.prod` já inclui:
- Instalação de dependências PHP via Composer
- Instalação de dependências Node.js via NPM
- Build dos assets frontend
- Otimização e limpeza

## 📦 Dependências Instaladas

### PHP (Composer)
- Laravel Framework 12.0
- Inertia.js Laravel Adapter 1.3+
- Laravel Breeze 2.2+
- Laravel Sanctum 4.0+
- E outras dependências listadas em `composer.json`

### Node.js (NPM)
- Vue.js 3.4.0+
- Inertia.js Vue3 Adapter 1.0.0+
- Tailwind CSS 3.2.1+
- Vite 5.0.0+
- E outras dependências listadas em `package.json`

## ⚠️ Troubleshooting

### Erro: "vendor directory not found"
```bash
docker exec -it sdc_app_dev composer install
```

### Erro: "node_modules not found"
```bash
docker exec -it sdc_node npm install
```

### Erro de Permissões
```bash
docker exec -it sdc_app_dev chmod -R 775 storage bootstrap/cache
docker exec -it sdc_app_dev chown -R www-data:www-data storage bootstrap/cache
```

### Limpar Cache do Composer
```bash
docker exec -it sdc_app_dev composer clear-cache
```

### Limpar Cache do NPM
```bash
docker exec -it sdc_node npm cache clean --force
```

### Reinstalar Tudo do Zero
```bash
# Parar containers
docker-compose -f docker-compose.dev.yml down

# Remover volumes (cuidado: isso apaga dados!)
docker-compose -f docker-compose.dev.yml down -v

# Reconstruir e iniciar
docker-compose -f docker-compose.dev.yml up -d --build

# Instalar dependências
./docker/install-dependencies.sh
```

## 📚 Referências

- [Documentação do Laravel](https://laravel.com/docs)
- [Documentação do Inertia.js](https://inertiajs.com/)
- [Documentação do Vue.js](https://vuejs.org/)
- [Documentação do Tailwind CSS](https://tailwindcss.com/)
- [Documentação do Vite](https://vitejs.dev/)

---

**Última atualização**: 2025-01-27

