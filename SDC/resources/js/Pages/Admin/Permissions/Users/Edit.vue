<template>
  <AuthenticatedLayout>
    <div class="permissions-container">
      <div class="page-header">
        <div class="header-content">
          <div class="header-left">
            <Link :href="route('admin.permissions.users.show', user.id)" class="back-link">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              Voltar
            </Link>
            <h1 class="page-title">Editar Usuário</h1>
            <p class="page-subtitle">Atualize informações, cargos e permissões</p>
          </div>
        </div>
      </div>

      <form @submit.prevent="submitForm" class="content-grid">
        <div class="main-content">
          <div class="section-card">
            <div class="section-header">
              <h3 class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Informações Básicas
              </h3>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="name" class="form-label">Nome Completo</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="form-input"
                  :class="{ 'input-error': errors.name }"
                  placeholder="Digite o nome completo"
                  required
                />
                <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
              </div>

              <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="form-input"
                  :class="{ 'input-error': errors.email }"
                  placeholder="Digite o email"
                  required
                />
                <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
              </div>
            </div>
          </div>

          <div class="section-card">
            <div class="section-header">
              <h3 class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Cargos
              </h3>
              <span class="count-badge">{{ selectedRolesCount }}</span>
            </div>

            <div class="roles-selection">
              <div
                v-for="role in availableRoles"
                :key="role.id"
                class="role-checkbox-card"
                :class="{ 'selected': form.roles.includes(role.id), 'disabled': role.slug === 'super-admin' && !canEditSuperAdmin }"
              >
                <label class="role-checkbox-label">
                  <input
                    type="checkbox"
                    :value="role.id"
                    v-model="form.roles"
                    :disabled="role.slug === 'super-admin' && !canEditSuperAdmin"
                    class="role-checkbox-input"
                  />
                  <div class="role-checkbox-content">
                    <div class="role-checkbox-header">
                      <div class="role-icon-small" :class="`hierarchy-${role.hierarchy_level}`">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                          <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                        </svg>
                      </div>
                      <div class="role-checkbox-info">
                        <h4 class="role-checkbox-name">{{ role.name }}</h4>
                        <p class="role-checkbox-description">{{ role.description }}</p>
                      </div>
                      <div class="checkbox-indicator">
                        <svg v-if="form.roles.includes(role.id)" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                      </div>
                    </div>
                    <div class="role-checkbox-footer">
                      <span class="role-hierarchy-badge">Nível {{ role.hierarchy_level }}</span>
                      <span class="role-permissions-badge">{{ role.permissions_count || 0 }} permissões</span>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <div class="section-card">
            <div class="section-header">
              <h3 class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Permissões Diretas
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
                  <span class="module-count-badge">{{ getModuleSelectedCount(moduleName) }}/{{ modulePermissions.length }}</span>
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
                        v-model="form.direct_permissions"
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
              <h3 class="summary-title">Resumo das Alterações</h3>
              <div class="summary-items">
                <div class="summary-item">
                  <span class="summary-label">Cargos Selecionados</span>
                  <span class="summary-value">{{ selectedRolesCount }}</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Permissões Diretas</span>
                  <span class="summary-value">{{ selectedPermissionsCount }}</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Total de Permissões</span>
                  <span class="summary-value">{{ totalPermissionsCount }}</span>
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
                {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
              </button>
              <Link :href="route('admin.permissions.users.show', user.id)" class="btn btn-secondary btn-block">
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
  user: {
    type: Object,
    required: true
  },
  availableRoles: {
    type: Array,
    default: () => []
  },
  availablePermissions: {
    type: Array,
    default: () => []
  },
  canEditSuperAdmin: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  roles: props.user.roles?.map(r => r.id) || [],
  direct_permissions: props.user.direct_permissions?.map(p => p.id) || []
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

const selectedRolesCount = computed(() => form.roles.length);

const selectedPermissionsCount = computed(() => form.direct_permissions.length);

const totalPermissionsCount = computed(() => {
  const directCount = form.direct_permissions.length;
  const rolePermissions = props.availableRoles
    .filter(role => form.roles.includes(role.id))
    .reduce((sum, role) => sum + (role.permissions_count || 0), 0);
  return directCount + rolePermissions;
});

const toggleModule = (moduleName) => {
  const index = expandedModules.value.indexOf(moduleName);
  if (index > -1) {
    expandedModules.value.splice(index, 1);
  } else {
    expandedModules.value.push(moduleName);
  }
};

const getModuleSelectedCount = (moduleName) => {
  const modulePermissionIds = groupedPermissions.value[moduleName]?.map(p => p.id) || [];
  return form.direct_permissions.filter(id => modulePermissionIds.includes(id)).length;
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
  form.put(route('admin.permissions.users.update', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      console.log('User updated successfully');
    }
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

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #cbd5e1;
}

.form-input {
  padding: 0.75rem 1rem;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  color: #f1f5f9;
  font-size: 0.9375rem;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input.input-error {
  border-color: #ef4444;
}

.error-message {
  color: #f87171;
  font-size: 0.8125rem;
  font-weight: 500;
}

.roles-selection {
  display: grid;
  gap: 1rem;
}

.role-checkbox-card {
  background: #0f172a;
  border: 2px solid #334155;
  border-radius: 8px;
  transition: all 0.2s;
  cursor: pointer;
}

.role-checkbox-card:hover:not(.disabled) {
  border-color: #475569;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.role-checkbox-card.selected {
  border-color: #3b82f6;
  background: rgba(59, 130, 246, 0.05);
}

.role-checkbox-card.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.role-checkbox-label {
  display: block;
  cursor: pointer;
}

.role-checkbox-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.role-checkbox-content {
  padding: 1.25rem;
}

.role-checkbox-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.role-icon-small {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  flex-shrink: 0;
}

.role-icon-small.hierarchy-0 {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.05) 100%);
  color: #f87171;
}

.role-icon-small.hierarchy-1 {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.05) 100%);
  color: #fbbf24;
}

.role-icon-small.hierarchy-2 {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
  color: #60a5fa;
}

.role-icon-small svg {
  width: 20px;
  height: 20px;
}

.role-checkbox-info {
  flex: 1;
}

.role-checkbox-name {
  font-size: 1rem;
  font-weight: 600;
  color: #f1f5f9;
  margin-bottom: 0.25rem;
}

.role-checkbox-description {
  font-size: 0.875rem;
  color: #94a3b8;
  line-height: 1.5;
}

.checkbox-indicator {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  border: 2px solid #334155;
  background: #0f172a;
  transition: all 0.2s;
}

.role-checkbox-card.selected .checkbox-indicator {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.checkbox-indicator svg {
  width: 16px;
  height: 16px;
}

.role-checkbox-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 1rem;
  border-top: 1px solid #334155;
  font-size: 0.8125rem;
  font-weight: 600;
}

.role-hierarchy-badge {
  color: #64748b;
}

.role-permissions-badge {
  color: #60a5fa;
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
