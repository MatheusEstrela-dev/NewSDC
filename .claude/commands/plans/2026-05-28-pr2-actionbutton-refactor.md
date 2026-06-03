# PR #2 — Refatoração do ActionButton + Extinção do TableActions

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tornar o `ActionButton.vue` a fonte única de verdade para botões de ação no sistema (modo único + modo grupo), e deletar os três componentes/composables órfãos: `TableActions.vue`, `SmartTableActions.vue`, `useActionConfig.js`.

**Architecture:** Abordagem C — Convention over Configuration: `ActionButton` monta o slug automaticamente via `can(module.resource.aliasMap[action] ?? action)`. Aceita prop `actions` (array) para renderizar grupo (botões inline + dropdown). Prop `allowed` continua sendo escape hatch (AND com permissão, não substituição). Auto-checagem para TODAS as ações (não só `create`). Fail-closed: sem `resource` → escondido.

**Tech Stack:** Vue 3 SFC + Composition API + Inertia + Spatie Permission (já existente).

**Spec:** [docs/superpowers/specs/2026-05-28-padronizacao-permissions-actionbutton-design.md](docs/superpowers/specs/2026-05-28-padronizacao-permissions-actionbutton-design.md)

---

## Files Touched

| Operação | Arquivo |
|---|---|
| Reescrever (extensão) | [SDC/resources/js/Components/Atoms/Button/ActionButton.vue](SDC/resources/js/Components/Atoms/Button/ActionButton.vue) |
| Deletar | [SDC/resources/js/Components/Molecules/Table/TableActions.vue](SDC/resources/js/Components/Molecules/Table/TableActions.vue) |
| Deletar | [SDC/resources/js/Components/Molecules/Table/SmartTableActions.vue](SDC/resources/js/Components/Molecules/Table/SmartTableActions.vue) |
| Deletar | [SDC/resources/js/composables/ui/useActionConfig.js](SDC/resources/js/composables/ui/useActionConfig.js) |
| Ajustar exports | [SDC/resources/js/composables/ui/index.js](SDC/resources/js/composables/ui/index.js) |
| Migrar telas | 18 arquivos `.vue` (listados em Task 3) |

---

## Decisões fixadas

| # | Decisão | Detalhe |
|---|---|---|
| F1 | Prop nova `actions: Array \| null` | Presença ativa modo grupo |
| F2 | Item do array: `{ action, handler, allowed?, placement?, module?, resource?, label?, variant? }` | `handler` é callback (substitui emits) |
| F3 | `placement: 'inline' \| 'menu'` (default `'inline'`) | Decide se vai num botão lado a lado ou no dropdown |
| F4 | `ACTION_ALIAS` interno mapeia `check→validar, archive→arquivar, assign→atribuir, finalize→finalize` | Finalize fica em EN nesse PR; renomeia para PT no PR #3 |
| F5 | `UI_ONLY_ACTIONS = ['options', 'warning', 'notifications']` | Sempre liberados, sem slug |
| F6 | `allowed` é AND com permissão | Não substitui RBAC |
| F7 | `hasPermissionFor` é única função, reutilizada por modo único | DRY |
| F8 | Modo único mantém API atual (zero breaking) | Compatibilidade preserva os ~13 consumidores de ActionButton modo único |
| F9 | Botão "options" do dropdown 3-pontos é gerado automaticamente quando há `menuActions.length > 0` | Não precisa declarar no array |
| F10 | Sem testes phpunit nessa fase | Validação é visual + build |

---

### Task 1: Reescrever `ActionButton.vue` (núcleo)

**Files:**
- Modify: `SDC/resources/js/Components/Atoms/Button/ActionButton.vue` (arquivo inteiro reescrito; preserva imports e estrutura de upload)

- [ ] **Step 1: Substituir conteúdo do arquivo**

Conteúdo completo do novo arquivo:

