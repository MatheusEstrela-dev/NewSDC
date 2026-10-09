<template>
  <Modal :show="show" max-width="md" centralizado @close="fechar">
    <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-white text-left shadow-2xl dark:border-slate-800 dark:bg-slate-900">
      <div class="border-b border-emerald-800/30 bg-gradient-to-r from-emerald-900 to-emerald-700 px-6 py-5">
        <h3 class="text-lg font-bold text-white">
          {{ protocolo?.arquivado ? 'Reativar e emitir CCPAE' : 'Emitir CCPAE' }}
        </h3>
        <p class="text-sm text-emerald-100/80">Protocolo #{{ protocolo?.protocoloNumero || 'N/D' }}</p>
      </div>

      <div class="space-y-4 px-6 py-6">
        <p v-if="protocolo?.arquivado" class="text-sm text-amber-700 dark:text-amber-300">
          Este protocolo esta arquivado e sera desarquivado automaticamente.
        </p>

        <FormField v-model="form.codigo" label="Codigo do CCPAE" required :error="form.errors.codigo" />
        <FormDateField v-model="form.dt_emissao" label="Data de emissao" required :error="form.errors.dt_emissao" />
        <ToggleField
          v-model="form.empreendimento_novo"
          label="Empreendimento novo"
          description="A vigencia conta da Licenca de Operacao (Art. 4). Sem LO anterior ao PAE, conta da emissao (Art. 5)."
        />
        <FormDateField
          v-if="form.empreendimento_novo"
          v-model="form.dt_licenca_operacao"
          label="Data da Licenca de Operacao"
          required
          :error="form.errors.dt_licenca_operacao"
        />

        <p class="text-sm text-slate-600 dark:text-slate-300">
          Vigente ate: <span class="font-semibold">{{ vencimentoPrevisto || '—' }}</span>
        </p>
        <p class="text-sm text-slate-700 dark:text-slate-200">DCO: {{ rotuloSituacaoDco(protocolo?.dcoSituacao) }}.</p>
        <a v-if="protocolo" :href="route('pae.protocolo.dco.show', protocolo.id)" class="inline-block text-sm font-semibold text-blue-700 underline dark:text-blue-300">Conferir avaliação e declarações</a>
        <p class="text-sm text-slate-700 dark:text-slate-200">Evacuação: {{ rotuloSituacaoEvacuacao(protocolo?.evacuacaoSituacao) }} (informativo, não bloqueia).</p>
        <p class="text-sm text-slate-700 dark:text-slate-200">Simulado: {{ rotuloSituacaoSimulado(protocolo?.simuladoSituacao) }}.</p>
        <a v-if="protocolo" :href="route('pae.protocolo.simulados.show', protocolo.id)" class="inline-block text-sm font-semibold text-blue-700 underline dark:text-blue-300">Conferir exigibilidade e relatórios</a>
        <p v-if="protocolo && !simuladoPronto(protocolo.simuladoSituacao)" class="text-sm text-amber-700 dark:text-amber-300">Sem simulado dispensado ou relatório validado vigente hoje. A emissão será conferida no servidor pela data informada.</p>
        <p v-if="protocolo && !protocolo.dcoEmissaoPronta" class="text-sm text-amber-700 dark:text-amber-300">Na data de hoje não há DCO exigível comprovada. A emissão será conferida pela data informada.</p>
        <InputError v-if="erroGeral" :message="erroGeral" />
      </div>

      <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950">
        <Button variant="secondary" :disabled="form.processing" @click="fechar">Cancelar</Button>
        <Button variant="success" :disabled="form.processing || !form.codigo" @click="emitir">
          {{ form.processing ? 'Emitindo...' : 'Emitir CCPAE' }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import InputError from '@/Components/InputError.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import { hojeISO } from '@/Support/dataLocal';
import { rotuloSituacaoDco } from '@/utils/paeDco';
import { rotuloSituacaoEvacuacao } from '@/utils/paeEvacuacao';
import { rotuloSituacaoSimulado, simuladoPronto } from '@/utils/paeSimulado';

const props = defineProps({
  show: { type: Boolean, default: false },
  protocolo: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const form = useForm({
  codigo: '',
  dt_emissao: hojeISO(),
  empreendimento_novo: false,
  dt_licenca_operacao: '',
});

const erroGeral = computed(() => form.errors.status || form.errors.ccpae || form.errors.dco || form.errors.simulado);

function somarTresAnos(iso) {
  if (!iso) return null;
  const [ano, mes, dia] = iso.split('-').map(Number);
  const alvo = ano + 3;
  const ultimoDia = new Date(Date.UTC(alvo, mes, 0)).getUTCDate();
  const diaFinal = Math.min(dia, ultimoDia);
  return `${String(diaFinal).padStart(2, '0')}/${String(mes).padStart(2, '0')}/${alvo}`;
}

const vencimentoPrevisto = computed(() => somarTresAnos(
  form.empreendimento_novo ? form.dt_licenca_operacao : form.dt_emissao,
));

watch(() => props.show, (aberto) => {
  if (aberto) {
    form.reset();
    form.clearErrors();
    form.dt_emissao = hojeISO();
  }
});

function fechar() {
  form.reset();
  form.clearErrors();
  emit('close');
}

function emitir() {
  if (!props.protocolo) return;
  form.post(route('pae.protocolo.ccpae.store', props.protocolo.id), {
    preserveScroll: true,
    onSuccess: () => emit('close'),
  });
}
</script>
