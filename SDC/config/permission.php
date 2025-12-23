<?php

return [
    'models' => [
        'permission' => App\Models\Permission::class,
        'role' => App\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => null,
        'permission_pivot_key' => null,
        'model_morph_key' => 'model_id',
        'team_foreign_key' => 'team_id',
    ],

    // Integra o método de checagem no Gate (User::can / Gate::allows) automaticamente
    'register_permission_check_method' => true,

    // Octane: manter falso por padrão; pode ser ativado se necessário
    'register_octane_reset_listener' => false,

    // Se você quiser auditar attach/detach, ative e crie listeners
    'events_enabled' => false,

    // Teams desabilitado (não usamos ACL por time no NewSDC atualmente)
    'teams' => false,

    'team_resolver' => \Spatie\Permission\DefaultTeamResolver::class,

    'use_passport_client_credentials' => false,

    // Segurança: não expor nomes/roles requeridos em exceptions por padrão
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,

    'enable_wildcard_permission' => false,

    'cache' => [
        // Mantemos 1 hora (mais alinhado com o sistema anterior) e invalidação automática em alterações
        'expiration_time' => \DateInterval::createFromDateString('1 hour'),
        'key' => 'spatie.permission.cache',
        'store' => 'default',
    ],
];