```vue
<template>
  <!-- ===== MODO GRUPO ===== -->
  <div v-if="isGroupMode" class="flex items-center gap-2">
    <ButtonIcon
      v-for="(item, idx) in visibleInlineActions"
      :key="`inline-${idx}-${item.action}`"
      :icon="ActionIcons[item.action]"
      :variant="item.variant ?? ActionIconVariants[item.action] ?? 'secondary'"
      :size="size"
      :title="item.label ?? ActionLabels[item.action] ?? item.action"
      @click="item.handler"
    />

    <Dropdown
      v-if="visibleMenuActions.length > 0"
      align="right"
      width="48"
      content-classes="py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
    >
      <template #trigger>
        <ButtonIcon
          :icon="ActionIcons.options"
          variant="secondary"
          :size="size"
          title="Opcoes"
        />
      </template>

      <template #content>
        <button
          v-for="(item, idx) in visibleMenuActions"
          :key="`menu-${idx}-${item.action}`"
          type="button"
          class="w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
          @click="item.handler"
        >
          <component
            :is="ActionIcons[item.action]"
            class="w-4 h-4 flex-shrink-0"
          />
          <span>{{ item.label ?? ActionLabels[item.action] ?? item.action }}</span>
        </button>
      </template>
    </Dropdown>
  </div>

  <!-- ===== MODO ÚNICO (compatibilidade) ===== -->
  <template v-else-if="shouldRenderSingle">
    <input
      v-if="isUploadButton"
      ref="fileInputRef"
      type="file"
      class="hidden"
      :accept="uploadAccept"
      :multiple="uploadMultiple"
      @change="handleFileChange"
    />
    <ButtonIcon
      v-if="!showLabel"
      :icon="computedIcon"
      :variant="computedIconVariant"
      :size="size"
      :disabled="buttonDisabled"
      :type="type"
      :title="tooltipTitle"
      @click="handleClick"
    />
    <Button
      v-else
      :variant="computedVariant"
      :size="size"
      :icon="computedIcon"
      :icon-position="iconPosition"
      :disabled="buttonDisabled"
      :loading="buttonLoading"
      :type="type"
      :full-width="fullWidth"
      :title="tooltipTitle"
      @click="handleClick"
    >
      <slot>{{ computedLabel }}</slot>
    </Button>
  </template>
</template>

<script setup>
/**
 * ActionButton — fonte unica de botoes de acao.
 *
 * MODO UNICO: <ActionButton module="pae" resource="protocolos" action="edit" @click="..." />
 * MODO GRUPO: <ActionButton module="pae" resource="protocolos" :actions="[...]" />
 *
 * Permissao: convention over configuration.
 * Slug calculado = `${module}.${resource}.${ACTION_ALIAS[action] ?? action}`.
 * Verificado via usePermissions().can(slug).
 *
 * Override: prop `allowed` (booleano) faz AND com a checagem RBAC.
 *
 * UI-only actions (`options`, `warning`, `notifications`) nao pedem slug.
 */
import { computed, markRaw, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Button from './Button.vue';
import ButtonIcon from './ButtonIcon.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { usePermissions } from '@/composables/auth';

import PlusIcon from '../../Icons/PlusIcon.vue';
import EyeIcon from '../../Icons/EyeIcon.vue';
import PencilIcon from '../../Icons/PencilIcon.vue';
import TrashIcon from '../../Icons/TrashIcon.vue';
import PrinterIcon from '../../Icons/PrinterIcon.vue';
import DownloadIcon from '../../Icons/DownloadIcon.vue';
import DocumentIcon from '../../Icons/DocumentIcon.vue';
import DocumentTextIcon from '../../Icons/DocumentTextIcon.vue';
import CheckIcon from '../../Icons/CheckIcon.vue';
import ArchiveBoxIcon from '../../Icons/ArchiveBoxIcon.vue';
import UploadIcon from '../../Icons/UploadIcon.vue';
import PaperClipIcon from '../../Icons/PaperClipIcon.vue';
import ClockIcon from '../../Icons/ClockIcon.vue';
import ExclamationIcon from '../../Icons/ExclamationIcon.vue';
import EllipsisVerticalIcon from '../../Icons/EllipsisVerticalIcon.vue';
import UserIcon from '../../Icons/UserIcon.vue';
import BellIcon from '../../Icons/BellIcon.vue';

// ===== METADATA =====

const ActionIcons = {
  create: markRaw(PlusIcon),
  view: markRaw(EyeIcon),
  edit: markRaw(PencilIcon),
  delete: markRaw(TrashIcon),
  print: markRaw(PrinterIcon),
  export: markRaw(DownloadIcon),
  duplicate: markRaw(DocumentIcon),
  finalize: markRaw(CheckIcon),
  check: markRaw(CheckIcon),
  pdf: markRaw(DocumentTextIcon),
  archive: markRaw(ArchiveBoxIcon),
  upload: markRaw(UploadIcon),
  attachments: markRaw(PaperClipIcon),
  history: markRaw(ClockIcon),
  warning: markRaw(ExclamationIcon),
  options: markRaw(EllipsisVerticalIcon),
  assign: markRaw(UserIcon),
  notifications: markRaw(BellIcon),
};

const ActionLabels = {
  create: 'Novo',
  view: 'Visualizar',
  edit: 'Editar',
  delete: 'Excluir',
  print: 'Imprimir',
  export: 'Exportar',
  duplicate: 'Duplicar',
  finalize: 'Finalizar',
  check: 'Validar',
  pdf: 'PDF',
  archive: 'Arquivar',
  upload: 'Upload',
  attachments: 'Anexos',
  history: 'Historico',
  warning: 'Aviso',
  options: 'Opcoes',
  assign: 'Atribuir',
  notifications: 'Notificacoes',
};

const ActionVariants = {
  create: 'primary',
  view: 'primary',
  edit: 'warning',
  delete: 'danger',
  print: 'info',
  export: 'secondary',
  duplicate: 'secondary',
  finalize: 'success',
  check: 'success',
  pdf: 'danger',
  archive: 'warning',
  upload: 'black',
  attachments: 'success',
  history: 'info',
  warning: 'warning',
  options: 'secondary',
  assign: 'info',
  notifications: 'secondary',
};

const ActionIconVariants = {
  create: 'primary',
  view: 'primary',
  edit: 'warning',
  delete: 'vibrant-danger',
  print: 'info',
  export: 'secondary',
  duplicate: 'secondary',
  finalize: 'success',
  check: 'success',
  pdf: 'vibrant-danger',
  archive: 'topaz',
  upload: 'black',
  attachments: 'success',
  history: 'success',
  warning: 'vibrant-warning',
  options: 'secondary',
  assign: 'info',
  notifications: 'secondary',
};

// Mapa de aliases: action key visual -> verbo do slug
const ACTION_ALIAS = {
  check: 'validar',
  archive: 'arquivar',
  assign: 'atribuir',
};

// Acoes UI-only nao pedem slug
const UI_ONLY_ACTIONS = ['options', 'warning', 'notifications'];

// ===== PROPS / EMITS =====

const props = defineProps({
  module: { type: String, default: 'global' },
  resource: { type: String, default: null },
  action: {
    type: String,
    default: null,
    validator: (value) => value === null || [
      'create', 'view', 'edit', 'delete', 'print',
      'export', 'duplicate', 'finalize', 'attachments',
      'history', 'archive', 'upload', 'warning',
      'options', 'assign', 'notifications', 'check', 'pdf'
    ].includes(value),
  },
  actions: { type: Array, default: null },
  allowed: { type: Boolean, default: null },
  fallback: { type: String, default: 'hide', validator: v => ['hide', 'disable'].includes(v) },
  variant: { type: String, default: null },
  label: { type: String, default: null },
  showLabel: { type: Boolean, default: true },
  icon: { type: [Object, Function], default: null },
  size: { type: String, default: 'md' },
  iconPosition: { type: String, default: 'left' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
  fullWidth: { type: Boolean, default: false },
  tooltipText: { type: String, default: '' },
  uploadUrl: { type: String, default: null },
  uploadFieldName: { type: String, default: 'arquivo' },
  uploadData: { type: Object, default: () => ({}) },
  uploadAccept: { type: String, default: '' },
  uploadMultiple: { type: Boolean, default: false },
  uploadOptions: { type: Object, default: () => ({}) },
});

const emit = defineEmits([
  'click',
  'files-selected',
  'upload-start',
  'upload-success',
  'upload-error',
  'upload-finish',
]);

const fileInputRef = ref(null);
const isUploading = ref(false);

const { can } = usePermissions();
const page = usePage();

// ===== PERMISSION CORE =====

const isSuperAdmin = computed(() => page.props.auth?.user?.is_super_admin === true);

function hasPermissionFor({ action, module = props.module, resource = props.resource, allowed = null }) {
  if (isSuperAdmin.value) return true;
  if (UI_ONLY_ACTIONS.includes(action)) return true;
  if (!resource) return allowed === true;

  const slugAction = ACTION_ALIAS[action] ?? action;
  const canByPerm = can(`${module}.${resource}.${slugAction}`);

  if (allowed !== null) return canByPerm && allowed;
  return canByPerm;
}

// ===== MODO GRUPO =====

const isGroupMode = computed(() => Array.isArray(props.actions));

const visibleInlineActions = computed(() =>
  (props.actions ?? [])
    .filter(a => (a.placement ?? 'inline') === 'inline')
    .filter(a => hasPermissionFor({
      action: a.action,
      module: a.module,
      resource: a.resource,
      allowed: a.allowed ?? null,
    }))
);

const visibleMenuActions = computed(() =>
  (props.actions ?? [])
    .filter(a => a.placement === 'menu')
    .filter(a => hasPermissionFor({
      action: a.action,
      module: a.module,
      resource: a.resource,
      allowed: a.allowed ?? null,
    }))
);

// ===== MODO ÚNICO =====

const hasPermissionSingle = computed(() => hasPermissionFor({
  action: props.action,
  module: props.module,
  resource: props.resource,
  allowed: props.allowed,
}));

const shouldRenderSingle = computed(() => {
  if (props.fallback === 'hide') return hasPermissionSingle.value;
  return true;
});

const isDisabledSingle = computed(() => {
  if (props.fallback === 'disable') return !hasPermissionSingle.value;
  return false;
});

const isUploadButton = computed(() => props.action === 'upload' && !!props.uploadUrl);

const buttonDisabled = computed(() => isDisabledSingle.value || props.disabled || props.loading || isUploading.value);
const buttonLoading = computed(() => props.loading || isUploading.value);

const computedVariant = computed(() => props.variant || ActionVariants[props.action] || 'primary');
const computedIconVariant = computed(() => props.variant || ActionIconVariants[props.action] || 'secondary');
const computedIcon = computed(() => props.icon || ActionIcons[props.action] || null);
const computedLabel = computed(() => props.label !== null ? props.label : ActionLabels[props.action] || '');

const tooltipTitle = computed(() => {
  if (!hasPermissionSingle.value && props.fallback === 'disable') {
    return props.tooltipText || 'Voce nao possui permissao para esta acao';
  }
  return props.tooltipText || computedLabel.value || '';
});

const handleClick = (event) => {
  if (!hasPermissionSingle.value || buttonDisabled.value) return;
  emit('click', event);
  if (isUploadButton.value) fileInputRef.value?.click();
};

// ===== UPLOAD (preservado) =====

const appendFormValue = (formData, key, value) => {
  if (value === null || value === undefined) return;
  if (Array.isArray(value)) {
    value.forEach((item) => appendFormValue(formData, `${key}[]`, item));
    return;
  }
  formData.append(key, value);
};

const buildUploadPayload = (files) => {
  const formData = new FormData();
  Object.entries(props.uploadData).forEach(([key, value]) => {
    appendFormValue(formData, key, value);
  });
  files.forEach((file) => {
    formData.append(props.uploadFieldName, file);
  });
  return formData;
};

const uploadFiles = (files) => {
  if (!files.length || !props.uploadUrl) return;

  isUploading.value = true;
  emit('upload-start', files);

  router.post(props.uploadUrl, buildUploadPayload(files), {
    preserveScroll: true,
    forceFormData: true,
    ...props.uploadOptions,
    onSuccess: (pageObj) => {
      props.uploadOptions.onSuccess?.(pageObj);
      emit('upload-success', { files, page: pageObj });
    },
    onError: (errors) => {
      props.uploadOptions.onError?.(errors);
      emit('upload-error', { files, errors });
    },
    onFinish: (visit) => {
      props.uploadOptions.onFinish?.(visit);
      isUploading.value = false;
      emit('upload-finish', { files, visit });
    },
  });
};

const handleFileChange = (event) => {
  const files = Array.from(event.target.files || []);
  emit('files-selected', files);
  uploadFiles(files);
  event.target.value = '';
};
</script>
```

