<template>
  <Head title="TDAP — Dashboard" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      title="TDAP — Transporte e Distribuição de Água Potável"
      :description="escopo
        ? `Dados do município de ${escopo.municipio_nome}, conforme sua lotação`
        : 'Gestão de cronogramas, prestadores, caminhões-tanque, vistorias e viagens'"
      :icon="TruckIcon"
      :icon-image="moduleIcon('tdap')"
    />

    <!-- KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
      <StatCard
        title="Cronogramas ativos"
        :value="kpis.cronogramas_ativos"
        :icon="CheckCircleIcon"
        variant="success"
        :clickable="pode.cronogramas"
        @click="router.visit(linkCronogramas('ativo'))"
      />
      <StatCard
        title="Encerrados"
        :value="kpis.cronogramas_encerrados"
        :icon="CheckIcon"
        variant="info"
        :clickable="pode.cronogramas"
        @click="router.visit(linkCronogramas('encerrado'))"
      />
      <StatCard
        title="Rascunhos"
        :value="kpis.cronogramas_rascunhos"
        :icon="DocumentTextIcon"
        variant="warning"
        :clickable="pode.cronogramas"
        @click="router.visit(linkCronogramas('rascunho'))"
      />
      <StatCard
        title="Volume ativo (m³)"
        :value="m3(kpis.volume_ativo_m3)"
        :subtitle="`${m3(kpis.volume_entregue_m3)} entregues · ${m3(kpis.m3_entregues_mes)} neste mês`"
        :icon="CubeIcon"
        variant="info"
        :format-number="false"
        :clickable="pode.cronogramas"
        @click="router.visit(linkCronogramas('ativo'))"
      />
      <StatCard
        title="Prestadores ativos"
        :value="kpis.prestadores_ativos"
        :subtitle="`${kpis.prestadores_em_operacao} com cronograma ativo`"
        :icon="BuildingIcon"
        variant="success"
        :clickable="pode.prestadores"
        @click="router.visit(route('tdap.prestadores.index', { ativo: 1 }))"
      />
      <StatCard
        title="Viagens p/ validar"
        :value="kpis.viagens_pendentes_validar"
        :icon="ClockIcon"
        variant="warning"
        :clickable="pode.validarViagens && kpis.viagens_pendentes_validar > 0"
        @click="router.visit(route('tdap.viagens.pendentes'))"
      />
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
      <EntregasRealizadasPainel
        class="xl:col-span-2"
        :entregas="entregas"
        :carregando="carregandoEntregas"
        @update:periodo="trocarPeriodo"
      />
      <CoberturaRegionalPainel :cobertura="cobertura" :link-cronogramas="pode.cronogramas ? linkCronogramas('ativo') : ''" />
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-5">
      <CronogramasAtivosPainel
        :class="pode.historico ? 'xl:col-span-3' : 'xl:col-span-5'"
        :cronogramas="cronogramasAtivos"
        :total-ativos="kpis.cronogramas_ativos"
        :pode-abrir="pode.cronogramas"
        :link-todos="linkCronogramas('ativo')"
      />
      <AtividadeRecentePainel
        v-if="pode.historico"
        class="xl:col-span-2"
        :eventos="eventosRecentes"
        :viagens-pendentes="pode.validarViagens ? kpis.viagens_pendentes_validar : 0"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import EntregasRealizadasPainel from '@/Components/Organisms/Tdap/Dashboard/EntregasRealizadasPainel.vue';
import CoberturaRegionalPainel from '@/Components/Organisms/Tdap/Dashboard/CoberturaRegionalPainel.vue';
import CronogramasAtivosPainel from '@/Components/Organisms/Tdap/Dashboard/CronogramasAtivosPainel.vue';
import AtividadeRecentePainel from '@/Components/Organisms/Tdap/Dashboard/AtividadeRecentePainel.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  kpis: {
    type: Object,
    required: true,
    default: () => ({
      cronogramas_ativos: 0,
      cronogramas_encerrados: 0,
      cronogramas_rascunhos: 0,
      m3_entregues_mes: 0,
      volume_ativo_m3: 0,
      volume_entregue_m3: 0,
      prestadores_ativos: 0,
      prestadores_em_operacao: 0,
      viagens_pendentes_validar: 0,
    }),
  },
  eventosRecentes:   { type: Array, default: () => [] },
  cronogramasAtivos: { type: Array, default: () => [] },
  entregas:          { type: Object, required: true },
  cobertura:         { type: Object, required: true },
  /** null = estado inteiro; senao, o municipio a que os numeros se limitam. */
  escopo:            { type: Object, default: null },
});

// Atalho so para quem pode abrir o destino: card clicavel que leva a um 403
// e pior que card parado. Mesmas permissoes das rotas em routes/modules/tdap.php.
const { can } = usePermissions();
const pode = computed(() => ({
  cronogramas: can('tdap.cronogramas.view'),
  prestadores: can('tdap.prestadores.view'),
  validarViagens: can('tdap.viagens.validar'),
  historico: can('tdap.historico.view'),
}));

// Listagem de Cronogramas no mesmo recorte do numero clicado: estado do card e,
// para quem e lotado em municipio, o proprio municipio (a listagem e estadual).
function linkCronogramas(estado) {
  return route('tdap.cronogramas.index', {
    estado,
    ...(props.escopo ? { municipio_id: props.escopo.municipio_id } : {}),
  });
}

const m3 = (valor) => Number(valor || 0).toLocaleString('pt-BR', { maximumFractionDigits: 0 });

// Tempo real: qualquer mudanca no modulo (viagem, alocacao, cronograma,
// prestador, historico) avisa o canal e o painel se rebusca pelo controller --
// mesmo recorte, mesmas permissoes. O reload mantem o ?periodo= da URL.
useAtualizacaoAoVivo({
  canal: 'listagem.tdap',
  evento: '.RecursoAtualizado',
  props: ['kpis', 'eventosRecentes', 'cronogramasAtivos', 'entregas', 'cobertura'],
});

const carregandoEntregas = ref(false);

// So a serie do grafico volta do servidor; o periodo fica na URL para
// sobreviver ao F5 e ao link compartilhado.
function trocarPeriodo(periodo) {
  router.reload({
    only: ['entregas'],
    data: { periodo },
    onStart: () => { carregandoEntregas.value = true; },
    onFinish: () => { carregandoEntregas.value = false; },
  });
}
</script>
