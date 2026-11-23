<script setup>
import { computed } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';
import { naoInformado } from '@/Composables/treinamento/exportarIndicadoresCsv';
import { SEXOS, SITUACOES, formatarPeriodo, rotuloMes } from '@/Composables/treinamento/useTemaGrafico';

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

const geradoEm = computed(() => (props.relatorio?.gerado_em ? new Date(props.relatorio.gerado_em) : new Date()).toLocaleString('pt-BR'));
const documentTitle = computed(() => `Indicadores das Capacitacoes - ${new Date().toISOString().slice(0, 10)}`);

const k = computed(() => props.relatorio?.kpis ?? {});

// Pares rotulo/valor, desenhados em duas colunas de pares por linha.
const indicadores = computed(() => [
  ['Total de participantes', num(k.value.total_participantes)],
  ['Turmas', num(k.value.total_capacitacoes)],
  ['Concluídos', num(k.value.concluidos)],
  ['Em andamento', num(k.value.em_andamento)],
  ['Desistentes', num(k.value.desistentes)],
  ['Taxa de desistência', `${dec(k.value.taxa_desistencia_pct)}%`],
  ['Municípios atendidos', `${num(k.value.municipios_atendidos)} de ${num(k.value.municipios_total)}`],
  ['Municípios de MG atendidos', `${dec(k.value.pct_municipios_mg)}%`],
  ['REDECs atendidas', `${num(k.value.redecs_atendidas)} de ${num(k.value.redecs_total)}`],
  ['REDECs atendidas (%)', `${dec(k.value.pct_redecs)}%`],
  ['Participação feminina', `${dec(k.value.participacao_feminina_pct)}%`],
  ['Participação masculina', `${dec(k.value.participacao_masculina_pct)}%`],
  ['Capacitados por 100 mil hab.', k.value.capacitados_por_100k == null ? '—' : dec(k.value.capacitados_por_100k)],
  ['Média de participantes por turma', dec(k.value.media_participantes_por_capacitacao)],
]);

const paresDeIndicadores = computed(() => {
  const pares = [];
  for (let i = 0; i < indicadores.value.length; i += 2) pares.push(indicadores.value.slice(i, i + 2));
  return pares;
});

const porSexo = computed(() => {
  const s = props.relatorio?.porSexo ?? {};
  return SEXOS.map((sexo) => [sexo.nome, s[sexo.chave] ?? 0]);
});

const satisfacao = computed(() => props.relatorio?.satisfacao ?? null);

// Mesmo criterio do painel: com curso/turma filtrado compara turmas, sem ele compara cursos.
const rankingSatisfacao = computed(() => {
  const s = satisfacao.value ?? {};
  return s.por_turma?.length
    ? s.por_turma.map((t) => ({ chave: `t:${t.treinamento_id}`, nome: t.versao ? `${t.titulo} (${t.versao})` : t.titulo, n: t.n, media: t.media }))
    : (s.por_curso ?? []).map((c, i) => ({ chave: `c:${c.curso_id ?? i}`, nome: c.nome, n: c.n, media: c.media }));
});

