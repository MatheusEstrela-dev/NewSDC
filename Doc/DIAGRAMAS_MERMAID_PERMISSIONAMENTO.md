# Diagramas Mermaid - Sistema de Permissionamento NewSDC

**Data:** 2025-01-30  
**Status:** SISTEMA ATUAL EM PRODUÇÃO

---

## 1. Diagrama ER - Estrutura de Dados

```mermaid
erDiagram
    USERS ||--o{ ROLE_USER : "tem"
    ROLES ||--o{ ROLE_USER : "pertence a"
    ROLES ||--o{ PERMISSION_ROLE : "possui"
    PERMISSIONS ||--o{ PERMISSION_ROLE : "atribuída a"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "gera"
    
    USERS {
        bigint id PK
        string name
        string email UK
        string cpf UK
        string password
        timestamp email_verified_at
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }
    
    ROLES {
        bigint id PK
        string name UK
        string slug UK
        text description
        boolean is_active
        integer hierarchy_level
        timestamp created_at
        timestamp updated_at
    }
    
    PERMISSIONS {
        bigint id PK
        string name UK
        string slug UK
        text description
        string group
        string module
        boolean is_active
        boolean is_immutable
        timestamp created_at
        timestamp updated_at
    }
    
    ROLE_USER {
        bigint id PK
        bigint user_id FK
        bigint role_id FK
        timestamp created_at
        timestamp updated_at
    }
    
    PERMISSION_ROLE {
        bigint id PK
        bigint role_id FK
        bigint permission_id FK
        timestamp created_at
        timestamp updated_at
    }
    
    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id FK
        string name
        string token UK
        json abilities
        timestamp last_used_at
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }
```

---

## 2. Fluxo de Autorização (Runtime)

```mermaid
flowchart TD
    START([Requisição API]) --> AUTH{Autenticado?<br/>auth:sanctum}
    
    AUTH -->|Não| RETURN_401[Retorna 401<br/>Unauthenticated]
    AUTH -->|Sim| LOAD_USER[Carrega User + Roles + Permissions<br/>do Banco de Dados]
    
    LOAD_USER --> CHECK_MIDDLEWARE{Middleware<br/>permission:*?}
    
    CHECK_MIDDLEWARE -->|Sim| CHECK_PERMISSION{User.hasAnyPermission<br/>permissions?}
    CHECK_MIDDLEWARE -->|Não| ALLOW[Permite Acesso]
    
    CHECK_PERMISSION -->|Sim| ALLOW
    CHECK_PERMISSION -->|Não| RETURN_403[Retorna 403<br/>Forbidden - Insufficient permission]
    
    ALLOW --> EXECUTE[Executa Controller]
    EXECUTE --> RETURN_200[Retorna 200 OK]
    
    RETURN_401 --> END([Fim])
    RETURN_403 --> END
    RETURN_200 --> END
    
    style START fill:#90EE90
    style END fill:#FFB6C1
    style ALLOW fill:#90EE90
    style RETURN_401 fill:#FF6B6B
    style RETURN_403 fill:#FF6B6B
    style RETURN_200 fill:#87CEEB
    style CHECK_PERMISSION fill:#FFD700
```

---

## 3. Hierarquia de Roles e Permissões

