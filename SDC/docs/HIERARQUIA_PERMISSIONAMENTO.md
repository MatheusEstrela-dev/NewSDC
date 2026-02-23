# Sistema de Permissionamento Granular - NewSDC

Documentacao completa do sistema de permissionamento baseado em Spatie Laravel Permission com extensoes de hierarquia.

---

## 1. Visao Geral da Arquitetura

```mermaid
graph TB
    subgraph CLIENT["Cliente"]
        BROWSER["Browser"]
        VUE["Vue.js + Inertia"]
    end

    subgraph LARAVEL["Laravel Backend"]
        subgraph MIDDLEWARE_LAYER["Camada de Middleware"]
            AUTH_MW["auth:sanctum"]
            HIERARCHY_MW["hierarchy:user/role"]
            PERMISSION_MW["permission:nome"]
            ROLE_MW["role:nome"]
        end

        subgraph AUTH_LAYER["Camada de Autorizacao"]
            GATES["Gates"]
            POLICIES["Policies"]
            SPATIE["Spatie Permission"]
        end

        subgraph SERVICE_LAYER["Camada de Servicos"]
            HIERARCHY_SVC["HierarchyService"]
            AUTH_SVC["AuthService"]
            TOKEN_SVC["TokenService"]
        end

        subgraph MODEL_LAYER["Camada de Modelos"]
            USER_MODEL["User"]
            ROLE_MODEL["Role"]
            PERM_MODEL["Permission"]
            AUDIT_MODEL["PermissionAuditLog"]
        end
    end

    subgraph DATABASE["Banco de Dados"]
        USERS_TB["users"]
        ROLES_TB["roles"]
        PERMS_TB["permissions"]
        MHR_TB["model_has_roles"]
        MHP_TB["model_has_permissions"]
        RHP_TB["role_has_permissions"]
        AUDIT_TB["permission_audit_log"]
    end

    BROWSER --> VUE
    VUE --> AUTH_MW
    AUTH_MW --> HIERARCHY_MW
    HIERARCHY_MW --> PERMISSION_MW
    PERMISSION_MW --> ROLE_MW
    ROLE_MW --> GATES
    GATES --> POLICIES
    POLICIES --> SPATIE
    SPATIE --> SERVICE_LAYER
    SERVICE_LAYER --> MODEL_LAYER
    MODEL_LAYER --> DATABASE

    style HIERARCHY_MW fill:#3b82f6,color:#fff
    style HIERARCHY_SVC fill:#3b82f6,color:#fff
    style SPATIE fill:#8b5cf6,color:#fff
```

---

## 2. Hierarquia de Cargos (Roles)

### 2.1 Piramide de Niveis

```mermaid
graph TD
    subgraph PYRAMID["Piramide de Poder"]
        L0["SUPER ADMIN<br/>Level 0<br/>Acesso Total + Imutavel"]
        L1["ADMIN<br/>Level 1<br/>Gerenciamento Completo"]
        L2["MANAGER<br/>Level 2<br/>Aprovacao + Gestao"]
        L3["ANALYST<br/>Level 3<br/>Criacao + Edicao"]
        L4["OPERATOR<br/>Level 4<br/>Criacao Basica"]
        L5["VIEWER<br/>Level 5<br/>Somente Leitura"]
        L6["USER<br/>Level 6<br/>Acesso Minimo"]
    end

    L0 --> L1
    L1 --> L2
    L2 --> L3
    L3 --> L4
    L4 --> L5
    L5 --> L6

    style L0 fill:#dc2626,color:#fff,stroke:#991b1b,stroke-width:3px
    style L1 fill:#f59e0b,color:#fff,stroke:#d97706,stroke-width:2px
    style L2 fill:#3b82f6,color:#fff,stroke:#2563eb,stroke-width:2px
    style L3 fill:#10b981,color:#fff,stroke:#059669,stroke-width:2px
    style L4 fill:#8b5cf6,color:#fff,stroke:#7c3aed,stroke-width:2px
    style L5 fill:#6b7280,color:#fff,stroke:#4b5563,stroke-width:2px
    style L6 fill:#374151,color:#fff,stroke:#1f2937,stroke-width:2px
```

### 2.2 Tabela de Cargos

| Level | Slug | Nome | Descricao | Quantidade de Permissoes |
|-------|------|------|-----------|-------------------------|
| 0 | super-admin | Super Admin | Acesso total e irrestrito | TODAS |
| 1 | admin | Administrador | Gerenciamento completo | ~32 |
| 2 | manager | Gestor | Aprovacao e gestao de modulos | ~15 |
| 3 | analyst | Analista | Criacao e edicao de registros | ~10 |
| 4 | operator | Operador | Criacao basica | ~5 |
| 5 | viewer | Visualizador | Somente leitura | ~3 |
| 6 | user | Usuario | Acesso minimo | ~2 |

---

## 3. Sistema de Permissoes

### 3.1 Estrutura de Permissoes por Modulo

