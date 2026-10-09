<template>
  <Head :title="`Simulados - ${protocolo.num_protocolo}`" />

  <PaeTelaLayout
    v-model:aba="aba"
    titulo="Simulados do Anexo C"
    subtitulo="Relatório anual do exercício simulado · Resolução GMG nº 83/2024"
    :icone="BellAlertIcon"
    :protocolo="protocolo"
    :status-label="seloRotulo"
    :status-variant="varianteSituacaoSimulado(resumo.situacao)"
    :abas="abas"
  >
    <template #avisos>
      <PaeAviso v-if="protocolo.arquivado" tom="aviso">Protocolo arquivado: histórico disponível somente para leitura.</PaeAviso>
      <PaeAviso v-if="selecionado && !selecionado.vigente" tom="neutro">Consultando a versão {{ selecionado.versao }} do simulado de {{ formatarData(selecionado.dt_realizacao) }}, somente leitura. Abra a versão vigente para revisar.</PaeAviso>
    </template>

    <template #topo>
      <SimuladoSituacaoPainel :resumo="resumo" />
      <SimuladoExigibilidadePainel :resumo="resumo" :protocolo-id="protocolo.id" :pode-validar="can_validar" />
    </template>

    <template #default="{ aba: ativa }">
      <PaeAviso v-if="!formularioDisponivel && ativa !== 'historico'" tom="aviso">O relatório só pode ser registrado depois de a CEDEC avaliar o simulado como exigível.</PaeAviso>
      <!-- As abas do formulario ficam montadas (v-show): trocar de aba nao pode perder o PDF escolhido. -->
      <div v-show="formularioDisponivel && ativa !== 'historico'">
        <div v-show="ativa === 'envio'">
          <SimuladoEnvioTab :form="form" :somente-leitura="somenteLeitura" :selecionado="selecionado" :protocolo-id="protocolo.id" :can-view="can_view" />
        </div>
        <div v-show="ativa === 'criterios'">
          <SimuladoCriteriosTab :criterios="form.criterios" :catalogo="resumo.catalogo" :indicios="indicios" :erros="form.errors" :somente-leitura="somenteLeitura" :atualizando="atualizandoIndicios" :erro-indicios="erroIndicios" @atualizar-indicios="atualizarIndicios" />
        </div>
        <div v-show="ativa === 'tempos'">
          <SimuladoTemposTab :tempos="form.tempos" :erros="form.errors" :somente-leitura="somenteLeitura" @adicionar="adicionarLinha" @remover="removerLinha" />
        </div>
        <div v-show="ativa === 'alarme'">
          <SimuladoAlarmeTab :alarme="form.alarme" :indicios="indicios" :erros="form.errors" :somente-leitura="somenteLeitura" />
        </div>
        <div v-show="ativa === 'informativos'">
          <SimuladoInformativosTab :informativos="form.informativos" :erros="form.errors" :somente-leitura="somenteLeitura" @adicionar-ano="adicionarAno" @remover-ano="removerAno" />
        </div>
      </div>
      <SimuladoHistoricoTab v-if="ativa === 'historico'" :relatorios="resumo.relatorios" :selecionado-id="selecionadoId" :protocolo-id="protocolo.id" :pode-validar="can_validar" :can-view="can_view" @abrir="abrir" @novo="novo" />
    </template>

    <template #rodape>
      <PaeAviso v-if="mensagemGeral" tom="erro">{{ mensagemGeral }}</PaeAviso>
      <div v-if="formularioDisponivel && !somenteLeitura" class="flex flex-wrap justify-end gap-3">
        <Button :loading="form.processing" :disabled="!form.arquivo || !form.num_sei" @click="enviar">Registrar relatório</Button>
      </div>
      <CollapsibleSection v-if="resumo.ccpae" namespace="pae" section-id="simulado-ccpae" title="Evidência usada no CCPAE" :icon="ShieldCheckIcon" tom="neutro">
        <p class="text-sm text-slate-700 dark:text-slate-300">{{ resumo.ccpae.codigo }} · avaliação #{{ resumo.ccpae.simulado_avaliacao_id || 'legada, sem referência' }} · relatório #{{ resumo.ccpae.simulado_relatorio_id || 'não exigido ou legado' }}</p>
      </CollapsibleSection>
    </template>
  </PaeTelaLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import SimuladoAlarmeTab from '@/Components/Organisms/Pae/Simulados/SimuladoAlarmeTab.vue';
