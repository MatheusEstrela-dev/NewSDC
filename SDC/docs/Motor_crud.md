# Core de Configuracao de Acoes (Action Config)

## Visao Geral

O Core de Configuracao de Acoes e um sistema centralizado para gerenciar os botoes de acao (visualizar, imprimir, editar, excluir, etc.) de forma global no sistema SDC.

Este sistema segue os principios:
- **DDD (Domain-Driven Design)**: Cada modulo define suas proprias acoes
- **SOLID**: Interfaces segregadas, inversao de dependencia
- **DRY**: Configuracao centralizada, sem duplicacao

---

## Arquitetura

```
app/
├── Core/
│   └── Actions/
│       ├── Contracts/
│       │   ├── ActionConfigInterface.php
│       │   └── ModuleActionsInterface.php
│       ├── DTOs/
│       │   └── ActionConfigDTO.php
│       ├── Enums/
│       │   └── ActionType.php
│       ├── Services/
│       │   └── ActionConfigService.php
│       └── AbstractModuleActions.php
├── Modules/
│   └── [Modulo]/
│       └── Config/
│           └── [Modulo]ActionsConfig.php
└── Providers/
    └── ActionConfigServiceProvider.php

resources/js/
├── composables/
│   └── useActionConfig.js
└── domain/
    └── actions/
        └── ActionTypes.js
```

---

## Backend

### ActionType Enum

Define os tipos de acoes disponiveis:

```php
use App\Core\Actions\Enums\ActionType;

ActionType::VIEW        // Visualizar
ActionType::PRINT       // Imprimir
ActionType::EDIT        // Editar
ActionType::DELETE      // Excluir
ActionType::ATTACHMENTS // Anexos
ActionType::HISTORY     // Historico
ActionType::ARCHIVE     // Arquivar
ActionType::UPLOAD      // Upload
ActionType::EXPORT      // Exportar
ActionType::DUPLICATE   // Duplicar
```

### ActionConfigDTO

Objeto de transferencia para configuracao de uma acao:

```php
use App\Core\Actions\DTOs\ActionConfigDTO;

$config = new ActionConfigDTO(
    action: 'view',
    enabled: true,
    permission: 'rat.view',
    icon: 'EyeIcon',
    variant: 'primary',
    label: 'Visualizar',
    order: 1,
    requiresConfirmation: false
);

// Ou a partir do ActionType
$config = ActionConfigDTO::fromActionType(
    actionType: ActionType::DELETE,
    enabled: true,
    permission: 'rat.delete'
);
```

### Criando Configuracao de Modulo

1. Crie a classe de configuracao em `app/Modules/[Modulo]/Config/`:

```php
namespace App\Modules\Rat\Config;

use App\Core\Actions\AbstractModuleActions;
use App\Core\Actions\Enums\ActionType;

class RatActionsConfig extends AbstractModuleActions
{
    public function getModuleName(): string
    {
        return 'rat';
    }

    public function getAvailableActions(): array
    {
        return [
            ActionType::VIEW,
            ActionType::PRINT,
            ActionType::EDIT,
            ActionType::ATTACHMENTS,
            ActionType::DELETE,
        ];
    }

    public function getDefaultConfig(): array
    {
        return [
            ActionType::VIEW->value => $this->createActionConfig(
                actionType: ActionType::VIEW,
                order: 1
            ),
            ActionType::PRINT->value => $this->createActionConfig(
                actionType: ActionType::PRINT,
                order: 2
            ),
            // ...
        ];
    }

    // Opcional: regras condicionais
    public function getConditionalOverrides(mixed $entity = null): array
    {
        if ($entity?->status === 'finalizado') {
            return [
                ActionType::EDIT->value => ['enabled' => false],
                ActionType::DELETE->value => ['enabled' => false],
            ];
        }
        return [];
    }
}
```

2. Registre no arquivo `config/actions.php`:

```php
'modules' => [
    \App\Modules\Rat\Config\RatActionsConfig::class,
    // ...
],
```

### Compartilhando com Frontend (Inertia)

No `HandleInertiaRequests.php`:

```php
use App\Core\Actions\Services\ActionConfigService;

public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'actionConfigs' => fn () => app(ActionConfigService::class)->toFrontendConfig(),
    ]);
}
```

---

## Frontend

### useActionConfig Composable

```javascript
import { useActionConfig } from '@/composables/useActionConfig';

const {
    getTableActionsProps,
    isActionEnabled,
    ActionTypes
} = useActionConfig('rat');
```

### Uso no Template

