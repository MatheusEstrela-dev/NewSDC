<template>
    <div>
        <Head :title="`RAT — Alvos da Ocorrência #${ocorrencia?.id}`" />

        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <div class="mb-6">
                <Link :href="route('compdec.rat.alvos.index')" class="text-sm text-blue-600 hover:underline">&larr; Voltar para Alvos</Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Alvos — Ocorrência #{{ ocorrencia?.id }}</h1>
                <p class="text-sm text-gray-500">BO: {{ ocorrencia?.numero_bos ?? '—' }}</p>
            </div>

            <!-- Dados da Ocorrência -->
            <div class="bg-white rounded-lg shadow border border-gray-200 p-6 mb-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Dados da Ocorrência</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span :class="statusClass(ocorrencia?.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium">
                                {{ ocorrencia?.status ?? '—' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Data/Hora Fato</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ocorrencia?.data_hora_fato ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2" v-if="ocorrencia?.descricao">
                        <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ ocorrencia.descricao }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Ações -->
            <div class="flex justify-end gap-3">
                <Link
                    :href="route('compdec.rat.show', ocorrencia?.id)"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    Ver Ocorrência Completa
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    ocorrencia: { type: Object, default: () => ({}) },
});

function statusClass(status) {
    const map = {
        finalizado:   'bg-green-100 text-green-800',
        em_andamento: 'bg-yellow-100 text-yellow-800',
        rascunho:     'bg-gray-100 text-gray-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
}
</script>