- [ ] **Step 2: Validar sintaxe Vue**

Não tem linter de Vue rodando localmente. Validar visualmente:
- `<template>` tem fechamento correto.
- `<script setup>` está balanceado.
- Imports sem duplicidade.
- `defineProps` sem chave faltando.

---

### Task 2: Migrar telas (18 arquivos, agrupadas por módulo)

Cada bloco abaixo é uma sub-tarefa atômica para um subagente.

**Files (consolidado):**
- 18 arquivos `.vue`, listados em sub-tarefas 2.1 a 2.9.

#### 2.1 — PAE Protocolos (2 arquivos)

- [ ] **Step 1: Migrar `PaeProtocolosTable.vue`**

Arquivo: `SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue`

Substituir o elemento `<TableActions ...>` por:

```vue
<ActionButton
  module="pae"
  resource="protocolos"
  :actions="[
    { action: 'edit',    handler: () => $emit('edit', protocolo),    allowed: canEdit },
    { action: 'history', handler: () => $emit('history', protocolo) },
    { action: 'archive', handler: () => $emit('archive', protocolo) },
    { action: 'delete',  handler: () => $emit('delete', protocolo),  allowed: canDelete },
    { action: 'check',  placement: 'menu', handler: () => $emit('check', protocolo),  allowed: canCheck },
    { action: 'pdf',    placement: 'menu', handler: () => $emit('pdf', protocolo),    allowed: canPdf },
    { action: 'assign', placement: 'menu', handler: () => $emit('assign', protocolo), allowed: canAtribuir && isAssignableStatus(protocolo.situacao) },
  ]"
/>
```

