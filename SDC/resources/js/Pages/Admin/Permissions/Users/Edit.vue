<template>
  <AuthenticatedLayout>
    <Head title="Editar Usuário" />
    <div class="w-full py-6">


      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
          <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Editar Usuário</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Atualize informações, cargos e permissões</p>
          </div>
      </div>

      <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-3 space-y-6">
          
          <!-- Basic Info Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Informações Básicas
              </h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nome Completo</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100 placeholder-slate-400"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.name }"
                  placeholder="Digite o nome completo"
                  required
                />
                <span v-if="errors.name" class="text-xs text-red-500 font-medium">{{ errors.name }}</span>
              </div>

              <div class="space-y-2">
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100 placeholder-slate-400"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.email }"
                  placeholder="Digite o email"
                  required
                />
                <span v-if="errors.email" class="text-xs text-red-500 font-medium">{{ errors.email }}</span>
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
                Cargos
              </h3>
              <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                {{ selectedRolesCount }}
              </span>
            </div>

            <div class="p-6 space-y-3">
              <div
                v-for="role in availableRoles"
                :key="role.id"
                @click="!checkRoleDisabled(role) && toggleRole(role.id)"
                class="relative flex items-center gap-4 p-4 rounded-xl border-2 transition-all cursor-pointer"
                :class="[
                  form.roles.includes(role.id)
                    ? 'border-teal-500 bg-teal-50/30 dark:bg-teal-900/10'
                    : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600',
                  checkRoleDisabled(role) ? 'opacity-50 cursor-not-allowed' : ''
                ]"
              >
                <!-- Role Icon - Design Pattern from Roles/Index -->
                <div
                  class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0 border transition-colors"
                  :class="getRoleIconClass(role.hierarchy_level, form.roles.includes(role.id))"
                >
                  <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                  </svg>
                </div>

                <!-- Role Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <h4 class="font-semibold text-slate-900 dark:text-slate-100">{{ role.name }}</h4>
                  </div>
                  <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-1">{{ role.description }}</p>
                  <div class="flex items-center gap-4 mt-2">
                    <span class="text-xs text-slate-400 dark:text-slate-500">Nivel {{ role.hierarchy_level }}</span>
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ role.permissions_count || 0 }} permissoes</span>
                  </div>
                </div>

                <!-- Checkbox -->
                <div class="flex-shrink-0">
                  <div
                    class="w-6 h-6 rounded-md border-2 flex items-center justify-center transition-colors"
                    :class="form.roles.includes(role.id)
                      ? 'bg-teal-600 border-teal-600'
                      : 'border-slate-300 dark:border-slate-600'"
                  >
                    <svg v-if="form.roles.includes(role.id)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Direct Permissions Card - Renderizado via ACL Config -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <div class="flex justify-between items-center">
                <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                  <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Permissoes Efetivas
                </h3>
                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                  {{ selectedPermissionsCount }}
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                Marque/desmarque para definir as permissoes finais do usuario.
                <span class="text-blue-600 dark:text-blue-400">cargo</span> indica permissao herdada.
              </p>
            </div>

            <div class="p-6 space-y-4">
              <!-- Itera sobre modulos do config/permissions.php via $page.props.acl.modules -->
              <div v-for="(groups, moduleName) in aclModules" :key="moduleName" class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                <div
                  @click="toggleModule(moduleName)"
                  class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/30 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-900/50 transition-colors select-none"
                >
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-90 text-blue-500': expandedModules.includes(moduleName) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <h4 class="font-semibold text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider">{{ moduleName }}</h4>
                  </div>
                  <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded">
                    {{ getModuleSelectedCount(moduleName) }}/{{ getModuleTotalCount(groups) }}
                  </span>
                </div>

                <div v-show="expandedModules.includes(moduleName)" class="border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                  <!-- Itera sobre grupos dentro do modulo -->
                  <div v-for="(permissions, groupName) in groups" :key="groupName" class="p-4 border-b border-slate-100 dark:border-slate-700 last:border-b-0">
                    <h5 class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-3 uppercase tracking-wide">{{ groupName }}</h5>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                      <!-- Itera sobre acoes/permissoes do grupo -->
                      <div v-for="(slug, action) in permissions" :key="slug" class="flex items-center gap-1">
                        <input
                          :id="`perm-${slug}`"
                          type="checkbox"
                          :value="slug"
                          v-model="form.direct_permissions"
                          :disabled="isImmutablePermission(slug)"
                          class="w-4 h-4 text-purple-600 bg-slate-100 border-slate-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-slate-800 focus:ring-2 dark:bg-slate-700 dark:border-slate-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        />
                        <label
                          :for="`perm-${slug}`"
                          class="text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none"
                          :class="{ 'opacity-50 cursor-not-allowed': isImmutablePermission(slug) }"
                          :title="slug"
                        >
                          {{ formatActionName(action) }}
                        </label>
                        <span
                          v-if="isFromRole(slug)"
                          class="text-[10px] px-1 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"
                          title="Herdada do cargo"
                        >
                          cargo
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="lg:col-span-1">
          <div class="sticky top-6 space-y-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden p-6">
              <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Resumo das Alterações</h3>
              
              <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center text-sm border-b border-slate-100 dark:border-slate-700 pb-2">
                  <span class="text-slate-600 dark:text-slate-400">Cargos Selecionados</span>
                  <span class="font-medium text-slate-900 dark:text-slate-100">{{ selectedRolesCount }}</span>
                </div>
                <div class="flex justify-between items-center text-sm border-b border-slate-100 dark:border-slate-700 pb-2">
                  <span class="text-slate-600 dark:text-slate-400">Permissões Diretas</span>
                  <span class="font-medium text-slate-900 dark:text-slate-100">{{ selectedPermissionsCount }}</span>
                </div>
                <div class="flex justify-between items-center text-sm font-medium pt-1">
                  <span class="text-slate-800 dark:text-slate-200">Total de Permissões</span>
                  <span class="text-blue-600 dark:text-blue-400">{{ totalPermissionsCount }}</span>
                </div>
              </div>

              <div class="space-y-3">
                <button
                  type="submit"
                  class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm disabled:opacity-75 disabled:cursor-not-allowed"
                  :disabled="form.processing"
                >
                  <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span v-else>Salvar Alterações</span>
                </button>
                
                <Link
                  :href="route('admin.permissions.users.show', user.id)"
                  class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-lg transition-colors"
                >
                  Cancelar
                </Link>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useHierarchy } from '@/Composables/useHierarchy';
