<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <!-- Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700 dark:from-primary-700 dark:to-primary-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <PencilSquareIcon class="w-5 h-5 text-white" />
                    </div>
                    <h3 class="text-lg font-semibold text-white">Escolha o tipo de edicao</h3>
                </div>
                <button
                    @click="emit('close')"
                    class="text-white/80 hover:text-white transition-colors p-1 hover:bg-white/10 rounded"
                >
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                Selecione qual parte do processo deseja editar:
            </p>

            <!-- Opcao: Editar Processo -->
            <button
                @click="handleEditProcesso"
                class="w-full p-4 flex items-start gap-4 rounded-lg border-2 border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group text-left"
            >
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors">
                    <DocumentTextIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                        Editar Processo
                    </h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Dados gerais, datas, decretos, vigencia e reconhecimento
                    </p>
                </div>
                <ChevronRightIcon class="w-5 h-5 text-slate-400 group-hover:text-blue-500 mt-1" />
            </button>

            <!-- Opcao: Editar Dados do Desastre -->
            <button
                @click="handleEditDesastres"
                class="w-full p-4 flex items-start gap-4 rounded-lg border-2 border-slate-200 dark:border-slate-700 hover:border-amber-500 dark:hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors group text-left"
            >
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg group-hover:bg-amber-200 dark:group-hover:bg-amber-900/50 transition-colors">
                    <ExclamationTriangleIcon class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-slate-800 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400">
                        Editar Dados do Desastre
                    </h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Danos humanos, materiais, prejuizos e totais por municipio
                    </p>
                </div>
                <ChevronRightIcon class="w-5 h-5 text-slate-400 group-hover:text-amber-500 mt-1" />
            </button>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 flex justify-end">
            <button
                @click="emit('close')"
                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
            >
                Cancelar
            </button>
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal.vue';
import {
    PencilSquareIcon,
    XMarkIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    processoId: {
        type: [Number, String],
        required: true,
    },
});

const emit = defineEmits(['close']);

const handleEditProcesso = () => {
    emit('close');
    router.visit(`/decretacoes/${props.processoId}/edit`);
};

const handleEditDesastres = () => {
    emit('close');
    router.visit(`/decretacoes/${props.processoId}/desastres/edit`);
};
</script>
