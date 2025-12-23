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
            <h1 class="page-title">Detalhes do Cargo</h1>
            <p class="page-subtitle">Visualize informações e permissões do cargo</p>
          </div>
          <div class="header-actions">
            <Link
              v-if="!role.is_immutable"
              :href="route('admin.permissions.roles.edit', role.id)"
              class="btn btn-primary"
            >
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Editar Cargo
            </Link>
          </div>
        </div>
      </div>

      <div class="content-grid">
        <div class="main-content">
          <div class="info-card">
            <div class="card-header">
              <div class="role-icon-large" :class="`hierarchy-${role.hierarchy_level}`">
                <svg fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                  <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                </svg>
              </div>
              <div class="role-info">
                <h2 class="role-name">{{ role.name }}</h2>
                <p class="role-slug">{{ role.slug }}</p>
                <div class="status-badges">
                  <span v-if="role.is_active" class="badge badge-success">
                    <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Ativo
                  </span>
                  <span v-else class="badge badge-inactive">
                    <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    Inativo
                  </span>
                  <span v-if="role.is_immutable" class="badge badge-warning">
                    <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    Imutável
                  </span>
                </div>
              </div>
            </div>

            <div class="info-section">
              <h3 class="info-section-title">Descrição</h3>
              <p class="info-section-content">{{ role.description }}</p>
            </div>

            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">ID</div>
                <div class="info-value">#{{ role.id }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Nível de Hierarquia</div>
                <div class="info-value">{{ role.hierarchy_level }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Criado em</div>
                <div class="info-value">{{ formatDate(role.created_at) }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Última Atualização</div>
                <div class="info-value">{{ formatDate(role.updated_at) }}</div>
              </div>
            </div>
          </div>

          <div class="section-card">
            <div class="section-header">
              <h3 class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Usuários com este Cargo
              </h3>
              <span class="count-badge">{{ role.users?.length || 0 }}</span>
            </div>

            <div v-if="role.users && role.users.length > 0" class="users-list">
              <Link
                v-for="user in role.users"
                :key="user.id"
                :href="route('admin.permissions.users.show', user.id)"
                class="user-item"
              >
                <div class="user-avatar">{{ getUserInitials(user.name) }}</div>
                <div class="user-info">
                  <div class="user-name">{{ user.name }}</div>
                  <div class="user-email">{{ user.email }}</div>
                </div>
                <svg class="user-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>

            <div v-else class="empty-state-small">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <p>Nenhum usuário possui este cargo</p>
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
              <span class="count-badge">{{ permissionsCount }}</span>
            </div>

            <div v-if="groupedPermissions && Object.keys(groupedPermissions).length > 0" class="permissions-list">
              <div v-for="(permissions, module) in groupedPermissions" :key="module" class="permission-module">
                <h4 class="module-name">{{ formatModuleName(module) }}</h4>
                <div class="permission-badges">
                  <PermissionBadge
                    v-for="permission in permissions"
                    :key="permission.id"
                    :label="permission.name"
                    :module="module"
                    :showIcon="true"
                  />
                </div>
              </div>
            </div>

            <div v-else class="empty-state-small">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              <p>Nenhuma permissão atribuída a este cargo</p>
            </div>
          </div>
        </div>

        <div class="sidebar-content">
          <StatsCard
            label="Total de Usuários"
            :value="role.users?.length || 0"
            :icon="UsersIcon"
            variant="primary"
          />
          <StatsCard
            label="Permissões"
            :value="permissionsCount"
            :icon="PermissionsIcon"
            variant="success"
          />
          <StatsCard
            label="Nível Hierárquico"
            :value="role.hierarchy_level"
            :icon="HierarchyIcon"
            variant="default"
          />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PermissionBadge from '@/Components/Admin/PermissionBadge.vue';
import StatsCard from '@/Components/Admin/StatsCard.vue';

const props = defineProps({
  role: {
    type: Object,
    required: true
  }
});

const UsersIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })
]);

const PermissionsIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' })
]);

const HierarchyIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' })
]);

const permissionsCount = computed(() => props.role.permissions?.length || 0);

const groupedPermissions = computed(() => {
  if (!props.role.permissions) return {};

  return props.role.permissions.reduce((acc, permission) => {
    const module = permission.name.split('.')[0];
    if (!acc[module]) {
      acc[module] = [];
    }
    acc[module].push(permission);
    return acc;
  }, {});
});

const getUserInitials = (name) => {
  const names = name.split(' ');
  if (names.length >= 2) {
    return (names[0][0] + names[names.length - 1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
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

.content-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 1.5rem;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 12px;
  overflow: hidden;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 2rem;
  border-bottom: 1px solid #334155;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
}

.role-icon-large {
  width: 96px;
  height: 96px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.role-icon-large.hierarchy-0 {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.05) 100%);
  color: #f87171;
}

.role-icon-large.hierarchy-1 {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.05) 100%);
  color: #fbbf24;
}

.role-icon-large.hierarchy-2 {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
  color: #60a5fa;
}

.role-icon-large svg {
  width: 48px;
  height: 48px;
}

.role-info {
  flex: 1;
}

.role-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f1f5f9;
  margin-bottom: 0.25rem;
}

.role-slug {
  font-size: 1rem;
  color: #94a3b8;
  font-family: monospace;
  margin-bottom: 0.75rem;
}

.status-badges {
  display: flex;
  gap: 0.5rem;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 600;
}

.badge-success {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
}

.badge-inactive {
  background: rgba(148, 163, 184, 0.15);
  color: #94a3b8;
}

.badge-warning {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
}

.badge-icon {
  width: 16px;
  height: 16px;
}

.info-section {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #334155;
}

.info-section-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.75rem;
}

.info-section-content {
  font-size: 0.9375rem;
  color: #e2e8f0;
  line-height: 1.6;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
  padding: 2rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-value {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #e2e8f0;
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

.users-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.user-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  transition: all 0.2s;
  text-decoration: none;
}

.user-item:hover {
  border-color: #475569;
  transform: translateX(4px);
  background: rgba(59, 130, 246, 0.05);
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
}

.user-name {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #f1f5f9;
}

.user-email {
  font-size: 0.8125rem;
  color: #94a3b8;
}

.user-arrow {
  width: 20px;
  height: 20px;
  color: #64748b;
  transition: all 0.2s;
}

.user-item:hover .user-arrow {
  color: #60a5fa;
  transform: translateX(4px);
}

.permissions-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.permission-module {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.module-name {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #cbd5e1;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.permission-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.empty-state-small {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
  color: #64748b;
}

.empty-state-small svg {
  width: 48px;
  height: 48px;
  margin-bottom: 0.75rem;
  opacity: 0.5;
}

.empty-state-small p {
  font-size: 0.9375rem;
  font-weight: 500;
}

.sidebar-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

@media (max-width: 1280px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .sidebar-content {
    grid-template-columns: repeat(3, 1fr);
    display: grid;
  }
}

@media (max-width: 768px) {
  .permissions-container {
    padding: 1rem;
  }

  .info-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .sidebar-content {
    grid-template-columns: 1fr;
  }
}
</style>
