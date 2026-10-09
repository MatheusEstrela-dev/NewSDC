<template>
  <Head :title="`DCO - ${protocolo.num_protocolo}`" />

  <PaeTelaLayout
    v-model:aba="aba"
    titulo="Declaração de Conformidade e Operacionalidade"
    :icone="ShieldCheckIcon"
    :protocolo="protocolo"
    :status-label="rotuloSituacao"
    :status-variant="varianteSituacao"
    :abas="abas"
  >
    <template #avisos>
      <PaeAviso tom="neutro">Competência {{ resumo.competencia_anual }} · prazo {{ formatarData(resumo.vencimento) }}</PaeAviso>
      <PaeAviso v-if="resumo.entrega_tardia" tom="aviso">A DCO desta competência foi apresentada após 30 de junho. O atraso permanece registrado no histórico.</PaeAviso>
      <PaeAviso v-if="resumo.alerta_legado" tom="aviso">Este CCPAE é anterior ao registro de aplicabilidade da DCO. A CEDEC deve avaliar o protocolo.</PaeAviso>
      <PaeAviso v-if="['atrasada', 'nao_conforme'].includes(resumo.situacao)" tom="erro">Pendência para análise da CEDEC. O sistema não altera automaticamente a vigência do CCPAE.</PaeAviso>
      <PaeAviso v-if="protocolo.arquivado" tom="aviso">Protocolo arquivado: histórico disponível somente para leitura.</PaeAviso>
    </template>

    <template #default="{ aba: ativa }">
      <div v-if="ativa === 'aplicabilidade'" class="space-y-4">
        <CollapsibleSection namespace="pae" section-id="dco-vigente" title="Decisão vigente" subtitle="A avaliação mais recente da CEDEC prevalece." :icon="ShieldCheckIcon">
          <div v-if="resumo.avaliacao" class="rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-800">
            <p class="font-semibold text-slate-900 dark:text-white">{{ resumo.avaliacao.resultado === 'aplicavel' ? 'DCO aplicável' : 'DCO não aplicável' }}</p>
            <p class="mt-1 text-slate-700 dark:text-slate-200">{{ resumo.avaliacao.fundamentacao }}</p>
            <p class="mt-1 text-slate-500 dark:text-slate-400">SEI {{ resumo.avaliacao.num_sei }} · {{ resumo.avaliacao.decisor?.name || 'Responsável não disponível' }} · {{ formatarData(resumo.avaliacao.decidido_em) }}</p>
          </div>
          <PaeAviso v-else tom="aviso">Aplicabilidade ainda não avaliada. A emissão de CCPAE ficará bloqueada.</PaeAviso>
        </CollapsibleSection>

        <CollapsibleSection v-if="can_validar" namespace="pae" section-id="dco-avaliar" title="Registrar nova avaliação" subtitle="Cada avaliação é um registro novo; o histórico é preservado." :icon="PencilSquareIcon" tom="success">
          <form class="space-y-3" @submit.prevent="avaliar">
            <FormSelect v-model="avaliacao.resultado" label="Resultado" :options="OPCOES_RESULTADO_AVALIACAO" placeholder="" :error="avaliacao.errors.resultado" required />
            <FormTextarea v-model="avaliacao.fundamentacao" label="Fundamentação" :rows="3" :error="avaliacao.errors.fundamentacao" required />
            <FormField v-model="avaliacao.num_sei" label="Número SEI" maxlength="100" :error="avaliacao.errors.num_sei" required />
            <p v-if="errosSemCampo(avaliacao.errors, CAMPOS_AVALIACAO)" class="text-sm text-red-600 dark:text-red-300">{{ errosSemCampo(avaliacao.errors, CAMPOS_AVALIACAO) }}</p>
            <Button type="submit" :loading="avaliacao.processing">Registrar avaliação</Button>
          </form>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="dco-historico-avaliacoes" title="Histórico de avaliações" :subtitle="`${resumo.avaliacoes.length} registro(s)`" :icon="ClockIcon" tom="neutro">
          <p v-if="!resumo.avaliacoes.length" class="text-sm text-slate-500">Nenhuma avaliação registrada.</p>
          <ol v-else class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li v-for="item in resumo.avaliacoes" :key="item.id" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
              <strong>{{ item.resultado === 'aplicavel' ? 'Aplicável' : 'Não aplicável' }}</strong> · SEI {{ item.num_sei }} · {{ formatarData(item.decidido_em) }}
              <p class="mt-1">{{ item.fundamentacao }}</p>
            </li>
          </ol>
        </CollapsibleSection>
      </div>

      <div v-else class="space-y-4">
        <CollapsibleSection v-if="can_validar && resumo.avaliacao?.resultado === 'aplicavel'" namespace="pae" section-id="dco-registrar" title="Registrar declaração" subtitle="Cada apresentação gera uma versão; a mais recente da competência prevalece na emissão." :icon="DocumentArrowUpIcon" tom="success">
          <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="registrarDocumento">
            <FormField v-model="documento.competencia" type="number" label="Competência" step="1" :error="documento.errors.competencia" required />
            <FormSelect v-model="documento.resultado" label="Resultado conferido" :options="OPCOES_RESULTADO_DOCUMENTO" placeholder="" :error="documento.errors.resultado" required />
            <FormDateField v-model="documento.dt_documento" label="Data da DCO" :error="documento.errors.dt_documento" required />
            <FormDateField v-model="documento.dt_apresentacao" label="Apresentada à CEDEC em" :error="documento.errors.dt_apresentacao" required />
            <FormField v-model="documento.num_sei" label="Número SEI" maxlength="100" :error="documento.errors.num_sei" required />
            <FormFileField v-model="documento.arquivo" label="Arquivo PDF (até 20 MiB)" :error="documento.errors.arquivo" required />
            <FormTextarea v-model="documento.observacao" class="sm:col-span-2" label="Observação" :rows="2" :error="documento.errors.observacao" />
            <p v-if="errosSemCampo(documento.errors, CAMPOS_DOCUMENTO)" class="text-sm text-red-600 dark:text-red-300 sm:col-span-2">{{ errosSemCampo(documento.errors, CAMPOS_DOCUMENTO) }}</p>
            <div class="sm:col-span-2"><Button type="submit" :loading="documento.processing">Registrar DCO</Button></div>
          </form>
        </CollapsibleSection>

        <CollapsibleSection namespace="pae" section-id="dco-declaracoes" title="Declarações apresentadas" :subtitle="`${resumo.documentos.length} versão(ões)`" :icon="DocumentTextIcon" tom="neutro">
          <p v-if="!resumo.documentos.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhuma DCO apresentada.</p>
          <ul v-else class="space-y-3">
            <li v-for="item in resumo.documentos" :key="item.id" class="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <strong class="text-slate-900 dark:text-white">{{ item.competencia }} · versão {{ item.versao }} · {{ item.resultado === 'positiva' ? 'Positiva' : 'Não conforme' }}</strong>
                <a v-if="can_view" :href="route('pae.protocolo.dco.documentos.download', [protocolo.id, item.id])" class="font-semibold text-blue-700 underline dark:text-blue-300">Baixar PDF</a>
              </div>
              <p class="mt-1 text-slate-600 dark:text-slate-300">DCO de {{ formatarData(item.dt_documento) }} · apresentada {{ formatarData(item.dt_apresentacao) }} · SEI {{ item.num_sei }}</p>
              <p v-if="item.entrega_tardia" class="mt-1 text-amber-700 dark:text-amber-300">Apresentação após o prazo anual.</p>
              <p v-if="item.observacao" class="mt-1 text-slate-600 dark:text-slate-300">{{ item.observacao }}</p>
            </li>
          </ul>
        </CollapsibleSection>
      </div>
    </template>

    <template v-if="resumo.ccpae" #rodape>
      <CollapsibleSection namespace="pae" section-id="dco-ccpae" title="Evidência usada no CCPAE" :icon="ShieldCheckIcon" tom="neutro">
        <p class="text-sm text-slate-700 dark:text-slate-300">{{ resumo.ccpae.codigo }} · avaliação #{{ resumo.ccpae.dco_avaliacao_id || 'legada, sem referência' }} · DCO #{{ resumo.ccpae.dco_documento_id || 'não exigida ou legada' }}</p>
      </CollapsibleSection>
    </template>
  </PaeTelaLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormDateField from '@/Components/Molecules/Form/FormDateField.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormFileField from '@/Components/Molecules/Form/FormFileField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import PaeTelaLayout from '@/Templates/Pae/PaeTelaLayout.vue';
