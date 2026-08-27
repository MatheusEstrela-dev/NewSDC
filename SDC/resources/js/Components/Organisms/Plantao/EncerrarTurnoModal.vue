<script setup>
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import Modal from '@/Components/Modal.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  // turnoAtivo do PlantaoIndexController: { id, data, periodo,
  // plantonista_nome, plantonista_saida_nome, snapshot_sugerido }.
  turno: {
    type: Object,
    default: null,
  },
  // filterOptions.niveis, ja no formato {value, label} de toSelectArray() --
  // nao remapear.
  filterOptions: {
    type: Object,
    default: () => ({ niveis: [] }),
  },
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
  snapshots: [],
  ocorrencias_destaque: '',
});

const subtituloModal = computed(() => {
  if (!props.turno) return '';
  return `${props.turno.data} - ${props.turno.periodo}`;
});

// A guarda de dominio (PassagemInvalidaException) vira erro de formulario na
// chave "plantao" -- e aqui que o usuario ve a razao da rejeicao, e nao numa
// pagina de erro.
const erroDominio = computed(() => form.errors.plantao || '');

// Copia local do snapshot sugerido: o plantonista edita esta copia, nunca a
// prop `turno`. Recriada a cada abertura do modal para refletir o estado
// corrente das viaturas.
watch(
  () => props.show,
  (visivel) => {
    if (!visivel) return;
    form.clearErrors();
    form.ocorrencias_destaque = '';
    form.snapshots = (props.turno?.snapshot_sugerido ?? []).map((linha) => ({ ...linha }));
  },
);

function handleClose() {
  if (form.processing) return;
  emit('close');
}

function handleSubmit() {
  form
    .transform((data) => ({
      ocorrencias_destaque: data.ocorrencias_destaque || null,
      snapshots: data.snapshots.map((linha) => ({
        viatura_id: linha.viatura_id,
        hodometro: linha.hodometro === '' ? null : Number(linha.hodometro),
        nivel_combustivel: linha.nivel_combustivel || null,
        alteracoes: linha.alteracoes || null,
        anotacao: linha.anotacao || null,
        em_condicoes: !!linha.em_condicoes,
      })),
    }))
    .post(route('plantao.passagem.encerrar', props.turno.id), {
      preserveScroll: true,
      onSuccess: () => emit('saved'),
    });
}
</script>

<template>
  <Modal :show="show" max-width="4xl" @close="handleClose">
    <form @submit.prevent="handleSubmit">
      <header class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
          <ClipboardDocumentListIcon class-name="h-5 w-5" />
        </div>

        <div class="min-w-0 flex-1">
          <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Encerrar turno</h2>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ subtituloModal }}</p>
        </div>

        <button
          type="button"
          class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
          aria-label="Fechar"
          @click="handleClose"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </header>

      <div class="max-h-[70vh] space-y-5 overflow-y-auto px-5 py-4">
        <div
          v-if="erroDominio"
          class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300"
        >
          {{ erroDominio }}
        </div>

        <p class="text-sm text-slate-600 dark:text-slate-400">
          Confira ou corrija o estado de cada viatura antes de encerrar. Quem assumir o proximo turno
          vai conferir estes dados na tela de aceite.
        </p>

        <div class="space-y-3">
          <div
            v-for="(linha, index) in form.snapshots"
            :key="linha.viatura_id"
            class="space-y-3 rounded-lg border border-slate-200 p-4 dark:border-slate-700/50"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span class="font-semibold text-slate-900 dark:text-slate-100">
                {{ linha.prefixo }} - {{ linha.placa }}
              </span>
              <span v-if="linha.ultimo_condutor_nome" class="text-xs text-slate-500 dark:text-slate-400">
                Ultimo condutor: {{ linha.ultimo_condutor_nome }}
              </span>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <FormField
                v-model="linha.hodometro"
                type="number"
                label="Hodometro (km)"
                required
                inputmode="numeric"
                :error="form.errors[`snapshots.${index}.hodometro`]"
              />

              <FormSelect
                v-model="linha.nivel_combustivel"
                label="Nivel de combustivel"
                required
                :options="filterOptions.niveis"
                :error="form.errors[`snapshots.${index}.nivel_combustivel`]"
              />
            </div>

            <FormTextarea
              v-model="linha.alteracoes"
              label="Alteracoes / avarias"
              :rows="2"
              :error="form.errors[`snapshots.${index}.alteracoes`]"
            />

            <FormField
              v-model="linha.anotacao"
              label="Anotacao"
              hint="Curta, aparece em destaque no relatorio (ex.: Exclusiva Sobreaviso)"
              :error="form.errors[`snapshots.${index}.anotacao`]"
            />

            <ToggleField
              v-model="linha.em_condicoes"
              label="Em condicoes de uso"
              description="Desligue se a viatura, apesar de disponivel no cadastro, nao estiver em condicoes na conferencia fisica"
            />
          </div>
        </div>

        <FormTextarea
          v-model="form.ocorrencias_destaque"
          label="Ocorrencias em destaque"
          hint="Aparece no topo do relatorio de passagem, antes das viaturas"
          :rows="3"
          :error="form.errors.ocorrencias_destaque"
        />
      </div>

      <footer class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800/40">
        <FormActions
          submit-label="Encerrar turno"
          :loading="form.processing"
          @cancel="handleClose"
          @submit="handleSubmit"
        />
      </footer>
    </form>
  </Modal>
</template>
