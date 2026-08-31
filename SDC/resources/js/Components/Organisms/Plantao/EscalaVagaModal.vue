<script setup>
/**
 * Preenche uma vaga da escala, ou troca o plantonista de uma ja existente.
 *
 * Na edicao so o plantonista e alteravel. Data e tipo de turno definem a
 * IDENTIDADE da vaga -- ha indice unico parcial (data, tipo_turno_id) sobre os
 * dois -- entao mudar qualquer um deles e, na pratica, apagar e recriar, que e
 * o que os botoes de remover e adicionar ja fazem.
 */
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import Modal from '@/Components/Modal.vue';
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  // Vaga existente (extendedProps do evento) ou null para criar.
  vaga: {
    type: Object,
    default: null,
  },
  // Data pre-selecionada quando o usuario clica num dia vazio.
  dataSugerida: {
    type: String,
    default: '',
  },
  escalaId: {
    type: Number,
    default: null,
  },
  // Ja em {value, label} -- nao remapear.
  tiposTurno: {
    type: Array,
    default: () => [],
  },
  plantonistas: {
    type: Array,
    default: () => [],
  },
  podeRemover: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const form = reactive({ data: '', tipo_turno_id: '', plantonista_id: '' });
const errors = reactive({});
const processando = ref(false);

const editando = computed(() => !!props.vaga?.itemId);

const titulo = computed(() => (editando.value ? 'Trocar plantonista' : 'Preencher vaga'));

function limparErros() {
  Object.keys(errors).forEach((chave) => delete errors[chave]);
}

watch(
  () => props.show,
  (visivel) => {
    if (!visivel) return;

    limparErros();
    processando.value = false;

    if (props.vaga?.itemId) {
      form.data = props.vaga.data ?? '';
      form.tipo_turno_id = props.vaga.tipoTurnoId ?? '';
      form.plantonista_id = props.vaga.plantonistaId ?? '';
      return;
    }

    form.data = props.dataSugerida ?? '';
    form.tipo_turno_id = '';
    form.plantonista_id = '';
  },
);

const fechar = () => {
  if (processando.value) return;
  emit('close');
};

const submeter = () => {
  limparErros();
  processando.value = true;

  const opcoes = {
    preserveScroll: true,
    onError: (erros) => Object.assign(errors, erros),
    onSuccess: () => emit('close'),
    onFinish: () => {
      processando.value = false;
    },
  };

  if (editando.value) {
    router.put(
      route('plantao.escala.itens.update', props.vaga.itemId),
      { plantonista_id: form.plantonista_id },
      opcoes,
    );
    return;
  }

  router.post(route('plantao.escala.itens.store', props.escalaId), { ...form }, opcoes);
};

const remover = () => {
  if (!props.vaga?.itemId) return;

  processando.value = true;
  router.delete(route('plantao.escala.itens.destroy', props.vaga.itemId), {
    preserveScroll: true,
    onSuccess: () => emit('close'),
    onFinish: () => {
      processando.value = false;
    },
  });
};
</script>

<template>
  <Modal :show="show" max-width="lg" @close="fechar">
    <form @submit.prevent="submeter">
      <header class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
          <CalendarIcon class="h-5 w-5" />
        </div>

        <div class="min-w-0 flex-1">
          <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ titulo }}</h2>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            {{ editando ? 'Data e turno nao mudam: remova a vaga e crie outra.' : 'Escolha o dia, o turno e quem assume.' }}
          </p>
        </div>

        <button
          type="button"
          class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
          aria-label="Fechar"
          @click="fechar"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </header>

      <div class="space-y-4 px-5 py-4">
        <p v-if="errors.vaga" class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">
          {{ errors.vaga }}
        </p>

        <FormDateField
          v-model="form.data"
          label="Data"
          required
          :disabled="editando"
          :error="errors.data"
        />

        <FormSelect
          v-model="form.tipo_turno_id"
          label="Turno"
          required
          :disabled="editando"
          :options="tiposTurno"
          :error="errors.tipo_turno_id"
          placeholder="Selecione o turno..."
        />

        <FormSelect
          v-model="form.plantonista_id"
          label="Plantonista"
          required
          :options="plantonistas"
          :error="errors.plantonista_id"
          placeholder="Selecione o plantonista..."
          hint="Somente quem esta cadastrado e ativo aparece aqui."
        />
      </div>

      <footer class="flex flex-col-reverse gap-2 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700/50">
        <button
          v-if="editando && podeRemover"
          type="button"
          class="rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-50 dark:text-red-400 dark:hover:bg-red-500/10"
          :disabled="processando"
          @click="remover"
        >
          Remover vaga
        </button>
        <span v-else class="hidden sm:block"></span>

        <!--
          @submit explicito: o FormActions auto-fechado desenha o proprio botao
          e EMITE o evento em vez de submeter o form nativo. Sem este listener o
          botao "Preencher" nao faz nada -- ha um teste do projeto que varre os
          .vue justamente para pegar isso.
        -->
        <FormActions
          :loading="processando"
          :submit-label="editando ? 'Salvar' : 'Preencher'"
          cancel-label="Cancelar"
          @submit="submeter"
          @cancel="fechar"
        />
      </footer>
    </form>
  </Modal>
</template>
