<template>
  <AuthenticatedLayout>
    <div class="permissions-container">
      <div class="page-header">
        <div class="header-content">
          <div class="header-left">
            <Link :href="route('admin.permissions.roles.index')" class="back-link">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              Voltar
            </Link>
            <h1 class="page-title">Criar Novo Cargo</h1>
            <p class="page-subtitle">Defina nome, descrição e permissões do cargo</p>
          </div>
        </div>
      </div>

      <form @submit.prevent="submitForm" class="content-grid">
        <div class="main-content">
          <div class="section-card">
            <div class="section-header">
              <h3 class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Informações Básicas
              </h3>
            </div>

            <div class="form-grid">
              <div class="form-group full-width">
                <label for="name" class="form-label">Nome do Cargo *</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="form-input"
                  :class="{ 'input-error': errors.name }"
                  placeholder="Ex: Administrador, Gestor, Analista"
                  required
                />
                <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
              </div>

              <div class="form-group">
                <label for="slug" class="form-label">Slug (Identificador) *</label>
                <input
                  id="slug"
                  v-model="form.slug"
                  type="text"
                  class="form-input"
                  :class="{ 'input-error': errors.slug }"
                  placeholder="Ex: admin, gestor, analista"
                  required
                />
                <span class="form-hint">Somente letras minúsculas, números e hífens</span>
                <span v-if="errors.slug" class="error-message">{{ errors.slug }}</span>
              </div>

              <div class="form-group">
                <label for="hierarchy_level" class="form-label">Nível Hierárquico *</label>
                <select
                  id="hierarchy_level"
                  v-model="form.hierarchy_level"
                  class="form-input"
                  :class="{ 'input-error': errors.hierarchy_level }"
                  required
                >
                  <option value="">Selecione um nível</option>
                  <option value="0">Nível 0 - Super Admin</option>
                  <option value="1">Nível 1 - Admin</option>
                  <option value="2">Nível 2 - Gestor</option>
                  <option value="3">Nível 3 - Operacional</option>
                  <option value="4">Nível 4 - Visualização</option>
                </select>
                <span class="form-hint">Quanto menor o número, maior o nível de acesso</span>
                <span v-if="errors.hierarchy_level" class="error-message">{{ errors.hierarchy_level }}</span>
              </div>

              <div class="form-group full-width">
                <label for="description" class="form-label">Descrição *</label>
                <textarea
                  id="description"
                  v-model="form.description"
                  class="form-textarea"
                  :class="{ 'input-error': errors.description }"
                  placeholder="Descreva as responsabilidades e escopo deste cargo"
                  rows="3"
                  required
                />
                <span v-if="errors.description" class="error-message">{{ errors.description }}</span>
              </div>

              <div class="form-group">
                <label class="checkbox-label">
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="checkbox-input"
                  />
                  <span class="checkbox-text">Cargo Ativo</span>
                </label>
                <span class="form-hint">Cargos inativos não podem ser atribuídos a novos usuários</span>
              </div>
            </div>
          </div>

          <div class="section-card">
            <div class="section-header">
              <h3 class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Permissões
              </h3>
              <span class="count-badge">{{ selectedPermissionsCount }}</span>
            </div>

            <div class="permissions-modules">
              <div v-for="(modulePermissions, moduleName) in groupedPermissions" :key="moduleName" class="permission-module-section">
                <div class="module-header" @click="toggleModule(moduleName)">
                  <div class="module-header-left">
                    <svg class="module-expand-icon" :class="{ 'expanded': expandedModules.includes(moduleName) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <h4 class="module-header-title">{{ formatModuleName(moduleName) }}</h4>
                  </div>
                  <div class="module-actions">
                    <button
                      type="button"
                      @click.stop="selectAllModule(moduleName)"
                      class="module-action-btn"
                    >
                      Selecionar Todos
                    </button>
                    <span class="module-count-badge">{{ getModuleSelectedCount(moduleName) }}/{{ modulePermissions.length }}</span>
                  </div>
                </div>

                <Transition name="expand">
                  <div v-if="expandedModules.includes(moduleName)" class="module-permissions-grid">
                    <label
                      v-for="permission in modulePermissions"
                      :key="permission.id"
                      class="permission-checkbox-label"
                    >
                      <input
                        type="checkbox"
                        :value="permission.id"
                        v-model="form.permissions"
                        class="permission-checkbox-input"
                      />
                      <span class="permission-checkbox-text">{{ formatPermissionName(permission.name) }}</span>
                      <span class="permission-checkbox-indicator"></span>
                    </label>
                  </div>
                </Transition>
              </div>
            </div>
          </div>
        </div>

        <div class="sidebar-content">
          <div class="sticky-sidebar">
            <div class="summary-card">
              <h3 class="summary-title">Resumo</h3>
              <div class="summary-items">
                <div class="summary-item">
                  <span class="summary-label">Permissões Selecionadas</span>
                  <span class="summary-value">{{ selectedPermissionsCount }}</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Módulos</span>
                  <span class="summary-value">{{ selectedModulesCount }}</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Nível Hierárquico</span>
                  <span class="summary-value">{{ form.hierarchy_level || '-' }}</span>
                </div>
              </div>
            </div>

            <div class="actions-card">
              <button type="submit" class="btn btn-primary btn-block" :disabled="form.processing">
                <svg v-if="!form.processing" class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="btn-spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle class="spinner-circle" cx="12" cy="12" r="10" stroke-width="4" />
                </svg>
                {{ form.processing ? 'Criando...' : 'Criar Cargo' }}
              </button>
              <Link :href="route('admin.permissions.roles.index')" class="btn btn-secondary btn-block">
                Cancelar
              </Link>
            </div>
          </div>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  availablePermissions: {
    type: Array,
    default: () => []
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const form = useForm({
  name: '',
  slug: '',
  hierarchy_level: '',
  description: '',
  is_active: true,
  permissions: []
});

const expandedModules = ref(['users', 'pae', 'rat']);

const groupedPermissions = computed(() => {
  return props.availablePermissions.reduce((acc, permission) => {
    const module = permission.name.split('.')[0];
    if (!acc[module]) {
      acc[module] = [];
    }
    acc[module].push(permission);
    return acc;
  }, {});
});

const selectedPermissionsCount = computed(() => form.permissions.length);

const selectedModulesCount = computed(() => {
  const modules = new Set();
  props.availablePermissions.forEach(permission => {
    if (form.permissions.includes(permission.id)) {
      const module = permission.name.split('.')[0];
      modules.add(module);
    }
  });
  return modules.size;
});

const toggleModule = (moduleName) => {
  const index = expandedModules.value.indexOf(moduleName);
  if (index > -1) {
    expandedModules.value.splice(index, 1);
  } else {
    expandedModules.value.push(moduleName);
  }
};

const selectAllModule = (moduleName) => {
  const modulePermissionIds = groupedPermissions.value[moduleName]?.map(p => p.id) || [];
  const allSelected = modulePermissionIds.every(id => form.permissions.includes(id));

  if (allSelected) {
    form.permissions = form.permissions.filter(id => !modulePermissionIds.includes(id));
  } else {
    const newPermissions = [...new Set([...form.permissions, ...modulePermissionIds])];
    form.permissions = newPermissions;
  }
};

const getModuleSelectedCount = (moduleName) => {
  const modulePermissionIds = groupedPermissions.value[moduleName]?.map(p => p.id) || [];
  return form.permissions.filter(id => modulePermissionIds.includes(id)).length;
};

const formatModuleName = (module) => {
  const moduleNames = {
    users: 'Usuários',
    roles: 'Cargos',
    permissions: 'Permissões',
    pae: 'PAE',
    rat: 'RAT',
    bi: 'Business Intelligence',
    integrations: 'Integrações',
    webhooks: 'Webhooks',
    system: 'Sistema'
  };
  return moduleNames[module] || module.toUpperCase();
};

const formatPermissionName = (name) => {
  const parts = name.split('.');
  const action = parts[parts.length - 1];
  const actionNames = {
    view: 'Visualizar',
    create: 'Criar',
    edit: 'Editar',
    delete: 'Deletar',
    manage: 'Gerenciar'
  };
  return actionNames[action] || action;
};

const submitForm = () => {
  form.post(route('admin.permissions.roles.store'), {
    preserveScroll: true
  });
};
</script>

<style scoped>
.permissions-container {
  padding: 2rem;
  background: #0f172a;
  min-height: 100vh;
}

.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.header-left {
  flex: 1;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #60a5fa;
  font-size: 0.9375rem;
  font-weight: 500;
  margin-bottom: 1rem;
  transition: all 0.2s;
}

.back-link:hover {
  color: #3b82f6;
  transform: translateX(-4px);
}

.back-link svg {
  width: 20px;
  height: 20px;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: #f1f5f9;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  font-size: 0.9375rem;
  color: #94a3b8;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 1.5rem;
  align-items: start;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 12px;
  padding: 1.5rem;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: #f1f5f9;
  flex: 1;
}

.section-icon {
  width: 20px;
  height: 20px;
  color: #60a5fa;
}

.count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 0.5rem;
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 700;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #cbd5e1;
}

