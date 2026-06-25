<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="$emit('close')">
    <div class="w-full max-w-md space-y-4 rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
      <header class="flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Novo PMDA</h2>
        <button type="button" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="$emit('close')">✕</button>
      </header>
      <p class="text-xs text-slate-500 dark:text-slate-400">Selecione o município para iniciar o preenchimento do PMDA.</p>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Município</span>
        <SelectInput v-model="municipioId" :options="municipioOptions" placeholder="Selecione…" />
      </label>

      <div class="flex justify-end gap-2">
        <Button variant="secondary" size="sm" type="button" @click="$emit('close')">Cancelar</Button>
        <Button variant="success" size="sm" :disabled="!municipioId" @click="continuar">Criar</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  municipios: { type: Array, default: () => [] },
});

defineEmits(['close']);

const municipioId = ref('');

const municipioOptions = computed(() =>
  props.municipios.map((m) => ({ value: m.id, label: `${m.nome} / ${m.uf}` }))
);

watch(() => props.show, (v) => { if (v) municipioId.value = ''; });

function continuar() {
  if (!municipioId.value) return;
  // Redireciona para o endpoint do metodo de criacao (form completo).
  router.visit(route('pmda.planos.create', { municipio_id: municipioId.value }));
}
</script>