```mermaid
graph LR
    subgraph USERS["Modulo: Users"]
        U1["users.view"]
        U2["users.create"]
        U3["users.edit"]
        U4["users.delete"]
    end

    subgraph ROLES["Modulo: Roles"]
        R1["roles.view"]
        R2["roles.create"]
        R3["roles.edit"]
        R4["roles.delete"]
    end

    subgraph PERMISSIONS["Modulo: Permissions"]
        P1["permissions.view"]
        P2["permissions.manage"]
    end

    subgraph PAE["Modulo: PAE"]
        PAE1["pae.empreendimentos.view"]
        PAE2["pae.empreendimentos.create"]
        PAE3["pae.empreendimentos.edit"]
        PAE4["pae.empreendimentos.delete"]
        PAE5["pae.empreendimentos.approve"]
    end

    subgraph RAT["Modulo: RAT"]
        RAT1["rat.protocolos.view"]
        RAT2["rat.protocolos.create"]
        RAT3["rat.protocolos.edit"]
        RAT4["rat.protocolos.delete"]
        RAT5["rat.protocolos.finalize"]
    end

    subgraph BI["Modulo: BI"]
        BI1["bi.dashboards.view"]
        BI2["bi.reports.export"]
        BI3["bi.dashboards.create"]
    end

    subgraph INTEGRATIONS["Modulo: Integrations"]
        INT1["integrations.view"]
        INT2["integrations.create"]
        INT3["integrations.edit"]
        INT4["integrations.execute"]
    end

    subgraph WEBHOOKS["Modulo: Webhooks"]
        WH1["webhooks.send"]
        WH2["webhooks.logs.view"]
    end

    subgraph SYSTEM["Modulo: System"]
        SYS1["system.logs.view"]
        SYS2["system.cache.clear"]
        SYS3["system.settings.manage"]
    end

    style USERS fill:#3b82f6,color:#fff
    style ROLES fill:#f59e0b,color:#fff
    style PERMISSIONS fill:#dc2626,color:#fff
    style PAE fill:#10b981,color:#fff
    style RAT fill:#8b5cf6,color:#fff
    style BI fill:#ec4899,color:#fff
```

### 3.2 Matriz de Permissoes por Cargo

```mermaid
graph TD
    subgraph MATRIX["Matriz de Acesso"]
        direction LR

        subgraph SA["Super Admin L0"]
            SA_P["32 Permissoes<br/>TODAS"]
        end

        subgraph AD["Admin L1"]
            AD_P["32 Permissoes<br/>users.* roles.* permissions.*<br/>pae.* rat.* bi.* system.*"]
        end

        subgraph MG["Manager L2"]
            MG_P["15 Permissoes<br/>pae.* rat.*<br/>bi.view bi.export<br/>integrations.view/execute"]
        end

        subgraph AN["Analyst L3"]
            AN_P["10 Permissoes<br/>pae.view/create/edit<br/>rat.view/create/edit<br/>bi.view/export"]
        end

        subgraph OP["Operator L4"]
            OP_P["5 Permissoes<br/>pae.view/create<br/>rat.view/create<br/>bi.view"]
        end

        subgraph VW["Viewer L5"]
            VW_P["3 Permissoes<br/>pae.view<br/>rat.view<br/>bi.view"]
        end
    end

    style SA fill:#dc2626,color:#fff
    style AD fill:#f59e0b,color:#fff
    style MG fill:#3b82f6,color:#fff
    style AN fill:#10b981,color:#fff
    style OP fill:#8b5cf6,color:#fff
    style VW fill:#6b7280,color:#fff
```

---

## 4. Fluxo de Verificacao de Acesso

### 4.1 Fluxo Completo de Autorizacao

