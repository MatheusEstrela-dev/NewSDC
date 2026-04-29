<template>
    <div>
        <Head :title="`RAT — Editar Ocorrência #${ocorrencia?.id}`" />

        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-3xl mx-auto">
            <div class="mb-6">
                <Link :href="route('rat.show', ocorrencia?.id)" class="text-sm text-blue-600 hover:underline">&larr; Voltar</Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Editar Ocorrência #{{ ocorrencia?.id }}</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6 bg-white p-6 rounded-lg shadow border border-gray-200">
                <!-- Número BO -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número do BO</label>
                    <input
                        v-model="form.numero_bos"
                        type="text"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                    />
                    <p v-if="form.errors.numero_bos" class="mt-1 text-sm text-red-600">{{ form.errors.numero_bos }}</p>
                </div>

                <!-- Data/Hora do Fato -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data/Hora do Fato <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.data_hora_fato"
                        type="datetime-local"
                        required
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                    />
                    <p v-if="form.errors.data_hora_fato" class="mt-1 text-sm text-red-600">{{ form.errors.data_hora_fato }}</p>
                </div>

                <!-- Descrição -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea
                        v-model="form.descricao"
                        rows="4"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                    ></textarea>
                    <p v-if="form.errors.descricao" class="mt-1 text-sm text-red-600">{{ form.errors.descricao }}</p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <Link
                        :href="route('rat.show', ocorrencia?.id)"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >Cancelar</Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Salvando…' : 'Salvar Alterações' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    ocorrencia: { type: Object, default: () => ({}) },
});

const form = useForm({
    numero_bos:   props.ocorrencia?.numero_bos   ?? '',
    prazo_edicao: props.ocorrencia?.prazo_edicao  ?? '',
    historico:    props.ocorrencia?.historico     ?? '',
});

function submit() {
    form.put(route('rat.update', props.ocorrencia?.id), {
        preserveScroll: true,
    });
}
</script>
