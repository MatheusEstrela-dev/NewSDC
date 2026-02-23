<template>

    <Head title="Criar Novo Cargo" />
    <div class="w-full py-6">
      <!-- Page Header -->
      <div class="mb-8">


        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Criar Novo Cargo</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Defina nome, descrição e permissões do cargo</p>
          </div>
        </div>
      </div>

      <form @submit.prevent="submitForm" class="grid grid-cols-1 xl:grid-cols-4 gap-6 items-start">
        <!-- Main Content -->
        <div class="xl:col-span-3 space-y-6">
          
          <!-- Basic Info Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Informações Básicas
              </h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="col-span-1 md:col-span-2 space-y-2">
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nome do Cargo *</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100 placeholder-slate-400"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.name }"
                  placeholder="Ex: Administrador, Gestor, Analista"
                  required
                />
                <span v-if="errors.name" class="text-xs text-red-500 font-medium">{{ errors.name }}</span>
              </div>

              <div class="space-y-2">
                <label for="slug" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Slug (Identificador) *</label>
                <input
                  id="slug"
                  v-model="form.slug"
                  type="text"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100 placeholder-slate-400"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.slug }"
                  placeholder="Ex: admin, gestor, analista"
                  required
                />
                <span class="text-xs text-slate-500 dark:text-slate-400">Somente letras minúsculas, números e hífens</span>
                <span v-if="errors.slug" class="text-xs text-red-500 font-medium">{{ errors.slug }}</span>
              </div>

              <div class="space-y-2">
                <label for="hierarchy_level" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nível Hierárquico *</label>
                <select
                  id="hierarchy_level"
                  v-model="form.hierarchy_level"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.hierarchy_level }"
                  required
                >
                  <option value="">Selecione um nível</option>
                  <option value="0">Nível 0 - Super Admin</option>
                  <option value="1">Nível 1 - Admin</option>
                  <option value="2">Nível 2 - Gestor</option>
                  <option value="3">Nível 3 - Operacional</option>
                  <option value="4">Nível 4 - Visualização</option>
                </select>
                <span class="text-xs text-slate-500 dark:text-slate-400">Quanto menor o número, maior o nível de acesso</span>
                <span v-if="errors.hierarchy_level" class="text-xs text-red-500 font-medium">{{ errors.hierarchy_level }}</span>
              </div>

              <div class="col-span-1 md:col-span-2 space-y-2">
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Descrição *</label>
                <textarea
                  id="description"
                  v-model="form.description"
                  class="w-full px-4 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow text-slate-900 dark:text-slate-100 placeholder-slate-400"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.description }"
                  placeholder="Descreva as responsabilidades e escopo deste cargo"
                  rows="3"
                  required
                />
                <span v-if="errors.description" class="text-xs text-red-500 font-medium">{{ errors.description }}</span>
              </div>

              <div class="col-span-1 md:col-span-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-slate-800 focus:ring-2 dark:bg-slate-700 dark:border-slate-600"
                  />
                  <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Cargo Ativo</span>
                </label>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 ml-6">Cargos inativos não podem ser atribuídos a novos usuários</p>
              </div>
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
                {{ selectedPermissionsCount }}
              </span>
            </div>

            <div class="p-6 space-y-4">
              <div v-for="(modulePermissions, moduleName) in groupedPermissions" :key="moduleName" class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                <div 
                  @click="toggleModule(moduleName)"
                  class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/30 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-900/50 transition-colors select-none"
                >
                  <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-90 text-blue-500': expandedModules.includes(moduleName) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <h4 class="font-semibold text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider">{{ formatModuleName(moduleName) }}</h4>
                  </div>
                  <div class="flex items-center gap-3">
                    <button
                      type="button"
                      @click.stop="selectAllModule(moduleName)"
                      class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors"
                    >
                      Selecionar Todos
                    </button>
                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded">
                      {{ getModuleSelectedCount(moduleName) }}/{{ modulePermissions.length }}
                    </span>
                  </div>
                </div>

                <div v-show="expandedModules.includes(moduleName)" class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                  <div v-for="permission in modulePermissions" :key="permission.id" class="flex items-start">
                    <div class="flex items-center h-5">
                      <input
                        :id="`perm-${permission.id}`"
                        type="checkbox"
                        :value="permission.id"
                        v-model="form.permissions"
                        class="w-4 h-4 text-purple-600 bg-slate-100 border-slate-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-slate-800 focus:ring-2 dark:bg-slate-700 dark:border-slate-600"
                      />
                    </div>
                    <label :for="`perm-${permission.id}`" class="ml-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                      {{ formatPermissionName(permission.name) }}
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="xl:col-span-1" ref="sidebarContainer">
          <div 
            ref="sidebarContent"
            :class="[
              'transition-all duration-300',
              isSticky ? 'fixed top-24 z-10' : ''
            ]"
            :style="isSticky ? { width: sidebarWidth + 'px' } : {}"
          >
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden p-6">
              <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-4">Resumo</h3>
              
              <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center text-sm border-b border-slate-100 dark:border-slate-700 pb-2">
                  <span class="text-slate-600 dark:text-slate-400">Permissões Selecionadas</span>
                  <span class="font-medium text-slate-900 dark:text-slate-100">{{ selectedPermissionsCount }}</span>
                </div>
                <div class="flex justify-between items-center text-sm border-b border-slate-100 dark:border-slate-700 pb-2">
                  <span class="text-slate-600 dark:text-slate-400">Módulos</span>
                  <span class="font-medium text-slate-900 dark:text-slate-100">{{ selectedModulesCount }}</span>
                </div>
                <div class="flex justify-between items-center text-sm font-medium pt-1">
                  <span class="text-slate-800 dark:text-slate-200">Nível Hierárquico</span>
                  <span class="text-blue-600 dark:text-blue-400">{{ form.hierarchy_level || '-' }}</span>
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
                  <span v-else>Criar Cargo</span>
                </button>
                
                <Link
                  :href="route('admin.permissions.roles.index')"
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

