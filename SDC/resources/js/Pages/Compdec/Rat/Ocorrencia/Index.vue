<template>
    <div>
        <Head title="RAT — Ocorrências (Estrutura Polimórfica)" />

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Ocorrências RAT</h1>
                    <p class="mt-1 text-sm text-gray-500">Listagem pela nova estrutura polimórfica (relatos).</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="mb-4 flex flex-col sm:flex-row gap-3">
                <input
                    v-model="localFilters.status"
                    type="text"
                    placeholder="Filtrar por status..."
                    class="w-full sm:w-48 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                    @input="applyFilters"
                />
                <input
                    v-model="localFilters.numero_bos"
                    type="text"
                    placeholder="Número do BO..."
                    class="w-full sm:w-48 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                    @input="applyFilters"
                />
            </div>

            <!-- Tabela -->
            <div class="overflow-hidden rounded-lg border border-gray-200 shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nº BO</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Criado em</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-if="!ocorrencias?.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">Nenhuma ocorrência encontrada.</td>
                        </tr>
                        <tr v-for="oc in ocorrencias?.data" :key="oc.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ oc.id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ oc.numero_bos ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="statusClass(oc.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ oc.status ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(oc.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('compdec.rat.ocorrencias.show', oc.id)" class="text-sm text-blue-600 hover:underline">
                                    Ver
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
    ocorrencias: { type: Object, default: () => ({}) },
});

const localFilters = reactive({ status: '', numero_bos: '' });

function applyFilters() {
    router.get(route('compdec.rat.ocorrencias.index'), localFilters, { preserveState: true, replace: true });
}

function statusClass(status) {
    const map = {
        finalizado:   'bg-green-100 text-green-800',
        em_andamento: 'bg-yellow-100 text-yellow-800',
        rascunho:     'bg-gray-100 text-gray-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('pt-BR');
}
</script>
