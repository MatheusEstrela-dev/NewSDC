<template>
  <AuthenticatedLayout>
    <div class="permissions-container">
      <div class="page-header">
        <div class="header-content">
          <div class="header-left">
            <Link :href="route('admin.permissions.users.index')" class="back-link">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              Voltar
            </Link>
            <h1 class="page-title">Detalhes do Usuário</h1>
            <p class="page-subtitle">Visualize informações detalhadas e permissões</p>
          </div>
          <div class="header-actions">
            <Link :href="route('admin.permissions.users.edit', user.id)" class="btn btn-primary">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Editar Usuário
            </Link>
          </div>
        </div>
      </div>

      <div class="content-grid">
        <div class="main-content">
          <div class="info-card">
            <div class="card-header">
              <div class="avatar-large">
                {{ userInitials }}
              </div>
              <div class="user-info">
                <h2 class="user-name">{{ user.name }}</h2>
                <p class="user-email">{{ user.email }}</p>
                <div class="status-badges">
                  <span v-if="user.email_verified_at" class="badge badge-success">
                    <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Email Verificado
                  </span>
                  <span v-else class="badge badge-warning">
                    <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Email Pendente
                  </span>
                </div>
              </div>
            </div>

            <div class="info-grid">
              <div class="info-item">
                <div class="info-label">ID</div>
                <div class="info-value">#{{ user.id }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Criado em</div>
                <div class="info-value">{{ formatDate(user.created_at) }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Última Atualização</div>
                <div class="info-value">{{ formatDate(user.updated_at) }}</div>
              </div>
              <div class="info-item">
                <div class="info-label">Último Acesso</div>
                <div class="info-value">{{ user.last_login_at ? formatDate(user.last_login_at) : 'Nunca' }}</div>
              </div>
            </div>
          </div>

          <div class="section-card">
            <div class="section-header">
              <h3 class="section-title">
                <svg class="section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Cargos Atribuídos
              </h3>
              <span class="count-badge">{{ user.roles?.length || 0 }}</span>
            </div>
            <div v-if="user.roles && user.roles.length > 0" class="roles-grid">
              <div v-for="role in user.roles" :key="role.id" class="role-card">
                <div class="role-header">
                  <div class="role-icon" :class="`hierarchy-${role.hierarchy_level}`">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                      <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                    </svg>
                  </div>
                  <div class="role-info">
                    <h4 class="role-name">{{ role.name }}</h4>
                    <p class="role-description">{{ role.description }}</p>
                  </div>
                </div>
                <div class="role-footer">
                  <span class="role-hierarchy">Nível {{ role.hierarchy_level }}</span>
                  <span class="role-permissions-count">{{ role.permissions_count || 0 }} permissões</span>
                </div>
              </div>
            </div>
            <div v-else class="empty-state-small">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <p>Nenhum cargo atribuído</p>
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
              <span class="count-badge">{{ directPermissionsCount }}</span>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <p>Nenhuma permissão direta atribuída</p>
            </div>
          </div>
        </div>

        <div class="sidebar-content">
          <StatsCard
            label="Total de Cargos"
            :value="user.roles?.length || 0"
            :icon="RolesIcon"
            variant="primary"
          />
          <StatsCard
            label="Permissões Diretas"
            :value="directPermissionsCount"
            :icon="PermissionsIcon"
            variant="success"
          />
          <StatsCard
            label="Total de Permissões"
            :value="allPermissionsCount"
            :icon="ShieldIcon"
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
  user: {
    type: Object,
    required: true
  }
});

const RolesIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
]);

const PermissionsIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' })
]);

const ShieldIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' })
]);

const userInitials = computed(() => {
  const names = props.user.name.split(' ');
  if (names.length >= 2) {
    return (names[0][0] + names[names.length - 1][0]).toUpperCase();
  }
  return props.user.name.substring(0, 2).toUpperCase();
});

const directPermissionsCount = computed(() => {
  return props.user.direct_permissions?.length || 0;
});

const allPermissionsCount = computed(() => {
  const directCount = props.user.direct_permissions?.length || 0;
  const rolePermissions = props.user.roles?.reduce((sum, role) => sum + (role.permissions_count || 0), 0) || 0;
  return directCount + rolePermissions;
});

const groupedPermissions = computed(() => {
  if (!props.user.direct_permissions) return {};

  return props.user.direct_permissions.reduce((acc, permission) => {
    const module = permission.name.split('.')[0];
    if (!acc[module]) {
      acc[module] = [];
    }
    acc[module].push(permission);
    return acc;
  }, {});
});

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

.avatar-large {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
}

.user-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f1f5f9;
  margin-bottom: 0.25rem;
}

.user-email {
  font-size: 1rem;
  color: #94a3b8;
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

.badge-warning {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
}

.badge-icon {
  width: 16px;
  height: 16px;
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

.roles-grid {
  display: grid;
  gap: 1rem;
}

.role-card {
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  padding: 1.25rem;
  transition: all 0.2s;
}

.role-card:hover {
  border-color: #475569;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.role-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.role-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  flex-shrink: 0;
}

.role-icon.hierarchy-0 {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.05) 100%);
  color: #f87171;
}

.role-icon.hierarchy-1 {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.05) 100%);
  color: #fbbf24;
}

.role-icon.hierarchy-2 {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
  color: #60a5fa;
}

.role-icon svg {
  width: 24px;
  height: 24px;
}

.role-info {
  flex: 1;
}

.role-name {
  font-size: 1rem;
  font-weight: 600;
  color: #f1f5f9;
  margin-bottom: 0.25rem;
}

.role-description {
  font-size: 0.875rem;
  color: #94a3b8;
  line-height: 1.5;
}

.role-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 1rem;
  border-top: 1px solid #334155;
  font-size: 0.8125rem;
  font-weight: 600;
}

.role-hierarchy {
  color: #64748b;
}

.role-permissions-count {
  color: #60a5fa;
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