import { route } from 'ziggy-js';

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

const page = usePage();
const { isSuperAdmin, canAssignRole, isImmutablePermission: checkImmutable } = useHierarchy();

const aclModules = computed(() => page.props.acl?.modules ?? {});

const checkRoleDisabled = (role) => {
  if (isSuperAdmin.value) return false;
  if (role.slug === 'super-admin' && !props.canEditSuperAdmin) return true;
  return !canAssignRole(role);
};

const isImmutablePermission = (slug) => {
  return checkImmutable(slug);
};

const getRoleIconClass = (hierarchyLevel, isSelected) => {
  const baseClasses = {
    0: 'bg-red-50 dark:bg-red-900/20 text-red-500 border-red-200 dark:border-red-800',
    1: 'bg-amber-50 dark:bg-amber-900/20 text-amber-500 border-amber-200 dark:border-amber-800',
    2: 'bg-blue-50 dark:bg-blue-900/20 text-blue-500 border-blue-200 dark:border-blue-800',
    3: 'bg-purple-50 dark:bg-purple-900/20 text-purple-500 border-purple-200 dark:border-purple-800',
    4: 'bg-teal-50 dark:bg-teal-900/20 text-teal-500 border-teal-200 dark:border-teal-800',
    5: 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500 border-indigo-200 dark:border-indigo-800',
    6: 'bg-pink-50 dark:bg-pink-900/20 text-pink-500 border-pink-200 dark:border-pink-800',
  };

  if (isSelected) {
    return 'bg-teal-100 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 border-teal-300 dark:border-teal-700';
  }

  return baseClasses[hierarchyLevel] || 'bg-slate-50 dark:bg-slate-900/20 text-slate-500 border-slate-200 dark:border-slate-700';
};

const toggleRole = (roleId) => {
  const index = form.roles.indexOf(roleId);
  if (index > -1) {
    form.roles.splice(index, 1);
  } else {
    form.roles.push(roleId);
  }
};

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  roles: props.user.roles?.map(r => r.id) || [],
  direct_permissions: props.user.effective_permissions || props.user.direct_permissions?.map(p => p.name) || []
});

const rolePermissionsList = computed(() => props.user.role_permissions || []);

const isFromRole = (slug) => {
  return rolePermissionsList.value.includes(slug) && !props.user.direct_permissions?.some(p => p.name === slug);
};

const expandedModules = ref(['SISTEMA', 'PAE', 'RAT']);

const selectedRolesCount = computed(() => form.roles.length);

const selectedPermissionsCount = computed(() => form.direct_permissions.length);

const totalPermissionsCount = computed(() => {
  return form.direct_permissions.length;
});

const toggleModule = (moduleName) => {
  const index = expandedModules.value.indexOf(moduleName);
  if (index > -1) {
    expandedModules.value.splice(index, 1);
  } else {
    expandedModules.value.push(moduleName);
  }
};

const getModuleTotalCount = (groups) => {
  let count = 0;
  for (const groupName in groups) {
    count += Object.keys(groups[groupName]).length;
  }
  return count;
};

const getModuleSelectedCount = (moduleName) => {
  const groups = aclModules.value[moduleName] ?? {};
  let count = 0;
  for (const groupName in groups) {
    for (const action in groups[groupName]) {
      const slug = groups[groupName][action];
      if (form.direct_permissions.includes(slug)) {
        count++;
      }
    }
  }
  return count;
};

const formatActionName = (action) => {
  const actionNames = {
    view: 'Visualizar',
    create: 'Criar',
    edit: 'Editar',
    delete: 'Deletar',
    manage: 'Gerenciar',
    approve: 'Aprovar',
    finalize: 'Finalizar',
    execute: 'Executar',
    export: 'Exportar',
    send: 'Enviar',
    logs: 'Ver Logs',
    cache: 'Cache',
    settings: 'Config'
  };
  return actionNames[action] || action.charAt(0).toUpperCase() + action.slice(1);
};

const submitForm = () => {
  form.put(route('admin.permissions.users.update', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      // Success
    }
  });
};
</script>
