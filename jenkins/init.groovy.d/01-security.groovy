import jenkins.model.*
import hudson.security.*
import jenkins.security.s2m.AdminWhitelistRule

def instance = Jenkins.getInstance()

println("--> Configuring security settings...")

// Criar usuário admin padrão (MUDAR SENHA EM PRODUÇÃO!)
def hudsonRealm = new HudsonPrivateSecurityRealm(false)
hudsonRealm.createAccount('admin', System.getenv('JENKINS_ADMIN_PASSWORD') ?: 'admin123')
instance.setSecurityRealm(hudsonRealm)

// Configurar autorização baseada em matriz
def strategy = new FullControlOnceLoggedInAuthorizationStrategy()
strategy.setAllowAnonymousRead(false)
instance.setAuthorizationStrategy(strategy)

// Desabilitar CLI over remoting (segurança)
jenkins.CLI.get().setEnabled(false)

// Habilitar CSRF Protection
instance.setCrumbIssuer(new DefaultCrumbIssuer(true))

// Configurar Agent -> Master Security
instance.getInjector().getInstance(AdminWhitelistRule.class).setMasterKillSwitch(false)

instance.save()

println("--> Security configured successfully!")
