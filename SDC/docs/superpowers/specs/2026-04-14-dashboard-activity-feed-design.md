# Design: Activity Feed — Últimas Movimentações + Preferências de Notificação

**Data:** 2026-04-14
**Projeto:** NewSDC — `c:\Users\x24679188\Documents\Github\NewSDC\SDC`
**Módulo alvo:** `app/Modules/Dashboard/` + `app/Models/` + `resources/js/Components/Dashboard/Widgets/TimelineWidget.vue`

---

## Contexto

O bloco "Últimas Movimentações" no Dashboard (`TimelineWidget.vue`) exibe dados mockados. O painel de Configurações > Notificações tem UI com 4 módulos e 3 toggles cada (No Sistema / E-mail / Push) mas não persiste nada no banco. O objetivo é conectar ambos com dados reais, adicionar modo de atualização configurável (polling ou WebSocket), e mostrar as últimas 7 movimentações relacionadas ao perfil do usuário logado.

---

## Fonte de Dados

O `AuditLog` existente (`app/Models/AuditLog.php`) registra `user_id`, `event`, `table_name`, `row_id`, `old_values`, `new_values`, `created_at`. É a fonte primária do feed.

### Tipos de movimentação (opção C — próprias + de terceiros em registros atribuídos)

| Tipo | Query | Tabela AuditLog |
|------|-------|-----------------|
| Ações próprias | `user_id = $userId` | qualquer `table_name` |
| PAE atribuído | `pae_protocolos.analista_atual_id = $userId` → AuditLog de outros usuários nessas linhas | `pae_protocolos` |
| Demandas atribuídas | `tasks.assigned_to = $userId` → AuditLog de outros usuários nessas linhas | `tasks` |
| Decretações / RAT | apenas ações próprias (sem campo de responsável confirmado) | — |

Resultado: merge das queries acima, ordenado por `created_at DESC`, limitado a 7 itens, filtrado pelos módulos com `canal_sistema = true`.

---

## Modelo de Dados

### Nova tabela: `user_notification_preferences`

```sql
id               BIGINT PK
user_id          BIGINT FK → users (cascade delete)
module           ENUM('rat','pae','meteorologia','demandas','decretacoes','ajuda_humanitaria')
canal_sistema    BOOLEAN DEFAULT TRUE
canal_email      BOOLEAN DEFAULT FALSE
canal_push       BOOLEAN DEFAULT FALSE
created_at       TIMESTAMP
updated_at       TIMESTAMP
UNIQUE KEY (user_id, module)
```

### Coluna na tabela `users` (migration adicional)

```sql
notification_update_mode   ENUM('polling','realtime')  DEFAULT 'polling'
```

Controla globalmente se o usuário usa polling 60s ou WebSocket em tempo real.

---

## Arquitetura — Fase 1 (Polling)

### Novos arquivos PHP

```
app/Models/UserNotificationPreference.php
app/Modules/Dashboard/Services/ActivityFeedService.php
app/Http/Controllers/ActivityFeedController.php
app/Http/Controllers/NotificationPreferencesController.php
database/migrations/YYYY_create_user_notification_preferences_table.php
database/migrations/YYYY_add_notification_update_mode_to_users_table.php
```

### ActivityFeedService

```php
public function getFeed(int $userId, int $limit = 7): array
```

1. Carrega módulos habilitados: `UserNotificationPreference::where('user_id', $userId)->where('canal_sistema', true)->pluck('module')`
2. Query ações próprias no AuditLog filtrado por `table_name` dos módulos ativos
3. Query PAE: IDs de `pae_protocolos` onde `analista_atual_id = $userId` → AuditLog de outros nessas linhas
4. Query Demandas: IDs de `tasks` onde `assigned_to = $userId` → AuditLog de outros nessas linhas
5. Merge com `Collection::merge()`, `sortByDesc('created_at')`, `take($limit)`
6. Mapeia para array `[type, acao, modulo, referencia, tempoRelativo]`

### Endpoints

