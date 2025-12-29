Aqui está o arquivo .markdown completo e estruturado. Você pode copiar este conteúdo, salvar como ARCHITECTURE.md ou README_ARCH.md na raiz do seu projeto e usar como a "Bíblia" do desenvolvimento para sua equipe.
Markdown

# 🏛️ Manual de Arquitetura de Software - Projeto ERP/SDC

## 1. Visão Geral
Este projeto utiliza uma **Arquitetura Híbrida (Modular Monolith)**.
O objetivo é equilibrar a agilidade de desenvolvimento do Laravel com a robustez do **Domain-Driven Design (DDD)** e a escalabilidade visual do **Atomic Design**.

### Pilares da Arquitetura
1.  **Backend Híbrido:**
    * **Padrão Laravel (MVC):** Para contextos simples (CRUDs auxiliares, Autenticação).
    * **Modular DDD:** Para o *Core Business* (Regras de negócio complexas).
2.  **Frontend Componentizado:**
    * **Atomic Design:** Biblioteca de componentes visuais agnósticos.
    * **Inertia.js:** Conexão direta entre Backend e Frontend (Monólito Moderno).
3.  **Design Patterns:**
    * Repository Pattern, DTOs (Data Transfer Objects) e Use Cases.

---

## 2. Mapa Completo de Pastas (Blueprint)

Abaixo, a estrutura de diretórios definitiva, mapeando onde cada responsabilidade reside.

