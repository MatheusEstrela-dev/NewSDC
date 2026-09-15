<script setup>
import { computed } from 'vue';
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
import { usePmdaWizard } from '@/Composables/pmda/usePmdaWizard.js';

defineOptions({ layout: AuthenticatedLayout });
const props = defineProps({
  plano: { type: Object, required: true },
  pontos_disponiveis: { type: Array, default: () => [] },
  comunidades_disponiveis: { type: Array, default: () => [] },
  comunidade_solicitacoes: { type: Array, default: () => [] },
  compdec_ficha: { type: Object, default: () => ({}) },
  compdec_anexos: { type: Array, default: () => [] },
  compdec_equipe: { type: Array, default: () => [] },
});

// Resource FLAT com campo proprio "data" (string ISO): so desembrulha quando "data"
// for objeto (envelope real). Ver Pmda/Create.vue.
const dados = computed(() => {
  const p = props.plano;
  if (!p) return null;
  return p.data && typeof p.data === 'object' ? p.data : p;
});

// Edit: edicao de um PMDA existente — todas as etapas liberadas.
// (A CRIACAO roda inteira no contexto Create/continuar, nunca aqui.)
const { activeTab, tabs, goTo, next, prev } = usePmdaWizard({
  allUnlocked: true,
  initialTab: 1,
});

const devolucaoEm = computed(() => {
  const iso = dados.value?.devolucao_em;
  return iso ? new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : null;
});

const tabsComBadge = computed(() =>
  tabs.value.map((t) => {
    if (t.id === 3) return { ...t, badge: props.compdec_equipe?.length || null };
    if (t.id === 4) return { ...t, badge: dados.value.pontos?.length || null };
    if (t.id === 5) return { ...t, badge: dados.value.comunidades?.length || null };
    return t;
  })
);

const form = useForm({
  motivo: dados.value.motivo ?? '',
  // ISS / Prefeitura
  cobra_iss: dados.value.cobra_iss ?? false,
  num_lei_iss: dados.value.num_lei_iss ?? '',
  aliquota_iss: dados.value.aliquota_iss ?? '',
  resp_cob_iss: dados.value.resp_cob_iss ?? '',
  nome_prefeito: dados.value.nome_prefeito ?? '',
  tel_prefeitura: dados.value.tel_prefeitura ?? '',
  email_prefeitura: dados.value.email_prefeitura ?? '',
  tel_prefeito: dados.value.tel_prefeito ?? '',
  cel_prefeito: dados.value.cel_prefeito ?? '',
  cep: dados.value.cep ?? '',
  endereco: dados.value.endereco ?? '',
  bairro: dados.value.bairro ?? '',
  populacao: dados.value.populacao ?? '',
  pop_rural: dados.value.pop_rural ?? '',
  area: dados.value.area ?? '',
  // COMPDEC
  compdec_coordenador: dados.value.compdec_coordenador ?? '',
  compdec_email: dados.value.compdec_email ?? '',
  compdec_tel: dados.value.compdec_tel ?? '',
  compdec_decreto: dados.value.compdec_decreto ?? '',
  compdec_lei: dados.value.compdec_lei ?? '',
  // Acoes de resposta
  acao_decreto_se: dados.value.acao_decreto_se ?? false,
  acao_caminhao_pipa: dados.value.acao_caminhao_pipa ?? false,
  acao_cestas_basicas: dados.value.acao_cestas_basicas ?? false,
  justificativa_apoio: dados.value.justificativa_apoio ?? '',
});

// Persiste os campos de nivel do plano e avanca para a proxima etapa.
function salvarEAvancar() {
  form.put(route('pmda.planos.update', dados.value.id), {
    preserveScroll: true,
    onSuccess: () => next(),
  });
}

function voltar() {
  router.visit(route('pmda.planos.index'));
}
</script>

<template>
  <Head :title="`PMDA — ${dados.protocolo ?? 'Plano'}`" />

  <div class="pb-6">
    <PageHeader
      :title="`PMDA ${dados.protocolo ?? ''}`"
      :icon="DocumentTextIcon"
      :icon-image="moduleIcon('pmda')"
      variant="gradient"
    >
      <template #actions>
        <PmdaStatusBadge :label="dados.status_label" :cor="dados.status_cor" />
      </template>
    </PageHeader>

    <!-- Somente-leitura: a situacao ja fechou o ciclo de edicao. Dizer isso na
         tela evita o usuario preencher tudo de novo para o backend recusar no
         salvar -- o middleware pmda.editavel barra do outro lado. -->
    <div
      v-if="!dados.editavel"
      class="mb-4 rounded-lg border border-slate-300 bg-slate-50 p-4 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-200"
    >
      <p class="font-semibold">Somente leitura — PMDA {{ (dados.status_label || '').toLowerCase() }}</p>
      <p class="mt-1">
        Este plano não aceita mais edição. Para alterar os dados, duplique o PMDA na
        listagem e envie um novo protocolo.
      </p>
    </div>

    <!-- Devolutiva da CEDEC: o motivo precisa estar na tela onde o municipio
         corrige, e nao so na serie historica que ele nao abre. -->
    <div
      v-if="dados.devolvido"
      class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
    >
      <p class="font-semibold">PMDA devolvido pela CEDEC-MG para alteração</p>
      <p v-if="dados.devolucao_motivo" class="mt-1">{{ dados.devolucao_motivo }}</p>
      <p class="mt-1 text-xs opacity-80">
        <span v-if="dados.devolucao_por">Por {{ dados.devolucao_por }}</span>
        <span v-if="dados.devolucao_por && devolucaoEm"> · </span>
        <span v-if="devolucaoEm">{{ devolucaoEm }}</span>
      </p>
      <p class="mt-2">Faça as correções e envie o PMDA novamente na aba Anexos.</p>
    </div>

    <div v-if="Object.keys(form.errors).length" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
      <p class="font-semibold">Não foi possível salvar. Corrija:</p>
      <ul class="ml-5 mt-1 list-disc">
        <li v-for="(msg, key) in form.errors" :key="key">{{ msg }}</li>
      </ul>
    </div>

    <RatTabs :tabs="tabsComBadge" :active-tab="activeTab" @tab-change="goTo">
      <!-- `disabled` no fieldset se propaga por ancestralidade no DOM, entao um
           atributo desliga os campos das sete abas sem prop nova em cada secao.
           `display: contents` mantem o layout identico ao de antes. -->
      <fieldset :disabled="!dados.editavel" class="contents">
      <PmdaInicioSection
        v-if="activeTab === 1"
        :form="form"
        :protocolo="dados.protocolo"
        :saving="form.processing"
        @next="salvarEAvancar"
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
        v-else-if="activeTab === 3"
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
        v-else-if="activeTab === 4"
        :plano="dados"
        @next="next"
        @prev="prev"
      />
      <PmdaDistribuicaoSection
        v-else-if="activeTab === 5"
        :plano="dados"
        :comunidades-disponiveis="comunidades_disponiveis"
        :solicitacoes="comunidade_solicitacoes"
        @next="next"
        @prev="prev"
      />
      <PmdaAcoesSection
        v-else-if="activeTab === 6"
        :form="form"
        :saving="form.processing"
        @next="salvarEAvancar"
        @prev="prev"
      />
      <PmdaAnexosSection
        v-else-if="activeTab === 7"
        :plano="dados"
        @prev="prev"
        @revisar="goTo(1)"
      />
      </fieldset>
    </RatTabs>
  </div>
</template>