```mermaid
flowchart TD
    START([HTTP Request]) --> AUTH{Autenticado?}

    AUTH -->|Nao| DENY_401[401 Unauthorized]
    AUTH -->|Sim| LOAD_USER[Carrega Usuario]

    LOAD_USER --> CHECK_ACTIVE{Usuario Ativo?}
    CHECK_ACTIVE -->|Nao| DENY_403_INACTIVE[403 Usuario Inativo]
    CHECK_ACTIVE -->|Sim| CHECK_SUPER{Super Admin?}

    CHECK_SUPER -->|Sim| BYPASS[Bypass Total]
    CHECK_SUPER -->|Nao| ROUTE_TYPE{Tipo de Rota}

    ROUTE_TYPE -->|Usuario| CHECK_USER_HIERARCHY
    ROUTE_TYPE -->|Role| CHECK_ROLE_HIERARCHY
    ROUTE_TYPE -->|Permissao| CHECK_PERMISSION

    subgraph USER_FLOW["Verificacao de Usuario"]
        CHECK_USER_HIERARCHY[Obter Level do Ator]
        GET_TARGET_LEVEL[Obter Level do Alvo]
        COMPARE_USER{Ator.Level < Alvo.Level?}

        CHECK_USER_HIERARCHY --> GET_TARGET_LEVEL
        GET_TARGET_LEVEL --> COMPARE_USER
    end

    subgraph ROLE_FLOW["Verificacao de Role"]
        CHECK_ROLE_HIERARCHY[Obter Roles Solicitadas]
        VALIDATE_ROLES[Validar Cada Role]
        COMPARE_ROLE{Pode Atribuir Todas?}

        CHECK_ROLE_HIERARCHY --> VALIDATE_ROLES
        VALIDATE_ROLES --> COMPARE_ROLE
    end

    subgraph PERM_FLOW["Verificacao de Permissao"]
        CHECK_PERMISSION[Verificar Permissao]
        HAS_DIRECT{Tem Direta?}
        HAS_VIA_ROLE{Tem via Role?}

        CHECK_PERMISSION --> HAS_DIRECT
        HAS_DIRECT -->|Nao| HAS_VIA_ROLE
    end

    COMPARE_USER -->|Sim| CHECK_POLICY
    COMPARE_USER -->|Nao| DENY_HIERARCHY[403 Hierarquia Insuficiente]

    COMPARE_ROLE -->|Sim| CHECK_POLICY
    COMPARE_ROLE -->|Nao| DENY_ROLE[403 Nao Pode Atribuir Role]

    HAS_DIRECT -->|Sim| ALLOW
    HAS_VIA_ROLE -->|Sim| ALLOW
    HAS_VIA_ROLE -->|Nao| DENY_PERM[403 Sem Permissao]

    CHECK_POLICY[Executar Policy] --> POLICY_RESULT{Autorizado?}
    POLICY_RESULT -->|Sim| ALLOW
    POLICY_RESULT -->|Nao| DENY_POLICY[403 Policy Negou]

    BYPASS --> ALLOW[200 OK - Executar Acao]
    ALLOW --> AUDIT[Registrar Auditoria]

    DENY_401 --> LOG_FAIL[Log de Falha]
    DENY_403_INACTIVE --> LOG_FAIL
    DENY_HIERARCHY --> LOG_FAIL
    DENY_ROLE --> LOG_FAIL
    DENY_PERM --> LOG_FAIL
    DENY_POLICY --> LOG_FAIL

    style ALLOW fill:#10b981,color:#fff
    style BYPASS fill:#10b981,color:#fff
    style DENY_401 fill:#ef4444,color:#fff
    style DENY_403_INACTIVE fill:#ef4444,color:#fff
    style DENY_HIERARCHY fill:#ef4444,color:#fff
    style DENY_ROLE fill:#ef4444,color:#fff
    style DENY_PERM fill:#ef4444,color:#fff
    style DENY_POLICY fill:#ef4444,color:#fff
    style CHECK_SUPER fill:#f59e0b,color:#fff
```

### 4.2 Regra de Gerenciamento por Hierarquia

```mermaid
flowchart LR
    subgraph REGRA["Regra Principal de Hierarquia"]
        direction TB
        ATOR["Usuario Logado<br/>(Ator)"]
        ALVO["Usuario Alvo<br/>(Target)"]
        FORMULA["hierarchy_level_ator < hierarchy_level_alvo"]
        RESULT{Resultado}
        PODE["PODE Gerenciar"]
        NAO_PODE["NAO PODE Gerenciar"]

        ATOR --> FORMULA
        ALVO --> FORMULA
        FORMULA --> RESULT
        RESULT -->|TRUE| PODE
        RESULT -->|FALSE| NAO_PODE
    end

    style PODE fill:#10b981,color:#fff
    style NAO_PODE fill:#ef4444,color:#fff
```

### 4.3 Exemplos de Verificacao

```mermaid
graph LR
    subgraph EXEMPLO1["Exemplo 1: Admin edita Analyst"]
        E1_ADMIN["Admin<br/>Level 1"]
        E1_ANALYST["Analyst<br/>Level 3"]
        E1_CHECK["1 < 3 = TRUE"]
        E1_RESULT["PERMITIDO"]

        E1_ADMIN --> E1_CHECK
        E1_ANALYST --> E1_CHECK
        E1_CHECK --> E1_RESULT
    end

    subgraph EXEMPLO2["Exemplo 2: Manager edita Admin"]
        E2_MANAGER["Manager<br/>Level 2"]
        E2_ADMIN["Admin<br/>Level 1"]
        E2_CHECK["2 < 1 = FALSE"]
        E2_RESULT["NEGADO"]

        E2_MANAGER --> E2_CHECK
        E2_ADMIN --> E2_CHECK
        E2_CHECK --> E2_RESULT
    end

    subgraph EXEMPLO3["Exemplo 3: Analyst edita Analyst"]
        E3_A1["Analyst<br/>Level 3"]
        E3_A2["Analyst<br/>Level 3"]
        E3_CHECK["3 < 3 = FALSE"]
        E3_RESULT["NEGADO"]

        E3_A1 --> E3_CHECK
        E3_A2 --> E3_CHECK
        E3_CHECK --> E3_RESULT
    end

    style E1_RESULT fill:#10b981,color:#fff
    style E2_RESULT fill:#ef4444,color:#fff
    style E3_RESULT fill:#ef4444,color:#fff
```