**Antes (manual):**
```vue
<TableActions
  :show-view="true"
  :show-print="true"
  :show-edit="canEdit"
  :show-delete="canDelete"
  @view="handleView"
/>
```

**Depois (com Core):**
```vue
<script setup>
import { useActionConfig } from '@/composables/useActionConfig';

const { getTableActionsProps } = useActionConfig('rat');
</script>

<template>
  <TableActions
    v-bind="getTableActionsProps()"
    @view="handleView"
    @print="handlePrint"
    @edit="handleEdit"
    @delete="handleDelete"
  />
</template>
```

### Sobrescrevendo Acoes Localmente

```vue
<script setup>
const { getTableActionsProps } = useActionConfig('rat');

// Desabilitar edicao para este componente especifico
const actionsProps = getTableActionsProps({ edit: false });
</script>
```

### Acoes Baseadas em Entidade

```vue
<script setup>
import { useActionConfig, CommonActionRules } from '@/composables/useActionConfig';

const { getTableActionsPropsForEntity } = useActionConfig('rat');

// Props variam baseado no status da entidade
const getActionsForRow = (rat) => {
    return getTableActionsPropsForEntity(rat, {
        edit: CommonActionRules.disableEditWhenFinalized,
        delete: CommonActionRules.disableDeleteWhenInProgress,
    });
};
</script>

<template>
  <tr v-for="rat in rats" :key="rat.id">
    <td>
      <TableActions
        v-bind="getActionsForRow(rat)"
        @view="() => handleView(rat.id)"
      />
    </td>
  </tr>
</template>
```

### Verificando Acoes Individualmente

```vue
<script setup>
const { isActionEnabled, ActionTypes } = useActionConfig('rat');

// Verificar se pode editar
const canEdit = isActionEnabled(ActionTypes.EDIT);

// Verificar com override local
const canDelete = isActionEnabled(ActionTypes.DELETE, { delete: !isProtected });
</script>
```

---

## Fluxo de Dados

```
Backend                          Frontend
   |                                |
   | 1. Registra ModuleConfig       |
   |    no ServiceProvider          |
   |                                |
   | 2. ActionConfigService         |
   |    processa configs            |
   |                                |
   | 3. Inertia compartilha         |
   |    actionConfigs               |
   |         ------------------>    |
   |                                | 4. useActionConfig
   |                                |    le configs
   |                                |
   |                                | 5. usePermissions
   |                                |    verifica permissao
   |                                |
   |                                | 6. Renderiza botoes
   |                                |    habilitados
```

---

## Principios SOLID Aplicados

| Principio | Implementacao |
|-----------|---------------|
| **S**RP | `ActionConfigDTO` - apenas transfere dados |
| **O**CP | `ActionType` enum - extensivel sem modificar |
| **L**SP | `AbstractModuleActions` - substituivel |
| **I**SP | Interfaces segregadas (`ActionConfigInterface`, `ModuleActionsInterface`) |
| **D**IP | Services dependem de interfaces, nao implementacoes |

---

## Adicionando Nova Acao

1. Adicionar ao enum `ActionType.php`:
```php
case APPROVE = 'approve';

public function label(): string
{
    return match ($this) {
        // ...
        self::APPROVE => 'Aprovar',
    };
}
```

2. Adicionar ao `ActionTypes.js`:
```javascript
APPROVE: 'approve'
```

3. Adicionar ao `ActionDefaults`:
```javascript
[ActionTypes.APPROVE]: {
    icon: 'CheckIcon',
    variant: 'success',
    label: 'Aprovar'
}
```

4. Adicionar prop ao `TableActions.vue`:
```vue
<ButtonIcon
  v-if="showApprove"
  :icon="CheckIcon"
  variant="success"
  @click="$emit('approve')"
/>
```

5. Usar no modulo que precisa:
```php
public function getAvailableActions(): array
{
    return [
        // ...
        ActionType::APPROVE,
    ];
}
```

---

## Arquivos Criados

### Backend
- `app/Core/Actions/Enums/ActionType.php`
- `app/Core/Actions/DTOs/ActionConfigDTO.php`
- `app/Core/Actions/Contracts/ActionConfigInterface.php`
- `app/Core/Actions/Contracts/ModuleActionsInterface.php`
- `app/Core/Actions/Services/ActionConfigService.php`
- `app/Core/Actions/AbstractModuleActions.php`
- `app/Providers/ActionConfigServiceProvider.php`
- `config/actions.php`

### Frontend
- `resources/js/domain/actions/ActionTypes.js`
- `resources/js/composables/useActionConfig.js`

### Exemplo de Modulo
- `app/Modules/Rat/Config/RatActionsConfig.php`
