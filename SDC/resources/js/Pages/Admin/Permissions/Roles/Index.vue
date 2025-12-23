<template>
  <AuthenticatedLayout>
    <div class="permissions-container">
      <div class="page-header">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">Gerenciamento de Cargos</h1>
            <p class="page-subtitle">Gerencie cargos e suas permissões do sistema</p>
          </div>
          <div class="header-actions">
            <Link :href="route('admin.permissions.roles.create')" class="btn btn-primary">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Novo Cargo
            </Link>
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
          class="tab-item active"
        >
          <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          Cargos
        </Link>
        <Link
          :href="route('admin.permissions.permissions.index')"
          class="tab-item"
        >
          <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Permissões
        </Link>
      </div>

      <div class="stats-grid">
        <StatsCard
          label="Total de Cargos"
          :value="stats.total"
          :icon="RolesIcon"
          variant="primary"
        />
        <StatsCard
          label="Cargos Ativos"
          :value="stats.active"
          :icon="ActiveIcon"
          variant="success"
        />
        <StatsCard
          label="Usuários com Cargos"
          :value="stats.users_with_roles"
          :icon="UsersIcon"
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
                placeholder="Buscar cargo..."
                class="search-input"
                @input="handleSearch"
              />
            </div>
          </div>
        </div>

        <div v-if="roles.data.length > 0" class="roles-grid">
          <div v-for="role in roles.data" :key="role.id" class="role-card">
            <div class="role-card-header">
              <div class="role-icon-container" :class="`hierarchy-${role.hierarchy_level}`">
                <svg fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                  <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                </svg>
              </div>
              <div class="role-status-badges">
                <span v-if="role.is_active" class="status-badge active">Ativo</span>
                <span v-else class="status-badge inactive">Inativo</span>
                <span v-if="role.is_immutable" class="status-badge immutable">
                  <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                  </svg>
                  Imutável
                </span>
              </div>
            </div>

            <div class="role-card-body">
              <h3 class="role-name">{{ role.name }}</h3>
              <p class="role-description">{{ role.description }}</p>

              <div class="role-metrics">
                <div class="metric-item">
                  <svg class="metric-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  <span class="metric-value">{{ role.users_count }}</span>
                  <span class="metric-label">Usuários</span>
                </div>
                <div class="metric-item">
                  <svg class="metric-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  <span class="metric-value">{{ role.permissions_count }}</span>
                  <span class="metric-label">Permissões</span>
                </div>
                <div class="metric-item">
                  <svg class="metric-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  <span class="metric-value">{{ role.hierarchy_level }}</span>
                  <span class="metric-label">Nível</span>
                </div>
              </div>
            </div>

            <div class="role-card-footer">
              <Link :href="route('admin.permissions.roles.show', role.id)" class="btn-icon-action">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </Link>
              <Link v-if="!role.is_immutable" :href="route('admin.permissions.roles.edit', role.id)" class="btn-icon-action">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </Link>
              <button
                v-if="!role.is_immutable && role.users_count === 0"
                @click="confirmDelete(role)"
                class="btn-icon-action danger"
              >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          <h3>Nenhum cargo encontrado</h3>
          <p>Não há cargos cadastrados no sistema.</p>
          <Link :href="route('admin.permissions.roles.create')" class="btn btn-primary">
            Criar Primeiro Cargo
          </Link>
        </div>

        <div v-if="roles.data.length > 0" class="pagination">
          <Link
            v-for="link in roles.links"
            :key="link.label"
            :href="link.url"
            class="pagination-link"
            :class="{ active: link.active, disabled: !link.url }"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <ConfirmDialog
      :isOpen="showDeleteDialog"
      title="Deletar Cargo"
      :message="`Tem certeza que deseja deletar o cargo '${roleToDelete?.name}'?`"
      description="Esta ação não pode ser desfeita."
      variant="danger"
      confirmText="Sim, deletar"
      cancelText="Cancelar"
      :loading="isDeleting"
      @confirm="deleteRole"
      @cancel="showDeleteDialog = false"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, h } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatsCard from '@/Components/Admin/StatsCard.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';

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
  roles: {
    type: Object,
    required: true
  },
  stats: {
    type: Object,
    default: () => ({ total: 0, active: 0, users_with_roles: 0 })
  },
  filters: {
    type: Object,
    default: () => ({ search: '' })
  }
});

const RolesIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
]);

const ActiveIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })
]);

const UsersIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })
]);

const search = ref(props.filters.search || '');
const showDeleteDialog = ref(false);
const roleToDelete = ref(null);
const isDeleting = ref(false);

