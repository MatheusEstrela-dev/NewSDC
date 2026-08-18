<template>

    <Head title="Gerenciamento de Permissões" />
    <div>


      <PageHeader
        title="Gerenciamento de Permissões"
        description="Visualize todas as permissões disponíveis no sistema"
        :icon-image="moduleIcon('permissionamento')"
        variant="gradient"
        class="mb-6 md:mb-8"
      />

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
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 border-b-2 border-transparent hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Cargos
          </Link>
          <Link
            :href="route('admin.permissions.permissions.index')"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400 bg-blue-50/50 dark:bg-blue-900/10 rounded-t-lg"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Permissões
          </Link>
        </div>
      </div>

      <div class="mb-8">
        <StatCardsGrid>
          <StatCard
            title="Total de Permissões"
            :value="stats.total"
            :icon="PermissionsIcon"
            variant="info"
          />
          <StatCard
            title="Módulos"
            :value="stats.modules"
            :icon="ModulesIcon"
            variant="success"
            subtitle="Áreas do sistema"
          />
          <StatCard
            title="Permissões Ativas"
            :value="stats.active"
            :icon="ActiveIcon"
            variant="warning"
            subtitle="Em uso pelos cargos"
          />
        </StatCardsGrid>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
          <div class="flex flex-col md:flex-row gap-4">
            <div class="relative flex-1 md:max-w-md">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                v-model="search"
                type="text"
                placeholder="Buscar permissão..."
                class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                @input="handleSearch"
              />
            </div>
            <select 
              v-model="selectedModule" 
              @change="handleModuleFilter" 
              class="min-w-[200px] px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow cursor-pointer"
            >
              <option value="">Todos os Módulos</option>
              <option v-for="module in availableModules" :key="module" :value="module">
                {{ formatModuleName(module) }}
              </option>
            </select>
          </div>
        </div>

        <div v-if="groupedPermissions && Object.keys(groupedPermissions).length > 0" class="p-6 flex flex-col gap-4">
          <div v-for="(modulePerms, moduleName) in groupedPermissions" :key="moduleName" class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden bg-white dark:bg-slate-800">
            <div 
              class="flex justify-between items-center p-4 bg-slate-50 dark:bg-slate-900/50 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors select-none"
              @click="toggleModule(moduleName)"
            >
              <div class="flex items-center gap-4">
                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-90 text-blue-500': expandedModules.includes(moduleName) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center border"
                     :class="{
                       'bg-blue-50 dark:bg-blue-900/20 text-blue-500 border-blue-200 dark:border-blue-800': moduleName === 'users',
                       'bg-purple-50 dark:bg-purple-900/20 text-purple-500 border-purple-200 dark:border-purple-800': moduleName === 'roles',
                       'bg-red-50 dark:bg-red-900/20 text-red-500 border-red-200 dark:border-red-800': moduleName === 'permissions',
                       'bg-green-50 dark:bg-green-900/20 text-green-500 border-green-200 dark:border-green-800': moduleName === 'pae',
                       'bg-amber-50 dark:bg-amber-900/20 text-amber-500 border-amber-200 dark:border-amber-800': moduleName === 'rat',
                       'bg-teal-50 dark:bg-teal-900/20 text-teal-500 border-teal-200 dark:border-teal-800': moduleName === 'bi',
                       'bg-orange-50 dark:bg-orange-900/20 text-orange-500 border-orange-200 dark:border-orange-800': moduleName === 'integrations',
                       'bg-rose-50 dark:bg-rose-900/20 text-rose-500 border-rose-200 dark:border-rose-800': moduleName === 'webhooks',
                       'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500 border-indigo-200 dark:border-indigo-800': moduleName === 'system',
                       'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700': !['users','roles','permissions','pae','rat','bi','integrations','webhooks','system'].includes(moduleName)
                     }"
                >
                  <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">{{ formatModuleName(moduleName) }}</h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ getModuleDescription(moduleName) }}</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-xs font-semibold rounded-md border border-blue-100 dark:border-blue-900/30">
                {{ modulePerms.length }} permissões
              </span>
            </div>

            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform scale-y-95 opacity-0"
              enter-to-class="transform scale-y-100 opacity-100"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="transform scale-y-100 opacity-100"
              leave-to-class="transform scale-y-95 opacity-0"
            >
              <div v-show="expandedModules.includes(moduleName)" class="p-4 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                  <div
                    v-for="permission in modulePerms"
                    :key="permission.id"
                    class="group bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:shadow-md hover:border-slate-300 dark:hover:border-slate-600 transition-all duration-200"
                  >
                    <div class="p-3 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-800/50 group-hover:bg-slate-50 dark:group-hover:bg-slate-800 transition-colors">
                      <PermissionBadge
                        :label="permission.name"
                        :module="moduleName"
                        :showIcon="true"
                      />
                    </div>
                    <div class="p-3">
                      <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 h-10 mb-3">{{ permission.description || 'Sem descrição' }}</p>
                      <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-500">
                        <div class="flex items-center gap-1">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 4 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                          </svg>
                          <span>{{ permission.guard_name || 'web' }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                          </svg>
                          <span>{{ permission.roles_count || 0 }} cargos</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-12 px-4">
          <svg class="w-16 h-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100">Nenhuma permissão encontrada</h3>
          <p class="text-slate-500 dark:text-slate-400 mt-1">Não há permissões que correspondam aos critérios de busca.</p>
        </div>
      </div>
    </div>

</template>

<script setup>
import { ref, computed, h } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import PermissionBadge from '@/Components/Admin/PermissionBadge.vue';
import { useMobile } from '@/Composables/useMobile';

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
  permissions: {
    type: Array,
    required: true
  },
  stats: {
    type: Object,
    default: () => ({ total: 0, modules: 0, active: 0 })
  },
  filters: {
    type: Object,
    default: () => ({ search: '', module: '' })
  },
  /**
   * Prefixo de slug -> rotulo do modulo, vindo de config/permissions.php.
   *
   * Substitui os dois catalogos hardcoded que esta tela mantinha: eles cobriam 9
   * modulos e o config tem 17, entao quem ficasse de fora aparecia como o
   * prefixo em caixa alta com "Modulo do sistema" na descricao. Derivado do
   * config, modulo novo entra sozinho.
   */
  modulos: {
    type: Object,
    default: () => ({}),
  },
});

const PermissionsIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' })
]);

const ModulesIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' })
]);

const ActiveIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })
]);

const { isMobile } = useMobile();

const search = ref(props.filters.search || '');
const selectedModule = ref(props.filters.module || '');
const expandedModules = ref([]);

const groupedPermissions = computed(() => {
  return props.permissions.reduce((acc, permission) => {
    const module = permission.name.split('.')[0];
    if (!acc[module]) {
      acc[module] = [];
    }
    acc[module].push(permission);
    return acc;
  }, {});
});

const availableModules = computed(() => {
  return Object.keys(groupedPermissions.value).sort();
});

const toggleModule = (moduleName) => {
  const index = expandedModules.value.indexOf(moduleName);
  if (index > -1) {
    expandedModules.value.splice(index, 1);
  } else {
    expandedModules.value.push(moduleName);
  }
};

const handleSearch = debounce(() => {
  router.get(route('admin.permissions.permissions.index'), {
    search: search.value,
    module: selectedModule.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
}, 300);

const handleModuleFilter = () => {
  router.get(route('admin.permissions.permissions.index'), {
    search: search.value,
    module: selectedModule.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

const formatModuleName = (module) => {
  return props.modulos?.[module] || module.toUpperCase();
};

/**
 * Descricao do modulo. O config nao guarda uma frase por modulo, entao a tela
 * informa o prefixo de slug e quantas permissoes ele tem -- que e o que orienta
 * quem administra acesso, mais util que a frase generica "Modulo do sistema"
 * que os modulos fora do catalogo hardcoded recebiam.
 */
const getModuleDescription = (module) => {
  const total = (props.permissions ?? []).filter(
    (p) => typeof p.name === 'string' && p.name.startsWith(`${module}.`),
  ).length;

  return `Prefixo ${module}.* — ${total} permissao(oes)`;
};

</script>
