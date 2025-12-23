<template>
  <AuthenticatedLayout>
    <div class="permissions-container">
      <div class="page-header">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">Gerenciamento de Permissões</h1>
            <p class="page-subtitle">Visualize todas as permissões disponíveis no sistema</p>
          </div>
        </div>
      </div>

      <div class="tabs-container">
        <Link
          :href="route('admin.permissions.users.index')"
          class="tab-item"
        >
          <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Usuários
        </Link>
        <Link
          :href="route('admin.permissions.roles.index')"
          class="tab-item"
        >
          <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          Cargos
        </Link>
        <Link
          :href="route('admin.permissions.permissions.index')"
          class="tab-item active"
        >
          <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Permissões
        </Link>
      </div>

      <div class="stats-grid">
        <StatsCard
          label="Total de Permissões"
          :value="stats.total"
          :icon="PermissionsIcon"
          variant="primary"
        />
        <StatsCard
          label="Módulos"
          :value="stats.modules"
          :icon="ModulesIcon"
          variant="success"
        />
        <StatsCard
          label="Permissões Ativas"
          :value="stats.active"
          :icon="ActiveIcon"
          variant="default"
        />
      </div>

      <div class="content-card">
        <div class="card-header">
          <div class="search-filters">
            <div class="search-box">
              <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                v-model="search"
                type="text"
                placeholder="Buscar permissão..."
                class="search-input"
                @input="handleSearch"
              />
            </div>
            <select v-model="selectedModule" @change="handleModuleFilter" class="module-filter">
              <option value="">Todos os Módulos</option>
              <option v-for="module in availableModules" :key="module" :value="module">
                {{ formatModuleName(module) }}
              </option>
            </select>
          </div>
        </div>

        <div v-if="groupedPermissions && Object.keys(groupedPermissions).length > 0" class="permissions-content">
          <div v-for="(modulePerms, moduleName) in groupedPermissions" :key="moduleName" class="module-section">
            <div class="module-section-header" @click="toggleModule(moduleName)">
              <div class="module-title-wrapper">
                <svg class="module-expand-icon" :class="{ 'expanded': expandedModules.includes(moduleName) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <div class="module-icon" :class="`module-${moduleName}`">
                  <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <h3 class="module-title">{{ formatModuleName(moduleName) }}</h3>
                  <p class="module-description">{{ getModuleDescription(moduleName) }}</p>
                </div>
              </div>
              <span class="permission-count-badge">{{ modulePerms.length }} permissões</span>
            </div>

            <Transition name="expand">
              <div v-if="expandedModules.includes(moduleName)" class="permissions-grid">
                <div
                  v-for="permission in modulePerms"
                  :key="permission.id"
                  class="permission-card"
                >
                  <div class="permission-card-header">
                    <PermissionBadge
                      :label="permission.name"
                      :module="moduleName"
                      :showIcon="true"
                    />
                  </div>
                  <div class="permission-card-body">
                    <p class="permission-description">{{ permission.description || 'Sem descrição' }}</p>
                    <div class="permission-meta">
                      <div class="meta-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 4 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>{{ permission.guard_name || 'web' }}</span>
                      </div>
                      <div class="meta-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>{{ permission.roles_count || 0 }} cargos</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <div v-else class="empty-state">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <h3>Nenhuma permissão encontrada</h3>
          <p>Não há permissões que correspondam aos critérios de busca.</p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatsCard from '@/Components/Admin/StatsCard.vue';
import PermissionBadge from '@/Components/Admin/PermissionBadge.vue';

const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

const props = defineProps({
  permissions: {
    type: Array,
    required: true
  },
  stats: {
    type: Object,
    default: () => ({ total: 0, modules: 0, active: 0 })
  },
  filters: {
    type: Object,
    default: () => ({ search: '', module: '' })
  }
});

const PermissionsIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' })
]);

const ModulesIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' })
]);

const ActiveIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })
]);

const search = ref(props.filters.search || '');
const selectedModule = ref(props.filters.module || '');
const expandedModules = ref([]);

const groupedPermissions = computed(() => {
  return props.permissions.reduce((acc, permission) => {
    const module = permission.name.split('.')[0];
    if (!acc[module]) {
      acc[module] = [];
    }
    acc[module].push(permission);
    return acc;
  }, {});
});

const availableModules = computed(() => {
  return Object.keys(groupedPermissions.value).sort();
});

const toggleModule = (moduleName) => {
  const index = expandedModules.value.indexOf(moduleName);
  if (index > -1) {
    expandedModules.value.splice(index, 1);
  } else {
    expandedModules.value.push(moduleName);
  }
};