```plaintext
PROJETO_RAIZ/
├── app/
│   ├── Http/                               🟢 CAMADA "STANDARD" (Simples & Rápido)
│   │   ├── Controllers/
│   │   │   ├── Auth/                       # Login, Registro, Reset de Senha
│   │   │   ├── Admin/                      # CRUDs simples (ex: Cidades, Logs, Configs)
│   │   │   └── ProfileController.php       # Perfil do Usuário
│   │   ├── Requests/                       # Validações HTTP para rotas simples
│   │   └── Middleware/                     # Segurança global (Auth, ACL)
│   │
│   ├── Models/                             🟢 MODELS GLOBAIS (Eloquent)
│   │   ├── User.php                        # Acessado por todo o sistema
│   │   ├── AuditLog.php                    # Logs de sistema
│   │   └── Estado.php                      # Tabelas auxiliares
│   │
│   ├── Services/                           🛠️ SERVIÇOS COMPARTILHADOS
│   │   ├── UploadService.php               # Upload (S3/Local)
│   │   └── NotificationService.php         # Disparo de e-mails/SMS genérico
│   │
│   └── Modules/                            🧠 CORE BUSINESS (Complexidade DDD)
│       ├── Rat/                            # Exemplo: Módulo de Relatório (RAT)
│       │   ├── Application/                # CAMADA DE APLICAÇÃO (Orquestração)
│       │   │   ├── UseCases/               # Ações Únicas (ex: CreateRatUseCase.php)
│       │   │   └── DTOs/                   # Transporte de dados (ex: RatInputDto.php)
│       │   │
│       │   ├── Domain/                     # CAMADA DE DOMÍNIO (Regras Puras)
│       │   │   ├── Entities/               # Entidades de Negócio (ex: Rat.php)
│       │   │   ├── ValueObjects/           # Objetos de Valor (ex: Coordenada.php)
│       │   │   └── Contracts/              # Interfaces (ex: RatRepositoryInterface.php)
│       │   │
│       │   ├── Infrastructure/             # CAMADA DE INFRAESTRUTURA (Tecnologia)
│       │   │   └── Persistence/            # Implementação do Banco
│       │   │       └── EloquentRatRepository.php
│       │   │
│       │   └── Presentation/               # CAMADA DE APRESENTAÇÃO (Entrada/Saída)
│       │       ├── Http/
│       │       │   ├── Controllers/        # RatController.php (Chama UseCases)
│       │       │   ├── Requests/           # RatFormRequest.php (Validação)
│       │       │   └── Resources/          # RatResource.php (API Json se houver)
│       │       └── routes.php              # Rotas exclusivas do módulo
│       │
│       ├── Compdec/                        # Módulo Compdec (Segue a mesma estrutura)
│       └── Tdap/                           # Módulo Tdap (Segue a mesma estrutura)
│
├── resources/js/                           🔵 FRONTEND (Atomic Design + Inertia)
│   ├── Components/                         🎨 DESIGN SYSTEM (Visual Puro)
│   │   ├── Atoms/                          # Indivisíveis (Button, Input, Icon, Label)
│   │   ├── Molecules/                      # Agrupamentos (SearchBar, FormField, Alert)
│   │   └── Organisms/                      # Complexos (Navbar, Footer, DataTable)
│   │
│   ├── Pages/                              🚀 PÁGINAS (Views do Inertia)
│   │   ├── Auth/                           # Login.vue (Reflete AuthController)
│   │   ├── Dashboard.vue                   # Home
│   │   │
│   │   ├── Rat/                            # Espelho do Módulo RAT
│   │   │   ├── Index.vue                   # Listagem de RATs
│   │   │   ├── Create.vue                  # Tela de Cadastro
│   │   │   └── Partials/                   # Organismos exclusivos do RAT
│   │   │       ├── RatForm.vue             # Formulário usando Atoms/Molecules
│   │   │       └── RatTimeline.vue         # Histórico visual
│   │   │
│   │   └── Compdec/                        # Espelho do Módulo COMPDEC
│   │       └── ...
│   │
│   └── app.js                              # Configuração do Vue/Inertia
│
└── routes/                                 🛣️ ROTAS
    ├── web.php                             # Rotas Globais e Simples
    └── modules/                            # Rotas Importadas dos Módulos
        ├── rat.php
        └── compdec.php

3. Detalhamento do Backend (DDD)

Para os módulos dentro de app/Modules, seguimos estritamente a Clean Architecture:
A. Domain (O Coração)

    O que é: Onde vivem as regras que não dependem de framework.

    Regra: Proibido usar Eloquent Model, DB:: ou Request aqui.

    Conteúdo: Entidades Puras, Value Objects, Exceções de Domínio e Interfaces (Contratos).

B. Application (O Cérebro)

    O que é: Orquestra o fluxo de dados.

    Regra: Recebe um DTO, aplica lógica, chama o Repositório (pela Interface).

    Conteúdo: UseCases (Casos de Uso) e DTOs.

C. Infrastructure (As Ferramentas)

    O que é: Implementação técnica dos contratos do Domínio.

    Conteúdo: Repositórios que usam Eloquent, Clientes de API Externa, Integração com FileSystem.

D. Presentation (A Porta)

    O que é: Ponto de entrada HTTP/Console.

    Regra: Não contém regras de negócio. Apenas valida dados (FormRequest), chama o UseCase e retorna a resposta (Inertia::render).

4. Detalhamento do Frontend (Atomic Design)

A interface é construída como peças de Lego dentro de resources/js/Components:

    Atoms (Átomos):

        Componentes base que não fazem nada sozinhos.

        Exemplos: PrimaryButton.vue, TextInput.vue, Badge.vue.

    Molecules (Moléculas):

        Junção de átomos com uma função específica.

        Exemplos: SearchInput.vue (Input + Ícone de Lupa), UserAvatar.vue (Foto + Nome).

    Organisms (Organismos):

        Seções completas da interface.

        Exemplos: TopNavbar.vue, SideMenu.vue, Footer.vue.

        Nota: Organismos específicos de negócio (ex: RatForm.vue) podem ficar dentro de Pages/Rat/Partials para não poluir a biblioteca global.

    Pages (Páginas):

        Onde o Laravel injeta os dados (Props). Conecta os organismos para formar a tela.

5. Fluxo de Dados (Data Flow)

Como uma requisição trafega pelo sistema (Exemplo: "Criar um RAT"):
Snippet de código

sequenceDiagram
    participant User as Usuário (Browser)
    participant Route as Laravel Route
    participant Controller as RatController (Presentation)
    participant Request as RatFormRequest
    participant UseCase as CreateRatUseCase (Application)
    participant Repo as RatRepository (Infra)
    participant DB as Banco de Dados

    User->>Route: POST /rat/store
    Route->>Controller: Encaminha Requisição
    Controller->>Request: Valida Dados (Rules)
    Request-->>Controller: Dados Válidos (Array)
    Controller->>UseCase: Executa (Passando DTO)
    UseCase->>Repo: save(RatEntity)
    Repo->>DB: Eloquent::create()
    DB-->>Repo: Retorna ID
    Repo-->>UseCase: Retorna Entidade Criada
    UseCase-->>Controller: Sucesso
    Controller-->>User: Redirect (Inertia) -> Index

6. Guia de Decisão (Quando usar o quê?)
Cenário	Onde Implementar?	Arquitetura
Login, Reset de Senha	app/Http/Controllers/Auth	Padrão Laravel
CRUD de Cidades/Estados	app/Http/Controllers/Admin	Padrão Laravel
Novo Relatório RAT (Complexo)	app/Modules/Rat	DDD Completo
Movimentação Financeira	app/Modules/Tdap	DDD Completo
Botão "Salvar" Genérico	js/Components/Atoms	Atomic Design
Formulário do RAT	js/Pages/Rat/Partials	Atomic + DDD
7. Comandos Úteis

    Criar nova estrutura de módulo: (Manual ou via script customizado) mkdir -p app/Modules/NomeModulo/{Application,Domain,Infrastructure,Presentation}

    Rodar testes de um módulo específico: php artisan test app/Modules/Rat

Documento mantido pela equipe de Desenvolvimento.