---

## 5. Estrutura do Banco de Dados

### 5.1 Diagrama ER Completo

```mermaid
erDiagram
    USERS ||--o{ MODEL_HAS_ROLES : "tem_roles"
    USERS ||--o{ MODEL_HAS_PERMISSIONS : "tem_permissoes_diretas"
    USERS ||--o{ PERMISSION_AUDIT_LOG : "gera_logs"
    USERS }o--|| ORGAOS : "pertence_a"

    ROLES ||--o{ MODEL_HAS_ROLES : "atribuida_a"
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : "possui"

    PERMISSIONS ||--o{ MODEL_HAS_PERMISSIONS : "atribuida_diretamente"
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : "atribuida_via_role"

    ORGAOS ||--o{ ORGAO_USER : "usuarios"
    USERS ||--o{ ORGAO_USER : "orgaos"

    USERS {
        bigint id PK
        string name "NOT NULL"
        string email UK "NOT NULL"
        string cpf UK "NULLABLE"
        boolean active "DEFAULT true"
        string password "HASHED"
        bigint orgao_principal_id FK "NULLABLE"
        timestamp email_verified_at "NULLABLE"
        string remember_token "NULLABLE"
        timestamp created_at
        timestamp updated_at
    }

    ROLES {
        bigint id PK
        string name "NOT NULL"
        string guard_name "DEFAULT web"
        string slug UK "NOT NULL"
        integer hierarchy_level "DEFAULT 99"
        text description "NULLABLE"
        boolean is_active "DEFAULT true"
        timestamp created_at
        timestamp updated_at
    }

    PERMISSIONS {
        bigint id PK
        string name "NOT NULL"
        string guard_name "DEFAULT web"
        string slug "NOT NULL"
        text description "NULLABLE"
        string group "DEFAULT general"
        string module "NULLABLE"
        boolean is_active "DEFAULT true"
        boolean is_immutable "DEFAULT false"
        timestamp created_at
        timestamp updated_at
    }

    MODEL_HAS_ROLES {
        bigint role_id FK,PK
        bigint model_id PK
        string model_type PK "App\\Models\\User"
    }

    MODEL_HAS_PERMISSIONS {
        bigint permission_id FK,PK
        bigint model_id PK
        string model_type PK "App\\Models\\User"
    }

    ROLE_HAS_PERMISSIONS {
        bigint permission_id FK,PK
        bigint role_id FK,PK
    }

    PERMISSION_AUDIT_LOG {
        bigint id PK
        bigint user_id FK "NOT NULL"
        string action "NOT NULL (50)"
        string entity_type "NOT NULL (100)"
        bigint entity_id "NULLABLE"
        json before_state "NULLABLE"
        json after_state "NULLABLE"
        string ip_address "NOT NULL (45)"
        text user_agent "NULLABLE"
        timestamp created_at
    }

    ORGAOS {
        bigint id PK
        string codigo UK "NOT NULL"
        string nome "NOT NULL"
        enum tipo "compdec|redec|cedec"
        bigint municipio_id FK "NULLABLE"
        bigint orgao_superior_id FK "NULLABLE"
        enum status "ativo|inativo|em_implantacao|suspenso"
        json abrangencia "NULLABLE"
        json metadata "NULLABLE"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    ORGAO_USER {
        bigint id PK
        bigint orgao_id FK "CASCADE"
        bigint user_id FK "CASCADE"
        string funcao "coordenador|agente|tecnico|apoio"
        boolean is_principal "DEFAULT false"
        timestamp created_at
        timestamp updated_at
    }
```

### 5.2 Indices e Constraints

```mermaid
graph TD
    subgraph INDICES["Indices Importantes"]
        I1["roles.slug - UNIQUE"]
        I2["roles.hierarchy_level - INDEX"]
        I3["permissions.slug - INDEX"]
        I4["permissions.group - INDEX"]
        I5["permissions.module - INDEX"]
        I6["permission_audit_log.user_id - INDEX"]
        I7["permission_audit_log.action - INDEX"]
        I8["permission_audit_log.created_at - INDEX"]
    end

    subgraph CONSTRAINTS["Constraints"]
        C1["users.email - UNIQUE"]
        C2["users.cpf - UNIQUE NULLABLE"]
        C3["roles.name + guard_name - UNIQUE"]
        C4["permissions.name + guard_name - UNIQUE"]
        C5["model_has_roles - PK(role_id, model_id, model_type)"]
        C6["role_has_permissions - PK(permission_id, role_id)"]
    end
```

---

## 6. Arquitetura de Componentes

### 6.1 Backend (Laravel)

