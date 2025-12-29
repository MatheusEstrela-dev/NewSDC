# Dicionário de Pastas e Conceitos do Projeto NewSDC

Este documento detalha o propósito e o conceito por trás de cada diretório principal no projeto NewSDC, servindo como um guia para a navegação e compreensão da estrutura.

---

## 📂 Raiz do Projeto (`NewSDC/`)

A pasta raiz contém a aplicação principal, documentação de alto nível e arquivos de configuração globais.

-   📄 **Arquivos `.md`**: Uma coleção de documentos para planejamento, resumos de decisões de arquitetura e guias rápidos (ex: `GEMINI.md`, `DECRETACOES_MAPEAMENTO_COMPLETO.md`). Funcionam como uma base de conhecimento dinâmica do projeto.
-   📄 **`.gitignore`**: Especifica arquivos e pastas que o Git deve ignorar (ex: `node_modules`, `vendor`, arquivos de ambiente como `.env`).
-   📄 **`justfile` / `Makefile`**: Arquivos de automação de tarefas que fornecem comandos de atalho para operações comuns (ex: iniciar o ambiente Docker, rodar testes, limpar cache). `just` é um "command runner" moderno.
-   📄 **`Jenkinsfile`**: Arquivo de configuração para o pipeline de CI/CD do Jenkins, definindo os estágios de build, teste e deploy.
-   📂 **`.git/`**: Diretório oculto que armazena todos os metadados e objetos do repositório Git. É o coração do controle de versão.
-   📂 **`.claude/`, `.cursor/`**: Diretórios de configuração específicos de IDEs ou editores de código. Contêm configurações locais do ambiente de desenvolvimento.
-   📂 **`.playwright-mcp/`**: Um diretório customizado, provavelmente para armazenar artefatos de testes End-to-End com Playwright, como screenshots de referência (`.png`) para testes de regressão visual.
-   📂 **`Doc/`**: Repositório central para a documentação arquitetural e estratégica do projeto, como diagramas, guias de migração e resumos executivos.
-   📂 **`vars/`**: Provavelmente relacionado ao CI/CD com Jenkins, armazenando scripts reutilizáveis (`.groovy`) ou variáveis de pipeline para processos como Blue/Green Deployment.
-   📂 **`SDC/`**: **O coração do projeto**. É o diretório da aplicação Laravel. Sua estrutura interna é detalhada a seguir.

---

## 🚀 Aplicação Principal (`SDC/`)

Esta pasta contém todo o código-fonte, configurações e dependências da aplicação Laravel/Vue.

### 📁 `app/` - O Código Core da Aplicação
Onde a lógica de negócio e as regras do sistema residem.

-   📁 **`app/Modules/`**: **A implementação central do DDD**. Cada subpasta (ex: `Demandas`, `Rat`, `Pae`) representa um "Bounded Context" (Contexto Delimitado) do negócio, contendo suas próprias camadas `Presentation`, `Application`, `Domain` e `Infrastructure`. Esta é a parte mais importante da organização do código.
-   📁 **`app/Http/`**: Contém elementos relacionados ao protocolo HTTP que são globais ou ainda não foram movidos para um módulo específico, como `Middleware` globais e `Controllers` não pertencentes a um domínio.
-   📁 **`app/Providers/`**: Service Providers do Laravel. Usados para registrar serviços no contêiner de injeção de dependência, configurar listeners de eventos, registrar rotas de módulos, etc.
-   📁 **`app/Models/`**: Contém modelos Eloquent que são globais ou compartilhados por múltiplos módulos (como `User.php`). Em uma arquitetura DDD estrita, as entidades principais residem dentro dos módulos, mas este diretório ainda é útil para modelos transversais.
-   📁 **`app/Console/`**: Definição dos comandos `artisan` customizados para a aplicação.

### 📁 `bootstrap/` - Inicialização
Scripts que inicializam o framework Laravel.

