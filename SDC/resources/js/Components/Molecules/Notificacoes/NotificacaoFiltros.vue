<template>
    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4">
        <div class="flex items-center gap-2 mb-4">
            <FunnelIcon class="w-4 h-4 text-slate-400" />
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Filtros</h2>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Módulo</label>
                <select
                    :value="modelValue.modulo"
                    class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm"
                    @change="atualizar('modulo', $event.target.value || null)"
                >
                    <option value="">Todos</option>
                    <option v-for="m in modulos" :key="m.slug" :value="m.slug">{{ m.label }}</option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Severidade</label>
                <select
                    :value="modelValue.tipo"
                    class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm"
                    @change="atualizar('tipo', $event.target.value || null)"
                >
                    <option value="">Todas</option>
                    <option v-for="t in TIPOS" :key="t.valor" :value="t.valor">{{ t.label }}</option>
                </select>
            </div>

            <label class="flex items-center gap-2 cursor-pointer pb-2">
                <input
                    type="checkbox"
                    :checked="modelValue.apenas_nao_lidas"
                    class="rounded border-slate-300 dark:border-slate-600 dark:bg-slate-700 text-blue-600 focus:ring-blue-500/50"
                    @change="atualizar('apenas_nao_lidas', $event.target.checked)"
                >
                <span class="text-sm text-slate-600 dark:text-slate-300">Apenas não lidas</span>
            </label>

            <button
                v-if="temFiltro"
                class="pb-2 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
                @click="limpar"
            >
                Limpar filtros
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { FunnelIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: { type: Object, required: true },
    modulos: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const TIPOS = [
    { valor: 'urgent', label: 'Urgente' },
    { valor: 'error', label: 'Erro' },
    { valor: 'warning', label: 'Atenção' },
    { valor: 'success', label: 'Sucesso' },
    { valor: 'info', label: 'Informação' },
];

const temFiltro = computed(
    () => !!props.modelValue.modulo || !!props.modelValue.tipo || props.modelValue.apenas_nao_lidas
);

const atualizar = (campo, valor) => {
    emit('update:modelValue', { ...props.modelValue, [campo]: valor });
};

const limpar = () => {
    emit('update:modelValue', { modulo: null, tipo: null, apenas_nao_lidas: false });
};
</script>