import SimuladoCriteriosTab from '@/Components/Organisms/Pae/Simulados/SimuladoCriteriosTab.vue';
import SimuladoEnvioTab from '@/Components/Organisms/Pae/Simulados/SimuladoEnvioTab.vue';
import SimuladoExigibilidadePainel from '@/Components/Organisms/Pae/Simulados/SimuladoExigibilidadePainel.vue';
import SimuladoHistoricoTab from '@/Components/Organisms/Pae/Simulados/SimuladoHistoricoTab.vue';
import SimuladoInformativosTab from '@/Components/Organisms/Pae/Simulados/SimuladoInformativosTab.vue';
import SimuladoSituacaoPainel from '@/Components/Organisms/Pae/Simulados/SimuladoSituacaoPainel.vue';
import SimuladoTemposTab from '@/Components/Organisms/Pae/Simulados/SimuladoTemposTab.vue';
import { usePaeSimuladoForm } from '@/Composables/pae/usePaeSimuladoForm';
import PaeTelaLayout from '@/Templates/Pae/PaeTelaLayout.vue';
import { rotuloSituacaoSimulado, varianteSituacaoSimulado } from '@/utils/paeSimulado';
import { errosSemCampo, formatarData } from '@/utils/paeTela';
import { BellAlertIcon, ClipboardDocumentCheckIcon, ClockIcon, DocumentArrowUpIcon, InformationCircleIcon, ShieldCheckIcon, SpeakerWaveIcon } from '@heroicons/vue/24/outline';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  resumo: { type: Object, required: true },
  can_validar: { type: Boolean, default: false },
  can_view: { type: Boolean, default: false },
});

const vigente = () => props.resumo.relatorios.find((r) => r.vigente) ?? null;

const { form, indicios, atualizandoIndicios, erroIndicios, selecionadoId, carregar, atualizarIndicios, registrar, adicionarLinha, removerLinha, adicionarAno, removerAno } = usePaeSimuladoForm(props.protocolo.id, vigente());

// Raiz do campo no payload -> aba onde ele aparece. Chaves fora do mapa (chave_idempotencia, protocolo...) caem no aviso geral.
const ABA_DO_CAMPO = {
  dt_realizacao: 'envio', nivel_emergencia: 'envio', dt_apresentacao: 'envio', num_sei: 'envio', aviso_cedec_em: 'envio', arquivo: 'envio', integrado: 'envio', barragens_integradas: 'envio', observacao: 'envio',
  criterios: 'criterios', tempos: 'tempos', alarme: 'alarme', informativos: 'informativos',
};
const raizDoCampo = (campo) => campo.split('.')[0];

const aba = ref('envio');
const errosPorAba = computed(() => Object.keys(form.errors).reduce((contagem, campo) => {
  const destino = ABA_DO_CAMPO[raizDoCampo(campo)];
  return destino ? { ...contagem, [destino]: (contagem[destino] ?? 0) + 1 } : contagem;
}, {}));

const abas = computed(() => [
  { id: 'envio', label: 'Envio', icon: DocumentArrowUpIcon, badge: errosPorAba.value.envio || null },
  { id: 'criterios', label: 'Critérios', icon: ClipboardDocumentCheckIcon, badge: errosPorAba.value.criterios || null },
  { id: 'tempos', label: 'Tempos', icon: ClockIcon, badge: errosPorAba.value.tempos || null },
  { id: 'alarme', label: 'Alarme', icon: SpeakerWaveIcon, badge: errosPorAba.value.alarme || null },
  { id: 'informativos', label: 'Informativos', icon: InformationCircleIcon, badge: errosPorAba.value.informativos || null },
  { id: 'historico', label: 'Histórico', icon: ShieldCheckIcon, badge: props.resumo.relatorios.length || null },
]);

const selecionado = computed(() => props.resumo.relatorios.find((r) => r.id === selecionadoId.value) ?? null);
const formularioDisponivel = computed(() => props.resumo.avaliacao?.resultado === 'exigivel');
const somenteLeitura = computed(() => !props.can_validar || props.protocolo.arquivado || (selecionado.value !== null && !selecionado.value.vigente));
const seloRotulo = computed(() => {
  const rotulo = rotuloSituacaoSimulado(props.resumo.situacao);
  return rotulo.charAt(0).toUpperCase() + rotulo.slice(1);
});

const mensagemGeral = computed(() => {
  const comAba = Object.keys(form.errors).filter((campo) => ABA_DO_CAMPO[raizDoCampo(campo)]);
  const semCampo = errosSemCampo(form.errors, comAba).trim();
  const total = comAba.length;
  const rotulos = abas.value.filter((a) => errosPorAba.value[a.id]).map((a) => `${a.label} (${errosPorAba.value[a.id]})`).join(', ');
  return [semCampo, total ? `Há ${total} campo(s) a corrigir nas abas: ${rotulos}.` : ''].filter(Boolean).join(' ');
});

// Depois de um erro do servidor, leva o analista a primeira aba com campo a corrigir.
function irParaPrimeiraAbaComErro() {
  const primeira = abas.value.find((a) => errosPorAba.value[a.id]);
  if (primeira) aba.value = primeira.id;
}

const enviar = () => registrar(irParaPrimeiraAbaComErro);

function abrir(relatorio) {
  carregar(relatorio);
  aba.value = 'envio';
}

const novo = () => abrir(null);

// Depois de registrar, a versao nova passa a ser a vigente: o formulario recarrega dela (nova chave, PDF novo).
watch(() => props.resumo.relatorios[0]?.id, () => carregar(vigente()));
</script>