Verificar e remover `import TableActions from ...` no `<script setup>`. Adicionar `import ActionButton from '@/Components/Atoms/Button/ActionButton.vue'` se não existir.

- [ ] **Step 2: Migrar `PaeProtocoloCard.vue`**

Arquivo: `SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue`

Substituir por:

```vue
<ActionButton
  module="pae"
  resource="protocolos"
  :actions="[
    { action: 'view',          handler: () => $emit('view', protocolo) },
    { action: 'print',         handler: () => $emit('print', protocolo) },
    { action: 'edit',          handler: () => $emit('edit', protocolo),          allowed: canEdit },
    { action: 'history',       handler: () => $emit('history', protocolo) },
    { action: 'notifications', handler: () => $emit('notifications', protocolo) },
    { action: 'archive',       handler: () => $emit('archive', protocolo),       allowed: canDelete },
    { action: 'check',  placement: 'menu', handler: () => $emit('check', protocolo),  allowed: canCheck },
    { action: 'pdf',    placement: 'menu', handler: () => $emit('pdf', protocolo),    allowed: canPdf },
    { action: 'assign', placement: 'menu', handler: () => $emit('assign', protocolo), allowed: canAtribuir && isAssignableStatus(protocolo.situacao) },
  ]"
/>
```

#### 2.2 — RAT (2 arquivos)

