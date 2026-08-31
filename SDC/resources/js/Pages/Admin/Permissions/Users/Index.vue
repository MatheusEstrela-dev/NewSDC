<template>

    <Head title="Gerenciamento de Usuários" />
    <div>
      

      <PageHeader
        title="Gerenciamento de Usuários"
        description="Gerencie usuários, cargos e permissões do sistema"
        :icon-image="moduleIcon('permissionamento')"
        variant="gradient"
        class="mb-6 md:mb-8"
      >
        <template #actions>
          <div v-if="canCreate" class="flex flex-wrap items-center gap-2 sm:gap-3">
            <Link
              :href="route('admin.permissions.users.create')"
              class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm hover:shadow"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Novo Usuario
            </Link>
          </div>
        </template>
      </PageHeader>

      <!-- Tabs Navigation -->
      <div class="border-b border-slate-200 dark:border-slate-700 mb-6 md:mb-8 overflow-x-auto scrollbar-hide">
        <div class="flex space-x-1 min-w-max">
          <Link
            :href="route('admin.permissions.users.index')"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400 bg-blue-50/50 dark:bg-blue-900/10 rounded-t-lg"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Usuários
          </Link>
          <Link
            :href="route('admin.permissions.roles.index')"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 border-b-2 border-transparent hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
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

      <!-- Filters -->
      <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            v-model="form.search"
            @input="debouncedSearch"
            placeholder="Buscar usuários por nome ou email..."
            class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
          />
        </div>

        <select 
          v-model="form.role" 
          @change="filter" 
          class="min-w-[200px] px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow cursor-pointer"
        >
          <option value="">Todos os cargos</option>
          <option v-for="role in roles" :key="role.id" :value="role.slug">
            {{ role.name }}
          </option>
        </select>
      </div>

      <!-- Mobile/Tablet Cards View -->
      <div v-if="isMobile || isTablet" class="space-y-4">
        <TableMobileCard
          v-for="user in users.data"
          :key="user.id"
          :title="user.name"
          :subtitle="user.email"
          :data="{
            email: user.email,
            status: statusLabel(effectiveStatus(user)),
            created_at: formatDate(user.created_at),
            roles: user.roles.map(r => r.name).join(', ') || 'Nenhum cargo'
          }"
          :fields="[
            { key: 'roles', label: 'Cargos', fullWidth: true },
            { key: 'status', label: 'Status' },
            { key: 'created_at', label: 'Cadastrado' }
          ]"
        >
          <template #actions>
            <div class="flex items-center justify-end gap-1.5">
              <ActionButton
                module="users"
                resource=""
                size="sm"
                :actions="[
                  { action: 'view',   handler: () => router.get(route('admin.permissions.users.show', user.id)) },
                  { action: 'edit',   handler: () => router.get(route('admin.permissions.users.edit', user.id)), allowed: canEdit },
                  { action: 'delete', handler: () => confirmDelete(user), allowed: canDelete },
                ]"
              />
            </div>
          </template>
        </TableMobileCard>

        <!-- Empty State Mobile -->
        <div v-if="users.data.length === 0" class="flex flex-col items-center justify-center py-12 px-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
          <svg class="w-16 h-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="text-lg font-medium text-slate-900 dark:text-slate-100">Nenhum usuario encontrado</p>
          <p class="text-slate-500 dark:text-slate-400 mt-1">Tente ajustar os filtros de busca.</p>
        </div>

        <!--
          Sem paginacao propria aqui. Este bloco vive em v-if="isMobile || isTablet",
          mas o <Pagination> do sistema fica FORA dos ramos de breakpoint e ja atende
          os tres tamanhos: manter as duas fazia aparecer paginacao duplicada em
          mobile e tablet. A versao removida ainda imprimia link.label do paginator do
          Laravel, que sem lang/pt_BR/pagination.php sai como "pagination.previous".
        -->
      </div>

      <!-- Desktop Table View -->
      <div v-if="isDesktop" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Usuario</th>
                <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden sm:table-cell">Email</th>
                <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cargos</th>
                <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center hidden md:table-cell">Perm. Diretas</th>
                <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden lg:table-cell">Cadastrado em</th>
                <th class="px-4 lg:px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Acoes</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm flex-shrink-0">
                      {{ getUserInitials(user.name) }}
                    </div>
                    <div class="min-w-0">
                      <span class="text-sm font-medium text-slate-900 dark:text-slate-100 block truncate">{{ user.name }}</span>
                      <span class="text-xs text-slate-500 dark:text-slate-400 sm:hidden block truncate">{{ user.email }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400 hidden sm:table-cell">
                  {{ user.email }}
                </td>
                <td class="px-4 lg:px-6 py-4">
                  <div class="flex flex-wrap gap-1">
                    <!-- Cargo e categoria, nao estado: cor explicita em vez de variant. -->
                    <Badge v-for="role in user.roles.slice(0, 2)" :key="role.id" cor="blue" size="sm">
                      {{ role.name }}
                    </Badge>
                    <Badge v-if="user.roles.length > 2" cor="slate" size="sm">
                      +{{ user.roles.length - 2 }}
                    </Badge>
                    <span v-if="user.roles.length === 0" class="text-xs text-slate-400 italic">Nenhum</span>
                  </div>
                </td>
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-center hidden md:table-cell">
                  <Badge cor="purple" size="sm">{{ user.permissions_count ?? 0 }}</Badge>
                </td>
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                  <Badge :variant="statusVariant(effectiveStatus(user))" size="sm">
                    {{ statusLabel(effectiveStatus(user)) }}
                  </Badge>
                </td>
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400 hidden lg:table-cell">
                  {{ formatDate(user.created_at) }}
                </td>
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-right">
                  <ActionButton
                    module="users"
                    resource=""
                    size="sm"
                    :actions="[
                      { action: 'view',   handler: () => router.get(route('admin.permissions.users.show', user.id)) },
                      { action: 'edit',   handler: () => router.get(route('admin.permissions.users.edit', user.id)), allowed: canEdit },
                      { action: 'delete', handler: () => confirmDelete(user), allowed: canDelete },
                    ]"
                  />
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Empty State -->
          <div v-if="users.data.length === 0" class="flex flex-col items-center justify-center py-12 px-4">
            <svg class="w-16 h-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <p class="text-lg font-medium text-slate-900 dark:text-slate-100">Nenhum usuário encontrado</p>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Tente ajustar os filtros de busca.</p>
          </div>
        </div>
      </div>

      <!-- Paginacao: bloco separado abaixo do card, padrao do sistema -->
      <Pagination :pagination="users" @page-change="onPageChange" />

      <ConfirmDialog
        :isOpen="showDeleteDialog"
        title="Excluir Usuário"
        :message="`Tem certeza que deseja excluir o usuário '${userToDelete?.name}'?`"
        description="O usuário será removido da lista (exclusão lógica). Para apenas bloquear o acesso temporariamente, use o toggle 'Status do usuário' na tela de edição."
        variant="danger"
        confirmText="Sim, excluir"
        cancelText="Cancelar"
        :loading="isDeleting"
        @confirm="deleteUser"
        @cancel="cancelDelete"
      />

    </div>

