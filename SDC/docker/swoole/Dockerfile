# ============================================================================
# SDC - DOCKERFILE SWOOLE (Producao)
# Octane sobre Swoole: coroutines + I/O assincrono + task workers.
# Baseado em docker/frankenphp/Dockerfile (tuning opcache/JIT) e
# docker/Dockerfile.prod (estrutura de build). FrankenPHP segue como rollback.
# ============================================================================

FROM php:8.4-cli-alpine

LABEL maintainer="SDC Team"
LABEL environment="production"
LABEL server="swoole"

# Dependencias de runtime + build (build-deps removido ao final)
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng \
    libjpeg-turbo \
    freetype \
    libwebp \
    oniguruma \
    libxml2 \
    libzip \
    postgresql-libs \
    icu-libs \
    jemalloc \
    openssl \
    && apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    openssl-dev \
    linux-headers \
    $PHPIZE_DEPS

# Extensoes PHP nativas
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) \
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
    sockets

# Extensoes PECL: swoole (coroutines), igbinary, redis.
# swoole DEVE ser compilado com openssl: sob SWOOLE_HOOK_ALL o predis abre o
# Redis da Azure via tls:// (porta 6380) e o hook de stream do Swoole so trata
# TLS se a extensao tiver openssl (define SWOOLE_SSL). A extensao 'sockets'
# nativa ja foi instalada acima (swoole --enable-sockets exige php_sockets.h).
# brotli e desligado de proposito: vem ligado por padrao no Swoole 6 e exige
# libbrotlienc; compressao HTTP usa gzip (suficiente, TLS/HTTP2 terminam na
# borda Azure). Combinacao validada localmente: SWOOLE_SSL=true.
RUN pecl install igbinary \
    && docker-php-ext-enable igbinary \
    && pecl install redis-6.0.2 \
    && docker-php-ext-enable redis \
    && pecl install --configureoptions 'enable-openssl="yes" enable-sockets="yes" enable-brotli="no"' swoole \
    && docker-php-ext-enable swoole \
    && apk del .build-deps

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Bun para compilar assets (temporario - removido apos build)
RUN apk add --no-cache curl bash unzip \
    && curl -fsSL https://bun.sh/install | bash \
    && ln -s /root/.bun/bin/bun /usr/local/bin/bun \
    && ln -s /root/.bun/bin/bunx /usr/local/bin/bunx

# Configuracao PHP: memoria, limites de upload e opcache/JIT calibrado para
# workers persistentes (mesmos valores do Dockerfile FrankenPHP).
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo "memory_limit=512M"; \
        echo "max_execution_time=30"; \
        echo "upload_max_filesize=64M"; \
        echo "post_max_size=64M"; \
    } > /usr/local/etc/php/conf.d/99-memory.ini \
    && { \
        echo "opcache.enable=1"; \
        echo "opcache.enable_cli=1"; \
        echo "opcache.memory_consumption=256"; \
        echo "opcache.interned_strings_buffer=32"; \
        echo "opcache.max_accelerated_files=20000"; \
        echo "opcache.validate_timestamps=0"; \
        echo "opcache.jit_buffer_size=100M"; \
        echo "opcache.jit=1255"; \
    } > /usr/local/etc/php/conf.d/99-opcache.ini

# jemalloc: alocador para processos de longa duracao (menos fragmentacao).
ENV LD_PRELOAD=/usr/lib/libjemalloc.so.2

WORKDIR /var/www

# Dependencias primeiro (cache Docker)
COPY composer.json composer.lock* ./
COPY package.json bun.lock* ./

RUN test -f composer.json || (echo "ERRO: composer.json nao encontrado!" && ls -la && exit 1)

# Dependencias PHP. --ignore-platform-reqs porque ext-swoole pode nao constar
# como require no composer.json; a extensao esta instalada acima.
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs \
    --no-scripts || (echo "ERRO ao instalar dependencias" && cat composer.json && exit 1)

# Resto do codigo
COPY . .

# Remover hot file do Vite dev server
RUN rm -f public/hot

RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

# Autoloader otimizado com codigo presente
RUN composer dump-autoload --optimize --classmap-authoritative

# Symlink para case-sensitivity (Linux)
RUN cd resources/js && ln -sf composables Composables

# Compilar assets com Bun
RUN bun install --frozen-lockfile \
    && bun run build \
    && ls -la public/build/ \
    && test -f public/build/manifest.json || exit 1

# Remover Bun apos build
RUN rm -rf /root/.bun /usr/local/bin/bun /usr/local/bin/bunx

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint (strip CRLF para compatibilidade Linux)
COPY docker/swoole/scripts/entrypoint.sh /start.sh
RUN sed -i 's/\r$//' /start.sh && chmod +x /start.sh

RUN test -f /var/www/composer.json \
    && test -d /var/www/vendor \
    && test -f /var/www/public/build/manifest.json \
    && php -m | grep -qi swoole \
    && echo "Build verificado: Swoole pronto"

# Swoole escuta na 8000 (Azure precisa de WEBSITES_PORT=8000)
EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
    CMD curl -sf http://localhost:8000/health || exit 1

CMD ["/start.sh"]
