# Guia Rapido - Justfile Sistema de Permissionamento

**Versao:** 2.0.0
**Data:** 2025-12-23

---

## Comandos Principais

### Setup Inicial Completo

Execute este comando para configurar todo o sistema de permissionamento do zero:

```bash
just permissions-setup
```

Este comando executa automaticamente:
1. Backup do banco atual
2. Migrations do sistema de permissionamento
3. Seed de roles e permissions
4. Limpeza de cache
5. Verificacao de integridade
6. Exibicao de status final

---

## Comandos Individuais

### 1. Migrations

#### Executar apenas migrations de permissionamento
```bash
just migrate-permissions
```

Migrations incluidas:
- `2025_12_10_000001_create_roles_table.php`
- `2025_12_10_000002_create_permissions_table.php`
- `2025_12_10_000003_create_role_user_table.php`
- `2025_12_10_000004_create_permission_role_table.php`
- `2025_12_23_000001_create_permission_audit_log_table.php`

---

### 2. Seeds

#### Popular roles e permissions
```bash
just seed-permissions
```

Popula:
- 7 roles (super-admin, admin, manager, analyst, operator, viewer, user)
- 38 permissions organizadas por modulo
- Associacoes role-permission

---

### 3. Status e Informacoes

#### Ver status completo do sistema
```bash
just permissions-status
```

Exibe:
- Contadores (roles, permissions, users, audit logs)
- Roles por hierarquia
- Permissions por modulo
- Ultimos 10 logs de auditoria

#### Verificar integridade
```bash
just permissions-check
```

Verifica:
- Existencia de todas as tabelas
- Integridade referencial
- Registros orfaos

---

### 4. Consultas Especificas

#### Listar todos os roles
```bash
just permissions-roles
```

#### Listar permissions por modulo
```bash
just permissions-by-module
```

#### Ver permissions de um role especifico
```bash
just permissions-role super-admin
just permissions-role admin
just permissions-role manager
```

#### Ver roles de um usuario especifico
```bash
just permissions-user 1
just permissions-user 123
```

#### Ver permissions de um modulo especifico
```bash
just permissions-module pae
just permissions-module rat
just permissions-module bi
```

---

### 5. Auditoria

#### Ver ultimos logs de auditoria
```bash
just permissions-audit          # Ultimos 10
just permissions-audit 20       # Ultimos 20
just permissions-audit 50       # Ultimos 50
```

#### Ver logs de um usuario especifico
```bash
just permissions-audit-user 1
just permissions-audit-user 123
```

---

### 6. Contadores

#### Ver contadores do sistema
```bash
just permissions-count
```

Exibe:
- Total de Roles ativos
- Total de Permissions ativas
- Total de Users verificados
- Total de Role Assignments
- Total de Audit Logs

---

## Workflow de Implementacao em Producao

### Passo 1: Backup
```bash
just backup "pre-permissions-v2"
```

### Passo 2: Executar Migrations
```bash
ENV=prod just migrate-permissions
```

### Passo 3: Popular Dados
```bash
ENV=prod just seed-permissions
```

### Passo 4: Limpar Cache
```bash
ENV=prod just cache-clear
```

### Passo 5: Verificar
```bash
ENV=prod just permissions-check
ENV=prod just permissions-status
```

---

## Comandos de Manutencao

### Limpar todos os caches
```bash
just cache-clear
```

### Otimizar caches para producao
```bash
just cache-optimize
```

### Ver documentacao
```bash
just permissions-docs
```

---

## Exemplos Praticos

### Exemplo 1: Ver permissions do role "manager"
```bash
just permissions-role manager
```

**Saida esperada:**
```
+----+---------------------------+---------------------------+--------+
| id | Permission                | Slug                      | Modulo |
+----+---------------------------+---------------------------+--------+
| 5  | Approve Empreendimentos   | pae.empreendimentos.approve | pae  |
| 10 | Finalize Protocolos       | rat.protocolos.finalize   | rat    |
| ...
```

### Exemplo 2: Ver roles do usuario ID 1
```bash
just permissions-user 1
```

