<script setup>
import { computed } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';
import { naoInformado } from '@/Composables/treinamento/exportarIndicadoresCsv';

/**
 * Relatorio do painel de Capacitacoes, salvo em PDF pela impressao do
 * navegador. Sem graficos: tabelas limpas imprimem melhor e carregam o numero
 * exato. `relatorio` e a prop lazy do servidor (tudo, sem paginar) e `filtros`
 * o texto do recorte, para quem le o papel saber que nao e o acervo inteiro.
 */
const props = defineProps({
  show:      { type: Boolean, default: false },
  relatorio: { type: Object, default: null },
  filtros:   { type: String, default: '' },
});

defineEmits(['close']);

const num = (v) => Number(v ?? 0).toLocaleString('pt-BR');
const dec = (v) => Number(v ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
const dash = (v) => (v === null || v === undefined || v === '' ? '—' : v);
const data = (iso) => (iso ? iso.split('-').reverse().join('/') : '');

const geradoEm = computed(() => (props.relatorio?.gerado_em ? new Date(props.relatorio.gerado_em) : new Date()).toLocaleString('pt-BR'));
const documentTitle = computed(() => `Indicadores das Capacitacoes - ${new Date().toISOString().slice(0, 10)}`);

const k = computed(() => props.relatorio?.kpis ?? {});

// Pares rotulo/valor, desenhados em duas colunas de pares por linha.
const indicadores = computed(() => [
  ['Total de capacitações', num(k.value.total_capacitacoes)],
  ['Total de participantes', num(k.value.total_participantes)],
  ['Municípios atendidos', num(k.value.municipios_atendidos)],
  ['REDECs atendidas', num(k.value.redecs_atendidas)],
  ['Participação feminina', `${dec(k.value.participacao_feminina_pct)}%`],
  ['Participação masculina', `${dec(k.value.participacao_masculina_pct)}%`],
  ['Capacitados por 100 mil hab.', k.value.capacitados_por_100k == null ? '—' : dec(k.value.capacitados_por_100k)],
  ['Municípios de MG atendidos', `${dec(k.value.pct_municipios_mg)}%`],
  ['REDECs atendidas (%)', `${dec(k.value.pct_redecs)}%`],
  ['Média de participantes por capacitação', dec(k.value.media_participantes_por_capacitacao)],
]);

const paresDeIndicadores = computed(() => {
  const pares = [];
  for (let i = 0; i < indicadores.value.length; i += 2) pares.push(indicadores.value.slice(i, i + 2));
  return pares;
});

const porSexo = computed(() => {
  const s = props.relatorio?.porSexo ?? {};
  return [
    ['Feminino', s.feminino ?? 0],
    ['Masculino', s.masculino ?? 0],
    ['Não informado', s.nao_informado ?? 0],
  ];
});

const periodo = (l) => {
  const ini = data(l.periodo_inicio);
  const fim = data(l.periodo_fim);
  return ini && fim && ini !== fim ? `${ini} a ${fim}` : (ini || fim || '—');
};
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Relatório de Indicadores das Capacitações"
    :document-title="documentTitle"
    @close="$emit('close')"
  >
    <div v-if="relatorio" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="GOVERNO DO ESTADO DE MINAS GERAIS - CEDEC"
          subtitulo="INDICADORES DAS CAPACITAÇÕES"
          :numero="geradoEm"
          label-numero="GERADO EM"
        >
          <div style="font-size: 0.8rem; text-align: center;">Diretoria de Educação em Proteção e Defesa Civil</div>
        </PrintHeader>

        <div class="card-body p-0">
          <PrintSection titulo="FILTROS APLICADOS">
            <table class="bos-table">
              <tr>
                <td class="field-value">{{ filtros || 'Nenhum' }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="INDICADORES PRINCIPAIS">
            <table class="bos-table">
              <tr v-for="(par, i) in paresDeIndicadores" :key="i">
                <template v-for="[rotulo, valor] in par" :key="rotulo">
                  <td class="field-label" width="30%">{{ rotulo.toUpperCase() }}</td>
                  <td class="field-value" width="20%" style="font-weight: bold;">{{ valor }}</td>
                </template>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="PARTICIPANTES POR REDEC">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="60%">REDEC</td>
                <td class="field-label" width="20%">CAPACITAÇÕES</td>
                <td class="field-label" width="20%">PARTICIPANTES</td>
              </tr>
              <tr v-for="r in relatorio.porRedec" :key="r.redec_id">
                <td class="field-value">{{ dash(r.nome) }}</td>
                <td class="field-value text-center">{{ num(r.capacitacoes) }}</td>
                <td class="field-value text-center">{{ num(r.participantes) }}</td>
              </tr>
              <tr v-if="!relatorio.porRedec?.length">
                <td class="field-value text-center" colspan="3">Nenhuma REDEC no recorte.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="MUNICÍPIOS COM MAIS CAPACITADOS (TOP 20)">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="6%">#</td>
                <td class="field-label" width="34%">MUNICÍPIO</td>
                <td class="field-label" width="30%">REDEC</td>
                <td class="field-label" width="15%">CAPACITAÇÕES</td>
                <td class="field-label" width="15%">PARTICIPANTES</td>
              </tr>
              <tr v-for="(m, i) in relatorio.rankingMunicipios" :key="m.municipio_id">
                <td class="field-value text-center">{{ i + 1 }}</td>
                <td class="field-value">{{ dash(m.nome) }}</td>
                <td class="field-value">{{ dash(m.redec) }}</td>
                <td class="field-value text-center">{{ num(m.capacitacoes) }}</td>
                <td class="field-value text-center">{{ num(m.participantes) }}</td>
              </tr>
              <tr v-if="!relatorio.rankingMunicipios?.length">
                <td class="field-value text-center" colspan="5">Nenhum município no recorte.</td>
              </tr>
              <tr v-if="relatorio.semMunicipio">
                <td class="field-label" colspan="3">EAD / SEM MUNICÍPIO</td>
                <td class="field-value text-center">{{ num(relatorio.semMunicipio.capacitacoes) }}</td>
                <td class="field-value text-center">{{ num(relatorio.semMunicipio.participantes) }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="CAPACITAÇÕES POR ANO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="40%">ANO</td>
                <td class="field-label" width="30%">CAPACITAÇÕES</td>
                <td class="field-label" width="30%">PARTICIPANTES</td>
              </tr>
              <tr v-for="a in relatorio.porAno" :key="a.ano">
                <td class="field-value">{{ a.ano }}</td>
                <td class="field-value text-center">{{ num(a.capacitacoes) }}</td>
                <td class="field-value text-center">{{ num(a.participantes) }}</td>
              </tr>
              <tr v-if="!relatorio.porAno?.length">
                <td class="field-value text-center" colspan="3">Sem capacitações no recorte.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="PERFIL POR SEXO">
            <table class="bos-table">
              <tr>
                <td v-for="[rotulo] in porSexo" :key="rotulo" class="field-label" width="33%">{{ rotulo.toUpperCase() }}</td>
              </tr>
              <tr>
                <td v-for="[rotulo, valor] in porSexo" :key="rotulo" class="field-value text-center" style="font-weight: bold;">{{ num(valor) }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection :titulo="`CAPACITAÇÕES REALIZADAS (${relatorio.linhas?.length ?? 0})`">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="16%">MUNICÍPIO</td>
                <td class="field-label" width="12%">REDEC</td>
                <td class="field-label" width="12%">MESORREGIÃO</td>
                <td class="field-label" width="22%">CAPACITAÇÃO</td>
                <td class="field-label" width="12%">PERÍODO</td>
                <td class="field-label" width="6%">PART.</td>
                <td class="field-label" width="6%">MASC.</td>
                <td class="field-label" width="6%">FEM.</td>
                <td class="field-label" width="8%">N. INF.</td>
              </tr>
              <tr v-for="(l, i) in relatorio.linhas" :key="i">
                <td class="field-value">{{ l.municipio ?? 'EAD / sem município' }}</td>
                <td class="field-value">{{ dash(l.redec) }}</td>
                <td class="field-value">{{ dash(l.mesorregiao) }}</td>
                <td class="field-value">{{ l.treinamento }}</td>
                <td class="field-value">{{ periodo(l) }}</td>
                <td class="field-value text-center">{{ num(l.participantes) }}</td>
                <td class="field-value text-center">{{ num(l.masculino) }}</td>
                <td class="field-value text-center">{{ num(l.feminino) }}</td>
                <td class="field-value text-center">{{ num(naoInformado(l)) }}</td>
              </tr>
              <tr v-if="!relatorio.linhas?.length">
                <td class="field-value text-center" colspan="9">Nenhuma capacitação no recorte.</td>
              </tr>
            </table>
          </PrintSection>
        </div>
      </div>
    </div>
  </BasePrintModal>
</template>