import { errosSemCampo, formatarData } from '@/utils/paeTela';
import { ClockIcon, DocumentArrowUpIcon, DocumentTextIcon, PencilSquareIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  resumo: { type: Object, required: true },
  can_validar: { type: Boolean, default: false },
  can_view: { type: Boolean, default: false },
});

const CAMPOS_AVALIACAO = ['resultado', 'fundamentacao', 'num_sei'];
const CAMPOS_DOCUMENTO = ['competencia', 'resultado', 'dt_documento', 'dt_apresentacao', 'num_sei', 'arquivo', 'observacao'];
const OPCOES_RESULTADO_AVALIACAO = [{ value: 'aplicavel', label: 'Aplicável' }, { value: 'nao_aplicavel', label: 'Não aplicável' }];
const OPCOES_RESULTADO_DOCUMENTO = [{ value: 'positiva', label: 'Positiva' }, { value: 'nao_conforme', label: 'Não conforme' }];

const aba = ref('aplicabilidade');
const abas = [
  { id: 'aplicabilidade', label: 'Aplicabilidade', icon: ShieldCheckIcon },
  { id: 'declaracoes', label: 'Declarações', icon: DocumentTextIcon, badge: props.resumo.documentos.length || null },
];

const avaliacao = useForm({ resultado: 'aplicavel', fundamentacao: '', num_sei: '', chave_idempotencia: crypto.randomUUID() });
const documento = useForm({ competencia: props.resumo.competencia_anual, resultado: 'positiva', dt_documento: '', dt_apresentacao: '', num_sei: '', observacao: '', arquivo: null, chave_idempotencia: crypto.randomUUID() });