.form-input,
.form-textarea {
  padding: 0.75rem 1rem;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  color: #f1f5f9;
  font-size: 0.9375rem;
  transition: all 0.2s;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input.input-error,
.form-textarea.input-error {
  border-color: #ef4444;
}

.form-hint {
  font-size: 0.8125rem;
  color: #64748b;
}

.error-message {
  color: #f87171;
  font-size: 0.8125rem;
  font-weight: 500;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
}

.checkbox-input {
  width: 18px;
  height: 18px;
  border-radius: 4px;
  border: 2px solid #334155;
  cursor: pointer;
}

.checkbox-text {
  font-size: 0.9375rem;
  color: #e2e8f0;
  font-weight: 500;
}

.permissions-modules {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.permission-module-section {
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  overflow: hidden;
}

.module-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  cursor: pointer;
  transition: all 0.2s;
}

.module-header:hover {
  background: rgba(59, 130, 246, 0.05);
}

.module-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.module-expand-icon {
  width: 16px;
  height: 16px;
  color: #64748b;
  transition: transform 0.2s;
}

.module-expand-icon.expanded {
  transform: rotate(90deg);
}

.module-header-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #e2e8f0;
}

.module-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.module-action-btn {
  padding: 0.375rem 0.75rem;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid #334155;
  border-radius: 6px;
  color: #60a5fa;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.module-action-btn:hover {
  background: rgba(59, 130, 246, 0.15);
  border-color: #3b82f6;
}

