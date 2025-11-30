# ============================================================================
# Unified Exporter - Redis + MySQL Metrics
# ============================================================================
FROM alpine:3.19

# Instalar dependências
RUN apk add --no-cache \
    ca-certificates \
    supervisor \
    curl \
    wget

# Baixar Redis Exporter
RUN wget -q https://github.com/oliver006/redis_exporter/releases/download/v1.58.0/redis_exporter-v1.58.0.linux-amd64.tar.gz \
    && tar xzf redis_exporter-v1.58.0.linux-amd64.tar.gz \
    && mv redis_exporter-v1.58.0.linux-amd64/redis_exporter /usr/local/bin/ \
    && rm -rf redis_exporter-v1.58.0.linux-amd64*

# Baixar MySQL Exporter
RUN wget -q https://github.com/prometheus/mysqld_exporter/releases/download/v0.15.1/mysqld_exporter-0.15.1.linux-amd64.tar.gz \
    && tar xzf mysqld_exporter-0.15.1.linux-amd64.tar.gz \
    && mv mysqld_exporter-0.15.1.linux-amd64/mysqld_exporter /usr/local/bin/ \
    && rm -rf mysqld_exporter-0.15.1.linux-amd64*

# Copiar configuração do Supervisor
COPY supervisord.conf /etc/supervisord.conf

# Healthcheck
HEALTHCHECK --interval=10s --timeout=5s --retries=3 \
    CMD wget -q --spider http://localhost:9121/metrics && \
        wget -q --spider http://localhost:9104/metrics || exit 1

EXPOSE 9121 9104

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