```mermaid
graph TB
    subgraph TRAITS["Traits"]
        HAS_HIERARCHY["HasHierarchy.php<br/>Metodos de Hierarquia"]
        HAS_ROLES["HasRoles (Spatie)<br/>Metodos de Roles/Permissions"]
    end

    subgraph CONTRACTS["Contracts/Interfaces"]
        HIERARCHY_INTERFACE["HierarchyServiceInterface"]
    end

    subgraph SERVICES["Services"]
        HIERARCHY_SERVICE["HierarchyService.php<br/>Logica de Verificacao"]
        AUTH_SERVICE["AuthService.php<br/>Autenticacao"]
        TOKEN_SERVICE["TokenService.php<br/>Tokens Sanctum"]
    end

    subgraph MIDDLEWARE["Middleware"]
        CHECK_HIERARCHY["CheckHierarchy.php<br/>Protecao de Rotas"]
        CHECK_USER_ACTIVE["CheckUserActive.php"]
    end

    subgraph POLICIES["Policies"]
        USER_POLICY["UserPolicy.php"]
        ROLE_POLICY["RolePolicy.php"]
        PERMISSION_POLICY["PermissionPolicy.php"]
    end

    subgraph MODELS["Models"]
        USER["User.php"]
        ROLE["Role.php"]
        PERMISSION["Permission.php"]
        AUDIT["PermissionAuditLog.php"]
    end

    subgraph CONTROLLERS["Controllers"]
        USER_MGMT["UserManagementController"]
        ROLE_MGMT["RoleManagementController"]
        PERM_MGMT["PermissionManagementController"]
    end

    HIERARCHY_INTERFACE --> HIERARCHY_SERVICE
    HAS_HIERARCHY --> USER
    HAS_ROLES --> USER
    HIERARCHY_SERVICE --> CHECK_HIERARCHY
    CHECK_HIERARCHY --> USER_POLICY
    CHECK_HIERARCHY --> ROLE_POLICY
    USER_POLICY --> USER
    ROLE_POLICY --> ROLE
    PERMISSION_POLICY --> PERMISSION
    USER_MGMT --> USER_POLICY
    ROLE_MGMT --> ROLE_POLICY
    PERM_MGMT --> PERMISSION_POLICY

    style HAS_HIERARCHY fill:#8b5cf6,color:#fff
    style HIERARCHY_SERVICE fill:#3b82f6,color:#fff
    style CHECK_HIERARCHY fill:#f59e0b,color:#fff
```

### 6.2 Frontend (Vue.js)

```mermaid
graph TB
    subgraph COMPOSABLES["Composables"]
        USE_HIERARCHY["useHierarchy.js<br/>Logica de Hierarquia Frontend"]
    end

    subgraph PAGES["Pages"]
        USERS_INDEX["Users/Index.vue"]
        USERS_SHOW["Users/Show.vue"]
        USERS_EDIT["Users/Edit.vue"]
        ROLES_INDEX["Roles/Index.vue"]
        ROLES_EDIT["Roles/Edit.vue"]
        PERMS_INDEX["Permissions/Index.vue"]
    end

    subgraph COMPONENTS["Components"]
        ROLE_CARD["RoleCard.vue"]
        PERM_CHECKBOX["PermissionCheckbox.vue"]
        HIERARCHY_BADGE["HierarchyBadge.vue"]
    end

    USE_HIERARCHY --> USERS_EDIT
    USE_HIERARCHY --> ROLES_EDIT
    ROLE_CARD --> USERS_EDIT
    PERM_CHECKBOX --> USERS_EDIT
    HIERARCHY_BADGE --> ROLE_CARD

    style USE_HIERARCHY fill:#42b883,color:#fff
```

---

## 7. Diagramas de Sequencia

### 7.1 Login e Geracao de Token

```mermaid
sequenceDiagram
    participant C as Cliente
    participant API as API Controller
    participant AS as AuthService
    participant TS as TokenService
    participant U as User Model
    participant DB as Database

    C->>API: POST /api/auth/login {email, password}
    API->>AS: authenticate(email, password)
    AS->>U: findByEmail(email)
    U->>DB: SELECT * FROM users
    DB-->>U: User Record
    U-->>AS: User Instance

    AS->>AS: Hash::check(password)

    alt Senha Invalida
        AS-->>API: throw AuthenticationException
        API-->>C: 401 Unauthorized
    end

    AS->>TS: createTokenForUser(user)
    TS->>U: getAllPermissions()
    U->>DB: Query permissions via roles
    DB-->>U: Permissions
    U-->>TS: Collection<Permission>

    TS->>U: createToken(name, abilities)
    U->>DB: INSERT INTO personal_access_tokens
    DB-->>U: Token Created
    U-->>TS: NewAccessToken

    TS-->>AS: Token + Abilities
    AS-->>API: {user, token, token_type}
    API-->>C: 200 OK {data: {user, token}}
```

### 7.2 Edicao de Usuario com Verificacao de Hierarquia

