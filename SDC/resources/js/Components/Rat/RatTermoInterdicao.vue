<template>
  <div class="rat-section-card mt-6">
    <button type="button" class="rat-section-header w-full text-left" @click="aberto = !aberto">
      <div class="rat-section-icon rat-section-icon-warning">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
      </div>
      <div class="flex-1">
        <h3 class="rat-section-title">Termo de Interdição</h3>
        <p class="text-xs opacity-75">{{ subtitulo }}</p>
      </div>
      <span v-if="termo" class="px-2 py-0.5 rounded text-xs font-semibold"
            :class="emitido ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
        {{ emitido ? 'Emitido' : 'Rascunho' }}
      </span>
      <span class="ml-2 text-sm">{{ aberto ? '▲' : '▼' }}</span>
    </button>

    <div v-show="aberto" class="rat-section-content">
      <div v-if="contexto.interdicao_marcada && !emitido"
           class="mb-4 p-3 rounded border border-yellow-400 bg-yellow-50 text-yellow-900 text-sm">
        A vistoria indica interdição: o RAT só pode ser finalizado depois que este termo for emitido.
      </div>
      <div v-if="sugerido" class="mb-4 p-3 rounded border border-blue-300 bg-blue-50 text-blue-900 text-sm">
        Termo sugerido a partir do RAT de origem. Revise, informe seu registro no CREA e emita.
      </div>
      <div v-if="!contexto.pode_editar && !emitido" class="mb-4 text-sm opacity-75">
        {{ contexto.exige_crea
          ? 'Somente agente com registro no CREA pode emitir o termo deste RAT relacionado.'
          : 'Você não tem permissão para registrar o termo de interdição.' }}
      </div>

      <fieldset :disabled="somenteLeitura" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-grid-2">
          <FormSelect label="Tipo de interdição" v-model="form.tipo" :options="tipoOptions" required />
          <FormField label="Prazo (dias)" type="number" v-model="form.prazo_dias" />
        </div>
        <FormField type="textarea" label="Área interditada" v-model="form.area_interditada" :rows="2" required
                   placeholder="Imóvel, cômodos ou trecho interditado" />
        <FormField type="textarea" label="Fundamentação técnica" v-model="form.fundamentacao" :rows="3" required />
        <FormField type="textarea" label="Medidas exigidas" v-model="form.medidas" :rows="2" />
        <div class="rat-grid-2">
          <FormField label="Responsável técnico" v-model="form.responsavel_nome" />
          <FormField v-if="contexto.exige_crea || form.registro_crea" label="Registro no CREA"
                     v-model="form.registro_crea" :required="contexto.exige_crea" />
        </div>
      </fieldset>

      <p v-if="emitido" class="mt-3 text-sm">
        Emitido por <strong>{{ termo.emitido_por || '—' }}</strong> em {{ dataEmissao }}.
      </p>

      <div v-if="!somenteLeitura" class="flex gap-3 justify-end mt-4">
        <button type="button" class="px-4 py-2 rounded border text-sm" :disabled="enviando" @click="salvar">
          Salvar rascunho
        </button>
        <button type="button" class="px-4 py-2 rounded bg-red-600 text-white text-sm" :disabled="enviando" @click="emitir">
          Emitir termo
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import { useToast } from '@/Composables/useToast';
import { computed, reactive, ref } from 'vue';

/**
 * Termo de interdição da vistoria (#57). O contexto vem do servidor
 * (RatTermoInterdicaoService::contexto): termo atual, sugestão do RAT de
 * origem, se o usuário pode editar e se o bloco abre expandido.
 */
const props = defineProps({
  ratId: { type: String, required: true },
  contexto: { type: Object, default: () => ({}) },
});

const { show: toast } = useToast();

const tipoOptions = [
  { value: 'parcial', label: 'Interdição parcial' },
  { value: 'total', label: 'Interdição total' },
];

const termo = ref(props.contexto.termo ?? null);
const sugerido = ref(!termo.value && !!props.contexto.sugestao);
const aberto = ref(!!props.contexto.abrir_por_padrao);
const enviando = ref(false);

const base = termo.value ?? props.contexto.sugestao ?? {};
const form = reactive({
  tipo: base.tipo ?? '',
  area_interditada: base.area_interditada ?? '',
  fundamentacao: base.fundamentacao ?? '',
  medidas: base.medidas ?? '',
  prazo_dias: base.prazo_dias ?? '',
  responsavel_nome: termo.value?.responsavel_nome ?? '',
  registro_crea: termo.value?.registro_crea ?? '',
});

const emitido = computed(() => termo.value?.status === 'emitido');
const somenteLeitura = computed(() => emitido.value || !props.contexto.pode_editar);
const subtitulo = computed(() => (props.contexto.relacionado
  ? 'RAT relacionado: emissão pelo agente com registro no CREA'
  : 'RAT original: registro pela COMPDEC'));
const dataEmissao = computed(() => (termo.value?.emitido_em
  ? new Date(termo.value.emitido_em).toLocaleString('pt-BR') : '—'));

const mensagemDeErro = (e) => e?.response?.data?.message
  || Object.values(e?.response?.data?.errors ?? {})[0]?.[0]
  || 'Não foi possível salvar o termo de interdição.';

async function salvar() {
  enviando.value = true;
  try {
    const axios = window.axios || (await import('axios')).default;
    const { data } = await axios.put(route('rat.termo-interdicao.salvar', props.ratId), {
      ...form,
      prazo_dias: form.prazo_dias === '' ? null : Number(form.prazo_dias),
    });
    termo.value = data.termo;
    sugerido.value = false;
    toast(data.message, 'success');
    return true;
  } catch (e) {
    toast(mensagemDeErro(e), 'error');
    return false;
  } finally {
    enviando.value = false;
  }
}

async function emitir() {
  if (!(await salvar())) return;
  if (!window.confirm('Emitir o termo de interdição? Depois de emitido ele não pode ser alterado.')) return;
  enviando.value = true;
  try {
    const axios = window.axios || (await import('axios')).default;
    const { data } = await axios.post(route('rat.termo-interdicao.emitir', props.ratId));
    termo.value = data.termo;
    toast(data.message, 'success');
  } catch (e) {
    toast(mensagemDeErro(e), 'error');
  } finally {
    enviando.value = false;
  }
}
</script>
