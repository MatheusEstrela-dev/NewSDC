/**
 * Shared Jenkins Library Function: Conflict Detection
 * Verifica conflitos no repositório antes do build
 * Segue o princípio DRY (Don't Repeat Yourself)
 */

def call(Map config = [:]) {
    def branchName = config.branchName ?: env.GIT_BRANCH
    def workspace = config.workspace ?: env.WORKSPACE
    def author = config.author ?: env.GIT_AUTHOR

    echo '🔍 Checking for repository conflicts...'

    script {
        def hasConflicts = false
        def conflictLog = ""

        try {
            // Buscar atualizações do repositório remoto
            sh 'git fetch origin'

            // Verificar se branch atual está atrás do remoto
            def localCommit = sh(
                script: 'git rev-parse HEAD',
                returnStdout: true
            ).trim()

            def remoteCommit = sh(
                script: "git rev-parse origin/${branchName}",
                returnStdout: true
            ).trim()

            if (localCommit != remoteCommit) {
                echo "⚠️  Detectados novos commits no repositório remoto"

                // Tentar merge simulado para detectar conflitos
                def mergeTest = sh(
                    script: "git merge-tree \$(git merge-base HEAD origin/${branchName}) HEAD origin/${branchName}",
                    returnStdout: true
                ).trim()

                if (mergeTest.contains('<<<<<<<') || mergeTest.contains('=======') || mergeTest.contains('>>>>>>>')) {
                    hasConflicts = true
                    conflictLog = """
===========================================
⚠️  CONFLITO DETECTADO NO REPOSITÓRIO
===========================================
Branch: ${branchName}
Commit Local: ${localCommit}
Commit Remoto: ${remoteCommit}
Data: ${new Date()}
Author: ${author}

DETALHES DO CONFLITO:
${mergeTest}
===========================================
"""

                    // Gravar log de conflito
                    writeFile file: 'conflict-log.txt', text: conflictLog
                    archiveArtifacts artifacts: 'conflict-log.txt', allowEmptyArchive: false

                    echo conflictLog
                    error("❌ CONFLITO DETECTADO: Build interrompido para evitar deploy com conflitos. Resolva os conflitos manualmente antes de fazer deploy.")
                } else {
                    echo "✅ Nenhum conflito detectado - merge seria limpo"
                }
            } else {
                echo "✅ Branch local está sincronizado com remoto"
            }

        } catch (Exception e) {
            echo "⚠️  Erro ao verificar conflitos: ${e.message}"
            // Continuar o build mesmo se a verificação falhar
            // Em produção, você pode querer ser mais restritivo
            if (config.strictMode == true) {
                throw e
            }
        }
    }
}
