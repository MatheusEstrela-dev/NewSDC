# SDC - TABELA DA VERDADE: Sistema de Permissoes

> **Fonte de Verdade:** `config/permissions.php`
> **Gate:** `AuthServiceProvider::Gate::before()` — super-admin bypassa TODAS as permissoes
> **Middleware:** Spatie `can:` via `Illuminate\Auth\Middleware\Authorize`
> **Frontend:** `Sidebar.vue` — `hasPermission()` consulta `usePage().props.auth.user.permissions[]`

---

## 1. HIERARQUIA DE CARGOS (LEVELS)

| Level | Slug          | Nome           | Descricao                                              | Protegido |
|:-----:|:-------------:|:--------------:|:------------------------------------------------------:|:---------:|
| **0** | `super-admin` | Super Admin    | Acesso total e irrestrito — Desenvolvimento/Manutencao | SIM       |
| **1** | `admin`       | Administrador  | Administrador geral do sistema                         |           |
| **2** | `manager`     | Gestor         | Gestor de area — Pode aprovar e gerenciar modulos      |           |
| **3** | `analyst`     | Analista       | Analista tecnico — Pode criar e editar registros       |           |
| **4** | `operator`    | Operador       | Operador de sistema — Visualizar e criar basicos       |           |
| **5** | `viewer`      | Visualizador   | Acesso somente leitura                                 |           |
| **6** | `user`        | Usuario        | Usuario padrao do sistema                              |           |
| 99    | *(padrao)*    | Sem Cargo      | Nivel padrao se nenhum cargo atribuido                 |           |

> **Regra:** Nivel MENOR = autoridade MAIOR. Super-admin (0) comanda todos.
> **Gate::before:** Se `$user->hasRole('super-admin')` retorna `true` para qualquer ability.

---

## 2. TABELA DA VERDADE — MODULO.GRUPO.ACAO

### Legenda: C=Create, V=View, E=Edit, D=Delete, X=Export, A=Approve, F=Finalize, M=Manage, P=Processar

### 2.1 SISTEMA

| Slug                     | Grupo          | Acao     | super-admin | admin | manager | analyst | operator | viewer | user |
|:-------------------------|:---------------|:---------|:-----------:|:-----:|:-------:|:-------:|:--------:|:------:|:----:|
| `users.view`             | Usuarios       | View     | *           | X     |         |         |          |        |      |
| `users.create`           | Usuarios       | Create   | *           | X     |         |         |          |        |      |
| `users.edit`             | Usuarios       | Edit     | *           | X     |         |         |          |        |      |
| `users.delete`           | Usuarios       | Delete   | *           | X     |         |         |          |        |      |
| `roles.view`             | Cargos         | View     | *           | X     |         |         |          |        |      |
| `roles.create`           | Cargos         | Create   | *           | X     |         |         |          |        |      |
| `roles.edit`             | Cargos         | Edit     | *           | X     |         |         |          |        |      |
| `roles.delete`           | Cargos         | Delete   | *           | X     |         |         |          |        |      |
| `permissions.view`       | Permissoes     | View     | *           | X     |         |         |          |        |      |
| `permissions.manage`     | Permissoes     | Manage   | *           | X     |         |         |          |        |      |
| `system.logs.view`       | Configuracoes  | Logs     | *           | X     |         |         |          |        |      |
| `system.cache.clear`     | Configuracoes  | Cache    | *           | X     |         |         |          |        |      |
| `system.settings.manage` | Configuracoes  | Settings | *           | X     |         |         |          |        |      |

### 2.2 PAE