-   📄 **`app.php`**: O ponto de partida que carrega o framework, as configurações e os serviços.
-   📁 **`cache/`**: Armazena os arquivos de cache gerados pelo Laravel para otimizar a performance (ex: cache de rotas, configurações e serviços).

### 📁 `config/` - Configuração
Arquivos de configuração da aplicação. Cada arquivo (`database.php`, `cache.php`, `logging.php`) permite configurar um serviço específico do Laravel.

### 📁 `database/` - Banco de Dados
Tudo relacionado à estrutura e aos dados do banco de dados.

-   📁 **`migrations/`**: Arquivos de migração que controlam o versionamento do schema do banco de dados.
-   📁 **`seeders/`**: Classes para popular o banco de dados com dados iniciais ou de teste.
-   📁 **`factories/`**: "Fábricas" de modelos, usadas para gerar dados falsos para testes automatizados ou seeding.

### 📁 `docker/` - Ambiente de Desenvolvimento
Configurações do ambiente de contêineres Docker.

-   📄 **`docker-compose.yml`**: Orquestra a criação e a comunicação entre os contêineres do ambiente (ex: `app`, `nginx`, `db`, `redis`).
-   📄 **`Dockerfile.dev`**: "Receita" para construir a imagem Docker do container da aplicação PHP/Laravel para desenvolvimento.
-   📁 **`nginx/`, `mysql/`**: Arquivos de configuração específicos para os contêineres do Nginx e MySQL.

### 📁 `public/` - Raiz Web
O único diretório acessível publicamente pela web.

-   📄 **`index.php`**: O ponto de entrada para todas as requisições HTTP que chegam à aplicação.
-   📄 **`build/`** (gerado): Onde o Vite coloca os assets compilados (CSS, JS) para produção.

### 📁 `resources/` - Código-Fonte Frontend e Views
Assets brutos, não compilados.

-   📁 **`resources/js/`**: Código-fonte JavaScript e Vue.js, organizado com a metodologia Atomic Design e espelhando os módulos do backend.
    -   📁 **`Pages/`**: Componentes de página do Inertia.js.
    -   📁 **`Components/`**: Componentes reutilizáveis (Atoms, Molecules, Organisms).
    -   📁 **`Layouts/`**: Componentes de layout da aplicação.
-   📁 **`resources/css/`**: Arquivos CSS brutos.
-   📁 **`resources/views/`**: Templates Blade. Em um projeto Inertia, geralmente contém apenas o `app.blade.php`, que é o hospedeiro da aplicação Vue.

### 📁 `routes/` - Definição de Rotas
Define todos os endpoints da aplicação.

-   📄 **`web.php`**: Rotas para a interface web (atendidas pelo Inertia).
-   📄 **`api.php`**: Rotas para a API REST (geralmente stateless, com autenticação via token).
-   📁 **`modules/`**: **Boa prática**. Subdiretório que contém os arquivos de rota para cada módulo DDD, mantendo o encapsulamento do domínio.

### 📁 `storage/` - Arquivos Gerados
Armazena arquivos gerados pela aplicação durante sua execução.

-   📁 **`framework/`**: Cache, sessões, etc.
-   📁 **`logs/`**: Arquivos de log da aplicação (ex: `laravel.log`).
-   📁 **`app/`**: Arquivos gerados ou enviados pela aplicação (ex: uploads de usuários, anexos de tarefas).

### 📁 `tests/` - Testes Automatizados
Contém toda a suíte de testes da aplicação.

-   📁 **`Feature/`**: Testes de "feature", que testam uma funcionalidade do ponto de vista do usuário (ex: uma requisição HTTP completa).
-   📁 **`Unit/`**: Testes de "unidade", que testam uma pequena porção isolada de código (uma classe, um método).

### 📁 `vendor/` e `node_modules/`
Diretórios onde as dependências do projeto são instaladas pelo Composer (PHP) e pelo NPM/Bun (JavaScript), respectivamente. Eles são gerenciados pelos arquivos `composer.json` и `package.json` e geralmente são ignorados pelo Git.
