# Jenkins - CI/CD Pipeline

## Descrição

Container Jenkins configurado para CI/CD do projeto SDC.

## Acesso

- **URL**: http://localhost:8080
- **Porta**: 8080 (HTTP)
- **Porta**: 50000 (JNLP)

## Primeira Configuração

1. Acesse http://localhost:8080
2. Obtenha a senha inicial:
   ```bash
   docker exec sdc_jenkins cat /var/jenkins_home/secrets/initialAdminPassword
   ```
3. Siga o assistente de instalação
4. Instale os plugins recomendados ou selecione plugins específicos

## Configuração do Pipeline

O projeto já possui um `Jenkinsfile` na raiz do projeto que define o pipeline completo de CI/CD.

### Criar um Job Pipeline

1. No Jenkins, clique em "New Item"
2. Digite um nome para o job (ex: "SDC Pipeline")
3. Selecione "Pipeline"
4. Em "Pipeline Definition", selecione "Pipeline script from SCM"
5. Configure:
   - **SCM**: Git
   - **Repository URL**: URL do seu repositório
   - **Branch**: `*/main` ou `*/develop`
   - **Script Path**: `Jenkinsfile`

## Volumes

- `jenkins_home`: Dados persistentes do Jenkins
- `/var/jenkins_workspace`: Workspace compartilhado com o projeto

## Integração com Docker

O Jenkins tem acesso ao Docker socket do host, permitindo executar comandos Docker dentro dos pipelines.

## Plugins Recomendados

- Docker Pipeline
- Git
- GitHub Integration
- Blue Ocean (interface moderna)
- Pipeline Utility Steps
- AnsiColor

## Comandos Úteis

### Reiniciar Jenkins

```bash
docker restart sdc_jenkins
```

### Ver Logs

```bash
docker logs -f sdc_jenkins
```

### Acessar Shell do Container

```bash
docker exec -it sdc_jenkins bash
```

### Backup da Configuração

```bash
# Copiar configuração do Jenkins
docker cp sdc_jenkins:/var/jenkins_home ./jenkins_backup
```

## Segurança

⚠️ **Importante**: Em produção, configure autenticação adequada e não exponha o Jenkins publicamente sem proteção.