| Slug                          | Grupo            | Acao    | super-admin | admin | manager | analyst | operator | viewer | user |
|:------------------------------|:-----------------|:--------|:-----------:|:-----:|:-------:|:-------:|:--------:|:------:|:----:|
| `pae.empreendimentos.view`    | Empreendimentos  | View    | *           | X     | X       | X       | X        | X      | X    |
| `pae.empreendimentos.create`  | Empreendimentos  | Create  | *           | X     | X       | X       | X        |        |      |
| `pae.empreendimentos.edit`    | Empreendimentos  | Edit    | *           | X     | X       | X       |          |        |      |
| `pae.empreendimentos.delete`  | Empreendimentos  | Delete  | *           | X     |         |         |          |        |      |
| `pae.empreendimentos.approve` | Empreendimentos  | Approve | *           | X     | X       |         |          |        |      |
| `pae.empreendimentos.export`  | Empreendimentos  | Export  | *           | X     | X       |         |          |        |      |
| `pae.protocolos.view`         | Protocolos       | View    | *           | X     | X       | X       | X        | X      | X    |
| `pae.protocolos.create`       | Protocolos       | Create  | *           | X     | X       | X       | X        |        |      |
| `pae.protocolos.edit`         | Protocolos       | Edit    | *           | X     | X       | X       |          |        |      |
| `pae.protocolos.delete`       | Protocolos       | Delete  | *           | X     |         |         |          |        |      |
| `pae.protocolos.export`       | Protocolos       | Export  | *           | X     | X       |         |          |        |      |

### 2.3 RAT

| Slug                       | Grupo      | Acao     | super-admin | admin | manager | analyst | operator | viewer | user |
|:---------------------------|:-----------|:---------|:-----------:|:-----:|:-------:|:-------:|:--------:|:------:|:----:|
| `rat.protocolos.view`      | Protocolos | View     | *           | X     | X       | X       | X        | X      | X    |
| `rat.protocolos.create`    | Protocolos | Create   | *           | X     | X       | X       | X        |        |      |
| `rat.protocolos.edit`      | Protocolos | Edit     | *           | X     | X       | X       |          |        |      |
| `rat.protocolos.delete`    | Protocolos | Delete   | *           | X     |         |         |          |        |      |
| `rat.protocolos.finalize`  | Protocolos | Finalize | *           | X     | X       |         |          |        |      |
| `rat.protocolos.export`    | Protocolos | Export   | *           | X     | X       |         |          |        |      |

### 2.4 DEMANDAS

| Slug                       | Grupo    | Acao   | super-admin | admin | manager | analyst | operator | viewer | user |
|:---------------------------|:---------|:-------|:-----------:|:-----:|:-------:|:-------:|:--------:|:------:|:----:|
| `demandas.chamados.view`   | Chamados | View   | *           | X     | X       | X       | X        | X      | X    |
| `demandas.chamados.create` | Chamados | Create | *           | X     | X       | X       | X        |        | X    |
| `demandas.chamados.edit`   | Chamados | Edit   | *           | X     | X       | X       |          |        |      |
| `demandas.chamados.delete` | Chamados | Delete | *           | X     |         |         |          |        |      |
| `demandas.chamados.export` | Chamados | Export | *           | X     | X       |         |          |        |      |
| `demandas.chamados.manage` | Chamados | Manage | *           | X     | X       |         |          |        |      |

### 2.5 DECRETACOES

| Slug                          | Grupo     | Acao   | super-admin | admin | manager | analyst | operator | viewer | user |
|:------------------------------|:----------|:-------|:-----------:|:-----:|:-------:|:-------:|:--------:|:------:|:----:|
| `decretacoes.processos.view`  | Processos | View   | *           | X     | X       | X       | X        | X      |      |
| `decretacoes.processos.create`| Processos | Create | *           | X     | X       | X       |          |        |      |
| `decretacoes.processos.edit`  | Processos | Edit   | *           | X     | X       | X       |          |        |      |
| `decretacoes.processos.delete`| Processos | Delete | *           | X     |         |         |          |        |      |
| `decretacoes.processos.export`| Processos | Export | *           | X     | X       |         |          |        |      |

### 2.6 AJUDA HUMANITARIA