```mermaid
graph TB
    subgraph "ROLES (Cargos)"
        SUPER[Super Admin<br/>Nível 0<br/>BYPASS TOTAL]
        ADMIN[Admin<br/>Nível 1<br/>32 permissões]
        MANAGER[Manager<br/>Nível 2<br/>18 permissões]
        ANALYST[Analyst<br/>Nível 3<br/>12 permissões]
        OPERATOR[Operator<br/>Nível 4<br/>8 permissões]
        VIEWER[Viewer<br/>Nível 5<br/>6 permissões]
        USER[User<br/>Nível 6<br/>2 permissões]
    end
    
    subgraph "PERMISSÕES - USERS"
        U1[users.view]
        U2[users.create]
        U3[users.edit]
        U4[users.delete]
    end
    
    subgraph "PERMISSÕES - ROLES"
        R1[roles.view]
        R2[roles.create]
        R3[roles.edit]
        R4[roles.delete]
    end
    
    subgraph "PERMISSÕES - PERMISSIONS"
        P1[permissions.view]
        P2[permissions.manage]
    end
    
    subgraph "PERMISSÕES - PAE"
        PAE1[pae.empreendimentos.view]
        PAE2[pae.empreendimentos.create]
        PAE3[pae.empreendimentos.edit]
        PAE4[pae.empreendimentos.delete]
        PAE5[pae.empreendimentos.approve]
    end
    
    subgraph "PERMISSÕES - RAT"
        RAT1[rat.protocolos.view]
        RAT2[rat.protocolos.create]
        RAT3[rat.protocolos.edit]
        RAT4[rat.protocolos.delete]
        RAT5[rat.protocolos.finalize]
    end
    
    subgraph "PERMISSÕES - BI"
        BI1[bi.dashboards.view]
        BI2[bi.reports.export]
        BI3[bi.dashboards.create]
    end
    
    subgraph "PERMISSÕES - INTEGRATIONS"
        INT1[integrations.view]
        INT2[integrations.create]
        INT3[integrations.edit]
        INT4[integrations.execute]
    end
    
    subgraph "PERMISSÕES - WEBHOOKS"
        WH1[webhooks.send]
        WH2[webhooks.logs.view]
    end
    
    subgraph "PERMISSÕES - SYSTEM"
        SYS1[system.logs.view]
        SYS2[system.cache.clear]
        SYS3[system.settings.manage]
    end
    
    SUPER -.->|BYPASS| U1
    SUPER -.->|BYPASS| PAE1
    SUPER -.->|BYPASS| RAT1
    SUPER -.->|BYPASS| BI1
    
    ADMIN --> U1
    ADMIN --> U2
    ADMIN --> U3
    ADMIN --> U4
    ADMIN --> R1
    ADMIN --> R2
    ADMIN --> R3
    ADMIN --> PAE1
    ADMIN --> PAE2
    ADMIN --> PAE3
    ADMIN --> PAE4
    ADMIN --> PAE5
    ADMIN --> RAT1
    ADMIN --> RAT2
    ADMIN --> RAT3
    ADMIN --> RAT4
    ADMIN --> RAT5
    ADMIN --> BI1
    ADMIN --> BI2
    ADMIN --> BI3
    ADMIN --> INT1
    ADMIN --> INT2
    ADMIN --> INT3
    ADMIN --> INT4
    ADMIN --> WH1
    ADMIN --> WH2
    ADMIN --> SYS1
    ADMIN --> SYS2
    
    MANAGER --> PAE1
    MANAGER --> PAE2
    MANAGER --> PAE3
    MANAGER --> PAE5
    MANAGER --> RAT1
    MANAGER --> RAT2
    MANAGER --> RAT3
    MANAGER --> RAT5
    MANAGER --> BI1
    MANAGER --> BI2
    MANAGER --> INT1
    MANAGER --> INT4
    MANAGER --> WH1
    MANAGER --> WH2
    
    ANALYST --> PAE1
    ANALYST --> PAE2
    ANALYST --> PAE3
    ANALYST --> RAT1
    ANALYST --> RAT2
    ANALYST --> RAT3
    ANALYST --> BI1
    ANALYST --> BI2
    ANALYST --> INT1
    ANALYST --> WH2
    
    OPERATOR --> PAE1
    OPERATOR --> PAE2
    OPERATOR --> RAT1
    OPERATOR --> RAT2
    OPERATOR --> BI1
    
    VIEWER --> PAE1
    VIEWER --> RAT1
    VIEWER --> BI1
    
    USER --> PAE1
    USER --> RAT1
    
    style SUPER fill:#FFD700,stroke:#FF8C00,stroke-width:3px
    style ADMIN fill:#FF6B6B,stroke:#C92A2A,stroke-width:2px
    style MANAGER fill:#4ECDC4,stroke:#087F5B,stroke-width:2px
    style ANALYST fill:#95E1D3,stroke:#0CA678,stroke-width:2px
    style OPERATOR fill:#C7CEEA,stroke:#364FC7,stroke-width:2px
    style VIEWER fill:#FFDAC1,stroke:#FD7E14,stroke-width:2px
    style USER fill:#E0E0E0,stroke:#495057,stroke-width:1px
```

---

## 4. Proteção de Rotas API

