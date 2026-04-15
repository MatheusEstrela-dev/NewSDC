# Design: Gerenciamento de Tokens de API no Módulo de Permissões

**Data:** 2026-04-15
**Módulo:** Admin / Permissions / Users
**Tipo:** Nova funcionalidade

---

## Objetivo

Adicionar uma interface no frontend (tela `Show.vue` do usuário) que permita ao administrador visualizar, gerar e revogar tokens Bearer (Sanctum) para uso na API REST do SDC.

---

## Decisões de Design

| Decisão | Escolha |
|---|---|
| Localização na UI | Card dedicado na coluna principal, abaixo de "Permissões Diretas" |
| Quem opera | Apenas administrador na tela `Show.vue` |
| Tokens simultâneos | Múltiplos tokens nomeados por usuário |
| Expiração | Configurável ao gerar: 24h / 7 dias / 30 dias / Sem expiração |
| Schema de banco | Tabela `personal_access_tokens` do Sanctum (sem alterações) |

---

## Arquitetura

### Novos arquivos

```
app/Http/Controllers/Admin/UserTokenController.php
resources/js/Components/Admin/UserApiTokens.vue
```

### Arquivos modificados

```
app/Http/Controllers/Admin/UserManagementController.php  → show()
resources/js/Pages/Admin/Permissions/Users/Show.vue
routes/web.php
```

### Serviço reutilizado

`app/Services/Auth/TokenService.php` — sem modificações. Já provê:
- `createTokenForUser(User, string): NewAccessToken`
- `revokeToken(User, int): bool`
- `revokeAllTokens(User): void`
- `getUserTokens(User): array`

---

## Rotas

```
POST   admin/users/{user}/tokens           admin.permissions.users.tokens.store
DELETE admin/users/{user}/tokens/{tokenId} admin.permissions.users.tokens.destroy
```

Middleware: `can:users.edit` em ambas as rotas.

Agrupadas sob o mesmo prefix/name group existente em `routes/web.php`.

Não há rota GET separada para tokens — a listagem é carregada diretamente pelo `show()` do `UserManagementController`.

---

## Controller: UserTokenController

```
store(Request, $user)    → valida nome + expiração, cria token via TokenService, retorna token bruto UMA vez via flash
destroy($user, $tokenId) → revoga token via TokenService, redirect back
```

**Validação do store:**
- `name`: required, string, max:60
- `expires_in`: required, in:24h,7d,30d,never

**Mapeamento de expiração:**
- `24h` → `Carbon::now()->addHours(24)`
- `7d` → `Carbon::now()->addDays(7)`
- `30d` → `Carbon::now()->addDays(30)`
- `never` → `null`

**Flash após store:**
```php
return redirect()
    ->route('admin.permissions.users.show', $user)
    ->with('new_token', $token->plainTextToken)
    ->with('new_token_name', $validated['name']);
```

O token bruto trafega via sessão flash e é exibido **uma única vez** na próxima renderização.

---

## UserManagementController — show()

Adicionar carregamento de tokens ao método existente:

```php
public function show(User $user): Response
{
    $user->load(['roles.permissions', 'permissions']);
    // ... lógica existente ...

    $tokens = $user->tokens->map(fn($t) => [
        'id'           => $t->id,
        'name'         => $t->name,
        'abilities'    => $t->abilities,
        'last_used_at' => $t->last_used_at,
        'expires_at'   => $t->expires_at,
        'created_at'   => $t->created_at,
    ]);

    return Inertia::render('Admin/Permissions/Users/Show', [
        'user'      => $user,
        'tokens'    => $tokens,
        'newToken'  => session('new_token'),
        'newTokenName' => session('new_token_name'),
    ]);
}
```

---

## Componente: UserApiTokens.vue

**Props:**
```js
defineProps({
  userId:       { type: Number, required: true },
  tokens:       { type: Array,  default: () => [] },
  newToken:     { type: String, default: null },
  newTokenName: { type: String, default: null },
})
```

**Estados visuais:**

| Estado | Trigger |
|---|---|
| Lista normal | `showForm === false` e `newToken === null` |
| Form aberto (tokens dimmed) | `showForm === true` |
| Flash token revelado | `newToken !== null` (exibido uma vez, limpo ao fechar) |

**Comportamento do form:**
- Campos: `name` (input text) + `expires_in` (select)
- Submit via `router.post()` do Inertia
- Ao abrir o form: tokens existentes ficam com `opacity: 0.3` (`dimmed`)
- Ao fechar/cancelar: volta ao estado de lista normal

**Comportamento do flash:**
- Exibido no topo do card com fundo verde (`#0d2d1a`) e borda (`#166534`)
- Aviso: "Copie agora. Este valor não será exibido novamente."
- Botão "Copiar Token" usa `navigator.clipboard.writeText()`
- Após copiar: botão muda para "Copiado!" por 2 segundos
- Flash é limpo ao navegar (Inertia não persiste flash entre renders)

**Botão Revogar:**
- Dispara `router.delete()` com confirmação via `confirm()` nativo
- Sem modal adicional (YAGNI)

---

## Estados do Card — Resumo Visual

```
[Estado 1] Lista de tokens
  ┌─ Header: "Tokens de API" | badge(count) | btn "Gerar Novo"
  ├─ Token row: dot-status | nome | badge-status | mask | [Copiar] [Revogar]
  └─ Token row: ...

[Estado 2] Form aberto
  ┌─ Header: "Tokens de API" | badge(count)
  ├─ Token row (dimmed, opacity .3): ...
  ├─ Token row (dimmed, opacity .3): ...
  └─ Form overlay: input[nome] + select[expiração] + [Gerar] [Cancelar]

[Estado 3] Token recém-gerado
  ┌─ Header: "Tokens de API" | badge(count) | btn "Gerar Novo"
  ├─ Flash verde: nome do token + valor bruto + [Copiar Token]
  ├─ Token row: ...
  └─ Token row (novo): badge NOVO
```

---

## Segurança

- Rota protegida por `can:users.edit`
- Token bruto nunca salvo em banco (Sanctum salva apenas hash)
- Token bruto transmitido via flash de sessão server-side (não via JSON na prop)
- Nenhum token bruto logado

---

## Sem alterações no banco

A tabela `personal_access_tokens` do Sanctum já possui todas as colunas necessárias:
`id`, `tokenable_type`, `tokenable_id`, `name`, `token` (hash), `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`.

Nenhuma migration necessária.