| Slug                                | Grupo          | Acao   | super-admin | admin | manager | analyst | operator | viewer | user |
|:------------------------------------|:---------------|:-------|:-----------:|:-----:|:-------:|:-------:|:--------:|:------:|:----:|
| `humanitaria.beneficiarios.view`    | Beneficiarios  | View   | *           | X     | X       | X       | X        | X      |      |
| `humanitaria.beneficiarios.create`  | Beneficiarios  | Create | *           | X     | X       | X       | X        |        |      |
| `humanitaria.beneficiarios.edit`    | Beneficiarios  | Edit   | *           | X     | X       | X       |          |        |      |
| `humanitaria.beneficiarios.delete`  | Beneficiarios  | Delete | *           | X     |         |         |          |        |      |
| `humanitaria.beneficiarios.export`  | Beneficiarios  | Export | *           | X     | X       |         |          |        |      |

### 2.7 TDAP

| Slug                           | Grupo           | Acao      | super-admin | admin | manager | analyst | operator | viewer | user |
|:-------------------------------|:----------------|:----------|:-----------:|:-----:|:-------:|:-------:|:--------:|:------:|:----:|
| `tdap.products.view`           | Produtos        | View      | *           | X     | X       | X       | X        | X      |      |
| `tdap.products.create`         | Produtos        | Create    | *           | X     | X       | X       |          |        |      |
| `tdap.products.edit`           | Produtos        | Edit      | *           | X     | X       |         |          |        |      |
| `tdap.products.delete`         | Produtos        | Delete    | *           | X     |         |         |          |        |      |
| `tdap.recebimentos.view`       | Recebimentos    | View      | *           | X     | X       | X       | X        | X      |      |
| `tdap.recebimentos.create`     | Recebimentos    | Create    | *           | X     | X       | X       |          |        |      |
| `tdap.recebimentos.processar`  | Recebimentos    | Processar | *           | X     | X       |         |          |        |      |
| `tdap.movimentacoes.view`      | Movimentacoes   | View      | *           | X     | X       | X       | X        | X      |      |
| `tdap.movimentacoes.create`    | Movimentacoes   | Create    | *           | X     | X       | X       |          |        |      |
| `tdap.admin`                   | Admin           | Admin     | *           | X     |         |         |          |        |      |

### 2.8 TREINAMENTO

| Slug                          | Grupo  | Acao   | super-admin | admin | manager | analyst | operator | viewer | user |
|:------------------------------|:-------|:-------|:-----------:|:-----:|:-------:|:-------:|:--------:|:------:|:----:|
| `treinamento.cursos.view`     | Cursos | View   | *           | X     | X       | X       | X        | X      | X    |
| `treinamento.cursos.create`   | Cursos | Create | *           | X     | X       |         |          |        |      |
| `treinamento.cursos.edit`     | Cursos | Edit   | *           | X     | X       |         |          |        |      |
| `treinamento.cursos.delete`   | Cursos | Delete | *           | X     |         |         |          |        |      |
| `treinamento.cursos.export`   | Cursos | Export | *           | X     | X       |         |          |        |      |

### 2.9 BI / INTEGRACOES

| Slug                     | Grupo      | Acao    | super-admin | admin | manager | analyst | operator | viewer | user |
|:-------------------------|:-----------|:--------|:-----------:|:-----:|:-------:|:-------:|:--------:|:------:|:----:|
| `bi.dashboards.view`     | Dashboards | View    | *           | X     | X       | X       | X        | X      |      |
| `bi.dashboards.create`   | Dashboards | Create  | *           | X     |         |         |          |        |      |
| `bi.reports.export`      | Reports    | Export  | *           | X     | X       | X       |          |        |      |
| `integrations.view`      | APIs       | View    | *           | X     | X       | X       |          |        |      |
| `integrations.create`    | APIs       | Create  | *           | X     |         |         |          |        |      |
| `integrations.edit`      | APIs       | Edit    | *           | X     |         |         |          |        |      |
| `integrations.execute`   | APIs       | Execute | *           | X     | X       |         |          |        |      |
| `webhooks.send`          | Webhooks   | Send    | *           | X     | X       |         |          |        |      |
| `webhooks.logs.view`     | Webhooks   | Logs    | *           | X     | X       | X       |          |        |      |

> `*` = super-admin tem TODAS as permissoes via Gate::before (bypass)
> `X` = Permissao atribuida explicitamente no `role_permissions`

---