const handleSearch = debounce(() => {
  router.get(route('admin.permissions.roles.index'), { search: search.value }, {
    preserveState: true,
    preserveScroll: true
  });
}, 300);

const confirmDelete = (role) => {
  roleToDelete.value = role;
  showDeleteDialog.value = true;
};

const deleteRole = () => {
  if (!roleToDelete.value) return;

  isDeleting.value = true;
  router.delete(route('admin.permissions.roles.destroy', roleToDelete.value.id), {
    onSuccess: () => {
      showDeleteDialog.value = false;
      roleToDelete.value = null;
      isDeleting.value = false;
    },
    onError: () => {
      isDeleting.value = false;
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
  gap: 1.5rem;
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

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-icon {
  width: 18px;
  height: 18px;
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

.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
  padding: 1.5rem;
}

.role-card {
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  min-height: 320px;
}

.role-card:hover {
  border-color: #475569;
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.role-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.5rem;
  border-bottom: 1px solid #334155;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
}

.role-icon-container {
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  /* Default (casos em que não exista classe hierarchy-* mapeada) */
  background: linear-gradient(135deg, rgba(148, 163, 184, 0.15) 0%, rgba(148, 163, 184, 0.05) 100%);
  color: #cbd5e1;
  border: 1px solid rgba(148, 163, 184, 0.18);
}

.role-icon-container.hierarchy-0 {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.05) 100%);
  color: #f87171;
}

.role-icon-container.hierarchy-1 {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.05) 100%);
  color: #fbbf24;
}

.role-icon-container.hierarchy-2 {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
  color: #60a5fa;
  border-color: rgba(59, 130, 246, 0.18);
}

.role-icon-container.hierarchy-3 {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.18) 0%, rgba(139, 92, 246, 0.06) 100%);
  color: #a78bfa;
  border-color: rgba(139, 92, 246, 0.2);
}

.role-icon-container.hierarchy-4 {
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.18) 0%, rgba(20, 184, 166, 0.06) 100%);
  color: #5eead4;
  border-color: rgba(20, 184, 166, 0.2);
}

.role-icon-container.hierarchy-5 {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.18) 0%, rgba(99, 102, 241, 0.06) 100%);
  color: #a5b4fc;
  border-color: rgba(99, 102, 241, 0.2);
}

.role-icon-container.hierarchy-6 {
  background: linear-gradient(135deg, rgba(236, 72, 153, 0.18) 0%, rgba(236, 72, 153, 0.06) 100%);
  color: #f9a8d4;
  border-color: rgba(236, 72, 153, 0.2);
}

.role-icon-container svg {
  width: 28px;
  height: 28px;
}

.role-status-badges {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: flex-end;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 600;
}

.status-badge.active {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
}

.status-badge.inactive {
  background: rgba(148, 163, 184, 0.15);
  color: #94a3b8;
}

.status-badge.immutable {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
}

.status-badge svg {
  width: 14px;
  height: 14px;
}

.role-card-body {
  padding: 1.5rem;
  flex: 1;
}

.role-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: #f1f5f9;
  margin-bottom: 0.5rem;
}

.role-description {
  font-size: 0.9375rem;
  color: #94a3b8;
  line-height: 1.5;
  margin-bottom: 1.25rem;
}

.role-metrics {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.metric-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.375rem;
  padding: 1rem;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
}

.metric-icon {
  width: 24px;
  height: 24px;
  color: #60a5fa;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f1f5f9;
}

.metric-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.role-card-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #334155;
  background: #1e293b;
}

.btn-icon-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid #334155;
  background: #0f172a;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-icon-action:hover {
  background: rgba(59, 130, 246, 0.1);
  border-color: #3b82f6;
  color: #60a5fa;
}

.btn-icon-action.danger:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: #ef4444;
  color: #f87171;
}

.btn-icon-action svg {
  width: 20px;
  height: 20px;
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
  margin-bottom: 1.5rem;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  padding: 1.5rem;
  border-top: 1px solid #334155;
}

.pagination-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  padding: 0 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #94a3b8;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 6px;
  transition: all 0.2s;
  text-decoration: none;
}

.pagination-link:hover:not(.disabled):not(.active) {
  background: rgba(59, 130, 246, 0.1);
  border-color: #3b82f6;
  color: #60a5fa;
}

.pagination-link.active {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  border-color: #3b82f6;
  color: white;
}

.pagination-link.disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

  .roles-grid {
    grid-template-columns: 1fr;
  }

  .role-metrics {
    grid-template-columns: 1fr;
  }
}
</style>