const handleSearch = debounce(() => {
  router.get(route('admin.permissions.permissions.index'), {
    search: search.value,
    module: selectedModule.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
}, 300);

const handleModuleFilter = () => {
  router.get(route('admin.permissions.permissions.index'), {
    search: search.value,
    module: selectedModule.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
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

const getModuleDescription = (module) => {
  const moduleDescriptions = {
    users: 'Gerenciamento de usuários do sistema',
    roles: 'Controle de cargos e hierarquias',
    permissions: 'Gestão de permissões e acessos',
    pae: 'Processos Administrativos Eletrônicos',
    rat: 'Relatório de Atendimento Técnico',
    bi: 'Business Intelligence e Analytics',
    integrations: 'Integrações com sistemas externos',
    webhooks: 'Webhooks e notificações',
    system: 'Configurações gerais do sistema'
  };
  return moduleDescriptions[module] || 'Módulo do sistema';
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

.tabs-container {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #334155;
  overflow-x: auto;
}

.tab-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #94a3b8;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
  text-decoration: none;
  white-space: nowrap;
}

.tab-item:hover {
  color: #e2e8f0;
  background: rgba(59, 130, 246, 0.05);
}

.tab-item.active {
  color: #60a5fa;
  border-bottom-color: #60a5fa;
  background: rgba(59, 130, 246, 0.05);
}

.tab-icon {
  width: 20px;
  height: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.content-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 12px;
  overflow: hidden;
}

.card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #334155;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.03) 0%, rgba(139, 92, 246, 0.03) 100%);
}

.search-filters {
  display: flex;
  gap: 1rem;
}

.search-box {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  color: #64748b;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 3rem;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  color: #f1f5f9;
  font-size: 0.9375rem;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.module-filter {
  padding: 0.75rem 1rem;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  color: #f1f5f9;
  font-size: 0.9375rem;
  cursor: pointer;
  transition: all 0.2s;
}

.module-filter:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.permissions-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.module-section {
  display: flex;
  flex-direction: column;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 12px;
  overflow: hidden;
}

.module-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
  user-select: none;
  /* leve contraste vs fundo do bloco */
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.65) 0%, rgba(15, 23, 42, 0.35) 100%);
  border-bottom: 1px solid rgba(51, 65, 85, 0.9);
}

.module-section-header:hover {
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.72) 0%, rgba(15, 23, 42, 0.42) 100%);
  box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.08);
}

.module-title-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.module-expand-icon {
  width: 16px;
  height: 16px;
  color: #64748b;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
}

.module-expand-icon.expanded {
  transform: rotate(90deg);
  color: #60a5fa;
}

.module-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
}

.module-icon.module-users {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
  color: #60a5fa;
}

.module-icon.module-roles {
  background: linear-gradient(135deg, rgba(167, 139, 250, 0.15) 0%, rgba(167, 139, 250, 0.05) 100%);
  color: #a78bfa;
}

.module-icon.module-permissions {
  background: linear-gradient(135deg, rgba(244, 114, 182, 0.15) 0%, rgba(244, 114, 182, 0.05) 100%);
  color: #f472b6;
}

.module-icon.module-pae {
  background: linear-gradient(135deg, rgba(74, 222, 128, 0.15) 0%, rgba(74, 222, 128, 0.05) 100%);
  color: #4ade80;
}

.module-icon.module-rat {
  background: linear-gradient(135deg, rgba(251, 146, 60, 0.15) 0%, rgba(251, 146, 60, 0.05) 100%);
  color: #fb923c;
}

.module-icon.module-bi {
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.15) 0%, rgba(20, 184, 166, 0.05) 100%);
  color: #14b8a6;
}

.module-icon.module-integrations {
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.15) 0%, rgba(249, 115, 22, 0.05) 100%);
  color: #fb923c;
}

.module-icon.module-webhooks {
  background: linear-gradient(135deg, rgba(236, 72, 153, 0.15) 0%, rgba(236, 72, 153, 0.05) 100%);
  color: #ec4899;
}

.module-icon.module-system {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.05) 100%);
  color: #ef4444;
}

.module-icon svg {
  width: 24px;
  height: 24px;
}

.module-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #f1f5f9;
}

.module-description {
  font-size: 0.875rem;
  color: #94a3b8;
  margin-top: 0.25rem;
}

.permission-count-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: rgba(59, 130, 246, 0.15);
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #60a5fa;
}

.permissions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #334155;
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 2000px;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.permission-card {
  background: linear-gradient(135deg, #0f172a 0%, #1a1f35 100%);
  border: 1px solid #2d3548;
  border-radius: 10px;
  overflow: hidden;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.permission-card:hover {
  border-color: #475569;
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
  background: linear-gradient(135deg, #1a1f35 0%, #0f172a 100%);
}

.permission-card-header {
  padding: 1rem;
  border-bottom: 1px solid #2d3548;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
}

.permission-card-body {
  padding: 1rem;
  background: rgba(15, 23, 42, 0.3);
}

.permission-description {
  font-size: 0.875rem;
  color: #94a3b8;
  line-height: 1.5;
  margin-bottom: 0.75rem;
}

.permission-meta {
  display: flex;
  gap: 1rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: #64748b;
}

.meta-item svg {
  width: 16px;
  height: 16px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.empty-state svg {
  width: 64px;
  height: 64px;
  color: #475569;
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #e2e8f0;
  margin-bottom: 0.5rem;
}

.empty-state p {
  font-size: 0.9375rem;
  color: #94a3b8;
}

@media (max-width: 1280px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .permissions-container {
    padding: 1rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .permissions-grid {
    grid-template-columns: 1fr;
  }

  .search-filters {
    flex-direction: column;
  }

  .search-box {
    max-width: none;
  }
}
</style>
