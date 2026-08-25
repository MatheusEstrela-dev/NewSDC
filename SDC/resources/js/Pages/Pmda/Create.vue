<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import RatTabs from '@/Components/Rat/RatTabs.vue';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import PmdaInicioSection from '@/Components/Organisms/Pmda/PmdaInicioSection.vue';
import PmdaIssSection from '@/Components/Organisms/Pmda/PmdaIssSection.vue';
import PmdaCompdecSection from '@/Components/Organisms/Pmda/PmdaCompdecSection.vue';
import PmdaPontoSection from '@/Components/Organisms/Pmda/PmdaPontoSection.vue';
import PmdaDistribuicaoSection from '@/Components/Organisms/Pmda/PmdaDistribuicaoSection.vue';
import PmdaAcoesSection from '@/Components/Organisms/Pmda/PmdaAcoesSection.vue';
import PmdaAnexosSection from '@/Components/Organisms/Pmda/PmdaAnexosSection.vue';
import InstrucoesPmdaModal from '@/Components/Organisms/InstrucoesPmdaModal.vue';
import { usePmdaWizard } from '@/Composables/pmda/usePmdaWizard.js';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  // Ao iniciar (1a renderizacao): so o municipio. Apos o 1o POST: o plano persistido.
  municipio: { type: Object, default: null },
  plano: { type: Object, default: null },
  plano_id: { type: [Number, String], default: null },
  pontos_disponiveis: { type: Array, default: () => [] },
  comunidades_disponiveis: { type: Array, default: () => [] },
  comunidade_solicitacoes: { type: Array, default: () => [] },
  compdec_ficha: { type: Object, default: () => ({}) },
  compdec_anexos: { type: Array, default: () => [] },
  compdec_equipe: { type: Array, default: () => [] },
  iss_fallback: { type: Object, default: null },
});

// O PmdaPlanoResource e serializado FLAT (sem envelope "data") e possui um campo
// proprio "data" (string ISO da data do plano). So desembrulha quando "data" for um
// objeto (envelope real); caso contrario usa o proprio plano. Sem isso, dados virava
// a string da data e protocolo/status sumiam ("a gerar").
const dados = computed(() => {
  const p = props.plano;
  if (!p) return null;
  return p.data && typeof p.data === 'object' ? p.data : p;
});
// "criado" = o plano ja foi persistido (1o POST feito); habilita as abas-filhas.
// Usa o escalar plano_id (sempre presente apos o store) como fonte confiavel.
const planoId = computed(() => props.plano_id ?? dados.value?.id ?? null);
const criado = computed(() => Boolean(planoId.value));

const municipioLabel = computed(() => {
  if (props.municipio) return `${props.municipio.nome} / ${props.municipio.uf}`;
  return dados.value?.municipio ?? '';
});

// Antes de criar: Inicio + ISS liberados (campos do plano, em memoria).
// Apos criar: tudo liberado; comeca no COMPDEC (Inicio+ISS ja preenchidos).
// allUnlocked reativo: ao persistir (criado true), libera todas as abas sem remount.
const { activeTab, tabs, goTo, next, prev } = usePmdaWizard({
  allUnlocked: criado,
  maxUnlocked: 2,
  initialTab: criado.value ? 3 : 1,
});

// Ao concluir o 1o POST (criado vira true), avanca para o COMPDEC (Inicio+ISS prontos).
watch(criado, (v, old) => {
  if (v && !old) goTo(3);
});

// Instrucoes so na abertura do Novo PMDA (antes de qualquer persistencia).
const showInstrucoes = ref(!criado.value && Boolean(props.municipio));

const tabsComBadge = computed(() =>
  tabs.value.map((t) => {
    if (!criado.value) return t;
    if (t.id === 3) return { ...t, badge: dados.value?.compdec_membros?.length || null };
    if (t.id === 4) return { ...t, badge: dados.value?.pontos?.length || null };
    if (t.id === 5) return { ...t, badge: dados.value?.comunidades?.length || null };
    return t;
  })
);

