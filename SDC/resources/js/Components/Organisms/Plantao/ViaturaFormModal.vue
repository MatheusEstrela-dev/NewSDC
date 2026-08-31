<script setup>
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import Modal from '@/Components/Modal.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  viatura: {
    type: Object,
    default: null,
  },
  // Ja no formato {value, label} de toSelectArray() -- nao remapear.
  filterOptions: {
    type: Object,
    default: () => ({ status: [], localizacoes: [], niveis: [] }),
  },
});

const emit = defineEmits(['close', 'saved']);

const VAZIO = {
  prefixo: '',
  placa: '',
  marca: '',
  modelo: '',
  localizacao: '',
  status: 'DISPONIVEL',
  nivel_combustivel: '',
  hodometro_atual: '',
  exclusiva_sobreaviso: false,
  observacoes: '',
  ativo: true,
};

const form = reactive({ ...VAZIO });
const errors = reactive({});
const processando = ref(false);

const isEditing = computed(() => !!props.viatura?.id);

const tituloModal = computed(() => (isEditing.value ? 'Editar viatura' : 'Nova viatura'));

function limparErros() {
  Object.keys(errors).forEach((key) => delete errors[key]);
}

watch(
  () => props.show,
  (visivel) => {
    if (!visivel) return;
    limparErros();

    if (props.viatura) {
      Object.assign(form, {
        prefixo: props.viatura.prefixo ?? '',
        placa: props.viatura.placa ?? '',
        marca: props.viatura.marca ?? '',
        modelo: props.viatura.modelo ?? '',
        localizacao: props.viatura.localizacao_valor ?? '',
        status: props.viatura.status_valor ?? 'DISPONIVEL',
        // hodometro_atual e nivel_combustivel ficam fora da edicao: pertencem
        // ao MovimentacaoViaturaService (spec 3.1). Reenvia-los a partir da
        // lista ja renderizada revertia o valor que outra pessoa gravou num
        // retorno. Para corrigi-los, registra-se uma movimentacao.
        nivel_combustivel: '',
        hodometro_atual: '',
        exclusiva_sobreaviso: props.viatura.exclusiva_sobreaviso ?? false,
        observacoes: props.viatura.observacoes ?? '',
        ativo: props.viatura.ativo ?? true,
      });
    } else {
      Object.assign(form, VAZIO);
    }
  },
);

function handleClose() {
  if (processando.value) return;
  emit('close');
}

function handleSubmit() {
  processando.value = true;
  limparErros();

  const payload = { ...form };

  if (isEditing.value) {
    // O UpdateViaturaRequest nao aceita esses campos; enviar seria ruido.
    delete payload.hodometro_atual;
    delete payload.nivel_combustivel;
  } else {
    payload.hodometro_atual = form.hodometro_atual === '' ? null : Number(form.hodometro_atual);
    payload.nivel_combustivel = form.nivel_combustivel === '' ? null : form.nivel_combustivel;
  }

  const onSuccess = () => {
    processando.value = false;
    emit('saved');
  };
  const onError = (e) => {
    Object.assign(errors, e);
    processando.value = false;
  };

  if (isEditing.value) {
    router.put(route('plantao.viaturas.update', props.viatura.id), payload, {
      preserveScroll: true,
      onSuccess,
      onError,
    });
  } else {
    router.post(route('plantao.viaturas.store'), payload, {
      preserveScroll: true,
      onSuccess,
      onError,
    });
  }
}
</script>

<template>
  <Modal :show="show" max-width="2xl" @close="handleClose">
    <form @submit.prevent="handleSubmit">
      <header class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700/50">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
          <TruckIcon class="h-5 w-5" />
        </div>

        <div class="min-w-0 flex-1">
          <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ tituloModal }}</h2>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Cadastro e situacao da viatura na frota do plantao</p>
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
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField
            v-model="form.prefixo"
            label="Prefixo"
            required
            :error="errors.prefixo"
          />

          <FormField
            v-model="form.placa"
            label="Placa"
            required
            :error="errors.placa"
            hint="Convertida para maiusculas automaticamente"
          />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField
            v-model="form.modelo"
            label="Modelo"
            required
            :error="errors.modelo"
          />

          <FormField
            v-model="form.marca"
            label="Marca"
            :error="errors.marca"
          />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormSelect
            v-model="form.localizacao"
            label="Localizacao"
            required
            :options="filterOptions.localizacoes"
            :error="errors.localizacao"
          />

          <FormSelect
            v-model="form.status"
            label="Status"
            required
            :options="filterOptions.status"
            :error="errors.status"
          />
        </div>

        <!--
          Estado de partida: aparece somente no cadastro. Uma viatura nova nao
          tem movimentacao no ledger, entao o hodometro e o combustivel iniciais
          entram aqui. Na edicao os dois campos nao existem - quem os atualiza e
          o registro de saida/retorno (spec 3.1).
        -->
        <div v-if="!isEditing" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormSelect
            v-model="form.nivel_combustivel"
            label="Nivel de combustivel"
            :options="filterOptions.niveis"
            placeholder="Nao informado"
            :error="errors.nivel_combustivel"
          />

          <FormField
            v-model="form.hodometro_atual"
            type="number"
            label="Hodometro inicial (km)"
            inputmode="numeric"
            hint="Depois do cadastro, so muda por saida ou retorno"
            :error="errors.hodometro_atual"
          />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <ToggleField
            v-model="form.exclusiva_sobreaviso"
            label="Exclusiva de sobreaviso"
            description="Viatura reservada ao regime de sobreaviso"
          />

          <ToggleField
            v-model="form.ativo"
            label="Ativo"
            description="Viaturas inativas nao aparecem nas listas operacionais"
          />
        </div>

        <FormTextarea
          v-model="form.observacoes"
          label="Observacoes"
          :rows="3"
          :error="errors.observacoes"
        />
      </div>

      <footer class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800/40">
        <FormActions
          submit-label="Salvar"
          :loading="processando"
          @cancel="handleClose"
          @submit="handleSubmit"
        />
      </footer>
    </form>
  </Modal>
</template>
