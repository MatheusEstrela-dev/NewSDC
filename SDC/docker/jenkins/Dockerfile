# ===== DOCKERFILE JENKINS PRODUCTION-READY =====
# Resolve TODOS os problemas mencionados no jenkins02.md

FROM jenkins/jenkins:lts-jdk17

# Informações de build
LABEL maintainer="SDC DevOps Team"
LABEL description="Jenkins Master com Docker, SSH e ferramentas de CI/CD"
LABEL version="1.0.0"

# ===== RESOLVER PROBLEMA 1: UID/GID (Permission Denied) =====
# Por padrão, jenkins roda com UID 1000
# Vamos criar um grupo docker com GID correto para acessar docker.sock

USER root

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    openssh-client \
    vim \
    wget \
    unzip \
    jq \
    python3 \
    python3-pip \
    && rm -rf /var/lib/apt/lists/*

# ===== RESOLVER PROBLEMA 3: SSH e Git (Chaves e Hosts) =====
# Configurar SSH para aceitar hosts conhecidos automaticamente
RUN mkdir -p /var/jenkins_home/.ssh && \
    ssh-keyscan github.com >> /var/jenkins_home/.ssh/known_hosts && \
    ssh-keyscan gitlab.com >> /var/jenkins_home/.ssh/known_hosts && \
    ssh-keyscan bitbucket.org >> /var/jenkins_home/.ssh/known_hosts && \
    chmod 700 /var/jenkins_home/.ssh && \
    chmod 644 /var/jenkins_home/.ssh/known_hosts

# ===== INSTALAR DOCKER CLI (Docker-outside-of-Docker) =====
# IMPORTANTE: Não instalamos Docker daemon, apenas o cliente
RUN curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg && \
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null && \
    apt-get update && \
    apt-get install -y docker-ce-cli docker-compose-plugin && \
    rm -rf /var/lib/apt/lists/*

# ===== INSTALAR DOCKER COMPOSE STANDALONE =====
RUN curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose && \
    chmod +x /usr/local/bin/docker-compose && \
    docker-compose --version

# ===== RESOLVER PROBLEMA 2B: Acesso ao Docker Socket =====
# Criar grupo docker com GID dinâmico (será configurado via ARG)
ARG DOCKER_GID=999
RUN groupadd -g ${DOCKER_GID} docker || groupmod -g ${DOCKER_GID} docker
RUN usermod -aG docker jenkins

# ===== INSTALAR FERRAMENTAS ADICIONAIS PARA BUILDS =====

# Node.js e NPM (para builds frontend)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@latest && \
    node --version && npm --version

# PHP e Composer (para projetos Laravel/PHP)
RUN apt-get update && apt-get install -y \
    php8.2-cli \
    php8.2-curl \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-zip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer --version

# ===== PLUGINS JENKINS ESSENCIAIS =====
# Instalar plugins via jenkins-plugin-cli (mais rápido e confiável)
RUN jenkins-plugin-cli --plugins \
    git \
    workflow-aggregator \
    docker-workflow \
    docker-plugin \
    kubernetes \
    configuration-as-code \
    job-dsl \
    pipeline-stage-view \
    blueocean \
    credentials-binding \
    ssh-agent \
    github \
    gitlab-plugin \
    slack \
    email-ext \
    prometheus \
    timestamper \
    ws-cleanup \
    build-timeout \
    ansicolor

# ===== CONFIGURAÇÃO DE SEGURANÇA =====
# Desabilitar assistente de setup (configuraremos via JCasC)
ENV JAVA_OPTS="-Djenkins.install.runSetupWizard=false"

# ===== SCRIPTS DE INICIALIZAÇÃO =====
COPY --chown=jenkins:jenkins init.groovy.d/ /usr/share/jenkins/ref/init.groovy.d/
COPY --chown=jenkins:jenkins casc.yaml /var/jenkins_home/casc.yaml

# ===== HEALTHCHECK SCRIPT =====
COPY healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# ===== AJUSTAR PERMISSÕES =====
RUN chown -R jenkins:jenkins /var/jenkins_home

# ===== RESOLVER PROBLEMA 2A: Java Heap vs Docker Limit =====
# As variáveis JAVA_OPTS serão configuradas no docker-compose.yml
# mas garantimos valores padrão seguros aqui
ENV JAVA_OPTS="-Xms512m -Xmx2g -Djava.awt.headless=true"

# ===== EXPOR PORTAS =====
EXPOSE 8080 50000

# Voltar para usuário jenkins (SEGURANÇA)
USER jenkins

# Healthcheck
HEALTHCHECK --interval=60s --timeout=10s --retries=5 --start-period=120s \
  CMD /usr/local/bin/healthcheck.sh

WORKDIR /var/jenkins_home

# O entrypoint padrão do jenkins/jenkins:lts já está configurado
