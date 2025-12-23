<template>
  <AuthenticatedLayout>
    <div class="permissions-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">Gerenciamento de Usuários</h1>
            <p class="page-subtitle">Gerencie usuários, cargos e permissões do sistema</p>
          </div>
          <div class="header-actions">
            <Link :href="route('admin.permissions.users.create')" class="btn btn-primary">
              <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Novo Usuário
            </Link>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="tabs-container">
        <Link
          :href="route('admin.permissions.users.index')"
          class="tab-item active"
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
          class="tab-item"
        >
          <svg class="tab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Permissões
        </Link>
      </div>

      <!-- Filters -->
      <div class="filters-section">
        <div class="search-box">
          <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            v-model="form.search"
            @input="debouncedSearch"
            placeholder="Buscar usuários por nome ou email..."
            class="search-input"
          />
        </div>

        <select v-model="form.role" @change="filter" class="filter-select">
          <option value="">Todos os cargos</option>
          <option v-for="role in roles" :key="role.id" :value="role.slug">
            {{ role.name }}
          </option>
        </select>
      </div>

      <!-- Users Table -->
      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Usuário</th>
              <th>Email</th>
              <th>Cargos</th>
              <th>Permissões Diretas</th>
              <th>Status</th>
              <th>Cadastrado em</th>
              <th class="text-right">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users.data" :key="user.id">
              <td>
                <div class="user-info">
                  <div class="user-avatar">{{ getUserInitials(user.name) }}</div>
                  <span class="user-name">{{ user.name }}</span>
                </div>
              </td>
              <td>{{ user.email }}</td>
              <td>
                <div class="badges-list">
                  <span
                    v-for="role in user.roles"
                    :key="role.id"
                    class="badge badge-role"
                  >
                    {{ role.name }}
                  </span>
                  <span v-if="user.roles.length === 0" class="text-muted">Nenhum cargo</span>
                </div>
              </td>
              <td class="text-center">
                <span class="badge badge-info">{{ user.permissions?.length || 0 }}</span>
              </td>
              <td>
                <span
                  class="badge"
                  :class="user.email_verified_at ? 'badge-success' : 'badge-warning'"
                >
                  {{ user.email_verified_at ? 'Ativo' : 'Pendente' }}
                </span>
              </td>
              <td>{{ formatDate(user.created_at) }}</td>
              <td class="text-right">
                <div class="action-buttons">
                  <Link
                    :href="route('admin.permissions.users.show', user.id)"
                    class="btn-icon"
                    title="Visualizar"
                  >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </Link>
                  <Link
                    :href="route('admin.permissions.users.edit', user.id)"
                    class="btn-icon"
                    title="Editar"
                  >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="users.data.length === 0" class="empty-state">
          <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="empty-text">Nenhum usuário encontrado</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="users.data.length > 0" class="pagination-container">
        <div class="pagination-info">
          Mostrando {{ users.from }} até {{ users.to }} de {{ users.total }} usuários
        </div>
        <div class="pagination-links">
          <Link
            v-for="link in users.links"
            :key="link.label"
            :href="link.url"
            :class="['pagination-link', { active: link.active, disabled: !link.url }]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  users: Object,
  roles: Array,
  filters: Object,
});

const form = reactive({
  search: props.filters.search || '',
  role: props.filters.role || '',
});

let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filter();
  }, 300);
};

const filter = () => {
  router.get(route('admin.permissions.users.index'), form, {
    preserveState: true,
    preserveScroll: true,
  });
};

const getUserInitials = (name) => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};
</script>

<style scoped>
.permissions-container {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: #f1f5f9;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  color: #94a3b8;
  font-size: 0.9375rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  font-size: 0.9375rem;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border: none;
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
  border-bottom: 2px solid #1e293b;
  padding-bottom: 0;
}

.tab-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1.5rem;
  color: #94a3b8;
  text-decoration: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
  font-weight: 500;
}

.tab-item:hover {
  color: #e2e8f0;
}

.tab-item.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.tab-icon {
  width: 20px;
  height: 20px;
}

.filters-section {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.search-box {
  position: relative;
  flex: 1;
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
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  color: #e2e8f0;
  font-size: 0.9375rem;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
}

.filter-select {
  padding: 0.75rem 1rem;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 8px;
  color: #e2e8f0;
  font-size: 0.9375rem;
  min-width: 200px;
}

.table-container {
  background: #1e293b;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #0f172a;
}

.data-table th {
  padding: 1rem;
  text-align: left;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.data-table td {
  padding: 1rem;
  border-top: 1px solid #334155;
  color: #e2e8f0;
  font-size: 0.9375rem;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
}

.user-name {
  font-weight: 500;
}

.badges-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 500;
}

.badge-role {
  background: rgba(59, 130, 246, 0.1);
  color: #60a5fa;
}

.badge-success {
  background: rgba(34, 197, 94, 0.1);
  color: #4ade80;
}

.badge-warning {
  background: rgba(251, 146, 60, 0.1);
  color: #fb923c;
}

.badge-info {
  background: rgba(168, 85, 247, 0.1);
  color: #a78bfa;
}

.text-muted {
  color: #64748b;
  font-size: 0.875rem;
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-icon:hover {
  background: rgba(59, 130, 246, 0.1);
  border-color: #3b82f6;
  color: #60a5fa;
}

.btn-icon svg {
  width: 18px;
  height: 18px;
}

.empty-state {
  padding: 4rem 2rem;
  text-align: center;
}

.empty-icon {
  width: 64px;
  height: 64px;
  color: #475569;
  margin: 0 auto 1rem;
}

.empty-text {
  color: #64748b;
  font-size: 1.125rem;
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #1e293b;
  border-radius: 8px;
}

.pagination-info {
  color: #94a3b8;
  font-size: 0.875rem;
}

.pagination-links {
  display: flex;
  gap: 0.25rem;
}

.pagination-link {
  padding: 0.5rem 0.75rem;
  background: transparent;
  border: 1px solid #334155;
  border-radius: 6px;
  color: #e2e8f0;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.pagination-link:hover:not(.disabled) {
  background: rgba(59, 130, 246, 0.1);
  border-color: #3b82f6;
  color: #60a5fa;
}

.pagination-link.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.pagination-link.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
