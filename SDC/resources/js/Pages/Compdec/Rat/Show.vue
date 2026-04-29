<template>
    <div>
        <Head :title="`RAT — Ocorrência #${ocorrencia?.id}`" />

        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <Link :href="route('rat.index')" class="text-sm text-blue-600 hover:underline">&larr; Voltar</Link>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">Ocorrência RAT #{{ ocorrencia?.id }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        BO: {{ ocorrencia?.numero_bos ?? '—' }} &bull;
                        Criado em: {{ formatDate(ocorrencia?.created_at) }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('rat.edit', ocorrencia?.id)"
                        class="rounded-md bg-yellow-500 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-600"
                    >Editar</Link>
                </div>
            </div>

            <!-- Status badge -->
            <div class="mb-6">
                <span :class="statusClass(ocorrencia?.status)" class="inline-flex rounded-full px-3 py-1 text-sm font-semibold">
                    {{ ocorrencia?.status ?? '—' }}
                </span>
            </div>

            <!-- Dados gerais -->
            <div class="bg-white rounded-lg shadow border border-gray-200 divide-y divide-gray-100">
                <div v-for="(value, key) in displayFields" :key="key" class="px-6 py-4 flex flex-col sm:flex-row sm:items-start gap-1">
                    <span class="w-48 text-sm font-medium text-gray-500 flex-shrink-0">{{ key }}</span>
                    <span class="text-sm text-gray-900">{{ value ?? '—' }}</span>
                </div>
            </div>

            <!-- Histórico -->
            <div v-if="ocorrencia?.historico" class="mt-6 bg-white rounded-lg shadow border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-2">Histórico / Observações</h2>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ ocorrencia.historico }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    ocorrencia: { type: Object, default: () => ({}) },
});

const displayFields = computed(() => ({
    'Número BO':       props.ocorrencia?.numero_bos,
    'Prazo de Edição':  props.ocorrencia?.prazo_edicao
        ? new Date(props.ocorrencia.prazo_edicao).toLocaleString('pt-BR') : null,
    'Status':            props.ocorrencia?.status,
    'Criado em':         props.ocorrencia?.created_at ? new Date(props.ocorrencia.created_at).toLocaleString('pt-BR') : null,
    'Atualizado em':     props.ocorrencia?.updated_at ? new Date(props.ocorrencia.updated_at).toLocaleString('pt-BR') : null,
}));

function statusClass(status) {
    const map = {
        finalizado:    'bg-green-100 text-green-800',
        em_andamento:  'bg-yellow-100 text-yellow-800',
        rascunho:      'bg-gray-100 text-gray-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('pt-BR');
}
</script>
