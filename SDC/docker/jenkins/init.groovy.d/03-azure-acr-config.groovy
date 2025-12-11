import jenkins.model.*
import com.cloudbees.plugins.credentials.*
import com.cloudbees.plugins.credentials.domains.*
import com.cloudbees.plugins.credentials.impl.*

def instance = Jenkins.getInstance()

println("--> Configuring Azure ACR settings...")

// Configurar credenciais do Azure ACR
def acrUsername = System.getenv('AZURE_ACR_USERNAME')
def acrPassword = System.getenv('AZURE_ACR_PASSWORD')
def acrName = System.getenv('ACR_NAME') ?: 'apidover'

if (acrUsername && acrPassword) {
    def domain = Domain.global()
    def store = SystemCredentialsProvider.getInstance().getStore()

    // Remover credencial existente se houver
    def existingCreds = store.getCredentials(domain).find {
        it.id == 'azure-acr-credentials'
    }
    if (existingCreds) {
        store.removeCredentials(domain, existingCreds)
    }

    // Adicionar credenciais do ACR
    def acrCreds = new UsernamePasswordCredentialsImpl(
        CredentialsScope.GLOBAL,
        "azure-acr-credentials",
        "Azure Container Registry Credentials for ${acrName}",
        acrUsername,
        acrPassword
    )

    store.addCredentials(domain, acrCreds)
    println("--> Azure ACR credentials configured for ${acrName}.azurecr.io")
} else {
    println("--> ⚠️  Azure ACR credentials not found in environment variables")
    println("--> Set AZURE_ACR_USERNAME and AZURE_ACR_PASSWORD to configure ACR access")
}

// Configurar Service Principal do Azure (se disponível)
def azureClientId = System.getenv('AZURE_CLIENT_ID')
def azureClientSecret = System.getenv('AZURE_CLIENT_SECRET')

if (azureClientId && azureClientSecret) {
    def domain = Domain.global()
    def store = SystemCredentialsProvider.getInstance().getStore()

    // Remover credencial existente se houver
    def existingCreds = store.getCredentials(domain).find {
        it.id == 'azure-service-principal'
    }
    if (existingCreds) {
        store.removeCredentials(domain, existingCreds)
    }

    // Adicionar Service Principal
    def spCreds = new UsernamePasswordCredentialsImpl(
        CredentialsScope.GLOBAL,
        "azure-service-principal",
        "Azure Service Principal for Azure CLI",
        azureClientId,
        azureClientSecret
    )

    store.addCredentials(domain, spCreds)
    println("--> Azure Service Principal configured")
} else {
    println("--> ⚠️  Azure Service Principal not found in environment variables")
    println("--> Set AZURE_CLIENT_ID and AZURE_CLIENT_SECRET for Azure CLI authentication")
}

instance.save()

println("--> Azure ACR configuration completed!")




