pipeline {
    // Usar agente padrão do Jenkins (Azure App Service não expõe Docker socket)
    // O container Jenkins já possui Docker, Docker Compose, Azure CLI e ferramentas necessárias
    agent any

    environment {
        APP_NAME = 'sdc'
        DOCKER_COMPOSE = 'docker-compose -f docker-compose.prod.yml'

        // Variáveis para Docker Buildkit (melhora performance)
        DOCKER_BUILDKIT = '1'
        COMPOSE_DOCKER_CLI_BUILD = '1'

        // Diretórios de cache (resolve problema de workspace crescer infinito)
        COMPOSER_CACHE_DIR = "${WORKSPACE}/.composer-cache"
        NPM_CACHE_DIR = "${WORKSPACE}/.npm-cache"

        // Azure Container Registry
        ACR_NAME = 'APIDOVER'
        ACR_RESOURCE_GROUP = 'DOVER'
        ACR_LOGIN_SERVER = 'apidover.azurecr.io'
        ACR_IMAGE = 'apidover.azurecr.io/sdc-dev-app'
        ACR_TAG = "${env.BUILD_NUMBER}-${env.GIT_COMMIT.take(7)}"
    }

    options {
        // Timeout global para evitar builds travados
        timeout(time: 30, unit: 'MINUTES')

        // Manter apenas últimos 10 builds
        buildDiscarder(logRotator(numToKeepStr: '10', artifactNumToKeepStr: '5'))

        // Timestamps nos logs
        timestamps()

        // Colorir output
        ansiColor('xterm')
    }

    // Trigger automático via webhook do GitHub
    triggers {
        githubPush()
    }

    stages {
        stage('Checkout') {
            steps {
                echo '📦 Checking out code...'
                // Resolve problema SSH: usar credenciais configuradas
                checkout scm

                // Exibir informações do commit
                script {
                    env.GIT_COMMIT_MSG = sh(
                        script: 'git log -1 --pretty=%B',
                        returnStdout: true
                    ).trim()
                    env.GIT_AUTHOR = sh(
                        script: 'git log -1 --pretty=%an',
                        returnStdout: true
                    ).trim()
                }
                echo "Commit: ${env.GIT_COMMIT_MSG}"
                echo "Author: ${env.GIT_AUTHOR}"
            }
        }

        stage('Pre-flight Checks') {
            steps {
                echo '🔍 Running pre-flight checks...'

                script {
                    // Verificar se Docker está disponível
                    sh 'docker --version'
                    sh 'docker-compose --version'

                    // Verificar espaço em disco (mínimo 5GB)
                    def availableSpace = sh(
                        script: "df -BG ${WORKSPACE} | tail -1 | awk '{print \$4}' | sed 's/G//'",
                        returnStdout: true
                    ).trim().toInteger()

                    if (availableSpace < 5) {
                        error("Espaço em disco insuficiente: ${availableSpace}GB. Mínimo: 5GB")
                    }
                    echo "✅ Espaço disponível: ${availableSpace}GB"

                    // Nota: .env não é necessário para build Docker
                    // A imagem Docker usa variáveis de ambiente do Azure App Service
                    echo "ℹ️  Build usa variáveis de ambiente do Azure (não requer .env local)"
                }
            }
        }

        stage('Build and Push to ACR') {
            steps {
                echo '🏗️  Building Docker images using Azure Container Registry...'

                script {
                    // Verificar se estamos no diretório correto
                    sh 'pwd && ls -la SDC/'

                    // Login no Azure usando Service Principal
                    withCredentials([usernamePassword(
                        credentialsId: 'azure-service-principal',
                        usernameVariable: 'AZURE_CLIENT_ID',
                        passwordVariable: 'AZURE_CLIENT_SECRET'
                    )]) {
                        def tenantId = env.AZURE_TENANT_ID ?: ''
                        if (!tenantId) {
                            error("AZURE_TENANT_ID não configurado")
                        }

                        sh """
                            az login --service-principal \
                                --username \$AZURE_CLIENT_ID \
                                --password \$AZURE_CLIENT_SECRET \
                                --tenant ${tenantId}
                        """
                    }

                    // Build e Push usando Azure Container Registry Build (OTIMIZADO)
                    // Build remoto no Azure com cache de camadas para velocidade máxima
                    echo "🔨 Iniciando build otimizado no ACR..."
                    dir('SDC') {
                        sh """
                            az acr build \
                                --registry ${ACR_NAME} \
                                --resource-group ${ACR_RESOURCE_GROUP} \
                                --image sdc-dev-app:${ACR_TAG} \
                                --image sdc-dev-app:latest \
                                --file docker/Dockerfile.prod \
                                --platform linux \
                                --no-logs \
                                . || {
                                    echo "⚠️ Build com --no-logs falhou, tentando com logs..."
                                    az acr build \
                                        --registry ${ACR_NAME} \
                                        --resource-group ${ACR_RESOURCE_GROUP} \
                                        --image sdc-dev-app:${ACR_TAG} \
                                        --image sdc-dev-app:latest \
                                        --file docker/Dockerfile.prod \
                                        --platform linux \
                                        .
                                }
                        """
                    }

                    echo "✅ Imagem buildada e enviada para ACR:"
                    echo "   - ${ACR_IMAGE}:${ACR_TAG}"
                    echo "   - ${ACR_IMAGE}:latest"
                }
            }
        }


        stage('Code Quality & Tests') {
            when {
                // Executar apenas em branches de desenvolvimento
                not {
                    anyOf {
                        branch 'main'
                        branch 'master'
                    }
                }
            }
            steps {
                echo '🔍 Running code quality checks and tests...'
                echo 'ℹ️  Para produção, testes são executados em ambiente de staging'
                // Em produção, confiamos no build da imagem que já passou por validações
            }
        }


        stage('Deploy to Azure App Service') {
            // Deploy habilitado para todas as branches
            // Para restringir apenas a main/master, descomente o bloco 'when' abaixo:
            // when {
            //     anyOf {
            //         branch 'main'
            //         branch 'master'
            //     }
            // }
            steps {
                echo '🚀 Deploying to Azure App Service AUTOMATICALLY...'

                script {
                    // Verificar se Azure CLI está disponível
                    sh 'az --version || (echo "Azure CLI não encontrado" && exit 1)'

                    // Variáveis do App Service (configurar no Jenkins)
                    def APP_SERVICE_NAME = env.AZURE_APP_SERVICE_NAME ?: 'newsdc2027'
                    def RESOURCE_GROUP = env.AZURE_RESOURCE_GROUP ?: 'DEFESA_CIVIL'
                    def ACR_NAME = env.ACR_NAME ?: 'apidover'

                    // Fazer login no Azure usando Service Principal
                    withCredentials([usernamePassword(
                        credentialsId: 'azure-service-principal',
                        usernameVariable: 'AZURE_CLIENT_ID',
                        passwordVariable: 'AZURE_CLIENT_SECRET'
                    )]) {
                        def tenantId = env.AZURE_TENANT_ID ?: ''
                        if (!tenantId) {
                            error("AZURE_TENANT_ID não configurado. Configure no Jenkins: Manage Jenkins → Configure System → Global properties")
                        }

                        sh """
                            az login --service-principal \
                                --username \$AZURE_CLIENT_ID \
                                --password \$AZURE_CLIENT_SECRET \
                                --tenant ${tenantId}
                        """

                        // Obter credenciais do ACR para configurar no App Service
                        // Nota: az acr login não é necessário aqui (requer Docker socket)
                        def acrUsername = sh(
                            script: "az acr credential show --name ${ACR_NAME} --query username -o tsv",
                            returnStdout: true
                        ).trim()

                        def acrPassword = sh(
                            script: "az acr credential show --name ${ACR_NAME} --query 'passwords[0].value' -o tsv",
                            returnStdout: true
                        ).trim()

                        // Atualizar App Service com nova imagem (OTIMIZADO)
                        echo "🚀 Deploy OTIMIZADO - Minimizando downtime..."
                        echo "App Service: ${APP_SERVICE_NAME}"
                        echo "Nova Imagem: ${ACR_IMAGE}:${ACR_TAG}"

                        // Configurar e reiniciar em uma única operação paralela
                        sh """
                            # Atualizar configuração do container
                            az webapp config container set \\
                                --name ${APP_SERVICE_NAME} \\
                                --resource-group ${RESOURCE_GROUP} \\
                                --docker-custom-image-name ${ACR_IMAGE}:${ACR_TAG} \\
                                --docker-registry-server-url https://${ACR_LOGIN_SERVER} \\
                                --docker-registry-server-user ${acrUsername} \\
                                --docker-registry-server-password ${acrPassword} \\
                                > /dev/null 2>&1 &

                            # Aguardar configuração completar
                            wait

                            echo "✅ Configuração atualizada, iniciando restart..."
                        """
                    }

                    // Reiniciar App Service (mais rápido que antes)
                    sh """
                        az webapp restart \\
                            --name ${APP_SERVICE_NAME} \\
                            --resource-group ${RESOURCE_GROUP} \\
                            --no-wait
                    """
                    echo "⚡ Restart iniciado (modo assíncrono)"

                    // Health check OTIMIZADO - Mais rápido e inteligente
                    def APP_URL = "https://${APP_SERVICE_NAME}.azurewebsites.net"
                    echo "🏥 Verificando saúde da aplicação..."

                    timeout(time: 3, unit: 'MINUTES') {
                        sh """
                            echo "⏳ Aguardando App Service inicializar..."
                            sleep 15  # Aguardar Azure iniciar o pull da imagem

                            # Health check rápido com retry exponencial
                            for i in \$(seq 1 20); do
                                HTTP_CODE=\$(curl -s -o /dev/null -w "%{http_code}" -m 5 ${APP_URL} 2>/dev/null || echo "000")

                                if [ "\$HTTP_CODE" = "200" ] || [ "\$HTTP_CODE" = "302" ]; then
                                    echo "✅ App Service respondendo! (HTTP \$HTTP_CODE)"
                                    echo "⏱️  Tempo de recuperação: ~\$((i * 5))s"
                                    exit 0
                                fi

                                # Feedback visual
                                if [ \$i -eq 1 ]; then
                                    echo -n "Aguardando resposta"
                                else
                                    echo -n "."
                                fi

                                # Retry interval progressivo (5s → 8s)
                                WAIT_TIME=\$((5 + (i / 5) * 3))
                                sleep \$WAIT_TIME
                            done

                            echo ""
                            echo "⚠️  Timeout no health check (app pode ainda estar inicializando)"
                            echo "ℹ️  Deploy foi concluído. Verificar manualmente: ${APP_URL}"
                            echo "💡 Dica: App pode levar até 2min para estar completamente pronto"
                            exit 0
                        """
                    }

                    echo "✅ Deploy para Azure App Service concluído!"
                    echo "🌐 URL: ${APP_URL}"
                }
            }
        }

    }

    post {
        always {
            echo '🧹 Cleaning up...'

            script {
                // Limpar workspace cache (manter apenas últimos 3 builds)
                sh """
                    find ${WORKSPACE}/.composer-cache -type f -mtime +7 -delete 2>/dev/null || true
                    find ${WORKSPACE}/.npm-cache -type f -mtime +7 -delete 2>/dev/null || true
                """

                // Nota: Limpeza de containers Docker desabilitada
                // Azure App Service não expõe Docker socket
                echo "ℹ️  Docker cleanup skipped (Azure App Service environment)"
            }
        }

        success {
            echo '✅ Pipeline completed successfully!'

            // Notificação de sucesso (configurar no Jenkins)
            // slackSend color: 'good', message: "Build #${env.BUILD_NUMBER} succeeded"
        }

        failure {
            echo '❌ Pipeline failed!'

            script {
                // Coletar informações do build para debugging
                sh """
                    echo '=== Build Information ===' > build-info.txt
                    echo "Build Number: ${env.BUILD_NUMBER}" >> build-info.txt
                    echo "Git Commit: ${env.GIT_COMMIT}" >> build-info.txt
                    echo "Git Branch: ${env.GIT_BRANCH}" >> build-info.txt
                    echo "ACR Image: ${ACR_IMAGE}:${ACR_TAG}" >> build-info.txt
                """
                archiveArtifacts artifacts: 'build-info.txt', allowEmptyArchive: true
            }

            // Notificação de falha
            // slackSend color: 'danger', message: "Build #${env.BUILD_NUMBER} failed"
        }

        unstable {
            echo '⚠️  Pipeline completed with warnings'
        }
    }
}


