import jenkins.model.*
import org.jenkinsci.plugins.docker.commons.credentials.*
import com.cloudbees.plugins.credentials.*
import com.cloudbees.plugins.credentials.domains.*

def instance = Jenkins.getInstance()

println("--> Configuring Docker settings...")

// Configurar Docker Host
def dockerHost = System.getenv('DOCKER_HOST') ?: 'unix:///var/run/docker.sock'
System.setProperty('DOCKER_HOST', dockerHost)

println("--> Docker Host configured: ${dockerHost}")

// Configurar credenciais do Docker Registry (se necessário)
def dockerRegistryUrl = System.getenv('DOCKER_REGISTRY_URL')
def dockerUsername = System.getenv('DOCKER_USERNAME')
def dockerPassword = System.getenv('DOCKER_PASSWORD')

if (dockerRegistryUrl && dockerUsername && dockerPassword) {
    def domain = Domain.global()
    def store = SystemCredentialsProvider.getInstance().getStore()

    def dockerCreds = new UsernamePasswordCredentialsImpl(
        CredentialsScope.GLOBAL,
        "docker-registry-credentials",
        "Docker Registry Credentials",
        dockerUsername,
        dockerPassword
    )

    store.addCredentials(domain, dockerCreds)
    println("--> Docker Registry credentials configured")
}

instance.save()

println("--> Docker configuration completed!")
