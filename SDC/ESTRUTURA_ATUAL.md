# Estrutura de Pastas Atual - NewSDC

O projeto utiliza uma **Arquitetura Híbrida**. As funcionalidades de negócio (Módulos) seguem um padrão inspirado em **DDD/Arquitetura Limpa**, enquanto componentes globais seguem o padrão tradicional do **Laravel**.

Abaixo está o mapeamento da lógica atual cruzada com o padrão sugerido:

## 📂 Mapeamento de Camadas

### 1. Módulos de Negócio (Ex: Rat, Pae, Decretacoes)
Localizados em: `app/Modules/[Modulo]/`

| Camada Sugerida | Localização Atual (Modular) | Responsabilidade |
| :--- | :--- | :--- |
| **Controllers** | `Presentation/Http/Controllers/` | Entrada de dados e resposta HTTP. |
| **Requests** | `Presentation/Http/Requests/` | Validação de formulários (FormRequests). |
| **DTOs** | `Application/DTOs/` | Envelope de transferência de dados entre camadas. |
| **Services** | `Application/UseCases/` ou `Services/` | Lógica de negócio e orquestração. |
| **Interfaces** | `Domain/Repositories/` | Contratos (Interfaces) dos repositórios. |
| **Repositories** | `Infrastructure/Persistence/` | Implementação do CRUD (Eloquent). |
| **Models** | `Domain/Entities/` | Entidades de domínio (às vezes mapeadas para `app/Models`). |

### 2. Componentes Globais (Ex: User, Auth, Audit)
Localizados na raiz de: `app/`

| Camada Sugerida | Localização Atual (Global) | Responsabilidade |
| :--- | :--- | :--- |
| **Controllers** | `app/Http/Controllers/` | Controladores gerais (Auth, Search, Profile). |
| **Models** | `app/Models/` | Entidades globais do banco (User, Role, Permission). |
| **Services** | `app/Services/` | Serviços compartilhados (Auth, Cache, Log). |

---

## 🏗️ Visualização da Estrutura Modular (Exemplo: Rat)

```text
app/Modules/Rat/
├── Application/              <-- LÓGICA DE APLICAÇÃO
│   ├── DTOs/                 <-- [DTOs]
│   └── UseCases/             <-- [Services / Casos de Uso]
├── Domain/                   <-- REGRAS DE NEGÓCIO
│   ├── Entities/             <-- [Models / Entidades]
│   ├── Repositories/         <-- [Interfaces] (Contratos)
│   └── ValueObjects/         <-- Tipos de dados complexos
├── Infrastructure/           <-- DETALHES TÉCNICOS
│   └── Persistence/          <-- [Repositories] (Implementação Eloquent)
└── Presentation/             <-- INTERFACE COM O USUÁRIO (Web/API)
    └── Http/
        ├── Controllers/       <-- [Controllers]
        └── Requests/          <-- [Requests] (Validação)
```

## 📝 Observações
- **Modularização:** Cada grande funcionalidade é independente, facilitando a manutenção e testes.
- **Acoplamento:** As camadas internas (`Domain`, `Application`) não dependem de detalhes externos (`Http`, `Persistence`), seguindo princípios **SOLID**.