- [ ] **Step 1: Migrar `RatTableRow.vue`**

Arquivo: `SDC/resources/js/Components/Organisms/Rat/Table/RatTableRow.vue`

Substituir por:

```vue
<ActionButton
  module="rat"
  resource="protocolos"
  :actions="[
    { action: 'view',        handler: () => $emit('view', rat) },
    { action: 'print',       handler: () => $emit('print', rat) },
    { action: 'edit',        handler: () => $emit('edit', rat),        allowed: canEdit },
    { action: 'attachments', handler: () => $emit('attachments', rat), label: 'Relacionar' },
    { action: 'delete',      handler: () => $emit('delete', rat),      allowed: canDelete },
  ]"
/>
```

- [ ] **Step 2: Migrar `RatCard.vue`**

Arquivo: `SDC/resources/js/Components/Molecules/Rat/RatCard.vue`

Substituir por:

```vue
<ActionButton
  module="rat"
  resource="protocolos"
  size="md"
  :actions="[
    { action: 'view',        handler: () => $emit('view', rat) },
    { action: 'print',       handler: () => $emit('print', rat) },
    { action: 'edit',        handler: () => $emit('edit', rat),        allowed: canEdit },
    { action: 'attachments', handler: () => $emit('attachments', rat), label: 'Relacionar' },
    { action: 'delete',      handler: () => $emit('delete', rat),      allowed: canDelete },
  ]"
/>
```

#### 2.3 — Decretações (2 arquivos)

- [ ] **Step 1: Migrar `ProcessoTable.vue`**

Arquivo: `SDC/resources/js/Components/Organisms/Decretacoes/ProcessoTable.vue`

```vue
<ActionButton
  module="decretacoes"
  resource="processos"
  :actions="[
    { action: 'view',    handler: () => $emit('view', processo) },
    { action: 'print',   handler: () => $emit('print', processo) },
    { action: 'edit',    handler: () => $emit('edit', processo),    allowed: canEdit },
    { action: 'delete',  handler: () => $emit('delete', processo),  allowed: canDelete },
    { action: 'warning', handler: () => $emit('warning', processo) },
  ]"
/>
```

- [ ] **Step 2: Migrar `ProcessoCard.vue`**

Arquivo: `SDC/resources/js/Components/Molecules/Decretacoes/ProcessoCard.vue`

```vue
<ActionButton
  module="decretacoes"
  resource="processos"
  :actions="[
    { action: 'view',   handler: () => $emit('view', processo) },
    { action: 'print',  handler: () => $emit('print', processo) },
    { action: 'edit',   handler: () => $emit('edit', processo),   allowed: canEdit },
    { action: 'delete', handler: () => $emit('delete', processo), allowed: canDelete },
  ]"
/>
```

#### 2.4 — Ajuda Humanitária (2 arquivos)

- [ ] **Step 1: Migrar `BeneficiarioIndexTemplate.vue`**

Arquivo: `SDC/resources/js/Templates/AjudaHumanitaria/BeneficiarioIndexTemplate.vue`

```vue
<ActionButton
  module="humanitaria"
  resource="beneficiarios"
  size="sm"
  :actions="[
    { action: 'view',   handler: () => $emit('view', beneficiario) },
    { action: 'print',  handler: () => $emit('print', beneficiario) },
    { action: 'edit',   handler: () => $emit('edit', beneficiario),   allowed: canEdit },
    { action: 'delete', handler: () => $emit('delete', beneficiario), allowed: canDelete },
  ]"
/>
```

Nota: o `module` muda de `ajuda-humanitaria` (atual no template) para `humanitaria` (alinhado com slug do config).

- [ ] **Step 2: Migrar `BeneficiarioCard.vue`**

Arquivo: `SDC/resources/js/Components/Molecules/AjudaHumanitaria/BeneficiarioCard.vue`

```vue
<ActionButton
  module="humanitaria"
  resource="beneficiarios"
  :actions="[
    { action: 'view',   handler: () => $emit('view', beneficiario) },
    { action: 'print',  handler: () => $emit('print', beneficiario) },
    { action: 'edit',   handler: () => $emit('edit', beneficiario),   allowed: canEdit },
    { action: 'delete', handler: () => $emit('delete', beneficiario), allowed: canDelete },
  ]"
/>
```

#### 2.5 — Plantão (2 arquivos)

- [ ] **Step 1: Migrar `PlantaoTable.vue`**

