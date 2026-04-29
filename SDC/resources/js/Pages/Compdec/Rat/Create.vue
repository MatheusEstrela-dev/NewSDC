<template>
    <div>
        <Head title="RAT — Nova Ocorrência" />

        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-3xl mx-auto">
            <div class="mb-6">
                <Link :href="route('rat.index')" class="text-sm text-blue-600 hover:underline">&larr; Voltar para lista</Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Nova Ocorrência RAT</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6 bg-white p-6 rounded-lg shadow border border-gray-200">
                <!-- Número BO -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número do BO</label>
                    <input
                        v-model="form.numero_bos"
                        type="text"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                        placeholder="Ex.: 2025/001234"
                    />
                    <p v-if="formErrors.numero_bos" class="mt-1 text-sm text-red-600">{{ formErrors.numero_bos }}</p>
                </div>

                <!-- Prazo de Edição -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prazo de Edição</label>
                    <input
                        v-model="form.prazo_edicao"
                        type="datetime-local"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                        placeholder="Ex.: 2026-01-15T18:00"
                    />
                    <p v-if="formErrors.prazo_edicao" class="mt-1 text-sm text-red-600">{{ formErrors.prazo_edicao }}</p>
                </div>

                <!-- Histórico / Observações -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Histórico / Observações</label>
                    <textarea
                        v-model="form.historico"
                        rows="4"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"
                        placeholder="Registre observações iniciais sobre a ocorrência..."
                    ></textarea>
                    <p v-if="formErrors.historico" class="mt-1 text-sm text-red-600">{{ formErrors.historico }}</p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <Link
                        :href="route('rat.index')"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >Cancelar</Link>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ processing ? 'Salvando…' : 'Criar Ocorrência' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const inertiaForm = useForm({
    numero_bos:   '',
    prazo_edicao: '',
    historico:    '',
});

const form       = inertiaForm;
const processing = computed(() => inertiaForm.processing);
const formErrors = computed(() => inertiaForm.errors);

function submit() {
    inertiaForm.post(route('rat.store'), {
        preserveScroll: true,
    });
}
</script>