.module-count-badge {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #60a5fa;
}

.module-permissions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  padding: 1.25rem;
  border-top: 1px solid #334155;
}

.permission-checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.permission-checkbox-label:hover {
  border-color: #475569;
  background: rgba(59, 130, 246, 0.05);
}

.permission-checkbox-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.permission-checkbox-input:checked ~ .permission-checkbox-indicator {
  background: #3b82f6;
  border-color: #3b82f6;
}

.permission-checkbox-input:checked ~ .permission-checkbox-indicator::after {
  opacity: 1;
}

.permission-checkbox-text {
  flex: 1;
  font-size: 0.875rem;
  font-weight: 500;
  color: #e2e8f0;
}

.permission-checkbox-indicator {
  width: 18px;
  height: 18px;
  border: 2px solid #334155;
  border-radius: 4px;
  background: #0f172a;
  transition: all 0.2s;
  position: relative;
  flex-shrink: 0;
}

.permission-checkbox-indicator::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 10px;
  height: 10px;
  background: white;
  clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
  opacity: 0;
  transition: opacity 0.2s;
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  max-height: 500px;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
}

.sidebar-content {
  position: relative;
}

.sticky-sidebar {
  position: sticky;
  top: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-card,
.actions-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 12px;
  padding: 1.5rem;
}

.summary-title {
  font-size: 1rem;
  font-weight: 600;
  color: #f1f5f9;
  margin-bottom: 1.25rem;
}

.summary-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1rem;
  border-bottom: 1px solid #334155;
}

.summary-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.summary-label {
  font-size: 0.875rem;
  color: #94a3b8;
  font-weight: 500;
}

.summary-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #60a5fa;
}

.actions-card {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-block {
  width: 100%;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
  background: #334155;
  color: #e2e8f0;
}

.btn-secondary:hover {
  background: #475569;
}

.btn-icon {
  width: 18px;
  height: 18px;
}

.btn-spinner {
  width: 18px;
  height: 18px;
  animation: spin 1s linear infinite;
}

.spinner-circle {
  opacity: 0.25;
  stroke: currentColor;
  fill: none;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 1280px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .sticky-sidebar {
    position: relative;
    top: 0;
  }
}

@media (max-width: 768px) {
  .permissions-container {
    padding: 1rem;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .module-permissions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
