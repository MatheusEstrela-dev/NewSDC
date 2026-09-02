<script setup>
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import Modal from '@/Components/Modal.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  // 'saida' abre a viatura para transito; 'retorno' encerra a movimentacao aberta.
  modo: {
    type: String,
    default: 'saida',
    validator: (value) => ['saida', 'retorno'].includes(value),
  },
  viatura: {
    type: Object,
    default: null,
  },
  // Ja mapeado para {value, label} no backend -- SelectInput nao entende {id, name} cru.
  condutores: {
    type: Array,
    default: () => [],
  },
  // Turno ATIVO corrente, do ViaturaIndexController. Viaja no payload da saida
  // para que plantao_viatura_movimentacoes.plantao_id nao nasca NULL - e a
  // amarracao "esta saida ocorreu no turno de quem". Null e aceito pelo
  // MovimentacaoSaidaRequest: registrar saida nao exige turno aberto.
  plantaoAtivoId: {
    type: Number,
    default: null,
  },
  // Rotulo do turno ativo, so para exibir. O automatismo precisa ser visivel:
  // amarrar a saida a um plantao sem dizer qual e pedir confianca cega.
  plantaoAtivoRotulo: {
    type: String,
    default: null,
  },
  // {value, label} do usuario da sessao. Pre-seleciona o condutor: em quase
  // toda saida quem registra e quem dirige, e obrigar a procurar o proprio nome
  // numa lista de todos os usuarios do sistema e trabalho a toa.
  usuarioAtual: {
    type: Object,
    default: null,
  },
  // filterOptions.niveis ja vem no formato {value, label} do toSelectArray(): nao remapear.
  filterOptions: {
    type: Object,
    default: () => ({ niveis: [] }),
  },
});

const emit = defineEmits(['close', 'saved']);

const VAZIO = {
  condutor_id: '',
  saida_hodometro: '',
  saida_combustivel: '',
  destino: '',
  motivo: '',
  retorno_hodometro: '',
  retorno_combustivel: '',
  alteracoes: '',
};

const form = useForm({ ...VAZIO });

const isSaida = computed(() => props.modo === 'saida');

const tituloModal = computed(() => (isSaida.value ? 'Registrar saida' : 'Registrar retorno'));

const subtituloModal = computed(() => {
  if (!props.viatura) return '';
  return `${props.viatura.prefixo} - ${props.viatura.placa}`;
});

// A guarda de dominio (MovimentacaoInvalidaException) vira erro de formulario
// nas chaves "viatura" (saida) e "movimentacao" (retorno); e aqui que o usuario
// ve a razao da rejeicao, e nao numa pagina de erro.
const erroDominio = computed(() => form.errors.viatura || form.errors.movimentacao || '');

watch(
  () => props.show,
  (visivel) => {
    if (!visivel) return;
    form.clearErrors();
    form.reset();

    // Depois do reset, senao o valor padrao seria apagado junto.
    if (props.usuarioAtual?.value) {
      form.condutor_id = props.usuarioAtual.value;
    }
  },
);

function handleClose() {
  if (form.processing) return;
  emit('close');
}

function handleSubmit() {
  if (isSaida.value) {
    form
      .transform((data) => ({
        condutor_id: data.condutor_id || null,
        saida_hodometro: data.saida_hodometro === '' ? null : Number(data.saida_hodometro),
        saida_combustivel: data.saida_combustivel || null,
        destino: data.destino || null,
        motivo: data.motivo || null,
        plantao_id: props.plantaoAtivoId ?? null,
      }))
      .post(route('plantao.viaturas.saida', props.viatura.id), {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
      });

    return;
  }

  form
    .transform((data) => ({
      retorno_hodometro: data.retorno_hodometro === '' ? null : Number(data.retorno_hodometro),
      retorno_combustivel: data.retorno_combustivel || null,
      alteracoes: data.alteracoes || null,
    }))
    .post(route('plantao.movimentacoes.retorno', props.viatura.movimentacao_aberta_id), {
      preserveScroll: true,
      onSuccess: () => emit('saved'),
    });
}
</script>

<template>
  <Modal :show="show" max-width="lg" @close="handleClose">
    <form @submit.prevent="handleSubmit">
      <header class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
          <ArrowsRightLeftIcon class="h-5 w-5" />
        </div>

        <div class="min-w-0 flex-1">
          <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ tituloModal }}</h2>
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

        <template v-if="isSaida">
          <!--
            Turno e condutor sao preenchidos pelo sistema. O bloco existe para
            TORNAR ISSO VISIVEL -- o usuario precisa enxergar a que plantao a
            saida esta sendo amarrada, e que o condutor ja veio como ele.
          -->
          <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-800/40">
            <p class="text-slate-600 dark:text-slate-300">
              <span class="font-semibold">Turno:</span>
              {{ plantaoAtivoRotulo ?? 'nenhum turno aberto — a saida sera registrada sem vinculo' }}
            </p>
            <p v-if="usuarioAtual" class="mt-0.5 text-slate-500 dark:text-slate-400">
              Condutor preenchido com voce ({{ usuarioAtual.label }}). Troque abaixo se quem sai for outra pessoa.
            </p>
          </div>

          <FormSelect
            v-model="form.condutor_id"
            label="Condutor"
            required
            :options="condutores"
            :error="form.errors.condutor_id"
          />

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
              v-model="form.saida_hodometro"
              type="number"
              label="Hodometro de saida (km)"
              required
              inputmode="numeric"
              :error="form.errors.saida_hodometro"
            />

            <FormSelect
              v-model="form.saida_combustivel"
              label="Nivel de combustivel"
              required
              :options="filterOptions.niveis"
              :error="form.errors.saida_combustivel"
            />
          </div>

          <FormField
            v-model="form.destino"
            label="Destino"
            :error="form.errors.destino"
          />

          <FormField
            v-model="form.motivo"
            label="Motivo"
            :error="form.errors.motivo"
          />
        </template>

        <template v-else>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
              v-model="form.retorno_hodometro"
              type="number"
              label="Hodometro de retorno (km)"
              required
              inputmode="numeric"
              :error="form.errors.retorno_hodometro"
            />

            <FormSelect
              v-model="form.retorno_combustivel"
              label="Nivel de combustivel"
              required
              :options="filterOptions.niveis"
              :error="form.errors.retorno_combustivel"
            />
          </div>

          <FormTextarea
            v-model="form.alteracoes"
            label="Alteracoes / avarias"
            :rows="3"
            :error="form.errors.alteracoes"
          />
        </template>
      </div>

      <footer class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800/40">
        <FormActions
          submit-label="Confirmar"
          :loading="form.processing"
          @cancel="handleClose"
          @submit="handleSubmit"
        />
      </footer>
    </form>
  </Modal>
</template>
