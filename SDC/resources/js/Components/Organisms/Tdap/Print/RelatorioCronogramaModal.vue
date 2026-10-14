<script setup>
import { computed, ref, watch } from 'vue';
import BasePrintModal from '@/Components/Organisms/Print/BasePrintModal.vue';
import PrintHeader from '@/Components/Organisms/Print/Sections/PrintHeader.vue';
import PrintSection from '@/Components/Organisms/Print/Sections/PrintSection.vue';
import { useToast } from '@/Composables/useToast';
import { formatDate } from '@/utils/dateFormatter';

/**
 * Relatorio do cronograma TDAP (#62), salvo em PDF pela impressao do
 * navegador. Tres perspectivas, vindas de GET tdap.cronogramas.relatorio:
 * - cedec:     gestao (execucao, decisao do municipio, valor liberado);
 * - prestador: Prestador/COMPDEC (atesto viagem a viagem, duas assinaturas);
 * - copasa:    captacao (pontos, frota e volume captado por dia).
 */
const props = defineProps({
  show:        { type: Boolean, default: false },
  cronogramaId: { type: Number, default: null },
});

defineEmits(['close']);

const PERSPECTIVAS = [
  { valor: 'cedec', rotulo: 'CEDEC', titulo: 'RELATORIO GERENCIAL - CEDEC-MG' },
  { valor: 'prestador', rotulo: 'Prestador / COMPDEC', titulo: 'RELATORIO DE ATESTO - PRESTADOR / COMPDEC' },
  { valor: 'copasa', rotulo: 'COPASA', titulo: 'RELATORIO DE CAPTACAO - COPASA' },
];

const { show: toast } = useToast();
const perspectiva = ref(null);
const dados = ref(null);
const carregando = ref(false);

const c = computed(() => dados.value?.cronograma ?? {});
const r = computed(() => dados.value?.relatorio ?? { resumo: {}, diario: [], viagens: [] });
const pontos = computed(() => dados.value?.pontos_captacao ?? []);
const caminhoes = computed(() => c.value.caminhoes ?? []);
const atual = computed(() => PERSPECTIVAS.find((p) => p.valor === r.value.perspectiva) ?? PERSPECTIVAS[0]);
const documentTitle = computed(() => `Relatorio TDAP ${atual.value.rotulo} - ${c.value.numero ?? ''}`);
const municipio = computed(() => (c.value.municipio ? [c.value.municipio.nome, c.value.municipio.uf].filter(Boolean).join(' / ') : '—'));

const STATUS = { aprovada: 'Aprovada', rejeitada: 'Rejeitada', pendente: 'Pendente' };
const CONFIRMACAO = { confirmada: 'Confirmada', reprovada: 'Reprovada', pendente: 'Aguardando' };

async function carregar(p = null) {
  if (!props.cronogramaId) return;
  carregando.value = true;
  try {
    const url = route('tdap.cronogramas.relatorio', props.cronogramaId) + (p ? `?perspectiva=${p}` : '');
    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    dados.value = await res.json();
    perspectiva.value = dados.value.relatorio.perspectiva;
  } catch (_) {
    toast('Não foi possível gerar o relatório do cronograma.', 'error');
  } finally {
    carregando.value = false;
  }
}

watch(() => [props.show, props.cronogramaId], ([aberto]) => {
  if (aberto) carregar();
  else { dados.value = null; perspectiva.value = null; }
}, { immediate: true });

function trocar(p) {
  if (p !== perspectiva.value) carregar(p);
}