## 3. SIDEBAR — MODULO | CAN | V-ELEMENT | QUEM PODE

| # | Secao             | Item               | Rota                                          | Icone      | `v-if` computed       | Permissao Check                                             | Quem ve?                              |
|:-:|:------------------|:-------------------|:----------------------------------------------|:-----------|:----------------------|:------------------------------------------------------------|:--------------------------------------|
| 1 | PRINCIPAL         | Visao Geral        | `route('dashboard')`                          | dashboard  | *(sempre visivel)*    | Nenhum                                                      | **Todos autenticados**                |
| 2 | PRINCIPAL         | RAT                | `ratHref` (computed)                          | document   | `canSeeRat`           | `rat.protocolos.view`                                       | admin, manager, analyst, operator, viewer, user |
| 3 | PRINCIPAL         | DEMANDAS           | `route('demandas.index')`                     | checkbadge | `canSeeDemandas`      | `demandas.chamados.view`                                    | admin, manager, analyst, operator, viewer, user |
| 4 | PRINCIPAL         | PAE                | `paeHref` (computed)                          | document   | `canSeePae`           | `pae.protocolos.view` OU `pae.empreendimentos.view`        | admin, manager, analyst, operator, viewer, user |
| 5 | MODULOS DE GESTAO | Decretacoes        | `route('decretacoes.index')`                  | scale      | `canSeeDecretacoes`   | `decretacoes.processos.view`                                | admin, manager, analyst, operator, viewer |
| 6 | MODULOS DE GESTAO | Ajuda Humanitaria  | `route('ajuda-humanitaria.beneficiarios.index')` | heart   | `canSeeAjudaHumanitaria` | `humanitaria.beneficiarios.view`                         | admin, manager, analyst, operator, viewer |
| 7 | MODULOS DE GESTAO | Orgaos             | `route('compdec.index')`                      | building   | `canSeeOrgaos`        | `users.view` *(temporario)*                                 | admin                                 |
| 8 | MODULOS DE GESTAO | TDAP (submenu)     | `/tdap/*`                                     | folder     | `canSeeTdap`          | `tdap.products.view` OU `tdap.recebimentos.view` OU `tdap.movimentacoes.view` | admin, manager, analyst, operator, viewer |
| 9 | MODULOS DE GESTAO | Treinamento        | `route('treinamentos.index')`                 | academic   | `canSeeTreinamento`   | `treinamento.cursos.view`                                   | admin, manager, analyst, operator, viewer, user |
| 10| MODULOS DE GESTAO | Meteorologia       | `route('inmet.index')`                        | cloud      | `canSeeMeteorologia`  | `true` *(publico)*                                          | **Todos autenticados**                |
| 11| MODULOS DE GESTAO | Vistoria           | `route('dashboard')`                          | book       | `canSeeVistoria`      | `true` *(em desenvolvimento)*                               | **Todos autenticados**                |
| 12| ADMINISTRACAO     | Permissionamento   | `permissionamentoHref` (computed)             | lock       | `canSeeAdmin`         | `users.view` OU `roles.view` OU `permissions.view`         | admin                                 |

> **hasPermission()**: Retorna `true` se o user tem QUALQUER uma das permissoes listadas (OR logic).
> **super-admin**: Sempre retorna `true` (check em `Sidebar.vue` via `user.is_super_admin`).

---

## 4. MODULOS CRUD — ATOMIC DESIGN

### 4.1 DECRETACOES (`/decretacoes`)

| Acao     | Metodo | URI                    | Middleware                         | Controller                    | Status |
|:---------|:------:|:-----------------------|:-----------------------------------|:------------------------------|:------:|
| INDEX    | GET    | `/decretacoes`         | `can:decretacoes.processos.view`   | `ProcessoIndexController`     | OK     |
| SHOW     | GET    | `/decretacoes/{id}`    | `can:decretacoes.processos.view`   | `ProcessoShowController`      | OK     |
| CREATE   | GET    | `/decretacoes/create`  | `can:decretacoes.processos.create` | `ProcessoCreateController`    | OK     |
| STORE    | POST   | `/decretacoes`         | `can:decretacoes.processos.create` | `ProcessoStoreController`     | OK     |
| EDIT     | GET    | `/decretacoes/{id}/edit` | —                                | —                             | TODO   |
| UPDATE   | PUT    | `/decretacoes/{id}`    | —                                  | —                             | TODO   |
| DELETE   | DELETE | `/decretacoes/{id}`    | —                                  | —                             | TODO   |
| EXPORT   | GET    | `/decretacoes/export`  | `can:decretacoes.processos.export` | `ProcessoExportController`    | OK     |