**Saida esperada:**
```
+----+----------------------+-------------+-------+---------------------+
| id | Role                 | Slug        | Nivel | Atribuido em        |
+----+----------------------+-------------+-------+---------------------+
| 1  | Super Administrador  | super-admin | 0     | 2025-12-23 10:00:00 |
+----+----------------------+-------------+-------+---------------------+
```

### Exemplo 3: Ver todas permissions do modulo PAE
```bash
just permissions-module pae
```

**Saida esperada:**
```
+----+---------------------------+---------------------------+-----------+
| id | Nome                      | Slug                      | Imutavel  |
+----+---------------------------+---------------------------+-----------+
| 1  | View Empreendimentos      | pae.empreendimentos.view  | 0         |
| 2  | Create Empreendimentos    | pae.empreendimentos.create| 0         |
| 3  | Edit Empreendimentos      | pae.empreendimentos.edit  | 0         |
| 4  | Delete Empreendimentos    | pae.empreendimentos.delete| 0         |
| 5  | Approve Empreendimentos   | pae.empreendimentos.approve| 0        |
+----+---------------------------+---------------------------+-----------+
```

### Exemplo 4: Ver ultimos 5 logs de auditoria
```bash
just permissions-audit 5
```

**Saida esperada:**
```
+----+--------------+--------------+-----------+----+--------------+---------------------+
| id | Usuario      | Acao         | Entidade  | ID | IP           | Data/Hora           |
+----+--------------+--------------+-----------+----+--------------+---------------------+
| 10 | Admin User   | role.assigned| User      | 5  | 192.168.1.10 | 2025-12-23 14:30:00 |
| 9  | Admin User   | user.created | User      | 5  | 192.168.1.10 | 2025-12-23 14:29:45 |
| ...
```

---

## Troubleshooting

### Problema: Migration falha com erro "table already exists"

**Solucao:**
```bash
just migrate-status
just rollback
just migrate-permissions
```

### Problema: Seeds retornam erro de duplicacao

**Solucao:**
```bash
just query "DELETE FROM permission_role"
just query "DELETE FROM role_user WHERE role_id IN (SELECT id FROM roles WHERE slug IN ('super-admin', 'admin', 'manager'))"
just query "DELETE FROM permissions WHERE module IS NOT NULL"
just query "DELETE FROM roles WHERE hierarchy_level IS NOT NULL"
just seed-permissions
```

### Problema: Cache desatualizado

**Solucao:**
```bash
just cache-clear
just cache-optimize
```

### Problema: Verificar se migrations foram executadas

**Solucao:**
```bash
just migrate-status | grep permission
just permissions-check
```

---

## Referencia Rapida de Comandos

| Comando | Descricao |
|---------|-----------|
| `just permissions-setup` | Setup completo |
| `just migrate-permissions` | Executar migrations |
| `just seed-permissions` | Popular dados |
| `just permissions-status` | Ver status |
| `just permissions-check` | Verificar integridade |
| `just permissions-roles` | Listar roles |
| `just permissions-by-module` | Listar por modulo |
| `just permissions-audit` | Ver logs |
| `just permissions-role <slug>` | Permissions de role |
| `just permissions-user <id>` | Roles de usuario |
| `just permissions-module <mod>` | Permissions de modulo |
| `just permissions-docs` | Ver documentacao |

---

## Variavel de Ambiente

Para executar em producao, use a variavel `ENV`:

```bash
ENV=prod just permissions-setup
ENV=prod just permissions-status
ENV=prod just migrate-permissions
```

---

## Documentacao Completa

Para documentacao detalhada, consulte:

- **[PERMISSION_SYSTEM_ARCHITECTURE.md](PERMISSION_SYSTEM_ARCHITECTURE.md)** - Arquitetura completa
- **[RESUMO_EXECUTIVO_PERMISSIONAMENTO.md](RESUMO_EXECUTIVO_PERMISSIONAMENTO.md)** - Resumo executivo

---

**Versao:** 2.0.0
**Ultima atualizacao:** 2025-12-23
