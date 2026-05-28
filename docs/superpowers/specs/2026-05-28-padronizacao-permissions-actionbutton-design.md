# Padronização das 3 Camadas de Permissão (Config ↔ DB ↔ Frontend)

**Data:** 2026-05-28
**Status:** Design aprovado
**Sessão atual:** apenas PR #1 (adicionar slugs novos)

---

## Contexto

O sistema SDC tem hoje três camadas de autorização que **divergem**:

1. **Config** — [SDC/config/permissions.php](SDC/config/permissions.php) define a "tabela da verdade" de slugs (`modulo.recurso.acao`).
2. **DB** — tabelas Spatie (`permissions`, `roles`, `role_has_permissions`, `model_has_permissions`) sincronizadas via `RolesAndPermissionsSeeder` (idempotente).
3. **Frontend** — `ActionButton.vue` + `TableActions.vue` decidem visibilidade dos botões.

A análise revelou:

- **11 ações em uso no frontend** sem slug correspondente no config (`pae.protocolos.arquivar/history/validar/pdf`, `rat.protocolos.print/attachments`, `decretacoes.processos.print`, `humanitaria.beneficiarios.print`, `estoque.movimentacoes.history`, `compdec.usuarios.desvincular`, etc.).
- **4 mismatches frontend ↔ config** onde o nome diverge (`plantao.turnos` vs `plantao.plantoes`, `treinamento.cursos` vs `treinamentos.treinamentos`, `humanitaria` vs `ajuda-humanitaria`, `pae.protocolos.atribuir` vs action key `assign`).
- **`TableActions.vue` força `:allowed="true"`** ([linhas 11 e 29](SDC/resources/js/Components/Molecules/Table/TableActions.vue#L11-L29)) — desliga a checagem do `ActionButton`.
- **`useActionConfig.js`** consome `page.props.actionConfigs` que o backend nunca preencheu → default `true` para tudo.
- **Resultado**: botões aparecem para qualquer usuário não-super-admin que chegou na tela, sem checagem efetiva.

---

## Decisões de design (consolidadas)

| # | Decisão | Justificativa |
|---|---|---|
| D1 | Escopo: padronização completa das 3 camadas | Resolver tudo de uma vez, em PRs sequenciais e atômicos |
| D2 | Validação via comando artisan + phpunit | Garantir sincronia futura no CI |
| D3 | Renomear no config para alinhar com frontend (não o contrário) | Frontend está mais próximo do uso real, semântica do negócio |
| D4 | Ações de negócio em português (`validar`, `atribuir`, `arquivar`, `finalizar`); CRUD em inglês (`view`, `edit`, `delete`, `print`, `pdf`, `history`, `export`, `attachments`) | Convivência pragmática — CRUD já está estabelecido em inglês |
| D5 | Fail-closed: todo botão exige slug, exceto UI containers | Segurança por padrão |
| D6 | `options`, `warning`, `notifications` ficam fora do RBAC | São wrappers de UI, não permissões |
| D7 | Abordagem C — Convention over Configuration com escape hatch | `ActionButton` monta slug via convention, prop `allowed` é AND adicional |
| D8 | Centralizar TUDO no `ActionButton.vue` (modo único + modo grupo) | Eliminar duplicação entre `ActionButton`, `TableActions`, `SmartTableActions`, `useActionConfig` |
| D9 | Tokens Sanctum: UPDATE JSON `abilities` na migration de renomeação | Preservar tokens vivos sem revogação |
| D10 | Sem `ACTION_PERMISSION_RENAMED` no audit log | Sistema novo, sem histórico relevante a preservar |

---

## Arquitetura final

```
┌─────────────────────────────────────────────────────────────┐
│  CAMADA 1 — config/permissions.php  (FONTE DE VERDADE)      │
│  Estrutura: modules.<MODULO>.<Recurso>.<acao> = '<slug>'    │
└────────────────────┬────────────────────────────────────────┘
                     │  RolesAndPermissionsSeeder (updateOrCreate)
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  CAMADA 2 — DB                                              │
│  permissions, roles, role_has_permissions,                  │
│  model_has_permissions, model_has_roles                     │
└────────────────────┬────────────────────────────────────────┘
                     │  HandleInertiaRequests::share (auth.permissions)
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  CAMADA 3 — Frontend                                        │
│  ActionButton.vue (ÚNICO componente — modo botão ou grupo)  │
│    • can(`${module}.${resource}.${aliasMap[action] ?? action}`) │
│    • allowed (escape hatch para regras de negócio)          │
│    • UI_ONLY_ACTIONS = ['options', 'warning', 'notifications'] │
│    • ACTION_ALIAS = { check: 'validar', archive: 'arquivar', │
│                       assign: 'atribuir', finalize: 'finalizar' } │
└─────────────────────────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  CAMADA 4 (proteção real) — Controllers + Policies          │
│  ->middleware('can:<slug>') no construtor                   │
└─────────────────────────────────────────────────────────────┘
```

---

## Plano de execução em 4 PRs

### PR #1 — Adicionar slugs novos (sessão atual)

**Risco:** zero. Apenas adições, nenhuma renomeação.

**Tarefas:**

1. Em [SDC/config/permissions.php](SDC/config/permissions.php), adicionar:

| Módulo | Novos slugs |
|---|---|
| PAE → Protocolos | `pae.protocolos.arquivar`, `pae.protocolos.history`, `pae.protocolos.validar`, `pae.protocolos.pdf` |
| RAT → Protocolos | `rat.protocolos.print`, `rat.protocolos.attachments` |
| Decretações → Processos | `decretacoes.processos.print` |
| Humanitária → Beneficiários | `humanitaria.beneficiarios.print` |
| Estoque → Movimentações | `estoque.movimentacoes.history` |
| COMPDEC → UsuarioVinculo | `compdec.usuarios.desvincular` |

Total: **10 slugs novos** (cisternas.print fica para depois — atualmente `show-print="false"`).

2. Atribuir cada novo slug ao bloco `role_permissions` do config seguindo a regra:
   - `*.view`, `*.print`, `*.history`, `*.pdf` → admin + manager + analyst + operator + viewer
   - `*.arquivar`, `*.attachments` → admin + manager + analyst
   - `*.validar` → admin + manager
   - `*.desvincular` → admin

3. Rodar `php artisan db:seed --class=RolesAndPermissionsSeeder` em homologação. Verificar que as permissões aparecem na tela `/admin/permissions/roles/{id}`.

4. Validar manualmente: ninguém perdeu acesso, contagem de permissões por role subiu de forma esperada.

**Não-objetivos do PR #1:**

- Não renomear slugs (`plantao.turnos.*` permanece como está).
- Não tocar em `ActionButton.vue`.
- Não deletar `TableActions.vue` / `SmartTableActions.vue` / `useActionConfig.js`.
- Não tocar em controllers ou policies.

### PR #2 — Refatoração de `ActionButton` + deleção de `TableActions`/`SmartTableActions`/`useActionConfig`

**Sessão futura.** Reescreve `hasPermission` com convention, adiciona modo grupo (prop `actions`), prop `placement`. Deleta os 3 arquivos órfãos. Migra ~15 telas consumidoras.

### PR #3 — Renomeação atômica

**Sessão futura.** Migration de `UPDATE permissions.name` + `UPDATE personal_access_tokens.abilities` (JSON replace). Atualiza config, middlewares de controllers, referências em policies. Tudo num único commit.

### PR #4 — Frontend migra para slugs novos

**Sessão futura.** Telas passam a usar `module="plantao" resource="plantoes"` (e outros mismatches resolvidos). Comando `php artisan permissions:audit` é introduzido com cobertura completa.

---

## Validação (futura — entra no PR #2 ou PR #3)

Comando artisan `php artisan permissions:audit` que reporta:

```
[OK]   154 permissions no banco, 154 no config
[OK]   Zero slugs órfãs (no banco mas não no config ativo)
[OK]   Zero slugs no config sem registro no banco
[WARN] N usuários com permissões diretas (model_has_permissions)
[INFO] Todos os slugs diretos existem no config
```

Integrado ao CI via `composer test` ou hook de pre-deploy.

---

## Critério de sucesso

Após os 4 PRs:

- `php artisan permissions:audit` retorna verde.
- `grep -r ":allowed=\"true\"" SDC/resources/js` retorna zero ocorrências.
- `grep -r "TableActions" SDC/resources/js` retorna zero ocorrências.
- Toda tela com botões mostra o array `actions` no `ActionButton`.
- Cada `<ActionButton>` em produção tem `module` + `resource` ou `:allowed` explícito.
- Nenhum usuário em produção perdeu acesso a funcionalidades existentes.

---

## Critério de sucesso do PR #1 (sessão atual)

- 10 slugs adicionados em [SDC/config/permissions.php](SDC/config/permissions.php).
- `role_permissions` atualizado.
- `php artisan db:seed --class=RolesAndPermissionsSeeder` roda sem erros em homologação.
- Tela `/admin/permissions/roles/2` (Administrador) mostra ao menos os novos slugs PAE/RAT/Decretações/Humanitária/Estoque.
- Nada quebra: contagem antiga de permissões dos roles é preservada.
