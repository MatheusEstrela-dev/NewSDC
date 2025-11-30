#!/bin/bash
# ===== JENKINS HEALTHCHECK SCRIPT =====
# Verifica se o Jenkins está saudável e respondendo

set -e

# Verificar se Jenkins está respondendo
curl -f -s -o /dev/null http://localhost:8080/login || exit 1

# Verificar se há espaço em disco suficiente (mínimo 1GB)
AVAILABLE_SPACE=$(df -BG /var/jenkins_home | tail -1 | awk '{print $4}' | sed 's/G//')
if [ "$AVAILABLE_SPACE" -lt 1 ]; then
    echo "ERROR: Disk space low: ${AVAILABLE_SPACE}GB remaining"
    exit 1
fi

# Verificar memória Java
JAVA_PID=$(pgrep -f "jenkins.war")
if [ -n "$JAVA_PID" ]; then
    # Verificar se o processo está rodando há pelo menos 30 segundos
    UPTIME=$(ps -p "$JAVA_PID" -o etimes= | tr -d ' ')
    if [ "$UPTIME" -lt 30 ]; then
        echo "WARNING: Jenkins recently restarted (uptime: ${UPTIME}s)"
        exit 1
    fi
fi

echo "Jenkins is healthy"
exit 0