```mermaid
sequenceDiagram
    participant U as Usuario (Admin L1)
    participant FE as Frontend Vue
    participant MW as Middleware Stack
    participant HS as HierarchyService
    participant UP as UserPolicy
    participant UC as UserController
    participant DB as Database
    participant AL as AuditLog

    U->>FE: Clica em "Editar" (Analyst L3)
    FE->>MW: PUT /admin/permissions/users/5

    Note over MW: 1. auth middleware
    MW->>MW: Verifica Token/Sessao
    Note over MW: 2. hierarchy:user middleware

    MW->>HS: canUserManageTarget(Admin, Analyst)
    HS->>DB: SELECT hierarchy_level FROM roles
    DB-->>HS: Admin=1, Analyst=3
    HS->>HS: 1 < 3 = TRUE
    HS-->>MW: TRUE - Pode Gerenciar

    Note over MW: 3. can:users.edit middleware
    MW->>UP: update(Admin, Analyst)
    UP->>UP: before() - Nao e Super Admin
    UP->>UP: canManage(Analyst) = TRUE
    UP->>UP: can('users.edit') = TRUE
    UP-->>MW: Response::allow()

    MW->>UC: update(Request, User)
    UC->>UC: Valida dados
    UC->>DB: UPDATE users SET name=?, email=?
    DB-->>UC: 1 row affected

    UC->>AL: logAction('user.updated', ...)
    AL->>DB: INSERT INTO permission_audit_log
    DB-->>AL: Log Created

    UC-->>FE: Redirect to show
    FE-->>U: Mensagem de Sucesso
```

### 7.3 Tentativa de Edicao Bloqueada por Hierarquia

```mermaid
sequenceDiagram
    participant U as Usuario (Manager L2)
    participant FE as Frontend Vue
    participant MW as Middleware
    participant HS as HierarchyService
    participant AL as AuditLog

    U->>FE: Tenta editar Admin (L1)
    FE->>MW: PUT /admin/permissions/users/2

    Note over MW: hierarchy:user middleware

    MW->>HS: canUserManageTarget(Manager, Admin)
    HS->>HS: Manager.level=2, Admin.level=1
    HS->>HS: 2 < 1 = FALSE
    HS-->>MW: FALSE - NAO Pode Gerenciar

    MW->>AL: logAction('access.denied', ...)
    MW-->>FE: 403 Forbidden

    FE-->>U: Erro: "Hierarquia insuficiente"
```

---

## 8. Configuracao do Sistema

### 8.1 Arquivo config/permission.php

```mermaid
graph LR
    subgraph CONFIG["config/permission.php"]
        MODELS["models:<br/>permission: App\\Models\\Permission<br/>role: App\\Models\\Role"]
        TABLES["table_names:<br/>roles, permissions,<br/>model_has_permissions,<br/>model_has_roles,<br/>role_has_permissions"]
        CACHE["cache:<br/>expiration: 3600 (1 hora)<br/>key: spatie.permission.cache<br/>store: default"]
        OPTIONS["options:<br/>teams: false<br/>wildcard: false<br/>register_check: true"]
    end

    style MODELS fill:#3b82f6,color:#fff
    style TABLES fill:#10b981,color:#fff
    style CACHE fill:#f59e0b,color:#fff
    style OPTIONS fill:#8b5cf6,color:#fff
```

### 8.2 Registro no AppServiceProvider

```mermaid
graph TD
    subgraph PROVIDER["AppServiceProvider.php"]
        REGISTER["register()"]
        BIND["singleton binding:<br/>HierarchyServiceInterface -> HierarchyService"]
        BOOT["boot()"]
        CONTEXT["Context logging"]
        DB_LISTEN["DB slow query listener"]
        MAIL["Password reset mail"]
    end

    REGISTER --> BIND
    BOOT --> CONTEXT
    BOOT --> DB_LISTEN
    BOOT --> MAIL

    style BIND fill:#3b82f6,color:#fff
```

---

## 9. Sistema de Auditoria

### 9.1 Acoes Registradas

```mermaid
graph TD
    subgraph AUDIT_ACTIONS["Acoes de Auditoria"]
        subgraph ROLE_ACTIONS["Acoes de Role"]
            RA1["role.assigned"]
            RA2["role.removed"]
            RA3["role.created"]
            RA4["role.updated"]
            RA5["role.deleted"]
        end

        subgraph PERM_ACTIONS["Acoes de Permission"]
            PA1["permission.assigned"]
            PA2["permission.removed"]
            PA3["permission.created"]
        end

        subgraph USER_ACTIONS["Acoes de User"]
            UA1["user.created"]
            UA2["user.updated"]
            UA3["user.deleted"]
        end

        subgraph ACCESS_ACTIONS["Acoes de Acesso"]
            AA1["access.denied"]
            AA2["login.success"]
            AA3["login.failed"]
            AA4["logout"]
        end

        subgraph TOKEN_ACTIONS["Acoes de Token"]
            TA1["token.created"]
            TA2["token.revoked"]
        end
    end

    style ROLE_ACTIONS fill:#f59e0b,color:#fff
    style PERM_ACTIONS fill:#dc2626,color:#fff
    style USER_ACTIONS fill:#3b82f6,color:#fff
    style ACCESS_ACTIONS fill:#10b981,color:#fff
    style TOKEN_ACTIONS fill:#8b5cf6,color:#fff
```