const ROTULOS = { nao_avaliada: 'Não avaliada', nao_aplicavel: 'Não aplicável', comprovada: 'Comprovada', aguardando_prazo: 'Aguardando prazo', pendente_emissao: 'Pendente para emissão', atrasada: 'Atrasada', nao_conforme: 'Não conforme' };
const VARIANTES = { comprovada: 'success', nao_aplicavel: 'neutral', aguardando_prazo: 'warning', pendente_emissao: 'warning', atrasada: 'danger', nao_conforme: 'danger' };
const rotuloSituacao = computed(() => ROTULOS[props.resumo.situacao] || props.resumo.situacao);
const varianteSituacao = computed(() => VARIANTES[props.resumo.situacao] || 'default');

function avaliar() {
  avaliacao.post(route('pae.protocolo.dco.avaliar', props.protocolo.id), {
    preserveScroll: true,
    onSuccess: () => { avaliacao.reset('fundamentacao', 'num_sei'); avaliacao.chave_idempotencia = crypto.randomUUID(); },
  });
}

function registrarDocumento() {
  documento.post(route('pae.protocolo.dco.documentos.store', props.protocolo.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { documento.reset('dt_documento', 'dt_apresentacao', 'num_sei', 'observacao', 'arquivo'); documento.chave_idempotencia = crypto.randomUUID(); },
  });
}
</script>
