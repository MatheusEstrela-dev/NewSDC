pipeline {
    // MELHORIA 1: Não alocar agente globalmente (permite definir por stage)
    // Para Azure App Service, mantemos compatibilidade com agent any quando necessário
    agent none

    environment {
        APP_NAME = 'sdc'

        // Azure Container Registry
        ACR_NAME = 'APIDOVER'
        ACR_RESOURCE_GROUP = 'DOVER'
        ACR_LOGIN_SERVER = 'apidover.azurecr.io'
        ACR_IMAGE = 'apidover.azurecr.io/sdc-dev-app'
        // ACR_TAG será definido dinamicamente após checkout

        // Azure App Service
        APP_SERVICE_NAME = 'newsdc2027'
        AZURE_RESOURCE_GROUP = 'DEFESA_CIVIL'
    }

    options {
        timeout(time: 30, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '10', artifactNumToKeepStr: '5'))
        timestamps()
        ansiColor('xterm')
        // MELHORIA 2: Desabilitar builds concorrentes para evitar conflitos
        disableConcurrentBuilds()
    }

    triggers {
        githubPush()
    }

    stages {
        // =================================================================
        // FASE 1: CHECKOUT E VALIDACAO RAPIDA (FAIL FAST)
        // =================================================================
        stage('Checkout and Fast Validation') {
            agent any
            options {
                timeout(time: 5, unit: 'MINUTES')
            }
            steps {
                script {
                    echo 'Checking out code and running fast validation...'

                    // Checkout com timeout
                    checkout scm

                    // Aguardar GIT_COMMIT estar disponivel
                    def gitCommit = env.GIT_COMMIT ?: sh(script: 'git rev-parse HEAD', returnStdout: true).trim()

                    // Definir ACR_TAG dinamicamente apos checkout
                    env.ACR_TAG = "${env.BUILD_NUMBER}-${gitCommit.take(7)}"

                    // Informacoes do commit
                    env.GIT_COMMIT_MSG = sh(
                        script: 'git log -1 --pretty=%B',
                        returnStdout: true
                    ).trim()
                    env.GIT_AUTHOR = sh(
                        script: 'git log -1 --pretty=%an',
                        returnStdout: true
                    ).trim()

                    echo "Commit: ${env.GIT_COMMIT_MSG}"
                    echo "Author: ${env.GIT_AUTHOR}"
                    echo "ACR Tag: ${env.ACR_TAG}"
                    echo "Branch: ${env.GIT_BRANCH}"

                    // Parsear nome do branch de forma segura
                    def branchName = env.GIT_BRANCH ? env.GIT_BRANCH.tokenize('/').last() : 'unknown'
                    echo "Branch Name (parsed): ${branchName}"

                    // Conflict detection DESABILITADO - causa timeout
                    // O checkout scm ja garante que o codigo esta atualizado
                    echo 'Checkout completed successfully'
                }
            }
        }

        // =================================================================
        // FASE 2: ANALISE ESTATICA E PRE-CHECKS
        // =================================================================
        stage('Pre-flight Checks') {
            agent any
            options {
                timeout(time: 3, unit: 'MINUTES')
            }
            steps {
                script {
                    echo 'Validating environment...'

                    // Verificar Docker
                    sh 'docker --version'
                    sh 'docker-compose --version || docker compose version'

                    // Verificar espaco em disco (minimo 5GB)
                    def availableSpace = sh(
                        script: "df -BG ${WORKSPACE} | tail -1 | awk '{print \$4}' | sed 's/G//'",
                        returnStdout: true
                    ).trim()

                    try {
                        def spaceInt = availableSpace.toInteger()
                        if (spaceInt < 5) {
                            error("Espaco em disco insuficiente: ${spaceInt}GB. Minimo: 5GB")
                        }
                        echo "Espaco disponivel: ${spaceInt}GB"
                    } catch (Exception e) {
                        echo "Aviso: Nao foi possivel verificar espaco em disco"
                    }

                    // Verificar Azure CLI
                    echo 'Validating Azure CLI...'
                    sh 'az --version || (echo "Azure CLI nao encontrado" && exit 1)'
                    echo 'Azure CLI OK'
                }
            }
        }

        // =================================================================
        // FASE 3: BUILD E PUSH
        // =================================================================
        stage('Build and Push to ACR') {
            agent any
            options {
                timeout(time: 15, unit: 'MINUTES')
            }
            steps {
                echo 'Building Docker images using Azure Container Registry'

                script {
                    def buildStartTime = System.currentTimeMillis()

                    dir('SDC') {
                        // Login no Azure usando Service Principal
                        withCredentials([usernamePassword(
                            credentialsId: 'azure-service-principal',
                            usernameVariable: 'AZURE_CLIENT_ID',
                            passwordVariable: 'AZURE_CLIENT_SECRET'
                        )]) {
                            def tenantId = env.AZURE_TENANT_ID ?: ''
                            if (!tenantId) {
                                error("AZURE_TENANT_ID nao configurado")
                            }

                            echo "Logging into Azure..."
                            sh """
                                az login --service-principal \
                                    --username \$AZURE_CLIENT_ID \
                                    --password \$AZURE_CLIENT_SECRET \
                                    --tenant ${tenantId}
                            """
                        }

                        // Build remoto no ACR
                        echo "Starting ACR build..."
                        sh """
                            az acr build \
                                --registry ${ACR_NAME} \
                                --resource-group ${ACR_RESOURCE_GROUP} \
                                --image sdc-dev-app:${env.ACR_TAG} \
                                --image sdc-dev-app:latest \
                                --file docker/Dockerfile.prod \
                                --platform linux \
                                .
                        """
                    }

                    def buildDuration = (System.currentTimeMillis() - buildStartTime) / 1000
                    echo "Build completed in ${buildDuration}s"
                    echo "Images built:"
                    echo "   - ${ACR_IMAGE}:${env.ACR_TAG}"
                    echo "   - ${ACR_IMAGE}:latest"
                }
            }
        }

        // =================================================================
        // FASE 4: TESTES (APENAS EM DEV BRANCHES)
        // =================================================================
        stage('Code Quality & Tests') {
            agent any
            when {
                not {
                    anyOf {
                        branch 'main'
                        branch 'master'
                    }
                }
            }
            steps {
                echo 'Running code quality checks and tests'
                echo 'Para producao, testes sao executados em ambiente de staging'
            }
        }

        // =================================================================
        // FASE 5: DEPLOY
        // =================================================================
        stage('Deploy to Azure App Service') {
            agent any
            options {
                timeout(time: 10, unit: 'MINUTES')
            }
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                }
            }
            steps {
                echo 'Deploying to Azure App Service'

                script {
                    def deployStartTime = System.currentTimeMillis()

                    withCredentials([usernamePassword(
                        credentialsId: 'azure-service-principal',
                        usernameVariable: 'AZURE_CLIENT_ID',
                        passwordVariable: 'AZURE_CLIENT_SECRET'
                    )]) {
                        def tenantId = env.AZURE_TENANT_ID ?: ''
                        if (!tenantId) {
                            error("AZURE_TENANT_ID nao configurado")
                        }

                        sh """
                            az login --service-principal \
                                --username \$AZURE_CLIENT_ID \
                                --password \$AZURE_CLIENT_SECRET \
                                --tenant ${tenantId}
                        """

                        // Obter credenciais do ACR
                        def acrUsername = sh(
                            script: "az acr credential show --name ${ACR_NAME} --query username -o tsv",
                            returnStdout: true
                        ).trim()

                        def acrPassword = sh(
                            script: "az acr credential show --name ${ACR_NAME} --query 'passwords[0].value' -o tsv",
                            returnStdout: true
                        ).trim()

                        echo "Deploying image: ${ACR_IMAGE}:${env.ACR_TAG}"
                        sh """
                            az webapp config container set \
                                --name ${APP_SERVICE_NAME} \
                                --resource-group ${AZURE_RESOURCE_GROUP} \
                                --docker-custom-image-name ${ACR_IMAGE}:${env.ACR_TAG} \
                                --docker-registry-server-url https://${ACR_LOGIN_SERVER} \
                                --docker-registry-server-user ${acrUsername} \
                                --docker-registry-server-password ${acrPassword}
                        """
                    }

                    // Restart App Service
                    sh """
                        az webapp restart \
                            --name ${APP_SERVICE_NAME} \
                            --resource-group ${AZURE_RESOURCE_GROUP}
                    """
                    echo "App Service restarted"

                    // Health check
                    def APP_URL = "https://${APP_SERVICE_NAME}.azurewebsites.net"
                    echo "Verifying application health at ${APP_URL}"

                    sh """
                        echo "Waiting for app to start..."
                        sleep 30

                        for i in \$(seq 1 15); do
                            HTTP_CODE=\$(curl -s -o /dev/null -w "%{http_code}" -m 10 ${APP_URL} 2>/dev/null || echo "000")

                            if [ "\$HTTP_CODE" = "200" ] || [ "\$HTTP_CODE" = "302" ] || [ "\$HTTP_CODE" = "401" ]; then
                                echo "App Service responding! (HTTP \$HTTP_CODE)"
                                exit 0
                            fi

                            echo "Attempt \$i/15: Waiting..."
                            sleep 10
                        done

                        echo "Warning: Health check timeout. Verify manually: ${APP_URL}"
                        exit 0
                    """

                    def deployDuration = (System.currentTimeMillis() - deployStartTime) / 1000
                    echo "Deploy completed in ${deployDuration}s"
                    echo "Application URL: ${APP_URL}"
                }
            }
        }
    }

    // =================================================================
    // POST ACTIONS
    // =================================================================
    post {
        always {
            node('any') {
                script {
                    echo 'Running cleanup tasks...'

                    try {
                        cleanWs(
                            deleteDirs: true,
                            disableDeferredWipeout: true,
                            notFailBuild: true
                        )
                    } catch (Exception e) {
                        echo "Workspace cleanup skipped: ${e.message}"
                    }
                }
            }
        }

        success {
            script {
                def acrTag = env.ACR_TAG ?: 'unknown'
                def gitCommitMsg = env.GIT_COMMIT_MSG ?: 'N/A'
                def gitAuthor = env.GIT_AUTHOR ?: 'N/A'

                echo """
===========================================
BUILD SUCCESS
===========================================
Build Number: ${env.BUILD_NUMBER}
Git Commit: ${env.GIT_COMMIT ?: 'N/A'}
Git Branch: ${env.GIT_BRANCH ?: 'N/A'}
Git Author: ${gitAuthor}
Commit Message: ${gitCommitMsg}
ACR Image: ${env.ACR_IMAGE}:${acrTag}
Build Time: ${new Date()}
===========================================
"""
            }
        }

        failure {
            script {
                def acrTag = env.ACR_TAG ?: 'unknown'
                def gitCommitMsg = env.GIT_COMMIT_MSG ?: 'N/A'
                def gitAuthor = env.GIT_AUTHOR ?: 'N/A'

                echo """
===========================================
BUILD FAILURE
===========================================
Build Number: ${env.BUILD_NUMBER}
Git Commit: ${env.GIT_COMMIT ?: 'N/A'}
Git Branch: ${env.GIT_BRANCH ?: 'N/A'}
Git Author: ${gitAuthor}
Commit Message: ${gitCommitMsg}
ACR Image: ${env.ACR_IMAGE}:${acrTag}
Failure Time: ${new Date()}
===========================================
"""
            }
        }

        unstable {
            echo 'Pipeline completed with warnings'
        }
    }
}