### 9.2 Estrutura do Log

```mermaid
graph LR
    subgraph LOG_ENTRY["Registro de Auditoria"]
        FIELDS["user_id: Quem fez<br/>action: O que fez<br/>entity_type: Em que tipo<br/>entity_id: Em qual registro<br/>before_state: Estado anterior (JSON)<br/>after_state: Estado novo (JSON)<br/>ip_address: De onde<br/>user_agent: Com qual browser<br/>created_at: Quando"]
    end

    subgraph IMMUTABILITY["Imutabilidade"]
        NO_UPDATE["UPDATE: Bloqueado"]
        NO_DELETE["DELETE: Bloqueado"]
        ONLY_INSERT["Apenas INSERT permitido"]
    end

    LOG_ENTRY --> IMMUTABILITY

    style IMMUTABILITY fill:#ef4444,color:#fff
```

---

## 10. Resumo dos Arquivos

### 10.1 Arquivos Criados/Modificados

| Tipo | Arquivo | Descricao |
|------|---------|-----------|
| Trait | `app/Traits/HasHierarchy.php` | Metodos de hierarquia no User |
| Interface | `app/Contracts/HierarchyServiceInterface.php` | Contrato do service |
| Service | `app/Services/Auth/HierarchyService.php` | Logica de verificacao |
| Middleware | `app/Http/Middleware/CheckHierarchy.php` | Protecao de rotas |
| Policy | `app/Policies/UserPolicy.php` | Autorizacao de usuarios |
| Policy | `app/Policies/RolePolicy.php` | Autorizacao de roles |
| Model | `app/Models/User.php` | Model com HasHierarchy |
| Model | `app/Models/Role.php` | Extends Spatie Role |
| Model | `app/Models/Permission.php` | Extends Spatie Permission |
| Model | `app/Models/PermissionAuditLog.php` | Log imutavel |
| Composable | `resources/js/Composables/useHierarchy.js` | Logica no frontend |
| Seeder | `database/seeders/RolesAndPermissionsSeeder.php` | Dados iniciais |
| Config | `config/permission.php` | Configuracao Spatie |

### 10.2 Comandos Uteis

```bash
# Rodar seeder de permissoes
php artisan db:seed --class=RolesAndPermissionsSeeder

# Limpar cache de permissoes
php artisan permission:cache-reset

# Limpar todo cache
php artisan cache:clear

# Ver permissoes de um usuario
php artisan user:permissions admin@defesa.mg.gov.br
```

---

## 11. Melhorias Implementadas (2026-02-10)

### 11.1 Soft Deletes em Roles e Permissions

As tabelas `roles` e `permissions` agora suportam soft delete, permitindo manter historico de registros deletados.

```mermaid
graph LR
    subgraph SOFT_DELETE["Soft Delete"]
        ROLE["Role deletada"]
        PERM["Permission deletada"]
        RESTORE["Pode ser restaurada"]
        AUDIT["Mantida no historico"]
    end

    ROLE --> RESTORE
    PERM --> RESTORE
    ROLE --> AUDIT
    PERM --> AUDIT

    style RESTORE fill:#10b981,color:#fff
    style AUDIT fill:#3b82f6,color:#fff
```

### 11.2 TTL para Permissoes Temporarias

Suporte a roles e permissions temporarias com expiracao automatica.

```mermaid
graph TD
    subgraph TTL["Time To Live"]
        ASSIGN["Atribuir Role/Permission"]
        SET_TTL["Definir expires_at"]
        JOB["Job Horario"]
        CLEAN["Remove Expiradas"]
        CACHE["Limpa Cache Spatie"]
    end

    ASSIGN --> SET_TTL
    SET_TTL --> JOB
    JOB --> CLEAN
    CLEAN --> CACHE

    style JOB fill:#f59e0b,color:#fff
    style CLEAN fill:#ef4444,color:#fff
```

**Campos adicionados:**
- `model_has_roles.expires_at` (timestamp, nullable)
- `model_has_permissions.expires_at` (timestamp, nullable)

**Job de limpeza:** `CleanExpiredPermissions` executa a cada hora.

### 11.3 BasePolicy para DRY

Todas as policies agora estendem `BasePolicy`, centralizando:
- Super-admin bypass
- Helpers de hierarquia
- Verificacao de roles/permissions protegidas

```mermaid
graph TD
    subgraph POLICY_HIERARCHY["Hierarquia de Policies"]
        BASE["BasePolicy<br/>(abstrata)"]
        USER["UserPolicy"]
        ROLE["RolePolicy"]
        PERM["PermissionPolicy"]
    end

    BASE --> USER
    BASE --> ROLE
    BASE --> PERM

    style BASE fill:#8b5cf6,color:#fff
```

