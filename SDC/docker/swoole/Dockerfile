# ============================================================================
# SDC - DOCKERFILE SWOOLE (Producao)
# Octane sobre Swoole: coroutines + I/O assincrono + task workers.
# Baseado em docker/Dockerfile.prod (estrutura de build) com tuning
# opcache/JIT para workers persistentes.
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
# swoole DEVE ser compilado com:
#  - openssl: sob SWOOLE_HOOK_ALL o predis abre o Redis da Azure via tls:// (porta
#    6380) e o hook de stream do Swoole so trata TLS se a extensao tiver openssl.
#  - swoole-pgsql: SEM ele o SWOOLE_HOOK_ALL NAO hooka o pdo_pgsql e as queries
#    Postgres BLOQUEIAM o worker (zero paralelismo de I/O sob coroutine). Validado
#    por benchmark 2x pg_sleep(0.3): 600ms (serial) -> 336ms (paralelo, 2 conexoes).
# A extensao 'sockets' nativa ja foi instalada acima (--enable-sockets exige
# php_sockets.h); postgresql-dev (libpq) ja consta nos build-deps acima.
# Build MANUAL (configure+make) do swoole em vez de pecl --configureoptions: o
# pecl se mostrou instavel ao passar enable-swoole-pgsql; o manual e deterministico.
ARG SWOOLE_VERSION=6.2.1
# O pecl/PEAR ignora as variaveis de ambiente de proxy; em rede corporativa
# (build com --build-arg http_proxy=...) o proxy precisa ir para a config do
# PEAR. Sem proxy no build, o if e um no-op e nada muda.
RUN if [ -n "$http_proxy" ]; then pear config-set http_proxy "$http_proxy"; fi
RUN pecl install igbinary \
    && docker-php-ext-enable igbinary \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && cd /tmp \
    && pecl download swoole-${SWOOLE_VERSION} \
    && tar xzf swoole-*.tgz \
    && cd swoole-${SWOOLE_VERSION} \
    && phpize \
    && ./configure --enable-openssl --enable-sockets --enable-swoole-pgsql \
    && make -j"$(nproc)" \
    && make install \
    && docker-php-ext-enable swoole \
    && cd / \
    && rm -rf /tmp/swoole* \
    && apk del .build-deps

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Bun para compilar assets (temporario - removido apos build)
RUN apk add --no-cache curl bash unzip \
    && curl -fsSL https://bun.sh/install | bash \
    && ln -s /root/.bun/bin/bun /usr/local/bin/bun \
    && ln -s /root/.bun/bin/bunx /usr/local/bin/bunx

# Configuracao PHP: memoria, limites de upload e opcache/JIT calibrado para
# workers persistentes.
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
    --no-autoloader \
    --ignore-platform-reqs \
    --no-scripts || (echo "ERRO ao instalar dependencias" && cat composer.json && exit 1)

# Resto do codigo
COPY . .

# Remover hot file do Vite dev server
RUN rm -f public/hot

RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

# Autoloader otimizado com codigo presente
RUN composer dump-autoload --optimize --classmap-authoritative

# Gate de build: toda classe type-hintada nas rotas (ex.: FormRequest) deve
# resolver pelo autoloader, senao o BUILD falha. Evita publicar imagem com
# classmap inconsistente com o codigo -> 500 em toda pagina via Ziggy (10/06).
# APP_KEY dummy inline so para bootar o artisan -- route:verify-signatures nao
# toca em DB/rede; o entrypoint injeta o ambiente real em runtime. (.env e
# .dockerignored, entao nao existe no contexto de build.)
RUN APP_ENV=production APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    php artisan route:verify-signatures

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
    && php -r 'exit(defined("PASSWORD_ARGON2ID") ? 0 : 1);' \
    && echo "Build verificado: Swoole + Argon2id prontos"

# Swoole escuta na 8000 (Azure precisa de WEBSITES_PORT=8000)
EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
    CMD curl -sf http://localhost:8000/health || exit 1

CMD ["/start.sh"]
