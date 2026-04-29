<template>
    <Head title="Listagem RAT" />

    <div class="pb-8 transition-colors duration-300">
        <div class="w-full">
            <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-xl sm:rounded-xl p-4 sm:p-6 border border-slate-200 dark:border-slate-800 transition-colors duration-300">
                <!-- Cabeçalho -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Relatório de Atividades do CBMERJ — RAT</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-1">Gestão de ocorrências pela nova estrutura polimórfica.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('rat.create')"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white font-semibold rounded-lg shadow-md shadow-blue-500/20 transition-all font-sans"
                        >
                            Nova Ocorrência
                        </Link>
                        <a
                            :href="route('rat.export')"
                            class="inline-flex items-center px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-lg transition-all"
                        >
                            Exportar CSV
                        </a>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 bg-slate-50 dark:bg-slate-800/30 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                    <input
                        type="text"
                        v-model="localFilters.status"
                        placeholder="Filtrar por status..."
                        class="rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-blue-500 transition-colors"
                        @input="applyFilters"
                    >
                    <input
                        type="text"
                        v-model="localFilters.numero_bos"
                        placeholder="Número do BO..."
                        class="rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-blue-500 transition-colors"
                        @input="applyFilters"
                    >
                </div>

                <!-- Tabela -->
                <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-300 uppercase text-xs font-bold tracking-wider">
                                <th class="px-4 py-4 border-b dark:border-slate-700">ID</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700">Nº BO</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700">Status</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700">Criado em</th>
                                <th class="px-4 py-4 border-b dark:border-slate-700 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            <tr v-if="!ocorrencias?.data?.length">
                                <td colspan="5" class="px-4 py-12 text-center text-slate-400 dark:text-slate-500 italic">
                                    Nenhuma ocorrência encontrada.
                                </td>
                            </tr>
                            <tr 
                                v-for="oc in ocorrencias.data" 
                                :key="oc.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group"
                            >
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-slate-200">{{ oc.id }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-400 font-mono">{{ oc.numero_bos ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="statusClass(oc.status)" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-tight">
                                        {{ oc.status ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">{{ formatDate(oc.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-3">
                                        <!-- Ver -->
                                        <Link
                                            :href="route('rat.show', oc.id)"
                                            class="p-1.5 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-all"
                                            title="Visualizar"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                        <!-- Editar -->
                                        <Link
                                            :href="route('rat.edit', oc.id)"
                                            class="p-1.5 rounded-lg text-amber-500 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition-all"
                                            title="Editar"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </Link>
                                        <!-- Excluir -->
                                        <button
                                            type="button"
                                            @click="deleteRat(oc.id)"
                                            class="p-1.5 rounded-lg text-rose-500 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-all"
                                            title="Excluir"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div v-if="ocorrencias?.last_page > 1" class="mt-8 flex justify-center gap-2">
                    <Link
                        v-for="page in ocorrencias.last_page"
                        :key="page"
                        :href="`?page=${page}`"
                        :class="page === ocorrencias.current_page 
                            ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/20' 
                            : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'"
                        class="rounded-lg border px-4 py-2 text-sm font-semibold transition-all duration-200"
                    >
                        {{ page }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    ocorrencias: { type: Object, default: () => ({ data: [] }) },
    filters:     { type: Object, default: () => ({}) },
});

const localFilters = reactive({
    status:     props.filters?.status ?? '',
    numero_bos: props.filters?.numero_bos ?? '',
});

function applyFilters() {
    router.get(route('rat.index'), localFilters, { 
        preserveState: true, 
        replace: true,
        preserveScroll: true 
    });
}

function deleteRat(id) {
    if (!confirm('Tem certeza que deseja excluir esta ocorrência? Ela será removida da lista, mas permanecerá arquivada no sistema.')) {
        return;
    }

    router.delete(route('rat.destroy', id), {
        onSuccess: () => {
            // Notificações podem ser adicionadas aqui
        },
    });
}

function statusClass(status) {
    const map = {
        finalizado:   'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400',
        em_andamento: 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400',
        rascunho:     'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400',
    };
    return map[status] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400';
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('pt-BR');
}
</script>
