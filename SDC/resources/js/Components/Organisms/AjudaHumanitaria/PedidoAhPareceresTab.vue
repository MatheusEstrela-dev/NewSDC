<template>
  <div class="space-y-6">
    <div v-if="canParecer">
      <Button variant="primary" size="md" @click="abrirModal">Emitir parecer</Button>
    </div>

    <ListEmptyState
      v-if="!pareceres.length"
      title="Nenhum parecer emitido"
      helper="O avanço para o Diretor exige ao menos um parecer favorável"
    />

    <ul v-else class="space-y-4">
      <li
        v-for="p in pareceres"
        :key="p.id"
        class="rounded-xl border border-slate-200 p-4 dark:border-slate-700"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2">
            <span
              :class="[
                'rounded-full px-2.5 py-1 text-xs font-medium',
                p.favoravel
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200'
                  : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
              ]"
            >
              {{ p.situacao_label }}
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ p.etapa_label }}</span>
          </div>

          <button
            v-if="canParecer"
            type="button"
            class="text-xs font-medium text-red-600 hover:underline dark:text-red-400"
            @click="$emit('remover-parecer', p.id)"
          >
            Remover
          </button>
        </div>

        <p class="mt-3 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">
          {{ p.parecer }}
        </p>

        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
          {{ formatarData(p.data_parecer) }}<span v-if="p.autor"> · {{ p.autor }}</span>
        </p>
      </li>
    </ul>

    <Modal :show="modalAberto" max-width="2xl" @close="fecharModal">
      <div class="p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Emitir parecer</h3>

        <div class="mt-4 space-y-4">
          <div class="grid gap-4 sm:grid-cols-3">
            <FormDateField v-model="formulario.data_parecer" label="Data" :required="true" />
            <FormSelect
              v-model="formulario.situacao"
              label="Situação"
              :options="situacoes"
              :required="true"
              placeholder="Selecione"
            />
            <FormSelect
              v-model="formulario.etapa"
              label="Etapa"
              :options="etapas"
              :required="true"
              placeholder="Selecione"
            />
          </div>

          <FormTextarea
            v-model="formulario.parecer"
            label="Parecer"
            :rows="6"
            :required="true"
            placeholder="Fundamentação técnica do pleito"
          />
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <Button variant="secondary" size="md" @click="fecharModal">Cancelar</Button>
          <Button variant="primary" size="md" :disabled="!podeSalvar" @click="salvar">
            Registrar
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Modal from '@/Components/Modal.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

defineProps({
  pareceres: { type: Array, default: () => [] },
  situacoes: { type: Array, default: () => [] },
  etapas: { type: Array, default: () => [] },
  canParecer: { type: Boolean, default: false },
});

const emit = defineEmits(['emitir-parecer', 'remover-parecer']);

const modalAberto = ref(false);

const formulario = reactive({
  data_parecer: new Date().toISOString().slice(0, 10),
  situacao: '',
  etapa: '',
  parecer: '',
});

const podeSalvar = computed(
  () => formulario.situacao !== '' && formulario.etapa !== '' && formulario.parecer.trim() !== '',
);

function abrirModal() {
  formulario.data_parecer = new Date().toISOString().slice(0, 10);
  formulario.situacao = '';
  formulario.etapa = '';
  formulario.parecer = '';
  modalAberto.value = true;
}

function fecharModal() {
  modalAberto.value = false;
}

function salvar() {
  emit('emitir-parecer', { ...formulario });
  fecharModal();
}

function formatarData(valor) {
  if (!valor) return '—';

  const [ano, mes, dia] = String(valor).split('-');

  return dia ? `${dia}/${mes}/${ano}` : valor;
}
</script>
