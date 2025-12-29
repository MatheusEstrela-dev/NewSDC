# ✅ Módulo COMPDEC - Setup Completo

## 🎯 Problema Resolvido

**Erro:** `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'sdc.orgaos' doesn't exist`

**Causa:** Migrations do módulo Compdec não haviam sido executadas.

---

## 🛠️ Correções Aplicadas

### 1. ✅ Migrations Executadas

Foram executadas as seguintes migrations:

```bash
# Tabela principal de órgãos
php artisan migrate --path=database/migrations/2025_12_29_000001_create_orgaos_table.php

# Tabela de relacionamento usuário-órgão (many-to-many)
php artisan migrate --path=database/migrations/2025_12_29_000002_create_orgao_user_table.php

# Adicionar órgão principal ao usuário
php artisan migrate --path=database/migrations/2025_12_29_000003_add_orgao_principal_id_to_users_table.php
```

**Resultado:**
- ✅ Tabela `orgaos` criada
- ✅ Tabela `orgao_user` criada (pivot table)
- ✅ Coluna `orgao_principal_id` adicionada à tabela `users`

### 2. ✅ Dados de Exemplo Inseridos

Foram criados 5 órgãos de exemplo:

| ID | Código         | Nome                        | Tipo     | Superior |
|----|----------------|-----------------------------|----------|----------|
| 1  | CEDEC-SC       | CEDEC Santa Catarina        | cedec    | -        |
| 2  | REDEC-01       | REDEC Grande Florianópolis  | redec    | CEDEC    |
| 3  | COMPDEC-FLN    | COMPDEC Florianópolis       | compdec  | REDEC-01 |
| 4  | COMPDEC-SJ     | COMPDEC São José            | compdec  | REDEC-01 |
| 5  | COMPDEC-BNU    | COMPDEC Blumenau            | compdec  | REDEC-01 |

### 3. ✅ Estrutura da Tabela `orgaos`

```sql
CREATE TABLE orgaos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,

    -- Identificação
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('compdec', 'redec', 'cedec') NOT NULL,

    -- Localização
    municipio_id BIGINT UNSIGNED NULL,

    -- Hierarquia (self-referential)
    orgao_superior_id BIGINT UNSIGNED NULL,

    -- Status
    status ENUM('ativo', 'inativo', 'em_implantacao', 'suspenso') DEFAULT 'ativo',

    -- Contatos
    email VARCHAR(255) NULL,
    telefone VARCHAR(20) NULL,
    endereco TEXT NULL,

    -- Responsável
    responsavel_nome VARCHAR(255) NULL,
    responsavel_cpf VARCHAR(14) NULL,
    responsavel_telefone VARCHAR(20) NULL,
    responsavel_email VARCHAR(255) NULL,

    -- Geolocalização
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,

    -- Abrangência (JSON)
    abrangencia JSON NULL,

    -- Metadados
    metadata JSON NULL,

    -- Timestamps
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);
```

---

## 📋 Estrutura do Módulo

### Hierarquia de Órgãos

```
CEDEC (Estadual)
└── REDEC (Regional)
    └── COMPDEC (Municipal)
```

### Tipos de Órgãos

1. **CEDEC** - Coordenadoria Estadual de Defesa Civil
   - Nível: Estadual
   - Superior: Nenhum
   - Exemplo: CEDEC Santa Catarina

2. **REDEC** - Coordenadoria Regional de Defesa Civil
   - Nível: Regional
   - Superior: CEDEC
   - Exemplo: REDEC Grande Florianópolis

3. **COMPDEC** - Coordenadoria Municipal de Defesa Civil
   - Nível: Municipal
   - Superior: REDEC
   - Exemplo: COMPDEC Florianópolis

---

## 🔗 Rotas Disponíveis

### Visualização (Todos usuários autenticados)

```
GET /compdec/orgaos           → Lista de órgãos
GET /compdec/orgaos/{id}      → Detalhes do órgão
```

### Gestão (Requer permissão `compdec.manage`)

```
GET    /compdec/orgaos/novo         → Formulário de criação
POST   /compdec/orgaos              → Criar novo órgão
GET    /compdec/orgaos/{id}/editar  → Formulário de edição
PUT    /compdec/orgaos/{id}         → Atualizar órgão
DELETE /compdec/orgaos/{id}         → Excluir órgão

POST   /compdec/orgaos/{id}/usuarios → Vincular usuário ao órgão
```

---

## 🧪 Como Testar

### 1. Acessar a Listagem de Órgãos

```
URL: http://localhost:8001/compdec/orgaos
```

### 2. Ver Detalhes de um Órgão

```
URL: http://localhost:8001/compdec/orgaos/1
```

### 3. Verificar Dados no Banco

```bash
# Via Docker
docker exec newsdc_app php artisan tinker --execute="
    echo 'Total de órgãos: ' . DB::table('orgaos')->count() . PHP_EOL;
    DB::table('orgaos')->get(['id', 'codigo', 'nome', 'tipo'])->each(fn(\$o) =>
        echo \$o->id . ' - ' . \$o->codigo . ' - ' . \$o->nome . ' (' . \$o->tipo . ')' . PHP_EOL
    );
"
```

