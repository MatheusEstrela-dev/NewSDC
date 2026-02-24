<template>

    <Head title="Gerenciamento de Cargos" />
    <div>
      

      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-6 md:mb-8">
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-slate-800 dark:text-slate-100">Gerenciamento de Cargos</h1>
          <p class="text-xs md:text-sm text-slate-600 dark:text-slate-400 mt-1">Gerencie cargos e suas permissões do sistema</p>
        </div>
        <div v-if="canCreate" class="w-full md:w-auto flex justify-center">
          <Link :href="route('admin.permissions.roles.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm hover:shadow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Novo Cargo
          </Link>
        </div>
      </div>

      <div class="border-b border-slate-200 dark:border-slate-700 mb-6 md:mb-8 overflow-x-auto scrollbar-hide">
        <div class="flex space-x-1 min-w-max">
          <Link
            :href="route('admin.permissions.users.index')"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 border-b-2 border-transparent hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Usuários
          </Link>
          <Link
            :href="route('admin.permissions.roles.index')"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400 bg-blue-50/50 dark:bg-blue-900/10 rounded-t-lg"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Cargos
          </Link>
          <Link
            :href="route('admin.permissions.permissions.index')"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 border-b-2 border-transparent hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Permissões
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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

      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
          <div class="flex flex-col md:flex-row gap-4">
            <div class="relative flex-1 max-w-md">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                v-model="search"
                type="text"
                placeholder="Buscar cargo..."
                class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                @input="handleSearch"
              />
            </div>
          </div>
        </div>

        <div v-if="roles.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
          <div v-for="role in roles.data" :key="role.id" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-200 flex flex-col min-h-[320px] group">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-br from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 flex justify-between items-start">
              <div class="w-14 h-14 rounded-xl flex items-center justify-center border transition-all duration-300 group-hover:scale-110 group-hover:shadow-md"
                   :class="{
                     'bg-red-50 dark:bg-red-900/20 text-red-600 border-red-200 dark:border-red-800': role.hierarchy_level === 0,
                     'bg-blue-50 dark:bg-blue-900/20 text-blue-600 border-blue-200 dark:border-blue-800': role.hierarchy_level === 1,
                     'bg-purple-50 dark:bg-purple-900/20 text-purple-600 border-purple-200 dark:border-purple-800': role.hierarchy_level === 2,
                     'bg-teal-50 dark:bg-teal-900/20 text-teal-600 border-teal-200 dark:border-teal-800': role.hierarchy_level === 3,
                     'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 border-indigo-200 dark:border-indigo-800': role.hierarchy_level === 4,
                     'bg-slate-50 dark:bg-slate-900/20 text-slate-600 border-slate-200 dark:border-slate-800': role.hierarchy_level === 5,
                     'bg-gray-50 dark:bg-gray-900/20 text-gray-500 border-gray-200 dark:border-gray-800': role.hierarchy_level === 6,
                   }"
              >
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                  <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                </svg>
              </div>
              <div class="flex flex-col gap-2 items-end">
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold"
                      :class="role.is_active 
                        ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' 
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                >
                  {{ role.is_active ? 'Ativo' : 'Inativo' }}
                </span>
                <span v-if="role.is_immutable" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                  </svg>
                  Imutável
                </span>
              </div>
            </div>

            <div class="p-6 flex-1 flex flex-col">
              <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 mb-2">{{ role.name }}</h3>
              <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mb-6 h-10">{{ role.description }}</p>

              <div class="grid grid-cols-3 gap-3 mt-auto">
                <div class="flex flex-col items-center gap-1 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                  <svg class="w-5 h-5 text-blue-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  <span class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ role.users_count }}</span>
                  <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Usuários</span>
                </div>
                <div class="flex flex-col items-center gap-1 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                  <svg class="w-5 h-5 text-purple-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  <span class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ role.permissions_count }}</span>
                  <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Permissões</span>
                </div>
                <div class="flex flex-col items-center gap-1 p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                  <svg class="w-5 h-5 text-amber-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  <span class="text-lg font-bold text-slate-800 dark:text-slate-100">{{ role.hierarchy_level }}</span>
                  <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Nível</span>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2">
              <TableActions
                :show-view="true"
                :show-edit="canEdit && !role.is_immutable"
                :show-delete="canDelete && !role.is_immutable && role.users_count === 0"
                :show-print="false"
                :show-attachments="false"
                size="sm"
                @view="router.get(route('admin.permissions.roles.show', role.id))"
                @edit="router.get(route('admin.permissions.roles.edit', role.id))"
                @delete="confirmDelete(role)"
              />
            </div>
          </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-12 px-4">
          <svg class="w-16 h-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">Nenhum cargo encontrado</h3>
          <p class="text-slate-500 dark:text-slate-400 mt-1 mb-4">Não há cargos cadastrados no sistema.</p>
          <Link :href="route('admin.permissions.roles.create')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-sm">
            Criar Primeiro Cargo
          </Link>
        </div>

        <div class="px-6 py-4 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
          <Pagination :pagination="roles" @page-change="onPageChange" />
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

</template>

<script setup>
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import StatsCard from '@/Components/Admin/StatsCard.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import TableActions from '@/Components/Molecules/Table/TableActions.vue';
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { h, ref } from 'vue';
import { route } from 'ziggy-js';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const canCreate = can('roles.create');
const canEdit = can('roles.edit');
const canDelete = can('roles.delete');

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

const onPageChange = (page) => {
  router.get(route('admin.permissions.roles.index'), { search: search.value, page }, {
    preserveState: true,
    preserveScroll: true
  });
};
</script>