Arquivo: `SDC/resources/js/Components/Organisms/Plantao/PlantaoTable.vue`

Nota: o frontend usa `resource="plantoes"`. No PR #3 mudaremos o config também. Por agora, o slug `plantao.plantoes.*` **não existe** no banco — portanto `can()` retornará false e os botões somem. Como tela já está em produção, **manter compatibilidade temporária** passando `allowed: canEdit` (que já vem do parent) e deixando o RBAC fail-closed naturalmente.

```vue
<ActionButton
  module="plantao"
  resource="turnos"
  :actions="[
    { action: 'view',   handler: () => $emit('view', plantao) },
    { action: 'edit',   handler: () => $emit('edit', plantao),   allowed: canEdit },
    { action: 'delete', handler: () => $emit('delete', plantao), allowed: canDelete },
  ]"
/>
```

**Decisão técnica**: usar `resource="turnos"` para casar com slug atual do config (`plantao.turnos.*`). O PR #4 vai inverter (renomear no config + frontend).

- [ ] **Step 2: Migrar `PlantaoGrid.vue`**

Arquivo: `SDC/resources/js/Components/Organisms/Plantao/PlantaoGrid.vue`

Mesma substituição da `PlantaoTable.vue`, ajustando para o contexto do loop.

#### 2.6 — Cisternas (1 arquivo)

- [ ] **Step 1: Migrar `CisternaTable.vue`**

Arquivo: `SDC/resources/js/Components/Organisms/Cisterna/CisternaTable.vue`

```vue
<ActionButton
  module="cisternas"
  resource=""
  size="sm"
  :actions="[
    { action: 'view',   handler: () => $emit('show', cisterna) },
    { action: 'edit',   handler: () => $emit('edit', cisterna),   allowed: canEdit },
    { action: 'delete', handler: () => $emit('delete', cisterna), allowed: canDelete },
  ]"
/>
```

Nota: `cisternas` no config é módulo de um único nível (`cisternas.view`, `cisternas.create`, etc — sem `resource`). Para o ActionButton entender, passar `resource=""` e modificar `hasPermissionFor` para construir `module.action` quando `resource` é vazio.

**Importante**: a versão do ActionButton da Task 1 NÃO suporta esse caso. Vou ajustar:

Editar o `hasPermissionFor` no `ActionButton.vue` (Task 1, função `hasPermissionFor`) para tratar `resource === ''` (string vazia) como "sem resource — usa `module.action`":

```js
function hasPermissionFor({ action, module = props.module, resource = props.resource, allowed = null }) {
  if (isSuperAdmin.value) return true;
  if (UI_ONLY_ACTIONS.includes(action)) return true;

  const slugAction = ACTION_ALIAS[action] ?? action;

  // resource vazio/null: slug = module.action (caso cisternas)
  let slug;
  if (resource === '' || resource === null || resource === undefined) {
    if (!module || module === 'global') return allowed === true;
    slug = `${module}.${slugAction}`;
  } else {
    slug = `${module}.${resource}.${slugAction}`;
  }

  const canByPerm = can(slug);
  if (allowed !== null) return canByPerm && allowed;
  return canByPerm;
}
```

Substituir o trecho anterior em ActionButton.vue por esta versão revisada.

#### 2.7 — Treinamento (2 arquivos)

- [ ] **Step 1: Migrar `TreinamentoIndexTemplate.vue`**

Arquivo: `SDC/resources/js/Templates/Treinamento/TreinamentoIndexTemplate.vue`

```vue
<ActionButton
  module="treinamento"
  resource="cursos"
  size="sm"
  :actions="[
    { action: 'view',   handler: () => $emit('view', treinamento) },
    { action: 'edit',   handler: () => $emit('edit', treinamento),   allowed: canEdit },
    { action: 'delete', handler: () => $emit('delete', treinamento), allowed: canDelete },
  ]"
/>
```

Nota: módulo é `treinamento` e resource é `cursos` (já alinhado com config — frontend antigo usava `treinamentos.treinamentos` mas vamos corrigir aqui).

- [ ] **Step 2: Migrar `TreinamentoCard.vue`**

Arquivo: `SDC/resources/js/Components/Molecules/Treinamento/TreinamentoCard.vue`

Mesmo padrão da Index.

#### 2.8 — COMPDEC (3 arquivos)

- [ ] **Step 1: Migrar `OrgaosIndex.vue`**

Arquivo: `SDC/resources/js/Pages/Compdec/OrgaosIndex.vue`

```vue
<ActionButton
  module="compdec"
  resource="orgaos"
  :actions="[
    { action: 'view',   handler: () => $emit('view', orgao) },
    { action: 'edit',   handler: () => $emit('edit', orgao),   allowed: canManage },
    { action: 'delete', handler: () => $emit('delete', orgao), allowed: canDeleteOrgao(orgao) },
  ]"
/>
```

