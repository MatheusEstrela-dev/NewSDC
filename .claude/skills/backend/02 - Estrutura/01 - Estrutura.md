# Arquitetura do Backend - NewSDC

Este documento detalha a arquitetura do backend do projeto NewSDC, construído sobre o framework Laravel. Ele é projetado para ser uma referência técnica para desenvolvedores e arquitetos.

---

## 1. 🚀 Stack Tecnológica Principal

O backend utiliza uma stack de componentes modernos e focados em performance:

- **Framework:** Laravel 12
- **Linguagem:** PHP 8.3
- **Servidor de Aplicação:** Pronto para **Laravel Octane**, permitindo a execução com workers de alta performance (RoadRunner/Swoole) para eliminar o overhead do bootstrap do framework a cada requisição.
- **Autenticação:**
    - **Web:** Autenticação baseada em sessão padrão do Laravel.
    - **API:** **Laravel Sanctum** para autenticação baseada em tokens (API tokens e SPAs).
- **Autorização (RBAC):** **Spatie Laravel Permission** para um controle de acesso granular baseado em papéis (roles) e permissões.
- **Banco de Dados:** PostgreSQL, com o uso do **Doctrine/DBAL** para operações avançadas de schema nas migrações.
- **Integração Frontend:** **Inertia.js** (`inertiajs/inertia-laravel`) para servir um SPA Vue.js sem a necessidade de construir uma API REST separada para o frontend principal.

---

## 2. 🏛️ Padrão Arquitetural: Arquitetura em Camadas (DDD/LimpA)

A descoberta mais significativa é que o projeto não segue apenas a estrutura MVC padrão do Laravel. Ele implementa uma **Arquitetura em Camadas** (Layered Architecture), inspirada por conceitos de Domain-Driven Design (DDD) e Arquitetura Limpa.

Isso é evidenciado pela estrutura de diretórios dentro de `SDC/app/`:

- `Domain/`: **O Coração do Negócio.**
    - Contém a lógica de negócio pura, entidades (Models com foco no domínio), e regras que são independentes do framework. Ex: `Domain/User/`.
    - **Objetivo:** Isolar o que a aplicação *faz* de como ela *é apresentada* ou *persistida*.

- `Application/`: **Os Casos de Uso.**
    - Orquestra a lógica do domínio para executar tarefas específicas. Funciona como uma ponte entre a UI (Controllers) e o núcleo de negócio.

- `Infrastructure/`: **A Engrenagem.**
    - Contém as implementações técnicas das interfaces definidas no Domínio e na Aplicação. É aqui que o código "fala" com o mundo exterior.
    - Ex: Repositórios do Eloquent, clientes de APIs externas, implementações de cache.

- `Presentation/`: **A Interface com o Mundo.**
    - A camada mais externa, responsável por receber requisições e retornar respostas.
    - Ex: `Http/Controllers`, Comandos do Artisan (`Console/Commands`), e listeners de eventos.

**Benefícios desta abordagem:**
- **Separação de Preocupações:** A lógica de negócio não está acoplada ao framework.
- **Testabilidade:** O `Domain` pode ser testado de forma isolada, sem a necessidade de simular requisições HTTP ou banco de dados.
- **Manutenibilidade:** As regras de negócio são centralizadas e fáceis de encontrar, e a troca de uma implementação (ex: de Eloquent para um ORM diferente) se torna mais fácil, impactando apenas a camada de `Infrastructure`.

---

## 3. 🚦 Ciclo de Vida da Requisição e Roteamento

O projeto possui duas frentes de entrada bem definidas:

#### **3.1. Roteamento Web (`routes/web.php`)**
- **Propósito:** Servir a aplicação principal (o painel administrativo).
- **Mecanismo:** Usa o middleware `auth` do Laravel (sessões) e retorna respostas `Inertia::render()`.
- **Organização:** As rotas são modularizadas. O arquivo `web.php` delega a carga para arquivos específicos dentro de `routes/modules/` (ex: `decretacoes.php`, `rat.php`), mantendo o código limpo e organizado.

#### **3.2. Roteamento de API (`routes/api.php`)**
- **Propósito:** Expor dados para clientes externos (ex: Power BI), aplicativos móveis, ou até mesmo para o frontend através de chamadas AJAX diretas.
- **Mecanismo:** Usa o middleware `auth:sanctum` e retorna respostas JSON.
- **Características Notáveis:**
    - **Versioning:** Todas as rotas são prefixadas com `/v1/`.
    - **Middleware Customizado:** Utiliza middlewares próprios para lógicas complexas, como `decretacoes.api.auth`, que provavelmente implementa uma estratégia de autenticação dupla (ex: aceitando token Sanctum OU um token customizado do Power BI).
    - **Rate Limiting Avançado:** Aplica diferentes limites de requisição (`api-rate-limiter:pro`, `api-rate-limiter:default`) para diferentes tipos de clientes.

---

## 4. 🧩 Subsistemas e Pacotes Chave

- **Integração com IA (`google-gemini-php/client`):** Há uma integração direta com a API do Gemini do Google, provavelmente orquestrada dentro do `App/Core/IA`, para alimentar as funcionalidades de chat e assistente inteligente.
- **Gestão de Mídia (`spatie/laravel-medialibrary`):** Um pacote robusto para gerenciar uploads de arquivos, associando-os a modelos Eloquent. Essencial para funcionalidades como anexos em relatórios.
- **Documentação de API (`darkaonline/l5-swagger`):** Ferramenta para gerar documentação interativa da API (Swagger/OpenAPI), crucial para que os consumidores da API possam entender e testar os endpoints.
- **Logs Avançados (`rap2hpoutre/laravel-log-viewer`):** Embora a UI seja customizada via Inertia, a base para leitura e gerenciamento de logs é fornecida por este pacote, como visto no `LogViewerController`.