### 4.2 AJUDA HUMANITARIA (`/ajuda-humanitaria/beneficiarios`)

| Acao     | Metodo | URI                                        | Middleware                              | Controller                       | Status |
|:---------|:------:|:-------------------------------------------|:----------------------------------------|:---------------------------------|:------:|
| INDEX    | GET    | `/ajuda-humanitaria/beneficiarios`         | `can:humanitaria.beneficiarios.view`    | `BeneficiarioIndexController`    | OK     |
| SHOW     | GET    | `/ajuda-humanitaria/beneficiarios/{id}`    | `can:humanitaria.beneficiarios.view`    | `BeneficiarioShowController`     | OK     |
| CREATE   | —      | —                                          | —                                       | —                                | TODO   |
| STORE    | POST   | `/ajuda-humanitaria/beneficiarios`         | `can:humanitaria.beneficiarios.create`  | `BeneficiarioStoreController`    | OK     |
| EDIT     | —      | —                                          | —                                       | —                                | TODO   |
| UPDATE   | PUT    | `/ajuda-humanitaria/beneficiarios/{id}`    | `can:humanitaria.beneficiarios.edit`    | `BeneficiarioUpdateController`   | OK     |
| DELETE   | DELETE | `/ajuda-humanitaria/beneficiarios/{id}`    | `can:humanitaria.beneficiarios.delete`  | `BeneficiarioDestroyController`  | OK     |
| EXPORT   | GET    | `/ajuda-humanitaria/beneficiarios/export`  | `can:humanitaria.beneficiarios.export`  | `BeneficiarioExportController`   | OK     |

### 4.3 RAT (`/rat`)

| Acao     | Metodo | URI                | Middleware                       | Controller                      | Status |
|:---------|:------:|:-------------------|:---------------------------------|:--------------------------------|:------:|
| INDEX    | GET    | `/rat`             | `can:rat.protocolos.view`        | `RatIndexController@index`      | OK     |
| SHOW     | GET    | `/rat/{id}`        | `can:rat.protocolos.view`        | Closure (Inertia)               | OK     |
| CREATE   | GET    | `/rat/create`      | `can:rat.protocolos.create`      | Closure (Inertia)               | OK     |
| EDIT     | GET    | `/rat/{id}/edit`   | `can:rat.protocolos.edit`        | Closure (Inertia)               | OK     |
| UPDATE   | PUT    | `/rat/{id}`        | —                                | —                               | TODO   |
| DELETE   | DELETE | `/rat/{id}`        | `can:rat.protocolos.delete`      | `RatIndexController@destroy`    | OK     |
| EXPORT   | GET    | `/rat/export`      | `can:rat.protocolos.export`      | `RatIndexController@export`     | OK     |
| SYNC     | POST   | `/rat/sync`        | *(sem can:)*                     | `RatSyncController@sync`        | OK     |
| JSON     | GET    | `/rat/{id}/json`   | `can:rat.protocolos.view`        | `RatIndexController@showJson`   | OK     |

### 4.4 DEMANDAS (`/demandas` + `/admin/demandas`)

**Portal do Usuario:**