- [ ] **Step 2: Migrar `OrgaoShow.vue`**

Arquivo: `SDC/resources/js/Pages/Compdec/OrgaoShow.vue`

```vue
<ActionButton
  module="compdec"
  resource="usuarios"
  size="sm"
  :actions="[
    { action: 'delete', handler: () => $emit('delete', usuario), allowed: canVincularUsuarios, label: 'Desvincular' },
  ]"
/>
```

Mapear `delete` para usar slug `compdec.usuarios.desvincular`? Não — `delete` → slug `compdec.usuarios.delete`. Como queremos `desvincular`, precisamos ajustar o item:

```vue
{ action: 'delete', handler: () => $emit('delete', usuario), allowed: canVincularUsuarios, label: 'Desvincular' }
```

Para esse caso, criar um `ACTION_ALIAS_OVERRIDE` por item... ou simplesmente passar `resource` que faça o slug funcionar. Decisão pragmática: usar `:allowed="can('compdec.usuarios.desvincular') && canVincularUsuarios"` explicitamente no parent, OU adicionar uma sub-prop no item: `aliasOverride: 'desvincular'`.

**Decisão final**: adicionar suporte a item-level alias no `ActionButton.vue`. Estender `hasPermissionFor`:

```js
const slugAction = item.aliasOverride ?? ACTION_ALIAS[action] ?? action;
```

E uso fica:

```vue
{ action: 'delete', aliasOverride: 'desvincular', handler: () => $emit('delete', usuario), allowed: canVincularUsuarios, label: 'Desvincular' }
```

(Adicionar nessa Task 2.8 a edição do `hasPermissionFor` em `ActionButton.vue`.)

- [ ] **Step 3: Migrar `EquipeTable.vue`**

Arquivo: `SDC/resources/js/Components/Organisms/Compdec/EquipeTable.vue`

```vue
<ActionButton
  module="compdec"
  resource="equipe"
  size="sm"
  :actions="[
    { action: 'edit',   handler: () => $emit('edit', membro),   allowed: canEdit },
    { action: 'delete', handler: () => $emit('delete', membro), allowed: canDelete },
  ]"
/>
```

#### 2.9 — Admin/Permissions (2 arquivos)

- [ ] **Step 1: Migrar `Users/Index.vue`**

Arquivo: `SDC/resources/js/Pages/Admin/Permissions/Users/Index.vue`

Duas ocorrências (linhas 104-114 e 217-227). Substituir AMBAS por:

```vue
<ActionButton
  module="users"
  resource=""
  size="sm"
  :actions="[
    { action: 'view',   handler: () => $emit('view', user) },
    { action: 'edit',   handler: () => $emit('edit', user),   allowed: canEdit },
    { action: 'delete', handler: () => $emit('delete', user), allowed: canDelete },
  ]"
/>
```

Nota: `users` é módulo sem resource (`users.view`, `users.edit`, etc.) — passar `resource=""` igual ao caso de Cisternas.

- [ ] **Step 2: Migrar `Roles/Index.vue`**

Arquivo: `SDC/resources/js/Pages/Admin/Permissions/Roles/Index.vue`

```vue
<ActionButton
  module="roles"
  resource=""
  size="sm"
  :actions="[
    { action: 'view',   handler: () => $emit('view', role) },
    { action: 'edit',   handler: () => $emit('edit', role),   allowed: canEdit && !role.is_immutable },
    { action: 'delete', handler: () => $emit('delete', role), allowed: canDelete && !role.is_immutable && role.users_count === 0 },
  ]"
/>
```

---

### Task 3: Deletar arquivos órfãos

**Files:**
- Delete: `SDC/resources/js/Components/Molecules/Table/TableActions.vue`
- Delete: `SDC/resources/js/Components/Molecules/Table/SmartTableActions.vue`
- Delete: `SDC/resources/js/composables/ui/useActionConfig.js`
- Modify: `SDC/resources/js/composables/ui/index.js`

- [ ] **Step 1: Verificar zero referências**

```
grep -rn "TableActions" SDC/resources/js/ --include="*.vue" --include="*.js"
grep -rn "useActionConfig" SDC/resources/js/ --include="*.vue" --include="*.js"
```

Expected: zero ocorrências (após Task 2 completa). Se houver, voltar para Task 2.

- [ ] **Step 2: Deletar os 3 arquivos**

```
rm SDC/resources/js/Components/Molecules/Table/TableActions.vue
rm SDC/resources/js/Components/Molecules/Table/SmartTableActions.vue
rm SDC/resources/js/composables/ui/useActionConfig.js
```

- [ ] **Step 3: Limpar exports em `composables/ui/index.js`**

Ler o arquivo, identificar o re-export de `useActionConfig` e remover. Manter os demais exports intactos.

---

### Task 4: Validação

**Files:** N/A

