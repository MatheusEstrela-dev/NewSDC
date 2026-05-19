<template>

    <Head title="Detalhes do Usuário" />
    <div>
      


      <div class="mb-6 sm:mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-slate-100">Detalhes do Usuário</h1>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">Visualize informações detalhadas e permissões</p>
          </div>
          <Link :href="route('admin.permissions.users.edit', user.id)" class="inline-flex items-center justify-center gap-2 w-full md:w-auto px-4 py-2.5 md:py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm hover:shadow">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Editar Usuário
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- Main Content -->
        <div class="xl:col-span-3 space-y-6">
          
          <!-- Info Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <div class="flex flex-col sm:flex-row items-center sm:items-center gap-3 sm:gap-6">
                <div class="w-16 h-16 sm:w-24 sm:h-24 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl sm:text-3xl shadow-md shrink-0">
                  {{ userInitials }}
                </div>
                <div class="flex-1 min-w-0 text-center sm:text-left">
                  <h2 class="text-lg sm:text-2xl font-bold text-slate-900 dark:text-slate-100 mb-1 truncate">{{ user.name }}</h2>
                  <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mb-3 truncate">{{ user.email }}</p>
                  <div class="flex justify-center sm:justify-start gap-2">
                    <span
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                      :class="user.email_verified_at
                        ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'
                        : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300'"
                    >
                      <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path v-if="user.email_verified_at" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        <path v-else fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                      </svg>
                      {{ user.email_verified_at ? 'Email Verificado' : 'Email Pendente' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 p-4 sm:p-6">
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">#{{ user.id }}</div>
              </div>
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Criado em</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(user.created_at) }}</div>
              </div>
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Última Atualização</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(user.updated_at) }}</div>
              </div>
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Último Acesso</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ user.last_login_at ? formatDate(user.last_login_at) : 'Nunca' }}</div>
              </div>
            </div>
          </div>

          <!-- Roles Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Cargos Atribuídos
              </h3>
              <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                {{ user.roles?.length || 0 }}
              </span>
            </div>
            
            <div v-if="user.roles && user.roles.length > 0" class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
              <div v-for="role in user.roles" :key="role.id" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg p-4 sm:p-5 hover:shadow-md transition-all duration-200 group">
                <div class="flex items-start gap-3 sm:gap-4 mb-3 sm:mb-4">
                  <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex-shrink-0"
                       :class="{
                         'bg-red-50 dark:bg-red-900/20 text-red-500 border-red-200 dark:border-red-800': role.hierarchy_level === 0,
                         'bg-amber-50 dark:bg-amber-900/20 text-amber-500 border-amber-200 dark:border-amber-800': role.hierarchy_level === 1,
                         'bg-blue-50 dark:bg-blue-900/20 text-blue-500 border-blue-200 dark:border-blue-800': role.hierarchy_level === 2,
                         'bg-purple-50 dark:bg-purple-900/20 text-purple-500 border-purple-200 dark:border-purple-800': role.hierarchy_level === 3,
                       }"
                  >
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                      <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <h4 class="text-base font-bold text-slate-800 dark:text-slate-100 mb-1">{{ role.name }}</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2">{{ role.description }}</p>
                  </div>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700 text-sm">
                  <span class="font-medium text-slate-500 dark:text-slate-400">Nível {{ role.hierarchy_level }}</span>
                  <Link :href="route('admin.permissions.roles.show', role.id)" class="text-blue-600 dark:text-blue-400 hover:underline">
                    Ver detalhes
                  </Link>
                </div>
              </div>
            </div>
            <div v-else class="p-8 text-center">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
              </div>
              <p class="text-slate-500 dark:text-slate-400">Nenhum cargo atribuído a este usuário.</p>
            </div>
          </div>

          <!-- Direct Permissions Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Permissões Diretas
              </h3>
              <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                {{ directPermissionsCount }}
              </span>
            </div>

            <div v-if="groupedPermissions && Object.keys(groupedPermissions).length > 0" class="p-6 space-y-6">
              <div v-for="(permissions, module) in groupedPermissions" :key="module">
                <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">{{ formatModuleName(module) }}</h4>
                <div class="flex flex-wrap gap-2">
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
             <div v-else class="p-8 text-center">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <p class="text-slate-500 dark:text-slate-400">Nenhuma permissão direta atribuída.</p>
            </div>
          </div>

          <!-- Historico de Movimentacao -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Histórico de Movimentação
              </h3>
              <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300">
                {{ history.length }}
              </span>
            </div>

            <div v-if="history.length > 0" class="p-4 sm:p-6">
              <ul class="space-y-3">
                <li
                  v-for="event in history"
                  :key="event.id"
                  class="flex items-start gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-700"
                >
                  <div
                    class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 border"
                    :class="historyIconClass(event.kind)"
                  >
                    <component :is="historyIcon(event.kind)" class="w-4 h-4" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100 leading-snug">{{ event.summary }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                      por <span class="font-medium text-slate-600 dark:text-slate-300">{{ event.actor_name }}</span>
                      <span class="mx-1">·</span>
                      {{ formatDate(event.occurred_at) }}
                    </p>
                  </div>
                </li>
              </ul>
            </div>
            <div v-else class="p-8 text-center">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <p class="text-slate-500 dark:text-slate-400">Nenhuma movimentação registrada.</p>
            </div>
          </div>

          <!-- API Tokens Card -->
          <UserApiTokens
            :user-id="user.id"
            :tokens="tokens"
            :new-token="newToken"
            :new-token-name="newTokenName"
          />
        </div>

        <!-- Sidebar (horizontal on mobile, vertical on xl) -->
        <div class="xl:col-span-1 grid grid-cols-3 xl:grid-cols-1 gap-3 sm:gap-6">
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

