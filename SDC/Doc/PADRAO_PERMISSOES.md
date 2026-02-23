
# Guia de Padronização de Permissões (CRUD)

Este guia descreve como padronizar novos módulos seguindo a arquitetura de segurança do NewSDC (Config-Driven Security).

## 1. Definir Permissões (`config/permissions.php`)
O arquivo de configuração é a **Fonte da Verdade**. Adicione seu módulo aqui.

```php
// config/permissions.php
'modules' => [
    'SEU_MODULO' => [ // Ex: 'Estoque'
        'Recurso' => [ // Ex: 'Produtos'
            'view'   => 'estoque.produtos.view',
            'create' => 'estoque.produtos.create',
            'edit'   => 'estoque.produtos.edit',
            'delete' => 'estoque.produtos.delete',
        ],
    ],
],

// ... Atribuir aos cargos
'role_permissions' => [
    'operator' => [
        'estoque.produtos.view', // Operador só vê
    ],
    'manager' => [
        'estoque.produtos.*', // Gestor faz tudo
    ],
],
```

> **Ação Necessária**: Após editar, rode `php artisan db:seed --class=RolesAndPermissionsSeeder` para sincronizar com o banco.

## 2. Proteger Rotas (Backend)
No arquivo de rotas do módulo (`routes/modules/seu_modulo.php`), use o middleware `can`.

```php
// routes/modules/estoque.php

// Listagem (View)
Route::get('/', [Controller::class, 'index'])
    ->middleware('can:estoque.produtos.view');

// Criação (Create)
Route::post('/', [Controller::class, 'store'])
    ->middleware('can:estoque.produtos.create');

// Edição (Edit)
Route::put('/{id}', [Controller::class, 'update'])
    ->middleware('can:estoque.produtos.edit');

// Exclusão (Delete)
Route::delete('/{id}', [Controller::class, 'destroy'])
    ->middleware('can:estoque.produtos.delete');
```

## 3. Atomic Design no Frontend (Vue.js)
Use o composable `usePermissions` para esconder botões que o usuário não pode clicar.

### No Componente de Tabela (`TableRow.vue`)
```vue
<script setup>
import { usePermissions } from '@/Composables/usePermissions';
const { can } = usePermissions();
</script>

<template>
  <TableActions
    :show-view="can('estoque.produtos.view')"
    :show-edit="can('estoque.produtos.edit')"
    :show-delete="can('estoque.produtos.delete')"
    @delete="$emit('delete')"
  />
</template>
```

### No Componente de Card (`Card.vue`)
Faça o mesmo para a visualização em Grade/Card.

---

## Fluxo de Dados
1. **Login**: O `HandleInertiaRequests.php` envia `auth.user.permissions` para o frontend.
2. **Frontend**: O `usePermissions.js` verifica se a string `estoque.produtos.delete` existe nesse array.
3. **Ação**: Se existir, o botão aparece.
4. **Segurança**: Se o usuário tentar forçar a URL, o Middleware `can:` no backend bloqueia com 403.