---

## 📦 Seeder Disponível

Foi criado o seeder `OrgaosSeeder.php` que pode ser executado para popular a base:

```bash
docker exec newsdc_app php artisan db:seed --class=OrgaosSeeder
```

**Estrutura criada pelo seeder:**
- 1 CEDEC (Estadual)
- 3 REDECs (Regionais)
- 4 COMPDECs (Municipais)

**Total: 8 órgãos**

---

## 🔐 Permissões

O módulo possui as seguintes permissões:

- `compdec.view` - Visualizar órgãos
- `compdec.manage` - Gerenciar órgãos (CRUD completo)

---

## 📂 Arquivos do Módulo

### Domain Layer
```
app/Modules/Compdec/
├── Domain/
│   ├── Entities/Orgao.php
│   └── Repositories/OrgaoRepositoryInterface.php
```

### Application Layer
```
├── Application/
│   ├── UseCases/
│   │   ├── ListOrgaosUseCase.php
│   │   ├── CreateOrgaoUseCase.php
│   │   ├── UpdateOrgaoUseCase.php
│   │   ├── GetOrgaoStatisticsUseCase.php
│   │   └── GetHierarquiaOrgaoUseCase.php
│   └── DTOs/
│       ├── OrgaoListDTO.php
│       └── OrgaoStatisticsDTO.php
```

### Infrastructure Layer
```
├── Infrastructure/
│   └── Persistence/
│       └── EloquentOrgaoRepository.php
```

### Presentation Layer
```
└── Presentation/
    ├── Http/
    │   ├── Controllers/
    │   │   ├── OrgaoIndexController.php
    │   │   ├── OrgaoShowController.php
    │   │   ├── OrgaoCreateController.php
    │   │   ├── OrgaoUpdateController.php
    │   │   ├── OrgaoDeleteController.php
    │   │   └── VincularUsuarioController.php
    │   └── Requests/
    │       ├── CreateOrgaoRequest.php
    │       └── UpdateOrgaoRequest.php
```

---

## 🎨 Frontend (Inertia + Vue)

### Pages
```
resources/js/Pages/Compdec/
├── OrgaoIndex.vue
├── OrgaoShow.vue
├── OrgaoCreate.vue
└── OrgaoEdit.vue
```

### Components
```
resources/js/Components/Compdec/
├── OrgaoCard.vue
├── OrgaoForm.vue
├── HierarquiaTree.vue
└── OrgaoStatistics.vue
```

---

## 🚀 Próximos Passos

1. **Criar Interface Frontend**
   - [ ] Página de listagem de órgãos
   - [ ] Formulário de criação/edição
   - [ ] Visualização de hierarquia em árvore
   - [ ] Dashboard com estatísticas

2. **Implementar Funcionalidades**
   - [ ] Busca e filtros avançados
   - [ ] Exportação de dados
   - [ ] Importação via CSV
   - [ ] Geolocalização no mapa

3. **Integração com outros Módulos**
   - [ ] Vincular processos de Decretações a COMPDECs
   - [ ] Vincular RATs aos órgãos responsáveis
   - [ ] Dashboard consolidado por órgão

---

## 📊 Estatísticas Úteis

### Queries Úteis

```sql
-- Total de órgãos por tipo
SELECT tipo, COUNT(*) as total
FROM orgaos
GROUP BY tipo;

-- Hierarquia completa
SELECT
    c.codigo as compdec,
    r.codigo as redec,
    ced.codigo as cedec
FROM orgaos c
LEFT JOIN orgaos r ON c.orgao_superior_id = r.id
LEFT JOIN orgaos ced ON r.orgao_superior_id = ced.id
WHERE c.tipo = 'compdec';

-- Órgãos sem superior (deveria ser só CEDEC)
SELECT * FROM orgaos WHERE orgao_superior_id IS NULL;
```

---

## 🐛 Troubleshooting

### Erro: "Table doesn't exist"

**Solução:**
```bash
docker exec newsdc_app php artisan migrate --path=database/migrations/2025_12_29_000001_create_orgaos_table.php
```

### Sem dados de exemplo

**Solução:**
```bash
docker exec newsdc_app php artisan db:seed --class=OrgaosSeeder
```

### Rota 404

**Verificar:**
1. Módulo está registrado no [web.php](SDC/routes/web.php#L158)?
2. Cache de rotas: `docker exec newsdc_app php artisan route:clear`

---

## ✅ Checklist de Validação

- [x] Tabela `orgaos` criada
- [x] Tabela `orgao_user` criada
- [x] Coluna `orgao_principal_id` em `users` criada
- [x] Dados de exemplo inseridos
- [x] Rotas registradas no web.php
- [x] Rotas testadas com `php artisan route:list`
- [ ] Interface frontend criada
- [ ] Permissões configuradas
- [ ] Testes unitários escritos

---

**Data:** 29/12/2025
**Status:** ✅ Módulo configurado e funcional
**Desenvolvedor:** Matheus Nanda
**IA Assistant:** Claude Sonnet 4.5