```mermaid
graph LR
    subgraph "ROTAS API - MÓDULO PAE"
        PAE1[GET /api/v1/pae/empreendimentos<br/>permission:pae.empreendimentos.view]
        PAE2[GET /api/v1/pae/empreendimentos/{id}<br/>permission:pae.empreendimentos.view]
        PAE3[POST /api/v1/pae/empreendimentos<br/>permission:pae.empreendimentos.create]
        PAE4[PUT /api/v1/pae/empreendimentos/{id}<br/>permission:pae.empreendimentos.edit]
        PAE5[DELETE /api/v1/pae/empreendimentos/{id}<br/>permission:pae.empreendimentos.delete]
        PAE6[POST /api/v1/pae/empreendimentos/{id}/approve<br/>permission:pae.empreendimentos.approve]
    end
    
    subgraph "ROTAS API - MÓDULO RAT"
        RAT1[GET /api/v1/rat/protocolos<br/>permission:rat.protocolos.view]
        RAT2[GET /api/v1/rat/protocolos/{id}<br/>permission:rat.protocolos.view]
        RAT3[POST /api/v1/rat/protocolos<br/>permission:rat.protocolos.create]
        RAT4[PUT /api/v1/rat/protocolos/{id}<br/>permission:rat.protocolos.edit]
        RAT5[DELETE /api/v1/rat/protocolos/{id}<br/>permission:rat.protocolos.delete]
        RAT6[POST /api/v1/rat/protocolos/{id}/finalize<br/>permission:rat.protocolos.finalize]
    end
    
    subgraph "ROTAS API - MÓDULO BI"
        BI1[GET /api/v1/bi/entrada<br/>permission:bi.dashboards.view]
        BI2[GET /api/v1/bi/entrada/{id}<br/>permission:bi.dashboards.view]
    end
    
    subgraph "ROTAS API - INTEGRAÇÕES"
        INT1[GET /api/v1/integracao/rat/{id}/pae<br/>permission:integrations.view]
        INT2[GET /api/v1/integracao/pae/{id}/rat<br/>permission:integrations.view]
        INT3[POST /api/v1/power-bi/token<br/>permission:integrations.create]
        INT4[POST /api/v1/integration/execute<br/>permission:integrations.execute]
    end
    
    subgraph "ROTAS API - WEBHOOKS"
        WH1[POST /api/v1/webhooks/send<br/>permission:webhooks.send]
        WH2[POST /api/v1/webhooks/send-sync<br/>permission:webhooks.send]
        WH3[GET /api/v1/webhooks/logs<br/>permission:webhooks.logs.view]
    end
    
    subgraph "ROTAS API - SYSTEM"
        SYS1[GET /api/v1/logs/recent<br/>permission:system.logs.view]
        SYS2[GET /api/v1/logs/metrics<br/>permission:system.logs.view]
        SYS3[GET /api/v1/logs/errors<br/>permission:system.logs.view]
        SYS4[GET /api/v1/logs/stream<br/>permission:system.logs.view]
    end
    
    AUTH[auth:sanctum<br/>Middleware Global] --> PAE1
    AUTH --> RAT1
    AUTH --> BI1
    AUTH --> INT1
    AUTH --> WH1
    AUTH --> SYS1
    
    style AUTH fill:#FFD700,stroke:#FF8C00,stroke-width:3px
    style PAE1 fill:#87CEEB
    style RAT1 fill:#87CEEB
    style BI1 fill:#87CEEB
    style INT1 fill:#87CEEB
    style WH1 fill:#87CEEB
    style SYS1 fill:#87CEEB
```

---

## 5. Sequência de Verificação de Permissão

```mermaid
sequenceDiagram
    participant Client
    participant Route
    participant Middleware as CheckPermission<br/>Middleware
    participant User as User Model
    participant Role as Role Model
    participant Permission as Permission Model
    participant DB as Database
    
    Client->>Route: GET /api/v1/pae/empreendimentos<br/>Header: Authorization: Bearer {token}
    
    Route->>Middleware: auth:sanctum<br/>Valida token
    Middleware->>User: Carrega user do token
    
    Route->>Middleware: permission:pae.empreendimentos.view
    Middleware->>User: hasAnyPermission(['pae.empreendimentos.view'])
    
    User->>DB: SELECT roles WHERE user_id = ?<br/>AND is_active = true
    DB-->>User: [role_id: 1, role_id: 2]
    
    User->>DB: SELECT permissions WHERE role_id IN (1,2)<br/>AND slug = 'pae.empreendimentos.view'<br/>AND is_active = true
    DB-->>User: [permission_id: 5]
    
    User-->>Middleware: true (tem permissão)
    Middleware-->>Route: Permite acesso
    Route->>Client: 200 OK + dados
```

---

## 6. Arquitetura de Camadas

```mermaid
graph TB
    subgraph "CAMADA 1: AUTENTICAÇÃO"
        A1[Laravel Sanctum]
        A2[Bearer Tokens]
        A3[Token Validation]
    end
    
    subgraph "CAMADA 2: AUTORIZAÇÃO"
        B1[User Model<br/>hasPermission]
        B2[Role Model<br/>hasPermission]
        B3[Permission Model]
    end
    
    subgraph "CAMADA 3: MIDDLEWARE"
        C1[CheckPermission<br/>Middleware]
        C2[CheckRole<br/>Middleware]
        C3[auth:sanctum]
    end
    
    subgraph "CAMADA 4: ROTAS"
        D1[routes/api.php]
        D2[permission:*]
        D3[Controllers]
    end
    
    subgraph "CAMADA 5: BANCO DE DADOS"
        E1[users]
        E2[roles]
        E3[permissions]
        E4[role_user]
        E5[permission_role]
    end
    
    A1 --> B1
    A2 --> B1
    A3 --> C3
    B1 --> C1
    B2 --> C1
    B3 --> C1
    C1 --> D2
    C2 --> D2
    C3 --> D1
    D2 --> D3
    B1 --> E1
    B2 --> E2
    B3 --> E3
    B1 --> E4
    B2 --> E5
    
    style A1 fill:#FFD700
    style B1 fill:#87CEEB
    style C1 fill:#90EE90
    style D1 fill:#FFB6C1
    style E1 fill:#DDA0DD
```

---

**Documento gerado em:** 2025-01-30  
**Baseado em:** Análise do código-fonte do NewSDC/SDC  
**Status:** SISTEMA ATUAL EM PRODUÇÃO

