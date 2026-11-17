<template>
  <Head title="Consulta para edital" />
  <div class="w-full space-y-4 pb-8">
    <PageHeader
      title="Consulta para edital"
      description="Pessoas e municípios que concluíram os cursos do catálogo"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
      :espaco-inferior="false"
    />

    <ConsultaEditalFiltros :filtros="filtros" :opcoes="opcoes" :carregando="carregando" @consultar="consultar" />

    <TdapPainel v-if="temConsulta" rotulo="Resultado" titulo="Resumo" :subtitulo="resumo">
      <template #acoes>
        <a
          :href="urlExportar"
          class="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          <ArrowDownTrayIcon class="h-4 w-4" />
          Exportar CSV
        </a>
      </template>

      <div class="flex border-b border-slate-200 dark:border-slate-700/40" role="tablist">
        <button
          v-for="aba in ABAS"
          :key="aba.valor"
          type="button"
          role="tab"
          :aria-selected="abaAtiva === aba.valor"
          class="min-h-[44px] flex-1 border-b-2 px-4 text-sm font-medium transition-colors sm:flex-none"
          :class="abaAtiva === aba.valor
            ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
          @click="abaAtiva = aba.valor"
        >
          {{ aba.rotulo }}
        </button>
      </div>

      <div :class="{ 'opacity-60 transition-opacity': carregando }" :aria-busy="carregando">
        <ConsultaEditalPessoas v-if="abaAtiva === 'pessoas'" :pessoas="pessoas" @pagina="irParaPagina" />
        <ConsultaEditalMunicipios v-else :linhas="municipios" />
      </div>
    </TdapPainel>
  </div>
</template>

<script setup>
/**
 * Consulta para edital: escolhe-se curso(s) do catalogo e a tela lista quem
 * concluiu (pessoas) e de onde (municipios). Os filtros vivem na URL; o CSV
 * sai do servidor com o mesmo recorte e o CPF mascarado.
 */
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import ConsultaEditalFiltros from '@/Components/Organisms/Treinamento/ConsultaEdital/ConsultaEditalFiltros.vue';
import ConsultaEditalPessoas from '@/Components/Organisms/Treinamento/ConsultaEdital/ConsultaEditalPessoas.vue';
import ConsultaEditalMunicipios from '@/Components/Organisms/Treinamento/ConsultaEdital/ConsultaEditalMunicipios.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { formatarNumero } from '@/Composables/treinamento/useTemaGrafico';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  filtros: { type: Object, default: () => ({ cursos: [], modo: 'todos', redec_id: null, municipio_id: null }) },
  opcoes: { type: Object, default: () => ({ cursos: [], redecs: [], municipios: [] }) },
  pessoas: { type: Object, default: () => ({ data: [], meta: null }) },
  municipios: { type: Array, default: () => [] },
  total_pessoas: { type: Number, default: 0 },
  total_municipios: { type: Number, default: 0 },
});

const ABAS = [
  { valor: 'pessoas', rotulo: 'Pessoas' },
  { valor: 'municipios', rotulo: 'Municípios' },
];

const abaAtiva = ref('pessoas');
const carregando = ref(false);

const temConsulta = computed(() => (props.filtros.cursos ?? []).length > 0);

const rotulo = (qtd, singular, plural) => `${formatarNumero(qtd)} ${qtd === 1 ? singular : plural}`;
const resumo = computed(() => `${rotulo(props.total_pessoas, 'pessoa', 'pessoas')} em ${rotulo(props.total_municipios, 'município', 'municípios')}`);

// So vai para a URL o filtro preenchido.
function parametrosDe(f) {
  const parametros = { cursos: f.cursos, modo: f.modo };
  if (f.redec_id) parametros.redec_id = f.redec_id;
  if (f.municipio_id) parametros.municipio_id = f.municipio_id;
  return parametros;
}

const urlExportar = computed(() => route('treinamentos.consulta-edital.exportar', parametrosDe(props.filtros)));

function recarregar(parametros) {
  router.get(route('treinamentos.consulta-edital'), parametros, {
    only: ['filtros', 'pessoas', 'municipios', 'total_pessoas', 'total_municipios'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onStart: () => { carregando.value = true; },
    onFinish: () => { carregando.value = false; },
  });
}

const consultar = (filtros) => recarregar(parametrosDe(filtros));

const irParaPagina = (pagina) => recarregar({ ...parametrosDe(props.filtros), page: pagina });
</script>