**Metodos uteis em BasePolicy:**
- `isSuperAdmin(User $user)` - Verifica super-admin
- `canManageTarget(User $actor, User $target)` - Verifica hierarquia
- `canModifyRoleByLevel(User $actor, int $roleLevel)` - Verifica nivel
- `isProtectedRole(string $slug)` - Verifica role protegida
- `isImmutablePermission(string $permission)` - Verifica permissao imutavel
- `denyHierarchy(string $action)` - Response de negacao padronizado
- `checkPermissionOrDeny(User $user, string $permission)` - Verifica e retorna

### 11.4 Event Subscriber para Auditoria

O `PermissionEventSubscriber` registra automaticamente no audit log todas as operacoes de attach/detach de roles e permissions.

```mermaid
graph LR
    subgraph EVENTS["Eventos Spatie"]
        E1["RoleAttached"]
        E2["RoleDetached"]
        E3["PermissionAttached"]
        E4["PermissionDetached"]
    end

    subgraph SUBSCRIBER["PermissionEventSubscriber"]
        H1["handleRoleAttached"]
        H2["handleRoleDetached"]
        H3["handlePermissionAttached"]
        H4["handlePermissionDetached"]
    end

    subgraph AUDIT["PermissionAuditLog"]
        LOG["Registro Imutavel"]
    end

    E1 --> H1
    E2 --> H2
    E3 --> H3
    E4 --> H4

    H1 --> LOG
    H2 --> LOG
    H3 --> LOG
    H4 --> LOG

    style SUBSCRIBER fill:#3b82f6,color:#fff
    style LOG fill:#10b981,color:#fff
```

### 11.5 Melhorias no Audit Log

Novos campos adicionados para compliance:

| Campo | Tipo | Descricao |
|-------|------|-----------|
| `reason` | string(500) | Motivo da alteracao |
| `session_id` | string(100) | ID da sessao do usuario |

### 11.6 CHECK Constraints (MySQL 8+)

Validacoes no nivel do banco de dados:

| Tabela | Constraint | Expressao |
|--------|------------|-----------|
| roles | chk_hierarchy_level | hierarchy_level >= 0 AND hierarchy_level <= 99 |
| roles | chk_roles_is_active | is_active IN (0, 1) |
| permissions | chk_permissions_is_active | is_active IN (0, 1) |
| permissions | chk_is_immutable | is_immutable IN (0, 1) |

### 11.7 Indices Compostos para Performance

```mermaid
graph TD
    subgraph INDEXES["Indices Compostos"]
        I1["roles_guard_hierarchy_active_idx<br/>(guard_name, hierarchy_level, is_active)"]
        I2["permissions_guard_group_active_idx<br/>(guard_name, group, is_active)"]
        I3["permissions_module_active_idx<br/>(module, is_active)"]
        I4["model_has_roles_type_role_idx<br/>(model_type, role_id)"]
    end

    style I1 fill:#3b82f6,color:#fff
    style I2 fill:#10b981,color:#fff
    style I3 fill:#f59e0b,color:#fff
    style I4 fill:#8b5cf6,color:#fff
```

### 11.8 Migration Consolidada

Todas as melhorias estao na migration:
`2026_02_10_000001_enhance_permission_system.php`

**Comandos Just:**

```bash
# Executar migration
just perm-migrate

# Rollback
just perm-rollback

# Limpar cache de permissoes
just perm-cache-clear
```

---

## 12. Arquivos Criados/Modificados (2026-02-10)

| Tipo | Arquivo | Descricao |
|------|---------|-----------|
| Migration | `database/migrations/2026_02_10_000001_enhance_permission_system.php` | Migration consolidada |
| Policy | `app/Policies/BasePolicy.php` | Policy base abstrata |
| Listener | `app/Listeners/PermissionEventSubscriber.php` | Auditoria de eventos |
| Job | `app/Jobs/CleanExpiredPermissions.php` | Limpeza de TTL |
| Model | `app/Models/Role.php` | Adicionado SoftDeletes |
| Model | `app/Models/Permission.php` | Adicionado SoftDeletes |
| Model | `app/Models/PermissionAuditLog.php` | Novos campos |
| Trait | `app/Traits/HasHierarchy.php` | Delega ao HierarchyService |
| Policy | `app/Policies/UserPolicy.php` | Extends BasePolicy |
| Policy | `app/Policies/RolePolicy.php` | Extends BasePolicy |
| Policy | `app/Policies/PermissionPolicy.php` | Extends BasePolicy |
| Provider | `app/Providers/AuthServiceProvider.php` | Removido backdoor CPF |
| Provider | `app/Providers/EventServiceProvider.php` | Registrado subscriber |
| Kernel | `app/Console/Kernel.php` | Agendado job de limpeza |
| Justfile | `justfile` | Comandos perm-migrate/rollback |
