<template>
  <Head :title="`Evacuação - ${protocolo.num_protocolo}`" />

  <PaeTelaLayout
    v-model:aba="aba"
    titulo="Conferência de evacuação"
    subtitulo="Resolução GMG nº 83/2024 · Anexo E"
    :icone="UserGroupIcon"
    :protocolo="protocolo"
    :status-label="seloRotulo"
    :status-variant="seloVariante"
    :abas="abas"
  >
    <template #avisos>
      <PaeAviso v-if="historica" tom="aviso">
        Consultando a versão {{ conferencia.versao }}.
        <Link :href="route('pae.protocolo.evacuacao.show', protocolo.id)" class="font-semibold underline">Ir para a versão atual ({{ versao_atual }})</Link>
      </PaeAviso>
      <PaeAviso v-if="protocolo.arquivado" tom="aviso">Protocolo arquivado: conferência somente para consulta.</PaeAviso>
    </template>

    <template #topo>
      <EvacuacaoResultadoPainel :resultado="resultado" :simulado="simulado" />
    </template>

    <template #default="{ aba: ativa }">
      <EvacuacaoSetoresEditor v-if="ativa === 'setores'" :itens="form.setores" :erros="erros" :resultado="resultado" :simulado="simulado" :somente-leitura="!can_edit" @adicionar="adicionar('setores')" @remover="remover('setores', $event)" />
      <EvacuacaoRotasEditor v-else-if="ativa === 'rotas'" :itens="form.rotas" :erros="erros" :resultado="resultado" :simulado="simulado" :setores-disponiveis="idsSetores" :somente-leitura="!can_edit" @adicionar="adicionar('rotas')" @remover="remover('rotas', $event)" />
      <EvacuacaoAcessosEditor v-else-if="ativa === 'acessos'" :itens="form.acessos" :erros="erros" :resultado="resultado" :simulado="simulado" :rotas-disponiveis="idsRotas" :somente-leitura="!can_edit" @adicionar="adicionar('acessos')" @remover="remover('acessos', $event)" />
      <EvacuacaoPontosEditor v-else-if="ativa === 'pontos'" :itens="form.pontos_encontro" :erros="erros" :resultado="resultado" :simulado="simulado" :somente-leitura="!can_edit" @adicionar="adicionar('pontos_encontro')" @remover="remover('pontos_encontro', $event)" />

      <CollapsibleSection v-else namespace="pae" section-id="evacuacao-historico" title="Histórico de conferências" :subtitle="`${historico.length} versão(ões)`" :icon="ClockIcon" tom="neutro">
        <p v-if="!historico.length" class="text-sm text-slate-500 dark:text-slate-400">Nenhuma conferência registrada.</p>
        <ol v-else class="space-y-2 text-sm">
          <li v-for="item in historico" :key="item.versao" class="flex flex-col gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
            <span class="min-w-0 break-words text-slate-700 dark:text-slate-200">Versão {{ item.versao }} · {{ item.autor || '—' }} · {{ formatarData(item.created_at) }} · SEI {{ item.num_sei }} · TTE {{ item.tte_fmt ?? '—' }}</span>
            <span class="flex items-center gap-3">
              <Badge :variant="item.conforme ? 'success' : 'danger'" size="sm">{{ item.conforme ? 'Conforme' : 'Não conforme' }}</Badge>
              <Link :href="route('pae.protocolo.evacuacao.versao', [protocolo.id, item.versao])" class="font-semibold text-blue-700 underline dark:text-blue-300">Abrir</Link>
            </span>
          </li>
        </ol>
      </CollapsibleSection>
    </template>

    <template #rodape>
      <CollapsibleSection v-if="conferencia && !can_edit" namespace="pae" section-id="evacuacao-registro-lido" :title="`Registro da versão ${conferencia.versao}`" :icon="DocumentTextIcon" tom="neutro">
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
          <div><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Número SEI</dt><dd class="break-words text-slate-800 dark:text-slate-100">{{ conferencia.num_sei || '—' }}</dd></div>
          <div><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Autor</dt><dd class="break-words text-slate-800 dark:text-slate-100">{{ conferencia.autor || '—' }}</dd></div>
          <div><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Data</dt><dd class="break-words text-slate-800 dark:text-slate-100">{{ formatarData(conferencia.created_at) }}</dd></div>
          <div class="sm:col-span-2 lg:col-span-3"><dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Observação</dt><dd class="whitespace-pre-line break-words text-slate-800 dark:text-slate-100">{{ conferencia.observacao || '—' }}</dd></div>
        </dl>
      </CollapsibleSection>

      <CollapsibleSection namespace="pae" section-id="evacuacao-registro" title="Tempo declarado e registro" subtitle="Informe o SEI e registre depois de simular." :icon="PencilSquareIcon" tom="success">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <FormField v-model="form.tte_declarado" label="Tempo total declarado pelo empreendedor (mm:ss)" placeholder="15:00" maxlength="6" :disabled="!can_edit" :error="erros.tte_declarado" />
          <template v-if="can_edit">
            <FormField v-model="form.num_sei" label="Número SEI" maxlength="100" :error="erros.num_sei" />
            <FormTextarea v-model="form.observacao" label="Observação" :rows="1" :error="erros.observacao" />
          </template>
        </div>
        <p v-if="erros.chave_idempotencia || erros.protocolo" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ erros.chave_idempotencia || erros.protocolo }}</p>
        <p v-if="erroSimulacao" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ erroSimulacao }}</p>
        <div v-if="can_edit" class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
          <Button class="w-full sm:w-auto" variant="outline" :loading="simulando" @click="simular">{{ simulando ? 'Simulando...' : 'Simular' }}</Button>
          <Button class="w-full sm:w-auto" :disabled="!simulado || form.processing || !form.num_sei" @click="registrar">Registrar conferência</Button>
        </div>
      </CollapsibleSection>
    </template>
  </PaeTelaLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import EvacuacaoAcessosEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoAcessosEditor.vue';
import EvacuacaoPontosEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoPontosEditor.vue';
import EvacuacaoResultadoPainel from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoResultadoPainel.vue';
import EvacuacaoRotasEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoRotasEditor.vue';
import EvacuacaoSetoresEditor from '@/Components/Organisms/Pae/Evacuacao/EvacuacaoSetoresEditor.vue';
import { usePaeEvacuacaoForm } from '@/Composables/pae/usePaeEvacuacaoForm';
import PaeTelaLayout from '@/Templates/Pae/PaeTelaLayout.vue';
import { formatarData } from '@/utils/paeTela';
import { ArrowsPointingInIcon, ClockIcon, DocumentTextIcon, MapIcon, MapPinIcon, PencilSquareIcon, Squares2X2Icon, UserGroupIcon } from '@heroicons/vue/24/outline';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  protocolo: { type: Object, required: true },
  conferencia: { type: Object, default: null },
  historico: { type: Array, default: () => [] },
  historica: { type: Boolean, default: false },
  versao_atual: { type: Number, default: 0 },
  can_edit: { type: Boolean, default: false },
});

const { form, resultado, simulado, simulando, erros, erroSimulacao, simular, registrar, adicionar, remover } = usePaeEvacuacaoForm(props.protocolo.id, props.conferencia);

const aba = ref('setores');
const abas = computed(() => [
  { id: 'setores', label: 'Setores', icon: Squares2X2Icon, badge: form.setores.length || null },
  { id: 'rotas', label: 'Rotas', icon: MapIcon, badge: form.rotas.length || null },
  { id: 'acessos', label: 'Acessos', icon: ArrowsPointingInIcon, badge: form.acessos.length || null },
  { id: 'pontos', label: 'Pontos de encontro', icon: MapPinIcon, badge: form.pontos_encontro.length || null },
  { id: 'historico', label: 'Histórico', icon: ClockIcon, badge: props.historico.length || null },
]);

const seloRotulo = computed(() => (props.conferencia ? (props.conferencia.conforme ? 'Conforme' : 'Não conforme') : 'Não conferida'));
const seloVariante = computed(() => (props.conferencia ? (props.conferencia.conforme ? 'success' : 'danger') : 'default'));

const idsSetores = computed(() => form.setores.map((s) => s.id).filter(Boolean));
const idsRotas = computed(() => form.rotas.map((r) => r.id).filter(Boolean));
</script>
