# ===== JENKINS ON-PREMISE (VM 10.160.131.50, atras do proxy Prodemge) =====
# Docker-outside-of-Docker: so o CLI; o daemon e acessado via socket-proxy
# (DOCKER_HOST=tcp://docker-proxy:2375, ver docker-compose.jenkins.yml).
# Configuracao 100% via JCasC (casc.yaml) — sem init.groovy.d.
#
# Build atras do proxy (os DOIS cases, apt le maiuscula e wget minuscula):
#   docker build \
#     --build-arg HTTP_PROXY=http://proxy.prodemge.gov.br:8080 \
#     --build-arg HTTPS_PROXY=http://proxy.prodemge.gov.br:8080 \
#     --build-arg http_proxy=http://proxy.prodemge.gov.br:8080 \
#     --build-arg https_proxy=http://proxy.prodemge.gov.br:8080 \
#     --build-arg NO_PROXY="localhost,127.0.0.1,10.0.0.0/8,.prodemge.gov.br" \
#     --build-arg DOCKER_GID=$(getent group docker | cut -d: -f3) \
#     -t sdc-jenkins:onprem .

FROM jenkins/jenkins:lts-jdk17

LABEL maintainer="SDC DevOps Team"
LABEL description="Jenkins Master on-premise com Docker CLI para CI/CD do SDC"
LABEL version="2.0.0"

ARG HTTP_PROXY
ARG HTTPS_PROXY
ARG NO_PROXY
ARG http_proxy
ARG https_proxy
ARG no_proxy

USER root

# Dependencias do sistema (sem Azure CLI: deploy e 100% on-premise)
RUN apt-get update && apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    openssh-client \
    unzip \
    jq \
    && rm -rf /var/lib/apt/lists/*

# Sem ssh-keyscan nem ref/.ssh: SSH (porta 22) nao atravessa o proxy Prodemge
# e o checkout e 100% HTTPS + token. Diretorio root em ref/ quebra o find do
# entrypoint (roda como jenkins) — nao criar nada em ref/ como root.

# Docker CLI + compose plugin (Docker-outside-of-Docker: sem daemon)
RUN curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg && \
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null && \
    apt-get update && \
    apt-get install -y docker-ce-cli docker-compose-plugin && \
    rm -rf /var/lib/apt/lists/*

# Grupo docker alinhado ao GID do host — necessario apenas se o socket for
# montado direto; com o socket-proxy (padrao) e inofensivo.
ARG DOCKER_GID=999
RUN groupadd -g ${DOCKER_GID} docker || groupmod -g ${DOCKER_GID} docker
RUN usermod -aG docker jenkins

# Plugins essenciais (lista enxuta; Pipeline/Git/Credentials ja cobrem o
# Jenkinsfile.onprem — docker-workflow/kubernetes/blueocean ficam de fora).
# jenkins-plugin-cli e Java e ignora http_proxy: traduzir para -Dhttp.proxy*.
RUN if [ -n "$HTTPS_PROXY" ]; then \
        PROXY_HOST=$(echo "$HTTPS_PROXY" | sed -E 's#^https?://##; s#:[0-9]+/?$##'); \
        PROXY_PORT=$(echo "$HTTPS_PROXY" | sed -E 's#^.*:([0-9]+)/?$#\1#'); \
        export JAVA_TOOL_OPTIONS="-Dhttp.proxyHost=$PROXY_HOST -Dhttp.proxyPort=$PROXY_PORT -Dhttps.proxyHost=$PROXY_HOST -Dhttps.proxyPort=$PROXY_PORT"; \
    fi; \
    jenkins-plugin-cli --plugins \
    git \
    workflow-aggregator \
    configuration-as-code \
    job-dsl \
    credentials-binding \
    github \
    pipeline-stage-view \
    junit \
    email-ext \
    slack \
    prometheus \
    timestamper \
    ws-cleanup \
    build-timeout \
    ansicolor

# JCasC fora de /var/jenkins_home: bind mount do jenkins_home esconderia o
# arquivo copiado na imagem.
COPY --chown=jenkins:jenkins casc.yaml /opt/jenkins/casc.yaml
ENV CASC_JENKINS_CONFIG=/opt/jenkins/casc.yaml

ENV JAVA_OPTS="-Xms512m -Xmx2g -Djava.awt.headless=true -Djenkins.install.runSetupWizard=false"

EXPOSE 8080 50000

USER jenkins

HEALTHCHECK --interval=60s --timeout=10s --retries=5 --start-period=180s \
  CMD curl -sf http://localhost:8080/login >/dev/null || exit 1

WORKDIR /var/jenkins_home
