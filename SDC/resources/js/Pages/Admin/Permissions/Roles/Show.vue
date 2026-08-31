<template>

    <Head title="Detalhes do Cargo" />
    <div>
      <PageHeader
        title="Detalhes do Cargo"
        description="Visualize informações e permissões do cargo"
        :icon-image="moduleIcon('permissionamento')"
        variant="gradient"
        class="mb-6 md:mb-8"
      >
        <template #actions>
          <div class="flex flex-wrap items-center gap-2 sm:gap-3">
    <Link
    v-if="!role.is_immutable"
    :href="route('admin.permissions.roles.edit', role.id)"
    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm hover:shadow"
    >
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
    </svg>
    Editar Cargo
    </Link>
          </div>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- Main Content -->
        <div class="xl:col-span-3 space-y-6">
          
          <!-- Info Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <div class="flex items-center gap-6">
                <div class="w-24 h-24 rounded-2xl flex items-center justify-center shrink-0 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800"
                     :class="{
                       'bg-red-50 dark:bg-red-900/20 text-red-500 border-red-200 dark:border-red-800': role.hierarchy_level === 0,
                       'bg-amber-50 dark:bg-amber-900/20 text-amber-500 border-amber-200 dark:border-amber-800': role.hierarchy_level === 1,
                       'bg-blue-50 dark:bg-blue-900/20 text-blue-500 border-blue-200 dark:border-blue-800': role.hierarchy_level === 2,
                       'bg-purple-50 dark:bg-purple-900/20 text-purple-500 border-purple-200 dark:border-purple-800': role.hierarchy_level === 3,
                     }"
                >
                  <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-1 truncate">{{ role.name }}</h2>
                  <p class="font-mono text-sm text-slate-500 dark:text-slate-400 mb-3 truncate">{{ role.slug }}</p>
                  <div class="flex flex-wrap gap-2">
                    <span 
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                      :class="role.is_active 
                        ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' 
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                    >
                      <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path v-if="role.is_active" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                      </svg>
                      {{ role.is_active ? 'Ativo' : 'Inativo' }}
                    </span>
                    <span v-if="role.is_immutable" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300">
                      <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                      </svg>
                      Imutável
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
              <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Descrição</h3>
              <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed">{{ role.description }}</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-6">
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">#{{ role.id }}</div>
              </div>
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nível de Hierarquia</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ role.hierarchy_level }}</div>
              </div>
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Criado em</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(role.created_at) }}</div>
              </div>
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Última Atualização</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(role.updated_at) }}</div>
              </div>
            </div>
          </div>

          <!-- Users with Role Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Usuários com este Cargo
              </h3>
              <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                {{ role.users?.length || 0 }}
              </span>
            </div>
            
            <div v-if="role.users && role.users.length > 0" class="p-6 space-y-3">
              <Link
                v-for="user in role.users"
                :key="user.id"
                :href="route('admin.permissions.users.show', user.id)"
                class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg hover:shadow-md hover:translate-x-1 transition-all duration-200 group"
              >
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-sm shrink-0">
                  {{ getUserInitials(user.name) }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">{{ user.name }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ user.email }}</div>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
            <div v-else class="p-8 text-center">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <p class="text-slate-500 dark:text-slate-400">Nenhum usuário possui este cargo.</p>
            </div>
          </div>

          <!-- Permissions Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Permissões
              </h3>
              <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                {{ permissionsCount }}
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
              <p class="text-slate-500 dark:text-slate-400">Nenhuma permissão atribuída a este cargo.</p>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="xl:col-span-1 space-y-6">
          <StatCard
            title="Total de Usuários"
            :value="role.users?.length || 0"
            :icon="UsersIcon"
            variant="info"
          />
          <StatCard
            title="Permissões"
            :value="permissionsCount"
            :icon="PermissionsIcon"
            variant="success"
          />
          <StatCard
            title="Nível Hierárquico"
            :value="role.hierarchy_level"
            :icon="HierarchyIcon"
            variant="info"
          />
        </div>
      </div>
    </div>

</template>

<script setup>
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import PermissionBadge from '@/Components/Admin/PermissionBadge.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';

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
