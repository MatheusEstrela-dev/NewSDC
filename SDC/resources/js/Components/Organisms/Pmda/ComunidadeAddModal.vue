<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  planoId: { type: [Number, String], required: true },
  comunidades: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
  comunidade_id: '',
  nome: '',
  pop_atendida: '',
  distancia_km: '',
});

const options = computed(() => props.comunidades.map((c) => ({ value: c.id, label: c.nome })));

const selecionada = computed(() =>
  props.comunidades.find((c) => String(c.id) === String(form.comunidade_id)) ?? null
);

watch(() => props.show, (v) => { if (v) form.reset(); });

function fechar() {
  form.reset();
  form.clearErrors();
  emit('close');
}

function salvar() {
  if (!form.comunidade_id) return;
  // O backend deriva nome/coordenadas do registro mestre; enviamos o nome para
  // satisfazer a validacao e dar feedback imediato.
  form.nome = selecionada.value?.nome ?? '';
  form.post(route('pmda.planos.comunidades.store', props.planoId), {
    preserveScroll: true,
    onSuccess: () => fechar(),
  });
}
</script>

<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <div class="space-y-4 p-5">
      <header class="flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Adicionar Comunidade</h2>
        <button type="button" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="fechar">✕</button>
      </header>

      <p class="text-xs text-slate-500 dark:text-slate-400">
        Selecione uma comunidade já cadastrada para este município e informe os dados de atendimento do plano.
      </p>

      <div v-if="options.length === 0" class="rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
        Nenhuma comunidade cadastrada e disponível para este município. Use "Solicitar Cadastro de Comunidade".
      </div>

      <template v-else>
        <label class="block">
          <span class="pmda-field-label">Comunidade</span>
          <SelectInput v-model="form.comunidade_id" :options="options" placeholder="Selecione…" />
          <span v-if="form.errors.comunidade_id" class="mt-1 block text-xs text-red-600">{{ form.errors.comunidade_id }}</span>
        </label>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <label class="block">
            <span class="pmda-field-label">População (pessoas)</span>
            <TextInput v-model="form.pop_atendida" type="number" />
          </label>
          <label class="block">
            <span class="pmda-field-label">Distância (km)</span>
            <TextInput v-model="form.distancia_km" type="number" />
          </label>
        </div>
      </template>

      <div v-if="form.errors.comunidade_id || form.errors.nome" class="text-xs text-red-600">
        {{ form.errors.comunidade_id || form.errors.nome }}
      </div>

      <div class="flex justify-end gap-2 pt-1">
        <Button variant="secondary" size="sm" type="button" @click="fechar">Cancelar</Button>
        <Button
          variant="primary"
          size="sm"
          :disabled="!form.comunidade_id || form.processing"
          :loading="form.processing"
          @click="salvar"
        >
          Adicionar
        </Button>
      </div>
    </div>
  </Modal>
</template>
