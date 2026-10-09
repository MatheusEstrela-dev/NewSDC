<template>
  <CollapsibleSection namespace="pae" section-id="simulado-exigibilidade" title="Exigibilidade do simulado" subtitle="A avaliação mais recente da CEDEC prevalece (Arts. 17 e 21)." :icon="ShieldCheckIcon">
    <div v-if="resumo.avaliacao" class="rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-800">
      <p class="font-semibold text-slate-900 dark:text-white">
        {{ resumo.avaliacao.resultado === 'exigivel' ? 'Simulado exigível' : `Simulado dispensado: ${MOTIVOS_DISPENSA[resumo.avaliacao.motivo_dispensa]}` }}
      </p>
      <p class="mt-1 text-slate-700 dark:text-slate-200">{{ resumo.avaliacao.fundamentacao }}</p>
      <p class="mt-1 text-slate-500 dark:text-slate-400">SEI {{ resumo.avaliacao.num_sei }} · {{ resumo.avaliacao.decisor?.name || 'Responsável não disponível' }} · {{ formatarData(resumo.avaliacao.decidido_em) }}</p>
    </div>
    <PaeAviso v-else tom="aviso">Exigibilidade ainda não avaliada. A emissão de CCPAE ficará bloqueada.</PaeAviso>

    <form v-if="podeValidar" class="mt-4 space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700" @submit.prevent="avaliar">
      <h3 class="font-medium text-slate-900 dark:text-white">Registrar nova avaliação</h3>
      <FormSelect v-model="avaliacao.resultado" label="Resultado" :options="OPCOES_RESULTADO" placeholder="" :error="avaliacao.errors.resultado" required />
      <FormSelect v-if="avaliacao.resultado === 'dispensado'" v-model="avaliacao.motivo_dispensa" label="Motivo da dispensa" :options="OPCOES_MOTIVO" :error="avaliacao.errors.motivo_dispensa" required />
      <FormTextarea v-model="avaliacao.fundamentacao" label="Fundamentação" :rows="3" :error="avaliacao.errors.fundamentacao" required />
      <FormField v-model="avaliacao.num_sei" label="Número SEI" maxlength="100" :error="avaliacao.errors.num_sei" required />
      <p v-if="errosSemCampo(avaliacao.errors, CAMPOS)" class="text-sm text-red-600 dark:text-red-300">{{ errosSemCampo(avaliacao.errors, CAMPOS) }}</p>
      <Button type="submit" :loading="avaliacao.processing">Registrar avaliação</Button>
    </form>

    <h3 class="mt-6 font-medium text-slate-900 dark:text-white">Histórico de avaliações</h3>
    <p v-if="!resumo.avaliacoes.length" class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nenhuma avaliação registrada.</p>
    <ol v-else class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
      <li v-for="item in resumo.avaliacoes" :key="item.id" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
        <strong>{{ item.resultado === 'exigivel' ? 'Exigível' : `Dispensado (${MOTIVOS_DISPENSA[item.motivo_dispensa]})` }}</strong> · SEI {{ item.num_sei }} · {{ formatarData(item.decidido_em) }}
        <p class="mt-1">{{ item.fundamentacao }}</p>
      </li>
    </ol>
  </CollapsibleSection>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import { MOTIVOS_DISPENSA, OPCOES_MOTIVO } from '@/utils/paeSimulado';
import { errosSemCampo, formatarData, novoUuid } from '@/utils/paeTela';
import { ShieldCheckIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  resumo: { type: Object, required: true },
  protocoloId: { type: Number, required: true },
  podeValidar: { type: Boolean, default: false },
});

const CAMPOS = ['resultado', 'motivo_dispensa', 'fundamentacao', 'num_sei'];
const OPCOES_RESULTADO = [{ value: 'exigivel', label: 'Exigível' }, { value: 'dispensado', label: 'Dispensado' }];

const avaliacao = useForm({ resultado: 'exigivel', motivo_dispensa: '', fundamentacao: '', num_sei: '', chave_idempotencia: novoUuid() });

function avaliar() {
  avaliacao.transform((dados) => ({ ...dados, motivo_dispensa: dados.resultado === 'dispensado' ? dados.motivo_dispensa : null }))
    .post(route('pae.protocolo.simulados.avaliar', props.protocoloId), {
      preserveScroll: true,
      onSuccess: () => { avaliacao.reset('motivo_dispensa', 'fundamentacao', 'num_sei'); avaliacao.chave_idempotencia = novoUuid(); },
    });
}
</script>
