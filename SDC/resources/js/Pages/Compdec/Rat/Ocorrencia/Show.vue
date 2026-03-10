<template>
    <div>
        <Head :title="`RAT — Ocorrência #${ocorrencia?.id} (Relatos)`" />

        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <Link :href="route('compdec.rat.ocorrencias.index')" class="text-sm text-blue-600 hover:underline">&larr; Voltar</Link>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">Ocorrência #{{ ocorrencia?.id }}</h1>
                    <p class="text-sm text-gray-500">BO: {{ ocorrencia?.numero_bos ?? '—' }}</p>
                </div>
                <div>
                    <form
                        v-if="ocorrencia?.status !== 'finalizado'"
                        @submit.prevent="finalize"
                    >
                        <button
                            type="submit"
                            :disabled="finalizing"
                            class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                        >
                            {{ finalizing ? 'Finalizando…' : 'Finalizar Ocorrência' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <span :class="statusClass(ocorrencia?.status)" class="inline-flex rounded-full px-3 py-1 text-sm font-semibold">
                    {{ ocorrencia?.status ?? '—' }}
                </span>
            </div>

            <!-- Dados da Ocorrência -->
            <div class="bg-white rounded-lg shadow border border-gray-200 p-6 mb-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Dados Gerais</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Número BO</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ocorrencia?.numero_bos ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Data/Hora Fato</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ocorrencia?.data_hora_fato ?? '—' }}</dd>
                    </div>
                    <div v-if="ocorrencia?.descricao" class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ ocorrencia.descricao }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Relatos Polimórficos -->
            <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Relatos Vinculados</h2>
                <div v-if="!relatos.length" class="text-sm text-gray-400 py-4 text-center">
                    Nenhum relato vinculado a esta ocorrência.
                </div>
                <ul v-else class="divide-y divide-gray-100">
                    <li v-for="relato in relatos" :key="relato.id" class="py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900">{{ relato.conteudo_type ?? relato.type ?? 'Relato' }} #{{ relato.id }}</span>
                            <span class="text-xs text-gray-400">{{ formatDate(relato.created_at) }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    ocorrencia: { type: Object, default: () => ({}) },
});

const finalizing = ref(false);

const relatos = computed(() => props.ocorrencia?.relatos_morph ?? props.ocorrencia?.relatos ?? []);

function finalize() {
    finalizing.value = true;
    router.patch(route('compdec.rat.ocorrencias.finalize', props.ocorrencia?.id), {}, {
        preserveScroll: true,
        onFinish: () => { finalizing.value = false; },
    });
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