</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  availablePermissions: {
    type: Array,
    default: () => []
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const form = useForm({
  name: '',
  slug: '',
  hierarchy_level: '',
  description: '',
  is_active: true,
  permissions: []
});

const expandedModules = ref(['users', 'pae', 'rat']);

const groupedPermissions = computed(() => {
  return props.availablePermissions.reduce((acc, permission) => {
    const module = permission.name.split('.')[0];
    if (!acc[module]) {
      acc[module] = [];
    }
    acc[module].push(permission);
    return acc;
  }, {});
});

const selectedPermissionsCount = computed(() => form.permissions.length);

const selectedModulesCount = computed(() => {
  const modules = new Set();
  props.availablePermissions.forEach(permission => {
    if (form.permissions.includes(permission.id)) {
      const module = permission.name.split('.')[0];
      modules.add(module);
    }
  });
  return modules.size;
});

const toggleModule = (moduleName) => {
  const index = expandedModules.value.indexOf(moduleName);
  if (index > -1) {
    expandedModules.value.splice(index, 1);
  } else {
    expandedModules.value.push(moduleName);
  }
};

const selectAllModule = (moduleName) => {
  const modulePermissionIds = groupedPermissions.value[moduleName]?.map(p => p.id) || [];
  const allSelected = modulePermissionIds.every(id => form.permissions.includes(id));

  if (allSelected) {
    form.permissions = form.permissions.filter(id => !modulePermissionIds.includes(id));
  } else {
    const newPermissions = [...new Set([...form.permissions, ...modulePermissionIds])];
    form.permissions = newPermissions;
  }
};

const getModuleSelectedCount = (moduleName) => {
  const modulePermissionIds = groupedPermissions.value[moduleName]?.map(p => p.id) || [];
  return form.permissions.filter(id => modulePermissionIds.includes(id)).length;
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

const formatPermissionName = (name) => {
  const parts = name.split('.');
  const action = parts[parts.length - 1];
  const actionNames = {
    view: 'Visualizar',
    create: 'Criar',
    edit: 'Editar',
    delete: 'Deletar',
    manage: 'Gerenciar'
  };
  return actionNames[action] || action;
};

const submitForm = () => {
  form.post(route('admin.permissions.roles.store'), {
    preserveScroll: true
  });
};

// Scroll Sticky Logic
import { onMounted, onUnmounted } from 'vue';

const sidebarContainer = ref(null);
const sidebarContent = ref(null);
const isSticky = ref(false);
const sidebarWidth = ref(0);

const handleScroll = () => {
  if (!sidebarContainer.value) return;
  
  const rect = sidebarContainer.value.getBoundingClientRect();
  isSticky.value = rect.top < 96;
};

const updateWidth = () => {
  if (sidebarContainer.value) {
    sidebarWidth.value = sidebarContainer.value.clientWidth;
  }
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  window.addEventListener('resize', updateWidth);
  updateWidth();
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('resize', updateWidth);
});
</script>