| Acao     | Metodo | URI                            | Middleware                       | Controller                       | Status |
|:---------|:------:|:-------------------------------|:---------------------------------|:---------------------------------|:------:|
| INDEX    | GET    | `/demandas`                    | `can:demandas.chamados.view`     | `DemandasIndexController@index`  | OK     |
| SHOW     | GET    | `/demandas/{id}`               | `can:demandas.chamados.view`     | `TaskShowController@show`        | OK     |
| CREATE   | GET    | `/demandas/nova`               | `can:demandas.chamados.create`   | `TaskCreateController@create`    | OK     |
| STORE    | POST   | `/demandas`                    | `can:demandas.chamados.create`   | `TaskCreateController@store`     | OK     |
| COMMENT  | POST   | `/demandas/{id}/comentarios`   | `can:demandas.chamados.view`     | `TaskShowController@addComment`  | OK     |
| ATTACH   | POST   | `/demandas/{id}/anexos`        | `can:demandas.chamados.view`     | `TaskShowController@addAttachment`| OK    |

**Console Admin:**

| Acao     | Metodo | URI                                 | Middleware                       | Controller                           | Status |
|:---------|:------:|:------------------------------------|:---------------------------------|:-------------------------------------|:------:|
| INDEX    | GET    | `/admin/demandas`                   | `can:demandas.chamados.manage`   | `DemandasIndexController@adminIndex`  | OK     |
| EDIT     | GET    | `/admin/demandas/{id}/editar`       | `can:demandas.chamados.edit`     | `TaskShowController@edit`             | OK     |
| UPDATE   | PUT    | `/admin/demandas/{id}`              | `can:demandas.chamados.edit`     | `TaskShowController@update`           | OK     |
| DELETE   | DELETE | `/admin/demandas/{id}`              | `can:demandas.chamados.delete`   | `TaskShowController@destroy`          | OK     |
| EXPORT   | GET    | `/admin/demandas/export`            | `can:demandas.chamados.export`   | `DemandasIndexController@export`      | OK     |
| ASSIGN   | POST   | `/admin/demandas/{id}/atribuir`     | `can:demandas.chamados.manage`   | `DemandasIndexController@assign`      | OK     |
| STATUS   | POST   | `/admin/demandas/{id}/status`       | `can:demandas.chamados.edit`     | `DemandasIndexController@changeStatus`| OK     |

### 4.5 TDAP (`/tdap`)

| Acao       | Metodo | URI                                            | Middleware                          | Controller                           | Status |
|:-----------|:------:|:-----------------------------------------------|:------------------------------------|:-------------------------------------|:------:|
| DASHBOARD  | GET    | `/tdap`                                        | `can:tdap.products.view`            | `TdapDashboardController@index`      | OK     |
| PRODUTOS   | GET    | `/tdap/produtos`                               | `can:tdap.products.view`            | `TdapProductsController@index`       | OK     |
| PROD.STORE | POST   | `/tdap/produtos`                               | `can:tdap.products.create`          | `TdapProductsController@store`       | OK     |
| ESTOQUE    | GET    | `/tdap/produtos/{product}/estoque`             | `can:tdap.products.view`            | `TdapProductsController@estoque`     | OK     |
| RECEB.LIST | GET    | `/tdap/recebimentos`                           | `can:tdap.recebimentos.view`        | `TdapRecebimentosController@index`   | OK     |
| RECEB.STORE| POST   | `/tdap/recebimentos`                           | `can:tdap.recebimentos.create`      | `TdapRecebimentosController@store`   | OK     |
| RECEB.SHOW | GET    | `/tdap/recebimentos/{id}`                      | `can:tdap.recebimentos.view`        | `TdapRecebimentosController@show`    | OK     |
| PROCESSAR  | POST   | `/tdap/recebimentos/{id}/processar`            | `can:tdap.recebimentos.processar`   | `TdapRecebimentosController@processar`| OK    |
| MOV.LIST   | GET    | `/tdap/movimentacoes`                          | `can:tdap.movimentacoes.view`       | `TdapMovimentacoesController@index`  | OK     |
| MOV.SAIDA  | POST   | `/tdap/movimentacoes/saida`                    | `can:tdap.movimentacoes.create`     | `TdapMovimentacoesController@saida`  | OK     |
| HISTORICO  | GET    | `/tdap/movimentacoes/produto/{product}/historico`| `can:tdap.movimentacoes.view`      | `TdapMovimentacoesController@historico`| OK   |

### 4.6 TREINAMENTO (`/treinamentos`)

