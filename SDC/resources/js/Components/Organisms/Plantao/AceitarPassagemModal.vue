<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ViaturaSnapshotCard from '@/Components/Molecules/Plantao/ViaturaSnapshotCard.vue';
import Modal from '@/Components/Modal.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  // turnoPendente do PlantaoIndexController: { id, data, periodo,
  // plantonista_nome, encerrado_em, encerrado_por_terceiro,
  // encerrado_por_nome, snapshots (SnapshotDTO) }.
  turno: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
  acao: 'aceitar',
  divergencia: '',
});

// Controla a revelacao do textarea obrigatorio de divergencia. Enquanto
// falso, a tela mostra os dois botoes de decisao (Aceitar / Apontar
// divergencia); ao apontar, o textarea aparece e os botoes viram
// Cancelar / Confirmar divergencia.
const mostrarDivergencia = ref(false);

const subtituloModal = computed(() => {
  if (!props.turno) return '';
  return `${props.turno.data} - ${props.turno.periodo} - ${props.turno.plantonista_nome}`;
});

// A guarda de dominio (PassagemInvalidaException) vira erro de formulario na
// chave "plantao" -- por exemplo, tentar aceitar a propria passagem.
const erroDominio = computed(() => form.errors.plantao || '');

watch(
  () => props.show,
  (visivel) => {
    if (!visivel) return;
    form.clearErrors();
    form.acao = 'aceitar';
    form.divergencia = '';
    mostrarDivergencia.value = false;
  },
);

function handleClose() {
  if (form.processing) return;
  emit('close');
}

function submeter() {
  form.post(route('plantao.passagem.aceitar', props.turno.id), {
    preserveScroll: true,
    onSuccess: () => emit('saved'),
  });
}

function aceitar() {
  form.acao = 'aceitar';
  form.divergencia = '';
  submeter();
}

function abrirDivergencia() {
  form.clearErrors();
  mostrarDivergencia.value = true;
}

function cancelarDivergencia() {
  form.clearErrors('divergencia');
  form.divergencia = '';
  mostrarDivergencia.value = false;
}

function confirmarDivergencia() {
  form.acao = 'divergencia';
  submeter();
}
</script>

<template>
  <Modal :show="show" max-width="2xl" @close="handleClose">
    <div>
      <header class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
          <CheckBadgeIcon class-name="h-5 w-5" />
        </div>

        <div class="min-w-0 flex-1">
          <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Conferir passagem de servico</h2>
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

      <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-4">
        <div
          v-if="erroDominio"
          class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300"
        >
          {{ erroDominio }}
        </div>

        <div
          v-if="turno?.encerrado_por_terceiro"
          class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300"
        >
          Encerrado por {{ turno.encerrado_por_nome }} em nome de {{ turno.plantonista_nome }}.
        </div>

        <div class="space-y-3">
          <ViaturaSnapshotCard
            v-for="snapshot in turno?.snapshots ?? []"
            :key="snapshot.id"
            :snapshot="snapshot"
          />
        </div>

        <FormTextarea
          v-if="mostrarDivergencia"
          v-model="form.divergencia"
          label="Descreva a divergencia"
          required
          hint="Obrigatorio: o proximo turno precisa saber o que nao conferiu"
          :rows="3"
          :error="form.errors.divergencia"
        />
      </div>

      <footer class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800/40">
        <FormActions v-if="!mostrarDivergencia" align="right">
          <Button variant="secondary" :disabled="form.processing" @click="handleClose">
            Fechar
          </Button>
          <Button variant="warning" :disabled="form.processing" @click="abrirDivergencia">
            Apontar divergencia
          </Button>
          <Button variant="primary" :loading="form.processing" @click="aceitar">
            Aceitar
          </Button>
        </FormActions>

        <FormActions v-else align="right">
          <Button variant="secondary" :disabled="form.processing" @click="cancelarDivergencia">
            Cancelar
          </Button>
          <Button
            variant="danger"
            :loading="form.processing"
            :disabled="!form.divergencia"
            @click="confirmarDivergencia"
          >
            Confirmar divergencia
          </Button>
        </FormActions>
      </footer>
    </div>
  </Modal>
</template>