</template>

<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import TableMobileCard from '@/Components/Molecules/Table/TableMobileCard.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useMobile } from '@/Composables/useMobile';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();

const canCreate = can('users.create');
const canEdit = can('users.edit');
const canDelete = can('users.delete');

const props = defineProps({
  users: Object,
  roles: Array,
  filters: Object,
});

const { isMobile, isTablet, isDesktop } = useMobile();

const mobileFields = [
  { key: 'email', label: 'Email' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Cadastrado' },
];

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

const effectiveStatus = (user) => {
  if (user.active === false) return 'inactive';
  if (['suspended', 'blocked', 'pending'].includes(user.status)) return user.status;
  return 'active';
};

const statusLabel = (status) => {
  const labels = {
    active: 'Ativo',
    inactive: 'Desativado',
    suspended: 'Suspenso',
    pending: 'Pendente',
    blocked: 'Bloqueado',
  };
  return labels[status] || status || 'Desconhecido';
};

/**
 * Status do usuario na semantica do Badge.
 *
 * Antes esta funcao devolvia classes Tailwind a mao, e elas divergiam do padrao em
 * tres pontos: sem borda, fundo -50 em vez de -100 e dark com -900/20 em vez de
 * -500/20. Com a semantica, a aparencia vem do Badge e nao ha o que divergir.
 *
 * suspended e blocked caem em danger junto com inactive: sao todos impedimento de
 * acesso, e distingui-los por cor sugeria uma gradacao que o sistema nao trata.
 */
const statusVariant = (status) => ({
  active: 'success',
  pending: 'warning',
  suspended: 'danger',
  inactive: 'danger',
  blocked: 'danger',
}[status] ?? 'default');

const showDeleteDialog = ref(false);
const userToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (user) => {
  userToDelete.value = user;
  showDeleteDialog.value = true;
};

const cancelDelete = () => {
  showDeleteDialog.value = false;
  userToDelete.value = null;
};

const deleteUser = () => {
  if (!userToDelete.value) return;
  isDeleting.value = true;
  router.delete(route('admin.permissions.users.destroy', userToDelete.value.id), {
    preserveScroll: true,
    onFinish: () => {
      isDeleting.value = false;
      showDeleteDialog.value = false;
      userToDelete.value = null;
    },
  });
};

const onPageChange = (page) => {
  router.get(route('admin.permissions.users.index'), { ...form, page }, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>