const form = useForm({
  municipio_id: props.municipio?.id ?? dados.value?.municipio_id,
  motivo: dados.value?.motivo ?? '',
  // ISS / Prefeitura (fallback: ultimo PMDA do municipio quando criando novo)
  cobra_iss: dados.value?.cobra_iss ?? props.iss_fallback?.cobra_iss ?? false,
  num_lei_iss: dados.value?.num_lei_iss ?? props.iss_fallback?.num_lei_iss ?? '',
  aliquota_iss: dados.value?.aliquota_iss ?? props.iss_fallback?.aliquota_iss ?? '',
  resp_cob_iss: dados.value?.resp_cob_iss ?? props.iss_fallback?.resp_cob_iss ?? '',
  nome_prefeito: dados.value?.nome_prefeito ?? props.iss_fallback?.nome_prefeito ?? '',
  tel_prefeitura: dados.value?.tel_prefeitura ?? props.iss_fallback?.tel_prefeitura ?? '',
  email_prefeitura: dados.value?.email_prefeitura ?? props.iss_fallback?.email_prefeitura ?? '',
  tel_prefeito: dados.value?.tel_prefeito ?? props.iss_fallback?.tel_prefeito ?? '',
  cel_prefeito: dados.value?.cel_prefeito ?? props.iss_fallback?.cel_prefeito ?? '',
  cep: dados.value?.cep ?? props.iss_fallback?.cep ?? '',
  endereco: dados.value?.endereco ?? props.iss_fallback?.endereco ?? '',
  bairro: dados.value?.bairro ?? props.iss_fallback?.bairro ?? '',
  populacao: dados.value?.populacao ?? props.iss_fallback?.populacao ?? '',
  pop_rural: dados.value?.pop_rural ?? props.iss_fallback?.pop_rural ?? '',
  area: dados.value?.area ?? props.iss_fallback?.area ?? '',
  // COMPDEC
  compdec_coordenador: dados.value?.compdec_coordenador ?? '',
  compdec_email: dados.value?.compdec_email ?? '',
  compdec_tel: dados.value?.compdec_tel ?? '',
  compdec_decreto: dados.value?.compdec_decreto ?? '',
  compdec_lei: dados.value?.compdec_lei ?? '',
  // Acoes de resposta
  acao_decreto_se: dados.value?.acao_decreto_se ?? false,
  acao_caminhao_pipa: dados.value?.acao_caminhao_pipa ?? false,
  acao_cestas_basicas: dados.value?.acao_cestas_basicas ?? false,
  justificativa_apoio: dados.value?.justificativa_apoio ?? '',
});

// Persiste os campos do plano. Antes da criacao -> 1 POST (store) que segue para
// /continuar (mesmo contexto Create). Depois -> PUT (update) e avanca a aba.
function salvarEAvancar() {
  if (!criado.value) {
    form.post(route('pmda.planos.store'));
    return;
  }
  form.put(route('pmda.planos.update', planoId.value), {
    preserveScroll: true,
    onSuccess: () => next(),
  });
}

// Etapa 1 (Inicio): antes de criar so navega (sem tocar o banco); depois salva.
function avancarInicio() {
  if (!criado.value) { next(); return; }
  salvarEAvancar();
}

function voltar() {
  router.visit(route('pmda.planos.index'));
}
</script>

<template>
  <Head title="Novo PMDA" />

  <InstrucoesPmdaModal v-if="municipio" :show="showInstrucoes" :municipio="municipio" @close="showInstrucoes = false" />

  <div class="pb-6">
    <PageHeader
      :title="`Novo PMDA — ${municipioLabel}`"
      :icon="DocumentTextIcon"
      :icon-image="moduleIcon('pmda')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex flex-wrap items-center justify-end gap-2">
          <span class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/20 dark:text-blue-300">
            <DocumentTextIcon class="h-4 w-4" />
            Protocolo Nº <span class="ml-1 font-mono">{{ dados?.protocolo ?? '— a gerar' }}</span>
          </span>
          <span class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
            Município: <span class="ml-1">{{ municipioLabel }}</span>
          </span>
          <PmdaStatusBadge v-if="criado" :label="dados.status_label" :cor="dados.status_cor" />
        </div>
      </template>
    </PageHeader>

    <div v-if="Object.keys(form.errors).length" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
      <p class="font-semibold">Não foi possível salvar. Corrija:</p>
      <ul class="ml-5 mt-1 list-disc">
        <li v-for="(msg, key) in form.errors" :key="key">{{ msg }}</li>
      </ul>
    </div>

    <RatTabs :tabs="tabsComBadge" :active-tab="activeTab" @tab-change="goTo">
      <PmdaInicioSection
        v-if="activeTab === 1"
        :form="form"
        :protocolo="dados?.protocolo"
        :next-label="criado ? 'Salvar e Avançar' : 'Avançar'"
        :saving="form.processing"
        @next="avancarInicio"
        @prev="prev"
      />
      <PmdaIssSection
        v-else-if="activeTab === 2"
        :form="form"
        :saving="form.processing"
        @next="salvarEAvancar"
        @prev="prev"
      />
      <PmdaCompdecSection
        v-else-if="activeTab === 3 && criado"
        :form="form"
        :plano="dados"
        :ficha="compdec_ficha"
        :anexos="compdec_anexos"
        :equipe="compdec_equipe"
        :saving="form.processing"
        @next="salvarEAvancar"
        @prev="prev"
      />
      <PmdaPontoSection
        v-else-if="activeTab === 4 && criado"
        :plano="dados"
        @next="next"
        @prev="prev"
      />
      <PmdaDistribuicaoSection
        v-else-if="activeTab === 5 && criado"
        :plano="dados"
        :comunidades-disponiveis="comunidades_disponiveis"
        :solicitacoes="comunidade_solicitacoes"
        @next="next"
        @prev="prev"
      />
      <PmdaAcoesSection
        v-else-if="activeTab === 6 && criado"
        :form="form"
        :saving="form.processing"
        @next="salvarEAvancar"
        @prev="prev"
      />
      <PmdaAnexosSection
        v-else-if="activeTab === 7 && criado"
        :plano="dados"
        @prev="prev"
        @revisar="goTo(1)"
      />
    </RatTabs>
  </div>
</template>
