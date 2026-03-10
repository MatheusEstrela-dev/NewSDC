<template>
    <div>
        <Head title="RAT — Alvos de Ocorrências" />

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Alvos — Locais de Interesse</h1>
                <p class="mt-1 text-sm text-gray-500">Endereços e locais vinculados às ocorrências RAT em andamento.</p>
            </div>

            <!-- Tabela de Ocorrências com Alvos -->
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
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">Nenhuma ocorrência em andamento.</td>
                        </tr>
                        <tr v-for="oc in ocorrencias?.data" :key="oc.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ oc.id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ oc.numero_bos ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">
                                    {{ oc.status ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(oc.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('compdec.rat.alvos.show', oc.id)" class="text-sm text-blue-600 hover:underline">
                                    Ver Alvos
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
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    ocorrencias: { type: Object, default: () => ({}) },
});

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('pt-BR');
}
</script>
