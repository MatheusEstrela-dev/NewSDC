# ============================================================================
# SDC - DOCKERFILE FRANKENPHP (Producao)
# Build otimizado com Early Hints e HTTP/2-3 nativo
# ============================================================================

# FROM dunglas/frankenphp:latest-php8.3-alpine
FROM c24773c3328e

LABEL maintainer="SDC Team"
LABEL environment="production"
LABEL server="frankenphp"

ARG UID=1000
ARG GID=1000

# Instalar extensoes PHP necessarias
RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    sockets \
    redis

# Instalar Composer via apk (evita pull do DockerHub bloqueado por proxy)
RUN apk add --no-cache composer

# Instalar Bun para build de assets (temporario)
RUN apk add --no-cache curl bash unzip \
    && curl -fsSL https://bun.sh/install | bash \
    && ln -s /root/.bun/bin/bun /usr/local/bin/bun \
    && ln -s /root/.bun/bin/bunx /usr/local/bin/bunx

# Configuracao PHP otimizada
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/99-memory.ini \
    && echo "max_execution_time=30" >> /usr/local/etc/php/conf.d/99-memory.ini \
    && echo "upload_max_filesize=32M" >> /usr/local/etc/php/conf.d/99-memory.ini \
    && echo "post_max_size=34M" >> /usr/local/etc/php/conf.d/99-memory.ini \
    && echo "opcache.enable=1" > /usr/local/etc/php/conf.d/99-opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/99-opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/99-opcache.ini \
    && echo "opcache.interned_strings_buffer=32" >> /usr/local/etc/php/conf.d/99-opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/99-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/99-opcache.ini \
    && echo "opcache.jit_buffer_size=100M" >> /usr/local/etc/php/conf.d/99-opcache.ini \
    && echo "opcache.jit=1255" >> /usr/local/etc/php/conf.d/99-opcache.ini

# Verificar binario frankenphp (ja vem na imagem base)
RUN test -f /usr/local/bin/frankenphp && chmod +x /usr/local/bin/frankenphp && echo "FrankenPHP binary OK"

# Diretorio de trabalho
WORKDIR /app

# Copiar arquivos de dependencias primeiro (cache Docker)
COPY composer.json composer.lock* ./
COPY package.json bun.lock* ./

# Verificar composer.json
RUN test -f composer.json || (echo "ERRO: composer.json nao encontrado!" && exit 1)

# Instalar dependencias PHP (sem otimizar ainda - codigo nao copiado)
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-autoloader \
    --ignore-platform-reqs \
    --no-scripts

# Copiar codigo da aplicacao
COPY . .

# Substituir FrankenPHP pelo binario v1.12.1 (base image vem com versao antiga)
RUN cp /app/docker/frankenphp/frankenphp-linux-x86_64 /usr/local/bin/frankenphp \
    && chmod +x /usr/local/bin/frankenphp \
    && ln -sf /usr/local/bin/frankenphp /app/frankenphp

# Remover arquivo hot do Vite dev server
RUN rm -f public/hot

# Agora otimizar autoloader com codigo presente
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts

# Criar symlink para resolver case-sensitivity
RUN cd resources/js && ln -sf composables Composables 2>/dev/null || true

# Instalar dependencias e compilar assets com Bun
RUN bun install && \
    bun run build && \
    test -f public/build/manifest.json || exit 1

# Remover Bun apos compilar (reduzir tamanho)
RUN rm -rf /root/.bun /usr/local/bin/bun /usr/local/bin/bunx

# Criar diretorios necessarios
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copiar script de entrypoint
COPY docker/frankenphp/scripts/entrypoint.sh /start.sh
RUN chmod +x /start.sh

# Verificar build
RUN test -f /app/composer.json && \
    test -d /app/vendor && \
    test -f /app/public/build/manifest.json && \
    echo "Build verificado: FrankenPHP pronto"

# Expor portas (HTTP, HTTPS, Admin API Caddy)
EXPOSE 80 443 2019

# Health check — HTTP puro (TLS terminado pelo Azure na borda)
HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
    CMD curl -sf http://localhost:${PORT:-80}/health || exit 1

# Comando padrao
CMD ["/start.sh"]