- [ ] **Step 1: Build do frontend**

```
cd SDC && npm run build
```

Ou Bun se for o caso (verificar `package.json`). Expected: build OK sem erros.

- [ ] **Step 2: Smoke test visual de 3 telas em ambiente local**

Abrir em browser:
- `/admin/permissions/users` — botões edit/delete aparecem para admin, não para viewer.
- `/pae/protocolos` — botões inline + dropdown "três pontos" com Validar/PDF/Atribuir Analista para manager.
- `/rat` — botões view/print/edit/attachments/delete aparecem para analyst.

- [ ] **Step 3: Confirmar zero referências TableActions/SmartTableActions/useActionConfig**

```
grep -rn "TableActions\|SmartTableActions\|useActionConfig" SDC/resources/js/
```

Expected: zero linhas.

---

### Task 5: Commit + push

**Files:** N/A

- [ ] **Step 1: Stage seletivo**

```
git add SDC/resources/js/Components/Atoms/Button/ActionButton.vue
git add SDC/resources/js/composables/ui/index.js
git add SDC/resources/js/Components/Organisms/Pae/Protocolos/PaeProtocolosTable.vue
git add SDC/resources/js/Components/Molecules/Pae/Protocolos/PaeProtocoloCard.vue
git add SDC/resources/js/Components/Organisms/Rat/Table/RatTableRow.vue
git add SDC/resources/js/Components/Molecules/Rat/RatCard.vue
git add SDC/resources/js/Components/Organisms/Decretacoes/ProcessoTable.vue
git add SDC/resources/js/Components/Molecules/Decretacoes/ProcessoCard.vue
git add SDC/resources/js/Templates/AjudaHumanitaria/BeneficiarioIndexTemplate.vue
git add SDC/resources/js/Components/Molecules/AjudaHumanitaria/BeneficiarioCard.vue
git add SDC/resources/js/Components/Organisms/Plantao/PlantaoTable.vue
git add SDC/resources/js/Components/Organisms/Plantao/PlantaoGrid.vue
git add SDC/resources/js/Components/Organisms/Cisterna/CisternaTable.vue
git add SDC/resources/js/Templates/Treinamento/TreinamentoIndexTemplate.vue
git add SDC/resources/js/Components/Molecules/Treinamento/TreinamentoCard.vue
git add SDC/resources/js/Pages/Compdec/OrgaosIndex.vue
git add SDC/resources/js/Pages/Compdec/OrgaoShow.vue
git add SDC/resources/js/Components/Organisms/Compdec/EquipeTable.vue
git add SDC/resources/js/Pages/Admin/Permissions/Users/Index.vue
git add SDC/resources/js/Pages/Admin/Permissions/Roles/Index.vue
git add docs/superpowers/plans/2026-05-28-pr2-actionbutton-refactor.md
git rm SDC/resources/js/Components/Molecules/Table/TableActions.vue
git rm SDC/resources/js/Components/Molecules/Table/SmartTableActions.vue
git rm SDC/resources/js/composables/ui/useActionConfig.js
```

- [ ] **Step 2: Commit**

```
git commit -m "feat(permissions): centraliza logica de botoes no ActionButton e remove TableActions/SmartTableActions/useActionConfig

ActionButton agora suporta:
- Modo unico (compativel com uso atual)
- Modo grupo via prop :actions (substitui TableActions)
- Auto-checagem RBAC via can(module.resource.action) com aliases (check->validar, archive->arquivar, assign->atribuir)
- :allowed como AND com permissao (escape hatch para regras de negocio)
- UI_ONLY_ACTIONS (options/warning/notifications) sempre liberadas
- Suporte a modulos sem resource (cisternas, users, roles) via resource=''

Migracao de 18 telas de TableActions/SmartTableActions para ActionButton modo grupo.

Removidos arquivos orfaos:
- Components/Molecules/Table/TableActions.vue
- Components/Molecules/Table/SmartTableActions.vue
- composables/ui/useActionConfig.js

Fase 2 da padronizacao das 3 camadas de permissoes (config -> DB -> frontend)."
```

- [ ] **Step 3: Push**

```
git push -u origin feat/permissions-actionbutton-refactor
```

---

## Critério de sucesso do PR #2

- `npm run build` passa sem erros.
- Zero referências a `TableActions`, `SmartTableActions`, `useActionConfig` no codebase.
- Em browser: telas de PAE Protocolos, RAT, Decretações, Humanitária, Plantão, Cisternas, Treinamento, COMPDEC, Users/Roles renderizam os botões corretamente.
- Botões aparecem/desaparecem de acordo com slug do usuário (testar com admin x analyst x viewer).
- Dropdown "três pontos" funciona em PAE Protocolos.
- 18 arquivos `.vue` modificados + 3 deletados + 1 modificado (composables/ui/index.js) + ActionButton.vue + plano = ~22 entradas no commit.