| Acao     | Metodo | URI                        | Middleware                         | Controller                        | Status |
|:---------|:------:|:---------------------------|:-----------------------------------|:----------------------------------|:------:|
| INDEX    | GET    | `/treinamentos`            | `can:treinamento.cursos.view`      | `TreinamentoIndexController`      | OK     |
| SHOW     | GET    | `/treinamentos/{id}`       | `can:treinamento.cursos.view`      | `TreinamentoShowController`       | OK     |
| STORE    | POST   | `/treinamentos/admin`      | `can:treinamento.cursos.create`    | `TreinamentoStoreController`      | OK     |
| EXPORT   | GET    | `/treinamentos/export`     | `can:treinamento.cursos.export`    | `TreinamentoExportController`     | OK     |

### 4.7 COMPDEC / ORGAOS (`/compdec/orgaos`)

| Acao     | Metodo | URI                               | Middleware          | Controller                         | Status |
|:---------|:------:|:----------------------------------|:--------------------|:-----------------------------------|:------:|
| INDEX    | GET    | `/compdec/orgaos`                 | *(sem can:)*        | `OrgaoIndexController`             | OK     |
| SHOW     | GET    | `/compdec/orgaos/{id}`            | *(sem can:)*        | `OrgaoShowController`              | OK     |
| CREATE   | GET    | `/compdec/orgaos/novo`            | `can:compdec.manage` | `OrgaoCreateController@create`    | OK     |
| STORE    | POST   | `/compdec/orgaos`                 | `can:compdec.manage` | `OrgaoCreateController@store`     | OK     |
| EDIT     | GET    | `/compdec/orgaos/{id}/editar`     | `can:compdec.manage` | `OrgaoUpdateController@edit`      | OK     |
| UPDATE   | PUT    | `/compdec/orgaos/{id}`            | `can:compdec.manage` | `OrgaoUpdateController@update`    | OK     |
| DELETE   | DELETE | `/compdec/orgaos/{id}`            | `can:compdec.manage` | `OrgaoDeleteController`           | OK     |
| VINCULAR | POST   | `/compdec/orgaos/{id}/usuarios`   | `can:compdec.manage` | `VincularUsuarioController`       | OK     |

### 4.8 PAE (`/pae`)

| Acao     | Metodo | URI                | Middleware                          | Controller                         | Status |
|:---------|:------:|:-------------------|:------------------------------------|:-----------------------------------|:------:|
| INDEX    | GET    | `/pae`             | `can:pae.empreendimentos.view`      | Closure (Inertia)                  | OK     |
| PROTOCOLOS| GET   | `/pae/protocolo`   | `can:pae.protocolos.view`           | Closure (Inertia)                  | OK     |
| EXPORT   | GET    | `/pae/export`      | `can:pae.protocolos.export`         | `PaeProtocoloController@export`    | OK     |

### 4.9 PERMISSIONAMENTO (`/admin/permissions`)

| Acao          | Metodo | URI                                            | Middleware      | Controller                          |
|:--------------|:------:|:-----------------------------------------------|:----------------|:------------------------------------|
| USERS.INDEX   | GET    | `/admin/permissions/users`                     | `can:users.view`| `UserManagementController@index`    |
| USERS.CREATE  | GET    | `/admin/permissions/users/create`              | `can:users.view`| `UserManagementController@create`   |
| USERS.STORE   | POST   | `/admin/permissions/users`                     | `can:users.view`| `UserManagementController@store`    |
| USERS.SHOW    | GET    | `/admin/permissions/users/{user}`              | `can:users.view`| `UserManagementController@show`     |
| USERS.EDIT    | GET    | `/admin/permissions/users/{user}/edit`         | `can:users.view`| `UserManagementController@edit`     |
| USERS.UPDATE  | PUT    | `/admin/permissions/users/{user}`              | `can:users.view`| `UserManagementController@update`   |
| USERS.DELETE  | DELETE | `/admin/permissions/users/{user}`              | `can:users.view`| `UserManagementController@destroy`  |
| USERS.ROLES   | POST   | `/admin/permissions/users/{user}/roles`        | `can:users.view`| `UserManagementController@syncRoles`|
| USERS.PERMS   | POST   | `/admin/permissions/users/{user}/permissions`  | `can:users.view`| `UserManagementController@syncPermissions`|
| ROLES.INDEX   | GET    | `/admin/permissions/roles`                     | `can:users.view`| `RoleManagementController@index`    |
| ROLES.STORE   | POST   | `/admin/permissions/roles`                     | `can:users.view`| `RoleManagementController@store`    |
| ROLES.UPDATE  | PUT    | `/admin/permissions/roles/{role}`              | `can:users.view`| `RoleManagementController@update`   |
| ROLES.DELETE  | DELETE | `/admin/permissions/roles/{role}`              | `can:users.view`| `RoleManagementController@destroy`  |
| ROLES.PERMS   | POST   | `/admin/permissions/roles/{role}/permissions`  | `can:users.view`| `RoleManagementController@syncPermissions`|

