<template>
    <div>
        <Head title="RAT — Boletins de Ocorrência" />

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Boletins de Ocorrência (BO)</h1>
                    <p class="mt-1 text-sm text-gray-500">BOs vinculados às ocorrências RAT.</p>
                </div>
            </div>

            <!-- Filtro por número BO -->
            <div class="mb-4">
                <input
                    v-model="localFilters.numero_bos"
                    type="text"
                    placeholder="Buscar por número do BO..."
                    class="w-full sm:w-64 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
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
                        <tr v-if="!bos?.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">Nenhum BO encontrado.</td>
                        </tr>
                        <tr v-for="bo in bos?.data" :key="bo.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ bo.id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ bo.numero_bos ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="statusClass(bo.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium">
                                    {{ bo.status ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(bo.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('rat.show', bo.id)" class="text-sm text-blue-600 hover:underline">Ver</Link>
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
    bos:     { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const localFilters = reactive({
    numero_bos: props.filters?.numero_bos ?? '',
});

function applyFilters() {
    router.get(route('rat.bo.index'), localFilters, { preserveState: true, replace: true });
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