</template>

<script setup>
import { computed, h } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import PermissionBadge from '@/Components/Admin/PermissionBadge.vue';
import StatsCard from '@/Components/Admin/StatsCard.vue';
import UserApiTokens from '@/Components/Organisms/Permissions/UserApiTokens.vue';

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  tokens: {
    type: Array,
    default: () => []
  },
  newToken: {
    type: String,
    default: null
  },
  newTokenName: {
    type: String,
    default: null
  },
  history: {
    type: Array,
    default: () => []
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

const HistoryIconPower = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M18.364 5.636a9 9 0 11-12.728 0M12 3v10' })
]);

const HistoryIconUserCog = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' })
]);

const HistoryIconBuilding = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
]);

const HistoryIconKey = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z' })
]);

const HistoryIconEdit = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' })
]);

const HistoryIconCheck = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })
]);

const HistoryIconBan = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' })
]);

const historyIcon = (kind) => {
  switch (kind) {
    case 'activated':           return HistoryIconCheck;
    case 'deactivated':         return HistoryIconBan;
    case 'role_granted':
    case 'role_revoked':
    case 'permission_granted':
    case 'permission_revoked':  return HistoryIconUserCog;
    case 'orgao_changed':
    case 'municipio_changed':   return HistoryIconBuilding;
    case 'password_reset':      return HistoryIconKey;
    case 'created':             return HistoryIconCheck;
    case 'deleted':             return HistoryIconBan;
    default:                    return HistoryIconEdit;
  }
};

const historyIconClass = (kind) => {
  switch (kind) {
    case 'activated':
    case 'created':
      return 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-200 dark:border-green-800';
    case 'deactivated':
    case 'deleted':
      return 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800';
    case 'role_granted':
    case 'permission_granted':
      return 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-800';
    case 'role_revoked':
    case 'permission_revoked':
      return 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800';
    case 'orgao_changed':
      return 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800';
    case 'municipio_changed':
      return 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 border-orange-300 dark:border-orange-700 ring-2 ring-orange-200 dark:ring-orange-800';
    case 'password_reset':
      return 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-800';
    default:
      return 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700';
  }
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