const media = (v) => (v === null || v === undefined ? '—' : dec(v));
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
                <td class="field-label" width="40%">REDEC</td>
                <td class="field-label" width="20%">MUNICÍPIOS ATENDIDOS</td>
                <td class="field-label" width="20%">TURMAS</td>
                <td class="field-label" width="20%">PARTICIPANTES</td>
              </tr>
              <tr v-for="r in relatorio.porRedec" :key="r.redec_id">
                <td class="field-value">{{ dash(r.nome) }}</td>
                <td class="field-value text-center">{{ num(r.municipios_atendidos) }} de {{ num(r.municipios_total) }}</td>
                <td class="field-value text-center">{{ num(r.capacitacoes) }}</td>
                <td class="field-value text-center">{{ num(r.participantes) }}</td>
              </tr>
              <tr v-if="!relatorio.porRedec?.length">
                <td class="field-value text-center" colspan="4">Nenhuma REDEC no recorte.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="MUNICÍPIOS COM MAIS CAPACITADOS (TOP 20)">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="6%">#</td>
                <td class="field-label" width="34%">MUNICÍPIO</td>
                <td class="field-label" width="30%">REDEC</td>
                <td class="field-label" width="15%">TURMAS</td>
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

          <PrintSection titulo="TURMAS POR ANO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="40%">ANO</td>
                <td class="field-label" width="30%">TURMAS</td>
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

          <PrintSection titulo="PARTICIPAÇÕES POR MÊS">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">MÊS</td>
                <td class="field-label" width="20%">PARTICIPANTES</td>
                <td v-for="s in SITUACOES" :key="s.chave" class="field-label" width="20%">{{ s.nome.toUpperCase() }}</td>
              </tr>
              <tr v-for="m in relatorio.porMes ?? []" :key="m.mes">
                <td class="field-value">{{ rotuloMes(m.mes) }}</td>
                <td class="field-value text-center">{{ num(m.participantes) }}</td>
                <td v-for="s in SITUACOES" :key="s.chave" class="field-value text-center">{{ num(m[s.chave]) }}</td>
              </tr>
              <tr v-if="!relatorio.porMes?.length">
                <td class="field-value text-center" colspan="5">Sem participações no período.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="PERFIL POR SEXO">
            <table class="bos-table">
              <tr>
                <td v-for="[rotulo] in porSexo" :key="rotulo" class="field-label" width="20%">{{ rotulo.toUpperCase() }}</td>
              </tr>
              <tr>
                <td v-for="[rotulo, valor] in porSexo" :key="rotulo" class="field-value text-center" style="font-weight: bold;">{{ num(valor) }}</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="PERFIL POR FAIXA ETÁRIA">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="60%">FAIXA ETÁRIA (IDADE NA INSCRIÇÃO)</td>
                <td class="field-label" width="40%">PARTICIPANTES</td>
              </tr>
              <tr v-for="f in relatorio.porFaixaEtaria ?? []" :key="f.faixa">
                <td class="field-value">{{ f.rotulo }}</td>
                <td class="field-value text-center">{{ num(f.total) }}</td>
              </tr>
              <tr v-if="!relatorio.porFaixaEtaria?.length">
                <td class="field-value text-center" colspan="2">Sem participantes no recorte.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection titulo="SATISFAÇÃO (AVALIAÇÃO DOS CONCLUINTES)">
            <table v-if="satisfacao?.n" class="bos-table">
              <tr>
                <td class="field-label" width="30%">AVALIAÇÕES</td>
                <td class="field-value" width="20%" style="font-weight: bold;">{{ num(satisfacao.n) }}</td>
                <td class="field-label" width="30%">MÉDIA (1 A 5)</td>
                <td class="field-value" width="20%" style="font-weight: bold;">{{ media(satisfacao.media) }}</td>
              </tr>
              <tr>
                <td class="field-label" colspan="2">NOTA</td>
                <td class="field-label">AVALIAÇÕES</td>
                <td class="field-label">%</td>
              </tr>
              <tr v-for="d in satisfacao.distribuicao ?? []" :key="d.nota">
                <td class="field-value" colspan="2">{{ d.nota }} - {{ d.rotulo }}</td>
                <td class="field-value text-center">{{ num(d.total) }}</td>
                <td class="field-value text-center">{{ dec(d.pct) }}%</td>
              </tr>
              <template v-if="rankingSatisfacao.length">
                <tr>
                  <td class="field-label" colspan="2">{{ satisfacao.por_turma?.length ? 'TURMA' : 'CURSO' }}</td>
                  <td class="field-label">AVALIAÇÕES</td>
                  <td class="field-label">MÉDIA</td>
                </tr>
                <tr v-for="r in rankingSatisfacao" :key="r.chave">
                  <td class="field-value" colspan="2">{{ dash(r.nome) }}</td>
                  <td class="field-value text-center">{{ num(r.n) }}</td>
                  <td class="field-value text-center">{{ media(r.media) }}</td>
                </tr>
              </template>
            </table>
            <table v-else class="bos-table">
              <tr>
                <td class="field-value text-center">Sem avaliações no recorte.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection :titulo="`CAPACITAÇÕES REALIZADAS (${relatorio.linhas?.length ?? 0})`">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="13%">MUNICÍPIO</td>
                <td class="field-label" width="10%">REDEC</td>
                <td class="field-label" width="10%">MESORREGIÃO</td>
                <td class="field-label" width="19%">CAPACITAÇÃO</td>
                <td class="field-label" width="12%">PERÍODO</td>
                <td class="field-label" width="5%">PART.</td>
                <td class="field-label" width="5%">CONCL.</td>
                <td class="field-label" width="5%">EM AND.</td>
                <td class="field-label" width="5%">DESIST.</td>
                <td class="field-label" width="5%">MASC.</td>
                <td class="field-label" width="5%">FEM.</td>
                <td class="field-label" width="6%">OUTROS / N. INF.</td>
              </tr>
              <tr v-for="(l, i) in relatorio.linhas" :key="i">
                <td class="field-value">{{ l.municipio ?? 'EAD / sem município' }}</td>
                <td class="field-value">{{ dash(l.redec) }}</td>
                <td class="field-value">{{ dash(l.mesorregiao) }}</td>
                <td class="field-value">{{ l.treinamento }}</td>
                <td class="field-value">{{ formatarPeriodo(l.periodo_inicio, l.periodo_fim) }}</td>
                <td class="field-value text-center">{{ num(l.participantes) }}</td>
                <td class="field-value text-center">{{ num(l.concluidos) }}</td>
                <td class="field-value text-center">{{ num(l.em_andamento) }}</td>
                <td class="field-value text-center">{{ num(l.desistentes) }}</td>
                <td class="field-value text-center">{{ num(l.masculino) }}</td>
                <td class="field-value text-center">{{ num(l.feminino) }}</td>
                <td class="field-value text-center">{{ num(naoInformado(l)) }}</td>
              </tr>
              <tr v-if="!relatorio.linhas?.length">
                <td class="field-value text-center" colspan="12">Nenhuma capacitação no recorte.</td>
              </tr>
            </table>
          </PrintSection>
        </div>
      </div>
    </div>
  </BasePrintModal>
</template>
