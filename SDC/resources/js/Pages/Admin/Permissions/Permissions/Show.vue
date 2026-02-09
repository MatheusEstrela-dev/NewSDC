<template>
  <AuthenticatedLayout>
    <Head title="Detalhes da Permissão" />
    <div class="w-full py-6">
      <!-- Page Header -->
      <div class="mb-8">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Detalhes da Permissão</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Visualize informações e cargos associados a esta permissão</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- Main Content -->
        <div class="xl:col-span-3 space-y-6">
          
          <!-- Info Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
              <div class="flex items-center gap-6">
                <div class="w-20 h-20 rounded-2xl flex items-center justify-center shrink-0 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-purple-500 shadow-sm">
                  <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-1 truncate">{{ permission.name }}</h2>
                  <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                      {{ formatModuleName(permission.name.split('.')[0]) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
              <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Descrição</h3>
              <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed">
                {{ permission.description || 'Nenhuma descrição disponível para esta permissão.' }}
              </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 p-6">
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">#{{ permission.id }}</div>
              </div>
              <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Slug do Sistema</div>
                <div class="text-sm font-mono text-slate-900 dark:text-slate-100">{{ permission.name }}</div>
              </div>
               <div class="space-y-1">
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Módulo</div>
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatModuleName(permission.name.split('.')[0]) }}</div>
              </div>
            </div>
          </div>

          <!-- Roles with Permission Card -->
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
              <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Cargos com esta Permissão
              </h3>
              <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                {{ permission.roles?.length || 0 }}
              </span>
            </div>
            
            <div v-if="permission.roles && permission.roles.length > 0" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
              <Link
                v-for="role in permission.roles"
                :key="role.id"
                :href="route('admin.permissions.roles.show', role.id)"
                class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg hover:shadow-md hover:translate-x-1 transition-all duration-200 group"
              >
                <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-600 shrink-0 border border-amber-200 dark:border-amber-900">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">{{ role.name }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ role.slug }}</div>
                </div>
                <svg class="w-5 h-5 text-slate-400 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </Link>
            </div>
            <div v-else class="p-8 text-center text-slate-500 dark:text-slate-400">
              Nenhum cargo possui esta permissão.
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="xl:col-span-1 space-y-6">
           <StatsCard
            label="Total de Cargos"
            :value="permission.roles?.length || 0"
            :icon="RolesIcon"
            variant="warning"
          />
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { h } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatsCard from '@/Components/Admin/StatsCard.vue';

const props = defineProps({
  permission: {
    type: Object,
    required: true
  }
});

const RolesIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
  h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
]);

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
