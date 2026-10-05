<template>
  <div class="space-y-4">
    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4">
        <dt class="text-xs modal-serie-apoio">Notificação da FEAM</dt>
        <dd class="mt-1 flex flex-wrap items-center gap-2 text-sm modal-serie-valor">
          {{ dataBR(prazos?.dt_notificacao_feam) || 'Não informada' }}
          <Badge v-if="prazos?.dt_notificacao_feam_estimada" variant="neutral" size="sm">Estimada</Badge>
        </dd>
      </div>
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4">
        <dt class="text-xs modal-serie-apoio">Limite para protocolar (Art. 7, 10 dias úteis)</dt>
        <dd class="mt-1 flex flex-wrap items-center gap-2 text-sm modal-serie-valor">
          {{ dataBR(prazos?.limite_protocolo) || '—' }}
          <Badge v-if="prazos?.fora_do_prazo" variant="danger" size="sm">Fora do prazo</Badge>
        </dd>
      </div>
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4">
        <dt class="text-xs modal-serie-apoio">Limite de análise (Art. 9, 300 dias)</dt>
        <dd class="mt-1 flex flex-wrap items-center gap-2 text-sm modal-serie-valor">
          {{ dataBR(prazos?.limite_analise) || '—' }}
          <PrazosPill :prazo="prazos?.situacao || 'ok'" />
        </dd>
      </div>
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4">
        <dt class="text-xs modal-serie-apoio">Dias pausados por diligência</dt>
        <dd class="mt-1 text-sm modal-serie-valor">{{ prazos?.dias_pausados ?? 0 }}</dd>
      </div>
      <div class="modal-serie-cartao min-w-0 rounded-xl p-4 sm:col-span-2">
        <dt class="text-xs modal-serie-apoio">CCPAE</dt>
        <dd class="mt-1 text-sm modal-serie-valor">
          <template v-if="prazos?.ccpae">
            {{ prazos.ccpae.codigo }}: emitido em {{ dataBR(prazos.ccpae.dt_emissao) }}, vigente até {{ dataBR(prazos.ccpae.dt_vencimento) }}
          </template>
          <template v-else>Não emitido</template>
        </dd>
      </div>
    </dl>

    <form v-if="canEdit" class="modal-serie-cartao flex flex-col gap-3 rounded-xl p-4 sm:flex-row sm:items-end" @submit.prevent="salvar">
      <div class="min-w-0 flex-1">
        <FormDateField
          v-model="form.dt_notificacao_feam"
          label="Data da notificação da FEAM ao empreendedor"
          required
          :error="form.errors.dt_notificacao_feam"
        />
      </div>
      <Button type="submit" variant="primary" :disabled="form.processing || !form.dt_notificacao_feam">
        {{ form.processing ? 'Salvando...' : 'Salvar e recalcular' }}
      </Button>
    </form>
  </div>
</template>

<script setup>
/**
 * Prazos legais do protocolo (Resolucao GMG 83/2024, Arts. 4, 5, 7 e 9).
 * Valores calculados no servidor (PaePrazoService::resumo); aqui so exibe e
 * permite informar a data real da notificacao da FEAM.
 */
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import PrazosPill from '@/Components/Molecules/Pae/Protocolos/PrazosPill.vue';

const props = defineProps({
  prazos: { type: Object, default: null },
  protocoloId: { type: Number, default: null },
  canEdit: { type: Boolean, default: false },
});

const emit = defineEmits(['atualizado']);

const form = useForm({ dt_notificacao_feam: '' });

watch(() => props.prazos, (p) => {
  form.dt_notificacao_feam = p?.dt_notificacao_feam_estimada ? '' : (p?.dt_notificacao_feam ?? '');
}, { immediate: true });

function dataBR(iso) {
  if (!iso) return '';
  const [a, m, d] = iso.split('-');
  return `${d}/${m}/${a}`;
}

function salvar() {
  form.put(route('pae.protocolo.notificacao-feam', props.protocoloId), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => emit('atualizado'),
  });
}
</script>