### 4.10 METEOROLOGIA (`/inmet`) + SUPORTE (`/suporte`)

| Modulo       | Acao  | Metodo | URI        | Middleware    | Permissao Especifica |
|:-------------|:------|:------:|:-----------|:--------------|:---------------------|
| Meteorologia | INDEX | GET    | `/inmet`   | `auth`        | Nenhuma (publico)    |
| Suporte      | INDEX | GET    | `/suporte` | `auth`        | Nenhuma              |
| Suporte      | STORE | POST   | `/suporte` | `auth`        | Nenhuma              |

---

## 5. PERMISSOES IMUTAVEIS (Nao podem ser deletadas)

| Slug                      | Motivo                              |
|:--------------------------|:------------------------------------|
| `users.view`              | Necessario para gerenciamento basico|
| `users.edit`              | Necessario para gerenciamento basico|
| `roles.view`              | Sistema de cargos depende           |
| `permissions.view`        | Sistema de permissoes depende       |
| `system.settings.manage`  | Configuracoes do sistema            |

---

## 6. CARGOS PROTEGIDOS (Nao podem ser editados/deletados)

| Cargo         | Motivo                              |
|:--------------|:------------------------------------|
| `super-admin` | Cargo raiz do sistema — imutavel    |

---

## 7. PADRAO DE ROTAS — ServiceProvider (REGRA)

> **REGRA CRITICA:** Rotas de modulos devem ser carregadas **APENAS** via `routes/web.php` dentro de `Route::middleware('auth')`.
> **NAO** usar `$this->loadRoutesFrom()` nos ServiceProviders de modulo.
> **Padrao correto:** `RatServiceProvider` — boot() vazio, sem loadRoutesFrom.

| ServiceProvider             | loadRoutesFrom | Status  | Motivo se errado                                  |
|:----------------------------|:--------------:|:-------:|:--------------------------------------------------|
| `RatServiceProvider`        | NAO            | CORRETO | Padrao de referencia                              |
| `DecretacoesServiceProvider`| NAO            | CORRETO | Corrigido — antes causava 403 (sem web/auth)      |
| `AjudaHumanitariaServiceProvider`| NAO       | CORRETO | Corrigido — antes causava 403 (sem web/auth)      |
| `TreinamentoServiceProvider`| NAO            | CORRETO | Corrigido — antes causava 403 (sem web/auth)      |
| `TdapServiceProvider`       | NAO            | CORRETO | Corrigido — antes tinha so middleware('web')       |
| `InmetServiceProvider`      | NAO            | CORRETO | Corrigido — rotas duplicadas sem middleware        |
| `DemandasServiceProvider`   | NAO            | CORRETO | Corrigido — rotas duplicadas sem middleware        |
| `CompdecServiceProvider`    | NAO            | CORRETO | Corrigido — rotas duplicadas sem middleware        |

**Explicacao do bug:** `loadRoutesFrom()` no ServiceProvider.boot() roda DEPOIS do RouteServiceProvider, sobrescrevendo as rotas do web.php (com middleware auth+web) por versoes sem middleware. Resultado: sessao nula, usuario nulo, 403.