```
GET  /api/v1/activity-feed                 ActivityFeedController@index
GET  /api/v1/notification-preferences      NotificationPreferencesController@index
PUT  /api/v1/notification-preferences      NotificationPreferencesController@update
```

Todos protegidos por `auth:sanctum`. Resposta JSON.

### Mapeamento AuditLog → tipo visual

| table_name | event | type exibido |
|------------|-------|--------------|
| `pae_protocolos` | insert | `new_process` |
| `pae_protocolos` | update | `analysis` |
| `rat_ocorrencias` | insert | `alert` |
| `rat_ocorrencias` | update | `alert` |
| `tasks` | update | `analysis` |
| `dec_entrada_processos` | update + reconhecimento=Reconhecido% | `approval` |
| qualquer | insert | `new_process` (fallback) |
| qualquer | update | `analysis` (fallback) |

---

## Arquitetura — Fase 2 (WebSocket com Laravel Reverb)

### Infraestrutura

- `composer require laravel/reverb`
- `php artisan reverb:install`
- `BROADCAST_DRIVER=reverb` no `.env`
- Serviço `reverb` no `docker-compose.yml`
- `npm install laravel-echo pusher-js`
- Configurar `Echo` no `resources/js/bootstrap.js`

### Novo evento

`app/Events/UserActivityEvent.php` — implementa `ShouldBroadcast`
- Canal privado: `user.{userId}` (autenticado via `routes/channels.php`)
- Payload: mesmo formato de `ActivityFeedService::getFeed()` (1 item)

### Observadores

Observers existentes ou novos nos modelos-chave disparam `UserActivityEvent` nos métodos `created` e `updated`:

- `PaeProtocolo` → `PaeProtocoloObserver`
- `Task` → observer existente nas Demandas
- `RatOcorrencia` → observer no Rat
- `Processo` → `ProcessoObserver` (já existe)

O evento é disparado para o `analista_atual_id` (PAE) ou `assigned_to` (Demandas), não para o usuário que fez a ação.

---

## Frontend

### `TimelineWidget.vue`

Remover mock `historico ref([...])`. Usar composable `useActivityFeed`:

```js
const { items, isLoading, refresh } = useActivityFeed()
```

- `update_mode === 'polling'`: `setInterval(refresh, 60_000)` + fetch no mount
- `update_mode === 'realtime'`: `Echo.private('user.{id}').listen('UserActivityEvent', onEvent)`
- Estados: loading skeleton (3 linhas), empty state ("Nenhuma movimentação recente"), lista real

### `resources/js/composables/useActivityFeed.js` (novo)

- `fetch`: `GET /api/v1/activity-feed`
- Lê `usePage().props.auth.user.notification_update_mode` para decidir modo
- Retorna `{ items, isLoading, refresh }`

### `SettingsModal.vue`

- `onMounted`: `GET /api/v1/notification-preferences` → popula toggles
- On toggle change: `PUT /api/v1/notification-preferences` com debounce 300ms
- Adicionar seção "Modo de Atualização" no tab Notificações:
  - Radio: `Polling (60s)` / `Tempo Real`
  - Salva `notification_update_mode` na mesma chamada PUT

---

## Fora do Escopo

- Canal E-mail e Push (toggles existem na UI mas entrega de e-mail/push fica para outro spec)
- Filtros avançados no feed (por módulo, por data, por tipo)
- Paginação do feed além dos 7 itens
- Notificações em tempo real para Meteorologia (sem modelo de dados claro)
- Dashboard de administrador com feed de outros usuários

---

## Ordem de Implementação

1. Migration `user_notification_preferences` + coluna `notification_update_mode` em users
2. `UserNotificationPreference` model + `NotificationPreferencesController` + rotas
3. `ActivityFeedService` + `ActivityFeedController` + rotas
4. `TimelineWidget.vue` + `useActivityFeed.js` — polling funcional
5. `SettingsModal.vue` — salvar/carregar preferências
6. Instalar Reverb + configurar Docker
7. `UserActivityEvent` + observers nos módulos
8. `useActivityFeed.js` — branch realtime via Echo
9. `SettingsModal.vue` — adicionar toggle Polling/Tempo Real