function num(v, casas = 2) {
  return Number(v ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
}
function moeda(v) {
  return Number(v ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}
function periodo(i, f) {
  return i || f ? `${formatDate(i) || '—'} a ${formatDate(f) || '—'}` : '—';
}
function dash(v) {
  return v === null || v === undefined || v === '' ? '—' : v;
}
</script>

<template>
  <BasePrintModal
    :show="show"
    title="Relatório do Cronograma (PDF)"
    :document-title="documentTitle"
    :loading="carregando && !dados"
    @close="$emit('close')"
  >
    <template #toolbar>
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Perspectiva:</span>
        <button
          v-for="p in PERSPECTIVAS"
          :key="p.valor"
          type="button"
          class="px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors"
          :class="perspectiva === p.valor
            ? 'bg-sky-600 text-white border-sky-600'
            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600'"
          :disabled="carregando"
          @click="trocar(p.valor)"
        >
          {{ p.rotulo }}
        </button>
        <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto">Use “Imprimir” e escolha “Salvar como PDF”.</span>
      </div>
    </template>

    <div v-if="dados" class="container mx-auto">
      <div class="card border-2 border-black">
        <PrintHeader
          titulo="SISTEMA INTEGRADO DE DEFESA CIVIL"
          :subtitulo="atual.titulo"
          :numero="c.numero"
          label-numero="CRONOGRAMA"
        />

        <div class="card-body p-0">
          <PrintSection titulo="IDENTIFICACAO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="20%">MUNICIPIO</td>
                <td class="field-value" width="30%">{{ municipio }}</td>
                <td class="field-label" width="20%">VIGENCIA</td>
                <td class="field-value" width="30%">{{ periodo(c.dt_inicio_efetiva ?? c.dt_inicio, c.dt_final_efetiva ?? c.dt_final) }}</td>
              </tr>
              <tr>
                <td class="field-label">PRESTADOR</td>
                <td class="field-value">{{ dash(c.prestador?.nome) }}</td>
                <td class="field-label">CNPJ</td>
                <td class="field-value">{{ dash(c.prestador?.cnpj) }}</td>
              </tr>
              <tr>
                <td class="field-label">ATA / LOTE</td>
                <td class="field-value">{{ dash(c.ata?.numero) }} / {{ dash(c.lote?.numero) }}</td>
                <td class="field-label">SITUACAO</td>
                <td class="field-value">{{ dash((c.estado ?? '').toUpperCase()) }}</td>
              </tr>
            </table>
          </PrintSection>

          <!-- CEDEC: execucao e financeiro -->
          <PrintSection v-if="r.perspectiva === 'cedec'" titulo="EXECUCAO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="25%">VOLUME CONTRATADO (M3)</td>
                <td class="field-value" width="25%">{{ num(c.volume_contratado_m3) }}</td>
                <td class="field-label" width="25%">VOLUME APROVADO (M3)</td>
                <td class="field-value" width="25%">{{ num(r.resumo.m3_aprovado) }} ({{ num(c.execucao_percentual) }}%)</td>
              </tr>
              <tr>
                <td class="field-label">VIAGENS REGISTRADAS</td>
                <td class="field-value">{{ r.resumo.total }}</td>
                <td class="field-label">APROVADAS / REJEITADAS / PENDENTES</td>
                <td class="field-value">{{ r.resumo.aprovadas }} / {{ r.resumo.rejeitadas }} / {{ r.resumo.pendentes_validacao }}</td>
              </tr>
              <tr>
                <td class="field-label">CONFIRMACAO DO MUNICIPIO</td>
                <td class="field-value" colspan="3">
                  {{ r.resumo.confirmadas }} confirmadas, {{ r.resumo.reprovadas }} reprovadas, {{ r.resumo.pendentes_municipio }} aguardando
                </td>
              </tr>
              <tr>
                <td class="field-label">VALOR DO M3</td>
                <td class="field-value">{{ moeda(r.resumo.valor_m3) }}</td>
                <td class="field-label">VALOR LIBERADO (APROVADAS)</td>
                <td class="field-value"><strong>{{ moeda(r.resumo.valor_aprovado) }}</strong></td>
              </tr>
            </table>
          </PrintSection>

          <!-- PRESTADOR / COMPDEC: resumo do atesto -->
          <PrintSection v-if="r.perspectiva === 'prestador'" titulo="RESUMO DO ATESTO">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="25%">VIAGENS REGISTRADAS</td>
                <td class="field-value" width="25%">{{ r.resumo.total }}</td>
                <td class="field-label" width="25%">VOLUME REGISTRADO (M3)</td>
                <td class="field-value" width="25%">{{ num(r.resumo.m3_registrado) }}</td>
              </tr>
              <tr>
                <td class="field-label">CONFIRMADAS PELO MUNICIPIO</td>
                <td class="field-value">{{ r.resumo.confirmadas }}</td>
                <td class="field-label">REPROVADAS / AGUARDANDO</td>
                <td class="field-value">{{ r.resumo.reprovadas }} / {{ r.resumo.pendentes_municipio }}</td>
              </tr>
              <tr>
                <td class="field-label">VALIDADAS PELO ESTADO</td>
                <td class="field-value">{{ r.resumo.aprovadas }}</td>
                <td class="field-label">VOLUME VALIDADO (M3)</td>
                <td class="field-value">{{ num(r.resumo.m3_aprovado) }}</td>
              </tr>
            </table>
          </PrintSection>

          <!-- COPASA: captacao -->
          <template v-if="r.perspectiva === 'copasa'">
            <PrintSection titulo="PONTOS DE CAPTACAO">
              <table class="bos-table">
                <tr>
                  <td class="field-label" width="5%">#</td>
                  <td class="field-label" width="35%">PONTO</td>
                  <td class="field-label" width="20%">TIPO</td>
                  <td class="field-label" width="15%">CAPACIDADE (M3)</td>
                  <td class="field-label" width="25%">COORDENADAS</td>
                </tr>
                <tr v-for="(p, i) in pontos" :key="p.id">
                  <td class="field-value text-center">{{ i + 1 }}</td>
                  <td class="field-value">{{ dash(p.nome) }}</td>
                  <td class="field-value">{{ dash(p.tipo_nome) }}</td>
                  <td class="field-value">{{ Number(p.capacidade) > 0 ? num(p.capacidade) : '—' }}</td>
                  <td class="field-value">{{ p.latitude && p.longitude ? `${p.latitude}, ${p.longitude}` : '—' }}</td>
                </tr>
                <tr v-if="pontos.length === 0">
                  <td class="field-value text-center" colspan="5">Nenhum ponto de captacao vinculado.</td>
                </tr>
              </table>
            </PrintSection>
            <PrintSection titulo="FROTA QUE CAPTA">
              <table class="bos-table">
                <tr>
                  <td class="field-label" width="20%">PLACA</td>
                  <td class="field-label" width="40%">VEICULO</td>
                  <td class="field-label" width="20%">CAPACIDADE (M3)</td>
                  <td class="field-label" width="20%">VIAGENS PREVISTAS</td>
                </tr>
                <tr v-for="cc in caminhoes" :key="cc.id">
                  <td class="field-value">{{ dash(cc.placa) }}</td>
                  <td class="field-value">{{ dash(cc.marca_modelo) }}</td>
                  <td class="field-value">{{ num(cc.capacidade_m3) }}</td>
                  <td class="field-value">{{ cc.num_viagens }}</td>
                </tr>
                <tr v-if="caminhoes.length === 0">
                  <td class="field-value text-center" colspan="4">Nenhum caminhao alocado.</td>
                </tr>
              </table>
            </PrintSection>
          </template>

          <PrintSection :titulo="r.perspectiva === 'copasa' ? 'VOLUME CAPTADO POR DIA' : 'VIAGENS POR DIA'">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="18%">DATA</td>
                <td class="field-label" width="12%">VIAGENS</td>
                <td class="field-label" width="12%">APROVADAS</td>
                <td class="field-label" width="16%">VOLUME (M3)</td>
                <td class="field-label" width="42%">PLACAS</td>
              </tr>
              <tr v-for="d in r.diario" :key="d.data">
                <td class="field-value">{{ formatDate(d.data) }}</td>
                <td class="field-value">{{ d.viagens }}</td>
                <td class="field-value">{{ d.aprovadas }}</td>
                <td class="field-value">{{ num(d.m3) }}</td>
                <td class="field-value">{{ d.placas.join(', ') || '—' }}</td>
              </tr>
              <tr v-if="r.diario.length === 0">
                <td class="field-value text-center" colspan="5">Nenhuma viagem registrada.</td>
              </tr>
            </table>
          </PrintSection>

          <PrintSection v-if="r.viagens.length" titulo="VIAGENS">
            <table class="bos-table">
              <tr>
                <td class="field-label" width="12%">DATA</td>
                <td class="field-label" width="12%">PLACA</td>
                <td class="field-label" width="10%">M3</td>
                <td class="field-label" width="14%">ESTADO</td>
                <td class="field-label" width="14%">MUNICIPIO</td>
                <td class="field-label" width="38%">OBSERVACAO</td>
              </tr>
              <tr v-for="v in r.viagens" :key="v.id">
                <td class="field-value">{{ formatDate(v.data) }}</td>
                <td class="field-value">{{ dash(v.placa) }}</td>
                <td class="field-value">{{ num(v.m3) }}</td>
                <td class="field-value">{{ STATUS[v.status] ?? v.status }}</td>
                <td class="field-value">{{ CONFIRMACAO[v.status_confirmacao] ?? v.status_confirmacao }}</td>
                <td class="field-value">{{ dash(v.obs) }}</td>
              </tr>
            </table>
          </PrintSection>

          <table class="bos-table">
            <tr v-if="r.perspectiva === 'prestador'">
              <td class="field-value" width="50%" style="height: 60px; text-align: center; vertical-align: bottom;">
                ______________________________________<br>PRESTADOR DE SERVICO
              </td>
              <td class="field-value" width="50%" style="height: 60px; text-align: center; vertical-align: bottom;">
                ______________________________________<br>COORDENADOR(A) COMPDEC
              </td>
            </tr>
            <tr v-else-if="r.perspectiva === 'copasa'">
              <td class="field-value" width="50%" style="height: 60px; text-align: center; vertical-align: bottom;">
                ______________________________________<br>RESPONSAVEL COPASA
              </td>
              <td class="field-value" width="50%" style="height: 60px; text-align: center; vertical-align: bottom;">
                ______________________________________<br>RESPONSAVEL CEDEC-MG
              </td>
            </tr>
            <tr v-else>
              <td class="field-value" style="height: 60px; text-align: center; vertical-align: bottom;">
                ______________________________________<br>RESPONSAVEL CEDEC-MG
              </td>
            </tr>
          </table>

          <div style="padding: 8px 10px; font-size: 8px; color: #555; border: 1px solid #000; border-top: none;">
            Documento gerado em {{ new Date().toLocaleString('pt-BR') }} pelo Sistema Integrado de Defesa Civil - CEDEC-MG.
          </div>
        </div>
      </div>
    </div>
  </BasePrintModal>
</template>
